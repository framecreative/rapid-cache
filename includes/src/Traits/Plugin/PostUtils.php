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

trait PostUtils
{
    /**
     * All post statuses.
     *
     * @since 1.0.0
     *
     * @return array All post statuses.
     */
    public function postStatuses()
    {
        if (!is_null($statuses = &$this->cacheKey('postStatuses'))) {
            return $statuses; // Already did this.
        }
        $statuses = get_post_stati();
        $statuses = array_keys($statuses);

        return $statuses;
    }

    /**
     * All built-in post statuses.
     *
     * @since 1.0.0
     *
     * @return array All built-in post statuses.
     */
    public function builtInPostStatuses()
    {
        if (!is_null($statuses = &$this->cacheKey('builtInPostStatuses'))) {
            return $statuses; // Already did this.
        }
        $statuses = []; // Initialize.

        foreach (get_post_stati([], 'objects') as $_key => $_status) {
            if (!empty($_status->_builtin)) {
                $statuses[] = $_status->name;
            }
        }
        unset($_key, $_status); // Housekeeping.

        return $statuses;
    }
}
