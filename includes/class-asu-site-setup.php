<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ASU_Site_Setup {

	const HOME_TITLE = 'Startseite';

	public function create_static_home_page( ASU_Result $result, string $template = '' ): int {
		$home_id = wp_insert_post(
			array(
				'post_title'  => self::HOME_TITLE,
				'post_status' => 'publish',
				'post_type'   => 'page',
			),
			true
		);

		if ( $result->catch_wp_error( 'startseite', $home_id, 'Startseite anlegen' ) ) {
			return 0;
		}

		$home_id = (int) $home_id;

		if ( $home_id <= 0 ) {
			$result->fail( 'startseite', 'Die Startseite liess sich nicht anlegen.' );

			return 0;
		}

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

	public function set_permalink_structure( ASU_Result $result ): void {
		global $wp_rewrite;

		if ( ! is_object( $wp_rewrite ) ) {
			$result->fail( 'permalinks', 'Die Permalink-Verwaltung von WordPress war nicht erreichbar.' );

			return;
		}

		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		$wp_rewrite->flush_rules();

		$result->ok( 'permalinks', 'Permalinks stehen auf /%postname%/.' );
	}

	public function discourage_search_engines( ASU_Result $result ): void {
		update_option( 'blog_public', '0' );

		$result->ok( 'sichtbarkeit', 'Suchmaschinen sind blockiert. Vor dem Livegang wieder freigeben.' );
	}
}
