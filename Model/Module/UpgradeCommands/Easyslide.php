<?php

namespace Swissup\Core\Model\Module\UpgradeCommands;

class Easyslide extends \Swissup\Core\Model\Module\UpgradeCommands\AbstractCommand
{
    /**
     * Create new slider.
     * If duplicate is found - do nothing.
     *
     * @param  array $data Array of slider data arrays
     * <pre>
     * [
     *     [
     *         identifier
     *         title
     *         slider_config (serialized array)
     *         is_active
     *         slides => [
     *             [
     *                 image
     *                 title
     *                 description
     *                 sort_order
     *             ]
     *         ]
     *     ]
     * ]
     * </pre>
     * @return void
     */
    public function execute($data)
    {
        foreach ($data as $itemData) {
            $slider = $this->objectManager
                ->create('Swissup\EasySlide\Model\Slider')
                ->load($itemData['identifier'], 'identifier');
            if ($slider->getId()) {
                continue;
            }

            try {
                $slider->setData($itemData)->save();
            } catch (\Exception $e) {
                $this->fault('easyslide_slider_save', $e);
                continue;
            }

            $slideDefaults = array(
                'is_active'   => 1,
                'target'      => '_self',
                'description' => '',
                'slider_id'   => $slider->getId()
            );
            foreach ($itemData['slides'] as $slide) {
                try {
                    $this->objectManager
                        ->create('Swissup\EasySlide\Model\Slides')
                        ->setData(array_merge($slideDefaults, $slide))
                        ->save();
                } catch (\Exception $e) {
                    $this->fault('easyslide_slide_save', $e);
                    continue;
                }
            }
        }
    }
}
