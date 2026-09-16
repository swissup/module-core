<?php
namespace Swissup\Core\Console\Command\Installer;

use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PackageRemoveCommand extends PackageAbstractCommand
{
    protected function configure()
    {
        $this->setName('swissup:remove')
            ->setDescription('Remove swissup package(s) using composer and run setup:upgrade')
            ->addArgument(
                self::INPUT_ARGUMENT_PACKAGES,
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'Package name(s): swissup/firecheckout'
            )
            ->addOption(
                self::INPUT_OPTION_DRY_RUN,
                null,
                InputOption::VALUE_NONE,
                'Show what would be removed without changing any files'
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

            $this->validate([
                'composer remove ' . implode(' ', $packages),
                'bin/magento setup:upgrade',
            ], $isDryRun);

            if ($isDryRun) {
                $args = array_merge($this->getRemoveArgs($packages), ['--dry-run']);
                return $this->composer->run($args, $output, $input->isInteractive())
                    ? Cli::RETURN_FAILURE
                    : Cli::RETURN_SUCCESS;
            }

            return $this->runAndUpgrade($this->getRemoveArgs($packages), $input, $output);
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Cli::RETURN_FAILURE;
        }
    }

    /**
     * @param array $packages
     * @return array
     */
    private function getRemoveArgs(array $packages)
    {
        $args = array_merge(
            ['remove'],
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
     * Only swissup packages listed in composer.json can be removed.
     * Version constraint is dropped: composer remove does not accept it.
     *
     * @param array $packages
     * @return array Package names
     * @throws \RuntimeException
     */
    private function validatePackages(array $packages)
    {
        $required = array_change_key_case($this->composer->getRequirements());
        $names = [];

        foreach ($packages as $package) {
            $name = $this->getPackageName($package);

            if (strpos($name, 'swissup/') !== 0) {
                throw new \RuntimeException(sprintf(
                    'Only swissup packages can be removed with this command. Run composer remove %s instead.',
                    $name
                ));
            }

            if ($name === 'swissup/module-core') {
                throw new \RuntimeException(
                    'This command is a part of swissup/module-core. Run composer remove swissup/module-core instead.'
                );
            }

            if (!isset($required[$name])) {
                throw new \RuntimeException(sprintf(
                    'Package "%s" is not required in composer.json.',
                    $name
                ));
            }

            $names[] = $name;
        }

        return $names;
    }
}
