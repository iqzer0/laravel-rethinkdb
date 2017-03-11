<?php

namespace dkuzmenchuk\Rethinkdb\Eloquent;

use Carbon\Carbon;
use DateTime;
use dkuzmenchuk\Rethinkdb\Eloquent\Relations\BelongsTo;
use dkuzmenchuk\Rethinkdb\Eloquent\Relations\BelongsToMany;
use dkuzmenchuk\Rethinkdb\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Model extends \Illuminate\Database\Eloquent\Model
{
    /**
     * Disable autoincrement and casting primary key as integer
     * @var bool
     */
    public $incrementing = false;

    /**
     * Get the format for database stored dates.
     *
     * @return string
     */
    protected function getDateFormat()
    {
        return $this->dateFormat ?: 'Y-m-d H:i:s';
    }

    /**
     * Get the table qualified key name.
     *
     * @return string
     */
    public function getQualifiedKeyName()
    {
        return $this->getKeyName();
    }

    /**
     * Ensure Timestamps are returned in DateTime.
     *
     * @param \DateTime $value
     *
     * @return \DateTime
     */
    protected function asDateTime($value)
    {
        // Legacy support for Laravel 5.0
        if (!$value instanceof Carbon) {
            return Carbon::instance($value);
        }

        return parent::asDateTime($value);
    }

    /**
     * Retain DateTime format for storage.
     *
     * @param \DateTime $value
     *
     * @return string
     */
    public function fromDateTime($value)
    {
        if ($value instanceof DateTime) {
            return $value;
        }

        return parent::asDateTime($value);
    }

    /**
     * Get a new query builder instance for the connection.
     *
     * @return Builder
     */
    protected function newBaseQueryBuilder()
    {
        $connection = $this->getConnection();
        // Check the connection type
        if ($connection instanceof \dkuzmenchuk\Rethinkdb\Connection) {
            return new QueryBuilder($connection);
        }

        return parent::newBaseQueryBuilder();
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param \dkuzmenchuk\Rethinkdb\Query\Builder $query
     *
     * @return \dkuzmenchuk\Rethinkdb\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Define an inverse one-to-one or many relationship.
     *
     * @param string $related
     * @param string $foreignKey
     * @param string $otherKey
     * @param string $relation
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function belongsTo($related, $foreignKey = null, $otherKey = null, $relation = null)
    {
        // If no relation name was given, we will use this debug backtrace to extract
        // the calling method's name and use that as the relationship name as most
        // of the time this will be what we desire to use for the relationships.
        if (is_null($relation)) {
            list(, $caller) = debug_backtrace(false, 2);
            $relation = $caller['function'];
        }
        // If no foreign key was supplied, we can use a backtrace to guess the proper
        // foreign key name by using the name of the relationship function, which
        // when combined with an "_id" should conventionally match the columns.
        if (is_null($foreignKey)) {
            $foreignKey = snake_case($relation).'_id';
        }
        $instance = new $related();
        // Once we have the foreign key names, we'll just create a new Eloquent query
        // for the related models and returns the relationship instance which will
        // actually be responsible for retrieving and hydrating every relations.
        $query = $instance->newQuery();
        $otherKey = $otherKey ?: $instance->getKeyName();

        return new BelongsTo($query, $this, $foreignKey, $otherKey, $relation);
    }

    /**
     * Define a one-to-one relationship.
     *
     * @param string $related
     * @param string $foreignKey
     * @param string $localKey
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function hasOne($related, $foreignKey = null, $localKey = null)
    {
        $foreignKey = $foreignKey ?: $this->getForeignKey();
        $instance = new $related();
        $localKey = $localKey ?: $this->getKeyName();

        return new HasOne($instance->newQuery(), $this, $foreignKey, $localKey);
    }

    /**
     * Define a one-to-many relationship.
     *
     * @param string $related
     * @param string $foreignKey
     * @param string $localKey
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function hasMany($related, $foreignKey = null, $localKey = null)
    {
        $foreignKey = $foreignKey ?: $this->getForeignKey();
        $instance = new $related();
        $localKey = $localKey ?: $this->getKeyName();

        return new HasMany($instance->newQuery(), $this, $foreignKey, $localKey);
    }

    /**
     * Define a polymorphic one-to-many relationship.
     *
     * @param  string  $related
     * @param  string  $name
     * @param  string  $type
     * @param  string  $id
     * @param  string  $localKey
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function morphMany($related, $name, $type = null, $id = null, $localKey = null)
    {
        $instance = $this->newRelatedInstance($related);

        // Here we will gather up the morph type and ID for the relationship so that we
        // can properly query the intermediate table of a relation. Finally, we will
        // get the table and create the relationship instances for the developers.
        list($type, $id) = $this->getMorphs($name, $type, $id);

        $localKey = $localKey ?: $this->getKeyName();

        return new MorphMany($instance->newQuery(), $this, $type, $id, $localKey);
    }

    /**
     * Determine if the new and old values for a given key are numerically equivalent.
     *
     * @param string $key
     *
     * @return bool
     */
    protected function originalIsNumericallyEquivalent($key)
    {
        $current = $this->attributes[$key];
        $original = $this->original[$key];
        // Date comparison.
        if (in_array($key, $this->getDates())) {
            $current = $current instanceof DateTime ? $this->asDateTime($current) : $current;
            $original = $original instanceof DateTime ? $this->asDateTime($original) : $original;

            return $current == $original;
        }

        return parent::originalIsNumericallyEquivalent($key);
    }

    /**
     * Perform a model insert operation.
     * @param EloquentBuilder $query
     * @return bool
     */
    protected function performInsert(EloquentBuilder $query)
    {
        if ($this->fireModelEvent('creating') === false) {
            return false;
        }

        // First we'll need to create a fresh query instance and touch the creation and
        // update timestamps on this model, which are maintained by us for developer
        // convenience. After, we will just continue saving these model instances.
        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        // If the model has an incrementing key, we can use the "insertGetId" method on
        // the query builder, which will give us back the final inserted ID for this
        // table from the database. Not all tables have to be incrementing though.
        $attributes = $this->attributes;

        if ($this->getIncrementing()) {
            $this->insertAndSetId($query, $attributes);
        }

        // If the table isn't incrementing we'll simply insert these attributes as they
        // are. These attribute arrays must contain an "id" column previously placed
        // there by the developer as the manually determined key for these models.
        else {
            if (empty($attributes)) {
                return true;
            }

            $this->insertAndSetId($query, $attributes);
        }

        // We will go ahead and set the exists property to true, so that it is set when
        // the created event is fired, just in case the developer tries to update it
        // during the event. This will allow them to do so and run an update here.
        $this->exists = true;

        $this->wasRecentlyCreated = true;

        $this->fireModelEvent('created', false);

        return true;
    }

    /**
     * Define a many-to-many relationship.
     *
     * @param string $related
     * @param null $table
     * @param null $foreignKey
     * @param null $relatedKey
     * @param null $relation
     * @return BelongsToMany
     */
    public function belongsToMany($related, $table = null, $foreignKey = null, $relatedKey = null, $relation = null)
    {
        // If no relationship name was passed, we will pull backtraces to get the
        // name of the calling function. We will use that function name as the
        // title of this relation since that is a great convention to apply.
        if (is_null($relation)) {
            list(, $caller) = debug_backtrace(false, 2);
            $relation = $caller['function'];
        }

        // First, we'll need to determine the foreign key and "other key" for the
        // relationship. Once we have determined the keys we'll make the query
        // instances as well as the relationship instances we need for this.
        $instance = new $related();

        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $relatedKey = $relatedKey ?: $instance->getForeignKey();

        // If no table name was provided, we can guess it by concatenating the two
        // models using underscores in alphabetical order. The two model names
        // are transformed to snake case from their default CamelCase also.
        if (is_null($table)) {
            $table = $this->joiningTable($related);
        }

        return new BelongsToMany($instance->newQuery(), $this, $table, $foreignKey, $relatedKey, $relation);
    }
}
