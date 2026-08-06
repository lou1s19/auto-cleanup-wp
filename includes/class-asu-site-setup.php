<?php
/**
 * Grundeinstellungen der Website: Startseite, Permalinks, Sichtbarkeit.
 */

if ( ! defined('ABSPATH') ) { exit; }

class ASU_Site_Setup {

	/**
	 * Legt eine leere Seite "Startseite" an und setzt sie als statische Startseite.
	 * Die Seite bekommt das Elementor-Template "Full Width".
	 *
	 * @return int ID der neuen Seite, 0 bei Fehler.
	 */
	public function create_static_home_page() {
		$home_id = wp_insert_post([
			'post_title'  => 'Startseite',
			'post_status' => 'publish',
			'post_type'   => 'page',
		]);

		if ( ! $home_id || is_wp_error($home_id) ) {
			return 0;
		}

		// WordPress soll die Seite statt der Blog-Übersicht anzeigen.
		update_option('show_on_front', 'page');
		update_option('page_on_front', $home_id);

		// Hello/Elementor: Full-Width-Template setzen.
		update_post_meta($home_id, '_wp_page_template', 'elementor_full_width');

		return (int) $home_id;
	}

	/**
	 * Setzt die Permalink-Struktur auf /%postname%/ und schreibt die Rewrite-Regeln neu.
	 */
	public function set_permalink_structure() {
		global $wp_rewrite;

		if ( ! $wp_rewrite ) {
			return;
		}

		$wp_rewrite->set_permalink_structure('/%postname%/');
		$wp_rewrite->flush_rules();
	}

	/**
	 * Setzt das Häkchen "Suchmaschinen davon abhalten, diese Website zu indexieren".
	 * Gedacht für Baustellen- und Testseiten. Vor dem Livegang wieder entfernen.
	 */
	public function discourage_search_engines() {
		update_option('blog_public', '0');
	}
}
