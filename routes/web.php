<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PrinterController;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;
use Native\Laravel\Facades\Notification;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;

Route::match(['GET','POST'], '/', [PrinterController::class, 'print']);

Route::get('/print', function () {
    return view('welcome');
})->name('home');

Route::get('/test-print', function () {
    try {
        $connector = new WindowsPrintConnector("lp0");
        $printer = new Printer($connector);
        $printer->text("Hola Mundo desde Laravel\n");
        $printer->cut();
        $printer->close();
        return "Impresión enviada!";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});


Route::get('/notify-test', function () {
    try {
        Notification::title('Prueba de notificación')
            ->message('Si ves esto, NativePHP está ok')
            ->show();
        return 'OK';
    } catch (\Throwable $e) {
        Log::warning('notify-test failed', ['err' => $e->getMessage()]);
        return 'FAIL: '.$e->getMessage();
    }
});

