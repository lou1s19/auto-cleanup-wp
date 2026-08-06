<?php
/**
 * Aufräumen: Standard-Inhalte, überflüssige Themes und überflüssige Plugins entfernen.
 *
 * ACHTUNG: Diese Klasse löscht endgültig. Nur auf frischen Installationen einsetzen.
 */

if ( ! defined('ABSPATH') ) { exit; }

class ASU_Cleanup {

	/** Themes, die erhalten bleiben (Hello Elementor und passende Child-Themes). */
	private $keep_stylesheets = ['hello-elementor', 'hello', 'hello-child', 'hello-elementor-child'];

	/** Plugins, die entfernt werden. */
	private $remove_plugins = ['hello.php', 'akismet/akismet.php'];

	/**
	 * Löscht alle Beiträge und Seiten, inklusive Entwürfen und Papierkorb.
	 * Entfernt damit auch den Standard-Beitrag und die Standard-Seite von WordPress.
	 */
	public function delete_all_posts_and_pages() {
		$items = get_posts([
			'post_type'   => ['post', 'page'],
			'numberposts' => -1,
			'post_status' => ['publish', 'draft', 'pending', 'future', 'private', 'trash'],
		]);

		foreach ( $items as $item ) {
			// true = endgültig löschen, nicht in den Papierkorb.
			wp_delete_post($item->ID, true);
		}
	}

	/**
	 * Löscht alle Themes außer Hello Elementor.
	 * Ist ein anderes Theme aktiv, wird vorher auf Hello Elementor umgeschaltet,
	 * damit das aktive Theme nicht gelöscht wird.
	 */
	public function remove_unused_themes() {
		ASU_Wp_Admin::load_theme_functions();

		$current_stylesheet = get_option('stylesheet');
		$hello_stylesheet   = $this->find_hello_stylesheet();

		if ( $hello_stylesheet && $current_stylesheet !== $hello_stylesheet && function_exists('switch_theme') ) {
			switch_theme($hello_stylesheet);
			$current_stylesheet = $hello_stylesheet;
		}

		$themes = function_exists('wp_get_themes') ? wp_get_themes() : [];

		foreach ( $themes as $stylesheet => $theme ) {
			$template = method_exists($theme, 'get_template') ? $theme->get_template() : '';

			// Behalten: Hello Elementor und alles, was darauf aufbaut.
			if ( in_array($stylesheet, $this->keep_stylesheets, true) || in_array($template, $this->keep_stylesheets, true) ) {
				continue;
			}

			// Das gerade aktive Theme nie löschen.
			if ( $stylesheet === $current_stylesheet ) {
				continue;
			}

			if ( function_exists('delete_theme') ) {
				try {
					delete_theme($stylesheet);
				} catch ( \Exception $e ) {
					// Löschen fehlgeschlagen, z. B. wegen Dateirechten. Rest läuft weiter.
				}
			}
		}
	}

	/**
	 * Deaktiviert und löscht die WordPress-Standard-Plugins Hello Dolly und Akismet.
	 */
	public function remove_unused_plugins() {
		ASU_Wp_Admin::load_plugin_functions();

		$installed = function_exists('get_plugins') ? get_plugins() : [];

		foreach ( $installed as $plugin_file => $data ) {
			if ( ! in_array($plugin_file, $this->remove_plugins, true) ) {
				continue;
			}

			// Erst deaktivieren, dann löschen.
			if ( function_exists('deactivate_plugins') ) {
				try {
					deactivate_plugins($plugin_file, true);
				} catch ( \Exception $e ) {
					// Weiter mit dem nächsten Plugin.
				}
			}

			if ( function_exists('delete_plugins') ) {
				try {
					delete_plugins([ $plugin_file ]);
				} catch ( \Exception $e ) {
					// Weiter mit dem nächsten Plugin.
				}
			}
		}
	}

	/**
	 * Sucht das installierte Hello-Theme. Bevorzugt "hello-elementor".
	 *
	 * @return string Verzeichnisname des Themes, leer wenn keines gefunden wurde.
	 */
	private function find_hello_stylesheet() {
		if ( ! function_exists('wp_get_theme') ) {
			return '';
		}

		$preferred = wp_get_theme('hello-elementor');
		if ( $preferred && $preferred->exists() ) {
			return 'hello-elementor';
		}

		$fallback = wp_get_theme('hello');
		if ( $fallback && $fallback->exists() ) {
			return 'hello';
		}

		return '';
	}
}
