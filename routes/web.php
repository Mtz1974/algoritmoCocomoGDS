<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CocomoController;

// Redirige la raíz al estimador
Route::get('/', fn() => redirect('/cocomo'));

// Pantalla principal (formulario)
Route::get('/cocomo', [CocomoController::class, 'index'])->name('cocomo.index');

// Calcular resultados (POST del formulario)
Route::post('/cocomo', [CocomoController::class, 'calculate'])->name('cocomo.calculate');

// Exportar resultados a PDF (usa los mismos datos del formulario)
Route::post('/cocomo/pdf', [CocomoController::class, 'exportPdf'])->name('cocomo.pdf');
