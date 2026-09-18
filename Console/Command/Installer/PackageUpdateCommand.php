<?php
namespace Swissup\Core\Console\Command\Installer;

use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PackageUpdateCommand extends PackageAbstractCommand
{
    const INPUT_OPTION_WITH_DEPENDENCIES = 'with-dependencies';

    protected function configure()
    {
        $this->setName('swissup:package:update')
            ->setDescription('Update SwissupLabs packages using composer and run setup:upgrade')
            ->addArgument(
                self::INPUT_ARGUMENT_PACKAGES,
                InputArgument::IS_ARRAY,
                'Package name(s): swissup/firecheckout. All swissup packages are updated when omitted'
            )
            ->addOption(
                self::INPUT_OPTION_DRY_RUN,
                null,
                InputOption::VALUE_NONE,
                'Show what would be updated without changing any files'
            )
            ->addOption(
                self::INPUT_OPTION_WITH_DEPENDENCIES,
                'w',
                InputOption::VALUE_NONE,
                'Update 3rd party dependencies as well. Use it when composer cannot resolve the update'
            );
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $isDryRun = (bool) $input->getOption(self::INPUT_OPTION_DRY_RUN);

        try {
            $packages = $this->validatePackages(
                $input->getArgument(self::INPUT_ARGUMENT_PACKAGES)
            );

            if (!$packages) {
                $output->writeln('<comment>There are no swissup packages to update</comment>');
                return Cli::RETURN_SUCCESS;
            }

            $args = $this->getUpdateArgs(
                $packages,
                (bool) $input->getOption(self::INPUT_OPTION_WITH_DEPENDENCIES)
            );

            $this->validate([
                'composer ' . implode(' ', $args),
                'bin/magento setup:upgrade',
            ], $isDryRun);

            if (!$this->ensureRepositoryEnabled($input, $output)) {
                return Cli::RETURN_FAILURE;
            }

            if ($isDryRun) {
                return $this->composer->run(array_merge($args, ['--dry-run']), $output, $input->isInteractive())
                    ? Cli::RETURN_FAILURE
                    : Cli::RETURN_SUCCESS;
            }

            return $this->runAndUpgrade($args, $input, $output);
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Cli::RETURN_FAILURE;
        }
    }

    /**
     * Packages are listed explicitly, so composer is allowed to update them
     * even though they are root requirements. Everything else is kept locked:
     * --with-dependencies would also update the whole dependency tree of the
     * swissup packages (symfony, guzzle, etc).
     *
     * @param array $packages
     * @param boolean $withDependencies
     * @return array
     */
    private function getUpdateArgs(array $packages, $withDependencies)
    {
        $args = array_merge(['update'], $packages, ['--no-progress']);

        if ($withDependencies) {
            $args[] = '--with-dependencies';
        }

        if (!$this->composer->isDevInstalled()) {
            $args[] = '--no-dev';
        }

        return $args;
    }

    /**
     * Only swissup packages listed in composer.json can be updated.
     * Version constraint is dropped to keep composer.json untouched.
     *
     * @param array $packages
     * @return array Package names, or the swissup pattern when nothing is requested
     * @throws \RuntimeException
     */
    private function validatePackages(array $packages)
    {
        $installed = array_change_key_case($this->getInstalledPackages());

        if (!$packages) {
            return $installed ? ['swissup/*'] : [];
        }

        $names = [];

        foreach ($packages as $package) {
            $name = $this->getPackageName($package);

            if (!str_starts_with($name, 'swissup/')) {
                throw new \RuntimeException(sprintf(
                    'Only swissup packages can be updated with this command. Run composer update %s instead.',
                    $name
                ));
            }

            if (!isset($installed[$name])) {
                throw new \RuntimeException(sprintf(
                    'Package "%s" is not required in composer.json.',
                    $name
                ));
            }

            $names[] = $name;
        }

        return $names;
    }

    /**
     * @return array Package name => version constraint
     */
    private function getInstalledPackages()
    {
        return array_filter(
            $this->composer->getRequirements(),
            function ($name) {
                return str_starts_with(strtolower($name), 'swissup/');
            },
            ARRAY_FILTER_USE_KEY
        );
    }
}
