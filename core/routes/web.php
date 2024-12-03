<?php

use App\Livewire\Dashboard;
use App\Livewire\Display;
use App\Livewire\Preview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::redirect('/', '/dashboard');

Route::group(['middleware' => 'auth'], function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
});

Route::get('{resource:code}', Preview::class)->name('preview');
Route::get('{resource:code}.{ext}', Preview::class)->name('preview.ext');
//Route::get('{resource:code}/download', )->name('download');
