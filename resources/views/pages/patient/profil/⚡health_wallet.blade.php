<?php

use App\Enums\ClinicalMessageCategory;
use App\Enums\ClinicalMessagePriority;
use App\Models\DossierPatient;
use App\Services\ClinicalMessagingService;
use App\Services\ClinicalMessageTemplateService;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::app.other.profil_medical')] class extends Component {
    use WithFileUploads;

    public DossierPatient $patient;

    public bool $showComposeModal = false;

    public string $composeSubject = '';

    public string $composeBody = '';

    public string $composeCategory = 'suivi';

    public string $composePriority = 'normal';

    public bool $composeNotifyPatientEmail = true;

    /** @var array<int> */
    public array $composeStaffRecipients = [];

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $composeAttachments = [];

    public ?int $selectedTemplateId = null;

    public function mount(int $id): void
    {
        abort_unless(current_hopital_id(), 403, 'Selectionnez un hopital.');

        $this->patient = DossierPatient::query()
            ->whereHopitalId(current_hopital_id())
            ->findOrFail($id);
    }

    #[Computed]
    public function messages(): Collection
    {
        return app(ClinicalMessagingService::class)->messagesForPatient($this->patient);
    }

    #[Computed]
    public function categoryOptions(): array
    {
        return ClinicalMessageCategory::options();
    }

    #[Computed]
    public function staffRecipientOptions(): array
    {
        return app(ClinicalMessagingService::class)->staffRecipientOptions(auth()->user());
    }

    #[Computed]
    public function messageTemplates(): Collection
    {
        $lastConsultation = $this->patient->consultations()->latest('created_at')->first();

        return app(ClinicalMessageTemplateService::class)
            ->availableTemplates($lastConsultation?->departement_id);
    }

    public function updatedSelectedTemplateId(?int $value): void
    {
        if ($value === null) {
            return;
        }

        $template = app(ClinicalMessageTemplateService::class)->find($value);

        if ($template === null) {
            return;
        }

        $lastConsultation = $this->patient->consultations()->latest('created_at')->with('user')->first();

        $rendered = app(ClinicalMessageTemplateService::class)->render(
            $template,
            $this->patient,
            $lastConsultation,
        );

        $this->composeSubject = $rendered['subject'];
        $this->composeBody = $rendered['body'];
        $this->composeCategory = $rendered['category'];
    }

    public function openComposeModal(): void
    {
        $this->resetComposeForm();
        $this->showComposeModal = true;
    }

    public function sendMessage(): void
    {
        $validated = $this->validate([
            'composeSubject' => ['required', 'string', 'max:255'],
            'composeBody' => ['required', 'string', 'max:10000'],
            'composeCategory' => ['required', 'in:' . implode(',', array_column(ClinicalMessageCategory::options(), 'value'))],
            'composePriority' => ['required', 'in:normal,urgent'],
            'composeStaffRecipients' => ['array'],
            'composeStaffRecipients.*' => ['integer', 'exists:users,id'],
            'composeNotifyPatientEmail' => ['boolean'],
            'composeAttachments' => ['array', 'max:3'],
            'composeAttachments.*' => ['file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
        ]);

        app(ClinicalMessagingService::class)->sendToPatient(
            patient: $this->patient,
            subject: $validated['composeSubject'],
            body: $validated['composeBody'],
            category: ClinicalMessageCategory::from($validated['composeCategory']),
            priority: ClinicalMessagePriority::from($validated['composePriority']),
            staffRecipientIds: $validated['composeStaffRecipients'] ?? [],
            sender: auth()->user(),
            attachments: $this->composeAttachments,
            notifyPatientEmail: $validated['composeNotifyPatientEmail'] ?? true,
        );

        $this->showComposeModal = false;
        $this->resetComposeForm();
        unset($this->messages);

        Flux::toast(
            variant: 'success',
            heading: 'Message envoye',
            text: filled($this->patient->email) && $this->composeNotifyPatientEmail
                ? 'Message enregistre et email envoye au patient.'
                : 'Le message a ete ajoute a la messagerie clinique du patient.',
        );
    }

    public function tagClasses(string $tone): string
    {
        return match ($tone) {
            'emerald' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
            'sky' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
            'amber' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
            'violet' => 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300',
            'rose' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300',
            default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
        };
    }

    public function initials(string $sender): string
    {
        return collect(explode(' ', trim($sender)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
            ->implode('');
    }

    protected function resetComposeForm(): void
    {
        $this->composeSubject = '';
        $this->composeBody = '';
        $this->composeCategory = ClinicalMessageCategory::Suivi->value;
        $this->composePriority = ClinicalMessagePriority::Normal->value;
        $this->composeNotifyPatientEmail = filled($this->patient->email);
        $this->composeStaffRecipients = [];
        $this->composeAttachments = [];
        $this->selectedTemplateId = null;
        $this->resetValidation();
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
                ['label' => 'Messagerie clinique', 'icon' => 'envelope'],
            ]" />
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                Messagerie clinique
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                ID: {{ $patient->nin }} {{ ucfirst($patient->prenom) }} {{ ucfirst($patient->nom) }}
                {{ $patient->ins ? 'N°' . $patient->ins : '' }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <div
                class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Messages</p>
                <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ $this->messages->count() }}</p>
            </div>
            <flux:button variant="primary" icon="plus" wire:click="openComposeModal">
                Nouveau message
            </flux:button>
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
            <x-card header="Fil de messages" minimize>
                @if ($this->messages->isEmpty())
                    <div class="rounded-xl border border-dashed border-slate-200 px-6 py-12 text-center dark:border-slate-700">
                        <flux:icon.envelope class="mx-auto h-10 w-10 text-slate-300" />
                        <p class="mt-4 text-sm font-semibold text-slate-700 dark:text-slate-200">Aucun message pour ce patient</p>
                        <p class="mt-1 text-sm text-slate-500">Envoyez des consignes, rappels ou informations de suivi.</p>
                        <flux:button class="mt-4" variant="primary" size="sm" wire:click="openComposeModal">
                            Composer un message
                        </flux:button>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($this->messages as $message)
                            <details class="group overflow-hidden rounded-md border border-slate-200 bg-slate-50 transition dark:bg-slate-950/40">
                                <summary class="list-none cursor-pointer px-4 py-4 sm:px-5">
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                        <div class="flex items-start gap-4">
                                            <div
                                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-slate-200 text-xs font-black text-slate-600 dark:bg-slate-800 dark:text-slate-200">
                                                {{ $this->initials($message->senderDisplayName()) }}
                                            </div>

                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="text-base font-bold text-slate-900 dark:text-white">
                                                        {{ $message->senderDisplayName() }}
                                                    </p>
                                                    <span
                                                        class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $this->tagClasses($message->category->tone()) }}">
                                                        {{ $message->category->label() }}
                                                    </span>
                                                    @if ($message->priority === \App\Enums\ClinicalMessagePriority::Urgent)
                                                        <span class="rounded-full bg-red-100 px-2.5 py-1 text-[11px] font-bold text-red-700 dark:bg-red-500/15 dark:text-red-300">
                                                            Urgent
                                                        </span>
                                                    @endif
                                                </div>
                                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                                    <b>Objet:</b> {{ $message->subject }}
                                                </p>
                                                <p class="mt-1 text-xs text-slate-400">{{ $message->senderServiceLabel() }}</p>
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-between gap-3 lg:flex-col lg:items-end">
                                            <div class="text-right">
                                                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                                                    {{ $message->sent_at?->translatedFormat('d M Y') }}
                                                </p>
                                                <p class="text-xs text-slate-400">{{ $message->sent_at?->format('H:i') }}</p>
                                            </div>
                                            <flux:icon.chevron-down class="h-4 w-4 text-slate-400 transition group-open:rotate-180" />
                                        </div>
                                    </div>
                                </summary>

                                <div
                                    class="border-t border-slate-200 bg-slate-50/70 px-4 py-5 dark:border-slate-800 dark:bg-slate-900/60 sm:px-5">
                                    <div class="space-y-4 whitespace-pre-wrap text-sm leading-7 text-slate-600 dark:text-slate-300">
                                        {{ $message->body }}

                                        @if ($message->attachments->isNotEmpty())
                                            <div class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900">
                                                <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Pieces jointes</p>
                                                <ul class="mt-2 space-y-1">
                                                    @foreach ($message->attachments as $attachment)
                                                        <li>
                                                            <a href="{{ route('messaging.attachment.download', $attachment) }}"
                                                                class="text-sm text-sky-600 hover:underline">
                                                                {{ $attachment->original_name }} ({{ $attachment->humanSize() }})
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif

                                        <div class="pt-2">
                                            <p class="font-semibold text-slate-900 dark:text-white">Cordialement,</p>
                                            <p>{{ $message->senderDisplayName() }}</p>
                                        </div>
                                    </div>
                                </div>
                            </details>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </section>
    </div>

    <flux:modal wire:model.self="showComposeModal" class="max-w-2xl">
        <form wire:submit="sendMessage" class="space-y-5">
            <div>
                <flux:heading size="lg">Nouveau message</flux:heading>
                <flux:subheading>
                    Message pour {{ ucfirst($patient->prenom) }} {{ ucfirst($patient->nom) }}
                </flux:subheading>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold">Modele (optionnel)</label>
                    <select wire:model.live="selectedTemplateId"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
                        <option value="">— Choisir un modele —</option>
                        @foreach ($this->messageTemplates as $template)
                            <option value="{{ $template->id }}">{{ $template->name }} ({{ $template->category->label() }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold">Categorie *</label>
                    <select wire:model="composeCategory"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
                        @foreach ($this->categoryOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold">Priorite *</label>
                    <select wire:model="composePriority"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
                        <option value="normal">Normal</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold">Objet *</label>
                <input type="text" wire:model="composeSubject"
                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900"
                    placeholder="Ex: Consignes apres consultation" />
                @error('composeSubject') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold">Message *</label>
                <textarea wire:model="composeBody" rows="6"
                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900"
                    placeholder="Redigez votre message clinique..."></textarea>
                @error('composeBody') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold">Notifier un collegue (optionnel)</label>
                <select wire:model="composeStaffRecipients" multiple
                    class="min-h-28 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
                    @foreach ($this->staffRecipientOptions as $staff)
                        <option value="{{ $staff['id'] }}">{{ $staff['name'] }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-500">Maintenez Ctrl/Cmd pour selectionner plusieurs personnes.</p>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold">Pieces jointes (PDF, JPG, PNG — max 3 x 5 Mo)</label>
                <input type="file" wire:model="composeAttachments" multiple accept=".pdf,.jpg,.jpeg,.png"
                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900" />
                @error('composeAttachments.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            @if ($patient->email)
                <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                    <input type="checkbox" wire:model="composeNotifyPatientEmail" class="rounded border-slate-300" />
                    Envoyer une copie par email au patient ({{ $patient->email }})
                </label>
            @else
                <p class="text-xs text-amber-600">Aucun email patient — message visible uniquement dans la messagerie interne.</p>
            @endif

            <div class="flex justify-end gap-3">
                <flux:button type="button" variant="ghost" wire:click="$set('showComposeModal', false)">
                    Annuler
                </flux:button>
                <flux:button type="submit" variant="primary" icon="paper-airplane">
                    Envoyer
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
