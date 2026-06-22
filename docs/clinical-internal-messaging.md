# Messagerie clinique interne

## Objectif

Ce module fournit une messagerie interne indépendante des communications patient. Les patients ne sont jamais destinataires des messages `message_type = internal`; le dossier patient est uniquement un contexte optionnel.

## Architecture

```mermaid
flowchart LR
    UI[Livewire Inbox Gmail-like] --> Service[ClinicalMessagingService]
    Service --> Messages[clinical_messages]
    Service --> Threads[message_threads]
    Service --> Recipients[clinical_message_recipients]
    Service --> Statuses[message_user_statuses]
    Service --> Files[Laravel Storage]
    Service --> Audit[clinical_message_audits]
    Service --> Notify[Laravel Notifications]
    Notify --> DB[(notifications)]
    Notify --> Mail[Email optionnel]
    Notify -. futur .-> Reverb[Echo/Reverb temps reel]
```

## Tables

```mermaid
erDiagram
    message_threads ||--o{ clinical_messages : contains
    clinical_messages ||--o{ clinical_message_recipients : routes
    clinical_messages ||--o{ message_user_statuses : per_user_state
    clinical_messages ||--o{ clinical_message_attachments : files
    clinical_messages ||--o{ message_mentions : mentions
    clinical_messages ||--o{ message_labels : labels
    clinical_messages ||--o{ clinical_message_audits : audits
    users ||--o{ clinical_messages : sends
    users ||--o{ message_user_statuses : owns
    dossier_patients ||--o{ message_threads : optional_context
```

## Flux d'envoi

```mermaid
sequenceDiagram
    actor Staff
    participant UI as Livewire
    participant Service as ClinicalMessagingService
    participant DB as MySQL
    participant Storage
    participant Notifications

    Staff->>UI: Compose message
    UI->>Service: composeInternal()
    Service->>DB: create thread + message + recipients
    Service->>DB: create per-user statuses
    Service->>Storage: store attachments
    Service->>DB: capture mentions + audit
    Service->>Notifications: database notification always
    Notifications-->>Staff: badge / email optional
```

## Permissions

Accès à une conversation interne si:

- l'utilisateur appartient au même hôpital;
- le message est `internal`;
- l'utilisateur est expéditeur ou destinataire.

Les règles métier plus fines peuvent être ajoutées dans `ClinicalMessagePolicy::send()`:

- infirmier vers direction selon politique locale;
- chef de service vers son département;
- administrateur limité aux conversations autorisées.

## Notifications

`InternalClinicalMessageNotification` utilise toujours le canal `database`. L'email est optionnel via préférence utilisateur. Pour le temps réel, brancher l'événement après `notifyInternalRecipients()` avec Laravel Reverb/Echo afin de pousser:

- badge non lu;
- nouveau message;
- toast critique;
- mise à jour de compteur.

## UI

La page `/messagerie` expose:

- dossiers: réception, envoyés, brouillons, favoris, important, coordination, urgent, service, archives, corbeille;
- recherche globale sur sujet, corps, expéditeur, destinataires et patient;
- filtres catégorie, priorité, date;
- liste dense avec avatar, sujet, aperçu, date, pièce jointe, priorité, lu/non lu;
- fil complet type Gmail avec citations et pièces jointes;
- fenêtre de composition avec utilisateurs, groupes, services, patient optionnel et fichiers.

## Performance et montée en charge

- Index principaux: `hopital_id`, `message_type`, `status`, `last_activity_at`, `thread_id`, `user_id/read_at`.
- Pagination serveur sur la boîte.
- Etats par utilisateur dans `message_user_statuses`, évitant de dupliquer les messages.
- Pièces jointes stockées hors base via Laravel Storage.
- Notifications Laravel en queue.
- Pour plusieurs milliers d'utilisateurs: diffuser aux groupes/services via jobs chunkés, ajouter un index full text MySQL sur `subject/body/recipient_summary`, et archiver par politique de rétention.

## Conformité hospitalière

- Séparation explicite patient/interne.
- Patient seulement en contexte optionnel.
- Journal d'audit pour création, lecture, réponse, archivage, suppression et marquages.
- Téléchargements contrôlés par service/policy.
- Prévoir une politique de rétention par établissement et exports d'audit.
