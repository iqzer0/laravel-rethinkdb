<?php

namespace iqzer0\Rethinkdb\Eloquent\Relations;

class BelongsToMany extends \Illuminate\Database\Eloquent\Relations\BelongsToMany
{
    /**
     * Set the join clause for the relation query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     */
    protected function performJoin($query = null)
    {
        $query = $query ?: $this->query;

        // We need to join to the intermediate table on the related model's primary
        // key column with the intermediate table's foreign key for the related
        // model instance. Then we can set the "where" for the parent models.

        $key = $this->related->getKeyName();

        $query->join($this->table, $key, '=', $this->getQualifiedRelatedKeyName());

        return $this;
    }

    /**
     * Set a where clause for a pivot table column.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  mixed   $value
     * @param  string  $boolean
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function wherePivot($column, $operator = null, $value = null, $boolean = 'and')
    {
        $this->pivotWheres[] = func_get_args();

        return $this->where($column, $operator, $value, $boolean);
    }

    /**
     * Set a "where in" clause for a pivot table column.
     *
     * @param  string  $column
     * @param  mixed   $values
     * @param  string  $boolean
     * @param  bool    $not
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function wherePivotIn($column, $values, $boolean = 'and', $not = false)
    {
        $this->pivotWhereIns[] = func_get_args();

        return $this->whereIn($column, $values, $boolean, $not);
    }
    /**
     * Get the select columns for the relation query.
     *
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    protected function shouldSelect(array $columns = ['*'])
    {
        return array_merge($columns, $this->aliasedPivotColumns());
    }

    /**
     * Get the pivot columns for the relation.
     *
     * "pivot_" is prefixed ot each column for easy removal later.
     *
     * @return array
     */
    protected function aliasedPivotColumns()
    {
        $defaults = [$this->foreignKey, $this->relatedKey];

        return collect(array_merge($defaults, $this->pivotColumns))->map(function ($column) {
            return $column.' as pivot_'.$column;
        })->unique()->all();
    }

    /**
     * Get the fully qualified foreign key for the relation.
     *
     * @return string
     */
    public function getQualifiedForeignKeyName()
    {
        return $this->foreignKey;
    }

    /**
     * Get the fully qualified "related key" for the relation.
     *
     * @return string
     */
    public function getQualifiedRelatedKeyName()
    {
        return $this->relatedKey;
    }
}
