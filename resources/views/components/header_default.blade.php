<div class="max-w-7xl mx-auto mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        @isset($navigations)
            <x-breadcrumbs :items="$navigations" />
        @endisset

        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight mt-2">
            {{ $title ?? null }}
        </h1>
        @isset($subtitle)
            <p class="text-sm font-mono text-gray-500 dark:text-slate-400 mt-1">{{ $subtitle }}</p>
        @endisset
    </div>

    <div class="flex items-center gap-3">
        {{ $actions ?? null }}
    </div>
</div>
