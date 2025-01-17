<?php

namespace iqzer0\Rethinkdb\Console\Model;

use Illuminate\Console\GeneratorCommand as LaravelMakeModelCommand;
use Symfony\Component\Console\Input\InputOption;

class ModelMakeCommand extends LaravelMakeModelCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:rethink-model';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Rethinkdb model class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Model';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        if (parent::fire() !== false) {
            if ($this->option('migration')) {
                $table = Str::plural(Str::snake(class_basename($this->argument('name'))));

                $this->call('make:rethink-migration', ['name' => "create_{$table}_table", '--create' => $table]);
            }
        }
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/model.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param string $rootNamespace
     *
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['migration', 'm', InputOption::VALUE_NONE, 'Create a new migration file for the model.'],
        ];
    }
}
