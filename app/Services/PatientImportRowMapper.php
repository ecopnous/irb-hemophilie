<?php

namespace App\Services;

use App\Models\Configs\Assurance;
use Carbon\Carbon;
use Illuminate\Support\Str;

class PatientImportRowMapper
{
    public function map(array $row, int $hopitalId, int $userId): array
    {
        $genre = $this->normalizeGenre($row['genre'] ?? null);

        return array_filter([
            'prenom' => $this->string($row['prenom'] ?? null),
            'nom' => $this->nullableString($row['nom'] ?? null),
            'postnom' => $this->nullableString($row['postnom'] ?? null),
            'genre' => $genre,
            'etat_civil' => $this->normalizeEtatCivil($row['etat_civil'] ?? null),
            'date_naissance' => $this->parseDate($row['date_naissance'] ?? null),
            'telephone' => $this->nullableString($row['telephone'] ?? null),
            'email' => $this->nullableString($row['email'] ?? null),
            'ins' => $this->nullableString($row['ins'] ?? null),
            'quartier' => $this->nullableString($row['quartier'] ?? null),
            'avenue' => $this->nullableString($row['avenue'] ?? null),
            'num_habitation' => $this->nullableString($row['num_habitation'] ?? null),
            'note' => $this->nullableString($row['note'] ?? null),
            'assurance_id' => $this->resolveAssuranceId($row['assurance'] ?? null),
            'hopital_id' => $hopitalId,
            'user_id' => $userId,
        ], fn ($value) => $value !== null);
    }

    public function rules(): array
    {
        return [
            'prenom' => ['required', 'string', 'min:2', 'max:255'],
            'nom' => ['nullable', 'string', 'min:2', 'max:255'],
            'postnom' => ['nullable', 'string', 'min:2', 'max:255'],
            'genre' => ['required', 'in:M,F'],
            'etat_civil' => ['nullable', 'in:Célibataire,Marié,Divorcé,Veu(f)ve'],
            'date_naissance' => ['nullable', 'date', 'before:today'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'ins' => ['nullable', 'alpha_num', 'max:50', 'unique:dossier_patients,ins'],
            'quartier' => ['nullable', 'string', 'max:255'],
            'avenue' => ['nullable', 'string', 'max:255'],
            'num_habitation' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:500'],
            'assurance_id' => ['nullable', 'exists:assurances,id'],
        ];
    }

    private function normalizeGenre(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = Str::upper(Str::ascii(trim($value)));

        return match (true) {
            in_array($normalized, ['M', 'MASCULIN', 'HOMME', 'H', 'MALE'], true) => 'M',
            in_array($normalized, ['F', 'FEMININ', 'FEMME', 'FEMALE'], true) => 'F',
            default => Str::upper(trim($value)),
        };
    }

    private function normalizeEtatCivil(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return 'Célibataire';
        }

        $value = trim($value);

        return match (Str::lower(Str::ascii($value))) {
            'marie', 'marie(e)' => 'Marié',
            'divorce', 'divorce(e)' => 'Divorcé',
            'veuf', 'veuve', 'veu(f)ve' => 'Veu(f)ve',
            default => $value,
        };
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value)) {
            return Carbon::createFromTimestampUTC(((int) $value - 25569) * 86400)->format('Y-m-d');
        }

        $value = trim((string) $value);

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('Y-m-d');
            } catch (\Throwable) {
                continue;
            }
        }

        return Carbon::parse($value)->format('Y-m-d');
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

    private function string(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function nullableString(?string $value): ?string
    {
        return $this->string($value);
    }
}
