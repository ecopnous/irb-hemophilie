<?php

namespace App\Support;

use Illuminate\Support\Str;

class GradeNavigation
{
  public static function normalizeGrade(?string $grade): ?string
  {
    if ($grade === null || trim($grade) === '') {
      return null;
    }

    $normalized = Str::lower(Str::ascii(trim($grade)));

    return match ($normalized) {
      'laboratin', 'laborantin' => 'laborantin',
      default => $normalized,
    };
  }

  public static function userGrade(): ?string
  {
    $user = auth()->user();

    return $user ? static::normalizeGrade($user->grade) : null;
  }

  public static function hasFullAccess(?string $grade = null): bool
  {
    $grade ??= static::userGrade();

    return $grade !== null && in_array($grade, config('navigation.full_access_grades', []), true);
  }

  /**
   * @return array<int, string>
   */
  public static function allowedAreas(?string $grade = null): array
  {
    $grade ??= static::userGrade();

    if ($grade === null) {
      return [];
    }

    if (static::hasFullAccess($grade)) {
      return ['*'];
    }

    return config("navigation.grades.{$grade}", []);
  }

  public static function canAccessArea(string $area, ?string $grade = null): bool
  {
    $allowed = static::allowedAreas($grade);

    if (in_array('*', $allowed, true)) {
      return true;
    }

    return in_array($area, $allowed, true);
  }

  public static function resolveAreaForRoute(?string $routeName): ?string
  {
    if ($routeName === null || $routeName === '') {
      return null;
    }

    foreach (config('navigation.route_areas', []) as $area => $patterns) {
      foreach ($patterns as $pattern) {
        if (Str::is($pattern, $routeName)) {
          return $area;
        }
      }
    }

    return null;
  }

  public static function canAccessRoute(?string $routeName, ?string $grade = null): bool
  {
    $area = static::resolveAreaForRoute($routeName);

    if ($area === null) {
      return true;
    }

    return static::canAccessArea($area, $grade);
  }

  public static function shouldHideOnDashboard(string $area): bool
  {
    return in_array($area, config('navigation.dashboard_hide_when_denied', []), true);
  }
}
