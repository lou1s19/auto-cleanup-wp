<?php
/**
 * Plugin Name:       Auto Cleanup WP
 * Plugin URI:        https://github.com/lou1s19/auto-cleanup-wp
 * Description:       Räumt eine frische WordPress-Installation auf und richtet sie für Elementor ein. Deaktiviert sich danach selbst. Achtung: löscht Inhalte, Themes und Plugins endgültig.
 * Version:           1.2.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Louis
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       auto-cleanup-wp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Die einzige Datei, die von Hand geladen wird. Alles Weitere holt der
// Autoloader, sobald eine ASU_-Klasse gebraucht wird.
require_once __DIR__ . '/includes/class-asu-autoloader.php';

ASU_Autoloader::register( __DIR__ . '/includes' );

ASU_Plugin::boot( __FILE__ );
