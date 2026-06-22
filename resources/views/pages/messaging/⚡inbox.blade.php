<?php

use App\Enums\ClinicalMessageCategory;
use App\Enums\ClinicalMessagePriority;
use App\Models\ClinicalMessage;
use App\Models\Configs\Departement;
use App\Models\DossierPatient;
use App\Models\MessageUserStatus;
use App\Models\User;
use App\Services\ClinicalMessagingService;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Title('Messagerie clinique interne'), Layout('layouts::app')] class extends Component {
    use WithFileUploads;
    use WithPagination;

    public string $folder = 'inbox';
    public ?int $selectedMessageId = null;
    public bool $composeOpen = false;

    public string $search = '';
    public string $filterCategory = '';
    public string $filterPriority = '';
    public string $filterDate = '';

    public string $subject = '';
    public string $body = '';
    public string $priority = 'normal';
    public string $category = 'coordination';
    public ?int $patientId = null;
    public array $recipientUserIds = [];
    public array $recipientGroups = [];
    public array $recipientDepartmentIds = [];
    public array $attachments = [];

    public string $replyBody = '';

    public function mount(): void
    {
        abort_unless(current_hopital_id(), 403, 'Selectionnez un hopital.');

        if (request()->integer('message')) {
            $this->selectedMessageId = request()->integer('message');
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterCategory(): void
    {
        $this->resetPage();
    }

    public function updatedFilterPriority(): void
    {
        $this->resetPage();
    }

    public function updatedFilterDate(): void
    {
        $this->resetPage();
    }

    public function switchFolder(string $folder): void
    {
        $this->folder = $folder;
        $this->selectedMessageId = null;
        $this->resetPage();
    }

    public function openCompose(): void
    {
        $this->composeOpen = true;
    }

    public function closeCompose(): void
    {
        $this->composeOpen = false;
    }

    public function selectMessage(int $messageId): void
    {
        $message = app(ClinicalMessagingService::class)->findInternalForUser($messageId, auth()->user());

        if ($message === null) {
            Flux::toast(variant: 'danger', heading: 'Message introuvable', text: 'Cette conversation n est pas accessible.');

            return;
        }

        $this->selectedMessageId = $messageId;
        app(ClinicalMessagingService::class)->markAsRead($message, auth()->user());
    }

    public function sendMessage(bool $draft = false): void
    {
        $this->validate([
            'subject' => ['required', 'string', 'max:180'],
            'body' => [$draft ? 'nullable' : 'required', 'string', 'max:20000'],
            'priority' => ['required', 'string'],
            'category' => ['required', 'string'],
            'recipientUserIds' => ['array'],
            'recipientGroups' => ['array'],
            'recipientDepartmentIds' => ['array'],
            'patientId' => ['nullable', 'integer'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,docx,xlsx,jpg,jpeg,png'],
        ]);

        try {
            $message = app(ClinicalMessagingService::class)->composeInternal(
                sender: auth()->user(),
                subject: $this->subject,
                body: $this->body,
                category: ClinicalMessageCategory::from($this->category),
                priority: ClinicalMessagePriority::from($this->priority),
                userIds: array_map('intval', $this->recipientUserIds),
                groupKeys: $this->recipientGroups,
                departmentIds: array_map('intval', $this->recipientDepartmentIds),
                patientId: $this->patientId,
                attachments: $this->attachments,
                draft: $draft,
            );
        } catch (Throwable $exception) {
            Flux::toast(variant: 'danger', heading: 'Envoi impossible', text: $exception->getMessage());

            return;
        }

        $this->reset(['subject', 'body', 'patientId', 'recipientUserIds', 'recipientGroups', 'recipientDepartmentIds', 'attachments']);
        $this->priority = 'normal';
        $this->category = 'coordination';
        $this->composeOpen = false;
        $this->selectedMessageId = $message->id;

        Flux::toast(variant: 'success', heading: $draft ? 'Brouillon sauvegarde' : 'Message envoye');
    }

    public function sendReply(): void
    {
        $this->validate(['replyBody' => ['required', 'string', 'max:12000']]);

        $message = $this->selectedMessageId
            ? app(ClinicalMessagingService::class)->findInternalForUser($this->selectedMessageId, auth()->user())
            : null;

        if ($message === null) {
            Flux::toast(variant: 'danger', heading: 'Conversation introuvable');

            return;
        }

        $reply = app(ClinicalMessagingService::class)->replyInternal($message, auth()->user(), $this->replyBody);

        $this->replyBody = '';
        $this->selectedMessageId = $reply->id;

        Flux::toast(variant: 'success', heading: 'Reponse envoyee');
    }

    public function toggleFlag(int $messageId, string $flag): void
    {
        $message = app(ClinicalMessagingService::class)->findInternalForUser($messageId, auth()->user());

        abort_unless($message, 403);

        app(ClinicalMessagingService::class)->toggleUserFlag($message, auth()->user(), $flag);
    }

    public function moveMessage(int $messageId, string $target): void
    {
        $message = app(ClinicalMessagingService::class)->findInternalForUser($messageId, auth()->user());

        abort_unless($message, 403);

        app(ClinicalMessagingService::class)->moveForUser($message, auth()->user(), $target);
        $this->selectedMessageId = null;

        Flux::toast(variant: 'success', heading: 'Conversation mise a jour');
    }

    public function statusFor(ClinicalMessage $message): ?MessageUserStatus
    {
        return $message->userStatuses->firstWhere('user_id', auth()->id());
    }

    public function isUnread(ClinicalMessage $message): bool
    {
        if ((int) $message->sender_id === (int) auth()->id()) {
            return false;
        }

        return $this->statusFor($message)?->read_at === null;
    }

    public function folderItems(array $counts): array
    {
        return [
            ['key' => 'inbox', 'label' => 'Boite de reception', 'icon' => 'inbox', 'count' => $counts['inbox'] ?? 0],
            ['key' => 'sent', 'label' => 'Messages envoyes', 'icon' => 'paper-airplane', 'count' => null],
            ['key' => 'drafts', 'label' => 'Brouillons', 'icon' => 'document', 'count' => null],
            ['key' => 'starred', 'label' => 'Favoris', 'icon' => 'star', 'count' => $counts['starred'] ?? 0],
            ['key' => 'important', 'label' => 'Important', 'icon' => 'exclamation-circle', 'count' => $counts['important'] ?? 0],
            ['key' => 'coordination', 'label' => 'Coordination', 'icon' => 'users', 'count' => null],
            ['key' => 'urgent', 'label' => 'Urgent', 'icon' => 'bolt', 'count' => $counts['urgent'] ?? 0],
            ['key' => 'service', 'label' => 'Service', 'icon' => 'building-office-2', 'count' => null],
            ['key' => 'archived', 'label' => 'Archives', 'icon' => 'archive-box', 'count' => null],
            ['key' => 'trash', 'label' => 'Corbeille', 'icon' => 'trash', 'count' => $counts['trash'] ?? 0],
        ];
    }

    public function tagClasses(string $tone): string
    {
        return match ($tone) {
            'emerald' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
            'sky' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
            'cyan' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-500/15 dark:text-cyan-300',
            'amber' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
            'violet' => 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300',
            'rose' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300',
            'red' => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300',
            'indigo' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300',
            default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
        };
    }
};
?>

@php
    $service = app(\App\Services\ClinicalMessagingService::class);
    $counts = $service->mailboxCounts(auth()->user());
    $messages = $service->internalMailbox(auth()->user(), $folder, [
        'search' => $search,
        'category' => $filterCategory,
        'priority' => $filterPriority,
        'date' => $filterDate,
    ], 18);
    $selected = $selectedMessageId ? $service->findInternalForUser($selectedMessageId, auth()->user()) : null;
    $conversation = $selected ? $service->conversationForUser($selected, auth()->user()) : collect();
    $users = \App\Models\User::query()
        ->where('hopital_id', current_hopital_id())
        ->where('id', '!=', auth()->id())
        ->orderBy('name')
        ->get(['id', 'name', 'grade', 'departement_id']);
    $departments = \App\Models\Configs\Departement::query()->orderBy('name')->get(['id', 'name']);
    $patients = \App\Models\DossierPatient::query()
        ->whereHopitalId(current_hopital_id())
        ->orderByDesc('id')
        ->limit(80)
        ->get(['id', 'nin', 'nom', 'prenom']);
    $groups = [
        'medecin' => 'Tous les medecins',
        'infirmiere' => 'Tous les infirmiers',
        'laborantin' => 'Tous les laborantins',
        'radiologue' => 'Tous les radiologues',
        'pharmacien' => 'Tous les pharmaciens',
        'secretaire' => 'Reception',
        'comptable' => 'Comptabilite',
        'administrateur' => 'Administrateurs',
    ];
@endphp

<div class="flex h-[calc(100vh-4rem)] min-h-[720px] bg-white text-slate-900 dark:bg-slate-950 dark:text-white">
    <aside class="hidden w-72 shrink-0 border-r border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/70 lg:block">
        <flux:button icon="pencil-square" color="indigo" class="mb-5 w-full justify-center" wire:click="openCompose">
            Nouveau message
        </flux:button>

        <nav class="space-y-1">
            @foreach ($this->folderItems($counts) as $item)
                <button type="button" wire:click="switchFolder('{{ $item['key'] }}')"
                    @class([
                        'flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-medium transition',
                        'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-200' => $folder === $item['key'],
                        'text-slate-600 hover:bg-white hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white' => $folder !== $item['key'],
                    ])>
                    <flux:icon :name="$item['icon']" class="size-4" />
                    <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                    @if ($item['count'])
                        <span class="rounded-full bg-white px-2 py-0.5 text-xs font-bold text-indigo-700 dark:bg-slate-950 dark:text-indigo-200">{{ $item['count'] }}</span>
                    @endif
                </button>
            @endforeach
        </nav>
    </aside>

    <main class="grid min-w-0 flex-1 grid-cols-1 xl:grid-cols-[minmax(420px,0.9fr),minmax(0,1.25fr)]">
        <section class="flex min-w-0 flex-col border-r border-slate-200 dark:border-slate-800">
            <div class="border-b border-slate-200 p-4 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <flux:button icon="pencil-square" color="indigo" class="lg:hidden" wire:click="openCompose">Nouveau</flux:button>
                    <div class="relative min-w-0 flex-1">
                        <flux:icon.magnifying-glass class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                        <input wire:model.live.debounce.350ms="search" type="search"
                            class="h-11 w-full rounded-lg border border-slate-200 bg-slate-50 pl-10 pr-3 text-sm outline-none transition focus:border-indigo-400 focus:bg-white dark:border-slate-800 dark:bg-slate-900 dark:focus:border-indigo-500"
                            placeholder="Rechercher expéditeur, patient, sujet, message..." />
                    </div>
                </div>

                <div class="mt-3 grid grid-cols-3 gap-2">
                    <select wire:model.live="filterCategory" class="h-9 rounded-lg border border-slate-200 bg-white px-2 text-xs dark:border-slate-800 dark:bg-slate-900">
                        <option value="">Categorie</option>
                        @foreach (\App\Enums\ClinicalMessageCategory::options() as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                    <select wire:model.live="filterPriority" class="h-9 rounded-lg border border-slate-200 bg-white px-2 text-xs dark:border-slate-800 dark:bg-slate-900">
                        <option value="">Urgence</option>
                        @foreach (\App\Enums\ClinicalMessagePriority::options() as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                    <input wire:model.live="filterDate" type="date" class="h-9 rounded-lg border border-slate-200 bg-white px-2 text-xs dark:border-slate-800 dark:bg-slate-900" />
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto">
                @forelse ($messages as $message)
                    @php
                        $status = $this->statusFor($message);
                        $unread = $this->isUnread($message);
                    @endphp
                    <article wire:key="message-{{ $message->id }}"
                        @class([
                            'group flex cursor-pointer gap-3 border-b border-slate-100 px-4 py-3 transition hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-900',
                            'bg-indigo-50/70 dark:bg-indigo-500/10' => $selectedMessageId === $message->id,
                            'font-semibold' => $unread,
                        ])
                        wire:click="selectMessage({{ $message->id }})">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-slate-200 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            {{ str($message->senderDisplayName())->substr(0, 2)->upper() }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start gap-2">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm">{{ $message->senderDisplayName() }}</p>
                                    <p class="truncate text-sm {{ $unread ? 'text-slate-950 dark:text-white' : 'text-slate-700 dark:text-slate-300' }}">{{ $message->subject }}</p>
                                </div>
                                <span class="shrink-0 text-xs text-slate-400">{{ $message->sent_at?->format('d/m H:i') ?? 'Brouillon' }}</span>
                            </div>
                            <p class="mt-1 truncate text-xs font-normal text-slate-500">{{ $message->excerpt() }}</p>
                            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                @if ($unread)
                                    <span class="size-2 rounded-full bg-indigo-500"></span>
                                @endif
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $this->tagClasses($message->category->tone()) }}">{{ $message->category->label() }}</span>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $message->priority->badgeClasses() }}">{{ $message->priority->label() }}</span>
                                @if ($message->attachments->isNotEmpty())
                                    <flux:icon.paper-clip class="size-3.5 text-slate-400" />
                                @endif
                                @if ($message->dossierPatient)
                                    <span class="truncate rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                        {{ $message->dossierPatient->nin }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="flex shrink-0 flex-col gap-1 opacity-70 group-hover:opacity-100">
                            <button type="button" wire:click.stop="toggleFlag({{ $message->id }}, 'starred')" class="rounded p-1 hover:bg-slate-200 dark:hover:bg-slate-800">
                                <flux:icon.star @class(['size-4', 'text-amber-500 fill-amber-500' => filled($status?->starred_at), 'text-slate-400' => blank($status?->starred_at)]) />
                            </button>
                            <button type="button" wire:click.stop="toggleFlag({{ $message->id }}, 'important')" class="rounded p-1 hover:bg-slate-200 dark:hover:bg-slate-800">
                                <flux:icon.exclamation-circle @class(['size-4', 'text-red-500' => filled($status?->important_at), 'text-slate-400' => blank($status?->important_at)]) />
                            </button>
                        </div>
                    </article>
                @empty
                    <div class="px-6 py-20 text-center text-sm text-slate-500">Aucun message dans ce dossier.</div>
                @endforelse
            </div>

            <div class="border-t border-slate-200 px-4 py-3 dark:border-slate-800">
                {{ $messages->links() }}
            </div>
        </section>

        <section class="hidden min-w-0 flex-col bg-slate-50 dark:bg-slate-950 xl:flex">
            @if ($selected)
                <div class="border-b border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <h1 class="truncate text-xl font-bold">{{ $selected->subject }}</h1>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ $selected->recipient_summary ? 'A: ' . $selected->recipient_summary : 'Conversation interne' }}
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <flux:button size="sm" variant="ghost" icon="archive-box" wire:click="moveMessage({{ $selected->id }}, 'archive')" />
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="moveMessage({{ $selected->id }}, 'trash')" />
                        </div>
                    </div>
                    @if ($selected->dossierPatient)
                        <a href="{{ route('patient.show', $selected->dossierPatient->id) }}" wire:navigate
                            class="mt-3 inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-200">
                            <flux:icon.folder class="size-4" />
                            Patient {{ $selected->dossierPatient->nin }} - {{ $selected->dossierPatient->prenom }} {{ $selected->dossierPatient->nom }}
                        </a>
                    @endif
                </div>

                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto p-5">
                    @foreach ($conversation as $item)
                        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex items-start gap-3">
                                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-200">
                                    {{ str($item->senderDisplayName())->substr(0, 2)->upper() }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div>
                                            <p class="text-sm font-bold">{{ $item->senderDisplayName() }}</p>
                                            <p class="text-xs text-slate-500">{{ $item->senderServiceLabel() }} - {{ $item->sent_at?->translatedFormat('d F Y H:i') }}</p>
                                        </div>
                                        <div class="flex gap-1.5">
                                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $this->tagClasses($item->category->tone()) }}">{{ $item->category->label() }}</span>
                                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $item->priority->badgeClasses() }}">{{ $item->priority->label() }}</span>
                                        </div>
                                    </div>
                                    @if ($item->parent)
                                        <blockquote class="mt-3 border-l-2 border-slate-300 pl-3 text-xs text-slate-500 dark:border-slate-700">
                                            {{ $item->parent->excerpt(180) }}
                                        </blockquote>
                                    @endif
                                    <div class="mt-3 whitespace-pre-wrap text-sm leading-7 text-slate-700 dark:text-slate-200">{{ $item->body }}</div>
                                    @if ($item->attachments->isNotEmpty())
                                        <div class="mt-4 flex flex-wrap gap-2">
                                            @foreach ($item->attachments as $attachment)
                                                <a href="{{ route('messaging.attachment.download', $attachment) }}"
                                                    class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-xs font-medium text-slate-600 hover:border-indigo-300 hover:text-indigo-700 dark:border-slate-800 dark:text-slate-300">
                                                    <flux:icon.paper-clip class="size-4" />
                                                    {{ $attachment->original_name }} ({{ $attachment->humanSize() }})
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="border-t border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                    <textarea wire:model="replyBody" rows="3" class="w-full rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm outline-none focus:border-indigo-400 dark:border-slate-800 dark:bg-slate-950" placeholder="Repondre a tous..."></textarea>
                    <div class="mt-3 flex justify-end">
                        <flux:button icon="paper-airplane" color="indigo" wire:click="sendReply">Envoyer</flux:button>
                    </div>
                </div>
            @else
                <div class="flex h-full items-center justify-center p-8 text-center text-sm text-slate-500">
                    Selectionnez une conversation pour afficher le fil complet.
                </div>
            @endif
        </section>
    </main>

    @if ($composeOpen)
        <div class="fixed inset-0 z-50 flex items-end justify-end bg-slate-950/30 p-4">
            <section class="flex max-h-[88vh] w-full max-w-3xl flex-col overflow-hidden rounded-lg border border-slate-200 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-900">
                <header class="flex items-center justify-between border-b border-slate-200 px-5 py-3 dark:border-slate-800">
                    <h2 class="text-sm font-bold">Nouveau message interne</h2>
                    <button type="button" wire:click="closeCompose" class="rounded p-1 hover:bg-slate-100 dark:hover:bg-slate-800">
                        <flux:icon.x-mark class="size-5" />
                    </button>
                </header>

                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto p-5">
                    <input wire:model="subject" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-indigo-400 dark:border-slate-800 dark:bg-slate-950" placeholder="Sujet" />

                    <div class="grid gap-3 md:grid-cols-3">
                        <select wire:model="priority" class="h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm dark:border-slate-800 dark:bg-slate-950">
                            @foreach (\App\Enums\ClinicalMessagePriority::options() as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                        <select wire:model="category" class="h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm dark:border-slate-800 dark:bg-slate-950">
                            @foreach (\App\Enums\ClinicalMessageCategory::options() as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                        <select wire:model="patientId" class="h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm dark:border-slate-800 dark:bg-slate-950">
                            <option value="">Aucun patient lie</option>
                            @foreach ($patients as $patient)
                                <option value="{{ $patient->id }}">{{ $patient->nin }} - {{ $patient->prenom }} {{ $patient->nom }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <p class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-500">Utilisateurs</p>
                            <div class="max-h-44 space-y-1 overflow-y-auto rounded-lg border border-slate-200 p-2 dark:border-slate-800">
                                @foreach ($users as $user)
                                    <label class="flex items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-slate-50 dark:hover:bg-slate-800">
                                        <input type="checkbox" wire:model="recipientUserIds" value="{{ $user->id }}" class="rounded border-slate-300">
                                        <span class="min-w-0 truncate">{{ $user->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <p class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-500">Groupes</p>
                            <div class="space-y-1 rounded-lg border border-slate-200 p-2 dark:border-slate-800">
                                @foreach ($groups as $key => $label)
                                    <label class="flex items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-slate-50 dark:hover:bg-slate-800">
                                        <input type="checkbox" wire:model="recipientGroups" value="{{ $key }}" class="rounded border-slate-300">
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <p class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-500">Services</p>
                            <div class="max-h-44 space-y-1 overflow-y-auto rounded-lg border border-slate-200 p-2 dark:border-slate-800">
                                @foreach ($departments as $department)
                                    <label class="flex items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-slate-50 dark:hover:bg-slate-800">
                                        <input type="checkbox" wire:model="recipientDepartmentIds" value="{{ $department->id }}" class="rounded border-slate-300">
                                        <span class="min-w-0 truncate">{{ $department->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <textarea wire:model="body" rows="9" class="w-full rounded-lg border border-slate-200 p-3 text-sm leading-6 outline-none focus:border-indigo-400 dark:border-slate-800 dark:bg-slate-950" placeholder="Message..."></textarea>

                    <div>
                        <input type="file" wire:model="attachments" multiple accept=".pdf,.docx,.xlsx,.jpg,.jpeg,.png"
                            class="block w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100" />
                    </div>
                </div>

                <footer class="flex items-center justify-between border-t border-slate-200 px-5 py-3 dark:border-slate-800">
                    <flux:button variant="ghost" icon="document" wire:click="sendMessage(true)">Sauver brouillon</flux:button>
                    <flux:button icon="paper-airplane" color="indigo" wire:click="sendMessage(false)">Envoyer</flux:button>
                </footer>
            </section>
        </div>
    @endif
</div>
