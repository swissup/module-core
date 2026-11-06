<?php

declare(strict_types=1);

namespace Swissup\Core\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Swissup\Core\Model\PageSpeed\CheckInterface;
use Swissup\Core\Model\PageSpeedStatus;

class PageSpeed extends \Magento\Backend\Block\Template
{
    protected $_template = 'Swissup_Core::pagespeed.phtml';

    public function __construct(
        Context $context,
        private PageSpeedStatus $pageSpeedStatus,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return array[]
     */
    public function getResults(): array
    {
        return $this->pageSpeedStatus->run();
    }

    public function getIssuesCount(): int
    {
        return $this->pageSpeedStatus->getIssuesCount($this->getResults());
    }

    public function hasTests(): bool
    {
        foreach ($this->getResults() as $result) {
            if ($result['type'] === CheckInterface::TYPE_TEST) {
                return true;
            }
        }

        return false;
    }

    public function getPageSpeedInsightsUrl(): string
    {
        return 'https://pagespeed.web.dev/';
    }

    public function hasCommands(): bool
    {
        foreach ($this->getResults() as $result) {
            if (!$result['passed'] && $result['commands']) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $result
     * @return array Severity css class and label
     */
    public function getStatus(array $result): array
    {
        if ($result['passed']) {
            return ['class' => 'grid-severity-notice', 'label' => __('Optimal')];
        }

        switch ($result['type']) {
            case CheckInterface::TYPE_TEST:
                return ['class' => 'grid-severity-minor', 'label' => __('Test It')];
            case CheckInterface::TYPE_SUGGESTION:
                return ['class' => 'grid-severity-minor', 'label' => __('Suggestion')];
        }

        return ['class' => 'grid-severity-critical', 'label' => __('Not Optimal')];
    }
}
