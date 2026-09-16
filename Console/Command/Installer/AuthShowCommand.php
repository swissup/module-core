<?php
namespace Swissup\Core\Console\Command\Installer;

use Magento\Framework\Console\Cli;
use Swissup\Core\Model\Installer\ComposerRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AuthShowCommand extends Command
{
    private ComposerRepository $repository;

    public function __construct(ComposerRepository $repository)
    {
        $this->repository = $repository;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('swissup:auth:show')
            ->setDescription('Display your access keys');
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

            $table = new Table($output);
            $table->setHeaders(['Provider', 'Key']);

            foreach ($keys as $key) {
                $table->addRow([$this->repository->getKeyDomain($key) ?: '', $key]);
            }

            $table->render();
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }
}
