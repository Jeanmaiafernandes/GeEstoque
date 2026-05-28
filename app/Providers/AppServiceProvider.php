<?php

namespace App\Providers;

use App\Enums\RequisitionStatus;
use App\Models\Item;
use App\Models\Requisition;
use App\Support\Settings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Notificações do header: itens abaixo do mínimo + requisições pendentes (admin).
        View::composer('layouts.app', function ($view): void {
            $belowMin = 0;
            $pendingReq = 0;

            if (Auth::check()) {
                $belowMin = Item::where('active', true)->belowMinimum()->count();

                if (Auth::user()->isAdmin()) {
                    $pendingReq = Requisition::where('status', RequisitionStatus::Pendente->value)->count();
                }
            }

            $view->with('notifBelowMin', $belowMin)
                ->with('notifPendingReq', $pendingReq);
        });

        // Logo da empresa (substitui o monograma) no layout e no login.
        View::composer(['layouts.app', 'auth.login'], function ($view): void {
            $logo = null;
            try {
                $logo = Settings::companyLogoUrl();
            } catch (\Throwable) {
                // tabela settings ainda não existe (antes do migrate) — ignora.
            }
            $view->with('companyLogo', $logo);
        });
    }
}
