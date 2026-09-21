<?php
declare(strict_types=1);

function normalize_indian_phone(string $phoneNumber): ?string
{
    $phoneNumber = preg_replace('/[\s()-]+/', '', trim($phoneNumber)) ?? '';
    if (preg_match('/^[6-9]\d{9}$/', $phoneNumber)) {
        return '+91' . $phoneNumber;
    }

    if (preg_match('/^\+91[6-9]\d{9}$/', $phoneNumber)) {
        return $phoneNumber;
    }

    return null;
}
