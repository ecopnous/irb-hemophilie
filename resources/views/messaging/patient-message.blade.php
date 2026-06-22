<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $message->subject }} — IRB</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-slate-100 antialiased">
    <div class="mx-auto max-w-2xl px-4 py-10">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-50 px-6 py-5">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Messagerie clinique</p>
                <h1 class="mt-2 text-xl font-bold text-slate-900">{{ $message->subject }}</h1>
                <p class="mt-2 text-sm text-slate-500">
                    {{ $message->sent_at?->translatedFormat('l d F Y à H:i') }}
                    — {{ $message->senderDisplayName() }}
                </p>
            </div>

            <div class="space-y-4 px-6 py-6 text-sm leading-7 text-slate-700 whitespace-pre-wrap">
                {{ $message->body }}
            </div>

            @if ($message->attachments->isNotEmpty())
                <div class="border-t border-slate-200 px-6 py-5">
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Pieces jointes</p>
                    <ul class="mt-3 space-y-2">
                        @foreach ($message->attachments as $attachment)
                            <li>
                                <a href="{{ URL::temporarySignedRoute('messaging.patient.attachment', now()->addDays(30), ['attachment' => $attachment->id]) }}"
                                    class="text-sky-600 hover:underline">
                                    {{ $attachment->original_name }} ({{ $attachment->humanSize() }})
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="border-t border-slate-200 bg-slate-50 px-6 py-4 text-xs text-slate-500">
                Message destine a {{ $message->dossierPatient?->prenom }} {{ $message->dossierPatient?->nom }}.
                Document confidentiel — usage medical uniquement.
            </div>
        </div>
    </div>
</body>
</html>
