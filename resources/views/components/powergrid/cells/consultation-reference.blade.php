@props(['consultation', 'showDepistage' => true])

<div class="space-y-1">
    @if ($consultation->is_visite_program)
        <p class="font-bold tracking-tight text-blue-600 dark:text-blue-300">
            Rendez-Vous
        </p>
    @elseif ($showDepistage && $consultation->type === 'depistage')
        <p class="font-bold tracking-tight text-green-600 dark:text-green-300">
            Examen
        </p>
    @else
        <p class="font-bold tracking-tight text-slate-900 dark:text-white">
            Visite Médicale
        </p>
    @endif
    <p class="text-slate-500 dark:text-slate-400">
        {{ $consultation->reference }}
    </p>
</div>
