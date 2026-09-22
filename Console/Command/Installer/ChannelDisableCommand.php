<?php
namespace Swissup\Core\Console\Command\Installer;

use Magento\Framework\Console\Cli;
use Swissup\Core\Model\Installer\ComposerRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ChannelDisableCommand extends Command
{
    public function __construct(private ComposerRepository $repository)
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('swissup:channel:disable')
            ->setDescription('Remove SwissupLabs repository from composer.json file');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            if (!$this->repository->isEnabled()) {
                $output->writeln('<comment>Repository is not enabled</comment>');
                return Cli::RETURN_SUCCESS;
            }

            $this->repository->disable();
            $output->writeln('<info>Repository was disabled</info>');

            if ($this->repository->getKeys()) {
                $output->writeln(
                    'Access keys are kept. Run <comment>bin/magento swissup:auth:remove {key}</comment> to remove them.'
                );
            }
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }
}
