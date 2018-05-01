<?php

Flight::route('GET /login', [\App\Controllers\LoginController::instance(), 'show']);
Flight::route('POST /login', [\App\Controllers\LoginController::instance(), 'login']);
Flight::route('GET|POST /logout', [\App\Controllers\LoginController::instance(), 'logout']);

Flight::route('GET /', [\App\Controllers\DashboardController::instance(), 'redirects']);
Flight::route('GET /home(/page/@page)', [\App\Controllers\DashboardController::instance(), 'home']);
Flight::route('GET /system', [\App\Controllers\DashboardController::instance(), 'system']);

Flight::route('GET /users(/page/@page)', [\App\Controllers\UserController::instance(), 'index']);
Flight::route('GET /user/add', [\App\Controllers\UserController::instance(), 'create']);
Flight::route('POST /user/add', [\App\Controllers\UserController::instance(), 'store']);
Flight::route('GET /user/@id/edit', [\App\Controllers\UserController::instance(), 'edit']);
Flight::route('POST /user/@id', [\App\Controllers\UserController::instance(), 'update']);
Flight::route('GET /user/@id/delete', [\App\Controllers\UserController::instance(), 'delete']);
Flight::route('GET /profile', [\App\Controllers\UserController::instance(), 'profile']);
Flight::route('POST /profile/@id/edit', [\App\Controllers\UserController::instance(), 'profileEdit']);
Flight::route('POST /user/@id/refreshToken', [\App\Controllers\UserController::instance(), 'refreshToken']);
Flight::route('GET /user/@id/config/sharex', [\App\Controllers\UserController::instance(), 'getShareXconfigFile']);

Flight::route('POST /upload', [\App\Controllers\UploadController::instance(), 'upload']);
Flight::route('POST /upload/@id/publish', [\App\Controllers\UploadController::instance(), 'togglePublish']);
Flight::route('POST /upload/@id/unpublish', [\App\Controllers\UploadController::instance(), 'togglePublish']);
Flight::route('GET /upload/@id/raw', [\App\Controllers\UploadController::instance(), 'getRawById']);
Flight::route('POST /upload/@id/delete', [\App\Controllers\UploadController::instance(), 'delete']);
Flight::route('GET /@userCode/@filename', [\App\Controllers\UploadController::instance(), 'show']);
Flight::route('GET /@userCode/@filename/raw', [\App\Controllers\UploadController::instance(), 'showRaw']);
Flight::route('GET /@userCode/@filename/download', [\App\Controllers\UploadController::instance(), 'download']);