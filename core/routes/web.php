<?php

use App\Livewire\Admin\Settings;
use App\Livewire\Dashboard;
use App\Livewire\Integrations;
use App\Livewire\Preview;
use App\Livewire\User\Profile;
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

Route::group(['middleware' => ['auth', 'verified']], static function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');
    Route::get('integrations', Integrations::class)->name('integrations');
    Route::get('settings', Settings::class)->name('admin.settings')->can('administrate');
    Route::get('profile', Profile::class)->name('user.profile');
});

Route::get('{resource:code}.{ext}', Preview::class)->name('preview.ext');
Route::get('{resource:code}', Preview::class)->name('preview');
// Route::get('{resource:code}/download', )->name('download');
