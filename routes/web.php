<?php

use App\Http\Controllers\ToyController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('toys.index');
});

Route::resource('toys', ToyController::class);
Route::post('toys/{toy}/search-ebay', [ToyController::class, 'searchEbay'])->name('toys.search-ebay');
Route::get('toys/export/csv', [ToyController::class, 'exportCsv'])->name('toys.export-csv');

