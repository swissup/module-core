<?php
namespace Swissup\Core\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;

use Swissup\Core\Model\ComponentList\Loader;

/**
 * Command for displaying status of swissup modules
 */
class ModuleListCommand extends Command
{
    const INPUT_OPTION_TYPE = 'type';
    const INPUT_OPTION_ALL = 'all';
    const INPUT_OPTION_ALL_SHORTCUT = 'a';

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
        $this->addOption(
            self::INPUT_OPTION_TYPE,
            null,
            InputOption::VALUE_OPTIONAL,
            'module-type [module|theme|magento2-module|magento2-theme].',
            ''
        );

        $this->addOption(
            self::INPUT_OPTION_ALL,
            self::INPUT_OPTION_ALL_SHORTCUT,
            InputOption::VALUE_NONE,
            'Show all information'
        );

        $this->setName('swissup:module:list')
            ->setDescription('Displays status of swissup modules');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getOption(self::INPUT_OPTION_TYPE);
        $all = (bool) $input->getOption(self::INPUT_OPTION_ALL);

        if (!in_array($type, ['magento2-module', 'magento2-theme'])) {
            $type = 'magento2-' . $type;
        }
        if (!in_array($type, ['magento2-module', 'magento2-theme'])) {
            $type = false;
        }

        $items = $this->loader->getItems();
        $output->writeln('<info>List of swissup modules</info> : ' . count($items));

        $rows = [];
        $i = 0;
        $separator = new TableSeparator();
        $columns = explode(',', 'name,code,version,latest_version,type');
        if ($all) {
            $columns[] = 'release_date';
            $columns[] = 'path';
        }
        foreach ($items as $item) {
            if ($type !== false && $item['type'] != $type) {
                continue;
            }
            $row = [];
            foreach ($columns as $key) {
                $row[$key] = isset($item[$key]) ? $item[$key]: '';
            }

            $color = version_compare($row['version'], $row['latest_version'], '>=') ? 'green' : 'red';
            $row['version'] = "<fg={$color}>{$row['version']}</>";

            if (isset($row['release_date'])) {
                $row['release_date'] = date("Y-m-d", strtotime($row['release_date']));
            }
            if (!empty($row['path']) && strstr($row['path'], '/vendor/')) {
                list(, $row['path']) = explode('/vendor/', $row['path']);
                $row['path'] = './vendor/' . $row['path'];
            }

            $rows[] = $row;

            $i++;
            if ($i === 10) {
                $i = 0;
                $rows[] = $separator;
            }
        }

        $table = new Table($output);
        $headers = ['Package', 'Module', 'Version', 'Latest', 'Type'];
        if ($all) {
            $headers[] = 'Date';
            $headers[] = 'Path';
        }
        $table->setHeaders($headers);
        $table->setRows($rows);

        $table->render();
    }
}
