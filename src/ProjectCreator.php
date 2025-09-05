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
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the project')
            ->addOption('dev', null, null, 'Install the development version');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectName = $input->getArgument('name');
        $isDev = $input->getOption('dev');
        $filesystem = new Filesystem();

        // Check if directory already exists
        if (is_dir($projectName)) {
            $output->writeln("<error>The directory '$projectName' already exists!</error>");
            return Command::FAILURE;
        }

        $output->writeln("<info>Creating Machinjiri project: $projectName</info>");

        try {
            // Create project directory
            $filesystem->mkdir($projectName);
            chdir($projectName);

            // Initialize composer project
            $output->writeln("<info>Initializing Composer project...</info>");
            $this->runProcess(['composer', 'init', '--name', $projectName, '--type', 'project', '--stability', 'stable', '--no-interaction'], $output);

            // Require the framework
            $output->writeln("<info>Installing Machinjiri framework...</info>");
            $version = $isDev ? 'dev-main' : '^1.3';
            $this->runProcess(['composer', 'require', 'mlangenigroup/machinjiri:' . $version], $output);

            // Set up framework structure
            $this->setupFrameworkStructure($filesystem, $output);

            $output->writeln("<info>Project $projectName created successfully!</info>");
            $output->writeln("<comment>Next steps:</comment>");
            $output->writeln("<comment>  - cd $projectName</comment>");
            $output->writeln("<comment>  - Configure your .env file</comment>");
            $output->writeln("<comment>  - Run 'php artisan serve' to start development server</comment>");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $output->writeln("<error>Error: " . $e->getMessage() . "</error>");
            return Command::FAILURE;
        }
    }

    private function runProcess(array $command, OutputInterface $output): void
    {
        $process = new Process($command);
        $process->setTimeout(300);
        $process->run(function ($type, $buffer) use ($output) {
            $output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            throw new \RuntimeException("Process failed: " . $process->getErrorOutput());
        }
    }

    private function setupFrameworkStructure(Filesystem $filesystem, OutputInterface $output): void
    {
        $output->writeln("<info>Setting up project structure...</info>");
        
        $frameworkPath = 'vendor/mlangenigroup/machinjiri';
        
        // Copy environment file
        if ($filesystem->exists("$frameworkPath/.env.example")) {
            $filesystem->copy("$frameworkPath/.env.example", '.env');
            $output->writeln("<info>Created .env file</info>");
        }

        // Create directory structure
        $directories = [
            'app/Controllers',
            'app/Models',
            'app/Views',
            'config',
            'storage/cache',
            'storage/logs',
            'storage/sessions',
            'public/assets',
            'resources/views',
            'resources/assets',
            'routes'
        ];
        
        foreach ($directories as $dir) {
            if (!$filesystem->exists($dir)) {
                $filesystem->mkdir($dir, 0755);
            }
        }
        
        // Copy default configuration if available
        if ($filesystem->exists("$frameworkPath/config.example")) {
            $filesystem->mirror("$frameworkPath/config.example", 'config');
            $output->writeln("<info>Created config directory</info>");
        }
        
        // Create default routes file
        if (!$filesystem->exists('routes/web.php')) {
            $filesystem->dumpFile('routes/web.php', "<?php\n\n// Define your routes here\n");
            $output->writeln("<info>Created routes file</info>");
        }
        
        // Generate application key
        $this->generateAppKey($filesystem, $output);
    }

    private function generateAppKey(Filesystem $filesystem, OutputInterface $output): void
    {
        if ($filesystem->exists('.env')) {
            $envContent = file_get_contents('.env');
            if (strpos($envContent, 'APP_KEY=') === false) {
                $key = base64_encode(random_bytes(32));
                $filesystem->appendToFile('.env', "\nAPP_KEY=$key\n");
                $output->writeln("<info>Generated application key</info>");
            }
        }
    }
}