<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ASU_Plugin {

	const OPTION_RESULT = 'asu_setup_result';

	// Bleibt für immer stehen, auch nach der Selbstdeaktivierung. Sonst würde
	// eine versehentliche zweite Aktivierung Monate später alles löschen, was
	// inzwischen entstanden ist.
	const OPTION_RAN = 'asu_setup_ran';

	// Eine frische Installation bringt zwei Inhalte mit, dazu oft eine
	// Datenschutz-Vorlage im Entwurf.
	const MAX_EXISTING_CONTENT = 5;

	const OVERRIDE_CONSTANT = 'ASU_ALLOW_ON_EXISTING_SITE';

	private string $file;

	private ASU_Cleanup $cleanup;

	private ASU_Site_Setup $site_setup;

	private ASU_Elementor $elementor;

	private ?ASU_Result $result = null;

	public static function boot( string $file ): self {
		$plugin = new self( $file );
		$plugin->register();

		return $plugin;
	}

	// Die Bausteine dürfen hereingereicht werden, damit die Tests eigene einsetzen können.
	public function __construct( string $file, ?ASU_Cleanup $cleanup = null, ?ASU_Site_Setup $site_setup = null, ?ASU_Elementor $elementor = null ) {
		$this->file       = $file;
		$this->cleanup    = $cleanup ?? new ASU_Cleanup( plugin_basename( $this->file ) );
		$this->site_setup = $site_setup ?? new ASU_Site_Setup();
		$this->elementor  = $elementor ?? new ASU_Elementor();
	}

	public function register(): void {
		register_activation_hook( $this->file, array( $this, 'run_setup' ) );
		add_action( 'admin_init', array( $this, 'finish' ) );
	}

	public function run_setup( bool $network_wide = false ): ASU_Result {
		$result = new ASU_Result();

		if ( $this->is_multisite( $network_wide ) ) {
			// delete_theme() und delete_plugins() löschen Dateien, die sich alle
			// Sites eines Netzwerks teilen.
			$result->fail(
				'multisite',
				'Abgebrochen: In einem Multisite-Netzwerk würden Themes und Plugins für alle Sites gelöscht. Es wurde nichts verändert.'
			);

			return $this->store( $result );
		}

		$ran_at = get_option( self::OPTION_RAN );

		if ( $ran_at ) {
			$result->fail(
				'wiederholung',
				sprintf(
					'Abgebrochen: Das Setup ist auf dieser Website schon einmal gelaufen (%s). Es wurde nichts verändert. Wer es wirklich erneut braucht, löscht vorher die Option %s.',
					$ran_at,
					self::OPTION_RAN
				)
			);

			return $this->store( $result );
		}

		$existing = $this->existing_content_count();

		if ( $existing > self::MAX_EXISTING_CONTENT && ! $this->override_confirmed() ) {
			$result->fail(
				'nicht-frisch',
				sprintf(
					'Abgebrochen: Auf dieser Website liegen %d Beitraege und Seiten, das sieht nicht nach einer frischen Installation aus. Es wurde nichts veraendert. Wer trotzdem aufraeumen will, setzt vorher define( \'%s\', true ); in die wp-config.php.',
					$existing,
					self::OVERRIDE_CONSTANT
				)
			);

			return $this->store( $result );
		}

		// Der Merker steht vor dem Löschen. Bricht PHP mitten im Lauf hart ab
		// (Speicher, max_execution_time), wäre er sonst nie geschrieben worden:
		// der Admin sieht "nichts passiert", aktiviert erneut und verliert den
		// Rest. Grosse Websites sind für beides der wahrscheinlichste Fall.
		update_option( self::OPTION_RAN, gmdate( 'Y-m-d H:i' ) . ' UTC', false );

		try {
			$this->cleanup->delete_all_posts_and_pages( $result );

			$this->site_setup->create_static_home_page( $result, $this->elementor->page_template() );
			$this->site_setup->set_permalink_structure( $result );
			$this->site_setup->discourage_search_engines( $result );

			$this->cleanup->remove_unused_themes( $result );
			$this->cleanup->remove_unused_plugins( $result );

			$this->elementor->enable_containers( $result );
		} catch ( \Throwable $e ) {
			// Throwable statt Exception, damit auch PHP-Fehler wie TypeError hier
			// landen und das Protokoll trotzdem geschrieben wird.
			$result->fail( 'abbruch', sprintf( 'Unerwarteter Fehler: %s', $e->getMessage() ) );
		}

		return $this->store( $result );
	}

	// Ein Plugin kann sich nicht selbst abschalten, während WordPress es gerade
	// einschaltet. Deshalb erst beim nächsten Backend-Aufruf.
	public function finish(): void {
		// admin_init feuert auch bei AJAX, Cron und REST. Würde das Protokoll dort
		// verbraucht, sähe der Mensch die Meldung nie. Dasselbe gilt für jeden
		// angemeldeten Nutzer ohne Adminrechte.
		if ( $this->is_background_request() || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$stored = get_option( self::OPTION_RESULT );

		if ( false === $stored ) {
			return;
		}

		delete_option( self::OPTION_RESULT );

		if ( function_exists( 'deactivate_plugins' ) ) {
			deactivate_plugins( plugin_basename( $this->file ) );
		}

		$this->result = ASU_Result::from_array( $stored );

		add_action( 'admin_notices', array( $this, 'show_notice' ) );
	}

	// Ohne Meldung sähe es aus, als hätte das Aktivieren nicht funktioniert,
	// weil das Plugin danach wieder auf "inaktiv" steht.
	public function show_notice(): void {
		if ( ! current_user_can( 'manage_options' ) || ! $this->result instanceof ASU_Result ) {
			return;
		}

		$failures = $this->result->failures();
		$class    = array() === $failures ? 'notice-success' : 'notice-warning';

		$summary = array() === $failures
			? __( 'Setup fertig. Startseite, Permalinks und Elementor-Container sind gesetzt.', 'auto-cleanup-wp' )
			: __( 'Setup gelaufen, aber nicht alles hat geklappt:', 'auto-cleanup-wp' );

		echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible">';
		echo '<p><strong>' . esc_html__( 'Auto Cleanup WP', 'auto-cleanup-wp' ) . ':</strong> ';
		echo esc_html( $summary ) . ' ';
		echo esc_html__( 'Das Plugin hat sich selbst deaktiviert.', 'auto-cleanup-wp' ) . '</p>';

		if ( array() !== $failures ) {
			echo '<ul class="ul-disc">';

			foreach ( $failures as $failure ) {
				echo '<li>' . esc_html( $failure['detail'] ) . '</li>';
			}

			echo '</ul>';
		}

		echo '</div>';
	}

	private function existing_content_count(): int {
		$count = 0;

		foreach ( array( 'post', 'page' ) as $type ) {
			$counts = wp_count_posts( $type );

			if ( ! is_object( $counts ) ) {
				continue;
			}

			foreach ( get_object_vars( $counts ) as $status => $number ) {
				// Auto-Entwürfe legt WordPress von selbst an, sie zählen nicht als Inhalt.
				if ( 'auto-draft' === $status ) {
					continue;
				}

				$count += (int) $number;
			}
		}

		return $count;
	}

	private function override_confirmed(): bool {
		return defined( self::OVERRIDE_CONSTANT ) && constant( self::OVERRIDE_CONSTANT );
	}

	private function store( ASU_Result $result ): ASU_Result {
		// false = nicht autoladen, der Wert wird genau einmal gebraucht.
		update_option( self::OPTION_RESULT, $result->to_array(), false );

		return $result;
	}

	private function is_multisite( bool $network_wide ): bool {
		if ( $network_wide ) {
			return true;
		}

		return function_exists( 'is_multisite' ) && is_multisite();
	}

	private function is_background_request(): bool {
		if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
			return true;
		}

		if ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) {
			return true;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}

		return defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE;
	}
}
