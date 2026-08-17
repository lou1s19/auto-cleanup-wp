<?php
/**
 * Grundeinstellungen der Website: Startseite, Permalinks, Sichtbarkeit.
 *
 * @package AutoCleanupWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ASU_Site_Setup {

	/** Titel der Seite, die als Startseite gesetzt wird. */
	const HOME_TITLE = 'Startseite';

	/**
	 * Legt eine leere Seite an und setzt sie als statische Startseite.
	 *
	 * @param ASU_Result $result   Protokoll des Laufs.
	 * @param string     $template Slug des Seiten-Templates, leer für das Standard-Template.
	 * @return int ID der neuen Seite, 0 bei Fehler.
	 */
	public function create_static_home_page( ASU_Result $result, $template = '' ) {
		$home_id = wp_insert_post(
			array(
				'post_title'  => self::HOME_TITLE,
				'post_status' => 'publish',
				'post_type'   => 'page',
			),
			true // true = im Fehlerfall ein WP_Error statt einer stillen 0.
		);

		if ( $result->catch_wp_error( 'startseite', $home_id, 'Startseite anlegen' ) ) {
			return 0;
		}

		$home_id = (int) $home_id;

		if ( $home_id <= 0 ) {
			$result->fail( 'startseite', 'Die Startseite liess sich nicht anlegen.' );

			return 0;
		}

		// WordPress soll die Seite statt der Blog-Übersicht anzeigen.
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home_id );

		if ( '' !== $template ) {
			update_post_meta( $home_id, '_wp_page_template', $template );
		}

		$result->ok(
			'startseite',
			'' === $template
				? 'Startseite angelegt und gesetzt, mit dem Standard-Template.'
				: sprintf( 'Startseite angelegt und gesetzt, Template %s.', $template )
		);

		return $home_id;
	}

	/**
	 * Setzt die Permalink-Struktur auf /%postname%/ und schreibt die Rewrite-Regeln neu.
	 *
	 * @param ASU_Result $result Protokoll des Laufs.
	 * @return void
	 */
	public function set_permalink_structure( ASU_Result $result ) {
		global $wp_rewrite;

		if ( ! $wp_rewrite || ! is_object( $wp_rewrite ) ) {
			$result->fail( 'permalinks', 'Die Permalink-Verwaltung von WordPress war nicht erreichbar.' );

			return;
		}

		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		$wp_rewrite->flush_rules();

		$result->ok( 'permalinks', 'Permalinks stehen auf /%postname%/.' );
	}

	/**
	 * Setzt das Häkchen "Suchmaschinen davon abhalten, diese Website zu indexieren".
	 * Gedacht für Baustellen- und Testseiten. Vor dem Livegang wieder entfernen.
	 *
	 * @param ASU_Result $result Protokoll des Laufs.
	 * @return void
	 */
	public function discourage_search_engines( ASU_Result $result ) {
		update_option( 'blog_public', '0' );

		$result->ok( 'sichtbarkeit', 'Suchmaschinen sind blockiert. Vor dem Livegang wieder freigeben.' );
	}
}
