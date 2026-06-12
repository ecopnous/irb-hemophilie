<?php

use App\Models\DossierPatient;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::app.other.profil_medical')] class extends Component {
    public DossierPatient $patient;

    public array $messages = [];

    public function mount(int $id): void
    {
        $this->patient = DossierPatient::findOrFail($id);

        $this->messages = [
            [
                'id' => 1,
                'sender' => 'Dr Alexandra Della',
                'service' => 'Pediatrie',
                'recipient' => $this->patient->email ?: 'patient@exemple.cd',
                'date' => '09 mai 2026',
                'time' => '08:35',
                'tag' => 'Suivi',
                'tone' => 'emerald',
                'subject' => 'Mise a jour du suivi clinique et rappel de consultation',
                'preview' => 'Bonjour, merci de consulter ce message concernant la poursuite du suivi et les prochaines etapes de prise en charge.',
                'body' => ['Bonjour ' . ucfirst((string) $this->patient->prenom) . ',', 'Nous vous confirmons que votre dernier passage a bien ete enregistre dans votre dossier medical. Cette boite permettra aux medecins de vous transmettre des consignes, rappels et informations pratiques.', 'Les futurs messages pourront contenir un rappel de rendez-vous, un retour apres interpretation clinique, une orientation vers le laboratoire ou l imagerie, ainsi que des conseils de suivi personnalises.', 'Merci de garder cette boite active pour ne manquer aucune information importante.'],
                'signature' => 'Dr Alexandra Della',
                'open' => false,
            ],
            [
                'id' => 2,
                'sender' => 'Dr Marianne Audrey',
                'service' => 'Medecine interne',
                'recipient' => $this->patient->email ?: 'patient@exemple.cd',
                'date' => '07 mai 2026',
                'time' => '14:10',
                'tag' => 'Resultats',
                'tone' => 'sky',
                'subject' => 'Reception des resultats et conduite a tenir',
                'preview' => 'Un message type pour annoncer la disponibilite d un compte rendu ou d un resultat en attente de validation.',
                'body' => ['Ce modele de message accueillera les notifications de resultats ou d informations transmises apres relecture medicale.', 'Le contenu final pourra etre adapte selon le service, le type de consultation et la situation du patient.'],
                'signature' => 'Dr Marianne Audrey',
                'open' => false,
            ],
            [
                'id' => 3,
                'sender' => 'Dr Samuel Kanku',
                'service' => 'Coordination clinique',
                'recipient' => $this->patient->email ?: 'patient@exemple.cd',
                'date' => '05 mai 2026',
                'time' => '09:00',
                'tag' => 'Orientation',
                'tone' => 'amber',
                'subject' => 'Orientation vers un service complementaire',
                'preview' => 'Exemple de notification orientant le patient vers un autre pole de prise en charge.',
                'body' => ['Cette carte est prevue pour les messages brefs lies a une orientation, un rappel administratif ou une communication entre medecin et patient.', 'Les accordions sont deja prets a etre relies plus tard aux vraies donnees de messagerie.'],
                'signature' => 'Dr Samuel Kanku',
                'open' => false,
            ],
        ];
    }

    public function tagClasses(string $tone): string
    {
        return match ($tone) {
            'emerald' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
            'sky' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
            'amber' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
            default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
        };
    }

    public function initials(string $sender): string
    {
        return collect(explode(' ', trim($sender)))
            ->filter()
            ->take(2)
            ->map(fn($part) => strtoupper(substr($part, 0, 1)))
            ->implode('');
    }
};
?>

<div class="transition-colors duration-300">
    <div class="mx-auto mb-8 flex max-w-7xl flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <x-breadcrumbs :items="[
                ['label' => 'Accueil', 'link' => 'dashboard', 'icon' => 'home'],
                ['label' => 'Dossiers patients', 'link' => 'patient.index', 'icon' => 'folder'],
                ['label' => $patient->nin, 'link' => route('patient.show', $patient->id), 'icon' => 'identification'],
                ['label' => 'Boite d\'envoie', 'icon' => 'envelope'],
            ]" />
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                Boite d'envoie
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                ID: {{ $patient->nin }} {{ ucfirst($patient->prenom) }} {{ ucfirst($patient->nom) }}
                {{ $patient->ins ? 'N°' . $patient->ins : '' }}
            </p>
        </div>

        <div class="flex flex-wrap gap-3">
            <div
                class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Messages</p>
                <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ count($messages) }}</p>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-7xl space-y-6">
        <section class="grid gap-4 lg:grid-cols-[1.05fr,2.4fr]">
            <div class="rounded-md border border-slate-200 bg-slate-50 p-4 dark:bg-slate-800/70">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">CONSIGNES ET SUIVI</p>
                <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-white">
                    {{ $patient->email ?: 'Aucune adresse email renseignee' }}
                </p>
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    Rappels, consignes apres consultation, messages de coordination et informations de suivi.
                </p>
            </div>
            <x-card header="Messagerie clinique" minimize>
                <div class="space-y-3">
                    @foreach ($messages as $message)
                        <details
                            class='group overflow-hidden rounded-md border border-slate-200 bg-slate-50 transition dark:bg-slate-950/40',
                            @if ($message['open']) open @endif>
                            <summary class="list-none cursor-pointer px-4 py-4 sm:px-5">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="flex items-start gap-4">
                                        <div
                                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-slate-200 text-xs font-black text-slate-600 dark:bg-slate-800 dark:text-slate-200">
                                            {{ $this->initials($message['sender']) }}
                                        </div>

                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="text-base font-bold text-slate-900 dark:text-white">
                                                    {{ $message['sender'] }}</p>
                                                <span
                                                    class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $this->tagClasses($message['tone']) }}">
                                                    {{ $message['tag'] }}
                                                </span>
                                            </div>
                                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                                <b>Objet:</b> {{ $message['subject'] }}
                                            </p>
                                            {{-- <h3 class="mt-3 text-lg font-semibold text-slate-900 dark:text-white">
                                                {{ $message['subject'] }}
                                            </h3>
                                            <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                                                {{ $message['preview'] }}
                                            </p> --}}
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between gap-3 lg:flex-col lg:items-end">
                                        <div class="text-right">
                                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                                                {{ $message['date'] }}</p>
                                            <p class="text-xs text-slate-400">{{ $message['time'] }}</p>
                                        </div>
                                        <div class="flex items-center gap-2 text-slate-400">
                                            <flux:icon.arrow-path-rounded-square class="h-4 w-4" />
                                            <flux:icon.star class="h-4 w-4" />
                                            <flux:icon.chevron-down class="h-4 w-4 transition group-open:rotate-180" />
                                        </div>
                                    </div>
                                </div>
                            </summary>

                            <div
                                class="border-t border-slate-200 bg-slate-50/70 px-4 py-5 dark:border-slate-800 dark:bg-slate-900/60 sm:px-5">
                                <div class="space-y-5 text-sm leading-7 text-slate-600 dark:text-slate-300">
                                    @foreach ($message['body'] as $paragraph)
                                        <p>{{ $paragraph }}</p>
                                    @endforeach

                                    <div class="pt-2">
                                        <p class="font-semibold text-slate-900 dark:text-white">Cordialement,</p>
                                        <p>{{ $message['signature'] }}</p>
                                    </div>
                                </div>
                            </div>
                        </details>
                    @endforeach
                </div>
            </x-card>
        </section>
    </div>
</div>
