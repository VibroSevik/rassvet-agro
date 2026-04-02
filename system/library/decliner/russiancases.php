<?php

namespace Decliner;

/**
 * Список падежей, используемых в приложении для склонения слов при выводе ошибок.
 */
final class RussianCases
{
    public const INSTRUMENTAL = 'instrumental';
    public const ACCUSATIVE  = 'accusative';

    public function has(string $case): bool
    {
        return in_array($case, [self::INSTRUMENTAL, self::ACCUSATIVE]);
    }
}