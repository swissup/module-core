<?php

namespace Swissup\Core\Model;

use Psr\Log\LoggerInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class PatchStatus
{
    const STORAGE_KEY = 'patch_status';
    const CHECK_INTERVAL = 3600;
    const TOOL_PATH = 'vendor/bin/patch-status';

    private FileStorage $storage;

    private LoggerInterface $logger;

    private ?array $result = null;

    public function __construct(FileStorage $storage, LoggerInterface $logger)
    {
        $this->storage = $storage;
        $this->logger = $logger;
    }

    /**
     * The stored result, or a new one when the check is due
     */
    public function getResult(): array
    {
        if (!$this->isCheckRequired() && $stored = $this->getStored()) {
            return $stored;
        }

        return $this->run();
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

        // A request killed by the server timeout saves nothing, and the check
        // would restart on every page load. The previous result, marked as
        // checked, makes it wait for the next interval instead.
        $this->save(['checked_at' => $this->result['checked_at']] + ($this->getStored() ?: $this->result));

        try {
            $this->result['patches'] = $this->execute();
        } catch (\DomainException $e) {
            // the tool can't check git installations or run on restricted hosts
            $this->result['error'] = $e->getMessage();
            $this->result['unsupported'] = true;
        } catch (\Exception $e) {
            $this->result['error'] = $e->getMessage();
        }

        $this->save($this->result);

        return $this->result;
    }

    /**
     * An unwritable var folder shouldn't break the admin pages
     */
    private function save(array $data): void
    {
        try {
            $this->storage->save(json_encode($data), self::STORAGE_KEY);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }

    /**
     * @return array|null
     */
    public function getStored()
    {
        $data = json_decode((string) $this->storage->load(self::STORAGE_KEY), true);

        // a broken file shouldn't break the badge and the grid
        if (!is_array($data)
            || !isset($data['checked_at'], $data['patches'])
            || !is_int($data['checked_at'])
            || !is_array($data['patches'])
            || !array_key_exists('error', $data)
            || !(is_string($data['error']) || $data['error'] === null)
        ) {
            return null;
        }

        $data['patches'] = array_filter($data['patches'], 'is_string');

        return $data;
    }

    public function isCheckRequired(): bool
    {
        $data = $this->getStored();

        if (!$data || $data['checked_at'] < time() - self::CHECK_INTERVAL) {
            return true;
        }

        // the tool is updated along with the patches
        return $this->isToolInstalled()
            && filemtime(BP . '/' . self::TOOL_PATH) >= $data['checked_at'];
    }

    public function isToolInstalled(): bool
    {
        return is_file(BP . '/' . self::TOOL_PATH);
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
     * @throws \DomainException when the installation can't be checked
     */
    private function execute()
    {
        $tool = BP . '/' . self::TOOL_PATH;

        if (!$this->isToolInstalled()) {
            throw new \RuntimeException(
                (string) __('%1 tool is not installed.', self::TOOL_PATH)
            );
        }

        // a hosting restriction, the badge shouldn't nag about what can't be fixed
        if (!function_exists('proc_open')) {
            throw new \DomainException((string) __(
                '%1 tool can\'t run: the proc_open function is disabled on this server.',
                self::TOOL_PATH
            ));
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
