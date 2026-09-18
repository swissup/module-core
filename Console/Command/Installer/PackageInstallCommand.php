<?php

namespace Swissup\Core\Console\Command\Installer;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class PackageInstallCommand extends Command
{
    const INPUT_KEY_STORE = 'store';

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Symfony\Component\Console\Question\QuestionFactory
     */
    protected $questionFactory;

    /**
     * @var \Symfony\Component\Console\Question\ChoiceQuestionFactory
     */
    protected $choiceQuestionFactory;

    /**
     * @var \Symfony\Component\Console\Helper\QuestionHelper
     */
    protected $questionHelper;

    /**
     * @var \Swissup\Core\Installer\Installer
     */
    protected $installer;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Symfony\Component\Console\Question\QuestionFactory $questionFactory
     * @param \Symfony\Component\Console\Question\ChoiceQuestionFactory $choiceQuestionFactory
     * @param \Symfony\Component\Console\Helper\QuestionHelper $questionHelper
     * @param \Swissup\Core\Installer\Installer $installer
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Symfony\Component\Console\Question\QuestionFactory $questionFactory,
        \Symfony\Component\Console\Question\ChoiceQuestionFactory $choiceQuestionFactory,
        \Symfony\Component\Console\Helper\QuestionHelper $questionHelper,
        \Swissup\Core\Installer\Installer $installer
    ) {
        $this->storeManager = $storeManager;
        $this->questionFactory = $questionFactory;
        $this->choiceQuestionFactory = $choiceQuestionFactory;
        $this->questionHelper = $questionHelper;
        $this->installer = $installer;

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

        $this->installer
            ->setLogger($this->logger)
            ->run($packages, array_merge($formData, [
                'store_id' => $storeIds,
                'packages' => $packages,
            ]));

        $output->writeln('<info>Done.</info>');

        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
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
