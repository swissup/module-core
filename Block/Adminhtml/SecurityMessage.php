<?php

namespace Swissup\Core\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Swissup\Core\Model\PatchStatus;

class SecurityMessage extends \Magento\Backend\Block\Template
{
    protected $_template = 'Swissup_Core::security-message.phtml';

    private PatchStatus $patchStatus;

    public function __construct(
        Context $context,
        PatchStatus $patchStatus,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->patchStatus = $patchStatus;
    }

    /**
     * @return array|null Message text with an optional link to the docs
     */
    public function getMessage()
    {
        $url = 'https://swissuplabs.com/magento-2-security-patch-monitor';
        $result = $this->patchStatus->run();

        if ($result['error']) {
            return $this->patchStatus->isToolInstalled() ? ['text' => $result['error']] : [
                'text' => $result['error'],
                'url' => $url . '#install',
                'link' => __('How to install the patch-status tool'),
            ];
        }

        if (array_diff($result['patches'], ['applied'])) {
            return [
                'text' => __('Your store is missing one or more Adobe security patches. We recommend applying the updates as soon as possible.'),
                'url' => $url . '#find',
                'link' => __('How to install the patches'),
            ];
        }

        return null;
    }
}
