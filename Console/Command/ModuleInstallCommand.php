<?php
namespace Swissup\Core\Console\Command;

use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;

use Swissup\Core\Model\ComponentList\Loader;
use Swissup\Core\Console\Command\EmulatedAreaProcessor;

/**
 * Command for displaying info of swissup module
 */
class ModuleInstallCommand extends Command
{
    const INPUT_ARGUMENT_MODULE = 'module';
    const INPUT_ARGUMENT_IDENTITY_KEY = 'identity_key';
    const INPUT_OPTION_STORE = 'store';

    /**
     *
     * @var \Swissup\Core\Model\ComponentList\Loader
     */
    private $loader;

    /**
     *
     * @var \Swissup\Core\Model\ModuleFactory
     */
    private $moduleFactory;

    /** @var EmulatedAreaProcessor */
    private $emulatedAreaProcessor;

    /**
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * Inject dependencies
     *
     * @param \Swissup\Core\Model\ComponentList\Loader $loader
     * @param \Swissup\Core\Model\ModuleFactory $moduleFactory
     * @param EmulatedAreaProcessor $emulatedAreaProcessor Emulator adminhtml area for CLI command
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        Loader $loader,
        \Swissup\Core\Model\ModuleFactory $moduleFactory,
        EmulatedAreaProcessor $emulatedAreaProcessor,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->loader = $loader;
        $this->moduleFactory = $moduleFactory;
        $this->emulatedAreaProcessor = $emulatedAreaProcessor;
        $this->storeManager = $storeManager;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->addArgument(
            self::INPUT_ARGUMENT_MODULE,
            InputArgument::REQUIRED,
            'Module Name (php bin/magento swissup:module:list ) [Swissup_Core|Core|swissup/core|core]'
        );

        $this->addArgument(
            self::INPUT_ARGUMENT_IDENTITY_KEY,
            InputArgument::OPTIONAL,
            'Identity Key (Get your identity key at https://argentotheme.com/license/customer/activation/)'
        );

        $this->addOption(
            self::INPUT_OPTION_STORE,
            null,
            InputOption::VALUE_REQUIRED, // InputOption::VALUE_OPTIONAL,
            'Store ID (php bin/magento store:list).'//,
            // 0
        );

        $this->setName('swissup:module:install')
            ->setDescription('Install swissup module(theme)');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return $this->emulatedAreaProcessor->process(function () use ($input, $output) {
            $io = new SymfonyStyle($input, $output);

            $io->progressStart(100);
            $io->progressAdvance(0);

            $moduleCode = $input->getArgument(self::INPUT_ARGUMENT_MODULE);

            $items = $this->loader->getItems();

            $codes = array_column($items, 'code', 'name');
            $packages = array_keys($codes);
            if (in_array('Swissup_' . $moduleCode, $codes)) {
                $moduleCode = 'Swissup_' . $moduleCode;
            } elseif (in_array('swissup/' . $moduleCode, $packages)) {
                $moduleCode = 'swissup/' . $moduleCode;
            }

            if (in_array($moduleCode, $packages)) {
                $moduleCode = $codes[$moduleCode];
            }

            if (!isset($items[$moduleCode])) {
                $io->newLine();
                $io->caution('Package[Module] ' . $moduleCode .' doesn\'t exist');
                $io->note('Run : php bin/magento swissup:module:list');
                return Cli::RETURN_FAILURE;
            }
            $io->progressAdvance(10);

            $stores = $input->getOption(self::INPUT_OPTION_STORE);
            $stores = explode(',', $stores);

            $moduleModel = $this->moduleFactory->create();
            $moduleModel->load($moduleCode);
            $moduleModel->setNewStores($stores);
            $io->progressAdvance(10);

            $identityKey = $input->getArgument(self::INPUT_ARGUMENT_IDENTITY_KEY);
            if (!empty($identityKey)) {
                $moduleModel->setIdentityKey($identityKey);
            }

            $baseUrl = $this->storeManager->getStore()->getBaseUrl();
            $domain = parse_url($baseUrl, PHP_URL_HOST);

            $moduleModel->setDomain($domain);

            $result = $moduleModel->validateLicense();
            $io->progressAdvance(30);
            if (is_array($result) && isset($result['error'])) {
                $errors = $result['error'];
                $errors = call_user_func_array('__', $errors);
                $io->newLine();
                $io->error($errors);

                return Cli::RETURN_FAILURE;
            }

            $moduleModel->up();
            $io->progressFinish();

            $groupedErrors = $moduleModel->getInstaller()->getMessageLogger()->getErrors();

            if (count($groupedErrors)) {
                foreach ($groupedErrors as $type => $errors) {
                    foreach ($errors as $error) {
                        if (is_array($error)) {
                            $message = $error['message'];
                        } else {
                            $message = $error;
                        }

                        $io->newLine();
                        $io->error($message);
                    }
                }
                return Cli::RETURN_FAILURE;
            }

            $io->success("{$moduleCode} was installed");
            return Cli::RETURN_SUCCESS;
        });
    }
}
