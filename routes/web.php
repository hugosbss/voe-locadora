<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\RegistrationController;
use App\Http\Controllers\Public\ClientRegistrationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Cadastro público (acesso pelo celular)
|--------------------------------------------------------------------------
*/
Route::get('/', [ClientRegistrationController::class, 'create'])->name('client-registrations.create');

Route::post('/cadastro', [ClientRegistrationController::class, 'store'])
    ->name('client-registrations.store');

Route::get('/sucesso', [ClientRegistrationController::class, 'success'])
    ->name('client-registrations.success');

Route::get('/cep', [ClientRegistrationController::class, 'lookupCep'])
    ->name('cep.lookup');

Route::view('/politica-de-privacidade', 'policy.index')
    ->name('policy.index');

/*
|--------------------------------------------------------------------------
| Autenticação administrativa
|--------------------------------------------------------------------------
*/
Route::get('/admin/login', [AuthController::class, 'showLogin'])
    ->middleware('guest')
    ->name('admin.login');

Route::post('/admin/login', [AuthController::class, 'login'])
    ->middleware('guest')
    ->name('admin.login.attempt');

Route::post('/admin/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('admin.logout');

/*
|--------------------------------------------------------------------------
| Painel da locadora (somente autenticados)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('admin')->group(function (): void {
    Route::redirect('/', '/admin/cadastros')->name('admin.dashboard');

    Route::get('/cadastros', [RegistrationController::class, 'index'])
        ->name('admin.registrations.index');

    Route::get('/cadastros/{registration}', [RegistrationController::class, 'show'])
        ->whereNumber('registration')
        ->name('admin.registrations.show');

    Route::patch('/cadastros/{registration}/status', [RegistrationController::class, 'updateStatus'])
        ->whereNumber('registration')
        ->name('admin.registrations.status');

    Route::get('/cadastros/{registration}/foto/{document}', [RegistrationController::class, 'photo'])
        ->whereNumber('registration')
        ->name('admin.registrations.photo');
});
