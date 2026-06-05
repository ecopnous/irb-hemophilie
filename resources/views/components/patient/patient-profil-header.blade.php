@props(['patient', 'current_patient', 'title', 'subtitle', 'nav' => []])

<section
    class="overflow-hidden rounded-[2rem] border border-indigo-100 bg-gradient-to-br from-white via-indigo-50/70 to-slate-50 shadow-sm dark:border-slate-800 dark:from-slate-950 dark:via-slate-900 dark:to-slate-900 mb-6">
    <div class="flex flex-col gap-6 px-6 py-6 md:px-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-3">
                <x-breadcrumbs :items="$nav" />

                <div class="space-y-2">
                    <p class="text-xs font-black uppercase tracking-[0.24em] text-indigo-600 dark:text-indigo-300">
                        Dossier médical
                    </p>
                    <h1 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">
                        {{ $title ?? 'Fiche médicale' }}
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        {{ $subtitle ?? '' }}
                    </p>
                </div>
            </div>

            <div class="flex flex-col items-start gap-3 lg:items-end">
                <span
                    class="inline-flex rounded-full bg-indigo-100 px-3 py-1.5 text-sm font-bold text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300">
                    {{ $patient->nin }}
                </span>
                <div class="text-sm text-slate-500 dark:text-slate-400 lg:text-right">
                    <p>Identité santé: <span
                            class="font-semibold text-slate-700 dark:text-slate-200">{{ $patient->ins ?: '-' }}</span>
                    </p>
                    <p>Date de naissance: <span
                            class="font-semibold text-slate-700 dark:text-slate-200">{{ optional($patient->date_naissance)->format('d/m/Y') ?: '-' }}</span>
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <flux:button.group>
                        <flux:button href="{{ route('patient.print', $current_patient) }}" target="_blank"
                            icon="folder-arrow-down">Telecharger</flux:button>
                        <flux:dropdown>
                            <flux:button icon="chevron-down"></flux:button>

                            <flux:menu>
                                <flux:menu.group heading="Pdf">
                                    <flux:menu.item>Dossier Résumé</flux:menu.item>
                                    <flux:menu.item>Dossier Complet</flux:menu.item>
                                    <flux:menu.item>Dossier par période</flux:menu.item>
                                </flux:menu.group>

                                <flux:menu.group heading="Xlsx">
                                    <flux:menu.item>Dossier Résumé</flux:menu.item>
                                    <flux:menu.item>Dossier Complet</flux:menu.item>
                                    <flux:menu.item>Dossier par période</flux:menu.item>
                                </flux:menu.group>
                            </flux:menu>
                        </flux:dropdown>
                    </flux:button.group>

                    <flux:button href="#" variant="primary" icon="pencil-square" color="indigo">Éditer le profil
                    </flux:button>
                </div>
            </div>
        </div>
    </div>
</section>
