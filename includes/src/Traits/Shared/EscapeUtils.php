<?php
/**
 * This file is part of Rapid Cache
 *
 * @license GPLv3 (See LICENSE.txt for more details)
 * @copyright 2020 MegaOptim (https://megaoptim.com)
 * @copyright 2016 WP Sharks (https://wpsharks.com/)
 */
namespace MegaOptim\RapidCache\Traits\Shared;

use MegaOptim\RapidCache\Classes;

trait EscapeUtils
{
    /**
     * Escape single quotes.
     *
     * @since 1.0.0
     *
     * @param string $string Input string to escape.
     * @param int    $times  Optional. Defaults to one escape char; e.g. `\'`.
     *                       If you need to escape more than once, set this to something > `1`.
     *
     * @return string Escaped string; e.g. `Raam\'s the lead developer`.
     */
    public function escSq($string, $times = 1)
    {
        return str_replace("'", str_repeat('\\', abs($times))."'", (string) $string);
    }
}
