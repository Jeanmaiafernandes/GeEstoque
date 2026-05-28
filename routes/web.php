<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CompanySettingsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\MovementController;
use App\Http\Controllers\NfeImportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RequisitionController;
use App\Http\Controllers\SectorController;
use App\Http\Controllers\SystemParameterController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Perfil do próprio usuário e página de ajuda (todos os perfis).
    Route::get('perfil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('perfil', [ProfileController::class, 'update'])->name('profile.update');
    Route::view('ajuda', 'help.index')->name('help');

    // Materiais: index/show/kardex liberados a todos; escrita restrita a admin (middleware no controller).
    Route::get('items/{item}/kardex', [ItemController::class, 'kardex'])->whereNumber('item')->name('items.kardex');
    Route::patch('items/{item}/situacao', [ItemController::class, 'toggleActive'])->whereNumber('item')->name('items.toggle-active');
    Route::resource('items', ItemController::class);

    // Movimentações: disponíveis para todos os perfis (admin e operador).
    Route::get('movimentacoes', [MovementController::class, 'index'])->name('movements.index');
    Route::get('movimentacoes/registrar/{type}', [MovementController::class, 'create'])
        ->whereIn('type', ['entrada', 'saida', 'ajuste'])
        ->name('movements.create');
    Route::post('movimentacoes', [MovementController::class, 'store'])->name('movements.store');
    Route::get('movimentacoes/{movement}', [MovementController::class, 'show'])->whereNumber('movement')->name('movements.show');

    // Inventário rotativo guiado (disponível para todos os perfis).
    Route::get('inventarios', [InventoryController::class, 'index'])->name('inventories.index');
    Route::get('inventarios/novo', [InventoryController::class, 'create'])->name('inventories.create');
    Route::post('inventarios', [InventoryController::class, 'store'])->name('inventories.store');
    Route::get('inventarios/{inventory}', [InventoryController::class, 'show'])->whereNumber('inventory')->name('inventories.show');
    Route::put('inventarios/{inventory}', [InventoryController::class, 'update'])->whereNumber('inventory')->name('inventories.update');
    Route::post('inventarios/{inventory}/cancelar', [InventoryController::class, 'cancel'])->whereNumber('inventory')->name('inventories.cancel');

    // Requisição de material com aprovação (criar/ver: todos; aprovar/recusar: admin).
    Route::get('requisicoes', [RequisitionController::class, 'index'])->name('requisitions.index');
    Route::get('requisicoes/nova', [RequisitionController::class, 'create'])->name('requisitions.create');
    Route::post('requisicoes', [RequisitionController::class, 'store'])->name('requisitions.store');
    Route::get('requisicoes/{requisition}', [RequisitionController::class, 'show'])->whereNumber('requisition')->name('requisitions.show');
    Route::get('requisicoes/{requisition}/imprimir', [RequisitionController::class, 'print'])->whereNumber('requisition')->name('requisitions.print');
    Route::get('requisicoes/{requisition}/editar', [RequisitionController::class, 'edit'])->whereNumber('requisition')->name('requisitions.edit');
    Route::put('requisicoes/{requisition}', [RequisitionController::class, 'update'])->whereNumber('requisition')->name('requisitions.update');
    Route::post('requisicoes/{requisition}/aprovar', [RequisitionController::class, 'approve'])->whereNumber('requisition')->name('requisitions.approve');
    Route::post('requisicoes/{requisition}/recusar', [RequisitionController::class, 'reject'])->whereNumber('requisition')->name('requisitions.reject');
    Route::post('requisicoes/{requisition}/cancelar', [RequisitionController::class, 'cancel'])->whereNumber('requisition')->name('requisitions.cancel');

    // Importação de NF-e (XML) → entrada automática.
    Route::get('entrada-xml', [NfeImportController::class, 'create'])->name('nfe.create');
    Route::post('entrada-xml/analisar', [NfeImportController::class, 'preview'])->name('nfe.preview');
    Route::post('entrada-xml', [NfeImportController::class, 'store'])->name('nfe.store');

    // Relatórios (visualização + exportação Excel/PDF).
    // Etiquetas (código de barras + QR) para impressão.
    Route::get('etiquetas', [LabelController::class, 'index'])->name('labels.index');
    Route::get('etiquetas/imprimir', [LabelController::class, 'print'])->name('labels.print');

    Route::get('relatorios', [ReportController::class, 'index'])->name('reports.index');
    Route::get('relatorios/saldo-atual', [ReportController::class, 'saldoAtual'])->name('reports.saldo');
    Route::get('relatorios/abaixo-do-minimo', [ReportController::class, 'abaixoMinimo'])->name('reports.abaixo');
    Route::get('relatorios/entradas', [ReportController::class, 'entradas'])->name('reports.entradas');
    Route::get('relatorios/consumo-por-setor', [ReportController::class, 'consumoSetor'])->name('reports.consumo');

    // Cadastros administrativos.
    Route::middleware('role:admin')->group(function () {
        Route::resource('categories', CategoryController::class)->only(['index', 'store', 'edit', 'update', 'destroy']);
        Route::resource('users', UserController::class)->except(['show']);

        // Configurações — central + cadastros.
        Route::view('configuracoes', 'settings.index')->name('settings.index');

        Route::get('configuracoes/empresa', [CompanySettingsController::class, 'edit'])->name('settings.company.edit');
        Route::put('configuracoes/empresa', [CompanySettingsController::class, 'update'])->name('settings.company.update');

        // Setores
        Route::get('configuracoes/setores', [SectorController::class, 'index'])->name('sectors.index');
        Route::post('configuracoes/setores', [SectorController::class, 'store'])->name('sectors.store');
        Route::get('configuracoes/setores/{sector}/editar', [SectorController::class, 'edit'])->whereNumber('sector')->name('sectors.edit');
        Route::put('configuracoes/setores/{sector}', [SectorController::class, 'update'])->whereNumber('sector')->name('sectors.update');
        Route::delete('configuracoes/setores/{sector}', [SectorController::class, 'destroy'])->whereNumber('sector')->name('sectors.destroy');

        // Unidades
        Route::get('configuracoes/unidades', [UnitController::class, 'index'])->name('units.index');
        Route::post('configuracoes/unidades', [UnitController::class, 'store'])->name('units.store');
        Route::get('configuracoes/unidades/{unit}/editar', [UnitController::class, 'edit'])->whereNumber('unit')->name('units.edit');
        Route::put('configuracoes/unidades/{unit}', [UnitController::class, 'update'])->whereNumber('unit')->name('units.update');
        Route::delete('configuracoes/unidades/{unit}', [UnitController::class, 'destroy'])->whereNumber('unit')->name('units.destroy');

        // Parâmetros do sistema
        Route::get('configuracoes/parametros', [SystemParameterController::class, 'edit'])->name('settings.params.edit');
        Route::put('configuracoes/parametros', [SystemParameterController::class, 'update'])->name('settings.params.update');
    });
});
