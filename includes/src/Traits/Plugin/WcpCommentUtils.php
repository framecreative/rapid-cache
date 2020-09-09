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

trait WcpCommentUtils
{
    /**
     * Automatically clears cache files for a post associated with a particular comment.
     *
     * @since 1.0.0
     *
     * @attaches-to `trackback_post` hook.
     * @attaches-to `pingback_post` hook.
     * @attaches-to `comment_post` hook.
     *
     * @param int $comment_id A WordPress comment ID.
     *
     * @return int Total files cleared by this routine (if any).
     */
    public function autoClearCommentPostCache($comment_id)
    {
        $counter = 0; // Initialize.

        if (!($comment_id = (integer) $comment_id)) {
            return $counter; // Nothing to do.
        }
        if (!is_null($done = &$this->cacheKey('autoClearCommentPostCache', $comment_id))) {
            return $counter; // Already did this.
        }
        $done = true; // Flag as having been done.

        if (!$this->options['enable']) {
            return $counter; // Nothing to do.
        }
        if (!is_object($comment = get_comment($comment_id))) {
            return $counter; // Nothing we can do.
        }
        if (empty($comment->comment_post_ID)) {
            return $counter; // Nothing we can do.
        }
        if ($comment->comment_approved === 'spam' || $comment->comment_approved === '0') {
            // Don't allow next `autoClearPostCache()` call to clear post cache.
            $allow = &$this->cacheKey('autoClearPostCache_allow');
            $allow = false; // Flag as false; i.e., disallow.
            return $counter;
        }
        $counter += $this->autoClearXmlFeedsCache('blog-comments');
        $counter += $this->autoClearXmlFeedsCache('post-comments', $comment->comment_post_ID);
        $counter += $this->autoClearPostCache($comment->comment_post_ID);

        return $counter;
    }

    /**
     * Automatically clears cache files for a post associated with a particular comment.
     *
     * @since 1.0.0
     *
     * @attaches-to `transition_comment_status` hook.
     *
     * @param string    $new_status New comment status.
     * @param string    $old_status Old comment status.
     * @param \stdClass $comment    Comment object.
     *
     * @throws \Exception If a clear failure occurs.
     *
     * @return int Total files cleared by this routine (if any).
     *
     * @note This is also called upon by other routines which listen for
     *    events that are indirectly associated with a comment ID.
     */
    public function autoClearCommentPostCacheTransition($new_status, $old_status, $comment)
    {
        $counter = 0; // Initialize.

        if (!is_object($comment)) {
            return $counter; // Nothing we can do.
        }
        if (empty($comment->comment_post_ID)) {
            return $counter; // Nothing we can do.
        }
        if (!is_null($done = &$this->cacheKey('autoClearCommentPostCacheTransition', [$new_status, $old_status, $comment->comment_post_ID]))) {
            return $counter; // Already did this.
        }
        $done = true; // Flag as having been done.

        if (!$this->options['enable']) {
            return $counter; // Nothing to do.
        }
        if (!($old_status === 'approved' || ($old_status === 'unapproved' && $new_status === 'approved'))) {
            // If excluded here, don't allow next `autoClearPostCache()` call to clear post cache.
            $allow = &$this->cacheKey('autoClearPostCache_allow');
            $allow = false; // Flag as false; i.e., disallow.
            return $counter;
        }
        $counter += $this->autoClearXmlFeedsCache('blog-comments');
        $counter += $this->autoClearXmlFeedsCache('post-comments', $comment->comment_post_ID);
        $counter += $this->autoClearPostCache($comment->comment_post_ID);

        return $counter;
    }
}
