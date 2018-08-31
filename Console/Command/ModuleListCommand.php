<?php
namespace Swissup\Core\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;

use Swissup\Core\Model\ComponentList\Loader;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;

/**
 * Command for displaying status of swissup modules
 */
class ModuleListCommand extends Command
{
    /**
     *
     * @var \Swissup\Core\Model\ComponentList\Loader
     */
    private $loader;

    /**
     * @var \Magento\Framework\Module\Dir\Reader
     */
    private $componentRegistrar;

    /**
     * Inject dependencies
     *
     * @param \Swissup\Core\Model\ComponentList\Loader $loader
     * @param ComponentRegistrarInterface $componentRegistrar
     */
    public function __construct(
        Loader $loader,
        ComponentRegistrarInterface $componentRegistrar
    ) {
        $this->loader = $loader;
        $this->componentRegistrar = $componentRegistrar;
        parent::__construct();
    }


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('swissup:module:list')
            ->setDescription('Displays status of swissup modules');
        parent::configure();
    }

     /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $items = $this->loader->getItems();
        $output->writeln('<info>List of swissup modules</info> : ' . count($items));

        $rows = [];
        $i = 0;
        $separator = new TableSeparator();
        foreach ($items as $item) {
            $row = [];
            foreach (explode(',', 'name,code,version,latest_version,type,release_date') as $key) {
                $row[$key] = isset($item[$key]) ? $item[$key]: '';
            }

            $row['version'] = '<fg=' . (version_compare($row['version'], $row['latest_version'], '>=') ? 'green' : 'red') . '>'
                . $row['version']
            . '</>';

            $row['release_date'] = date("Y-m-d", strtotime($row['release_date']));

            $type = ComponentRegistrar::MODULE;
            $code = $row['code'];
            if ($row['type'] == 'magento2-theme') {
                $type = ComponentRegistrar::THEME;
                $code = substr($code, strlen('Swissup_ThemeFrontend'));
                $code = strtolower(trim(preg_replace('/([A-Z]+)/', "-$1", $code), '-'));
                $code = 'frontend/Swissup/' . $code;
            }
            $row['location'] = $this->componentRegistrar->getPath($type, $code);

            $rows[] = $row;

            $i++;
            if ($i === 10) {
                $i = 0;
                $rows[] = $separator;
            }
        }

        $table = new Table($output);
        $table->setHeaders(['Package', 'Module', 'Version', 'Latest', 'Type', 'Date', 'Location']);
        $table->setRows($rows);

        $table->render();
    }
}
