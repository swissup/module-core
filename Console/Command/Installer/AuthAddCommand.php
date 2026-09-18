<?php
namespace Swissup\Core\Console\Command\Installer;

use Magento\Framework\Console\Cli;
use Swissup\Core\Model\Installer\ComposerRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AuthAddCommand extends Command
{
    const INPUT_ARGUMENT_KEY = 'key';

    public function __construct(private ComposerRepository $repository)
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('swissup:auth:add')
            ->setDescription('Add SwissupLabs access key')
            ->addArgument(self::INPUT_ARGUMENT_KEY, InputArgument::REQUIRED, 'Access key');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $key = trim($input->getArgument(self::INPUT_ARGUMENT_KEY));

        try {
            if (in_array($key, $this->repository->getKeys(), true)) {
                $output->writeln('<comment>This key is already added</comment>');
                return Cli::RETURN_SUCCESS;
            }

            $packages = $this->repository->addKey($key);
            $output->writeln(sprintf(
                '<info>Key accepted. Packages available with this key: %d</info>',
                count($packages)
            ));

            if (!$this->repository->isEnabled()) {
                $output->writeln(
                    '<comment>SwissupLabs repository is not enabled. Run bin/magento swissup:channel:enable</comment>'
                );
            }
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }
}
