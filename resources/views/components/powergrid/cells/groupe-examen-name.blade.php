@props(['groupe'])

<div class="space-y-1">
    <a href="{{ route('laboratoire.groupes.show', $groupe->id) }}" wire:navigate
        class="font-bold tracking-tight text-slate-900 hover:text-sky-600 dark:text-white dark:hover:text-sky-300">
        {{ \Illuminate\Support\Str::ucfirst(\Illuminate\Support\Str::lower($groupe->name)) }}
    </a>
    @if (filled($groupe->description))
        <p class="line-clamp-1 text-xs text-slate-500 dark:text-slate-400">
            {{ $groupe->description }}
        </p>
    @endif
</div>
