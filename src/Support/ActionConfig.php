<?php

namespace Iamshehzada\ActionConfirmation\Support;

class ActionConfig
{
    public static function get(string $action): array
    {
        return (array) config("action-confirmation.actions.$action", []);
    }

    public static function ttl(string $action): int
    {
        $cfg = self::get($action);
        return (int) ($cfg['ttl'] ?? 300);
    }

    public static function channels(string $action): array
    {
        $cfg = self::get($action);
        return (array) ($cfg['channels'] ?? ['api', 'web']);
    }

    public static function targetClass(string $action): ?string
    {
        $cfg = self::get($action);
        return $cfg['target'] ?? null;
    }

    public static function reasonRequired(string $action): bool
    {
        $cfg = self::get($action);
        return (bool) ($cfg['reason_required'] ?? false);
    }
}
