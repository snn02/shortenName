<?php

declare(strict_types=1);

/**
 * Formats a full name string into "Имя Ф.", if a surname is present.
 */
function formatNameWithSurnameInitial(string $fullName): string
{
    $normalized = trim(preg_replace('/\s+/u', ' ', $fullName) ?? '');
    if ($normalized == '') {
        return '';
    }

    $parts = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $parts = array_values($parts);
    $count = count($parts);

    if ($count === 1) {
        return $parts[0];
    }

    $name = '';
    $surname = '';

    if ($count === 2) {
        [$first, $second] = $parts;

        if (isPatronymic($second)) {
            return $first;
        }

        if (isSurnameLikely($first) && !isSurnameLikely($second)) {
            $name = $second;
            $surname = $first;
        } else {
            $name = $first;
            $surname = $second;
        }
    } else {
        $first = $parts[0];
        $middle = $parts[1];
        $last = $parts[2];

        if (isPatronymic($middle)) {
            $name = $first;
            $surname = $last;
        } elseif (isPatronymic($last)) {
            $name = $middle;
            $surname = $first;
        } else {
            $name = $first;
            $surname = $last;
        }
    }

    if ($surname === '') {
        return $name;
    }

    $initial = mb_substr($surname, 0, 1, 'UTF-8');

    return trim($name . ' ' . $initial . '.');
}

function anonymizePersonalData(string $text): string
{
    $text = preg_replace(
        '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/iu',
        'email@email.com',
        $text
    ) ?? $text;

    $text = preg_replace(
        '/\+?\d[\d\s().-]{8,}\d/u',
        '999-999-99-99',
        $text
    ) ?? $text;

    [$text, $protected] = protectNameSegments($text);

    $text = preg_replace_callback(
        '/\b[А-ЯЁ][а-яё]+(?:\s+[А-ЯЁ][а-яё]+){0,2}\b/u',
        static function (array $matches): string {
            $stopWords = [
                'Телефон',
                'Контакты',
                'Клиент',
                'Контактное',
                'Лицо',
                'ИП',
            ];

            if (strpos($matches[0], ' ') === false && in_array($matches[0], $stopWords, true)) {
                return $matches[0];
            }

            return formatNameWithSurnameInitial($matches[0]);
        },
        $text
    ) ?? $text;

    if (!empty($protected)) {
        $text = strtr($text, $protected);
    }

    return $text;
}

function protectNameSegments(string $text): array
{
    $protected = [];
    $index = 0;

    $text = preg_replace_callback(
        '/\([^)]*\)/u',
        static function (array $matches) use (&$protected, &$index): string {
            $token = "__PROTECTED_BLOCK_{$index}__";
            $protected[$token] = $matches[0];
            $index++;

            return $token;
        },
        $text
    ) ?? $text;

    $text = preg_replace_callback(
        '/\bИП\s+[А-ЯЁ][а-яё]+(?:\s+[А-ЯЁ][а-яё]+){0,2}\b/u',
        static function (array $matches) use (&$protected, &$index): string {
            $token = "__PROTECTED_BLOCK_{$index}__";
            $protected[$token] = $matches[0];
            $index++;

            return $token;
        },
        $text
    ) ?? $text;

    return [$text, $protected];
}

function isPatronymic(string $value): bool
{
    return (bool) preg_match(
        '/(вич|вна|ович|евич|овна|евна|ична|ыныч|ич|оглы|уулу|кызы|кизи|гизи)$/iu',
        $value
    );
}

function isSurnameLikely(string $value): bool
{
    return (bool) preg_match(
        '/(ов|ев|ёв|ин|ын|ский|ская|ко|ук|юк|енко|ич|ыч|цева|ова|ева|ина|ына|ян|янц|дзе|швили|ия|ули|оглы|уулу|или)$/iu',
        $value
    );
}
