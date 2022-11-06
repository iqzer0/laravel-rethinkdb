<?php

use \iqzer0\Rethinkdb\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';
    protected static $unguarded = true;

    public function user()
    {
        return $this->belongsTo('User');
    }
}
