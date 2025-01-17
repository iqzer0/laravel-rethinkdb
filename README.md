laravel-rethinkdb
=================


[![Total Downloads](https://img.shields.io/packagist/dt/iqzer0/laravel-rethinkdb.svg?style=flat)](https://packagist.org/packages/iqzer0/laravel-rethinkdb)
[![MIT License](https://img.shields.io/packagist/l/iqzer0/laravel-rethinkdb.svg?style=flat)](https://packagist.org/packages/iqzer0/laravel-rethinkdb)
[![Build Status](https://img.shields.io/travis/iqzer0/laravel-rethinkdb/master.svg?style=flat)](https://travis-ci.org/iqzer0/laravel-rethinkdb)
[![Coverage Status](https://img.shields.io/codeclimate/coverage/github/iqzer0/laravel-rethinkdb.svg?style=flat)](https://codeclimate.com/github/iqzer0/laravel-rethinkdb)
[![Scrutinizer Quality Score](https://img.shields.io/scrutinizer/g/iqzer0/laravel-rethinkdb/master.svg?style=flat)](https://scrutinizer-ci.com/g/iqzer0/laravel-rethinkdb/)

RethinkDB adapter for Laravel (with Eloquent support)

God bless [@jenssegers](https://github.com/jenssegers) for his great [laravel-mongodb](https://github.com/jenssegers/laravel-mongodb) project. I have used his tests and some other code, since it's awesome codebase for supporting other NoSQL databases. I hope he won't be angry on me for that ;)

This project is aiming at Laravel 5 which is soon to be released, so it might not work with L4.

# Installation

## Requirements.

1. Rethinkdb : You need to make sure that you have installed [rethinkdb](http://www.rethinkdb.com) successfully, you can reffer to rethinkdb [documentation](https://rethinkdb.com/docs/) for the full instruction of how to install rethinkdb.

1. Laravel 5.2 : this package was designed to work with [laravel](http://laravel.com) 5.2, so it will not work with laravel 4.x.

## Installation

To fully install this package you will have either to add it manually to your `composer.json` file, or you can execute the following command :

`composer require "iqzer0/laravel-rethinkdb:dev-master"`

This will install the package and all the required package for it to work.

## Service Provider

After you install the library you will need to add the `Service Provider` file to your `app.php` file like :

`iqzer0\Rethinkdb\RethinkdbServiceProvider::class,`

inside your `providers` array.

## Database configuration

Now that you have the service provider setup, you will need to add the following configuration array at the end of your database connections array like :

        'rethinkdb' => [
            'name'      => 'rethinkdb',
            'driver'    => 'rethinkdb',
            'host'      => env('DB_HOST', 'localhost'),
            'port'      => env('DB_PORT', 28015),
            'database'  => env('DB_DATABASE', 'homestead'),            
        ]

After you add it, you can just configure your enviroment file to be something like :

	DB_HOST=localhost
	DB_DATABASE=homestead
	DB_CONNECTION=rethinkdb

but you can always updatr your `DB_HOST` to point to the IP where you have installed rethinkdb.

# Migration

## Create a Migration File

You can easily create a migration file using the following command which will create a migration file for you to create the users table and use the package schema instead of Laravel schema:

`php artisan make:rethink-migration Users --create`

Please note that you can use the same options that you use in `make:migration` with `make:rethink-migration`, as its based on laravel `make:migration`

Be aware that Laravel Schema API is not fully implemented.  For example, ID columns using increments will not be auto-incremented unsigned integers, and will instead be a UUID unless explicitly set.  The easiest solution is to maintain UUID use within RethinkDB, turn off incremental IDs in Laravel, and finally implement UUID use in Laravel.


## Running The Migrations

Nothing will change here, you will keep using the same laravel commands which you are used to execute to run the migration.

## Example of Laravel Users Migration file

This is an example of how the laravel Users Migration file has become

	<?php

	use iqzer0\Rethinkdb\Schema\Blueprint;
	use Illuminate\Database\Migrations\Migration;

	class CreateUsersTable extends Migration
	{
	    /**
	     * Run the migrations.
	     *
	     * @return void
	     */
	    public function up()
	    {
	        Schema::create('users', function (Blueprint $table) {
	            $table->increments('id');
	            $table->string('name');
	            $table->string('email')->unique();
	            $table->string('password', 60);
	            $table->rememberToken();
	            $table->timestamps();
	        });
	    }

	    /**
	     * Reverse the migrations.
	     *
	     * @return void
	     */
	    public function down()
	    {
	        Schema::drop('users');
	    }
	}


# Model

## Create a Model Class

You can easily create a model class using the following command which will create it for you and use the package model instead of Laravel model:

`php artisan make:rethink-model News`

Please note that you can use the same options that you use in `make:model` with `make:rethink-model`, as its based on laravel `make:model`

## Example of Laravel News Model Class

This is an example of how the laravel model class has become

	<?php

	namespace App;

	use \iqzer0\Rethinkdb\Eloquent\Model;

	class News extends Model
	{
	    //
	}

## Update a Model Class

Be aware that any model that Laravel generates during its initial installation will need to be updated manually in order for them to work properly.  For example, the User model extends `Illuminate\Foundation\Auth\User`, which further extends `Illuminate\Database\Eloquent\Model` instead of `\iqzer0\Rethinkdb\Eloquent\Model;`. The import `Illuminate\Foundation\Auth\User` needs to be removed from the User model and replaced with `\iqzer0\Rethinkdb\Eloquent\Model;`, and any interfaces and associated traits implemented in `Illuminate\Foundation\Auth\User` that are required will need to be ported to the User model.

## Example of Laravel User Model Class

This is an example of how the laravel User model class has become

```
use Illuminate\Auth\Authenticatable;
use \iqzer0\Rethinkdb\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class User extends Model implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword;
    
    //
}
```
