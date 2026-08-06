<?php
/**
 * Lädt WordPress-Funktionen nach, die beim Aktivieren eines Plugins noch nicht bereitstehen.
 *
 * Hintergrund: Funktionen wie get_plugins() oder delete_theme() liegen im Admin-Bereich
 * von WordPress und werden nicht bei jedem Aufruf automatisch geladen.
 */

if ( ! defined('ABSPATH') ) { exit; }

class ASU_Wp_Admin {

	/** Stellt sicher, dass get_plugins(), deactivate_plugins() und delete_plugins() verfügbar sind. */
	public static function load_plugin_functions() {
		if ( function_exists('deactivate_plugins') && function_exists('get_plugins') && function_exists('delete_plugins') ) {
			return;
		}

		$file = ABSPATH . 'wp-admin/includes/plugin.php';
		if ( file_exists($file) ) {
			require_once $file;
		}
	}

	/** Stellt sicher, dass wp_get_themes(), switch_theme() und delete_theme() verfügbar sind. */
	public static function load_theme_functions() {
		if ( ! function_exists('wp_get_themes') || ! function_exists('switch_theme') ) {
			$file = ABSPATH . 'wp-includes/theme.php';
			if ( file_exists($file) ) {
				require_once $file;
			}
		}

		if ( ! function_exists('delete_theme') ) {
			$file = ABSPATH . 'wp-admin/includes/theme.php';
			if ( file_exists($file) ) {
				require_once $file;
			}
		}
	}
}
