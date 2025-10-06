<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PrinterController;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;
use Native\Laravel\Facades\Notification;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;

Route::match(['GET','POST'], '/', [PrinterController::class, 'print']);


Route::get('/test-print', TestPrintController::class, 'test-print');




