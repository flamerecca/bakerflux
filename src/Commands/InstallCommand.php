<?php

namespace Flamerecca\Bakerflux\Commands;

use Flamerecca\Bakerflux\BakerfluxServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Intervention\Image\ImageServiceProviderLaravel5;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;

class InstallCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'bakerflux:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Bakerflux package';

    /**
     * @return array
     */
    protected function getOptions()
    {
        return [];
    }

    /**
     * Get the composer command for the environment.
     *
     * @return string
     */
    protected function findComposer()
    {
        if (file_exists(getcwd().'/composer.phar')) {
            return '"'.PHP_BINARY.'" '.getcwd().'/composer.phar';
        }

        return 'composer';
    }

    public function fire(Filesystem $filesystem)
    {
        return $this->handle($filesystem);
    }

    /**
     * Execute the console command.
     *
     * @param \Illuminate\Filesystem\Filesystem $filesystem
     *
     * @return void
     * @throws FileNotFoundException
     */
    public function handle(Filesystem $filesystem)
    {

        $this->info('Publishing useful traits');
        $this->publishTraits($filesystem);

        $this->info('Dumping the autoload files and reloading all new files');
        $this->composerReload();

        $this->info('Adding the storage symlink to your public folder');
        $this->call('storage:link');

        $this->info('Bakerflux Successfully Installed!');
    }

    /**
     * Publishing useful traits
     * @param Filesystem $filesystem
     * @throws FileNotFoundException
     */
    private function publishTraits(Filesystem $filesystem)
    {
        if (!$filesystem->isDirectory(app()->path('Traits/'))) {
            $filesystem->makeDirectory(app()->path('Traits/'));
        }
        $filesystem->put(
            app()->path('Traits/') . 'HasJsonResponses.php',
            $filesystem->get(dirname(__DIR__) . '/../ingredients/traits/HasJsonResponses.stub')
        );
    }

    /**
     * Dumping the autoload files in composer and reloading all new files
     */
    private function composerReload()
    {
        $composer = $this->findComposer();

        $process = new Process($composer.' dump-autoload');
        $process->setTimeout(null); // Setting timeout to null to prevent installation from stopping at a certain point in time
        $process->setWorkingDirectory(base_path())->run();
    }
}
