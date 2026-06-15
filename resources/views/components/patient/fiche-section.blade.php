@props([
    'title',
    'icon' => 'document-text',
    'incomplete' => false,
    'incompleteMessage' => 'Informations incomplètes',
    'section' => null,
    'accent' => 'indigo',
])

@php
    $accentMap = [
        'indigo' => 'from-indigo-500/10 to-indigo-500/0 text-indigo-600 dark:text-indigo-300 border-indigo-200/70 dark:border-indigo-500/20',
        'violet' => 'from-violet-500/10 to-violet-500/0 text-violet-600 dark:text-violet-300 border-violet-200/70 dark:border-violet-500/20',
        'sky' => 'from-sky-500/10 to-sky-500/0 text-sky-600 dark:text-sky-300 border-sky-200/70 dark:border-sky-500/20',
        'rose' => 'from-rose-500/10 to-rose-500/0 text-rose-600 dark:text-rose-300 border-rose-200/70 dark:border-rose-500/20',
        'amber' => 'from-amber-500/10 to-amber-500/0 text-amber-600 dark:text-amber-300 border-amber-200/70 dark:border-amber-500/20',
        'emerald' => 'from-emerald-500/10 to-emerald-500/0 text-emerald-600 dark:text-emerald-300 border-emerald-200/70 dark:border-emerald-500/20',
    ];
    $accentClasses = $accentMap[$accent] ?? $accentMap['indigo'];
@endphp

<section
    {{ $attributes->class([
        'overflow-hidden rounded-[1.75rem] border bg-white shadow-sm dark:bg-slate-950/70',
        'border-slate-200 dark:border-slate-800' => ! $incomplete,
        'border-amber-300/80 ring-1 ring-amber-300/50 dark:border-amber-500/30 dark:ring-amber-500/20' => $incomplete,
    ]) }}>
    <div class="bg-linear-to-r {{ $accentClasses }} border-b px-5 py-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex min-w-0 items-start gap-3">
                <div
                    class="flex size-10 shrink-0 items-center justify-center rounded-2xl border border-white/60 bg-white/80 shadow-sm dark:border-slate-700 dark:bg-slate-900/80">
                    <flux:icon :icon="$icon" class="size-5" />
                </div>
                <div class="min-w-0">
                    <h3 class="text-base font-black tracking-tight text-slate-900 dark:text-white">
                        {{ $title }}
                    </h3>
                    @if ($incomplete)
                        <p class="mt-1 flex items-center gap-1.5 text-xs font-medium text-amber-700 dark:text-amber-300">
                            <span class="size-2 rounded-full bg-amber-500"></span>
                            {{ $incompleteMessage }}
                        </p>
                    @else
                        <p class="mt-1 text-xs font-medium text-emerald-700 dark:text-emerald-300">
                            Section complète
                        </p>
                    @endif
                </div>
            </div>

            @if ($section)
                <flux:button size="sm" variant="ghost" icon="pencil-square"
                    wire:click="openSection('{{ $section }}')"
                    x-on:click="$tsui.open.modal('fiche-medicale-edit-modal')">
                    Modifier
                </flux:button>
            @endif
        </div>
    </div>

    <div class="p-5 sm:p-6">
        {{ $slot }}
    </div>
</section>
