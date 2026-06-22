<?php

namespace App\Http\Controllers;

use App\Models\ClinicalMessage;
use App\Models\ClinicalMessageAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClinicalMessageController extends Controller
{
    public function showPatientMessage(Request $request, ClinicalMessage $message)
    {
        $message->load(['dossierPatient', 'sender.departement', 'attachments']);

        return view('messaging.patient-message', [
            'message' => $message,
        ]);
    }

    public function downloadAttachment(Request $request, ClinicalMessageAttachment $attachment): StreamedResponse
    {
        $attachment->load('message.dossierPatient');

        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->download(
            $attachment->path,
            $attachment->original_name,
        );
    }

    public function downloadStaffAttachment(Request $request, ClinicalMessageAttachment $attachment): StreamedResponse
    {
        abort_unless(
            app(\App\Services\ClinicalMessagingService::class)->canAccessAttachment($attachment, $request->user()),
            403,
        );

        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->download(
            $attachment->path,
            $attachment->original_name,
        );
    }
}
