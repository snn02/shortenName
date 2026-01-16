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

function isPatronymic(string $value): bool
{
    return (bool) preg_match('/(вич|вна|ович|евич|овна|евна|ична|ыныч|ич)$/iu', $value);
}

function isSurnameLikely(string $value): bool
{
    return (bool) preg_match('/(ов|ев|ёв|ин|ын|ский|ская|ко|ук|юк|енко|ич|ыч|цева|ова|ева|ина|ына)$/iu', $value);
}
