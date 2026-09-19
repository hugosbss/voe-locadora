<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LinkController;
use App\Http\Controllers\Admin\NewPasswordController;
use App\Http\Controllers\Admin\PasswordResetLinkController;
use App\Http\Controllers\Admin\QuotaTypeController;
use App\Http\Controllers\Admin\RegistrationController;
use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VehicleController;
use App\Http\Controllers\Public\ClientRegistrationController;
use App\Http\Controllers\Public\HowItWorksController;
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

Route::get('/cadastro/disponibilidade', [ClientRegistrationController::class, 'quotaAvailability'])
    ->middleware('throttle:quota_availability')
    ->name('client-registrations.quota-availability');

Route::post('/cadastro', [ClientRegistrationController::class, 'store'])
    ->middleware('cadastro-throttle')
    ->name('client-registrations.store');

Route::get('/cadastro/contrato', [ClientRegistrationController::class, 'contractView'])
    ->name('client-registrations.contract');

Route::get('/sucesso', [ClientRegistrationController::class, 'success'])
    ->name('client-registrations.success');

Route::get('/cep', [ClientRegistrationController::class, 'lookupCep'])
    ->middleware('throttle:cep')
    ->name('cep.lookup');

Route::view('/politica-de-privacidade', 'policy.index')
    ->name('policy.index');

Route::get('/como-funciona', [HowItWorksController::class, 'index'])
    ->name('public.how-it-works');

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

Route::get('/admin/recuperar-senha', [PasswordResetLinkController::class, 'create'])
    ->middleware('guest')
    ->name('admin.password.request');

Route::post('/admin/recuperar-senha', [PasswordResetLinkController::class, 'store'])
    ->middleware(['guest', 'throttle:admin_password'])
    ->name('admin.password.email');

Route::get('/admin/recuperar-senha/{token}', [NewPasswordController::class, 'create'])
    ->middleware('guest')
    ->name('admin.password.reset');

Route::post('/admin/redefinir-senha', [NewPasswordController::class, 'store'])
    ->middleware(['guest', 'throttle:admin_password'])
    ->name('admin.password.update');

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

    Route::get('/configuracoes/usuarios', [UserController::class, 'index'])
        ->name('admin.users.index');

    Route::post('/configuracoes/usuarios', [UserController::class, 'store'])
        ->name('admin.users.store');

    Route::get('/cadastros', [RegistrationController::class, 'index'])
        ->name('admin.registrations.index');

    Route::get('/cadastros/{registration:uuid}', [RegistrationController::class, 'show'])
        ->name('admin.registrations.show');

    Route::get('/cadastros/{registration:uuid}/editar', [RegistrationController::class, 'edit'])
        ->name('admin.registrations.edit');

    Route::put('/cadastros/{registration:uuid}', [RegistrationController::class, 'update'])
        ->name('admin.registrations.update');

    Route::get('/cotas', [QuotaTypeController::class, 'index'])
        ->name('admin.quotas.index');

    Route::get('/cotas/criar', [QuotaTypeController::class, 'create'])
        ->name('admin.quotas.create');

    Route::post('/cotas', [QuotaTypeController::class, 'store'])
        ->name('admin.quotas.store');

    Route::get('/cotas/{quotaType}', [QuotaTypeController::class, 'show'])
        ->name('admin.quotas.show');

    Route::get('/cotas/{quotaType}/editar', [QuotaTypeController::class, 'edit'])
        ->name('admin.quotas.edit');

    Route::put('/cotas/{quotaType}', [QuotaTypeController::class, 'update'])
        ->name('admin.quotas.update');

    Route::delete('/cotas/{quotaType}', [QuotaTypeController::class, 'destroy'])
        ->name('admin.quotas.destroy');

    Route::post('/cotas/{quotaType}/toggle-status', [QuotaTypeController::class, 'toggleStatus'])
        ->name('admin.quotas.toggle-status');

    Route::post('/cotas/{quotaType}/veiculos', [QuotaTypeController::class, 'addVehicle'])
        ->name('admin.quotas.add-vehicle');

    Route::patch('/cotas/{quotaType}/veiculos/{configuration}', [QuotaTypeController::class, 'updateVehicleConfiguration'])
        ->name('admin.quotas.update-vehicle');

    Route::delete('/cotas/{quotaType}/veiculos/{configuration}', [QuotaTypeController::class, 'removeVehicleConfiguration'])
        ->name('admin.quotas.remove-vehicle');

    Route::get('/veiculos', [VehicleController::class, 'index'])
        ->name('admin.vehicles.index');

    Route::get('/veiculos/criar', [VehicleController::class, 'create'])
        ->name('admin.vehicles.create');

    Route::post('/veiculos', [VehicleController::class, 'store'])
        ->name('admin.vehicles.store');

    Route::get('/veiculos/{vehicle}', [VehicleController::class, 'show'])
        ->name('admin.vehicles.show');

    Route::get('/veiculos/{vehicle}/editar', [VehicleController::class, 'edit'])
        ->name('admin.vehicles.edit');

    Route::put('/veiculos/{vehicle}', [VehicleController::class, 'update'])
        ->name('admin.vehicles.update');

    Route::delete('/veiculos/{vehicle}', [VehicleController::class, 'destroy'])
        ->name('admin.vehicles.destroy');

    Route::post('/veiculos/{vehicle}/toggle-status', [VehicleController::class, 'toggleStatus'])
        ->name('admin.vehicles.toggle-status');

    Route::post('/veiculos/{vehicle}/configuracoes', [VehicleController::class, 'addQuotaConfiguration'])
        ->name('admin.vehicles.add-quota');

    Route::patch('/veiculos/{vehicle}/configuracoes/{configuration}', [VehicleController::class, 'updateQuotaConfiguration'])
        ->name('admin.vehicles.update-quota');

    Route::delete('/veiculos/{vehicle}/configuracoes/{configuration}', [VehicleController::class, 'removeQuotaConfiguration'])
        ->name('admin.vehicles.remove-quota');

    Route::patch('/cadastros/{registration:uuid}/status', [RegistrationController::class, 'updateStatus'])
        ->name('admin.registrations.status');

    Route::get('/cadastros/{registration:uuid}/foto/{document}', [RegistrationController::class, 'photo'])
        ->name('admin.registrations.photo');

    Route::get('/cadastros/{registration:uuid}/contrato-assinado', [RegistrationController::class, 'contract'])
        ->name('admin.registrations.contract');

    Route::get('/cadastros/{registration:uuid}/contrato-assinado/baixar', [RegistrationController::class, 'contractDownload'])
        ->name('admin.registrations.contract.download');

    Route::get('/cadastros/{registration:uuid}/contrato-assinado/assinatura', [RegistrationController::class, 'contractSignature'])
        ->name('admin.registrations.contract.signature');
});
