<?php

namespace App\Support;

final class NameRules
{
    private const PATTERN = '/^[\p{L}\p{M}\s.\'\x{2019}-]+$/u';

    public static function required(int $max = 100): array
    {
        return ['required', 'string', 'max:' . $max, 'regex:' . self::PATTERN];
    }

    public static function optional(int $max = 100): array
    {
        return ['nullable', 'string', 'max:' . $max, 'regex:' . self::PATTERN];
    }
}
