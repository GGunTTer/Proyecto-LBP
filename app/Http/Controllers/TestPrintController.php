<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Throwable;

class TestPrintController extends Controller
{
    public function testprint()
    {
        $printerName = config('printing.default_printer', env('PRINTER_NAME', 'lp0'));

        try {
            $connector = new WindowsPrintConnector($printerName);

            $printer = new Printer($connector);
            $printer->text("Hola Mundo desde Laravel\n");
            $printer->cut();
            $printer->close();

            return response("Impresión enviada a '{$printerName}'", Response::HTTP_OK);
        } catch (Throwable $e) {
            // Cierra la impresora si alcanzó a crearse
            try { if (isset($printer)) { $printer->close(); } } catch (Throwable $ignore) {}

            return response("Error: ".$e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
