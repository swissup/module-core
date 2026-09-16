<?php
namespace Swissup\Core\Console\Command\Installer;

use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class PackageRequireCommand extends PackageAbstractCommand
{
    protected function configure()
    {
        $this->setName('swissup:require')
            ->setDescription('Download swissup package(s) using composer and run setup:upgrade')
            ->addArgument(
                self::INPUT_ARGUMENT_PACKAGES,
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'Package name(s), optionally with version constraint: swissup/firecheckout:^2.0'
            )
            ->addOption(
                self::INPUT_OPTION_DRY_RUN,
                null,
                InputOption::VALUE_NONE,
                'Show what would be installed without changing any files'
            );
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $packages = $input->getArgument(self::INPUT_ARGUMENT_PACKAGES);
        $isDryRun = (bool) $input->getOption(self::INPUT_OPTION_DRY_RUN);

        try {
            $this->validate([
                'composer require ' . implode(' ', $packages),
                'bin/magento setup:upgrade',
            ], $isDryRun);

            if (!$this->ensureRepositoryEnabled($input, $output)) {
                return Cli::RETURN_FAILURE;
            }

            $this->validatePackages($packages);

            if ($isDryRun) {
                $args = array_merge($this->getRequireArgs($packages), ['--dry-run']);
                return $this->composer->run($args, $output, $input->isInteractive())
                    ? Cli::RETURN_FAILURE
                    : Cli::RETURN_SUCCESS;
            }

            return $this->runAndUpgrade($this->getRequireArgs($packages), $input, $output);
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Cli::RETURN_FAILURE;
        }
    }

    /**
     * @param array $packages
     * @return array
     */
    private function getRequireArgs(array $packages)
    {
        $args = array_merge(
            ['require'],
            $packages,
            [
                // swissup packages are often root requirements (installed one by one)
                '--update-with-all-dependencies',
                '--no-progress',
            ]
        );

        if (!$this->composer->isDevInstalled()) {
            $args[] = '--update-no-dev';
        }

        return $args;
    }

    /**
     * Offer to run swissup:repo:enable when repository or access key is missing
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return boolean
     * @throws \RuntimeException
     */
    private function ensureRepositoryEnabled(InputInterface $input, OutputInterface $output)
    {
        if ($this->repository->isEnabled()) {
            $credentials = $this->repository->getCredentials();
            if ($credentials['username'] && $credentials['password']) {
                return true;
            }
            $message = 'Access key is not found.';
        } else {
            $message = 'Swissuplabs repository is not enabled.';
        }

        if (!$input->isInteractive()) {
            throw new \RuntimeException($message . ' Run bin/magento swissup:repo:enable first.');
        }

        $question = new ConfirmationQuestion(
            sprintf('<comment>%s</comment> Run swissup:repo:enable now? [Y/n] ', $message),
            true
        );
        if (!$this->getHelper('question')->ask($input, $output, $question)) {
            return false;
        }

        return $this->getApplication()
            ->find('swissup:repo:enable')
            ->run(new ArrayInput([]), $output) === Cli::RETURN_SUCCESS;
    }

    /**
     * Check access key and packages availability before touching the store.
     * Much faster than "composer show --available".
     *
     * @param array $packages
     * @return void
     * @throws \RuntimeException
     */
    private function validatePackages(array $packages)
    {
        $credentials = $this->repository->getCredentials();
        if (!$credentials['username'] || !$credentials['password']) {
            throw new \RuntimeException(
                'Access key is not found. Run bin/magento swissup:repo:enable first.'
            );
        }

        $available = array_change_key_case(
            $this->repository->getPackages($credentials['username'], $credentials['password'])
        );

        foreach ($packages as $package) {
            $name = $this->getPackageName($package);
            if (!isset($available[$name])) {
                throw new \RuntimeException(sprintf(
                    'Package "%s" is not found.',
                    $name
                ));
            }
        }
    }
}
