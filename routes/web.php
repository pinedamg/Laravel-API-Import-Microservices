<?php

use App\Http\Controllers\EventController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/events', [EventController::class, 'index']);
