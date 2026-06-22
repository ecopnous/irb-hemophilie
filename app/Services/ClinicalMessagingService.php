<?php

namespace App\Services;

use App\Enums\ClinicalMessageCategory;
use App\Enums\ClinicalMessagePriority;
use App\Enums\ClinicalMessageStatus;
use App\Models\ClinicalMessage;
use App\Models\ClinicalMessageAttachment;
use App\Models\ClinicalMessageAudit;
use App\Models\ClinicalMessageRecipient;
use App\Models\ClinicalMessageTemplate;
use App\Models\Consultation;
use App\Models\Configs\Departement;
use App\Models\DossierPatient;
use App\Models\Laboratoire;
use App\Models\MessageLabel;
use App\Models\MessageMention;
use App\Models\MessageThread;
use App\Models\MessageUserStatus;
use App\Models\User;
use App\Notifications\ClinicalMessagePatientMail;
use App\Notifications\InternalClinicalMessageNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ClinicalMessagingService
{
    /**
     * @param  array<int>  $userIds
     * @param  array<string>  $groupKeys
     * @param  array<int>  $departmentIds
     * @param  array<int, UploadedFile|TemporaryUploadedFile>  $attachments
     */
    public function composeInternal(
        User $sender,
        string $subject,
        string $body,
        ClinicalMessageCategory $category,
        ClinicalMessagePriority $priority = ClinicalMessagePriority::Normal,
        array $userIds = [],
        array $groupKeys = [],
        array $departmentIds = [],
        ?int $patientId = null,
        array $attachments = [],
        bool $draft = false,
        ?int $threadId = null,
        ?int $parentId = null,
    ): ClinicalMessage {
        $recipientUsers = $draft
            ? collect()
            : $this->resolveInternalRecipients($sender, $userIds, $groupKeys, $departmentIds);

        if (! $draft && $recipientUsers->isEmpty()) {
            throw new \InvalidArgumentException('Selectionnez au moins un destinataire.');
        }

        return DB::transaction(function () use ($sender, $subject, $body, $category, $priority, $patientId, $attachments, $draft, $threadId, $parentId, $recipientUsers, $groupKeys, $departmentIds): ClinicalMessage {
            $thread = $threadId
                ? MessageThread::query()->whereHopitalId(current_hopital_id())->findOrFail($threadId)
                : MessageThread::query()->create([
                    'hopital_id' => current_hopital_id() ?? $sender->hopital_id,
                    'dossier_patient_id' => $patientId,
                    'subject' => $subject,
                    'category' => $category,
                    'priority' => $priority,
                    'last_message_at' => $draft ? null : now(),
                ]);

            $message = ClinicalMessage::query()->create([
                'hopital_id' => current_hopital_id() ?? $sender->hopital_id,
                'thread_id' => $thread->id,
                'dossier_patient_id' => $patientId,
                'parent_id' => $parentId,
                'sender_id' => $sender->id,
                'sender_type' => 'user',
                'message_type' => 'internal',
                'category' => $category,
                'priority' => $priority,
                'subject' => $subject,
                'body' => $body,
                'recipient_summary' => $recipientUsers->pluck('name')->take(4)->implode(', '),
                'status' => $draft ? ClinicalMessageStatus::Draft : ClinicalMessageStatus::Sent,
                'sent_at' => $draft ? null : now(),
                'last_activity_at' => $draft ? now() : now(),
                'metadata' => [
                    'group_keys' => array_values($groupKeys),
                    'department_ids' => array_values($departmentIds),
                ],
            ]);

            $this->ensureUserStatus($message, $sender, read: true);

            foreach ($recipientUsers as $recipient) {
                ClinicalMessageRecipient::query()->create([
                    'clinical_message_id' => $message->id,
                    'recipient_type' => 'user',
                    'recipient_id' => $recipient->id,
                    'display_name' => $recipient->name,
                    'routing_key' => $recipient->grade ?: 'user',
                    'channel' => 'in_app',
                ]);

                $this->ensureUserStatus($message, $recipient);
            }

            $this->storeAttachments($message, $attachments);
            $this->captureMentions($message);
            $this->audit($message, $sender, $draft ? 'draft_saved' : 'sent');

            if (! $draft) {
                $thread->forceFill(['last_message_at' => $message->sent_at])->save();
                $this->notifyInternalRecipients($message, $recipientUsers);
            }

            return $message->load(['thread', 'sender.departement', 'dossierPatient', 'recipients.user', 'attachments']);
        });
    }

    public function replyInternal(ClinicalMessage $parent, User $sender, string $body, bool $replyAll = true): ClinicalMessage
    {
        $parent->loadMissing('recipients');

        $userIds = $replyAll
            ? $parent->recipients
                ->where('recipient_type', 'user')
                ->pluck('recipient_id')
                ->push($parent->sender_id)
                ->filter()
                ->unique()
                ->reject(fn (int $id) => $id === $sender->id)
                ->values()
                ->all()
            : array_filter([(int) $parent->sender_id]);

        return $this->composeInternal(
            sender: $sender,
            subject: Str::startsWith($parent->subject, 'Re:') ? $parent->subject : 'Re: ' . $parent->subject,
            body: $body,
            category: $parent->category,
            priority: $parent->priority,
            userIds: $userIds,
            patientId: $parent->dossier_patient_id,
            threadId: $parent->thread_id,
            parentId: $parent->id,
        );
    }

    public function internalMailbox(User $user, string $folder = 'inbox', array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return ClinicalMessage::query()
            ->with(['sender.departement', 'dossierPatient:id,nin,nom,prenom', 'recipients', 'attachments', 'userStatuses' => fn ($query) => $query->where('user_id', $user->id)])
            ->whereHopitalId(current_hopital_id())
            ->where('message_type', 'internal')
            ->when($folder === 'drafts', fn (Builder $query) => $query->where('sender_id', $user->id)->where('status', ClinicalMessageStatus::Draft))
            ->when($folder !== 'drafts', fn (Builder $query) => $query->where('status', ClinicalMessageStatus::Sent))
            ->when($folder === 'sent', fn (Builder $query) => $query->where('sender_id', $user->id))
            ->when(! in_array($folder, ['sent', 'drafts'], true), function (Builder $query) use ($user): void {
                $query->where(function (Builder $nested) use ($user): void {
                    $nested
                        ->where('sender_id', $user->id)
                        ->orWhereHas('recipients', fn (Builder $recipient) => $recipient
                            ->where('recipient_type', 'user')
                            ->where('recipient_id', $user->id));
                });
            })
            ->when($folder === 'inbox', fn (Builder $query) => $query
                ->whereHas('recipients', fn (Builder $recipient) => $recipient
                    ->where('recipient_type', 'user')
                    ->where('recipient_id', $user->id))
                ->whereHas('userStatuses', fn (Builder $status) => $status->where('user_id', $user->id)->whereNull('archived_at')->whereNull('deleted_at')))
            ->when($folder === 'starred', fn (Builder $query) => $query
                ->whereHas('userStatuses', fn (Builder $status) => $status->where('user_id', $user->id)->whereNotNull('starred_at')->whereNull('deleted_at')))
            ->when($folder === 'important', fn (Builder $query) => $query
                ->whereHas('userStatuses', fn (Builder $status) => $status->where('user_id', $user->id)->whereNotNull('important_at')->whereNull('deleted_at')))
            ->when($folder === 'archived', fn (Builder $query) => $query
                ->whereHas('userStatuses', fn (Builder $status) => $status->where('user_id', $user->id)->whereNotNull('archived_at')->whereNull('deleted_at')))
            ->when($folder === 'trash', fn (Builder $query) => $query
                ->whereHas('userStatuses', fn (Builder $status) => $status->where('user_id', $user->id)->whereNotNull('deleted_at')))
            ->when(in_array($folder, ['coordination', 'urgent', 'service'], true), function (Builder $query) use ($folder, $user): void {
                if ($folder === 'coordination') {
                    $query->where('category', ClinicalMessageCategory::Coordination);
                } elseif ($folder === 'urgent') {
                    $query->whereIn('priority', [ClinicalMessagePriority::Urgent, ClinicalMessagePriority::Critical]);
                } else {
                    $query->whereHas('recipients', fn (Builder $recipient) => $recipient
                        ->where('recipient_type', 'service')
                        ->orWhere('routing_key', 'departement:' . $user->departement_id));
                }
            })
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
                $term = '%' . str($filters['search'])->squish() . '%';
                $query->where(function (Builder $search) use ($term): void {
                    $search->where('subject', 'like', $term)
                        ->orWhere('body', 'like', $term)
                        ->orWhere('recipient_summary', 'like', $term)
                        ->orWhereHas('sender', fn (Builder $sender) => $sender->where('name', 'like', $term))
                        ->orWhereHas('dossierPatient', fn (Builder $patient) => $patient
                            ->where('nin', 'like', $term)
                            ->orWhere('nom', 'like', $term)
                            ->orWhere('prenom', 'like', $term));
                });
            })
            ->when(filled($filters['category'] ?? null), fn (Builder $query) => $query->where('category', $filters['category']))
            ->when(filled($filters['priority'] ?? null), fn (Builder $query) => $query->where('priority', $filters['priority']))
            ->when(filled($filters['date'] ?? null), fn (Builder $query) => $query->whereDate('sent_at', $filters['date']))
            ->orderByDesc('last_activity_at')
            ->orderByDesc('sent_at')
            ->paginate($perPage);
    }

    /**
     * @return Collection<int, ClinicalMessage>
     */
    public function conversationForUser(ClinicalMessage $message, User $user): Collection
    {
        $authorized = $this->findInternalForUser($message->id, $user);

        abort_unless($authorized, 403);

        return ClinicalMessage::query()
            ->with(['sender.departement', 'recipients.user', 'attachments', 'dossierPatient'])
            ->where('thread_id', $message->thread_id)
            ->where('status', ClinicalMessageStatus::Sent)
            ->orderBy('sent_at')
            ->get();
    }

    public function findInternalForUser(int $messageId, User $user): ?ClinicalMessage
    {
        return ClinicalMessage::query()
            ->with(['sender.departement', 'dossierPatient', 'recipients.user', 'attachments', 'userStatuses' => fn ($query) => $query->where('user_id', $user->id)])
            ->whereHopitalId(current_hopital_id())
            ->where('message_type', 'internal')
            ->where(function (Builder $query) use ($user): void {
                $query
                    ->where('sender_id', $user->id)
                    ->orWhereHas('recipients', fn (Builder $recipient) => $recipient
                        ->where('recipient_type', 'user')
                        ->where('recipient_id', $user->id));
            })
            ->find($messageId);
    }

    public function mailboxCounts(User $user): array
    {
        $base = ClinicalMessage::query()
            ->whereHopitalId(current_hopital_id())
            ->where('message_type', 'internal')
            ->where('status', ClinicalMessageStatus::Sent)
            ->whereHas('userStatuses', fn (Builder $status) => $status->where('user_id', $user->id));

        return [
            'inbox' => (clone $base)->whereHas('userStatuses', fn (Builder $status) => $status->where('user_id', $user->id)->whereNull('read_at')->whereNull('deleted_at'))->count(),
            'starred' => (clone $base)->whereHas('userStatuses', fn (Builder $status) => $status->where('user_id', $user->id)->whereNotNull('starred_at')->whereNull('deleted_at'))->count(),
            'important' => (clone $base)->whereHas('userStatuses', fn (Builder $status) => $status->where('user_id', $user->id)->whereNotNull('important_at')->whereNull('deleted_at'))->count(),
            'urgent' => (clone $base)->whereIn('priority', [ClinicalMessagePriority::Urgent, ClinicalMessagePriority::Critical])->count(),
            'trash' => (clone $base)->whereHas('userStatuses', fn (Builder $status) => $status->where('user_id', $user->id)->whereNotNull('deleted_at'))->count(),
        ];
    }

    public function toggleUserFlag(ClinicalMessage $message, User $user, string $flag): void
    {
        $status = $this->ensureUserStatus($message, $user);
        $column = match ($flag) {
            'starred' => 'starred_at',
            'important' => 'important_at',
            default => throw new \InvalidArgumentException('Marqueur invalide.'),
        };

        $status->forceFill([$column => $status->{$column} ? null : now()])->save();
        $this->audit($message, $user, $flag . '_toggled');
    }

    public function moveForUser(ClinicalMessage $message, User $user, string $target): void
    {
        $status = $this->ensureUserStatus($message, $user);

        $status->forceFill(match ($target) {
            'archive' => ['archived_at' => now(), 'deleted_at' => null],
            'trash' => ['deleted_at' => now()],
            'restore' => ['archived_at' => null, 'deleted_at' => null],
            default => [],
        })->save();

        $this->audit($message, $user, 'moved_' . $target);
    }

    /**
     * @param  array<int>  $staffRecipientIds
     * @param  array<int, UploadedFile|TemporaryUploadedFile>  $attachments
     */
    public function sendToPatient(
        DossierPatient $patient,
        string $subject,
        string $body,
        ClinicalMessageCategory $category,
        ClinicalMessagePriority $priority = ClinicalMessagePriority::Normal,
        ?int $consultationId = null,
        array $staffRecipientIds = [],
        ?User $sender = null,
        array $attachments = [],
        bool $notifyPatientEmail = true,
    ): ClinicalMessage {
        $sender ??= Auth::user();

        $message = DB::transaction(function () use ($patient, $subject, $body, $category, $priority, $consultationId, $staffRecipientIds, $sender, $attachments): ClinicalMessage {
            $message = ClinicalMessage::query()->create([
                'hopital_id' => $patient->hopital_id,
                'dossier_patient_id' => $patient->id,
                'consultation_id' => $consultationId,
                'sender_id' => $sender?->id,
                'sender_type' => 'user',
                'category' => $category,
                'priority' => $priority,
                'subject' => $subject,
                'body' => $body,
                'status' => ClinicalMessageStatus::Sent,
                'sent_at' => now(),
            ]);

            ClinicalMessageRecipient::query()->create([
                'clinical_message_id' => $message->id,
                'recipient_type' => 'patient',
                'recipient_id' => $patient->id,
                'channel' => filled($patient->email) ? 'email' : 'in_app',
            ]);

            foreach (array_unique($staffRecipientIds) as $userId) {
                if ($sender && (int) $userId === (int) $sender->id) {
                    continue;
                }

                ClinicalMessageRecipient::query()->create([
                    'clinical_message_id' => $message->id,
                    'recipient_type' => 'user',
                    'recipient_id' => $userId,
                    'channel' => 'in_app',
                ]);
            }

            $this->storeAttachments($message, $attachments);

            return $message->load(['sender.departement', 'recipients', 'dossierPatient', 'attachments']);
        });

        if ($notifyPatientEmail) {
            $this->notifyPatientByEmail($message);
        }

        return $message;
    }

    public function sendSystemMessage(
        DossierPatient $patient,
        string $subject,
        string $body,
        ClinicalMessageCategory $category,
        ClinicalMessagePriority $priority = ClinicalMessagePriority::Normal,
        ?int $consultationId = null,
        array $metadata = [],
        bool $notifyPatientEmail = true,
        array $staffRecipientIds = [],
    ): ClinicalMessage {
        $message = DB::transaction(function () use ($patient, $subject, $body, $category, $priority, $consultationId, $metadata, $staffRecipientIds): ClinicalMessage {
            $message = ClinicalMessage::query()->create([
                'hopital_id' => $patient->hopital_id,
                'dossier_patient_id' => $patient->id,
                'consultation_id' => $consultationId,
                'sender_id' => null,
                'sender_type' => 'system',
                'category' => $category,
                'priority' => $priority,
                'subject' => $subject,
                'body' => $body,
                'status' => ClinicalMessageStatus::Sent,
                'sent_at' => now(),
                'metadata' => $metadata,
            ]);

            ClinicalMessageRecipient::query()->create([
                'clinical_message_id' => $message->id,
                'recipient_type' => 'patient',
                'recipient_id' => $patient->id,
                'channel' => filled($patient->email) ? 'email' : 'in_app',
            ]);

            foreach (array_unique($staffRecipientIds) as $userId) {
                ClinicalMessageRecipient::query()->create([
                    'clinical_message_id' => $message->id,
                    'recipient_type' => 'user',
                    'recipient_id' => $userId,
                    'channel' => 'in_app',
                ]);
            }

            return $message->load(['dossierPatient', 'attachments']);
        });

        if ($notifyPatientEmail) {
            $this->notifyPatientByEmail($message);
        }

        return $message;
    }

    public function sendConsultationClosedSummary(Consultation $consultation): ?ClinicalMessage
    {
        $consultation->loadMissing('dossierPatient');

        $patient = $consultation->dossierPatient;

        if ($patient === null) {
            return null;
        }

        $issueLabel = $this->consultationIssueLabel($consultation);

        $body = implode("\n\n", array_filter([
            'Bonjour ' . ucfirst((string) $patient->prenom) . ',',
            'Votre consultation du ' . $consultation->created_at?->translatedFormat('d F Y') . ' a ete classee.',
            'Issue : ' . $issueLabel . '.',
            filled($consultation->cause_issue) ? 'Precision : ' . $consultation->cause_issue : null,
            'Consultez votre messagerie clinique pour toute consigne complementaire ou prochaine etape de prise en charge.',
            'En cas de question urgente, contactez directement votre etablissement.',
        ]));

        return $this->sendSystemMessage(
            patient: $patient,
            subject: 'Compte-rendu de fin de consultation',
            body: $body,
            category: ClinicalMessageCategory::Suivi,
            consultationId: $consultation->id,
            metadata: ['trigger' => 'consultation_closed', 'issue' => $consultation->issue],
        );
    }

    public function sendAppointmentReminder(DossierPatient $patient, \DateTimeInterface $appointmentAt, ?User $doctor = null): ClinicalMessage
    {
        $doctorName = $doctor?->name ?? 'votre medecin';
        $dateHeure = $appointmentAt->format('d/m/Y à H:i');

        $body = implode("\n\n", [
            'Bonjour ' . ucfirst((string) $patient->prenom) . ',',
            'Nous vous rappelons votre rendez-vous prevu dans 48 heures.',
            'Date et heure : ' . $dateHeure,
            'Medecin : Dr ' . $doctorName,
            'Merci de vous presenter a l heure convenue. En cas d empechement, contactez la reception.',
        ]);

        return $this->sendSystemMessage(
            patient: $patient,
            subject: 'Rappel de rendez-vous dans 48 heures',
            body: $body,
            category: ClinicalMessageCategory::Rappel,
            metadata: ['trigger' => 'appointment_reminder_48h'],
        );
    }

    public function sendLabResultsValidated(Laboratoire $laboratoire): ?ClinicalMessage
    {
        if ($this->labResultsAlreadyNotified($laboratoire->id)) {
            return null;
        }

        $laboratoire->loadMissing([
            'consultation.dossierPatient',
            'consultation.user',
            'consultation.departement',
            'consultation.actes',
        ]);

        $consultation = $laboratoire->consultation;
        $patient = $consultation?->dossierPatient;

        if ($patient === null || $consultation === null) {
            return null;
        }

        $examensList = $this->buildValidatedLabExamensList($consultation);
        $templateService = app(ClinicalMessageTemplateService::class);

        $template = $this->resolveResultTemplate(
            $patient->hopital_id,
            $consultation->departement_id,
            'laboratoire',
        );

        if ($template !== null) {
            $rendered = $templateService->render($template, $patient, $consultation, [
                'examens_labo' => $examensList,
            ]);

            $subject = $rendered['subject'];
            $body = $rendered['body'];
        } else {
            $subject = 'Resultats de laboratoire disponibles';
            $body = implode("\n\n", [
                'Bonjour ' . ucfirst((string) $patient->prenom) . ',',
                'Vos resultats de laboratoire ont ete valides.',
                "Examens concernes :\n" . $examensList,
                'Votre medecin referent a ete informe. Consultez votre messagerie clinique ou contactez l etablissement pour la suite.',
            ]);
        }

        $staffIds = array_filter([(int) $consultation->user_id]);

        return $this->sendSystemMessage(
            patient: $patient,
            subject: $subject,
            body: $body,
            category: ClinicalMessageCategory::Resultats,
            consultationId: $consultation->id,
            metadata: [
                'trigger' => 'lab_results_validated',
                'laboratoire_id' => $laboratoire->id,
                'results_detail' => $this->buildLabResultsDetailForStaff($consultation),
            ],
            staffRecipientIds: $staffIds,
        );
    }

    public function sendImagingResultsValidated(Imagerie $imagerie): ?ClinicalMessage
    {
        if ($this->imagingResultsAlreadyNotified($imagerie->id)) {
            return null;
        }

        $imagerie->loadMissing([
            'consultation.dossierPatient',
            'consultation.user',
            'consultation.departement',
            'consultation.actes.departement',
        ]);

        $consultation = $imagerie->consultation;
        $patient = $consultation?->dossierPatient;

        if ($patient === null || $consultation === null) {
            return null;
        }

        $examensList = $this->buildValidatedImagingExamensList($consultation);
        $templateService = app(ClinicalMessageTemplateService::class);

        $template = $this->resolveResultTemplate(
            $patient->hopital_id,
            $consultation->departement_id,
            'imagerie',
        );

        if ($template !== null) {
            $rendered = $templateService->render($template, $patient, $consultation, [
                'examens_imagerie' => $examensList,
            ]);

            $subject = $rendered['subject'];
            $body = $rendered['body'];
        } else {
            $subject = 'Compte rendu d imagerie disponible';
            $body = implode("\n\n", [
                'Bonjour ' . ucfirst((string) $patient->prenom) . ',',
                'Votre compte rendu d imagerie a ete finalise.',
                "Examens concernes :\n" . $examensList,
                'Votre medecin referent a ete informe. Consultez votre messagerie clinique ou contactez l etablissement pour la suite.',
            ]);
        }

        $staffIds = array_filter([(int) $consultation->user_id]);

        return $this->sendSystemMessage(
            patient: $patient,
            subject: $subject,
            body: $body,
            category: ClinicalMessageCategory::Resultats,
            consultationId: $consultation->id,
            metadata: [
                'trigger' => 'imaging_results_validated',
                'imagerie_id' => $imagerie->id,
                'results_detail' => $this->buildImagingResultsDetailForStaff($consultation),
            ],
            staffRecipientIds: $staffIds,
        );
    }

    /**
     * @return Collection<int, ClinicalMessage>
     */
    public function messagesForPatient(DossierPatient $patient): Collection
    {
        return ClinicalMessage::query()
            ->with(['sender.departement', 'attachments'])
            ->where('dossier_patient_id', $patient->id)
            ->whereHopitalId(current_hopital_id())
            ->where('status', ClinicalMessageStatus::Sent)
            ->orderByDesc('sent_at')
            ->get();
    }

    public function inboxFor(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return ClinicalMessage::query()
            ->with(['sender.departement', 'dossierPatient:id,nin,nom,prenom', 'recipients', 'attachments'])
            ->whereHopitalId(current_hopital_id())
            ->where('status', ClinicalMessageStatus::Sent)
            ->where(function (Builder $query) use ($user): void {
                $query
                    ->where('sender_id', $user->id)
                    ->orWhereHas('recipients', function (Builder $recipientQuery) use ($user): void {
                        $recipientQuery
                            ->where('recipient_type', 'user')
                            ->where('recipient_id', $user->id);
                    });
            })
            ->orderByDesc('sent_at')
            ->paginate($perPage);
    }

    public function unreadCountFor(User $user): int
    {
        return ClinicalMessageRecipient::query()
            ->where('recipient_type', 'user')
            ->where('recipient_id', $user->id)
            ->whereNull('read_at')
            ->whereHas('message', function (Builder $query): void {
                $query
                    ->whereHopitalId(current_hopital_id())
                    ->where('message_type', 'internal')
                    ->where('status', ClinicalMessageStatus::Sent);
            })
            ->count();
    }

    public function markAsRead(ClinicalMessage $message, User $user): void
    {
        ClinicalMessageRecipient::query()
            ->where('clinical_message_id', $message->id)
            ->where('recipient_type', 'user')
            ->where('recipient_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($message->message_type === 'internal') {
            $status = $this->ensureUserStatus($message, $user);

            if ($status->read_at === null) {
                $status->forceFill(['read_at' => now()])->save();
                $this->audit($message, $user, 'read');
            }
        }
    }

    public function findForUser(int $messageId, User $user): ?ClinicalMessage
    {
        return ClinicalMessage::query()
            ->with(['sender.departement', 'dossierPatient', 'recipients', 'attachments'])
            ->whereHopitalId(current_hopital_id())
            ->where('status', ClinicalMessageStatus::Sent)
            ->where(function (Builder $query) use ($user): void {
                $query
                    ->where('sender_id', $user->id)
                    ->orWhereHas('recipients', function (Builder $recipientQuery) use ($user): void {
                        $recipientQuery
                            ->where('recipient_type', 'user')
                            ->where('recipient_id', $user->id);
                    });
            })
            ->find($messageId);
    }

    public function canAccessAttachment(ClinicalMessageAttachment $attachment, User $user): bool
    {
        $attachment->loadMissing('message');

        $message = $attachment->message;

        if ($message === null || $message->hopital_id !== current_hopital_id()) {
            return false;
        }

        if ($this->findForUser($message->id, $user) !== null) {
            return true;
        }

        return DossierPatient::query()
            ->whereHopitalId(current_hopital_id())
            ->where('id', $message->dossier_patient_id)
            ->exists();
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function staffRecipientOptions(?User $exclude = null): array
    {
        return User::query()
            ->when($exclude, fn (Builder $query) => $query->where('id', '!=', $exclude->id))
            ->whereNotNull('email')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name])
            ->all();
    }

    /**
     * @param  array<int>  $userIds
     * @param  array<string>  $groupKeys
     * @param  array<int>  $departmentIds
     * @return \Illuminate\Support\Collection<int, User>
     */
    protected function resolveInternalRecipients(User $sender, array $userIds, array $groupKeys, array $departmentIds): \Illuminate\Support\Collection
    {
        $query = User::query()
            ->where('hopital_id', current_hopital_id() ?? $sender->hopital_id)
            ->where('id', '!=', $sender->id)
            ->where(function (Builder $recipientQuery) use ($userIds, $groupKeys, $departmentIds): void {
                $recipientQuery->whereIn('id', array_filter($userIds));

                foreach (array_filter($groupKeys) as $groupKey) {
                    $recipientQuery->orWhere('grade', $groupKey);
                }

                if ($departmentIds !== []) {
                    $recipientQuery->orWhereIn('departement_id', array_filter($departmentIds));
                }
            });

        return $query->orderBy('name')->get()->unique('id')->values();
    }

    protected function ensureUserStatus(ClinicalMessage $message, User $user, bool $read = false): MessageUserStatus
    {
        return MessageUserStatus::query()->firstOrCreate(
            [
                'clinical_message_id' => $message->id,
                'user_id' => $user->id,
            ],
            [
                'read_at' => $read ? now() : null,
            ],
        );
    }

    protected function captureMentions(ClinicalMessage $message): void
    {
        preg_match_all('/@([A-Za-zÀ-ÿ0-9._ -]{2,60})/u', $message->body, $matches);

        $names = collect($matches[1] ?? [])
            ->map(fn (string $name) => trim($name))
            ->filter()
            ->unique();

        if ($names->isEmpty()) {
            return;
        }

        User::query()
            ->where('hopital_id', $message->hopital_id)
            ->where(function (Builder $query) use ($names): void {
                foreach ($names as $name) {
                    $query->orWhere('name', 'like', '%' . $name . '%');
                }
            })
            ->get()
            ->each(function (User $user) use ($message): void {
                MessageMention::query()->firstOrCreate([
                    'clinical_message_id' => $message->id,
                    'user_id' => $user->id,
                ]);
            });
    }

    /**
     * @param  \Illuminate\Support\Collection<int, User>  $recipients
     */
    protected function notifyInternalRecipients(ClinicalMessage $message, \Illuminate\Support\Collection $recipients): void
    {
        $message->loadMissing(['sender', 'attachments']);

        $recipients->each(function (User $recipient) use ($message): void {
            $recipient->notify(new InternalClinicalMessageNotification($message));
        });
    }

    protected function audit(ClinicalMessage $message, ?User $actor, string $action, array $payload = []): void
    {
        ClinicalMessageAudit::query()->create([
            'clinical_message_id' => $message->id,
            'message_thread_id' => $message->thread_id,
            'actor_id' => $actor?->id,
            'action' => $action,
            'payload' => $payload === [] ? null : $payload,
        ]);
    }

    protected function notifyPatientByEmail(ClinicalMessage $message): void
    {
        $message->loadMissing(['dossierPatient', 'attachments']);

        $email = $message->dossierPatient?->email;

        if (! filled($email)) {
            return;
        }

        Notification::route('mail', $email)
            ->notify(new ClinicalMessagePatientMail($message));

        ClinicalMessageRecipient::query()
            ->where('clinical_message_id', $message->id)
            ->where('recipient_type', 'patient')
            ->where('recipient_id', $message->dossier_patient_id)
            ->update(['delivery_status' => 'sent']);
    }

    /**
     * @param  array<int, UploadedFile|TemporaryUploadedFile>  $attachments
     */
    protected function storeAttachments(ClinicalMessage $message, array $attachments): void
    {
        foreach ($attachments as $file) {
            $path = $file->store('clinical-messages/' . $message->id, 'local');

            ClinicalMessageAttachment::query()->create([
                'clinical_message_id' => $message->id,
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize() ?: 0,
            ]);
        }
    }

    protected function consultationIssueLabel(Consultation $consultation): string
    {
        return match ($consultation->issue) {
            'ambulatoire' => 'Soins ambulatoires',
            'hospitalisation' => 'Hospitalisation',
            'suivi_medical' => 'Suivi medical',
            'transfert' => 'Transfert',
            'deces' => 'Deces',
            'autres' => filled($consultation->autre_issue) ? (string) $consultation->autre_issue : 'Autre',
            default => ucfirst((string) $consultation->issue),
        };
    }

    protected function labResultsAlreadyNotified(int $laboratoireId): bool
    {
        return ClinicalMessage::query()
            ->where('metadata->trigger', 'lab_results_validated')
            ->where('metadata->laboratoire_id', $laboratoireId)
            ->exists();
    }

    protected function imagingResultsAlreadyNotified(int $imagerieId): bool
    {
        return ClinicalMessage::query()
            ->where('metadata->trigger', 'imaging_results_validated')
            ->where('metadata->imagerie_id', $imagerieId)
            ->exists();
    }

    protected function resolveResultTemplate(?int $hopitalId, ?int $departementId, string $nameContains): ?ClinicalMessageTemplate
    {
        return ClinicalMessageTemplate::query()
            ->active()
            ->forContext($hopitalId, $departementId)
            ->where('category', ClinicalMessageCategory::Resultats)
            ->where('name', 'like', '%' . $nameContains . '%')
            ->orderByDesc('hopital_id')
            ->orderBy('sort_order')
            ->first();
    }

    protected function isImagingActe(mixed $acte): bool
    {
        if (data_get($acte, 'pivot.ref') === 'imagerie') {
            return true;
        }

        $departement = $acte->departement ?? null;

        if ($departement === null) {
            return false;
        }

        $name = strtolower((string) $departement->name);
        $ref = strtolower((string) ($departement->ref ?? ''));

        return str_contains($name, 'imagerie') || $ref === 'img';
    }

    protected function acteIsDocumentedForImaging(mixed $acte): bool
    {
        return filled(data_get($acte, 'pivot.clinique'))
            || filled(data_get($acte, 'pivot.protocole'))
            || filled(data_get($acte, 'pivot.cloture'));
    }

    protected function buildValidatedImagingExamensList(Consultation $consultation): string
    {
        $lines = $consultation->actes
            ->filter(fn ($acte) => $this->isImagingActe($acte) && $this->acteIsDocumentedForImaging($acte))
            ->map(fn ($acte) => '- ' . ($acte->name ?? 'Examen imagerie'))
            ->values();

        return $lines->isEmpty()
            ? '- Examens d imagerie'
            : $lines->implode("\n");
    }

    protected function buildImagingResultsDetailForStaff(Consultation $consultation): string
    {
        return $consultation->actes
            ->filter(fn ($acte) => $this->isImagingActe($acte) && $this->acteIsDocumentedForImaging($acte))
            ->map(function ($acte): string {
                $name = $acte->name ?? 'Examen imagerie';
                $parts = array_filter([
                    filled(data_get($acte, 'pivot.clinique')) ? 'Clinique : ' . data_get($acte, 'pivot.clinique') : null,
                    filled(data_get($acte, 'pivot.protocole')) ? 'Protocole : ' . data_get($acte, 'pivot.protocole') : null,
                    filled(data_get($acte, 'pivot.cloture')) ? 'Conclusion : ' . data_get($acte, 'pivot.cloture') : null,
                ]);

                return $name . (empty($parts) ? '' : ' — ' . implode(' | ', $parts));
            })
            ->implode("\n");
    }

    protected function buildValidatedLabExamensList(Consultation $consultation): string
    {
        $lines = $consultation->actes
            ->filter(fn ($acte) => data_get($acte, 'pivot.ref') === 'labo' && (bool) data_get($acte, 'pivot.valide'))
            ->map(fn ($acte) => '- ' . ($acte->name ?? $acte->designation ?? 'Examen'))
            ->values();

        return $lines->isEmpty()
            ? '- Examens de laboratoire'
            : $lines->implode("\n");
    }

    protected function buildLabResultsDetailForStaff(Consultation $consultation): string
    {
        return $consultation->actes
            ->filter(fn ($acte) => data_get($acte, 'pivot.ref') === 'labo' && (bool) data_get($acte, 'pivot.valide'))
            ->map(function ($acte): string {
                $name = $acte->name ?? $acte->designation ?? 'Examen';
                $result = data_get($acte, 'pivot.resultat');

                return filled($result)
                    ? "{$name} : {$result}"
                    : "{$name} : (resultat en attente de saisie)";
            })
            ->implode("\n");
    }
}
