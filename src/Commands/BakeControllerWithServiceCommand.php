<?php

namespace Flamerecca\Bakerflow\Commands;

use File;
use Flamerecca\Bakerflow\Generators\Generator;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class BakeControllerWithServiceCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'bakerflow:bake:controller';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'bake Controller with Service';

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
     * The array of command options.
     *
     * @return array
     */
    public function getOptions()
    {
        return [
            [
                'fillable',
                null,
                InputOption::VALUE_OPTIONAL,
                'The fillable attributes.',
                null
            ],
            [
                'rules',
                null,
                InputOption::VALUE_OPTIONAL,
                'The rules of validation attributes.',
                null
            ],
            [
                'validator',
                null,
                InputOption::VALUE_OPTIONAL,
                'Adds validator reference to the repository.',
                null
            ],
            [
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force the creation if file already exists.',
                null
            ]
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
        $this->bakeController();
        $this->bakeService();

        //this will create entity automatically
        $this->call('make:repository', $this->arguments());

        //force create validator
        $this->call('make:validator', [
            'name' => $this->argument('name'),
            '--force' => $this->option('force'),
        ]);

        $this->call('make:request', [
            'name' => $this->argument('name') . 'CreateRequest'
        ]);
        $this->call('make:request', [
            'name' => $this->argument('name') . 'UpdateRequest'
        ]);
        $this->bakeBinding();
    }

    /**
     *
     */
    private function bakeController()
    {
        $stubPath = __DIR__ . '/../../ingredients/controllers/controller.stub';
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
     * Get class name.
     *
     * @return string
     */
    private function getClass()
    {
        return Str::studly(class_basename($this->getArgumentName()));
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
     *
     */
    private function bakeService()
    {
        $stubPath = __DIR__ . '/../../ingredients/services/service.stub';
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

    private function getServiceNamespace()
    {
        return 'App\\Services';
    }

    /**
     * Handle repository and eloquent binding
     */
    private function bakeBinding()
    {
        $provider = File::get($this->getPath());
        $content = $this->generateBindingContent();
        File::put(
            $this->getPath(),
            str_replace(
                '//:end-bindings:',
                $content,
                $provider
            )
        );
    }

    /**
     * Get destination path for generated file.
     *
     * @return string
     */
    private function getPath()
    {
        if (!is_file(app()->path() . '/Providers/RepositoryServiceProvider.php')) {
            $this->call('make:provider', [
                'name' => 'RepositoryServiceProvider',
            ]);

            // placeholder to mark the place in file where to prepend repository bindings
            $provider = File::get(app()->path() . '/Providers/RepositoryServiceProvider.php');
            File::put(
                app()->path() . '/Providers/RepositoryServiceProvider.php',
                vsprintf(
                    str_replace('//', '%s', $provider),
                    [
                        '//',
                        '//:end-bindings:'
                    ]
                )
            );
        }
        return app()->path() . '/Providers/RepositoryServiceProvider.php';
    }

    /**
     * @return string
     */
    private function generateBindingContent()
    {

        $repositoryInterface = $this->getRepository() . "::class";
        $repositoryEloquent = $this->getEloquentRepository() . "::class";

        $replaces = collect([
            'REPOSITORY' => $repositoryInterface,
            'ELOQUENT' => $repositoryEloquent,
            'PLACEHOLDER' => '//:end-bindings:'

        ]);
        return (new Generator(
            __DIR__ . '/../../Ingredients/bindings/bindings.stub',
            $replaces
        ))->render();
    }

    /**
     * Gets repository full class name
     *
     * @return string
     */
    private function getRepository()
    {
        $repository = '\\App\\Repositories\\' . $this->getClass();

        return str_replace([
                "\\",
                '/'
            ], '\\', $repository) . 'Repository';
    }

    /**
     * Gets eloquent repository full class name
     *
     * @return string
     */
    private function getEloquentRepository()
    {
        $repository = '\\App\\Repositories\\' . $this->getClass();

        return str_replace([
                "\\",
                '/'
            ], '\\', $repository) . 'RepositoryEloquent';
    }
}
