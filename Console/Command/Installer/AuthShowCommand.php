<?php
namespace Swissup\Core\Console\Command\Installer;

use Magento\Framework\Console\Cli;
use Swissup\Core\Installer\ComposerRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AuthShowCommand extends Command
{
    public function __construct(private ComposerRepository $repository)
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('swissup:auth:show')
            ->setDescription('Display SwissupLabs access keys currently in use');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $keys = $this->repository->getKeys();

            if (!$keys) {
                $output->writeln('<comment>No access keys found. Run bin/magento swissup:auth:add {key} to add one.</comment>');
                return Cli::RETURN_SUCCESS;
            }

            $output->writeln('Username: ' . $this->repository->getUsername());
            $output->writeln('Password: ' . implode(' ', $keys));
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }
}
