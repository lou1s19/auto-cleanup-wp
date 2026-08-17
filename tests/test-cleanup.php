<?php
/**
 * Tests für ASU_Cleanup, die Klasse, die endgültig löscht.
 *
 * @package AutoCleanupWP
 */

test(
	'Inhalte: loescht alle Beitraege und Seiten, auch Auto-Entwuerfe und Papierkorb',
	function () {
		ASU_Fake_WP::add_post( 'post', 'publish' );
		ASU_Fake_WP::add_post( 'page', 'draft' );
		ASU_Fake_WP::add_post( 'page', 'auto-draft' );
		ASU_Fake_WP::add_post( 'post', 'trash' );
		ASU_Fake_WP::add_post( 'page', 'private' );

		$result  = new ASU_Result();
		$cleanup = new ASU_Cleanup();
		$cleanup->delete_all_posts_and_pages( $result );

		Assert::same( array(), ASU_Fake_WP::$posts, 'Es darf kein Beitrag und keine Seite uebrig bleiben.' );
		Assert::false( $result->has_failures(), 'Ein sauberer Lauf darf keinen Fehler melden.' );
	}
);

test(
	'Inhalte: laesst andere Post-Typen in Ruhe',
	function () {
		ASU_Fake_WP::add_post( 'post', 'publish' );
		$product = ASU_Fake_WP::add_post( 'product', 'publish' );

		$cleanup = new ASU_Cleanup();
		$cleanup->delete_all_posts_and_pages( new ASU_Result() );

		Assert::true( isset( ASU_Fake_WP::$posts[ $product ] ), 'Ein Produkt ist weder Beitrag noch Seite und muss bleiben.' );
	}
);

test(
	'Inhalte: meldet einen Fehler, wenn sich etwas nicht loeschen laesst',
	function () {
		ASU_Fake_WP::add_post( 'post', 'publish' );
		$stuck                          = ASU_Fake_WP::add_post( 'page', 'publish' );
		ASU_Fake_WP::$undeletable_posts = array( $stuck );

		$result  = new ASU_Result();
		$cleanup = new ASU_Cleanup();
		$cleanup->delete_all_posts_and_pages( $result );

		Assert::true( $result->has_failures(), 'Ein gescheitertes Loeschen muss im Protokoll stehen.' );
	}
);

test(
	'Themes: schaltet auf Hello Elementor um und loescht den Rest',
	function () {
		ASU_Fake_WP::add_theme( 'twentytwentyfour' );
		ASU_Fake_WP::add_theme( 'twentytwentyfive' );
		ASU_Fake_WP::add_hello_elementor();
		ASU_Fake_WP::$options['stylesheet'] = 'twentytwentyfour';

		$result  = new ASU_Result();
		$cleanup = new ASU_Cleanup();
		$cleanup->remove_unused_themes( $result );

		Assert::same( 'hello-elementor', get_option( 'stylesheet' ), 'Es muss auf Hello Elementor umgeschaltet werden.' );
		Assert::same( array( 'hello-elementor' ), array_keys( ASU_Fake_WP::$themes ), 'Nur Hello Elementor darf uebrig bleiben.' );
		Assert::false( $result->has_failures(), 'Ein sauberer Lauf darf keinen Fehler melden.' );
	}
);

test(
	'Themes: behaelt ein Child-Theme von Hello Elementor',
	function () {
		ASU_Fake_WP::add_hello_elementor();
		ASU_Fake_WP::add_theme( 'mein-child', 'hello-elementor' );
		ASU_Fake_WP::add_theme( 'twentytwentyfour' );
		ASU_Fake_WP::$options['stylesheet'] = 'hello-elementor';

		$cleanup = new ASU_Cleanup();
		$cleanup->remove_unused_themes( new ASU_Result() );

		Assert::true( isset( ASU_Fake_WP::$themes['mein-child'] ), 'Ein Child von Hello Elementor baut darauf auf und bleibt.' );
		Assert::false( isset( ASU_Fake_WP::$themes['twentytwentyfour'] ), 'Ein fremdes Theme muss weg.' );
	}
);

test(
	'Themes: ohne Hello Elementor bleibt das aktive Child-Theme UND sein Parent stehen',
	function () {
		// Genau der Fall, der die Website weiss gemacht haette: Hello ist nicht
		// installiert, also wird nicht umgeschaltet. Wird jetzt das Parent des
		// aktiven Child-Themes geloescht, findet WordPress keine Templates mehr.
		ASU_Fake_WP::add_theme( 'astra' );
		ASU_Fake_WP::add_theme( 'astra-child', 'astra' );
		ASU_Fake_WP::add_theme( 'twentytwentyfour' );
		ASU_Fake_WP::$options['stylesheet'] = 'astra-child';

		$result  = new ASU_Result();
		$cleanup = new ASU_Cleanup();
		$cleanup->remove_unused_themes( $result );

		Assert::true( isset( ASU_Fake_WP::$themes['astra-child'] ), 'Das aktive Theme darf nie geloescht werden.' );
		Assert::true( isset( ASU_Fake_WP::$themes['astra'] ), 'Das Parent des aktiven Themes darf nie geloescht werden.' );
		Assert::false( isset( ASU_Fake_WP::$themes['twentytwentyfour'] ), 'Ein unbeteiligtes Theme darf trotzdem weg.' );
	}
);

test(
	'Themes: ein fehlgeschlagenes Loeschen landet als WP_Error im Protokoll',
	function () {
		ASU_Fake_WP::add_hello_elementor();
		ASU_Fake_WP::add_theme( 'twentytwentyfour' );
		ASU_Fake_WP::$options['stylesheet']   = 'hello-elementor';
		ASU_Fake_WP::$delete_theme_returns    = array(
			'twentytwentyfour' => new WP_Error( 'fs_unavailable', 'Kein Schreibzugriff auf das Dateisystem.' ),
		);

		$result  = new ASU_Result();
		$cleanup = new ASU_Cleanup();
		$cleanup->remove_unused_themes( $result );

		Assert::true( $result->has_failures(), 'WP_Error ist keine Exception, muss aber trotzdem auffallen.' );

		$failures = $result->failures();

		Assert::same( 1, count( $failures ), 'Ein Problem ergibt genau eine Zeile in der Meldung, nicht zwei.' );
		Assert::text_contains( 'Kein Schreibzugriff', $failures[0]['detail'], 'Der Grund von WordPress gehoert in die Meldung.' );
	}
);

test(
	'Themes: ein stiller Fehlschlag ohne WP_Error wird trotzdem gemeldet',
	function () {
		ASU_Fake_WP::add_hello_elementor();
		ASU_Fake_WP::add_theme( 'twentytwentyfour' );
		ASU_Fake_WP::$options['stylesheet'] = 'hello-elementor';

		// delete_theme() liefert auch false oder null, ohne einen Grund zu nennen.
		ASU_Fake_WP::$delete_theme_returns = array( 'twentytwentyfour' => false );

		$result  = new ASU_Result();
		$cleanup = new ASU_Cleanup();
		$cleanup->remove_unused_themes( $result );

		Assert::true( $result->has_failures(), 'Auch ohne Begruendung darf das nicht als Erfolg durchgehen.' );
		Assert::same( 1, count( $result->failures() ), 'Genau eine Zeile.' );
	}
);

test(
	'Plugins: loescht nur Hello Dolly und Akismet',
	function () {
		ASU_Fake_WP::$plugins = array(
			'hello.php'            => array( 'Name' => 'Hello Dolly' ),
			'akismet/akismet.php'  => array( 'Name' => 'Akismet' ),
			'elementor/elementor.php' => array( 'Name' => 'Elementor' ),
		);

		$result  = new ASU_Result();
		$cleanup = new ASU_Cleanup();
		$cleanup->remove_unused_plugins( $result );

		Assert::same( array( 'elementor/elementor.php' ), array_keys( ASU_Fake_WP::$plugins ), 'Elementor muss bleiben.' );
		Assert::contains( 'hello.php', ASU_Fake_WP::$deactivated_plugins, 'Vor dem Loeschen wird deaktiviert.' );
		Assert::false( $result->has_failures(), 'Ein sauberer Lauf darf keinen Fehler melden.' );
	}
);

test(
	'Plugins: loescht sich niemals selbst',
	function () {
		// Absicherung fuer den Fall, dass jemand die Liste erweitert.
		$own = 'auto-cleanup-wp/auto-setup.php';

		$reflection = new ReflectionClass( 'ASU_Cleanup' );
		$constants  = $reflection->getConstant( 'REMOVE_PLUGINS' );

		Assert::missing( $own, $constants, 'Das eigene Plugin darf nicht auf der Liste stehen.' );

		ASU_Fake_WP::$plugins = array(
			$own        => array( 'Name' => 'Auto Cleanup WP' ),
			'hello.php' => array( 'Name' => 'Hello Dolly' ),
		);

		$cleanup = new ASU_Cleanup( $own );
		$cleanup->remove_unused_plugins( new ASU_Result() );

		Assert::missing( $own, ASU_Fake_WP::$deleted_plugins, 'Das eigene Plugin darf nie geloescht werden.' );
	}
);

test(
	'Plugins: ohne Hello Dolly und Akismet passiert nichts, und das ist kein Fehler',
	function () {
		ASU_Fake_WP::$plugins = array( 'elementor/elementor.php' => array( 'Name' => 'Elementor' ) );

		$result  = new ASU_Result();
		$cleanup = new ASU_Cleanup();
		$cleanup->remove_unused_plugins( $result );

		Assert::same( array(), ASU_Fake_WP::$deleted_plugins, 'Es gibt nichts zu loeschen.' );
		Assert::false( $result->has_failures(), 'Nichts zu tun ist kein Fehler.' );
	}
);

test(
	'Plugins: ein WP_Error beim Loeschen landet im Protokoll',
	function () {
		ASU_Fake_WP::$plugins                 = array( 'hello.php' => array( 'Name' => 'Hello Dolly' ) );
		ASU_Fake_WP::$delete_plugins_returns  = new WP_Error( 'could_not_remove_plugin', 'Konnte nicht entfernt werden.' );

		$result  = new ASU_Result();
		$cleanup = new ASU_Cleanup();
		$cleanup->remove_unused_plugins( $result );

		Assert::true( $result->has_failures(), 'Ein WP_Error muss auffallen.' );
	}
);

test(
	'Themes: ein fremdes Theme im Ordner "hello" wird nicht fuer Hello Elementor gehalten',
	function () {
		// Sonst waere darauf umgeschaltet und das echte aktive Theme geloescht worden.
		ASU_Fake_WP::add_theme( 'hello', '', 'Hello World Blog' );
		ASU_Fake_WP::add_theme( 'astra' );
		ASU_Fake_WP::$options['stylesheet'] = 'astra';

		$result  = new ASU_Result();
		$cleanup = new ASU_Cleanup();
		$cleanup->remove_unused_themes( $result );

		Assert::same( 'astra', get_option( 'stylesheet' ), 'Es darf nicht auf ein fremdes Theme umgeschaltet werden.' );
		Assert::true( isset( ASU_Fake_WP::$themes['astra'] ), 'Das aktive Theme bleibt.' );
	}
);

test(
	'Themes: nach einem fehlgeschlagenen Wechsel wird gar nichts geloescht',
	function () {
		// Wenn unklar ist, welches Theme WordPress fuer aktiv haelt, ist Loeschen
		// zu riskant. Ein falsch geloeschtes Theme kommt nicht zurueck.
		ASU_Fake_WP::add_hello_elementor();
		ASU_Fake_WP::add_theme( 'astra' );
		ASU_Fake_WP::add_theme( 'twentytwentyfour' );
		ASU_Fake_WP::$options['stylesheet'] = 'astra';
		ASU_Fake_WP::$switch_theme_works    = false;

		$result  = new ASU_Result();
		$cleanup = new ASU_Cleanup();
		$cleanup->remove_unused_themes( $result );

		Assert::same( array(), ASU_Fake_WP::$deleted_themes, 'Im unklaren Zustand wird nichts geloescht.' );
		Assert::true( $result->has_failures(), 'Und der Grund steht im Protokoll.' );
	}
);

test(
	'Inhalte: erwischt auch Seiten mit einem eigenen Post-Status aus einem Plugin',
	function () {
		ASU_Fake_WP::$extra_post_stati = array( 'wc-completed', 'in-pruefung' );

		ASU_Fake_WP::add_post( 'page', 'in-pruefung' );
		ASU_Fake_WP::add_post( 'post', 'publish' );

		$result  = new ASU_Result();
		$cleanup = new ASU_Cleanup();
		$cleanup->delete_all_posts_and_pages( $result );

		Assert::same( array(), ASU_Fake_WP::$posts, 'Ein eigener Status darf nichts durchrutschen lassen.' );
	}
);
