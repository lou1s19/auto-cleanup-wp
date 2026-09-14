<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ASU_Cleanup {

	const KEEP_STYLESHEETS = array( 'hello-elementor', 'hello', 'hello-child', 'hello-elementor-child' );

	const REMOVE_PLUGINS = array( 'hello.php', 'akismet/akismet.php' );

	const POST_STATUSES = array( 'publish', 'draft', 'auto-draft', 'pending', 'future', 'private', 'trash' );

	private string $own_plugin;

	public function __construct( string $own_plugin = '' ) {
		$this->own_plugin = $own_plugin;
	}

	public function delete_all_posts_and_pages( ASU_Result $result ): void {
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
			// true = endgültig, nicht in den Papierkorb.
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

	public function remove_unused_themes( ASU_Result $result ): void {
		$this->load_theme_functions();

		if ( ! function_exists( 'wp_get_themes' ) || ! function_exists( 'delete_theme' ) ) {
			$result->fail( 'themes', 'Die Theme-Funktionen von WordPress stehen nicht zur Verfügung.' );

			return;
		}

		if ( ! $this->switch_to_hello( $result ) ) {
			// Nach einem gescheiterten Wechsel ist unklar, welches Theme WordPress
			// für aktiv hält. Ein falsch gelöschtes Theme kommt nicht zurück.
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

			if ( $result->catch_wp_error( 'themes', $deletion, sprintf( 'Theme %s', $stylesheet ) ) ) {
				++$failed;
				continue;
			}

			// delete_theme() liefert true, false oder null. Alles ausser true ist
			// ein Fehlschlag, nur ohne Begründung.
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

	public function remove_unused_plugins( ASU_Result $result ): void {
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

		if ( function_exists( 'deactivate_plugins' ) ) {
			// true = ohne Deaktivierungs-Hooks, die Dateien verschwinden ohnehin gleich.
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

	// Plugins dürfen eigene Post-Status anmelden. Seiten mit so einem Status
	// blieben sonst liegen und tauchten später wieder auf.
	private function post_statuses(): array {
		$statuses = self::POST_STATUSES;

		if ( function_exists( 'get_post_stati' ) ) {
			$registered = get_post_stati();

			if ( is_array( $registered ) ) {
				$statuses = array_merge( $statuses, array_values( $registered ) );
			}
		}

		return array_values( array_unique( $statuses ) );
	}

	private function plugins_to_remove(): array {
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

	private function switch_to_hello( ASU_Result $result ): bool {
		$hello = $this->find_hello_stylesheet();

		if ( '' === $hello ) {
			// Ohne Ziel wird nicht gewechselt. Das aktive Theme und sein Parent
			// bleiben dann stehen, sonst wäre die Website nach dem Lauf weiss.
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

		switch_theme( $hello );

		// switch_theme() gibt nichts zurück, der Erfolg lässt sich nur nachlesen.
		if ( get_option( 'stylesheet' ) !== $hello ) {
			$result->fail( 'theme-wechsel', sprintf( 'Umschalten auf %s hat nicht funktioniert.', $hello ) );

			return false;
		}

		$result->ok( 'theme-wechsel', sprintf( 'Auf %s umgeschaltet.', $hello ) );

		return true;
	}

	private function protected_stylesheets(): array {
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

	private function is_protected( string $stylesheet, $theme, array $protected ): bool {
		if ( in_array( $stylesheet, $protected, true ) ) {
			return true;
		}

		$template = method_exists( $theme, 'get_template' ) ? $theme->get_template() : '';

		return in_array( $template, self::KEEP_STYLESHEETS, true );
	}

	private function find_hello_stylesheet(): string {
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

	// Der Ordnername ist kein Beweis. Läge dort ein fremdes Theme, würde das
	// Setup darauf umschalten und danach das echte aktive Theme löschen.
	private function is_hello_elementor( $theme ): bool {
		if ( ! method_exists( $theme, 'get' ) ) {
			return false;
		}

		return 0 === stripos( (string) $theme->get( 'Name' ), 'hello elementor' );
	}

	private function load_theme_functions(): void {
		$this->load_once( ABSPATH . 'wp-includes/theme.php', array( 'wp_get_themes', 'switch_theme' ) );
		$this->load_once( ABSPATH . 'wp-admin/includes/theme.php', array( 'delete_theme' ) );
	}

	private function load_plugin_functions(): void {
		$this->load_once( ABSPATH . 'wp-admin/includes/plugin.php', array( 'get_plugins', 'deactivate_plugins', 'delete_plugins' ) );

		// delete_plugins() braucht die Dateisystem-Funktionen, die ausserhalb des
		// Backends fehlen können.
		$this->load_once( ABSPATH . 'wp-admin/includes/file.php', array( 'WP_Filesystem' ) );
	}

	// Beim Aktivieren eines Plugins ist nur ein Teil von WordPress geladen. Ein
	// Aufruf einer noch fehlenden Funktion bricht PHP ab.
	private function load_once( string $file, array $functions ): void {
		foreach ( $functions as $function ) {
			if ( function_exists( $function ) ) {
				continue;
			}

			if ( file_exists( $file ) ) {
				require_once $file;
			}

			return;
		}
	}
}
