<?php

namespace Swissup\Core\Model;

use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class PatchStatus
{
    const STORAGE_KEY = 'patch_status';
    const CHECK_INTERVAL = 3600;
    const TOOL_PATH = 'vendor/bin/patch-status';

    private FileStorage $storage;

    private ?array $result = null;

    public function __construct(FileStorage $storage)
    {
        $this->storage = $storage;
    }

    public function run(): array
    {
        if ($this->result !== null) {
            return $this->result;
        }

        // Prevent parallel runs caused by multiple tabs and admin accounts. The
        // lock is held by $lock variable, and released once this method returns.
        $lock = $this->storage->lock(self::STORAGE_KEY);

        if (!$lock) {
            return $this->result = $this->getStored() ?: [
                'checked_at' => time(),
                'error' => (string) __('The check is already running, please reload the page in a minute.'),
                'unsupported' => false,
                'running' => true,
                'patches' => [],
            ];
        }

        $this->result = [
            'checked_at' => time(),
            'error' => null,
            'unsupported' => false,
            'patches' => [],
        ];

        try {
            $this->result['patches'] = $this->execute();
        } catch (\DomainException $e) {
            // git installations are not recognized by the tool
            $this->result['error'] = $e->getMessage();
            $this->result['unsupported'] = true;
        } catch (\Exception $e) {
            $this->result['error'] = $e->getMessage();
        }

        $this->storage->save(json_encode($this->result), self::STORAGE_KEY);

        return $this->result;
    }

    /**
     * @return array|null
     */
    public function getStored()
    {
        $data = json_decode((string) $this->storage->load(self::STORAGE_KEY), true);

        return is_array($data) ? $data : null;
    }

    public function isCheckRequired(): bool
    {
        $data = $this->getStored();

        if (!$data || $data['checked_at'] < time() - self::CHECK_INTERVAL) {
            return true;
        }

        // the tool is updated along with the patches
        $tool = BP . '/' . self::TOOL_PATH;

        return is_file($tool) && filemtime($tool) >= $data['checked_at'];
    }

    public function getCount($data): int
    {
        if (!$data || !empty($data['unsupported']) || !empty($data['running'])) {
            return 0;
        }

        if ($data['error']) {
            return 1;
        }

        return count(array_keys($data['patches'], 'missing', true));
    }

    /**
     * @return array Patch statuses keyed by patch id: not applied first
     * @throws \RuntimeException
     * @throws \DomainException when the installation is not recognized
     */
    private function execute()
    {
        $tool = BP . '/' . self::TOOL_PATH;

        if (!is_file($tool)) {
            throw new \RuntimeException(
                (string) __('%1 tool is not installed.', self::TOOL_PATH)
            );
        }

        $process = new Process([$this->getPhpBinary(), $tool, '--root=' . BP, '--format=json'], BP);
        $process->setTimeout(120);
        $process->run();

        $data = json_decode($process->getOutput(), true);

        if (!is_array($data)) {
            $output = trim($process->getErrorOutput() ?: $process->getOutput());

            throw new \RuntimeException((string) __(
                '%1 tool failed to run: %2',
                self::TOOL_PATH,
                $output ?: __('exit code %1', $process->getExitCode())
            ));
        }

        if (($data['base_version'] ?? 'unknown') === 'unknown') {
            throw new \DomainException((string) __(
                '%1 tool failed to check the installation: %2',
                self::TOOL_PATH,
                implode(' ', $data['warnings'] ?? [])
            ));
        }

        return array_fill_keys($data['missing_patches'] ?? [], 'missing')
            + array_fill_keys($data['unknown_patches'] ?? [], 'unknown')
            + array_fill_keys($data['applied_patches'] ?? [], 'applied');
    }

    private function getPhpBinary()
    {
        $php = PHP_BINDIR . '/php' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;

        try {
            $isExecutable = PHP_SAPI !== 'cli' && is_executable($php);
        } catch (\Exception $e) {
            // open_basedir restriction warning, turned into exception by magento
            $isExecutable = false;
        }

        return $isExecutable ? $php : ((new PhpExecutableFinder())->find(false) ?: 'php');
    }
}
