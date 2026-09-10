<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LinkController;
use App\Http\Controllers\Admin\RegistrationController;
use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\Public\ClientRegistrationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Entrada da aplicação
|--------------------------------------------------------------------------
|
| A raiz é a porta de entrada administrativa: visitantes são levados ao
| login; administradores autenticados vão direto para o painel.
|--------------------------------------------------------------------------
*/
Route::get('/', fn () => redirect()->route(
    auth()->check() ? 'admin.dashboard' : 'admin.login',
))->name('home');

/*
|--------------------------------------------------------------------------
| Formulário público de cadastro (acesso pelo celular)
|--------------------------------------------------------------------------
|
| O formulário é público e fica em /cadastro: o endereço é compartilhado
| pelo administrador com quem precisa realizar o cadastro.
|--------------------------------------------------------------------------
*/
Route::get('/cadastro', [ClientRegistrationController::class, 'create'])->name('client-registrations.create');

Route::post('/cadastro', [ClientRegistrationController::class, 'store'])
    ->middleware('throttle:cadastro')
    ->name('client-registrations.store');

Route::get('/sucesso', [ClientRegistrationController::class, 'success'])
    ->name('client-registrations.success');

Route::get('/cep', [ClientRegistrationController::class, 'lookupCep'])
    ->middleware('throttle:cep')
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
    ->middleware(['guest', 'throttle:admin_login'])
    ->name('admin.login.attempt');

Route::get('/admin/login/2fa', [AuthController::class, 'showTwoFactor'])
    ->middleware('guest')
    ->name('admin.login.two-factor');

Route::post('/admin/login/2fa', [AuthController::class, 'verifyTwoFactor'])
    ->middleware(['guest', 'throttle:admin_login'])
    ->name('admin.login.two-factor.verify');

Route::post('/admin/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('admin.logout');

Route::get('/admin/seguranca', [SecurityController::class, 'index'])
    ->middleware('auth')
    ->name('admin.security.index');

Route::post('/admin/seguranca/2fa/ativar', [SecurityController::class, 'enable2FA'])
    ->middleware('auth')
    ->name('admin.security.2fa.enable');

Route::post('/admin/seguranca/2fa/confirmar', [SecurityController::class, 'confirm2FA'])
    ->middleware(['auth', 'throttle:admin_login'])
    ->name('admin.security.2fa.confirm');

Route::post('/admin/seguranca/2fa/desativar', [SecurityController::class, 'disable2FA'])
    ->middleware('auth')
    ->name('admin.security.2fa.disable');

Route::get('/admin/seguranca/2fa/qr', [SecurityController::class, 'qrCode'])
    ->middleware('auth')
    ->name('admin.security.2fa.qr');

/*
|--------------------------------------------------------------------------
| Painel da locadora (somente autenticados)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('admin')->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])
        ->name('admin.dashboard');

    Route::get('/link-de-cadastro', [LinkController::class, 'show'])
        ->name('admin.registration-link');

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
