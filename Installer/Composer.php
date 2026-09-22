<?php

namespace Swissup\Core\Installer;

use Composer\Json\JsonFile;
use Magento\Framework\Composer\ComposerJsonFinder;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Runs bundled composer in a child process.
 *
 * In-process Composer\Console\Application is not used because:
 *  - bin/magento has already loaded classes that composer may update
 *  - Magento overrides COMPOSER_HOME with var/composer_home
 */
class Composer
{
    public function __construct(
        private ComposerJsonFinder $composerJsonFinder,
        private Process $process
    ) {
    }

    /**
     * Check if packages were installed without --no-dev flag
     *
     * @return boolean
     */
    public function isDevInstalled()
    {
        $file = new JsonFile($this->getRootDir() . '/vendor/composer/installed.json');

        if (!$file->exists()) {
            return true;
        }

        return $file->read()['dev'] ?? true;
    }

    /**
     * Root requirements from composer.json
     *
     * @return array Package name => version constraint
     */
    public function getRequirements()
    {
        $file = new JsonFile($this->getRootDir() . '/composer.json');
        $json = $file->exists() ? $file->read() : [];

        return array_merge($json['require'] ?? [], $json['require-dev'] ?? []);
    }

    /**
     * @return array Path => content (null if file does not exist)
     */
    public function backupFiles()
    {
        $backup = [];

        foreach (['composer.json', 'composer.lock'] as $filename) {
            $path = $this->getRootDir() . '/' . $filename;
            $backup[$path] = is_file($path) ? file_get_contents($path) : null;
        }

        return $backup;
    }

    /**
     * @param array $backup Result of backupFiles()
     * @return void
     * @throws \RuntimeException
     */
    public function restoreFiles(array $backup)
    {
        foreach ($backup as $path => $content) {
            if ($content === null) {
                if (is_file($path) && !unlink($path)) {
                    throw new \RuntimeException(sprintf('Unable to delete %s file', $path));
                }
            } elseif (file_put_contents($path, $content) === false) {
                throw new \RuntimeException(sprintf('Unable to restore %s file', $path));
            }
        }
    }

    /**
     * @param array $args
     * @param OutputInterface $output
     * @param boolean $interactive
     * @return int Exit code
     * @throws \RuntimeException
     */
    public function run(array $args, OutputInterface $output, $interactive = true)
    {
        if (!$this->process->canRun()) {
            throw new \RuntimeException(sprintf(
                'proc_open is disabled. Run the command manually: composer %s',
                implode(' ', $args)
            ));
        }

        return $this->process->run($this->getCommand($args), $output, $interactive, $this->getEnv());
    }

    /**
     * Run non-interactive composer command and return its stdout
     *
     * @param array $args
     * @return string
     * @throws \RuntimeException
     */
    public function capture(array $args)
    {
        return $this->process->capture($this->getCommand($args), $this->getEnv());
    }

    /**
     * @return boolean
     */
    public function hasAuthJson()
    {
        return is_file($this->getRootDir() . '/auth.json');
    }

    /**
     * @param array $args
     * @return array
     */
    private function getCommand(array $args)
    {
        $args[] = '--working-dir=' . $this->getRootDir();

        return array_merge([$this->getComposerBin()], $args);
    }

    /**
     * @return array
     */
    private function getEnv()
    {
        return ['COMPOSER_HOME' => false]; // unset Magento's var/composer_home
    }

    /**
     * @return string
     */
    private function getComposerBin()
    {
        // vendor/composer/composer/src/Composer/Composer.php
        $file = (new \ReflectionClass(\Composer\Composer::class))->getFileName();

        return dirname($file, 3) . '/bin/composer';
    }

    /**
     * @return string
     */
    private function getRootDir()
    {
        return dirname($this->composerJsonFinder->findComposerJson());
    }
}
