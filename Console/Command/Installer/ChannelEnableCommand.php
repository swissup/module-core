<?php
namespace Swissup\Core\Console\Command\Installer;

use Magento\Framework\Console\Cli;
use Swissup\Core\Model\Installer\ComposerRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class ChannelEnableCommand extends Command
{
    private ComposerRepository $repository;

    public function __construct(ComposerRepository $repository)
    {
        $this->repository = $repository;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('swissup:channel:enable')
            ->setDescription('Add SwissupLabs repository to composer.json file');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $username = $this->repository->getUsername();
            $keys = $this->repository->getKeys();
            $newKey = null;
            $packages = null;

            if ($keys) {
                $output->writeln('Currently used access keys:');
                foreach ($keys as $key) {
                    $domain = $this->repository->getKeyDomain($key);
                    $output->writeln('   - ' . ($domain ? sprintf('<info>%s</info>: %s', $domain, $key) : $key));
                }

                $question = new ConfirmationQuestion('Would you like to add another key? [y/N] ', false);
                if ($input->isInteractive() && $this->getHelper('question')->ask($input, $output, $question)) {
                    $newKey = $this->askKey($username, $input, $output);
                }
            } else {
                $newKey = $this->askKey($username, $input, $output);
            }

            if ($newKey !== null && in_array($newKey, $keys, true)) {
                $output->writeln('<comment>This key is already added</comment>');
            } elseif ($newKey !== null) {
                $packages = $this->repository->addKey($newKey);
                $output->writeln(sprintf(
                    '<info>Key accepted. Packages available with this key: %d</info>',
                    count($packages)
                ));

                $keys[] = $newKey;
            }

            if ($packages === null || count($keys) > 1) {
                $packages = $this->repository->getPackages($username, implode(' ', $keys));
            }
            $output->writeln(sprintf('<info>Available packages: %d</info>', count($packages)));

            if ($this->repository->isEnabled()) {
                $output->writeln('<info>Repository is already enabled</info>');
            } else {
                $this->repository->enable();
                $output->writeln('<info>Repository was enabled</info>');
            }
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }

    /**
     * @param string $username
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return string
     * @throws \RuntimeException
     */
    private function askKey($username, InputInterface $input, OutputInterface $output)
    {
        if (!$input->isInteractive()) {
            throw new \RuntimeException(sprintf('Access key for "%s" is required', $username));
        }

        $output->writeln([
            '1. Navigate to your account on the site where you\'ve made a purchase:',
            '   - <fg=blue>https://argentotheme.com/license/customer/activation/</>',
            '   - <fg=blue>https://firecheckout.net/license/customer/activation/</>',
            '   - <fg=blue>https://swissuplabs.com/license/customer/activation/</>',
            sprintf('2. Activate <fg=green;options=bold>%s</> domain.', $username),
            '3. Copy your access key and paste it here.',
        ]);

        $question = new Question(sprintf('Access key for <fg=green;options=bold>%s</>: ', $username));
        $question->setValidator(function ($value) {
            if (trim((string) $value) === '') {
                throw new \RuntimeException('Value cannot be empty');
            }
            return trim($value);
        });

        return $this->getHelper('question')->ask($input, $output, $question);
    }
}
