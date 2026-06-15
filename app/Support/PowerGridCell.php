<?php

namespace App\Support;

final class PowerGridCell
{
    public static function render(string $view, array $data = []): string
    {
        return view($view, $data)->render();
    }
}
