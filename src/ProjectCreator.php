<?php

namespace Mlangeni\Machinjiri\Core\Artisans\Helpers;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class ProjectCreator extends Command
{
    protected static $defaultName = 'new';

    protected function configure()
    {
        $this
            ->setDescription('Create a new Machinjiri application')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the project');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectName = $input->getArgument('name');
        $filesystem = new Filesystem();

        // Check if directory already exists
        if (is_dir($projectName)) {
            $output->writeln("<error>The directory '$projectName' already exists!</error>");
            return Command::FAILURE;
        }

        $output->writeln("<info>Creating Machinjiri project: $projectName</info>");

        // Create project directory
        $filesystem->mkdir($projectName);
        chdir($projectName);

        // Initialize composer project
        $process = new Process(['composer', 'init', '--name', $projectName, '--type', 'project', '--stability', 'dev', '--no-interaction']);
        $process->run();

        // Require the framework
        $process = new Process(['composer', 'require', 'mlangenigroup/machinjiri']);
        $process->run();

        // Copy framework files
        $this->setupFrameworkStructure($filesystem, $output);

        $output->writeln("<info>Project $projectName created successfully!</info>");
        $output->writeln("<comment>Run 'cd $projectName && php artisan serve' to start development server</comment>");

        return Command::SUCCESS;
    }

    private function setupFrameworkStructure(Filesystem $filesystem, OutputInterface $output)
    {
        $frameworkPath = 'vendor/mlangenigroup/machinjiri';
        
        // Copy environment file
        if ($filesystem->exists("$frameworkPath/.env.example")) {
            $filesystem->copy("$frameworkPath/.env.example", '.env');
        }

        // Create directories
        $directories = ['app/Controllers', 'app/Models', 'config', 'storage', 'public'];
        foreach ($directories as $dir) {
            $filesystem->mkdir($dir, 0755);
        }

        // Generate key
        $key = bin2hex(random_bytes(32));
        $envContent = file_get_contents('.env');
        $envContent = str_replace('APP_KEY=', "APP_KEY=$key", $envContent);
        file_put_contents('.env', $envContent);

        $output->writeln("<info>Framework structure created successfully!</info>");
    }
}