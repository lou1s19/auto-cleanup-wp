<?php
/**
 * Aufräumen: Standard-Inhalte, überflüssige Themes und überflüssige Plugins entfernen.
 *
 * ACHTUNG: Diese Klasse löscht endgültig. Nur auf frischen Installationen einsetzen.
 *
 * @package AutoCleanupWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ASU_Cleanup {

	/** Themes, die erhalten bleiben (Hello Elementor und passende Child-Themes). */
	const KEEP_STYLESHEETS = array( 'hello-elementor', 'hello', 'hello-child', 'hello-elementor-child' );

	/** Plugins, die entfernt werden. */
	const REMOVE_PLUGINS = array( 'hello.php', 'akismet/akismet.php' );

	/** Post-Status, die beim Löschen erfasst werden. */
	const POST_STATUSES = array( 'publish', 'draft', 'auto-draft', 'pending', 'future', 'private', 'trash' );

	/** @var string Eigener Plugin-Pfad, z. B. auto-cleanup-wp/auto-setup.php. Wird nie gelöscht. */
	private $own_plugin;

	/**
	 * @param string $own_plugin Ergebnis von plugin_basename() für dieses Plugin.
	 */
	public function __construct( $own_plugin = '' ) {
		$this->own_plugin = (string) $own_plugin;
	}

	/**
	 * Löscht alle Beiträge und Seiten, inklusive Entwürfen, Auto-Entwürfen und Papierkorb.
	 * Entfernt damit auch den Standard-Beitrag und die Standard-Seite von WordPress.
	 *
	 * @param ASU_Result $result Protokoll des Laufs.
	 * @return void
	 */
	public function delete_all_posts_and_pages( ASU_Result $result ) {
		$ids = get_posts(
			array(
				'post_type'   => array( 'post', 'page' ),
				'numberposts' => -1,
				'post_status' => $this->post_statuses(),
				'fields'      => 'ids',
			)
		);

		if ( ! is_array( $ids ) || array() === $ids ) {
			$result->skip( 'inhalte', 'Es gab keine Beiträge oder Seiten zum Löschen.' );

			return;
		}

		$deleted = 0;
		$failed  = 0;

		foreach ( $ids as $id ) {
			// true = endgültig löschen, nicht in den Papierkorb.
			if ( wp_delete_post( $id, true ) ) {
				++$deleted;
			} else {
				++$failed;
			}
		}

		if ( $failed > 0 ) {
			$result->fail(
				'inhalte',
				sprintf( '%d von %d Beiträgen und Seiten liessen sich nicht löschen.', $failed, count( $ids ) )
			);

			return;
		}

		$result->ok( 'inhalte', sprintf( '%d Beiträge und Seiten gelöscht.', $deleted ) );
	}

	/**
	 * Löscht alle Themes ausser Hello Elementor.
	 * Ist ein anderes Theme aktiv, wird vorher auf Hello Elementor umgeschaltet.
	 * Klappt das nicht, weil Hello Elementor fehlt, bleiben das aktive Theme UND
	 * dessen Parent stehen. Sonst würde bei einem aktiven Child-Theme das Parent
	 * gelöscht und die Website wäre weiss.
	 *
	 * @param ASU_Result $result Protokoll des Laufs.
	 * @return void
	 */
	public function remove_unused_themes( ASU_Result $result ) {
		$this->load_theme_functions();

		if ( ! function_exists( 'wp_get_themes' ) || ! function_exists( 'delete_theme' ) ) {
			$result->fail( 'themes', 'Die Theme-Funktionen von WordPress stehen nicht zur Verfügung.' );

			return;
		}

		if ( ! $this->switch_to_hello( $result ) ) {
			// Der Wechsel wurde versucht und ging schief. In diesem Zustand wird
			// nichts gelöscht: Welches Theme WordPress jetzt für aktiv hält, ist
			// unklar, und ein falsch gelöschtes Theme ist nicht rückholbar.
			$result->fail( 'themes', 'Wegen des fehlgeschlagenen Theme-Wechsels wurde kein Theme gelöscht.' );

			return;
		}

		$protected = $this->protected_stylesheets();
		$deleted   = 0;
		$failed    = 0;
		$silent    = array();

		foreach ( wp_get_themes() as $stylesheet => $theme ) {
			if ( $this->is_protected( $stylesheet, $theme, $protected ) ) {
				continue;
			}

			$deletion = delete_theme( $stylesheet );

			// Ein WP_Error nennt den Grund und steht damit schon im Protokoll.
			if ( $result->catch_wp_error( 'themes', $deletion, sprintf( 'Theme %s', $stylesheet ) ) ) {
				++$failed;
				continue;
			}

			// delete_theme() liefert true, false oder null. Alles ausser true ist
			// ein Fehlschlag, nur ohne Begründung. Die kommen gesammelt.
			if ( true !== $deletion ) {
				++$failed;
				$silent[] = $stylesheet;
				continue;
			}

			++$deleted;
		}

		if ( array() !== $silent ) {
			$result->fail(
				'themes',
				sprintf( 'Diese Themes liessen sich ohne Angabe eines Grundes nicht löschen: %s.', implode( ', ', $silent ) )
			);
		}

		if ( 0 === $failed ) {
			$result->ok( 'themes', sprintf( '%d überflüssige Themes gelöscht.', $deleted ) );
		}
	}

	/**
	 * Deaktiviert und löscht die WordPress-Standard-Plugins Hello Dolly und Akismet.
	 *
	 * @param ASU_Result $result Protokoll des Laufs.
	 * @return void
	 */
	public function remove_unused_plugins( ASU_Result $result ) {
		$this->load_plugin_functions();

		if ( ! function_exists( 'get_plugins' ) || ! function_exists( 'delete_plugins' ) ) {
			$result->fail( 'plugins', 'Die Plugin-Funktionen von WordPress stehen nicht zur Verfügung.' );

			return;
		}

		$targets = $this->plugins_to_remove();

		if ( array() === $targets ) {
			$result->skip( 'plugins', 'Hello Dolly und Akismet waren nicht installiert.' );

			return;
		}

		// Erst deaktivieren, dann löschen. true = ohne Deaktivierungs-Hooks.
		if ( function_exists( 'deactivate_plugins' ) ) {
			deactivate_plugins( $targets, true );
		}

		$deletion = delete_plugins( $targets );

		if ( $result->catch_wp_error( 'plugins', $deletion, 'Plugins löschen' ) ) {
			return;
		}

		if ( true !== $deletion ) {
			$result->fail(
				'plugins',
				sprintf( 'Diese Plugins liessen sich nicht löschen: %s.', implode( ', ', $targets ) )
			);

			return;
		}

		$result->ok( 'plugins', sprintf( 'Gelöscht: %s.', implode( ', ', $targets ) ) );
	}

	/**
	 * Alle Post-Status, nach denen gesucht wird.
	 *
	 * Die feste Liste deckt WordPress selbst ab. Plugins dürfen aber eigene
	 * Status anmelden, und Seiten mit so einem Status wären sonst durchgerutscht
	 * und später wieder aufgetaucht. Deshalb werden alle angemeldeten Status
	 * dazugenommen.
	 *
	 * @return array<int, string>
	 */
	private function post_statuses() {
		$statuses = self::POST_STATUSES;

		if ( function_exists( 'get_post_stati' ) ) {
			$registered = get_post_stati();

			if ( is_array( $registered ) ) {
				$statuses = array_merge( $statuses, array_values( $registered ) );
			}
		}

		return array_values( array_unique( $statuses ) );
	}

	/**
	 * Die installierten Plugins, die auf der Abschussliste stehen.
	 * Das eigene Plugin ist nie dabei, auch wenn jemand die Liste erweitert.
	 *
	 * @return array<int, string>
	 */
	private function plugins_to_remove() {
		$installed = get_plugins();

		if ( ! is_array( $installed ) ) {
			return array();
		}

		$targets = array_intersect( array_keys( $installed ), self::REMOVE_PLUGINS );

		if ( '' !== $this->own_plugin ) {
			$targets = array_diff( $targets, array( $this->own_plugin ) );
		}

		return array_values( $targets );
	}

	/**
	 * Schaltet auf Hello Elementor um, damit das bisher aktive Theme gelöscht werden darf.
	 *
	 * @param ASU_Result $result Protokoll des Laufs.
	 * @return bool False nur, wenn ein Wechsel versucht wurde und misslang.
	 */
	private function switch_to_hello( ASU_Result $result ) {
		$hello = $this->find_hello_stylesheet();

		if ( '' === $hello ) {
			$result->skip(
				'theme-wechsel',
				'Hello Elementor ist nicht installiert. Das aktive Theme und sein Parent bleiben stehen.'
			);

			return true;
		}

		if ( get_option( 'stylesheet' ) === $hello ) {
			return true;
		}

		if ( ! function_exists( 'switch_theme' ) ) {
			$result->fail( 'theme-wechsel', 'switch_theme() steht nicht zur Verfügung.' );

			return false;
		}

		// switch_theme() gibt nichts zurück, also wird das Ergebnis nachgelesen.
		switch_theme( $hello );

		if ( get_option( 'stylesheet' ) !== $hello ) {
			$result->fail( 'theme-wechsel', sprintf( 'Umschalten auf %s hat nicht funktioniert.', $hello ) );

			return false;
		}

		$result->ok( 'theme-wechsel', sprintf( 'Auf %s umgeschaltet.', $hello ) );

		return true;
	}

	/**
	 * Stylesheets, die auf keinen Fall gelöscht werden dürfen: die Hello-Familie,
	 * das aktive Theme und, falls ein Child-Theme aktiv ist, dessen Parent.
	 *
	 * @return array<int, string>
	 */
	private function protected_stylesheets() {
		$protected = self::KEEP_STYLESHEETS;

		if ( function_exists( 'wp_get_theme' ) ) {
			$active = wp_get_theme();

			if ( $active && $active->exists() ) {
				$protected[] = $active->get_stylesheet();
				$protected[] = $active->get_template();
			}
		}

		return array_values( array_unique( array_filter( $protected ) ) );
	}

	/**
	 * Behalten wird ein Theme, wenn es selbst geschützt ist oder wenn es auf einem
	 * Theme der Hello-Familie aufbaut.
	 *
	 * @param string          $stylesheet Verzeichnisname des Themes.
	 * @param WP_Theme|object $theme      Theme-Objekt von wp_get_themes().
	 * @param array<int, string> $protected Geschützte Stylesheets.
	 * @return bool
	 */
	private function is_protected( $stylesheet, $theme, array $protected ) {
		if ( in_array( $stylesheet, $protected, true ) ) {
			return true;
		}

		$template = method_exists( $theme, 'get_template' ) ? $theme->get_template() : '';

		return in_array( $template, self::KEEP_STYLESHEETS, true );
	}

	/**
	 * Sucht das installierte Hello Elementor. Bevorzugt den Ordner "hello-elementor".
	 *
	 * Der Ordnername allein reicht nicht als Beweis. Ein fremdes Theme in einem
	 * Ordner namens "hello" wäre sonst für Hello Elementor gehalten worden, das
	 * Setup hätte darauf umgeschaltet und danach das eigentliche Theme endgültig
	 * gelöscht. Deshalb wird zusätzlich der Theme-Name geprüft.
	 *
	 * @return string Verzeichnisname des Themes, leer wenn keines gefunden wurde.
	 */
	private function find_hello_stylesheet() {
		if ( ! function_exists( 'wp_get_theme' ) ) {
			return '';
		}

		foreach ( array( 'hello-elementor', 'hello' ) as $candidate ) {
			$theme = wp_get_theme( $candidate );

			if ( $theme && $theme->exists() && $this->is_hello_elementor( $theme ) ) {
				return $candidate;
			}
		}

		return '';
	}

	/**
	 * @param WP_Theme|object $theme Theme-Objekt.
	 * @return bool True, wenn das Theme sich selbst Hello Elementor nennt.
	 */
	private function is_hello_elementor( $theme ) {
		if ( ! method_exists( $theme, 'get' ) ) {
			return false;
		}

		return 0 === stripos( (string) $theme->get( 'Name' ), 'hello elementor' );
	}

	/**
	 * WordPress lädt nicht seinen ganzen Code bei jedem Seitenaufruf.
	 * Funktionen wie wp_get_themes() oder delete_theme() liegen in Dateien, die
	 * beim Aktivieren eines Plugins noch nicht geladen sind. Würde man sie
	 * einfach aufrufen, bricht PHP mit einem Fehler ab. Also erst die Datei
	 * nachladen, dann benutzen.
	 *
	 * @return void
	 */
	private function load_theme_functions() {
		$this->load_once(
			ABSPATH . 'wp-includes/theme.php',
			array( 'wp_get_themes', 'switch_theme' )
		);

		$this->load_once(
			ABSPATH . 'wp-admin/includes/theme.php',
			array( 'delete_theme' )
		);
	}

	/**
	 * Dasselbe für get_plugins(), deactivate_plugins() und delete_plugins().
	 * delete_plugins() braucht zusätzlich die Dateisystem-Funktionen aus file.php,
	 * die bei einem Aufruf ausserhalb des Backends fehlen können.
	 *
	 * @return void
	 */
	private function load_plugin_functions() {
		$this->load_once(
			ABSPATH . 'wp-admin/includes/plugin.php',
			array( 'get_plugins', 'deactivate_plugins', 'delete_plugins' )
		);

		$this->load_once(
			ABSPATH . 'wp-admin/includes/file.php',
			array( 'WP_Filesystem' )
		);
	}

	/**
	 * Lädt eine WordPress-Datei nach, wenn eine der genannten Funktionen fehlt.
	 *
	 * @param string             $file      Vollständiger Pfad.
	 * @param array<int, string> $functions Funktionen, die danach da sein sollen.
	 * @return void
	 */
	private function load_once( $file, array $functions ) {
		foreach ( $functions as $function ) {
			if ( ! function_exists( $function ) ) {
				if ( file_exists( $file ) ) {
					require_once $file;
				}

				return;
			}
		}
	}
}
