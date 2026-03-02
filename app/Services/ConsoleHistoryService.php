<?php

namespace App\Services;

use Illuminate\Support\Facades\Session;

/**
 * Stores console command history in the user's session.
 * Persists across page refreshes, no extensions, files, or DB tables needed.
 * History is cleared when the session is destroyed (logout).
 */
class ConsoleHistoryService
{
    private const SESSION_KEY = 'sysadmin_console_history';

    public function getAll(): array
    {
        return Session::get(self::SESSION_KEY, []);
    }

    public function add(array $entry): void
    {
        $history = $this->getAll();
        $history[] = $entry;
        Session::put(self::SESSION_KEY, $history);
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }
}

