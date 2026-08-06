<?php
/**
 * Plugin Name: Auto Cleanup WP
 * Description: Räumt eine frische WordPress-Installation auf und richtet sie für Elementor ein. Deaktiviert sich danach selbst. Achtung: löscht Inhalte, Themes und Plugins endgültig.
 * Version: 1.0.3
 * Author: Louis
 * License: MIT
 */

// Direkten Aufruf der Datei über den Browser verhindern.
if ( ! defined('ABSPATH') ) { exit; }

define('ASU_VERSION', '1.0.3');
define('ASU_PLUGIN_FILE', __FILE__);
define('ASU_PATH', plugin_dir_path(__FILE__));

// Die vier Bausteine laden. Eine Klasse pro Datei.
require_once ASU_PATH . 'includes/class-asu-cleanup.php';
require_once ASU_PATH . 'includes/class-asu-site-setup.php';
require_once ASU_PATH . 'includes/class-asu-elementor.php';
require_once ASU_PATH . 'includes/class-asu-plugin.php';

// Plugin starten.
$asu_plugin = new ASU_Plugin();
$asu_plugin->register();
