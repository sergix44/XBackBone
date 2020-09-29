<?php


namespace Tests;

use App\Database\Migrator;

trait BootApplication
{
    protected $app;

    public function createApplication(bool $rebuild = false)
    {
        if (!$rebuild && $this->app !== null) {
            return $this->app;
        }

        /** @var \Slim\App $app */
        $this->app = require BASE_DIR.'bootstrap/app.php';

        $migrator = new Migrator($this->app->getContainer()->get('database'), BASE_DIR.'resources/schemas');
        $migrator->migrate();

        return $this->app;
    }
}
