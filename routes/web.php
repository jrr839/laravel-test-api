<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

// API Documentation routes (protected)
Route::middleware(['auth', 'verified', 'can.view.docs'])->group(function () {
    Route::get('/docs', function () {
        return response()->file(public_path('docs/index.html'));
    })->name('scribe');

    Route::get('/docs.postman', function () {
        return response()->file(public_path('docs/collection.json'), [
            'Content-Type' => 'application/json',
        ]);
    })->name('scribe.postman');

    Route::get('/docs.openapi', function () {
        return response()->file(public_path('docs/openapi.yaml'), [
            'Content-Type' => 'text/yaml',
        ]);
    })->name('scribe.openapi');

    Route::get('/docs.json', function () {
        return response()->file(public_path('docs/collection.json'), [
            'Content-Type' => 'application/json',
        ]);
    })->name('docs.json');
});

require __DIR__.'/settings.php';
