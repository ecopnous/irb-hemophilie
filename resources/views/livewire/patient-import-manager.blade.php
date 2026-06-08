<div class="flex items-center gap-2">
    <flux:button variant="ghost" icon="arrow-up-tray" wire:click="openModal">
        Importer Excel
    </flux:button>

    <flux:modal wire:model="showModal" class="max-w-2xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Importer des patients</flux:heading>
                <flux:subheading>
                    Importez des dossiers depuis Excel (.xlsx, .xls) ou CSV. Les fichiers volumineux sont traites par lots
                    en arriere-plan sans bloquer l'interface.
                </flux:subheading>
            </div>

            <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-100">
                <p class="font-semibold">Conseils pour les gros fichiers</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li>Preferez le format <strong>CSV</strong> pour des centaines de milliers de lignes.</li>
                    <li>Traitement par lots de 1 000 lignes via file d'attente.</li>
                    <li>Les lignes invalides sont ignorees et exportees dans un rapport d'erreurs.</li>
                    <li>Lancez <code class="rounded bg-black/5 px-1 dark:bg-white/10">php artisan queue:work --queue=imports,default</code> (redemarrez-le apres une mise a jour du code).</li>
                </ul>
            </div>

            @if ($this->activeImport)
                <div
                    @if (!$this->activeImport->isFinished()) wire:poll.3s="checkImportStatus" @else wire:init="checkImportStatus" @endif
                    class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold text-slate-900 dark:text-white">Import en cours</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $this->activeImport->original_filename }}</p>
                        </div>
                        @php
                            $statusColor = match ($this->activeImport->status) {
                                'completed' => 'emerald',
                                'failed' => 'red',
                                'processing' => 'blue',
                                default => 'zinc',
                            };
                        @endphp
                        <flux:badge :color="$statusColor">
                            {{ ucfirst($this->activeImport->status) }}
                        </flux:badge>
                    </div>

                    <div class="mt-4">
                        <div class="mb-2 flex justify-between text-xs text-slate-500 dark:text-slate-400">
                            <span>Progression</span>
                            <span>{{ $this->activeImport->progressPercent() }}%</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                            <div
                                class="h-full rounded-full bg-indigo-500 transition-all duration-500"
                                style="width: {{ $this->activeImport->progressPercent() }}%"
                            ></div>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-3 gap-3 text-center">
                        <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800/60">
                            <p class="text-lg font-bold text-slate-900 dark:text-white">{{ number_format($this->activeImport->processed_rows) }}</p>
                            <p class="text-xs text-slate-500">Traitees</p>
                        </div>
                        <div class="rounded-xl bg-emerald-50 p-3 dark:bg-emerald-500/10">
                            <p class="text-lg font-bold text-emerald-700 dark:text-emerald-300">{{ number_format($this->activeImport->success_count) }}</p>
                            <p class="text-xs text-emerald-600 dark:text-emerald-400">Importees</p>
                        </div>
                        <div class="rounded-xl bg-red-50 p-3 dark:bg-red-500/10">
                            <p class="text-lg font-bold text-red-700 dark:text-red-300">{{ number_format($this->activeImport->failed_count) }}</p>
                            <p class="text-xs text-red-600 dark:text-red-400">Erreurs</p>
                        </div>
                    </div>

                    @if ($this->activeImport->isFinished())
                        @if ($this->activeImport->status === 'completed')
                            <flux:callout variant="success" class="mt-4" icon="check-circle">
                                Import termine. {{ number_format($this->activeImport->success_count) }} patient(s) ajoute(s).
                            </flux:callout>
                        @else
                            <flux:callout variant="danger" class="mt-4" icon="x-circle">
                                {{ $this->activeImport->error_message ?? 'Une erreur est survenue pendant l\'import.' }}
                            </flux:callout>
                        @endif

                        @if ($this->activeImport->errors_file_path && $this->activeImport->failed_count > 0)
                            <div class="mt-4">
                                <flux:button
                                    variant="ghost"
                                    icon="arrow-down-tray"
                                    :href="route('patient.import.errors', $this->activeImport)"
                                >
                                    Telecharger le rapport d'erreurs
                                </flux:button>
                            </div>
                        @endif
                    @endif
                </div>
            @endif

            <form wire:submit="startImport" class="space-y-4">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-300">
                        Fichier Excel ou CSV
                    </label>
                    <input
                        type="file"
                        wire:model="importFile"
                        accept=".xlsx,.xls,.csv,.txt"
                        class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:file:bg-indigo-500/10 dark:file:text-indigo-300"
                    />
                    @error('importFile')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    <div wire:loading wire:target="importFile" class="mt-2 text-xs text-slate-500">
                        Chargement du fichier...
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <flux:button
                        variant="ghost"
                        icon="document-arrow-down"
                        :href="route('patient.import.template')"
                    >
                        Telecharger l'exemple Excel
                    </flux:button>

                    <div class="flex gap-2">
                        <flux:modal.close>
                            <flux:button variant="ghost">Fermer</flux:button>
                        </flux:modal.close>
                        <flux:button
                            type="submit"
                            variant="primary"
                            color="indigo"
                            icon="arrow-up-tray"
                            wire:loading.attr="disabled"
                            wire:target="startImport,importFile"
                        >
                            Lancer l'import
                        </flux:button>
                    </div>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
