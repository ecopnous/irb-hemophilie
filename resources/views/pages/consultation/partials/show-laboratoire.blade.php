{{-- 6. Examen de laboratoire --}}
<section
                    class="rounded-md border border-slate-300 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                    <div
                        class="rounded-md border-b border-slate-200 bg-slate-50 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/80">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-500">Demandes
                                </p>
                                <h3 class="mt-1 text-lg font-black text-slate-900 dark:text-white">Laboratoire</h3>
                            </div>
                            <x-button wire:click="openEditor('laboratoire')" sm
                                x-on:click="$tsui.open.modal('consultation-section-modal')" icon="beaker">
                                Demandé examen
                            </x-button>
                        </div>
                    </div>

                    <div class="space-y-4 px-5 py-5">
                        <div
                            class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/60">
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Renseignement</p>
                            <p class="mt-2 text-sm leading-6 text-slate-700 dark:text-slate-300">
                                {{ $this->consultation->laboratoire?->renseignement ?: 'Aucun renseignement de laboratoire saisi.' }}
                            </p>
                        </div>

                        <div>
                            <div class="mb-3 flex items-center justify-between">
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">Examens demandés</p>
                                <span
                                    class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                    {{ $this->laboratoireActes()->count() }}
                                </span>
                            </div>
                            <div class="overflow-hidden border border-gray-200 rounded-lg shadow-sm">
                                <table class="min-w-full divide-y divide-gray-200 bg-white text-sm">
                                    <!-- En-tête d'actions -->
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th colspan="5" class="px-4 py-3 text-right">
                                                <a href="#"
                                                    class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-700 rounded-md hover:bg-blue-100 font-semibold transition-colors">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z">
                                                        </path>
                                                    </svg>
                                                    Imprimer le Bon de laboratoire
                                                </a>
                                            </th>
                                        </tr>
                                    </thead>

                                    <!-- En-tête des colonnes -->
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th
                                                class="px-4 py-3 font-semibold text-gray-700 border-b border-r border-gray-200 text-left">
                                                #</th>
                                            <th
                                                class="px-4 py-3 font-semibold text-gray-700 border-b border-r border-gray-200 text-left">
                                                Examens</th>
                                            <th
                                                class="px-4 py-3 font-semibold text-center text-gray-700 border-b border-r border-gray-200">
                                                Résultat</th>
                                            <th
                                                class="px-4 py-3 font-semibold text-gray-700 border-b border-r border-gray-200 text-center">
                                                Valeur normale</th>
                                            <th
                                                class="px-4 py-3 font-semibold text-gray-700 border-b border-r border-gray-200 text-right">
                                                Actions</th>
                                        </tr>
                                    </thead>

                                    <tbody class="divide-y divide-gray-200">
                                        @forelse ($this->laboratoireActes() as $acte)
                                            <tr class="hover:bg-gray-50 transition-colors">
                                                <td class="px-4 text-xs py-3 text-gray-500 border-r border-gray-200">
                                                    {{ $loop->iteration }}</td>
                                                <td
                                                    class="px-4 py-3 text-xs font-medium text-gray-900 border-r border-gray-200">
                                                    {{ $acte->name }}</td>
                                                <td class="px-4 text-xs py-3 border-r text-center border-gray-200">
                                                    @if ($acte->pivot->resultat && $acte->pivot->valide)
                                                        <span
                                                            class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-bold">
                                                            {{ $acte->pivot->resultat }}
                                                        </span>
                                                    @else
                                                        <span class="italic text-gray-400">En attente</span>
                                                    @endif
                                                </td>
                                                <td
                                                    class="px-4 py-3 text-xs text-center text-gray-600 border-r border-gray-200">
                                                    <span
                                                        class="font-mono text-xs">{{ $acte->valeur_normale ?? '[-]' }}</span>
                                                </td>
                                                <td
                                                    class="px-4 py-3 text-xs flex gap-2 justify-end border-r border-gray-200">
                                                    <flux:button size="xs" variant="primary" color="indigo"
                                                        wire:click="openLaboratoireActeNoteModal({{ $acte->id }})"
                                                        :icon="$acte->pivot->valide ? 'lock-closed' : 'pencil-square'"
                                                        :disabled="$acte->pivot->valide">
                                                        Note
                                                    </flux:button>
                                                    <flux:button size="xs" variant="danger"
                                                        wire:click="confirmDeleteLaboratoireActe({{ $acte->id }})"
                                                        :icon="$acte->pivot->valide ? 'lock-closed' : 'trash'"
                                                        :disabled="$acte->pivot->valide">
                                                        Supprimer
                                                    </flux:button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6"
                                                    class="px-4 py-8 text-center text-gray-400 italic bg-gray-50">
                                                    Aucun examen demandé.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        @if ($this->consultation->laboratoire)
                            <div>
                                <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white">Photos du bon de
                                        laboratoire</p>
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                            {{ $this->laboratoireImages()->count() }}
                                        </span>
                                        <a href="{{ route('laboratoire.show', $this->consultation->laboratoire->id) }}"
                                            wire:navigate
                                            class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100 dark:bg-indigo-500/10 dark:text-indigo-300 dark:hover:bg-indigo-500/20">
                                            <flux:icon.photo class="size-4" />
                                            Gerer les photos
                                        </a>
                                    </div>
                                </div>

                                @if ($this->laboratoireImages()->isNotEmpty())
                                    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
                                        @foreach ($this->laboratoireImages() as $image)
                                            <div wire:key="labo-photo-{{ $image->id }}"
                                                class="group overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900">
                                                <a href="{{ $image->url() }}" target="_blank" rel="noopener">
                                                    <img src="{{ $image->url() }}" alt="{{ $image->name }}"
                                                        class="aspect-square w-full object-cover transition group-hover:scale-[1.02]" />
                                                </a>
                                                <div class="space-y-1 p-3">
                                                    <p class="truncate text-xs font-semibold text-slate-800 dark:text-slate-200"
                                                        title="{{ $image->name }}">
                                                        {{ $image->name }}
                                                    </p>
                                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                                        {{ $image->created_at?->format('d/m/Y H:i') }}
                                                    </p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p
                                        class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                                        Aucune photo soumise pour ce bon de laboratoire.
                                    </p>
                                @endif
                            </div>
                        @endif
                    </div>
                </section>
