<?php
namespace Swissup\Core\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;
use Swissup\Core\Console\Command\ThemeCreate;

use Symfony\Component\Console\Input\InputOption;

class ThemeCreateCommand extends Command
{
    const THEME_DIR = 'design/frontend';

    const SECTION = 'frontend';

    /**
     *
     * @var ThemeCreate
     */
    private $create;

    /**
     * Inject dependencies
     *
     * @param \Swissup\Core\Console\Command\ThemeCreate $create
     * @param null $name
     */
    public function __construct(ThemeCreate $create, $name = null)
    {
        $this->create = $create;
        parent::__construct($name);
    }

    /**
     * Define Symfony\Console compatible command
     */
    protected function configure()
    {
        $this->setName('swissup:theme:create')
            ->setDescription('Create Local Swissup theme')
            ->addArgument('name', InputArgument::REQUIRED, 'Put the theme name you want to create (Local/argento-stripes)')
            ->addArgument('parent', InputArgument::REQUIRED, 'Put the parent short theme name (stripes)');

        $this->addOption(
            'css',
            null,
            InputOption::VALUE_OPTIONAL,
            'Should I create custom css?',
            false
        );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $themeName = $input->getArgument('name');
        if (strpos($themeName, '/') === false) {
            $themeName = 'Local/' . $themeName;
        }
        $parent = $input->getArgument('parent');

        if (strpos($parent, 'Swissup_ThemeFrontendArgento') === 0) {
            $parent = str_replace('Swissup_ThemeFrontendArgento', '', $parent);
            $parent = strtolower($parent);
        }

        $parentThemeName = 'Swissup/argento-' . $parent;
        $parentThemePackageName = 'swissup/theme-frontend-argento-' . $parent;

        if ($this->create->isExist($themeName)) {
            $output->writeln('<error>Theme dir already exist</error>');
            return 9;
        }
        $registration = $this->create->generateRegistration($themeName);
        $themeXml = $this->create->generateThemeXml($themeName, $parentThemeName);
        $composerjson = $this->create->generateComposerJson($themeName, $parentThemePackageName);

        $withCss = $input->getOption('css');
        $withCss = ($withCss !== false);
        if ($withCss) {
            $this->create->generateCustomCss($themeName);
        }

        if ($registration < 1 || $themeXml < 1 || $composerjson < 1) {
            $output->writeln('<error>Failed to generate files</error>');
            return 9;
        }

        $output->writeln('<success>New Local Swissup theme create done!</success>');
        $output->writeln('<warn>Please run setup:upgrade from Magento CLI</warn>');
    }
}
