<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PrinterController;
use App\Http\Controllers\TestPrintController;

Route::match(['GET','POST'], '/', [PrinterController::class, 'print']);


Route::get('/test-print', [TestPrintController::class, 'testprint'])->name('test.print');

