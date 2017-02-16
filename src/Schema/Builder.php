<?php

namespace dkuzmenchuk\RethinkDB\Schema;

use Closure;
use \Illuminate\Database\Connection;
use r;

use Illuminate\Database\Schema\Grammars\Grammar as BaseGrammar;

class Blueprint extends \Illuminate\Database\Schema\Blueprint
{
    /**
     * Create a new schema blueprint.
     *
     * @param Connection $connection
     * @param string     $table
     */
    public function __construct(Connection $connection, $table)
    {
        $this->connection = $connection;
        $this->table = $table;
    }

    /**
     * Execute the blueprint against the database.
     *
     * @param \Illuminate\Database\Connection              $connection
     * @param \Illuminate\Database\Schema\Grammars\Grammar $grammar
     *
     * @return void
     */
    public function build(Connection $connection, BaseGrammar $grammar)
    {
    }

    /**
     * Indicate that the table needs to be created.
     *
     * @return bool
     */
    public function create()
    {
        $conn = $this->connection->getConnection();
        $db = r\db($this->connection->getDatabaseName());
        $db->tableCreate($this->table)->run($conn);
    }

    /**
     * Indicate that the collection should be dropped.
     *
     * @return bool
     */
    public function drop()
    {
        $conn = $this->connection->getConnection();
        $db = r\db($this->connection->getDatabaseName());
        $db->tableDrop($this->table)->run($conn);
    }

    /**
     * Specify an index for the collection.
     *
     * @param string $column
     * @param mixed $options
     *
     * @param null $algorithm
     * @return Blueprint
     */
    public function index($column, $options = null, $algorithm = null)
    {
        $conn = $this->connection->getConnection();
        $db = r\db($this->connection->getDatabaseName());
        $db->table($this->table)->indexCreate($column)
            ->run($conn);

        return $this;
    }
}

class Builder extends \Illuminate\Database\Schema\Builder
{
    /**
     * Create a new database Schema manager.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Determine if the given table exists.
     *
     * @param string $table
     *
     * @return bool
     */
    public function hasTable($table)
    {
        $conn = $this->connection->getConnection();
        $db = r\db($this->connection->getDatabaseName());
        $tables = $db->tableList()->run($conn);

        return in_array($table, $tables);
    }

    /**
     * Create a new table on the schema.
     *
     * @param string  $table
     * @param Closure $callback
     *
     * @return bool
     */
    public function create($table, Closure $callback = null)
    {
        $blueprint = $this->createBlueprint($table);
        $blueprint->create();
        if ($callback) {
            $callback($blueprint);
        }
    }

    /**
     * Drop a table from the schema.
     *
     * @param string $table
     *
     * @return bool
     */
    public function drop($table)
    {
        $blueprint = $this->createBlueprint($table);

        return $blueprint->drop();
    }

    /**
     * Modify a table on the schema.
     *
     * @param string  $table
     * @param Closure $callback
     *
     * @return bool
     */
    public function table($table, Closure $callback)
    {
        $blueprint = $this->createBlueprint($table);
        if ($callback) {
            $callback($blueprint);
        }
    }

    /**
     * Create a new command set with a Closure.
     *
     * @param string  $table
     * @param Closure $callback
     *
     * @return Blueprint
     */
    protected function createBlueprint($table, Closure $callback = null)
    {
        return new Blueprint($this->connection, $table);
    }
}
