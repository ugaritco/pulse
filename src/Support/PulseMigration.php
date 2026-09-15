<?php

namespace Ugarit\Pulse\Support;

use Heritage\Database\Connection;
use Heritage\Database\Migrations\Migration;
use Heritage\Support\Facades\App;
use Heritage\Support\Facades\Config;
use Heritage\Support\Facades\DB;
use RuntimeException;

class PulseMigration extends Migration
{
    /**
     * Get the migration connection name.
     */
    public function getConnection(): ?string
    {
        return Config::get('pulse.storage.database.connection');
    }

    /**
     * Determine if the migration should run.
     */
    public function shouldRun(): bool
    {
        if (in_array($this->driver(), ['mariadb', 'mysql', 'pgsql', 'sqlite'])) {
            return true;
        }

        if (! App::environment('testing')) {
            throw new RuntimeException("Pulse does not support the [{$this->driver()}] database driver.");
        }

        if (Config::get('pulse.enabled')) {
            throw new RuntimeException("Pulse does not support the [{$this->driver()}] database driver. You can disable Pulse in your testsuite by adding `<env name=\"PULSE_ENABLED\" value=\"false\"/>` to your project's `phpunit.xml` file.");
        }

        return false;
    }

    /**
     * Get the database connection driver.
     */
    protected function driver(): string
    {
        return DB::connection($this->getConnection())->getDriverName();
    }
}
