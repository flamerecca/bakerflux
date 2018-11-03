<?php

namespace Flamerecca\Bakerflow\Commands;

use File;
use Flamerecca\Bakerflow\Exceptions\FileAlreadyExistsException;
use Flamerecca\Bakerflow\Generators\Generator;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class BakeMongoEntityCommand extends Command
{
    /**
     * The name of command.
     *
     * @var string
     */
    protected $name = 'bakerflow:bake:mongo-entity';

    /**
     * The description of command.
     *
     * @var string
     */
    protected $description = 'Bake a new mongo entity';

    /**
     * The array of command arguments.
     *
     * @return array
     */
    public function getArguments()
    {
        return [
            [
                'name',
                InputArgument::REQUIRED,
                'The name of model for which the controller is being generated.',
                null
            ],
        ];
    }

    /**
     * Execute the command.
     *
     * @return void
     */
    public function fire()
    {
        $this->handle();
    }

    /**
     * Execute the command.
     *
     * @see fire()
     * @return void
     */
    public function handle()
    {
        try {
            $this->bakeMongoEntity();
            $this->bakeMongoController();
            $this->bakeMongoService();
            $this->info('mongoDB entity created successfully.');
        } catch (FileAlreadyExistsException $e) {
            $this->error('file already exists!');
        }
    }

    private function bakeMongoEntity()
    {
        $stubPath = __DIR__ . '/../../ingredients/entities/mongo_model.stub';
        $servicePath = app()->path() . '/Entities/' . $this->getClass() . '.php';
        $replaces = collect([
            'class' => $this->getClass(),
            'namespace' => $this->getEntitiesNamespace(), 
            'fillable' => '[]',
        ]);
        $content = (new Generator($stubPath, $replaces))->render();
        File::put(
            $servicePath,
            $content
        );
    }

    /**
     * @return string
     * @author reccachao
     */
    private function getClass()
    {
        return Str::studly(class_basename($this->getArgumentName()));
    }
    /**
     * Get name input.
     *
     * @return string
     */
    public function getArgumentName()
    {
        $name = $this->argument('name');
        if (str_contains($this->argument('name'), '\\')) {
            $name = str_replace('\\', '/', $this->argument('name'));
        }
        if (str_contains($this->argument('name'), '/')) {
            $name = str_replace('/', '/', $this->argument('name'));
        }

        return Str::studly(str_replace(' ', '/', ucwords(str_replace('/', ' ', $name))));
    }


    /**
     * @return string
     * @author reccachao
     */
    private function getEntitiesNamespace()
    {
        return 'App\\Entities';
    }

    /**
     *
     */
    private function bakeMongoController()
    {
        $stubPath = __DIR__ . '/../../ingredients/controllers/mongo_controller.stub';
        $servicePath = app()->path() . '/Http/Controllers/' . $this->getControllerName() . 'Controller.php';
        $replaces = collect([
            'appname' => $this->getAppNamespace(),
            'class' => $this->getClass(),
            'controller' => $this->getControllerName(),
            'namespace' => $this->getControllerNamespace(),
            'singular' => $this->getSingularName(),
            'plural' => $this->getPluralName(),
        ]);
        $content = (new Generator($stubPath, $replaces))->render();
        File::put(
            $servicePath,
            $content
        );
    }

    /**
     * Get application namespace
     *
     * @return string
     */
    private function getAppNamespace()
    {
        return \Illuminate\Container\Container::getInstance()->getNamespace();
    }

    /**
     * Gets controller name based on model
     *
     * @return string
     */
    public function getControllerName()
    {
        return ucfirst($this->getPluralName());
    }

    /**
     * Gets controller name based on model
     *
     * @return string
     */
    public function getControllerNamespace()
    {
        return 'App\\Http\\Controllers';
    }

    /**
     * Gets singular name based on model
     *
     * @return string
     */
    private function getSingularName()
    {
        return str_singular(lcfirst(ucwords($this->getClass())));
    }

    /**
     * Gets plural name based on model
     *
     * @return string
     */
    private function getPluralName()
    {
        return str_plural(lcfirst(ucwords($this->getClass())));
    }

    /**
     *
     */
    private function bakeMongoService()
    {
        $stubPath = __DIR__ . '/../../ingredients/services/mongo_service.stub';
        $filesystem = new Filesystem();
        if (!$filesystem->isDirectory(app()->path('Services/'))) {
            $filesystem->makeDirectory(app()->path('Services/'));
        }
        $servicePath = app()->path() . '/Services/' . $this->getControllerName() . 'Service.php';
        $replaces = collect([
            'appname' => $this->getAppNamespace(),
            'class' => $this->getClass(),
            'controller' => $this->getControllerName(),
            'namespace' => $this->getServiceNamespace(),
            'singular' => $this->getSingularName(),
            'plural' => $this->getPluralName(),
        ]);
        $content = (new Generator($stubPath, $replaces))->render();
        File::put(
            $servicePath,
            $content
        );
    }

    /**
     * @return string
     */
    private function getServiceNamespace()
    {
        return 'App\\Services';
    }
}
