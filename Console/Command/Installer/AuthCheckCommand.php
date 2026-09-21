<?php
namespace Swissup\Core\Console\Command\Installer;

use Magento\Framework\Console\Cli;
use Swissup\Core\Model\ComponentList\Loader\Remote;
use Swissup\Core\Model\Installer\ComposerRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AuthCheckCommand extends Command
{
    public function __construct(
        private ComposerRepository $repository,
        private Remote $remote
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('swissup:auth:check')
            ->setDescription('Display SwissupLabs access keys with the number of packages available');
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

            $username = $this->repository->getUsername();
            $latest = $this->remote->getComponentsInfo();
            $packages = $this->repository->getPackagesBatch($username, $keys);

            $table = new Table($output);
            $table->setHeaders(['Username', 'Key', 'Provider', 'Packages']);

            foreach ($keys as $i => $key) {
                $summary = $packages[$key] instanceof \Exception
                    ? '<error>' . $packages[$key]->getMessage() . '</error>'
                    : $this->summarize($packages[$key], $latest);

                $table->addRow([
                    $i ? '' : $username,
                    $key,
                    $this->repository->getKeyDomain($key) ?: '',
                    $summary,
                ]);
            }

            $table->render();
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }

    /**
     * @param array $packages
     * @param array $latest
     * @return string
     */
    private function summarize(array $packages, array $latest)
    {
        $outdated = 0;

        foreach ($packages as $name => $versions) {
            if (empty($latest[$name]['version'])) {
                continue;
            }

            $version = $this->getLatestVersion(array_keys($versions));
            if ($version && version_compare($version, $latest[$name]['version'], '<')) {
                $outdated++;
            }
        }

        if (!$outdated) {
            return (string) count($packages);
        }

        return sprintf('%d (<comment>%d requires renewal</comment>)', count($packages), $outdated);
    }

    /**
     * @param string[] $versions
     * @return string
     */
    private function getLatestVersion(array $versions)
    {
        $versions = array_filter($versions, fn ($version) => strpos($version, 'dev-') !== 0);
        usort($versions, 'version_compare');

        return (string) end($versions);
    }
}
