<?php

namespace Swissup\Core\Plugin;

class PageConfigRenderer
{
    /**
     * Add font preload support in Magento < 2.3.3.
     *
     * Usage in `default_head_blocks.xml`:
     *
     *     <link rel="preload" src="fonts/muli.woff2"/>
     *
     * @param Magento\Framework\View\Page\Config\Renderer $subject
     * @param string $result
     * @return string
     */
    public function afterRenderAssets(
        \Magento\Framework\View\Page\Config\Renderer $subject,
        $result
    ) {
        if (strpos($result, 'rel="preload"') === false) {
            return $result;
        }

        preg_match_all('/<link.*rel="preload".*\/>/U', $result, $links);

        foreach ($links[0] as $link) {
            if (strpos($link, '.woff') === false ||
                strpos($link, 'crossorigin="anonymous"') !== false
            ) {
                continue;
            }

            $newLink = str_replace(
                'rel="preload"',
                'rel="preload" as="font" crossorigin="anonymous"',
                $link
            );
            $result = str_replace($link, $newLink, $result);
        }

        return $result;
    }
}
