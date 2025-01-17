<?php

use App\Http\Controllers\Api\VertexPromptController;
use App\Http\Controllers\Api\WebScrapingController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    Route::get('/scrape', [WebScrapingController::class, 'scrape']);
    Route::post('/generate-vertex', [VertexPromptController::class, 'generate']);

}); 
