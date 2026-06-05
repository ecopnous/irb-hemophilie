<?php
use App\Models\DossierPatient;
use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts::app.other.profil_medical')] class extends Component {
    public $patient;
    public function mount($id)
    {
        $this->patient = DossierPatient::findOrFail($id);
        view()->share('current_patient', $id);
    }
};
?>

<div class="transition-colors duration-300">
    <x-patient.patient-profil-header :nav="[
        ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
        ['label' => 'Dossiers patients', 'link' => 'patient.index', 'icon' => 'folder'],
        ['label' => $patient->nin, 'icon' => 'identification'],
    ]" :patient="$patient" :current_patient="$current_patient">
        <x-slot name="title">{{ ucfirst($patient->nom) }} {{ ucfirst($patient->postnom) }}
            {{ ucfirst($patient->prenom) }}</x-slot>
        <x-slot name="subtitle">ID: {{ $patient->nin }}
            {{ $patient->ins ? 'N°' . $patient->ins : '' }}</x-slot>
    </x-patient.patient-profil-header>

    <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8">
        <div class="lg:col-span-4 space-y-6">
            <div
                class="bg-white dark:bg-slate-800 rounded-[2rem] shadow-xl shadow-gray-200/50 dark:shadow-none border border-transparent dark:border-slate-700 overflow-hidden">
                <div class="h-32 bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500"></div>
                <div class="px-6 pb-8 text-center">
                    <div class="relative -mt-16 mb-4 inline-block">
                        <img class="h-32 w-32 rounded-[2rem] object-cover border-4 border-white dark:border-slate-800 shadow-2xl mx-auto"
                            src="{{ $patient->photo ? Storage::disk('public')->url($patient->photo) : 'https://ui-avatars.com/api/?background=6366f1&color=fff&name=' . $patient->prenom . '+' . $patient->nom }}"
                            alt="Photo Patient">
                        @if ($patient->is_dead)
                            <div
                                class="absolute -bottom-2 -right-2 bg-gray-900 text-white text-[10px] px-2 py-1 rounded-lg border-2 border-white uppercase font-black tracking-tighter">
                                Décédé</div>
                        @endif
                    </div>

                    <h2 class="text-2xl font-black text-gray-900 dark:text-white">{{ Str::lower($patient->prenom) }}
                        {{ Str::lower($patient->nom) }}</h2>
                    <p class="text-indigo-600 dark:text-indigo-400 font-bold uppercase text-xs tracking-widest mt-1">
                        {{ $patient->genre == 'M' ? 'Masculin' : 'Féminin' }} •
                        {{ \Carbon\Carbon::parse($patient->date_naissance)->age }} Ans</p>

                    <div class="mt-8 flex flex-col gap-3 text-left">
                        <div class="flex items-center p-3 bg-gray-50 dark:bg-slate-700/50 rounded-2xl">
                            <div
                                class="w-10 h-10 flex items-center justify-center bg-white dark:bg-slate-800 rounded-xl shadow-sm mr-4">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path
                                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                                    </path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wider">Téléphone</p>
                                <p class="text-sm font-semibold text-gray-700 dark:text-slate-200">
                                    {{ $patient->telephone ?? '+243 ...' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center p-3 bg-gray-50 dark:bg-slate-700/50 rounded-2xl">
                            <div
                                class="w-10 h-10 flex items-center justify-center bg-white dark:bg-slate-800 rounded-xl shadow-sm mr-4">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wider">Email</p>
                                <p class="text-sm font-semibold text-gray-700 dark:text-slate-200">
                                    {{ $patient->email ?? 'Non renseigné' }}</p>
                            </div>
                        </div>
                        <flux:button href="{{ route('patient.init_consult', $current_patient) }}" variant="primary"
                            color="indigo" wire:navigate>Nouvelle consultation</flux:button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div
                    class="bg-white dark:bg-slate-800 p-4 rounded-3xl border border-transparent dark:border-slate-700 shadow-sm">
                    <p class="text-[10px] font-bold text-gray-400 uppercase mb-2">Hémophile</p>
                    <span
                        class="px-2 py-1 {{ $patient->is_hemophile ? 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400' : 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400' }} text-[10px] rounded-lg font-black">
                        {{ $patient->is_hemophile ? 'OUI' : 'NON' }}
                    </span>
                </div>
                <div
                    class="bg-white dark:bg-slate-800 p-4 rounded-3xl border border-transparent dark:border-slate-700 shadow-sm">
                    <p class="text-[10px] font-bold text-gray-400 uppercase mb-2">Anémique</p>
                    <span
                        class="px-2 py-1 {{ $patient->is_anemique ? 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400' }} text-[10px] rounded-lg font-black">
                        {{ $patient->is_anemique ? 'OUI' : 'NON' }}
                    </span>
                </div>
            </div>
        </div>

        <div class="lg:col-span-8 space-y-6">

            <div
                class="bg-white dark:bg-slate-800 rounded-[2rem] p-8 shadow-sm border border-transparent dark:border-slate-700">
                <div class="flex items-center mb-8">
                    <div class="w-1.5 h-6 bg-indigo-600 rounded-full mr-3"></div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Filiation & Origines</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                    <div class="relative p-6 rounded-2xl bg-indigo-50/50 dark:bg-slate-700/30">
                        <span
                            class="absolute -top-3 left-6 px-3 py-1 bg-indigo-600 text-white text-[10px] font-black rounded-full uppercase">Père</span>
                        <h4 class="text-lg font-bold text-gray-800 dark:text-slate-200 mb-4">
                            {{ ucfirst($patient->nom_pere) }}
                        </h4>
                        <div class="space-y-2 text-sm">
                            <p class="flex justify-between text-gray-500 dark:text-slate-400 ">Province: <span
                                    class="font-bold text-gray-700 dark:text-slate-200">{{ ucfirst($patient->province_pere ?? '-') }}</span>
                            </p>
                            <p class="flex justify-between text-gray-500 dark:text-slate-400">Profession: <span
                                    class="font-bold text-gray-700 dark:text-slate-200">{{ ucfirst($patient->profession_pere ?? '-') }}</span>
                            </p>
                            <p class="flex justify-between text-gray-500 dark:text-slate-400 ">Tribu: <span
                                    class="font-bold text-gray-700 dark:text-slate-200">{{ ucfirst($patient->tribut_pere ?? '-') }}</span>
                            </p>
                        </div>
                    </div>

                    <div class="relative p-6 rounded-2xl bg-pink-50/50 dark:bg-slate-700/30">
                        <span
                            class="absolute -top-3 left-6 px-3 py-1 bg-pink-600 text-white text-[10px] font-black rounded-full uppercase">Mère</span>
                        <h4 class="text-lg font-bold text-gray-800 dark:text-slate-200 mb-4">
                            {{ ucfirst($patient->nom_mere) }}
                        </h4>
                        <div class="space-y-2 text-sm">
                            <p class="flex justify-between text-gray-500 dark:text-slate-400">Province: <span
                                    class="font-bold text-gray-700 dark:text-slate-200">{{ ucfirst($patient->province_mere ?? '-') }}</span>
                            </p>
                            <p class="flex justify-between text-gray-500 dark:text-slate-400">Profession: <span
                                    class="font-bold text-gray-700 dark:text-slate-200">{{ ucfirst($patient->profession_mere ?? '-') }}</span>
                            </p>
                            <p class="flex justify-between text-gray-500 dark:text-slate-400">Tribu: <span
                                    class="font-bold text-gray-700 dark:text-slate-200">{{ ucfirst($patient->tribut_mere ?? '-') }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div
                    class="bg-white dark:bg-slate-800 rounded-[2rem] p-6 shadow-sm border border-transparent dark:border-slate-700">
                    <h3 class="text-sm font-black text-gray-400 uppercase tracking-widest mb-4">Détails Naissance</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-slate-400 text-sm">Poids de naissance</span>
                            <span
                                class="px-3 py-1 bg-gray-100 dark:bg-slate-700 rounded-full font-bold text-gray-800 dark:text-slate-200">{{ $patient->poids_naissance ?? 'N/A' }}
                                kg</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-slate-400 text-sm">Rang dans la fratrie</span>
                            <span
                                class="text-indigo-600 dark:text-indigo-400 font-black">{{ $patient->rang_fratrie ?? '1' }}
                                / {{ $patient->nb_freres + $patient->nb_soeurs + 1 }}</span>
                        </div>
                    </div>
                </div>

                <div
                    class="bg-white dark:bg-slate-800 rounded-[2rem] p-6 shadow-sm border border-transparent dark:border-slate-700">
                    <h3 class="text-sm font-black text-gray-400 uppercase tracking-widest mb-4">Adresse Actuelle</h3>
                    <p class="text-gray-800 dark:text-slate-200 font-medium">
                        {{ $patient->num_habitation }}, Av. {{ $patient->avenue }}<br>
                        Q. {{ $patient->quartier }}, C. {{ $patient->commune->name ?? '-' }}
                    </p>
                    <p class="text-xs text-indigo-500 mt-2 font-bold">{{ $patient->ville->name ?? '-' }},
                        {{ $patient->province->name ?? '-' }}</p>
                </div>
            </div>

            <div
                class="bg-white dark:bg-slate-800 rounded-[2rem] p-8 shadow-sm border border-transparent dark:border-slate-700">
                <h3 class="text-sm font-black text-gray-400 uppercase tracking-widest mb-4">Observations Médicales</h3>
                <div class="relative">
                    <div class="absolute -left-4 top-0 bottom-0 w-1 bg-amber-400 rounded-full"></div>
                    <p class="text-gray-600 dark:text-slate-300 italic leading-relaxed pl-4">
                        {{ ucfirst($patient->note ?? 'Aucune note clinique particulière pour ce dossier.') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
    <div
        class="max-w-7xl mx-auto mt-4 bg-white dark:bg-slate-800 rounded-[2rem] p-8 shadow-sm border border-transparent dark:border-slate-700">
        <h3 class="text-sm font-black text-gray-400 uppercase tracking-widest mb-4">Tags liés au dossier du patient</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach ($patient->tags as $tag)
                <div class="relative">
                    <div class="absolute -left-4 top-0 bottom-0 w-1 bg-amber-400 rounded-full"></div>
                    <p class="text-gray-600 dark:text-slate-300 italic leading-relaxed pl-4">
                        {{ $tag->name }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>
    <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight my-6">
        Visite Programmée
    </h1>

    <livewire:visite-programme-for-patient-table :dossierPatientId="$patient->id" />
</div>
