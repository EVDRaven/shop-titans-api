<?php

namespace App\Console;

use Illuminate\Console\Events\CommandStarting;
use Symfony\Component\Console\Exception\RuntimeException;

/**
 * Guarda que impide ejecutar comandos destructivos contra la base de datos
 * de desarrollo protegida (por defecto, `shoptitans` sobre PostgreSQL).
 *
 * Se registra como listener del evento `CommandStarting` (ver AppServiceProvider).
 * Solo interviene en comandos concretos y cuando la conexión efectiva apunta a
 * una base de datos protegida; en cualquier otro caso no altera el comportamiento.
 */
final class DestructiveCommandGuard
{
    /** Comandos destructivos que deben bloquearse. */
    public const BLOCKED_COMMANDS = ['migrate:fresh', 'migrate:refresh', 'db:wipe'];

    /** Nombres de base de datos protegidos (solo se bloquean si el driver es PostgreSQL). */
    public const PROTECTED_DATABASES = ['shoptitans'];

    /**
     * Punto de entrada del listener.
     *
     * @throws RuntimeException cuando el comando es destructivo y apunta a la base protegida.
     */
    public function handle(CommandStarting $event): void
    {
        if (! in_array($event->command, self::BLOCKED_COMMANDS, true)) {
            return;
        }

        $connection = $this->requestedConnection($event);

        if (! $this->targetsProtectedDatabase($connection)) {
            return;
        }

        throw new RuntimeException(
            sprintf(
                'Comando destructivo bloqueado: "%s" apunta a la base de datos de desarrollo protegida "%s" (conexión "%s"). '
                .'Esta guarda evita la destrucción accidental de datos. Si de verdad necesitas ejecutarlo, usa una conexión distinta.',
                $event->command,
                implode('", "', self::PROTECTED_DATABASES),
                (string) $connection,
            )
        );
    }

    /**
     * Determina si la conexión indicada apunta a una base de datos protegida.
     */
    public function targetsProtectedDatabase(?string $connection): bool
    {
        if ($connection === null || $connection === '') {
            return false;
        }

        $config = config("database.connections.{$connection}");

        if (! is_array($config)) {
            return false;
        }

        return ($config['driver'] ?? null) === 'pgsql'
            && in_array($config['database'] ?? null, self::PROTECTED_DATABASES, true);
    }

    /**
     * Resuelve la conexión efectiva: la opción `--database` si se indicó,
     * o la conexión por defecto en caso contrario.
     */
    private function requestedConnection(CommandStarting $event): ?string
    {
        $requested = $event->input->getParameterOption('--database', null);

        if (is_string($requested) && $requested !== '') {
            return $requested;
        }

        $default = config('database.default');

        return is_string($default) ? $default : null;
    }
}
