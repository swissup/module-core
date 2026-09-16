<?php

namespace Swissup\Core\Model\Installer;

use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process as SymfonyProcess;

/**
 * Runs symfony console based php script (composer, bin/magento) in a child process
 */
class Process
{
    /**
     * @return boolean
     */
    public function canRun()
    {
        return function_exists('proc_open');
    }

    /**
     * @param array $command Script path and its arguments
     * @param OutputInterface $output
     * @param boolean $interactive
     * @param array $env
     * @return int Exit code
     * @throws \RuntimeException
     */
    public function run(array $command, OutputInterface $output, $interactive = true, array $env = [])
    {
        if (!$this->canRun()) {
            throw new \RuntimeException('proc_open is disabled');
        }

        $command[] = $output->isDecorated() ? '--ansi' : '--no-ansi';

        if (!$interactive) {
            $command[] = '--no-interaction';
        }

        if ($output->isQuiet()) {
            $command[] = '--quiet';
        } elseif ($output->isDebug()) {
            $command[] = '-vvv';
        } elseif ($output->isVeryVerbose()) {
            $command[] = '-vv';
        } elseif ($output->isVerbose()) {
            $command[] = '-v';
        }

        array_unshift($command, PHP_BINARY);

        $process = new SymfonyProcess($command, BP, $env);
        $process->setTimeout(null);

        if ($interactive && SymfonyProcess::isTtySupported()) {
            $process->setTty(true);
            return $process->run();
        }

        $errorOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        return $process->run(function ($type, $buffer) use ($output, $errorOutput) {
            $stream = $type === SymfonyProcess::ERR ? $errorOutput : $output;
            $stream->write($buffer, false, OutputInterface::OUTPUT_RAW);
        });
    }

    /**
     * Run non-interactive command and return its stdout
     *
     * @param array $command Script path and its arguments
     * @param array $env
     * @return string
     * @throws \RuntimeException
     */
    public function capture(array $command, array $env = [])
    {
        if (!$this->canRun()) {
            throw new \RuntimeException('proc_open is disabled');
        }

        array_unshift($command, PHP_BINARY);
        $command[] = '--no-ansi';
        $command[] = '--no-interaction';

        $process = new SymfonyProcess($command, BP, $env);
        $process->setTimeout(null);
        $process->run();

        if (!$process->isSuccessful()) {
            $error = preg_replace('/\s+/', ' ', trim($process->getErrorOutput()));
            throw new \RuntimeException($error ?: sprintf('Command failed with exit code %s', $process->getExitCode()));
        }

        return $process->getOutput();
    }
}
