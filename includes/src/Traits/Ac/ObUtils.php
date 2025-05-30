<?php
/**
 * This file is part of Rapid Cache
 *
 * @license GPLv3 (See LICENSE.txt for more details)
 * @copyright 2020 MegaOptim (https://megaoptim.com)
 * @copyright 2016 WP Sharks (https://wpsharks.com/)
 */
namespace MegaOptim\RapidCache\Traits\Ac;

use MegaOptim\RapidCache\Classes;

trait ObUtils
{
    /**
     * Protocol.
     *
     * @since 1.0.0
     *
     * @type string Protocol
     */
    public $protocol = '';

    /**
     * Host token.
     *
     * @since 1.0.0
     *
     * @type string Host token.
     */
    public $host_token = '';

    /**
     * Host base/dir tokens.
     *
     * @since 1.0.0
     *
     * @type string Host base/dir tokens.
     */
    public $host_base_dir_tokens = '';


    /**
     * Version salt.
     *
     * @since 1.0.0
     *
     * @type string Forced to a string.
     */
    public $version_salt = '';

    /**
     * Relative cache path.
     *
     * @since 1.0.0
     *
     * @type string Cache path.
     */
    public $cache_path = '';

    /**
     * Absolute cache file path.
     *
     * @since 1.0.0
     *
     * @type string Absolute cache file path.
     */
    public $cache_file = '';

    /**
     * Relative 404 cache path.
     *
     * @since 1.0.0
     *
     * @type string 404 cache path.
     */
    public $cache_path_404 = '';

    /**
     * Absolute 404 cache file path.
     *
     * @since 1.0.0
     *
     * @type string Absolute 404 cache file path.
     */
    public $cache_file_404 = '';

    /**
     * Version salt + location.
     *
     * @since 1.0.0
     *
     * @type string Version salt + location.
     */
    public $salt_location = '';

    /**
     * Calculated max age.
     *
     * @since 1.0.0
     *
     * @type int Calculated max age.
     */
    public $cache_max_age = 0;

    /**
     * Max age has been disabled?
     *
     * @since 1.0.0
     *
     * @type bool Max age disabled?
     */
    public $cache_max_age_disabled = false;

    /**
     * Calculated 12 hour expiration time.
     *
     * @since 1.0.0
     *
     * @type int Calculated 12 hour expiration time.
     */
    public $nonce_cache_max_age = 0;

    /**
     * Start output buffering or serve cache.
     *
     * @since 1.0.0
     *
     * @note This is a vital part of Rapid Cache.
     *       This method serves existing (fresh) cache files. It is also responsible
     *       for beginning the process of collecting the output buffer.
     *
     * @return mixed
     */
    public function maybeStartOutputBuffering()
    {
	    if (strcasecmp(PHP_SAPI, 'cli') === 0) {
            return $this->maybeSetDebugInfo($this::NC_DEBUG_PHP_SAPI_CLI);
        }
        if (empty($_SERVER['HTTP_HOST']) || !$this->hostToken()) {
            return $this->maybeSetDebugInfo($this::NC_DEBUG_NO_SERVER_HTTP_HOST);
        }
        if (empty($_SERVER['REQUEST_URI'])) {
            return $this->maybeSetDebugInfo($this::NC_DEBUG_NO_SERVER_REQUEST_URI);
        }
        if (defined('RAPID_CACHE_ALLOWED') && !RAPID_CACHE_ALLOWED) {
            return $this->maybeSetDebugInfo($this::NC_DEBUG_RAPID_CACHE_ALLOWED_CONSTANT);
        }
        if (isset($_SERVER['RAPID_CACHE_ALLOWED']) && !$_SERVER['RAPID_CACHE_ALLOWED']) {
            return $this->maybeSetDebugInfo($this::NC_DEBUG_RAPID_CACHE_ALLOWED_SERVER_VAR);
        }
        if (defined('DONOTCACHEPAGE')) { // Common to most WP cache plugins.
            return $this->maybeSetDebugInfo($this::NC_DEBUG_DONOTCACHEPAGE_CONSTANT);
        }
	    if (isset($_SERVER['DONOTCACHEPAGE'])) {
            return $this->maybeSetDebugInfo($this::NC_DEBUG_DONOTCACHEPAGE_SERVER_VAR);
        }
        if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {
            return $this->maybeSetDebugInfo($this::NC_DEBUG_XMLRPC_REQUEST_CONSTANT);
        }
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return $this->maybeSetDebugInfo($this::NC_DEBUG_REST_REQUEST_CONSTANT);
        }
        if (isset($_GET[mb_strtolower(MEGAOPTIM_RAPID_CACHE_SHORT_NAME).'AC']) && !filter_var($_GET[mb_strtolower(MEGAOPTIM_RAPID_CACHE_SHORT_NAME).'AC'], FILTER_VALIDATE_BOOLEAN)) {
            return $this->maybeSetDebugInfo($this::NC_DEBUG_AC_GET_VAR);
        }
	    if ($this->isUncacheableRequestMethod()) {
            return $this->maybeSetDebugInfo($this::NC_DEBUG_UNCACHEABLE_REQUEST);
        }
        if (isset($_SERVER['SERVER_ADDR']) && $this->currentIp() === $_SERVER['SERVER_ADDR']) {
            if ((!$this->isAutoCacheEngine()) && !$this->isLocalhost()) {
                return $this->maybeSetDebugInfo($this::NC_DEBUG_SELF_SERVE_REQUEST);
            } // Don't trip on requests by the auto-cache engine.
        }
        if (!RAPID_CACHE_FEEDS_ENABLE && $this->isFeed()) {
            return $this->maybeSetDebugInfo($this::NC_DEBUG_FEED_REQUEST);
        }
	    if (preg_match('/\/(?:wp\-[^\/]+|xmlrpc)\.php(?:[?]|$)/ui', $_SERVER['REQUEST_URI'])) {
            return $this->maybeSetDebugInfo($this::NC_DEBUG_WP_SYSTEMATICS);
        }
        if (is_admin() || preg_match('/\/wp-admin(?:[\/?]|$)/ui', $_SERVER['REQUEST_URI'])) {
            return $this->maybeSetDebugInfo($this::NC_DEBUG_WP_ADMIN);
        }
        if (is_multisite() && preg_match('/\/files(?:[\/?]|$)/ui', $_SERVER['REQUEST_URI'])) {
            return $this->maybeSetDebugInfo($this::NC_DEBUG_MS_FILES);
        }
	    if (!RAPID_CACHE_WHEN_LOGGED_IN && $this->isLikeUserLoggedIn()) {
            return $this->maybeSetDebugInfo($this::NC_DEBUG_IS_LIKE_LOGGED_IN_USER);
        }
	    if (!RAPID_CACHE_GET_REQUESTS && $this->requestContainsUncacheableQueryVars()) {
            return $this->maybeSetDebugInfo($this::NC_DEBUG_GET_REQUEST_QUERIES);
        }
	    if (!empty($_REQUEST['preview'])) { // Don't cache previews under any circumstance.
            return $this->maybeSetDebugInfo($this::NC_DEBUG_PREVIEW);
        }
        if (RAPID_CACHE_EXCLUDE_HOSTS && preg_match(RAPID_CACHE_EXCLUDE_HOSTS, $_SERVER['HTTP_HOST'])) {
            return $this->maybeSetDebugInfo($this::NC_DEBUG_EXCLUDED_HOSTS);
        }
        if (RAPID_CACHE_EXCLUDE_URIS && preg_match(RAPID_CACHE_EXCLUDE_URIS, $_SERVER['REQUEST_URI'])) {
            return $this->maybeSetDebugInfo($this::NC_DEBUG_EXCLUDED_URIS);
        }
        if (RAPID_CACHE_EXCLUDE_AGENTS && !empty($_SERVER['HTTP_USER_AGENT']) || (!$this->isAutoCacheEngine())) {
            if (!empty($_SERVER['HTTP_USER_AGENT']) && preg_match(RAPID_CACHE_EXCLUDE_AGENTS, $_SERVER['HTTP_USER_AGENT'])) {
                return $this->maybeSetDebugInfo($this::NC_DEBUG_EXCLUDED_AGENTS);
            } // Don't trip on requests by the auto-cache engine.
        }
        if (RAPID_CACHE_EXCLUDE_REFS && !empty($_REQUEST['_wp_http_referer'])) {
            if (preg_match(RAPID_CACHE_EXCLUDE_REFS, stripslashes($_REQUEST['_wp_http_referer']))) {
                return $this->maybeSetDebugInfo($this::NC_DEBUG_EXCLUDED_REFS);
            } // This variable is set by WordPress core in some cases.
        }
        if (RAPID_CACHE_EXCLUDE_REFS && !empty($_SERVER['HTTP_REFERER'])) {
            if (preg_match(RAPID_CACHE_EXCLUDE_REFS, $_SERVER['HTTP_REFERER'])) {
                return $this->maybeSetDebugInfo($this::NC_DEBUG_EXCLUDED_REFS);
            } // Based on the HTTP referrer in this case.
        }
        $this->host_token           = $this->hostToken();
        $this->host_base_dir_tokens = $this->hostBaseDirTokens();
        $this->protocol             = $this->isSsl() ? 'https://' : 'http://';

        $this->version_salt = ''; // Initialize the version salt.

        $this->version_salt = $this->applyFilters(get_class($this).'__version_salt', $this->version_salt);
        $this->version_salt = $this->applyFilters(MEGAOPTIM_RAPID_CACHE_GLOBAL_NS.'_version_salt', $this->version_salt);
        $this->version_salt = $this->applyWpFilters(MEGAOPTIM_RAPID_CACHE_GLOBAL_NS.'_version_salt', $this->version_salt);

        $this->cache_path = $this->buildCachePath($this->protocol.$this->host_token.$_SERVER['REQUEST_URI'], '', $this->version_salt);
        $this->cache_file = RAPID_CACHE_DIR.'/'.$this->cache_path; // Not considering a user cache. That's done in the postload phase.

        $this->cache_path_404 = $this->buildCachePath($this->protocol.$this->host_token.rtrim($this->host_base_dir_tokens, '/').'/'.RAPID_CACHE_404_CACHE_FILENAME);
        $this->cache_file_404 = RAPID_CACHE_DIR.'/'.$this->cache_path_404; // Not considering a user cache at all here--ever.

        $this->salt_location = ltrim($this->version_salt.' '.$this->protocol.$this->host_token.$_SERVER['REQUEST_URI']);

        $this->cache_max_age       = strtotime('-'.RAPID_CACHE_MAX_AGE); // Initialize; global config.
        $this->nonce_cache_max_age = strtotime('-12 hours'); // Initialize; based on a fixed expiration time.

        if (defined('RAPID_CACHE_WHEN_LOGGED_IN') && RAPID_CACHE_WHEN_LOGGED_IN === 'postload' && $this->isLikeUserLoggedIn()) {
            $this->postload['when_logged_in'] = true; // Enable postload check.
        } elseif (is_file($this->cache_file) && ($this->cache_max_age_disabled || filemtime($this->cache_file) >= $this->cache_max_age)) {
            list($headers, $cache) = explode('<!--headers-->', file_get_contents($this->cache_file), 2);

            $headers_list = $this->headersList(); // Headers that are enqueued already.

            foreach (unserialize($headers) as $_header) {
                if (!in_array($_header, $headers_list, true) && mb_stripos($_header, 'Last-Modified:') !== 0) {
                    header($_header); // Only cacheable/safe headers are stored in the cache.
                }
            } // unset($_header); // Just a little housekeeping.

            if (RAPID_CACHE_DEBUGGING_ENABLE && $this->isHtmlXmlDoc($cache)) {
                $total_time = number_format(microtime(true) - $this->timer, 5, '.', '');

                $DebugNotes = new Classes\Notes();

                $DebugNotes->add(__('Loaded via Cache On', 'rapid-cache'), date('M jS, Y @ g:i a T'));
                $DebugNotes->add(__('Loaded via Cache In', 'rapid-cache'), sprintf(__('%1$s seconds', 'rapid-cache'), $total_time));

                $cache .= "\n\n".$DebugNotes->asHtmlComments();
            }
            exit($cache); // Exit with cache contents.
        } else {
            ob_start([$this, 'outputBufferCallbackHandler']);
        }
    }

    /**
     * Output buffer handler; i.e. the cache file generator.
     *
     * @since 1.0.0
     *
     * @param string $buffer The buffer from {@link \ob_start()}.
     * @param int    $phase  A set of bitmask flags.
     *
     * @throws \Exception If unable to handle output buffering for any reason.
     *
     * @return string|bool The output buffer, or `FALSE` to indicate no change.
     *
     * @note We CANNOT depend on any WP functionality here; it will cause problems.
     *    Anything we need from WP should be saved in the postload phase as a scalar value.
     *
     * @attaches-to {@link \ob_start()}
     */
    public function outputBufferCallbackHandler($buffer, $phase)
    {
        if (!($phase & PHP_OUTPUT_HANDLER_END)) {
            throw new \Exception(sprintf(__('Unexpected OB phase: `%1$s`.', 'rapid-cache'), $phase));
        }

        $cache = trim((string) $buffer);

        if (!isset($cache[0])) {
            return false; // Don't cache an empty buffer.
        }
        if (!isset($GLOBALS[MEGAOPTIM_RAPID_CACHE_GLOBAL_NS.'_shutdown_flag'])) {
            return (bool) $this->maybeSetDebugInfo($this::NC_DEBUG_EARLY_BUFFER_TERMINATION);
        }
        if (defined('RAPID_CACHE_ALLOWED') && !RAPID_CACHE_ALLOWED) {
            return (bool) $this->maybeSetDebugInfo($this::NC_DEBUG_RAPID_CACHE_ALLOWED_CONSTANT);
        }
        if (isset($_SERVER['RAPID_CACHE_ALLOWED']) && !$_SERVER['RAPID_CACHE_ALLOWED']) {
            return (bool) $this->maybeSetDebugInfo($this::NC_DEBUG_RAPID_CACHE_ALLOWED_SERVER_VAR);
        }
        if (defined('DONOTCACHEPAGE')) {
            return (bool) $this->maybeSetDebugInfo($this::NC_DEBUG_DONOTCACHEPAGE_CONSTANT);
        }
        if (isset($_SERVER['DONOTCACHEPAGE'])) {
            return (bool) $this->maybeSetDebugInfo($this::NC_DEBUG_DONOTCACHEPAGE_SERVER_VAR);
        }
        if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {
            return (bool) $this->maybeSetDebugInfo($this::NC_DEBUG_XMLRPC_REQUEST_CONSTANT);
        }
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return (bool) $this->maybeSetDebugInfo($this::NC_DEBUG_REST_REQUEST_CONSTANT);
        }
        if ((!defined('RAPID_CACHE_WHEN_LOGGED_IN') || !RAPID_CACHE_WHEN_LOGGED_IN) && $this->is_user_logged_in) {
            return (bool) $this->maybeSetDebugInfo($this::NC_DEBUG_IS_LOGGED_IN_USER);
        }
        if ((!defined('RAPID_CACHE_WHEN_LOGGED_IN') || !RAPID_CACHE_WHEN_LOGGED_IN) && $this->isLikeUserLoggedIn()) {
            return (bool) $this->maybeSetDebugInfo($this::NC_DEBUG_IS_LIKE_LOGGED_IN_USER);
        }
        if (!RAPID_CACHE_CACHE_NONCE_VALUES && preg_match('/\b(?:'.implode('|', $this->getPossibleNonceKeys()).')\b/u', $cache)) {
            if (defined('RAPID_CACHE_WHEN_LOGGED_IN') && RAPID_CACHE_WHEN_LOGGED_IN && $this->isLikeUserLoggedIn()) {
                if (!RAPID_CACHE_CACHE_NONCE_VALUES_WHEN_LOGGED_IN) {
                    return (bool) $this->maybeSetDebugInfo($this::NC_DEBUG_IS_LOGGED_IN_USER_NONCE);
                }
            } else { // Use the default debug notice for nonce conflicts.
                return (bool) $this->maybeSetDebugInfo($this::NC_DEBUG_PAGE_CONTAINS_NONCE);
            } // An nonce makes the page dynamic; i.e., NOT cache compatible.
        }
        if ($this->is_404 && !RAPID_CACHE_CACHE_404_REQUESTS) {
            return (bool) $this->maybeSetDebugInfo($this::NC_DEBUG_404_REQUEST);
        }
        if (mb_stripos($cache, '<body id="error-page">') !== false) {
            return (bool) $this->maybeSetDebugInfo($this::NC_DEBUG_WP_ERROR_PAGE);
        }
        if (!$this->hasACacheableContentType()) {
            return (bool) $this->maybeSetDebugInfo($this::NC_DEBUG_UNCACHEABLE_CONTENT_TYPE);
        }
        if (!$this->hasACacheableStatus()) {
            return (bool) $this->maybeSetDebugInfo($this::NC_DEBUG_UNCACHEABLE_STATUS);
        }
        if ($this->is_maintenance) {
            return (bool) $this->maybeSetDebugInfo($this::NC_DEBUG_MAINTENANCE_PLUGIN);
        }
        if ($this->functionIsPossible('zlib_get_coding_type') && zlib_get_coding_type()
            && (!($zlib_oc = ini_get('zlib.output_compression')) || !filter_var($zlib_oc, FILTER_VALIDATE_BOOLEAN))
        ) {
            return (bool) $this->maybeSetDebugInfo($this::NC_DEBUG_OB_ZLIB_CODING_TYPE);
        }
        # Lock the cache directory while writes take place here.

        $cache_lock = $this->cacheLock(); // Lock cache directory.

        # Construct a temp file for atomic cache writes.

        $cache_file_tmp = $this->addTmpSuffix($this->cache_file);

        # Cache directory checks. The cache file directory is created here if necessary.

        if (!is_dir(RAPID_CACHE_DIR) && mkdir(RAPID_CACHE_DIR, 0775, true) && !is_file(RAPID_CACHE_DIR.'/.htaccess')) {
            file_put_contents(RAPID_CACHE_DIR.'/.htaccess', $this->htaccess_deny);
        }
        if (!is_dir($cache_file_dir = dirname($this->cache_file))) {
            $cache_file_dir_writable = mkdir($cache_file_dir, 0775, true);
        }
        if (empty($cache_file_dir_writable) && !is_writable($cache_file_dir)) {
            throw new \Exception(sprintf(__('Cache directory not writable. %1$s needs this directory please: `%2$s`. Set permissions to `755` or higher; `777` might be needed in some cases.', 'rapid-cache'), MEGAOPTIM_RAPID_CACHE_NAME, $cache_file_dir));
        }
        # This is where a new 404 request might be detected for the first time.

        if ($this->is_404 && is_file($this->cache_file_404)) {
            if (!(symlink($this->cache_file_404, $cache_file_tmp) && rename($cache_file_tmp, $this->cache_file))) {
                throw new \Exception(sprintf(__('Unable to create symlink: `%1$s` » `%2$s`. Possible permissions issue (or race condition), please check your cache directory: `%3$s`.', 'rapid-cache'), $this->cache_file, $this->cache_file_404, RAPID_CACHE_DIR));
            }
            $this->cacheUnlock($cache_lock); // Release.
            return (bool) $this->maybeSetDebugInfo($this::NC_DEBUG_1ST_TIME_404_SYMLINK);
        }
        /* ------- Otherwise, we need to construct & store a new cache file. ----------------------------------------------- */


        if (RAPID_CACHE_DEBUGGING_ENABLE && $this->isHtmlXmlDoc($cache)) {
            $total_time = number_format(microtime(true) - $this->timer, 5, '.', '');
            $time       = time(); // Needed below for expiration calculation.

            $DebugNotes = new Classes\Notes();
            $DebugNotes->addAsciiArt(sprintf(__('%1$s is Fully Functional', 'rapid-cache'), MEGAOPTIM_RAPID_CACHE_NAME));
            $DebugNotes->addLineBreak();

            if (defined('RAPID_CACHE_WHEN_LOGGED_IN') && RAPID_CACHE_WHEN_LOGGED_IN && $this->user_token) {
                $DebugNotes->add(__('Cache File User Token', 'rapid-cache'), $this->user_token);
            }
            if (defined('RAPID_CACHE_MOBILE_ADAPTIVE_SALT_ENABLE') && RAPID_CACHE_MOBILE_ADAPTIVE_SALT_ENABLE && defined('RAPID_CACHE_MOBILE_ADAPTIVE_SALT') && RAPID_CACHE_MOBILE_ADAPTIVE_SALT && $this->mobile_adaptive_salt) {
                // Note: Not using `$this->mobile_adaptive_salt` here. Instead, generating a human readable variation.
                $DebugNotes->add(__('Cache File for Mobile Device', 'rapid-cache'), $this->fillUaTokens(RAPID_CACHE_MOBILE_ADAPTIVE_SALT, false));
            }

            $DebugNotes->add(__('Cache File URL', 'rapid-cache'), $this->is_404 ? __('404 [error document]', 'rapid-cache') : $this->protocol.$this->host_token.$_SERVER['REQUEST_URI']);
            $DebugNotes->add(__('Cache File Path', 'rapid-cache'), str_replace(WP_CONTENT_DIR, '', $this->is_404 ? $this->cache_file_404 : $this->cache_file));

            $DebugNotes->addLineBreak();

            $DebugNotes->add(__('Cache File Version Salt', 'rapid-cache'), $this->version_salt ? $this->version_salt : __('n/a', 'rapid-cache'));

            $DebugNotes->addLineBreak();

            $DebugNotes->add(__('Cache File Generated Via', 'rapid-cache'), $this->isAutoCacheEngine() ? __('Auto-Cache Engine', 'rapid-cache') : __('HTTP request', 'rapid-cache'));
            $DebugNotes->add(__('Cache File Generated On', 'rapid-cache'), date('M jS, Y @ g:i a T'));
            $DebugNotes->add(__('Cache File Generated In', 'rapid-cache'), sprintf(__('%1$s seconds', 'rapid-cache'), $total_time));

            $DebugNotes->addLineBreak();

            if ((defined('RAPID_CACHE_WHEN_LOGGED_IN') && RAPID_CACHE_WHEN_LOGGED_IN) && $this->cache_max_age < $this->nonce_cache_max_age && preg_match('/\b(?:'.implode('|', $this->getPossibleNonceKeys()).')\b/u', $cache)) {
                $DebugNotes->add(__('Cache File Expires Early', 'rapid-cache'), __('yes, due to nonce in markup', 'rapid-cache'));
                $DebugNotes->add(__('Cache File Expires On', 'rapid-cache'), date('M jS, Y @ g:i a T', $time + ($time - $this->nonce_cache_max_age)));
                $DebugNotes->add(__('Cache File Auto-Rebuild On', 'rapid-cache'), date('M jS, Y @ g:i a T', $time + ($time - $this->nonce_cache_max_age)));
            } else {
                $DebugNotes->add(__('Cache File Expires On', 'rapid-cache'), date('M jS, Y @ g:i a T', $time + ($time - $this->cache_max_age)));
                $DebugNotes->add(__('Cache File Auto-Rebuild On', 'rapid-cache'), date('M jS, Y @ g:i a T', $time + ($time - $this->cache_max_age)));
            }
            $cache .= "\n".$DebugNotes->asHtmlComments();
        }

        $cache = $this->applyWpFilters(MEGAOPTIM_RAPID_CACHE_GLOBAL_NS . '_ob_callback_filter', $cache);

        if ($this->is_404) {
            if (file_put_contents($cache_file_tmp, serialize($this->cacheableHeadersList()).'<!--headers-->'.$cache) && rename($cache_file_tmp, $this->cache_file_404)) {
                if (!(symlink($this->cache_file_404, $cache_file_tmp) && rename($cache_file_tmp, $this->cache_file))) {
                    throw new \Exception(sprintf(__('Unable to create symlink: `%1$s` » `%2$s`. Possible permissions issue (or race condition), please check your cache directory: `%3$s`.', 'rapid-cache'), $this->cache_file, $this->cache_file_404, RAPID_CACHE_DIR));
                }
                $this->cacheUnlock($cache_lock); // Release.
                return $cache; // Return the newly built cache; with possible debug info.
            }
        } elseif (file_put_contents($cache_file_tmp, serialize($this->cacheableHeadersList()).'<!--headers-->'.$cache) && rename($cache_file_tmp, $this->cache_file)) {
            $this->cacheUnlock($cache_lock); // Release.
            return $cache; // Return the newly built cache; with possible debug info.
        }
        @unlink($cache_file_tmp); // Clean this up (if it exists); and throw an exception with information for the site owner.
        throw new \Exception(sprintf(__('%1$s: failed to write cache file for: `%2$s`; possible permissions issue (or race condition), please check your cache directory: `%3$s`.', 'rapid-cache'), MEGAOPTIM_RAPID_CACHE_NAME, $_SERVER['REQUEST_URI'], RAPID_CACHE_DIR));
    }

	/**
	 * Returns all possible nonce keys
	 *
	 * @since 1.2.0
	 *
	 * @return string[]
	 */
	protected function getPossibleNonceKeys() {
		return $this->applyWpFilters( MEGAOPTIM_RAPID_CACHE_GLOBAL_NS . '_possible_nonce_keys', [
			'_wpnonce',
			'akismet_comment_nonce',
			'rest_nonce',
		] );
	}
}
