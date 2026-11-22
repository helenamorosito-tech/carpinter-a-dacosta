<?php

use App\Http\Controllers\CutSheetPreviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/admin/cut-sheet-preview/{token}', [CutSheetPreviewController::class, 'show'])
        ->name('cut-sheet.preview')
        ->whereUuid('token');

    Route::get('/admin/cut-sheet-download/{token}', [CutSheetPreviewController::class, 'download'])
        ->name('cut-sheet.download')
        ->whereUuid('token');
});
