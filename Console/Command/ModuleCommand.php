<?php
namespace Swissup\Core\Console\Command;

use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;

use Swissup\Core\Model\ComponentList\Loader;

/**
 * Command for displaying info of swissup module
 */
class ModuleCommand extends Command
{
    /**
     *
     * @var \Swissup\Core\Model\ComponentList\Loader
     */
    private $loader;

    /**
     * Inject dependencies
     *
     * @param \Swissup\Core\Model\ComponentList\Loader $loader
     */
    public function __construct(Loader $loader)
    {
        $this->loader = $loader;
        parent::__construct();
    }


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->addArgument(
            'name',
            InputArgument::REQUIRED,
            'Package name or Module name (swissup/core or Swissup_Core)'
        );

        $this->setName('swissup:module')
            ->setDescription('Displays info of swissup module');
        parent::configure();
    }

    private function getLabelMapping()
    {
        return [
            'name' => 'Package',
            'code' => 'Module',
            'type' => 'Type',
            'description' => 'Description',
            'keywords' => 'Keywords',
            'version' => 'Version',
            'latest_version' => 'Latest Version',
            'release_date' => 'Released Date',
            'path' => 'Path',
            'link' => 'Homepage',
            'docs_link' => 'Documentation',
            // 'download_link' => 'Download',
            'changelog_link' => 'Changelog',
            // 'identity_key_link'
        ];
    }

     /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');

        $items = $this->loader->getItems();
        $codes = array_column($items, 'code', 'name');
        $packages = array_keys($codes);
        if (in_array('Swissup_' . $name, $codes)) {
            $name = 'Swissup_' . $name;
        } elseif (in_array('swissup/' . $name, $packages)) {
            $name = 'swissup/' . $name;
        }

        if (in_array($name, $packages)) {
            $name = $codes[$name];
        }
        // $output->writeln($name);
        if (!isset($items[$name])) {
            $output->writeln('<error>Package[Module] ' . $name .' doesn\'t exist</error>');
            $output->writeln('Run : <fg=yellow>php bin/magento swissup:module:list</>');
            return Cli::RETURN_FAILURE;
        }
        $item = $items[$name];
        $color = version_compare($item['version'], $item['latest_version'], '>=') ? 'green' : 'red';
        $item['version'] = "<fg={$color}>{$item['version']}</>";
        $item['release_date'] = date("Y-m-d H:i", strtotime($item['release_date']));

        $rows = [];
        foreach ($this->getLabelMapping() as $key => $label) {
            if (isset($item[$key])) {
                // $output->writeln("<info>$label : </info>" . $item[$key]);
                $rows[] = ["<info>$label</info>", $item[$key]];
            }
        }

        $table = new Table($output);
        // $table->setHeaders(['Param', 'Value']);
        $table->setRows($rows);
        // \Zend_Debug::dump($items[$name]);
        $table->render();
    }
}
