<?php
/**
 * Tests für ASU_Plugin, den Ablauf, und für ASU_Result, das Protokoll.
 *
 * @package AutoCleanupWP
 */

/**
 * Ein einsatzbereites Plugin auf einer typischen frischen Installation.
 *
 * @return ASU_Plugin
 */
function asu_test_plugin() {
	ASU_Fake_WP::add_post( 'post', 'publish' );
	ASU_Fake_WP::add_post( 'page', 'draft' );

	ASU_Fake_WP::add_theme( 'hello-elementor' );
	ASU_Fake_WP::add_theme( 'twentytwentyfour' );
	ASU_Fake_WP::$options['stylesheet'] = 'twentytwentyfour';

	ASU_Fake_WP::$plugins = array(
		'hello.php'                     => array( 'Name' => 'Hello Dolly' ),
		'auto-cleanup-wp/auto-setup.php' => array( 'Name' => 'Auto Cleanup WP' ),
	);

	return new ASU_Plugin(
		'/wp-content/plugins/auto-cleanup-wp/auto-setup.php',
		new ASU_Cleanup( 'auto-cleanup-wp/auto-setup.php' ),
		new ASU_Site_Setup(),
		new ASU_Elementor( true )
	);
}

test(
	'Ablauf: der komplette Lauf geht ohne Fehler durch',
	function () {
		$result = asu_test_plugin()->run_setup();

		Assert::false( $result->has_failures(), 'Auf einer frischen Installation darf nichts schiefgehen.' );

		// Uebrig bleibt genau die Startseite, die der Lauf selbst angelegt hat.
		Assert::same( array( get_option( 'page_on_front' ) ), array_keys( ASU_Fake_WP::$posts ), 'Nur die neue Startseite bleibt.' );
		Assert::same( 'hello-elementor', get_option( 'stylesheet' ), 'Hello Elementor ist aktiv.' );
		Assert::same( '/%postname%/', get_option( 'permalink_structure' ), 'Permalinks stehen.' );
		Assert::same( 'active', get_option( 'elementor_experiment-container' ), 'Container sind an.' );
		Assert::contains( 'hello.php', ASU_Fake_WP::$deleted_plugins, 'Hello Dolly ist weg.' );
	}
);

test(
	'Ablauf: die Startseite bekommt das Elementor-Template',
	function () {
		asu_test_plugin()->run_setup();

		$home_id = get_option( 'page_on_front' );

		Assert::same( 'elementor_header_footer', get_post_meta( $home_id, '_wp_page_template' ), 'Der Slug muss durchgereicht werden.' );
	}
);

test(
	'Multisite: bricht ab und loescht nichts',
	function () {
		ASU_Fake_WP::$is_multisite = true;

		$result = asu_test_plugin()->run_setup();

		Assert::true( $result->has_failures(), 'Der Abbruch muss als Fehlschlag im Protokoll stehen.' );
		Assert::same( array(), ASU_Fake_WP::$deleted_themes, 'Im Netzwerk teilen sich alle Sites die Theme-Dateien.' );
		Assert::same( array(), ASU_Fake_WP::$deleted_plugins, 'Dasselbe gilt fuer Plugins.' );
		Assert::same( array(), ASU_Fake_WP::$deleted_posts, 'Und Inhalte werden auch nicht angefasst.' );
	}
);

test(
	'Multisite: auch die Netzwerk-Aktivierung wird erkannt',
	function () {
		// register_activation_hook reicht $network_wide herein, is_multisite()
		// allein wuerde bei einer Netzwerk-Aktivierung nicht reichen.
		$result = asu_test_plugin()->run_setup( true );

		Assert::true( $result->has_failures(), 'Netzwerkweite Aktivierung muss ebenso abbrechen.' );
		Assert::same( array(), ASU_Fake_WP::$deleted_posts, 'Es darf nichts geloescht werden.' );
	}
);

test(
	'Abschluss: deaktiviert das Plugin und meldet den Erfolg',
	function () {
		$plugin = asu_test_plugin();
		$plugin->run_setup();

		$plugin->finish();

		Assert::contains( 'auto-cleanup-wp/auto-setup.php', ASU_Fake_WP::$deactivated_plugins, 'Das Plugin muss sich selbst abschalten.' );
		Assert::false( (bool) get_option( ASU_Plugin::OPTION_RESULT ), 'Das Protokoll wird nach dem Lesen entfernt.' );
		Assert::true( asu_has_action( 'admin_notices' ), 'Ohne Meldung wirkt es, als haette das Aktivieren nicht funktioniert.' );

		$notice = asu_do_action( 'admin_notices' );

		Assert::text_contains( 'notice-success', $notice, 'Ein sauberer Lauf ist gruen.' );
		Assert::text_contains( 'selbst deaktiviert', $notice, 'Der Mensch muss erfahren, warum das Plugin wieder inaktiv ist.' );
	}
);

test(
	'Abschluss: AJAX-Aufrufe duerfen das Protokoll nicht verbrauchen',
	function () {
		// admin_init feuert auch auf admin-ajax.php, und das erreichen sogar
		// nicht eingeloggte Besucher. Wuerde dort abgeraeumt, saehe niemand die
		// Meldung, obwohl das Plugin sich deaktiviert hat.
		$plugin = asu_test_plugin();
		$plugin->run_setup();

		ASU_Fake_WP::$doing_ajax = true;
		$plugin->finish();

		Assert::true( (bool) get_option( ASU_Plugin::OPTION_RESULT ), 'Das Protokoll muss liegen bleiben.' );
		Assert::missing(
			'auto-cleanup-wp/auto-setup.php',
			ASU_Fake_WP::$deactivated_plugins,
			'Das Plugin darf sich hier noch nicht selbst abschalten.'
		);

		// Der naechste echte Backend-Aufruf holt es nach.
		ASU_Fake_WP::$doing_ajax = false;
		$plugin->finish();

		Assert::true( asu_has_action( 'admin_notices' ), 'Jetzt kommt die Meldung.' );
	}
);

test(
	'Abschluss: ohne gelaufenes Setup passiert nichts',
	function () {
		$plugin = asu_test_plugin();

		$plugin->finish();

		Assert::same( array(), ASU_Fake_WP::$deactivated_plugins, 'Ohne Protokoll gibt es nichts abzuschliessen.' );
		Assert::false( asu_has_action( 'admin_notices' ), 'Und auch nichts zu melden.' );
	}
);

test(
	'Meldung: nennt die fehlgeschlagenen Schritte statt Erfolg zu behaupten',
	function () {
		ASU_Fake_WP::$is_multisite = true;

		$plugin = asu_test_plugin();
		$plugin->run_setup();
		$plugin->finish();

		$notice = asu_do_action( 'admin_notices' );

		Assert::text_contains( 'notice-warning', $notice, 'Ein Lauf mit Fehlern ist nicht gruen.' );
		Assert::text_contains( 'Multisite-Netzwerk', $notice, 'Der Grund gehoert in die Meldung.' );
	}
);

test(
	'Meldung: nur fuer Menschen mit Adminrechten',
	function () {
		$plugin = asu_test_plugin();
		$plugin->run_setup();
		$plugin->finish();

		ASU_Fake_WP::$can_manage_options = false;

		Assert::same( '', asu_do_action( 'admin_notices' ), 'Wer die Seite nicht verwalten darf, braucht die Meldung nicht.' );
	}
);

test(
	'Meldung: fremder Text wird escaped',
	function () {
		$result = new ASU_Result();
		$result->fail( 'themes', 'Kaputt <script>alert(1)</script>' );

		update_option( ASU_Plugin::OPTION_RESULT, $result->to_array() );

		$plugin = asu_test_plugin();
		$plugin->finish();

		$notice = asu_do_action( 'admin_notices' );

		Assert::text_contains( '&lt;script&gt;', $notice, 'Nichts darf ungeprueft ins Backend.' );
	}
);

test(
	'Protokoll: uebersteht den Weg durch die Option',
	function () {
		$result = new ASU_Result();
		$result->ok( 'inhalte', 'Alles gelöscht.' );
		$result->fail( 'themes', 'Ging nicht.' );
		$result->skip( 'container', 'Nicht noetig.' );

		$restored = ASU_Result::from_array( $result->to_array() );

		Assert::same( 3, count( $restored->steps() ), 'Alle Schritte muessen ankommen.' );
		Assert::same( 1, count( $restored->failures() ), 'Nur der eine Fehlschlag zaehlt.' );
		Assert::true( $restored->has_failures(), 'Und er muss auffallen.' );
	}
);

test(
	'Protokoll: kaputte Optionsdaten legen das Backend nicht lahm',
	function () {
		$restored = ASU_Result::from_array( 'kein array' );

		Assert::same( array(), $restored->steps(), 'Unbrauchbare Daten ergeben ein leeres Protokoll.' );
		Assert::false( $restored->has_failures(), 'Und keinen erfundenen Fehler.' );
	}
);

test(
	'Anmeldung: genau zwei Einstiegspunkte',
	function () {
		asu_test_plugin()->register();

		Assert::same( 1, count( ASU_Fake_WP::$activation_hooks ), 'Ein Aktivierungs-Hook.' );
		Assert::same( 1, count( ASU_Fake_WP::$actions['admin_init'] ), 'Ein admin_init.' );
	}
);
