<?php

namespace iqzer0\Rethinkdb\Migrations;

use Illuminate\Database\Migrations\MigrationCreator as LaravelMigrationCreator;

class MigrationCreator extends LaravelMigrationCreator
{
    /**
     * Get the path to the stubs.
     *
     * @return string
     */
    public function getStubPath()
    {
        return __DIR__.'/stubs';
    }
}
