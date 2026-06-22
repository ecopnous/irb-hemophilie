<?php

namespace App\Services;

use App\Models\ClinicalMessageTemplate;
use App\Models\Consultation;
use App\Models\DossierPatient;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ClinicalMessageTemplateService
{
    /**
     * @return Collection<int, ClinicalMessageTemplate>
     */
    public function availableTemplates(?int $departementId = null): Collection
    {
        return ClinicalMessageTemplate::query()
            ->active()
            ->forContext(current_hopital_id(), $departementId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function find(int $templateId): ?ClinicalMessageTemplate
    {
        return ClinicalMessageTemplate::query()
            ->active()
            ->forContext(current_hopital_id())
            ->find($templateId);
    }

    /**
     * @param  array<string, string>  $extra
     * @return array{subject: string, body: string, category: string}
     */
    public function render(
        ClinicalMessageTemplate $template,
        DossierPatient $patient,
        ?Consultation $consultation = null,
        array $extra = [],
    ): array {
        $context = $this->buildContext($patient, $consultation, $extra);

        return [
            'subject' => $this->applyPlaceholders($template->subject, $context),
            'body' => $this->applyPlaceholders($template->body, $context),
            'category' => $template->category->value,
        ];
    }

    /**
     * @param  array<string, string>  $extra
     * @return array<string, string>
     */
    public function buildContext(DossierPatient $patient, ?Consultation $consultation = null, array $extra = []): array
    {
        $doctor = $consultation?->user;

        return array_merge([
            'patient_prenom' => ucfirst((string) $patient->prenom),
            'patient_nom' => ucfirst((string) $patient->nom),
            'patient_nin' => (string) $patient->nin,
            'medecin' => $doctor instanceof User ? ('Dr ' . $doctor->name) : 'votre medecin',
            'date_consultation' => $consultation?->created_at?->translatedFormat('d F Y') ?? now()->translatedFormat('d F Y'),
            'examens_labo' => '—',
            'examens_imagerie' => '—',
        ], $extra);
    }

    /**
     * @param  array<string, string>  $context
     */
    public function applyPlaceholders(string $text, array $context): string
    {
        $replacements = [];

        foreach ($context as $key => $value) {
            $replacements['{{' . $key . '}}'] = $value;
        }

        return strtr($text, $replacements);
    }
}
