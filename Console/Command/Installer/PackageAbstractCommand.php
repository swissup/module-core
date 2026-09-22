<?php
namespace Swissup\Core\Console\Command\Installer;

use Magento\Framework\App\Cache\Manager as CacheManager;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\MaintenanceMode;
use Magento\Framework\App\State;
use Magento\Framework\App\State\CleanupFiles;
use Magento\Framework\Console\Cli;
use Magento\Framework\Setup\Declaration\Schema\FileSystem\Csv;
use Swissup\Core\Installer\Composer;
use Swissup\Core\Installer\ComposerRepository;
use Swissup\Core\Installer\Process;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

abstract class PackageAbstractCommand extends Command
{
    const INPUT_ARGUMENT_PACKAGES = 'packages';
    const INPUT_OPTION_DRY_RUN = 'dry-run';

    /**
     * Dependencies are injected (not proxied) on purpose:
     * they must be loaded before composer changes the files.
     */
    public function __construct(
        protected Composer $composer,
        protected ComposerRepository $repository,
        protected Process $process,
        protected State $appState,
        protected MaintenanceMode $maintenanceMode,
        protected CleanupFiles $cleanupFiles,
        protected CacheManager $cacheManager,
        protected DirectoryList $directoryList
    ) {
        parent::__construct();
    }

    /**
     * Package name without version constraint: a/b=2.0, a/b:^2.0
     *
     * @param string $package
     * @return string
     */
    protected function getPackageName($package)
    {
        return strtolower(strtok($package, ':= '));
    }

    /**
     * @return array
     */
    protected function getInstallArgs()
    {
        $args = ['install', '--no-progress'];

        if (!$this->composer->isDevInstalled()) {
            $args[] = '--no-dev';
        }

        return $args;
    }

    /**
     * @param array $manualCommands Commands to run when the store cannot be changed
     * @param boolean $isDryRun
     * @return void
     * @throws \RuntimeException
     */
    protected function validate(array $manualCommands, $isDryRun)
    {
        if (!$this->process->canRun()) {
            throw new \RuntimeException(sprintf(
                "proc_open is disabled. Run the commands manually:\n  %s",
                implode("\n  ", $manualCommands)
            ));
        }

        if (!$isDryRun && $this->appState->getMode() === State::MODE_PRODUCTION) {
            $manualCommands[] = 'bin/magento setup:di:compile';
            $manualCommands[] = 'bin/magento setup:static-content:deploy';

            throw new \RuntimeException(sprintf(
                "Production mode detected. Use your deployment process instead:\n  %s",
                implode("\n  ", $manualCommands)
            ));
        }
    }

    /**
     * Offer to run swissup:channel:enable when repository or access key is missing
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return boolean
     * @throws \RuntimeException
     */
    protected function ensureRepositoryEnabled(InputInterface $input, OutputInterface $output)
    {
        if ($this->repository->isEnabled()) {
            $credentials = $this->repository->getCredentials();
            if ($credentials['username'] && $credentials['password']) {
                return true;
            }
            $message = 'Access key is not found.';
        } else {
            $message = 'SwissupLabs repository is not enabled.';
        }

        if (!$input->isInteractive()) {
            throw new \RuntimeException($message . ' Run bin/magento swissup:channel:enable first.');
        }

        $question = new ConfirmationQuestion(
            sprintf('<comment>%s</comment> Run swissup:channel:enable now? [Y/n] ', $message),
            true
        );
        if (!$this->getHelper('question')->ask($input, $output, $question)) {
            return false;
        }

        return $this->getApplication()
            ->find('swissup:channel:enable')
            ->run(new ArrayInput([]), $output) === Cli::RETURN_SUCCESS;
    }

    /**
     * 1. Resolve dependencies and update composer.lock (store is online)
     * 2. Install packages and run setup:upgrade (store is in maintenance mode)
     *
     * @param array $args Composer require/remove arguments
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function runAndUpgrade(array $args, InputInterface $input, OutputInterface $output)
    {
        $interactive = $input->isInteractive();
        $installArgs = $this->getInstallArgs(); // must be read before vendor is changed
        $backup = $this->composer->backupFiles();

        // composer reverts composer.json and composer.lock itself on failure
        if ($this->composer->run(array_merge($args, ['--no-install']), $output, $interactive)) {
            return Cli::RETURN_FAILURE;
        }

        $enableMaintenance = !$this->maintenanceMode->isOn();
        $keepMaintenance = false;

        if ($enableMaintenance) {
            $output->writeln('<info>Enabling maintenance mode</info>');
            $this->maintenanceMode->set(true);
        }

        try {
            if ($this->composer->run($installArgs, $output, $interactive)) {
                $keepMaintenance = !$this->rollback($backup, $installArgs, $output, $interactive);
                return Cli::RETURN_FAILURE;
            }

            // Stale generated code and config cache may prevent bin/magento from booting
            $output->writeln('<info>Cleaning generated code and cache</info>');
            $this->cleanupFiles->clearCodeGeneratedFiles();
            $this->cacheManager->clean($this->cacheManager->getAvailableTypes());

            $output->writeln('<info>Running setup:upgrade</info>');
            $dumps = $this->getSchemaDumps();
            // safe-mode to dump the DB data of the disabled modules
            $code = $this->process->run(
                [BP . '/bin/magento', 'setup:upgrade', '--safe-mode=1'],
                $output,
                $interactive
            );
            $this->notifyAboutSchemaDumps($dumps, $output);

            if ($code) {
                // new code with outdated database - keep the store closed
                $keepMaintenance = true;
                $output->writeln(
                    '<error>setup:upgrade failed. Maintenance mode is still enabled. ' .
                    'Fix the error, run bin/magento setup:upgrade and bin/magento maintenance:disable</error>'
                );
                return Cli::RETURN_FAILURE;
            }

            $output->writeln('<info>Done</info>');
            return Cli::RETURN_SUCCESS;
        } finally {
            if ($enableMaintenance && !$keepMaintenance) {
                $output->writeln('<info>Disabling maintenance mode</info>');
                $this->maintenanceMode->set(false);
            }
        }
    }

    /**
     * Csv dumps created by setup:upgrade --safe-mode
     *
     * @return array Path => modification signature
     */
    private function getSchemaDumps()
    {
        clearstatcache();
        $result = [];

        foreach (glob($this->getSchemaDumpsDir() . '/*.csv') ?: [] as $path) {
            $result[$path] = filemtime($path) . ':' . filesize($path);
        }

        return $result;
    }

    /**
     * Dumps are never cleaned up and contain raw table data
     *
     * @param array $before Result of getSchemaDumps()
     * @param OutputInterface $output
     * @return void
     */
    private function notifyAboutSchemaDumps(array $before, OutputInterface $output)
    {
        $created = array_diff_assoc($this->getSchemaDumps(), $before);
        if (!$created) {
            return;
        }

        $output->writeln(sprintf(
            '<comment>Magento removed some data during setup:upgrade. We used --safe-mode=1 to save it to %s:</comment>',
            $this->getSchemaDumpsDir()
        ));

        foreach (array_keys($created) as $path) {
            $output->writeln('   - ' . basename($path));
        }

        $output->writeln(
            '<comment>Restore it with bin/magento setup:upgrade --data-restore=1 ' .
            'or delete the files - they are kept forever otherwise.</comment>'
        );
    }

    /**
     * @return string
     */
    private function getSchemaDumpsDir()
    {
        return $this->directoryList->getPath(DirectoryList::VAR_DIR) . '/' . Csv::DUMP_FOLDER;
    }

    /**
     * Restore composer.json, composer.lock and vendor directory
     *
     * @param array $backup
     * @param array $installArgs
     * @param OutputInterface $output
     * @param boolean $interactive
     * @return boolean
     */
    protected function rollback(array $backup, array $installArgs, OutputInterface $output, $interactive)
    {
        $output->writeln('<error>Installation failed. Restoring composer.json, composer.lock and vendor</error>');

        try {
            $this->composer->restoreFiles($backup);
            $code = $this->composer->run($installArgs, $output, $interactive);
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            $code = 1;
        }

        if ($code) {
            $output->writeln(
                '<error>Rollback failed. Maintenance mode is still enabled. ' .
                'Fix the error, run composer install and bin/magento maintenance:disable</error>'
            );
            return false;
        }

        $output->writeln('<info>Rollback completed</info>');
        return true;
    }
}
