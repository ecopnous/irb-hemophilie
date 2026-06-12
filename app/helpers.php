<?php

if (!function_exists('current_hopital_id')) {
    function current_hopital_id(): ?int
    {
        return session('hopital_id');
    }
}

if (!function_exists('current_hopital_nom')) {
    function current_hopital_nom(): ?string
    {
        return session('hopital_nom');
    }
}

if (!function_exists('current_hopital')) {
    function current_hopital(): array
    {
        return session('hopital', []);
    }
}

if (!function_exists('user_grade')) {
    function user_grade(): ?string
    {
        return \App\Support\GradeNavigation::userGrade();
    }
}

if (!function_exists('nav_can')) {
    function nav_can(string $area): bool
    {
        return \App\Support\GradeNavigation::canAccessArea($area);
    }
}

if (!function_exists('nav_route_can')) {
    function nav_route_can(?string $routeName = null): bool
    {
        return \App\Support\GradeNavigation::canAccessRoute($routeName ?? request()->route()?->getName());
    }
}
