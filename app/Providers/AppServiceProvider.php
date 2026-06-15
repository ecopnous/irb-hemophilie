<?php

namespace App\Providers;

use App\Models\Consultation;
use App\Observers\ConsultationObserver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use PhpParser\Node\Stmt\Block;
use TallStackUi\Facades\TallStackUi;

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
        Consultation::observe(ConsultationObserver::class);

        $this->ensurePublicStorageLink();
        $this->configureDefaults();
        TallStackUi::customize()
            ->table()
            ->Block('wrapper', 'overflow-hidden border  border-gray-300 dark:border-gray-700 rounded-lg shadow-lg') //*
            // ->Block('table.base', 'dark:divide-dark-500/50 min-w-full divide-y divide-gray-200')
            ->Block('table.th', 'dark:text-dark-200 px-3 py-2 text-left text-[13px] text-gray-700 border-r border-gray-300 dark:border-gray-600') //*
            // ->Block('table.tbody', 'dark:bg-dark-700 dark:divide-dark-500/20 divide-y divide-gray-200 bg-white')
            // ->Block('table.tr', '')
            ->Block('table.td', 'dark:text-dark-300 whitespace-nowrap px-3 py-2 text-[13px] text-gray-500 border-r border-t border-gray-300 dark:border-gray-600') //*
            // ->Block('table.thead.normal', 'bg-gray-50 dark:bg-slate-800') //*
            // ->Block('table.thead.striped', 'bg-gray-50 dark:bg-slate-900') //*
            // ->Block('slots.header', 'mb-2 dark:text-dark-300 text-gray-500')
            ->Block('empty', 'dark:text-dark-300 col-span-full whitespace-nowrap text-center px-3 py-4 text-sm text-gray-500');

        TallStackUi::customize()
            ->card()
            ->block('header.wrapper.base', 'flex items-center justify-between px-4 py-2');
    }

    protected function ensurePublicStorageLink(): void
    {
        $link = public_path('storage');

        if (is_link($link) || is_dir($link)) {
            return;
        }

        try {
            Artisan::call('storage:link');
        } catch (\Throwable) {
            //
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(
            fn(): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
