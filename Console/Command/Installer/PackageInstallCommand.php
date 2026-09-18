<?php

namespace Swissup\Core\Console\Command\Installer;

use Magento\Framework\Component\ComponentRegistrar;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class PackageInstallCommand extends Command
{
    const INPUT_KEY_STORE = 'store';
    const INPUT_KEY_NO_DOWNLOAD = 'no-download';

    protected InputInterface $input;

    protected OutputInterface $output;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    public function __construct(
        protected \Magento\Store\Model\StoreManagerInterface $storeManager,
        protected \Symfony\Component\Console\Question\QuestionFactory $questionFactory,
        protected \Symfony\Component\Console\Question\ChoiceQuestionFactory $choiceQuestionFactory,
        protected \Symfony\Component\Console\Helper\QuestionHelper $questionHelper,
        protected \Swissup\Core\Installer\Installer $installer,
        protected \Magento\Framework\Component\ComponentRegistrarInterface $componentRegistrar,
        protected \Magento\Theme\Model\Theme\ThemePackageInfo $themePackageInfo,
        protected \Swissup\Core\Helper\Component $componentHelper,
        protected \Swissup\Core\Model\Installer\Process $process
    ) {
        parent::__construct();
    }

    /**
     * Initializes the command after the input has been bound and before the input
     * is validated.
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->input = $input;
        $this->output = $output;
        $this->logger = new ConsoleLogger($output);

        $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);

        if (!function_exists('exec') || !function_exists('shell_exec')) {
            if (method_exists(QuestionHelper::class, 'disableStty')) {
                QuestionHelper::disableStty();
            }
        }

        parent::initialize($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('swissup:package:install')
            ->setDescription('Run installer of the downloaded SwissupLabs package(s)');

        $this->addArgument(
            PackageAbstractCommand::INPUT_ARGUMENT_PACKAGES,
            InputArgument::IS_ARRAY | InputArgument::REQUIRED,
            'Package name(s) and installer answers: swissup/firecheckout firecheckout_theme=light'
        );
        $this->addOption(
            self::INPUT_KEY_STORE,
            null,
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
            'Store ID'
        );
        $this->addOption(
            'commands',
            null,
            InputOption::VALUE_NONE,
            'Show available commands'
        );
        $this->addOption(
            self::INPUT_KEY_NO_DOWNLOAD,
            null,
            InputOption::VALUE_NONE,
            'Do not offer to download the packages missing in the codebase'
        );

        parent::configure();
    }

    protected function getArguments()
    {
        return $this->input->getArgument(PackageAbstractCommand::INPUT_ARGUMENT_PACKAGES);
    }

    protected function getPackages()
    {
        $packages = [];

        foreach ($this->getArguments() as $argument) {
            if (strpos($argument, '=') !== false || strpos($argument, '/') === false) {
                continue;
            }
            $packages[] = $argument;
        }

        if (!$packages) {
            throw new \RuntimeException('Package name is missing');
        }

        return $packages;
    }

    protected function getRequestParams($commands)
    {
        $params = [];

        foreach ($this->getArguments() as $argument) {
            if (strpos($argument, '/') !== false) {
                continue;
            }

            if (strpos($argument, '=') === false && !in_array($argument, $commands)) {
                continue;
            }

            $parts = explode('=', $argument);

            if (isset($params[$parts[0]])) {
                if (!is_array($params[$parts[0]])) {
                    $params[$parts[0]] = [$params[$parts[0]]];
                }
                $params[$parts[0]][] = $parts[1] ?? '';
            } else {
                $params[$parts[0]] = $parts[1] ?? '';
            }
        }

        return $params;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $packages = $this->getPackages();

        if (!$this->installer->hasInstaller($packages)) {
            $missing = $this->getMissingPackages($packages);

            if ($missing && $this->confirmDownload($missing)) {
                return $this->download($missing);
            }

            $output->writeln('<error>Installer file is not found.</error>');
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        $commands = $this->installer->getCommandAliases($packages);
        if ($input->getOption('commands')) {
            $commandsStr = implode(', ', $commands);
            $output->writeln("<info>Available commands: {$commandsStr}</info>");
            return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
        }

        $storeIds = $this->getStoreIds();
        $formData = [];
        $params = $this->getRequestParams($commands);

        foreach ($commands as $alias) {
            if (isset($params[$alias])) {
                $formData[$alias] = $params[$alias];
                $this->installer->setRunOnlyIfRequired(true);
            } elseif (isset($params['skip-' . $alias])) {
                $formData['skip-' . $alias] = $params['skip-' . $alias];
            }
        }

        $fields = $this->installer->getFormConfig($packages);
        foreach ($fields as $name => $config) {
            if (isset($params[$name])) {
                $formData[$name] = $params[$name];
            } else {
                $formData[$name] = $this->ask($config['title'], $config['options'] ?? null);
            }
        }

        $this->installer->setLogger($this->logger);
        $this->installer->run($packages, array_merge($formData, [
            'store_id' => $storeIds,
            'packages' => $packages,
        ]));

        $output->writeln('<info>Done.</info>');

        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
    }

    /**
     * Packages that are not registered in the codebase as a module or a theme
     *
     * @param array $packages
     * @return array
     */
    private function getMissingPackages(array $packages)
    {
        if ($this->input->getOption(self::INPUT_KEY_NO_DOWNLOAD)) {
            return [];
        }

        return array_filter($packages, function ($package) {
            $moduleName = $this->componentHelper->convertPackageNameToModuleName($package);

            return !$this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $moduleName)
                && !$this->themePackageInfo->getFullThemePath($package);
        });
    }

    /**
     * @param array $packages
     * @return boolean
     */
    private function confirmDownload(array $packages)
    {
        if (!$this->input->isInteractive()) {
            return false;
        }

        $question = new ConfirmationQuestion(
            sprintf(
                '<comment>Package(s) are not downloaded yet: %s</comment> Download and run installer? [Y/n] ',
                implode(' ', $packages)
            ),
            true
        );

        return $this->questionHelper->ask($this->input, $this->output, $question);
    }

    /**
     * Downloaded code is not registered in the running process, so the
     * installer is started again in a child process.
     *
     * @param array $packages
     * @return int
     */
    private function download(array $packages)
    {
        $arrayInput = new ArrayInput([
            PackageAbstractCommand::INPUT_ARGUMENT_PACKAGES => array_values($packages),
        ]);
        $arrayInput->setInteractive($this->input->isInteractive());

        $code = $this->getApplication()
            ->find('swissup:package:require')
            ->run($arrayInput, $this->output);

        if ($code !== \Magento\Framework\Console\Cli::RETURN_SUCCESS) {
            return $code;
        }

        return $this->process->run(
            $this->getRerunCommand(),
            $this->output,
            $this->input->isInteractive()
        );
    }

    /**
     * @return array
     */
    private function getRerunCommand()
    {
        $command = array_merge(
            [BP . '/bin/magento', $this->getName()],
            $this->getArguments(),
            ['--' . self::INPUT_KEY_NO_DOWNLOAD]
        );

        foreach ($this->input->getOption(self::INPUT_KEY_STORE) as $store) {
            $command[] = '--' . self::INPUT_KEY_STORE . '=' . $store;
        }

        if ($this->input->getOption('commands')) {
            $command[] = '--commands';
        }

        return $command;
    }

    private function getStoreIds()
    {
        $input = $this->input->getOption(self::INPUT_KEY_STORE);

        if (!$input) {
            $result = $this->askStoreIds();
        } else {
            // fix for the case when user entered --store=1,2 instead of --store=1 --store=2
            $result = [];
            foreach ($input as $ids) {
                foreach (explode(',', $ids) as $id) {
                    $result[] = $id;
                }
            }
        }

        return $result;
    }

    private function askStoreIds()
    {
        $stores = $this->getStoreList();

        $codes = $this->ask(
            (string) __('Please, select a Store'),
            $stores,
            true
        );

        $ids = [];
        $codeToId = array_flip($stores);
        foreach ($codes as $code) {
            $ids[] = $codeToId[$code];
        }

        return $ids;
    }

    private function ask($title, $options = null, $multiple = false)
    {
        if (!is_array($options)) {
            $question = $this->questionFactory->create([
                'question' => $title . ': ',
            ]);

            return $this->questionHelper->ask($this->input, $this->output, $question);
        }

        $choices = is_array(current($options)) ?
            $this->optionsToChoices($options) : $options;

        if (count($choices) > 1) {
            $question = $this->choiceQuestionFactory->create([
                'question' => $title,
                'choices' => $choices,
            ]);

            if ($multiple) {
                $question->setMultiselect(true);
            }

            $answer = $this->questionHelper->ask($this->input, $this->output, $question);
        } else {
            $answer = key($choices);
        }

        return $answer;
    }

    private function optionsToChoices($options)
    {
        $choices = [];
        foreach ($options as $option) {
            $choices[$option['value']] = $option['label'];
        }
        return $choices;
    }

    /**
     * Get the list of the stores WITHOUT WHITESPACES!
     * Symfony multiselect losing whitespaces.
     *
     * symfony/console/Question/ChoiceQuestion.php:133 and 140:
     * $selectedChoices = str_replace(' ', '', $selected);
     * $selectedChoices = explode(',', $selectedChoices);
     */
    private function getStoreList()
    {
        $result = [
            '0' => (string) __('All'),
        ];

        foreach ($this->storeManager->getStores() as $id => $store) {
            $result[(string)$id] = sprintf(
                '%s.[%s]',
                str_replace(' ', ' ', str_pad($store->getName(), 20, '.')),
                $store->getCode()
            );
        }

        return $result;
    }
}
