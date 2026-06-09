@props(['title', 'subtitle' => null, 'chart' => null, 'height' => 'h-64'])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900']) }}>
    <div class="mb-4">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ $title }}</h3>
        @if ($subtitle)
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $subtitle }}</p>
        @endif
    </div>
    @if ($chart)
        <div class="{{ $height }} w-full min-h-[16rem]">
            {!! $chart->container() !!}
        </div>
    @endif
</div>
