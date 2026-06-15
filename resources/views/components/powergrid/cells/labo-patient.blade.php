@props(['consultation'])

<div class="space-y-1">
    <p class="font-bold uppercase tracking-tight text-slate-900 dark:text-white">
        <a href="{{ route('laboratoire.show', $consultation->laboratoire_id) }}" class="hover:text-blue-600" wire:navigate>
            {{ $consultation->dossierPatient?->full_name }}
        </a>
    </p>
    <p class="text-slate-500 dark:text-slate-400">
        {{ $consultation->dossierPatient?->nin }}
    </p>
</div>
