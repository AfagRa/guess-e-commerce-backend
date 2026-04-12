<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/debug', function () {
    try {
        $count = \App\Models\Product::count();
        return response()->json(['products' => $count, 'db' => 'connected']);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});