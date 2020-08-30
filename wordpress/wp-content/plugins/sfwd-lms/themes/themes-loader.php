<?php
/**
 * LearnDash Theme Loader.
 *
 * @package LearnDash
 * @subpackage Themes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-ld-themes-register.php';

// Register your themes.
require_once __DIR__ . '/legacy/index.php';
require_once __DIR__ . '/ld30/index.php';
