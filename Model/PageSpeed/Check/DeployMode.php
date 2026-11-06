<?php

declare(strict_types=1);

namespace Swissup\Core\Model\PageSpeed\Check;

use Magento\Framework\App\State;
use Swissup\Core\Model\PageSpeed\CheckInterface;

class DeployMode implements CheckInterface
{
    public function __construct(
        private State $appState
    ) {
    }

    public function run(): ?array
    {
        $mode = $this->appState->getMode();
        $passed = $mode === State::MODE_PRODUCTION;

        return [
            'title' => (string) __('Application Mode'),
            'description' => (string) __(
                'Developer and default modes generate code and static files on the fly and skip many optimizations. Production mode is required for a fast store.'
            ),
            'passed' => $passed,
            'type' => self::TYPE_REQUIRED,
            'current' => ucfirst($mode),
            'recommended' => ucfirst(State::MODE_PRODUCTION),
            'scope' => [],
            'commands' => $passed ? [] : ['bin/magento deploy:mode:set production'],
            'note' => $passed ? '' : (string) __(
                'The command compiles the code and deploys static content, it may take a few minutes.'
            ),
            'url' => '',
        ];
    }
}
