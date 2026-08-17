<?php
/**
 * Tests für ASU_Site_Setup und ASU_Elementor.
 *
 * @package AutoCleanupWP
 */

test(
	'Startseite: bekommt den Template-Slug, den Elementor wirklich kennt',
	function () {
		// Der Slug heisst elementor_header_footer. Im Backend steht darueber
		// "Elementor Full Width", das ist nur der Anzeigename.
		$result = new ASU_Result();
		$setup  = new ASU_Site_Setup();

		$home_id = $setup->create_static_home_page( $result, ASU_Elementor::TEMPLATE_FULL_WIDTH );

		Assert::same( 'elementor_header_footer', get_post_meta( $home_id, '_wp_page_template' ), 'Der Template-Slug muss der von Elementor sein.' );
		Assert::same( 'page', get_option( 'show_on_front' ), 'WordPress muss eine Seite statt des Blogs zeigen.' );
		Assert::same( $home_id, get_option( 'page_on_front' ), 'Die neue Seite muss die Startseite sein.' );
		Assert::false( $result->has_failures(), 'Ein sauberer Lauf darf keinen Fehler melden.' );
	}
);

test(
	'Startseite: ohne Elementor wird gar kein Template gesetzt',
	function () {
		$result = new ASU_Result();
		$setup  = new ASU_Site_Setup();

		$home_id = $setup->create_static_home_page( $result, '' );

		Assert::same( '', get_post_meta( $home_id, '_wp_page_template' ), 'Ohne Elementor darf kein fremder Slug in der Seite stehen.' );
		Assert::false( $result->has_failures(), 'Das ist kein Fehler.' );
	}
);

test(
	'Startseite: ein WP_Error beim Anlegen laesst die Optionen unangetastet',
	function () {
		ASU_Fake_WP::$insert_post_returns = new WP_Error( 'db_error', 'Datenbank nicht erreichbar.' );

		$result = new ASU_Result();
		$setup  = new ASU_Site_Setup();

		$home_id = $setup->create_static_home_page( $result, ASU_Elementor::TEMPLATE_FULL_WIDTH );

		Assert::same( 0, $home_id, 'Ohne Seite gibt es keine ID.' );
		Assert::false( (bool) get_option( 'page_on_front' ), 'Es darf keine Startseite gesetzt werden, die es nicht gibt.' );
		Assert::true( $result->has_failures(), 'Der Fehler muss im Protokoll stehen.' );
	}
);

test(
	'Permalinks: werden gesetzt und die Regeln neu geschrieben',
	function () {
		$result = new ASU_Result();
		$setup  = new ASU_Site_Setup();

		$setup->set_permalink_structure( $result );

		Assert::same( '/%postname%/', get_option( 'permalink_structure' ), 'Saubere URLs ohne Datum.' );
		Assert::true( ASU_Fake_WP::$rules_flushed, 'Ohne neue Rewrite-Regeln greifen die Permalinks nicht.' );
		Assert::false( $result->has_failures(), 'Ein sauberer Lauf darf keinen Fehler melden.' );
	}
);

test(
	'Sichtbarkeit: Suchmaschinen werden blockiert',
	function () {
		$result = new ASU_Result();
		$setup  = new ASU_Site_Setup();

		$setup->discourage_search_engines( $result );

		Assert::same( '0', get_option( 'blog_public' ), 'Baustellenseiten gehoeren nicht in den Index.' );
	}
);

test(
	'Elementor: schreibt genau eine Option, und zwar die richtige',
	function () {
		$result    = new ASU_Result();
		$elementor = new ASU_Elementor( true );

		$elementor->enable_containers( $result );

		Assert::same( 'active', get_option( 'elementor_experiment-container' ), 'Das ist der Schluessel, den Elementor liest.' );
		Assert::same(
			array( 'elementor_experiment-container' ),
			array_keys( ASU_Fake_WP::$options ),
			'Es darf keine zweite, erfundene Option geschrieben werden.'
		);
	}
);

test(
	'Elementor: ohne Elementor wird nichts angefasst',
	function () {
		$result    = new ASU_Result();
		$elementor = new ASU_Elementor( false );

		$elementor->enable_containers( $result );

		Assert::same( array(), ASU_Fake_WP::$options, 'Ohne Elementor darf keine Option entstehen.' );
		Assert::same( '', $elementor->page_template(), 'Ohne Elementor gibt es kein Template.' );
		Assert::false( $result->has_failures(), 'Fehlendes Elementor ist kein Fehler.' );
	}
);
