<?php
require base_path('routes/api.php');

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
