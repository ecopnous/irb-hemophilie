@props(['groupe'])

<span @class([
    'inline-flex rounded-full px-2.5 py-1 text-xs font-bold',
    'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300' => $groupe->is_active,
    'bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-400' => ! $groupe->is_active,
])>
    {{ $groupe->is_active ? 'Actif' : 'Inactif' }}
</span>
