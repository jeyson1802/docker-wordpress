<?php
/**
 * Functions for uninstall LearnDash
 *
 * @since 2.5.0
 *
 * @package LearnDash
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

/**
 * Fires on plugin uninstall.
 */
do_action( 'learndash_uninstall' );
