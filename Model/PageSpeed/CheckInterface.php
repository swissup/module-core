<?php

declare(strict_types=1);

namespace Swissup\Core\Model\PageSpeed;

/**
 * A store setting that affects the storefront speed.
 *
 * Checks are added to Swissup\Core\Model\PageSpeed\CheckPool with di.xml, so
 * other modules can contribute their own recommendations.
 */
interface CheckInterface
{
    /**
     * The recommended value is always better, the menu badge shows the issue
     */
    public const TYPE_REQUIRED = 'required';

    /**
     * The recommended value is better for most stores, but the result depends
     * on the theme and environment, so it should be measured after switching
     */
    public const TYPE_TEST = 'test';

    /**
     * The recommendation requires server changes
     */
    public const TYPE_SUGGESTION = 'suggestion';

    /**
     * Compare the current setting with the recommended one.
     *
     * @return array|null Null when the check doesn't apply to the store, or:
     *  - title:       string   Setting name
     *  - description: string   Why the setting matters
     *  - type:        string   One of TYPE_* constants
     *  - passed:      bool     Whether the setting has the recommended value
     *  - current:     string   Current value
     *  - recommended: string   Recommended value
     *  - scope:       string[] Store views with other value (empty when all or global)
     *  - commands:    string[] CLI commands that apply the recommended value
     *  - note:        string   Additional instructions (optional)
     *  - url:         string   Documentation link (optional)
     */
    public function run(): ?array;
}
