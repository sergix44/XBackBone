<?php

use App\Http\Controllers\Api\V1\UploadController;

Route::post('upload', UploadController::class)->name('upload');
