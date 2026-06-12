<?php

namespace App\Services;

use App\Models\Configs\Acte;
use App\Models\Configs\Assurance;
use App\Models\Configs\Departement;
use App\Models\Configs\Projet;
use App\Models\Configs\Service;
use App\Models\DossierPatient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ConsultationImportRowMapper
{
    private static ?Collection $departements = null;

    private static ?Collection $actes = null;

    private static ?Collection $services = null;
    public function map(array $row, int $hopitalId): array
    {
        $type = $this->normalizeType($row['type'] ?? null);
        $dossierPatientId = $this->resolvePatientId($row, $hopitalId);
        $departementId = $this->resolveDepartementId($row['departement'] ?? null);
        $acteIds = $this->resolveActeIds($row['actes'] ?? null, $departementId);

        if ($type === 'depistage' && !$departementId && $acteIds !== []) {
            $departementId = Acte::query()
                ->whereKey($acteIds[0])
                ->value('departement_id');
        }

        return array_filter([
            'dossier_patient_id' => $dossierPatientId,
            'type' => $type,
            'type_visite' => $this->normalizeTypeVisite($row['type_visite'] ?? $row['type_fichier'] ?? null),
            'departement_id' => $departementId,
            'service_id' => $this->resolveServiceId($row['service'] ?? null, $departementId),
            'assurance_id' => $this->resolveAssuranceId($row['assurance'] ?? null),
            'projet_id' => $this->resolveProjetId($row['projet'] ?? null),
            'user_id' => $this->resolveUserId($row['medecin'] ?? null),
            'hopital_id' => $hopitalId,
            'symptomes' => $this->nullableString($row['symptomes'] ?? null),
            'examen_clinique' => $this->nullableString($row['examen_clinique'] ?? null),
            'diagnostic_presomption' => $this->nullableString($row['diagnostic_presomption'] ?? null),
            'complement_anamnese' => $this->nullableString($row['complement_anamnese'] ?? null),
            'plan_traitement_conduite' => $this->nullableString($row['plan_traitement_conduite'] ?? null),
            'poids' => $this->nullableInt($row['poids'] ?? null),
            'temperature' => $this->nullableInt($row['temperature'] ?? null),
            'taille' => $this->nullableInt($row['taille'] ?? null),
            'systolite' => $this->nullableInt($row['systolite'] ?? null),
            'diastolique' => $this->nullableInt($row['diastolique'] ?? null),
            'frequence_cardiaque' => $this->nullableInt($row['frequence_cardiaque'] ?? null),
            'frequence_respiratoire' => $this->nullableInt($row['frequence_respiratoire'] ?? null),
            'saturation_oxygene' => $this->nullableInt($row['saturation_oxygene'] ?? null),
            'glycemie' => $this->nullableInt($row['glycemie'] ?? null),
            'prelevement_effectue' => $this->normalizeBoolean($row['prelevement_effectue'] ?? null),
            'acte_ids' => $acteIds,
            'team_user_ids' => $this->resolveTeamUserIds($row['equipe'] ?? null),
            'created_at' => $this->parseDateTime($row['date_consultation'] ?? null),
            'use_project_period' => $this->normalizeBoolean($row['periode_projet'] ?? null) ?? false,
        ], fn ($value) => $value !== null && $value !== []);
    }

    public function rules(): array
    {
        return [
            'dossier_patient_id' => ['required', 'integer', 'exists:dossier_patients,id'],
            'type' => ['required', 'in:consultation,depistage'],
            'type_visite' => ['required', 'in:standard,hémophilie,drépanocytose,hemophile,redac'],
            'departement_id' => ['required', 'integer', 'exists:departements,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'assurance_id' => ['nullable', 'integer', 'exists:assurances,id'],
            'projet_id' => ['nullable', 'integer', 'exists:projets,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'hopital_id' => ['required', 'integer', 'exists:hopitals,id'],
            'symptomes' => ['nullable', 'string'],
            'examen_clinique' => ['nullable', 'string'],
            'diagnostic_presomption' => ['nullable', 'string'],
            'complement_anamnese' => ['nullable', 'string'],
            'plan_traitement_conduite' => ['nullable', 'string'],
            'poids' => ['nullable', 'integer', 'min:1', 'max:300'],
            'temperature' => ['nullable', 'integer', 'min:30', 'max:45'],
            'taille' => ['nullable', 'integer', 'min:30', 'max:250'],
            'systolite' => ['nullable', 'integer', 'min:5', 'max:30'],
            'diastolique' => ['nullable', 'integer', 'min:3', 'max:20'],
            'frequence_cardiaque' => ['nullable', 'integer', 'min:20', 'max:250'],
            'frequence_respiratoire' => ['nullable', 'integer', 'min:5', 'max:80'],
            'saturation_oxygene' => ['nullable', 'integer', 'min:50', 'max:100'],
            'glycemie' => ['nullable', 'integer', 'min:20', 'max:600'],
            'prelevement_effectue' => ['nullable', 'boolean'],
            'acte_ids' => ['required', 'array', 'min:1'],
            'acte_ids.*' => ['integer', 'exists:actes,id'],
            'team_user_ids' => ['nullable', 'array'],
            'team_user_ids.*' => ['integer', 'exists:users,id'],
            'created_at' => ['nullable', 'date'],
            'use_project_period' => ['nullable', 'boolean'],
        ];
    }

    private function resolvePatientId(array $row, int $hopitalId): ?int
    {
        $nin = $this->nullableString($row['nin'] ?? null);
        $ins = $this->nullableString($row['ins'] ?? null);

        if (!$nin && !$ins) {
            return null;
        }

        $query = DossierPatient::query()->where('hopital_id', $hopitalId);

        if ($nin) {
            $query->where('nin', $nin);
        } else {
            $query->where('ins', $ins);
        }

        return $query->value('id');
    }

    private function resolveDepartementId(?string $value): ?int
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $needle = $this->normalizeKey($value);

        return $this->departements()->first(function (Departement $departement) use ($needle) {
            return $this->normalizeKey($departement->ref) === $needle
                || $this->normalizeKey($departement->name) === $needle;
        })?->id;
    }

    private function resolveActeIds(?string $value, ?int $departementId): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        $ids = [];

        foreach (preg_split('/[,;|]/', $value) as $part) {
            $part = trim($part);

            if ($part === '') {
                continue;
            }

            if (is_numeric($part)) {
                $ids[] = (int) $part;

                continue;
            }

            $needle = $this->normalizeKey($part);

            $acte = $this->actes()->first(function (Acte $acte) use ($needle, $departementId) {
                if ($this->normalizeKey($acte->name) !== $needle) {
                    return false;
                }

                return !$departementId || (int) $acte->departement_id === $departementId;
            }) ?? $this->actes()->first(fn (Acte $acte) => $this->normalizeKey($acte->name) === $needle);

            if ($acte) {
                $ids[] = (int) $acte->id;
            }
        }

        return array_values(array_unique($ids));
    }

    private function resolveServiceId(?string $value, ?int $departementId): ?int
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $needle = $this->normalizeKey($value);

        return $this->services()->first(function (Service $service) use ($needle, $departementId) {
            if ($this->normalizeKey($service->name) !== $needle) {
                return false;
            }

            return !$departementId || (int) $service->departement_id === $departementId;
        })?->id;
    }

    private function resolveAssuranceId(?string $name): ?int
    {
        if ($name === null || trim($name) === '') {
            return null;
        }

        return Assurance::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower(trim($name))])
            ->value('id');
    }

    private function resolveProjetId(?string $value): ?int
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        return Projet::query()
            ->where(function ($query) use ($value) {
                $query->whereRaw('LOWER(name) = ?', [Str::lower($value)])
                    ->orWhereRaw('LOWER(reference) = ?', [Str::lower($value)]);
            })
            ->value('id');
    }

    private function resolveUserId(?string $value): ?int
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return $this->findUser(trim($value));
    }

    private function resolveTeamUserIds(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        $ids = [];

        foreach (preg_split('/[,;|]/', $value) as $part) {
            $part = trim($part);

            if ($part === '') {
                continue;
            }

            $userId = $this->findUser($part);

            if ($userId) {
                $ids[] = $userId;
            }
        }

        return array_values(array_unique($ids));
    }

    private function findUser(string $value): ?int
    {
        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return User::query()->where('email', $value)->value('id');
        }

        return User::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower($value)])
            ->value('id');
    }

    private function normalizeType(?string $value): string
    {
        $value = Str::lower(Str::ascii(trim((string) ($value ?: 'consultation'))));

        return $value === 'depistage' ? 'depistage' : 'consultation';
    }

    private function normalizeTypeVisite(?string $value): string
    {
        $value = Str::lower(Str::ascii(trim((string) ($value ?: 'standard'))));

        return match ($value) {
            'hemophile', 'hemophilie' => 'hémophilie',
            'redac', 'drepanocytose', 'drépanocytose' => 'drépanocytose',
            default => 'standard',
        };
    }

    private function normalizeBoolean(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        $value = Str::lower(trim((string) $value));

        return in_array($value, ['1', 'true', 'oui', 'yes', 'o'], true);
    }

    private function parseDateTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_numeric($value)) {
            return Carbon::createFromTimestampUTC(((int) $value - 25569) * 86400)->format('Y-m-d H:i:s');
        }

        $value = trim((string) $value);

        foreach (['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d', 'd/m/Y H:i', 'd/m/Y', 'd-m-Y H:i', 'd-m-Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('Y-m-d H:i:s');
            } catch (\Throwable) {
                continue;
            }
        }

        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    private function nullableString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function normalizeKey(?string $value): string
    {
        return Str::lower(Str::ascii(trim((string) $value)));
    }

    private function departements(): Collection
    {
        return static::$departements ??= Departement::query()->get(['id', 'ref', 'name']);
    }

    private function actes(): Collection
    {
        return static::$actes ??= Acte::query()->get(['id', 'name', 'departement_id']);
    }

    private function services(): Collection
    {
        return static::$services ??= Service::query()->get(['id', 'name', 'departement_id']);
    }
}
