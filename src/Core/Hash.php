<?php

namespace Niang\Core;

class Hash
{
    public static function make(string $value): string
    {
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
        return password_hash($value, $algo);
    }

    public static function check(string $value, string $hash): bool
    {
        return password_verify($value, $hash);
    }
}
