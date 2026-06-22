{{-- 7. Examen d'imagerie --}}
<section
                    class="rounded-md border border-slate-300 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                    <div
                        class="rounded-md border-b border-slate-200 bg-slate-50 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/80">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-500">Demandes
                                </p>
                                <h3 class="mt-1 text-lg font-black text-slate-900 dark:text-white">Imagerie</h3>
                            </div>
                            <x-button wire:click="openEditor('imagerie')" sm
                                x-on:click="$tsui.open.modal('consultation-section-modal')" icon="photo">
                                Demander / modifier
                            </x-button>
                        </div>
                    </div>

                    <div class="space-y-4 px-5 py-5">
                        <div
                            class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/60">
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Renseignement</p>
                            <p class="mt-2 text-sm leading-6 text-slate-700 dark:text-slate-300">
                                {{ $this->consultation->imagerie?->renseignement ?: 'Aucun renseignement d imagerie saisi.' }}
                            </p>
                        </div>

                        <div>
                            <div class="mb-3 flex items-center justify-between">
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">Examens demandes</p>
                                <span
                                    class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                    {{ $this->imagerieActes()->count() }}
                                </span>
                            </div>
                            <div class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800">
                                <table class="min-w-full border-collapse bg-white text-sm dark:bg-slate-950/40">
                                    <thead class="bg-slate-50 dark:bg-slate-900/70">
                                        <tr
                                            class="text-left text-xs font-bold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                                            <th class="px-4 py-3">Examen</th>
                                            <th class="px-4 py-3">Service</th>
                                            <th class="px-4 py-3 text-center">Images</th>
                                            <th class="px-4 py-3 text-center">Etat</th>
                                            <th class="px-4 py-3 text-right">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                        @forelse ($this->imagerieActes() as $acte)
                                            @php
                                                $isDocumented =
                                                    filled($acte->pivot->clinique ?? null) ||
                                                    filled($acte->pivot->protocole ?? null) ||
                                                    filled($acte->pivot->cloture ?? null);
                                            @endphp
                                            <tr
                                                class="transition-colors hover:bg-slate-50/70 dark:hover:bg-slate-900/40">
                                                <td class="px-4 py-3">
                                                    <div>
                                                        <p class="font-semibold text-slate-900 dark:text-white">
                                                            {{ $acte->name }}</p>
                                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                            {{ $this->consultation->reference }}
                                                        </p>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                                    {{ $acte->service?->name ?: ($acte->departement?->name ?: 'Imagerie') }}
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <span
                                                        class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                                        {{ $this->imagerieActeImages((int) $acte->id)->count() }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <span
                                                        class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold {{ $isDocumented ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300' }}">
                                                        {{ $isDocumented ? 'Renseigne' : 'A completer' }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    <a href="{{ route('imagerie.show', ['id' => $this->consultation->id, 'acte' => $acte->id]) }}"
                                                        wire:navigate
                                                        class="inline-flex items-center gap-2 rounded-xl border border-fuchsia-200 bg-fuchsia-50 px-3 py-1.5 text-xs font-bold text-fuchsia-700 transition hover:border-fuchsia-300 hover:bg-fuchsia-100 dark:border-fuchsia-500/20 dark:bg-fuchsia-500/10 dark:text-fuchsia-300">
                                                        {{ $isDocumented ? 'Ouvrir le compte rendu' : 'Renseigner cet examen' }}
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5"
                                                    class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                                                    Aucun examen d imagerie demande pour cette consultation.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        @if ($this->imagerieActes()->isNotEmpty())
                            <div>
                                <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white">Images d imagerie
                                    </p>
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                            {{ $this->imagerieImagesTotal() }}
                                        </span>
                                        <a href="{{ route('imagerie.show', $this->consultation->id) }}" wire:navigate
                                            class="inline-flex items-center gap-1 rounded-lg bg-fuchsia-50 px-3 py-1.5 text-xs font-semibold text-fuchsia-700 transition hover:bg-fuchsia-100 dark:bg-fuchsia-500/10 dark:text-fuchsia-300 dark:hover:bg-fuchsia-500/20">
                                            <flux:icon.photo class="size-4" />
                                            Gerer les images
                                        </a>
                                    </div>
                                </div>

                                @if ($this->imagerieImagesByActe()->isNotEmpty())
                                    <div class="space-y-6">
                                        @foreach ($this->imagerieImagesByActe() as $group)
                                            <div wire:key="imagerie-group-{{ $group->acte_id }}">
                                                <p
                                                    class="mb-3 text-xs font-bold uppercase tracking-[0.18em] text-fuchsia-600 dark:text-fuchsia-300">
                                                    {{ $group->acte_name }}
                                                    <span class="text-slate-400">({{ $group->images->count() }})</span>
                                                </p>
                                                <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
                                                    @foreach ($group->images as $image)
                                                        <div wire:key="imagerie-photo-{{ $image->id }}"
                                                            class="group overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900">
                                                            <a href="{{ $image->url() }}" target="_blank"
                                                                rel="noopener">
                                                                <img src="{{ $image->url() }}"
                                                                    alt="{{ $image->name }}"
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
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p
                                        class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                                        Aucune image soumise pour les examens d imagerie.
                                    </p>
                                @endif
                            </div>
                        @endif
                    </div>
                </section>
