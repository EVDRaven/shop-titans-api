<?php

namespace Tests\Unit;

use App\Console\DestructiveCommandGuard;
use Illuminate\Console\Events\CommandStarting;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Tests\TestCase;

class DestructiveCommandGuardTest extends TestCase
{
    private const COMMANDS = ['migrate:fresh', 'migrate:refresh', 'db:wipe'];

    protected function setUp(): void
    {
        parent::setUp();

        // Conexión ficticia que reproduce la base de datos de desarrollo protegida.
        config([
            'database.connections.protected_test' => [
                'driver' => 'pgsql',
                'database' => 'shoptitans',
            ],
            'database.connections.unprotected_pgsql' => [
                'driver' => 'pgsql',
                'database' => 'otra_base',
            ],
        ]);
    }

    public function test_blocks_destructive_commands_against_protected_database(): void
    {
        foreach (self::COMMANDS as $command) {
            try {
                $this->guard()->handle($this->event($command, ['--database' => 'protected_test']));
                $this->fail("El comando {$command} debió haberse bloqueado.");
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('bloqueado', $e->getMessage());
            }
        }
    }

    public function test_blocks_when_default_connection_is_the_protected_database(): void
    {
        config(['database.default' => 'protected_test']);

        $this->expectException(RuntimeException::class);

        $this->guard()->handle($this->event('migrate:fresh'));
    }

    public function test_does_not_block_non_destructive_commands(): void
    {
        config(['database.default' => 'protected_test']);

        $this->guard()->handle($this->event('migrate'));

        $this->assertTrue(true);
    }

    public function test_does_not_block_unprotected_pgsql_database(): void
    {
        $this->guard()->handle($this->event('db:wipe', ['--database' => 'unprotected_pgsql']));

        $this->assertTrue(true);
    }

    public function test_does_not_block_sqlite_connection(): void
    {
        // En el entorno de pruebas la conexión por defecto es SQLite :memory:.
        $this->assertSame('sqlite', config('database.default'));

        $this->guard()->handle($this->event('migrate:fresh'));

        $this->assertTrue(true);
    }

    public function test_targets_protected_database_detection(): void
    {
        $guard = $this->guard();

        $this->assertTrue($guard->targetsProtectedDatabase('protected_test'));
        $this->assertFalse($guard->targetsProtectedDatabase('unprotected_pgsql'));
        $this->assertFalse($guard->targetsProtectedDatabase('sqlite'));
        $this->assertFalse($guard->targetsProtectedDatabase('inexistente'));
        $this->assertFalse($guard->targetsProtectedDatabase(null));
        $this->assertFalse($guard->targetsProtectedDatabase(''));
    }

    private function guard(): DestructiveCommandGuard
    {
        return new DestructiveCommandGuard;
    }

    /**
     * @param  array<string, string>  $parameters
     */
    private function event(string $command, array $parameters = []): CommandStarting
    {
        return new CommandStarting($command, new ArrayInput($parameters), new NullOutput);
    }
}
