<?php
/**
 * This file is part of Rapid Cache
 *
 * @license GPLv3 (See LICENSE.txt for more details)
 * @copyright 2020 MegaOptim (https://megaoptim.com)
 * @copyright 2016 WP Sharks (https://wpsharks.com/)
 */

namespace MegaOptim\RapidCache\Classes;

/**
 * Actions.
 *
 * @since 1.0.0
 */
class Actions extends AbsBase
{
    /**
     * Allowed actions.
     *
     * @since 1.0.0
     */
    protected $allowed_actions = [
        'wipeCache',
        'clearCache',
        'ajaxWipeCache',
        'ajaxClearCache',
        'saveOptions',
        'restoreDefaultOptions',
        'dismissNotice',
    ];

    /**
     * Class constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        parent::__construct();
        if (empty($_REQUEST[MEGAOPTIM_RAPID_CACHE_GLOBAL_NS])) {
            return; // Not applicable.
        }
        foreach ((array) $_REQUEST[MEGAOPTIM_RAPID_CACHE_GLOBAL_NS] as $_action => $_args) {
            if (is_string($_action) && method_exists($this, $_action)) {
                if (in_array($_action, $this->allowed_actions, true)) {
                    $this->{$_action}($_args); // Do action!
                }
            }
        }
        unset($_action, $_args); // Housekeeping.
    }

    /**
     * Action handler.
     *
     * @param  mixed  $args
     *
     * @throws \Exception
     * @since 1.0.0
     *
     */
    protected function wipeCache($args)
    {
        if ( ! is_multisite() || ! $this->plugin->currentUserCanWipeCache()) {
            return; // Nothing to do.
        } elseif (empty($_REQUEST['_wpnonce']) || ! wp_verify_nonce($_REQUEST['_wpnonce'])) {
            return; // Unauthenticated POST data.
        }
        $counter     = $this->plugin->wipeCache(true);
        $redirect_to = self_admin_url('/admin.php');
        $query_args  = [
            'page'                                          => MEGAOPTIM_RAPID_CACHE_GLOBAL_NS,
            MEGAOPTIM_RAPID_CACHE_GLOBAL_NS.'_cache_wiped' => '1'
        ];
        $redirect_to = add_query_arg(urlencode_deep($query_args), $redirect_to);
        wp_redirect($redirect_to);
        exit();
    }

    /**
     * Action handler.
     *
     * @param  mixed  $args
     *
     * @throws \Exception
     * @since 1.0.0
     */
    protected function clearCache($args)
    {
        if ( ! $this->plugin->currentUserCanClearCache()) {
            return; // Not allowed to clear.
        } elseif (empty($_REQUEST['_wpnonce']) || ! wp_verify_nonce($_REQUEST['_wpnonce'])) {
            return; // Unauthenticated POST data.
        }
        $counter = $this->plugin->clearCache(true);

        $redirect_to = self_admin_url('/admin.php'); // Redirect preparations.
        $query_args  = ['page'                                            => MEGAOPTIM_RAPID_CACHE_GLOBAL_NS,
                        MEGAOPTIM_RAPID_CACHE_GLOBAL_NS.'_cache_cleared' => '1'
        ];
        $redirect_to = add_query_arg(urlencode_deep($query_args), $redirect_to);

        wp_redirect($redirect_to);
        exit();
    }

    /**
     * Action handler.
     *
     * @param  mixed  $args
     *
     * @throws \Exception
     * @since 1.0.0
     */
    protected function ajaxWipeCache($args)
    {
        if ( ! is_multisite() || ! $this->plugin->currentUserCanWipeCache()) {
            return; // Nothing to do.
        } elseif (empty($_REQUEST['_wpnonce']) || ! wp_verify_nonce($_REQUEST['_wpnonce'])) {
            return; // Unauthenticated POST data.
        }
        $counter = $this->plugin->wipeCache(true);


        $response = sprintf(__('<p>Wiped a total of <code>%2$s</code> cache files.</p>', 'rapid-cache'),
            esc_html(MEGAOPTIM_RAPID_CACHE_NAME), esc_html($counter));
        $response .= __('<p>Cache wiped for all sites. Re-creation will occur automatically over time.</p>',
            'rapid-cache');


        exit($response); // JavaScript will take it from here.
    }

    /**
     * Action handler.
     *
     * @param  mixed  $args
     *
     * @throws \Exception
     * @since 1.0.0
     */
    protected function ajaxClearCache($args)
    {
        if ( ! $this->plugin->currentUserCanClearCache()) {
            return; // Not allowed to clear.
        } elseif (empty($_REQUEST['_wpnonce']) || ! wp_verify_nonce($_REQUEST['_wpnonce'])) {
            return; // Unauthenticated POST data.
        }
        $counter = $this->plugin->clearCache(true);

        $response = sprintf(__('<p>Cleared a total of <code>%2$s</code> cache files.</p>', 'rapid-cache'),
            esc_html(MEGAOPTIM_RAPID_CACHE_NAME), esc_html($counter));

        if (is_multisite() && is_main_site()) {
            $response .= __('<p>Cache cleared for main site. Re-creation will occur automatically over time.</p>',
                'rapid-cache');
        } else {
            $response .= __('<p>Cache cleared for this site. Re-creation will occur automatically over time.</p>',
                'rapid-cache');
        }

        exit($response); // JavaScript will take it from here.
    }


    /**
     * Action handler.
     *
     * @param  mixed  $args
     *
     * @since 1.0.0
     *
     */
    protected function saveOptions($args)
    {
        if ( ! current_user_can($this->plugin->cap)) {
            return; // Nothing to do.
        } elseif (empty($_REQUEST['_wpnonce']) || ! wp_verify_nonce($_REQUEST['_wpnonce'])) {
            return; // Unauthenticated POST data.
        }
        if ( ! empty($_FILES[MEGAOPTIM_RAPID_CACHE_GLOBAL_NS]['tmp_name']['import_options'])) {
            $import_file_contents = file_get_contents($_FILES[MEGAOPTIM_RAPID_CACHE_GLOBAL_NS]['tmp_name']['import_options']);
            unlink($_FILES[MEGAOPTIM_RAPID_CACHE_GLOBAL_NS]['tmp_name']['import_options']); // Deleted uploaded file.

            $args = wp_slash(json_decode($import_file_contents, true));

            unset($args['crons_setup']); // CANNOT be imported!
            unset($args['last_pro_update_check']); // CANNOT be imported!
            unset($args['last_pro_stats_log']); // CANNOT be imported!
        }


        $args = $this->plugin->trimDeep(stripslashes_deep((array) $args));
        $this->plugin->updateOptions($args); // Save/update options.

        // Ensures `autoCacheMaybeClearPrimaryXmlSitemapError()` always validates the XML Sitemap when saving options (when applicable).
        delete_transient(MEGAOPTIM_RAPID_CACHE_GLOBAL_NS.'-'.md5($this->plugin->options['auto_cache_sitemap_url']));

        $redirect_to = self_admin_url('/admin.php'); // Redirect preparations.
        $query_args  = ['page' => MEGAOPTIM_RAPID_CACHE_GLOBAL_NS, MEGAOPTIM_RAPID_CACHE_GLOBAL_NS.'_updated' => '1'];

        $this->plugin->autoWipeCache(); // May produce a notice.

        if ($this->plugin->options['enable']) {
            if ( ! ($add_wp_cache_to_wp_config = $this->plugin->addWpCacheToWpConfig())) {
                $query_args[MEGAOPTIM_RAPID_CACHE_GLOBAL_NS.'_wp_config_wp_cache_add_failure'] = '1';
            }
            if ($this->plugin->isApache() && ! ($add_wp_htaccess = $this->plugin->addWpHtaccess())) {
                $query_args[MEGAOPTIM_RAPID_CACHE_GLOBAL_NS.'_wp_htaccess_add_failure'] = '1';
            }
            if ($this->plugin->isNginx() && $this->plugin->applyWpFilters(MEGAOPTIM_RAPID_CACHE_GLOBAL_NS.'_wp_htaccess_nginx_notice',
                    true)
                && ( ! isset($_SERVER['WP_NGINX_CONFIG']) || $_SERVER['WP_NGINX_CONFIG'] !== 'done')) {
                $query_args[MEGAOPTIM_RAPID_CACHE_GLOBAL_NS.'_wp_htaccess_nginx_notice'] = '1';
            }
            if ( ! ($add_advanced_cache = $this->plugin->addAdvancedCache())) {
                $query_args[MEGAOPTIM_RAPID_CACHE_GLOBAL_NS.'_advanced_cache_add_failure'] = $add_advanced_cache === null ? 'advanced-cache' : '1';
            }

            if ( ! $this->plugin->options['auto_cache_enable']) {
                // Dismiss and check again on `admin_init` via `autoCacheMaybeClearPhpReqsError()`.
                $this->plugin->dismissMainNotice('auto_cache_engine_minimum_requirements');
            }
            if ( ! $this->plugin->options['auto_cache_enable'] || ! $this->plugin->options['auto_cache_sitemap_url']) {
                // Dismiss and check again on `admin_init` via `autoCacheMaybeClearPrimaryXmlSitemapError()`.
                $this->plugin->dismissMainNotice('xml_sitemap_missing');
            }
            $this->plugin->updateBlogPaths(); // Multisite networks only.
        } else {
            if ( ! ($remove_wp_cache_from_wp_config = $this->plugin->removeWpCacheFromWpConfig())) {
                $query_args[MEGAOPTIM_RAPID_CACHE_GLOBAL_NS.'_wp_config_wp_cache_remove_failure'] = '1';
            }
            if ($this->plugin->isApache() && ! ($remove_wp_htaccess = $this->plugin->removeWpHtaccess())) {
                $query_args[MEGAOPTIM_RAPID_CACHE_GLOBAL_NS.'_wp_htaccess_remove_failure'] = '1';
            }
            if ( ! ($remove_advanced_cache = $this->plugin->removeAdvancedCache())) {
                $query_args[MEGAOPTIM_RAPID_CACHE_GLOBAL_NS.'_advanced_cache_remove_failure'] = '1';
            }
            // Dismiss notice when disabling plugin.
            $this->plugin->dismissMainNotice('xml_sitemap_missing');

            // Dismiss notice when disabling plugin.
            $this->plugin->dismissMainNotice('auto_cache_engine_minimum_requirements');
        }
        $redirect_to = add_query_arg(urlencode_deep($query_args), $redirect_to);

        wp_redirect($redirect_to);
        exit();
    }

    /**
     * Action handler.
     *
     * @param  mixed  $args
     *
     * @since 1.0.0
     *
     */
    protected function restoreDefaultOptions($args)
    {
        if ( ! current_user_can($this->plugin->cap)) {
            return; // Nothing to do.
        } elseif (is_multisite() && ! current_user_can($this->plugin->network_cap)) {
            return; // Nothing to do.
        } elseif (empty($_REQUEST['_wpnonce']) || ! wp_verify_nonce($_REQUEST['_wpnonce'])) {
            return; // Unauthenticated POST data.
        }
        $this->plugin->restoreDefaultOptions(); // Restore defaults.

        $redirect_to = self_admin_url('/admin.php'); // Redirect prep.
        $query_args  = [
            'page'                                       => MEGAOPTIM_RAPID_CACHE_GLOBAL_NS,
            MEGAOPTIM_RAPID_CACHE_GLOBAL_NS.'_restored' => '1'
        ];

        $this->plugin->autoWipeCache(); // May produce a notice.

        if ($this->plugin->options['enable']) {
            if ( ! ($add_wp_cache_to_wp_config = $this->plugin->addWpCacheToWpConfig())) {
                $query_args[MEGAOPTIM_RAPID_CACHE_GLOBAL_NS.'_wp_config_wp_cache_add_failure'] = '1';
            }
            if ($this->plugin->isApache() && ! ($add_wp_htaccess = $this->plugin->addWpHtaccess())) {
                $query_args[MEGAOPTIM_RAPID_CACHE_GLOBAL_NS.'_wp_htaccess_add_failure'] = '1';
            }
            if ($this->plugin->isNginx() && $this->plugin->applyWpFilters(MEGAOPTIM_RAPID_CACHE_GLOBAL_NS.'_wp_htaccess_nginx_notice',
                    true)
                && ( ! isset($_SERVER['WP_NGINX_CONFIG']) || $_SERVER['WP_NGINX_CONFIG'] !== 'done')) {
                $query_args[MEGAOPTIM_RAPID_CACHE_GLOBAL_NS.'_wp_htaccess_nginx_notice'] = '1';
            }
            if ( ! ($add_advanced_cache = $this->plugin->addAdvancedCache())) {
                $query_args[MEGAOPTIM_RAPID_CACHE_GLOBAL_NS.'_advanced_cache_add_failure'] = $add_advanced_cache === null ? 'advanced-cache' : '1';
            }

            if ( ! $this->plugin->options['auto_cache_enable']) {
                // Dismiss and check again on `admin_init` via `autoCacheMaybeClearPhpReqsError()`.
                $this->plugin->dismissMainNotice('auto_cache_engine_minimum_requirements');
            }
            if ( ! $this->plugin->options['auto_cache_enable'] || ! $this->plugin->options['auto_cache_sitemap_url']) {
                // Dismiss and check again on `admin_init` via `autoCacheMaybeClearPrimaryXmlSitemapError()`.
                $this->plugin->dismissMainNotice('xml_sitemap_missing');
            }
            $this->plugin->updateBlogPaths(); // Multisite networks only.
        } else {
            if ( ! ($remove_wp_cache_from_wp_config = $this->plugin->removeWpCacheFromWpConfig())) {
                $query_args[MEGAOPTIM_RAPID_CACHE_GLOBAL_NS.'_wp_config_wp_cache_remove_failure'] = '1';
            }
            if ($this->plugin->isApache() && ! ($remove_wp_htaccess = $this->plugin->removeWpHtaccess())) {
                $query_args[MEGAOPTIM_RAPID_CACHE_GLOBAL_NS.'_wp_htaccess_remove_failure'] = '1';
            }
            if ( ! ($remove_advanced_cache = $this->plugin->removeAdvancedCache())) {
                $query_args[MEGAOPTIM_RAPID_CACHE_GLOBAL_NS.'_advanced_cache_remove_failure'] = '1';
            }
            // Dismiss notice when disabling plugin.
            $this->plugin->dismissMainNotice('xml_sitemap_missing');

            // Dismiss notice when disabling plugin.
            $this->plugin->dismissMainNotice('auto_cache_engine_minimum_requirements');
        }
        $redirect_to = add_query_arg(urlencode_deep($query_args), $redirect_to);

        wp_redirect($redirect_to);
        exit();
    }

    /**
     * Action handler.
     *
     * @param  mixed  $args
     *
     * @since 1.0.0
     *
     */
    protected function dismissNotice($args)
    {
        if ( ! current_user_can($this->plugin->cap)) {
            return; // Nothing to do.
        } elseif (empty($_REQUEST['_wpnonce']) || ! wp_verify_nonce($_REQUEST['_wpnonce'])) {
            return; // Unauthenticated POST data.
        }
        $args = $this->plugin->trimDeep(stripslashes_deep((array) $args));
        $this->plugin->dismissNotice($args['key']);

        wp_redirect(remove_query_arg(MEGAOPTIM_RAPID_CACHE_GLOBAL_NS));
        exit();
    }
}
