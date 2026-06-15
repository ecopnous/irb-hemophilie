@props(['consultation', 'routeName' => 'patient.show'])

@php
    $routeParam = $routeName === 'patient.show'
        ? $consultation->dossierPatient?->id
        : $consultation->id;
@endphp

<div class="space-y-1">
    <p class="font-bold uppercase tracking-tight text-slate-900 dark:text-white">
        <a href="{{ route($routeName, $routeParam) }}" class="hover:text-blue-600" wire:navigate>
            {{ $consultation->dossierPatient?->full_name }}
        </a>
    </p>
    <p class="text-slate-500 dark:text-slate-400">
        {{ $consultation->dossierPatient?->genre }} ({{ $consultation->dossierPatient?->age }})
    </p>
</div>
