<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'IRB Hemophilie') }}</title>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900 dark:bg-neutral-950 dark:text-white">
        <div class="relative isolate overflow-hidden">
            <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top,_rgba(14,165,233,0.18),_transparent_55%)]"></div>

            <header class="mx-auto flex w-full max-w-7xl items-center justify-between px-6 py-6 lg:px-8">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-600 text-white shadow-lg shadow-sky-500/30">
                        <x-app-logo-icon class="size-6 fill-current" />
                    </span>
                    <span class="text-sm font-semibold tracking-wide">{{ config('app.name', 'IRB Hemophilie') }}</span>
                </a>

                @if (Route::has('login'))
                    <nav class="flex items-center gap-3">
                        @auth
                            <a
                                href="{{ route('dashboard') }}"
                                class="inline-flex items-center rounded-xl bg-sky-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-sky-700"
                            >
                                Tableau de bord
                            </a>
                        @else
                            <a
                                href="{{ route('login') }}"
                                class="inline-flex items-center rounded-xl bg-sky-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-sky-700"
                            >
                                Connexion
                            </a>
                        @endauth
                    </nav>
                @endif
            </header>

            <main class="mx-auto grid w-full max-w-7xl gap-10 px-6 pb-16 pt-8 lg:grid-cols-2 lg:items-center lg:gap-16 lg:px-8 lg:pb-24 lg:pt-16">
                <section class="space-y-8">
                    <div class="inline-flex items-center rounded-full border border-sky-200 bg-white px-4 py-1 text-xs font-medium text-sky-700 shadow-sm dark:border-sky-900/60 dark:bg-neutral-900 dark:text-sky-300">
                        Plateforme clinique securisee
                    </div>

                    <div class="space-y-4">
                        <h1 class="text-4xl font-bold leading-tight tracking-tight sm:text-5xl">
                            Centralisez la prise en charge de l'hemophilie en toute confiance.
                        </h1>
                        <p class="max-w-xl text-base leading-relaxed text-slate-600 dark:text-neutral-300">
                            Une interface professionnelle pour suivre les patients, coordonner les equipes et piloter les actes
                            avec une vision claire et des donnees fiables.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        @auth
                            <a
                                href="{{ route('dashboard') }}"
                                class="inline-flex items-center rounded-xl bg-sky-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-700"
                            >
                                Ouvrir le tableau de bord
                            </a>
                        @else
                            <a
                                href="{{ route('login') }}"
                                class="inline-flex items-center rounded-xl bg-sky-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-700"
                            >
                                Se connecter
                            </a>
                        @endauth
                    </div>
                </section>

                <section class="grid gap-4 sm:grid-cols-2">
                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Suivi patient</h2>
                        <p class="mt-2 text-sm text-slate-600 dark:text-neutral-300">Fiches medicales structurees et historiques accessibles instantanement.</p>
                    </article>
                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Coordination des equipes</h2>
                        <p class="mt-2 text-sm text-slate-600 dark:text-neutral-300">Collaboration fluide entre medecins, pharmacie et services support.</p>
                    </article>
                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Tracabilite des actes</h2>
                        <p class="mt-2 text-sm text-slate-600 dark:text-neutral-300">Chaque intervention est documentee pour un meilleur pilotage clinique.</p>
                    </article>
                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Securite et conformite</h2>
                        <p class="mt-2 text-sm text-slate-600 dark:text-neutral-300">Acces controles et environnement adapte aux usages hospitaliers.</p>
                    </article>
                </section>
            </main>
        </div>
    </body>
</html>
