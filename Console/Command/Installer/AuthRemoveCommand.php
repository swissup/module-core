<?php
namespace Swissup\Core\Console\Command\Installer;

use Magento\Framework\Console\Cli;
use Swissup\Core\Model\Installer\ComposerRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AuthRemoveCommand extends Command
{
    const INPUT_ARGUMENT_KEY = 'key';

    private ComposerRepository $repository;

    public function __construct(ComposerRepository $repository)
    {
        $this->repository = $repository;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('swissup:auth:remove')
            ->setDescription('Remove SwissupLabs access key')
            ->addArgument(self::INPUT_ARGUMENT_KEY, InputArgument::REQUIRED, 'Access key');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $key = trim($input->getArgument(self::INPUT_ARGUMENT_KEY));

        try {
            if (!$this->repository->removeKey($key)) {
                $output->writeln(sprintf(
                    '<comment>The key is saved in global composer auth.json. Remove it with:%s</comment>',
                    "\n  composer config --global --unset http-basic." . ComposerRepository::HOSTNAME
                ));
                return Cli::RETURN_FAILURE;
            }

            $output->writeln('<info>The key was removed</info>');
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }
}
