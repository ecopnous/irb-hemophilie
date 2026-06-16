<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-slate-50 antialiased dark:bg-neutral-950">
        <div class="relative flex min-h-svh items-center justify-center overflow-hidden px-6 py-10 md:px-10">
            <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top,_rgba(14,165,233,0.18),_transparent_55%)]"></div>

            <div class="w-full max-w-md">
                <a href="{{ route('home') }}" class="mb-6 flex flex-col items-center gap-2 font-medium" wire:navigate>
                    <span class="mb-1 flex h-11 w-11 items-center justify-center rounded-xl bg-sky-600 text-white shadow-lg shadow-sky-500/30">
                        <x-app-logo-icon class="size-7 fill-current" />
                    </span>
                    <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                </a>

                <div class="rounded-2xl border border-slate-200/80 bg-white/95 p-7 shadow-xl shadow-slate-300/20 backdrop-blur dark:border-neutral-800 dark:bg-neutral-900/90 dark:shadow-black/30 md:p-8">
                    <div class="mb-5">
                        <p class="text-center text-xs font-semibold uppercase tracking-[0.2em] text-sky-600 dark:text-sky-400">
                            {{ config('app.name', 'Laravel') }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-6">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
