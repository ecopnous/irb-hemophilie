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
