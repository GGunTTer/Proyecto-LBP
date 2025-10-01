<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Exception;
use BadMethodCallException;
use Throwable;
use Illuminate\Support\Facades\Log;

class PrinterController extends Controller
{
    private function runCopy(string $filename, string $device): bool
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $cmd = sprintf('copy /B %s %s', escapeshellarg($filename), escapeshellarg($device));
        } else {
            // Puedes ampliar aquí para otros SO si lo deseas.
            $cmd = sprintf('cp %s %s', escapeshellarg($filename), escapeshellarg($device));
        }

        exec($cmd, $output, $result);
        return $result === 0;
    }

    public function print(Request $request)
    {
        $validated = $request->validate([
            'rut0'      => 'required|string|min:7|max:10',
            'rut1'      => 'present|nullable|string|max:10',
            'codigosii' => 'string|required|min:2|max:3',
            'fecha'     => 'string|required|date_format:Y-m-d',
            'neto'      => 'required|integer|min:0',
            'exento'    => 'required|integer|min:0',
            'printer'   => 'required|integer|min:1',
            'id'        => 'required|integer|min:1'
        ]);

        $http = new Client;

        try {
            $response = $http->get('https://lioren.io/printing/thermal/json', [
                'query' => [
                    'rut0'      => $request->rut0,
                    'rut1'      => $request->rut1,
                    'codigosii' => $request->codigosii,
                    'fecha'     => $request->fecha,
                    'neto'      => $request->neto,
                    'exento'    => $request->exento,
                    'printer'   => $request->printer,
                    'id'        => $request->id,
                    'passcode'  => str_replace('=', '-', base64_encode(encrypt($request->id))),
                ],
                'verify' => false
            ]);

            $serverResponse = json_decode((string) $response->getBody(), true);

            if (isset($serverResponse['errors']) && count($serverResponse['errors']) > 0) {
                return $this->pushView(
                    title: 'Error de impresión',
                    body: 'Errores de validación remota.',
                    success: false,
                    httpCode: 422
                );
            }

            $dest      = $serverResponse['printer'] ?? '';
            $ubicacion = $serverResponse['ubicacion'] ?? '';
            $data      = base64_decode($serverResponse['data'] ?? '');

            // --- Validaciones de impresora ---
            if (preg_match("/^(LPT\d|COM\d)$/", $dest) === 1) {
                $hostname    = null;
                $printerName = $dest;
            } elseif (preg_match(
                "/^smb:\/\/([\s\d\w\-]+(:[\s\d\w\+\-]+)?@)?([\d\w\-]+\.)*[\d\w\-]+\/([\d\w\-]+\/)?[\d\w\-]+(\s[\d\w\-]+)*$/",
                $dest
            ) === 1) {
                $part        = parse_url($dest);
                $hostname    = $part['host'] ?? null;
                $path        = ltrim($part['path'] ?? '', '/');

                if (str_contains($path, "/")) {
                    [$workgroup, $printerName] = explode("/", $path, 2);
                } else {
                    $printerName = $path;
                }
                $userName     = $part['user'] ?? null;
                $userPassword = $part['pass'] ?? null;
            } elseif (preg_match("/^[\d\w-]+(\s[\d\w-]+)*$/", $dest) === 1) {
                $hostname    = gethostname() ?: 'localhost';
                $printerName = $dest;
            } else {
                throw new BadMethodCallException(
                    "Printer '{$dest}' is not valid. Use local port (LPT1, COM1, etc) or smb://computer/printer."
                );
            }

            $device = '//' . ($hostname ?? '') . '/' . $printerName;

            // --- Envío a impresora según SO ---
            if (env('PRINTER_OS') === 'windows') {
                $filename = tempnam(storage_path(), "escpos");
                file_put_contents($filename, $data);

                if (!$this->runCopy($filename, $device)) {
                    @unlink($filename);
                    throw new Exception("Failed to copy file to printer");
                }
                @unlink($filename);
            } elseif (env('PRINTER_OS') === 'osx') {
                $filename = tempnam(storage_path(), "escpos");
                file_put_contents($filename, $data);

                $cmd = sprintf(
                    'lpr -o raw%s -P %s %s 2>&1',
                    $ubicacion ? ' -H ' . escapeshellarg($ubicacion) : '',
                    escapeshellarg($dest),
                    escapeshellarg($filename)
                );

                exec($cmd, $retArr, $retVal);
                @unlink($filename);

                if ($retVal !== 0) {
                    throw new Exception("$cmd: " . implode($retArr));
                }
            } elseif (env('PRINTER_OS') === 'linux') {
                $filename = tempnam(storage_path(), "escpos");
                file_put_contents($filename, $data);

                if (!$this->runCopy($filename, $dest)) {
                    @unlink($filename);
                    throw new Exception("Failed to copy file to printer");
                }
                @unlink($filename);
            }

            return $this->pushView(
                title: 'Impresión completada',
                body: "Doc #{$validated['id']} enviado a '{$dest}'.",
                success: true
            );

        } catch (Throwable $e) {
            Log::error('Error al imprimir', ['ex' => $e->getMessage()]);
            return $this->pushView(
                title: 'Error de impresión',
                body: $e->getMessage(),
                success: false,
                httpCode: 500
            );
        }
    }
    
    private function pushView(string $title, string $body, bool $success, int $httpCode = 200)
    {
        return response()->view('print_result', [
            'title'     => $title,
            'body'      => $body,
            'timeoutMs' => $success ? 15000 : 20000,
            'vibrate'   => $success ? [100,100,100] : [200,80,200],
            'success'   => $success,
        ], $httpCode);
    }
}
