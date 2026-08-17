<?php
/**
 * Plugin Name:       Auto Cleanup WP
 * Plugin URI:        https://github.com/lou1s19/auto-cleanup-wp
 * Description:       Räumt eine frische WordPress-Installation auf und richtet sie für Elementor ein. Deaktiviert sich danach selbst. Achtung: löscht Inhalte, Themes und Plugins endgültig.
 * Version:           1.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Louis
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       auto-cleanup-wp
 *
 * @package AutoCleanupWP
 */

// Direkten Aufruf der Datei über den Browser verhindern.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Der Autoloader ist die einzige Datei, die von Hand geladen wird.
// Alles Weitere holt er sich selbst, sobald eine ASU_-Klasse gebraucht wird.
require_once __DIR__ . '/includes/class-asu-autoloader.php';

ASU_Autoloader::register( __DIR__ . '/includes' );

ASU_Plugin::boot( __FILE__ );
