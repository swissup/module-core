<?php
namespace Swissup\Core\Console\Command\Installer;

use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PackageUpdateCommand extends PackageAbstractCommand
{
    const PACKAGES_PATTERN = 'swissup/*';
    const INPUT_OPTION_WITH_DEPENDENCIES = 'with-dependencies';

    protected function configure()
    {
        $this->setName('swissup:package:update')
            ->setDescription('Update SwissupLabs packages using composer and run setup:upgrade')
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
        $args = $this->getUpdateArgs(
            (bool) $input->getOption(self::INPUT_OPTION_WITH_DEPENDENCIES)
        );

        try {
            if (!$this->getInstalledPackages()) {
                $output->writeln('<comment>There are no swissup packages to update</comment>');
                return Cli::RETURN_SUCCESS;
            }

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
     * @param boolean $withDependencies
     * @return array
     */
    private function getUpdateArgs($withDependencies)
    {
        $args = [
            'update',
            self::PACKAGES_PATTERN,
            '--no-progress',
        ];

        if ($withDependencies) {
            $args[] = '--with-dependencies';
        }

        if (!$this->composer->isDevInstalled()) {
            $args[] = '--no-dev';
        }

        return $args;
    }

    /**
     * @return array Package name => version constraint
     */
    private function getInstalledPackages()
    {
        $prefix = rtrim(self::PACKAGES_PATTERN, '*');

        return array_filter(
            $this->composer->getRequirements(),
            function ($name) use ($prefix) {
                return strpos(strtolower($name), $prefix) === 0;
            },
            ARRAY_FILTER_USE_KEY
        );
    }
}
