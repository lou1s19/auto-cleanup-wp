<?php
/**
 * Plugin Name: Auto Cleanup WP
 * Description: Richtet eine frische WordPress-Installation ein: löscht Standard-Inhalte, überflüssige Themes und Plugins, legt eine statische Startseite an, setzt die Permalinks auf /%postname%/ und aktiviert die Elementor Flexbox Container. Danach deaktiviert sich das Plugin selbst.
 * WARNUNG: Dieses Plugin löscht Inhalte, Themes und Plugins endgültig. Nur auf einer frischen Installation oder nach einem vollständigen Backup einsetzen.
 * Version: 1.2.0
 * Author: Louis
 * License: MIT
 */

// Direkten Aufruf der Datei über den Browser verhindern.
if ( ! defined('ABSPATH') ) { exit; }

define('ASU_VERSION', '1.2.0');
define('ASU_PLUGIN_FILE', __FILE__);
define('ASU_PATH', plugin_dir_path(__FILE__));

// Die einzelnen Bausteine laden. Jede Datei enthält genau eine Klasse.
require_once ASU_PATH . 'includes/class-asu-options.php';
require_once ASU_PATH . 'includes/class-asu-wp-admin.php';
require_once ASU_PATH . 'includes/class-asu-cleanup.php';
require_once ASU_PATH . 'includes/class-asu-site-setup.php';
require_once ASU_PATH . 'includes/class-asu-elementor-container.php';
require_once ASU_PATH . 'includes/class-asu-admin-notices.php';
require_once ASU_PATH . 'includes/class-asu-plugin.php';

// Plugin starten: Hauptklasse erzeugen und ihre Hooks bei WordPress anmelden.
$asu_plugin = new ASU_Plugin();
$asu_plugin->register();
