<?php
/**
 * This file is part of Rapid Cache
 *
 * @license GPLv3 (See LICENSE.txt for more details)
 * @copyright 2020 MegaOptim (https://megaoptim.com)
 * @copyright 2016 WP Sharks (https://wpsharks.com/)
 */
namespace MegaOptim\RapidCache\Traits\Plugin;

use MegaOptim\RapidCache\Classes;

trait WcpSettingUtils
{
    /**
     * Automatically clears all cache files for current blog under various conditions;
     *    used to check for conditions that don't have a hook that we can attach to.
     *
     * @since 1.0.0
     *
     * @attaches-to `admin_init` hook.
     */
    public function autoClearCacheOnSettingChanges()
    {
        $counter           = 0; // Initialize.
        $pagenow           = !empty($GLOBALS['pagenow']) ? $GLOBALS['pagenow'] : '';
        $other_option_page = !empty($_REQUEST['page']);
        $settings_updated  = !empty($_REQUEST['settings-updated']);

        if (!is_null($done = &$this->cacheKey('autoClearCacheOnSettingChanges', [$pagenow, $other_option_page, $settings_updated]))) {
            return $counter; // Already did this.
        }
        $done = true; // Flag as having been done.

        if ($pagenow === 'options-general.php' && $other_option_page) {
            return $counter; // Nothing to do. See: https://git.io/viYqE
        }

        if ($pagenow === 'options-general.php' && $settings_updated) {
            $this->addWpHtaccess(); // Update .htaccess if applicable
            $counter += $this->autoClearCache();
        } elseif ($pagenow === 'options-reading.php' && $settings_updated) {
            $counter += $this->autoClearCache();
        } elseif ($pagenow === 'options-discussion.php' && $settings_updated) {
            $counter += $this->autoClearCache();
        } elseif ($pagenow === 'options-permalink.php' && $settings_updated) {
            $this->addWpHtaccess(); // Update .htaccess if applicable
            $counter += $this->autoClearCache();
        }
    }
}
