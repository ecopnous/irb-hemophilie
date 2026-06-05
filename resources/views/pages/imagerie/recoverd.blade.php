{{-- Page Show --}}

<?php
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Bons d\'imagerie')] class extends Component {
    public $photos;
};
?>

<!-- Page : ImagingEntry.blade.php -->
<div class="min-h-screen bg-slate-50 p-4 dark:bg-slate-900 sm:p-6">
    <div class="mx-auto max-w-7xl">

        <!-- Top Header -->
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <div
                    class="flex items-center gap-2 text-xs font-bold text-primary-600 dark:text-primary-400 uppercase tracking-widest mb-1">
                    <x-icon name="photo" class="w-4 h-4" />
                    Bons d'analyse imagerie
                </div>
                <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white flex items-center gap-3">
                    Echo abdominale
                    <x-icon name="chevron-right" class="w-6 h-6 text-slate-300" />
                </h1>
            </div>

            <div class="flex gap-2">
                <x-button icon="printer" color="amber" text="Imprimer" class="font-bold" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">

            <!-- SECTION GAUCHE : Saisie du Protocole (8 colonnes) -->
            <div class="space-y-6 lg:col-span-8">

                <!-- Étape 1 : Protocole -->
                <x-card shadow="md" class="border-none ring-1 ring-slate-200 dark:ring-slate-800">
                    <div class="flex items-center gap-3 mb-6">
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-600 text-sm font-bold text-white">1</span>
                        <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200 uppercase tracking-tight">
                            Protocole médical</h2>
                    </div>

                    <div class="space-y-5">
                        <x-textarea label="Clinique *" placeholder="Observations cliniques et motifs de l'examen..."
                            rows="4" resize-none />

                        <x-textarea label="Protocole *" placeholder="Description détaillée de l'examen effectué..."
                            rows="6" resize-none />

                        <x-textarea label="Conclusion *" placeholder="Résumé et conclusion diagnostique..."
                            rows="3" resize-none />
                    </div>

                    <x-slot:footer>
                        <div class="flex justify-end">
                            <x-button icon="arrow-down-tray" text="Enregistrer le protocole" color="primary"
                                class="w-full md:w-auto" />
                        </div>
                    </x-slot:footer>
                </x-card>

                <!-- Étape 2 : Upload des images -->
                <x-card shadow="md" class="border-none ring-1 ring-slate-200 dark:ring-slate-800">
                    <div class="flex items-center gap-3 mb-6">
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-600 text-sm font-bold text-white">2</span>
                        <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200 uppercase tracking-tight">
                            Résultat de l'imagerie</h2>
                    </div>

                    <div
                        class="p-8 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50/50 dark:bg-slate-800/20 text-center">
                        <x-icon name="cloud-arrow-up" class="mx-auto h-12 w-12 text-slate-400 mb-4" />
                        <div class="mb-4">
                            <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Glissez-déposez vos
                                clichés ici ou</span>
                        </div>
                        {{-- <x-upload id="images" multiple x:model="photos" /> --}}
                    </div>

                    <x-slot:footer>
                        <div class="flex justify-end">
                            <x-button outline icon="photo" text="Sauvegarder les images" color="slate"
                                class="w-full md:w-auto" />
                        </div>
                    </x-slot:footer>
                </x-card>
            </div>

            <!-- SECTION DROITE : Recap Patient (4 colonnes) -->
            <div class="lg:col-span-4">
                <div class="sticky top-6 space-y-4">

                    <!-- Fiche Patient Stylisée -->
                    <div
                        class="overflow-hidden rounded-2xl bg-white dark:bg-slate-800 shadow-xl ring-1 ring-slate-200 dark:ring-slate-800">
                        <div class="bg-primary-600 p-4 text-white">
                            <div class="text-xs uppercase opacity-80 font-bold mb-1">Référence Dossier</div>
                            <div class="text-2xl font-black tracking-widest">003-0904</div>
                        </div>

                        <div class="p-0 divide-y divide-slate-100 dark:divide-slate-700">
                            <div class="p-4 bg-slate-50/50 dark:bg-slate-800/40">
                                <label class="text-[10px] uppercase font-bold text-slate-400">Nom complet</label>
                                <p class="text-md font-bold text-primary-600 dark:text-primary-400">ELVIS EKILA BANTOLA
                                </p>
                            </div>

                            <div class="grid grid-cols-2">
                                <div class="p-4 border-r border-slate-100 dark:border-slate-700">
                                    <label class="text-[10px] uppercase font-bold text-slate-400">Âge</label>
                                    <p class="text-sm font-semibold dark:text-slate-200">01/09/2019 (7 ans)</p>
                                </div>
                                <div class="p-4">
                                    <label class="text-[10px] uppercase font-bold text-slate-400">Sexe</label>
                                    <p class="text-sm font-semibold dark:text-slate-200">Masculin</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2">
                                <div class="p-4 border-r border-slate-100 dark:border-slate-700">
                                    <label class="text-[10px] uppercase font-bold text-slate-400">Département</label>
                                    <p class="text-sm font-semibold dark:text-slate-200 text-emerald-600">Pédiatrie</p>
                                </div>
                                <div class="p-4">
                                    <label class="text-[10px] uppercase font-bold text-slate-400">Date</label>
                                    <p class="text-sm font-semibold dark:text-slate-200">09/04/2026</p>
                                </div>
                            </div>

                            <div class="p-4">
                                <label class="text-[10px] uppercase font-bold text-slate-400">Examen demandé</label>
                                <p class="text-sm font-bold text-slate-700 dark:text-slate-300">Echo abdominale</p>
                            </div>
                        </div>
                    </div>

                    <!-- Note d'aide -->
                    <div
                        class="flex items-start gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-800">
                        <x-icon name="information-circle" class="w-5 h-5 text-blue-500 mt-0.5" />
                        <p class="text-xs text-blue-700 dark:text-blue-400 leading-relaxed">
                            Assurez-vous de remplir tous les champs marqués d'une astérisque avant de générer le rapport
                            final pour impression.
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
