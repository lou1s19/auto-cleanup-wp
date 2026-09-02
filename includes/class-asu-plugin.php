<?php
/**
 * Der Ablauf des Plugins. Hier steht, was wann passiert.
 * Die eigentliche Arbeit machen die anderen Klassen, die einander nicht kennen.
 *
 * @package AutoCleanupWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ASU_Plugin {

	/** Protokoll des Setups. Das Einzige, was den Aktivierungs-Aufruf überlebt. */
	const OPTION_RESULT = 'asu_setup_result';

	/**
	 * Wann das Setup gelaufen ist. Bleibt absichtlich für immer stehen, auch
	 * nachdem sich das Plugin deaktiviert hat. Ohne diese Notiz würde eine
	 * versehentliche zweite Aktivierung Monate später alle Inhalte löschen,
	 * die inzwischen entstanden sind.
	 */
	const OPTION_RAN = 'asu_setup_ran';

	/**
	 * Ab so vielen vorhandenen Beitraegen und Seiten gilt die Website nicht mehr als frisch.
	 * Eine neue WordPress-Installation bringt genau zwei mit ("Hallo Welt" und die
	 * Beispielseite), dazu kommt oft eine Datenschutz-Vorlage im Entwurf.
	 */
	const MAX_EXISTING_CONTENT = 5;

	/** Konstante in der wp-config.php, mit der sich die Frische-Pruefung uebergehen laesst. */
	const OVERRIDE_CONSTANT = 'ASU_ALLOW_ON_EXISTING_SITE';

	/** @var string Pfad zur Hauptdatei des Plugins. */
	private $file;

	/** @var ASU_Cleanup */
	private $cleanup;

	/** @var ASU_Site_Setup */
	private $site_setup;

	/** @var ASU_Elementor */
	private $elementor;

	/** @var ASU_Result|null Protokoll des letzten Laufs, sobald es gelesen wurde. */
	private $result = null;

	/**
	 * Baut das Plugin zusammen und meldet es bei WordPress an.
	 *
	 * @param string $file Pfad zur Hauptdatei des Plugins.
	 * @return ASU_Plugin
	 */
	public static function boot( $file ) {
		$plugin = new self( $file );
		$plugin->register();

		return $plugin;
	}

	/**
	 * Die Bausteine dürfen hereingereicht werden, damit die Tests eigene
	 * einsetzen können. Im Normalbetrieb baut das Plugin sie selbst.
	 *
	 * @param string              $file       Pfad zur Hauptdatei des Plugins.
	 * @param ASU_Cleanup|null    $cleanup    Aufräumen.
	 * @param ASU_Site_Setup|null $site_setup Grundeinstellungen.
	 * @param ASU_Elementor|null  $elementor  Elementor-Wissen.
	 */
	public function __construct( $file, ?ASU_Cleanup $cleanup = null, ?ASU_Site_Setup $site_setup = null, ?ASU_Elementor $elementor = null ) {
		$this->file       = (string) $file;
		$this->cleanup    = $cleanup ? $cleanup : new ASU_Cleanup( plugin_basename( $this->file ) );
		$this->site_setup = $site_setup ? $site_setup : new ASU_Site_Setup();
		$this->elementor  = $elementor ? $elementor : new ASU_Elementor();
	}

	/**
	 * Sagt WordPress, wann es uns aufrufen soll. Genau zwei Einstiegspunkte.
	 *
	 * @return void
	 */
	public function register() {
		// Einmalig, beim Klick auf "Aktivieren".
		register_activation_hook( $this->file, array( $this, 'run_setup' ) );

		// Beim nächsten Aufruf einer Backend-Seite.
		add_action( 'admin_init', array( $this, 'finish' ) );
	}

	/**
	 * Das komplette Setup. Läuft einmal, von oben nach unten.
	 *
	 * @param bool $network_wide True, wenn im Netzwerk aktiviert wurde.
	 * @return ASU_Result
	 */
	public function run_setup( $network_wide = false ) {
		$result = new ASU_Result();

		if ( $this->is_multisite( $network_wide ) ) {
			// delete_theme() und delete_plugins() löschen Dateien, die sich alle
			// Sites eines Netzwerks teilen. Ein Setup für eine Site darf das nicht.
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

		// Der Merker steht bewusst VOR dem Loeschen. Bricht PHP mitten im Lauf hart ab
		// (Speicher, max_execution_time), waere er sonst nie geschrieben worden. Der Admin
		// sieht dann "es ist nichts passiert", aktiviert erneut, und der Rest der Inhalte
		// waere weg. Genau der gefaehrliche Fall, eine grosse Website, ist auch der, in dem
		// so ein Abbruch am wahrscheinlichsten ist.
		update_option( self::OPTION_RAN, gmdate( 'Y-m-d H:i' ) . ' UTC', false );

		// Ab hier wird geloescht.
		try {
			$this->cleanup->delete_all_posts_and_pages( $result );

			$this->site_setup->create_static_home_page( $result, $this->elementor->page_template() );
			$this->site_setup->set_permalink_structure( $result );
			$this->site_setup->discourage_search_engines( $result );

			$this->cleanup->remove_unused_themes( $result );
			$this->cleanup->remove_unused_plugins( $result );

			$this->elementor->enable_containers( $result );
		} catch ( \Throwable $e ) {
			// Der Ablauf löscht endgültig. Bricht er in der Mitte ab, muss das
			// Protokoll trotzdem geschrieben werden, sonst weiss niemand, wie
			// weit er gekommen ist. Throwable statt Exception, damit auch
			// PHP-Fehler wie TypeError hier landen.
			$result->fail( 'abbruch', sprintf( 'Unerwarteter Fehler: %s', $e->getMessage() ) );
		}

		return $this->store( $result );
	}

	/**
	 * Aufräumen nach dem Setup: Plugin abschalten, Meldung zeigen.
	 *
	 * Warum nicht direkt in run_setup()? Weil ein Plugin sich nicht selbst
	 * abschalten kann, während WordPress es gerade einschaltet. Deshalb der
	 * Umweg über das Protokoll und den nächsten Seitenaufruf.
	 *
	 * @return void
	 */
	public function finish() {
		// admin_init feuert auch auf admin-ajax.php, im Cron und bei REST-Aufrufen.
		// Würde das Protokoll dort verbraucht, sähe der Mensch die Meldung nie.
		if ( $this->is_background_request() ) {
			return;
		}

		// Dasselbe gilt für jeden angemeldeten Nutzer ohne Adminrechte: Auch ein
		// Abonnent löst beim Aufruf seines Profils admin_init aus. Ohne diese
		// Prüfung würde er das Protokoll verbrauchen und die Meldung, die er
		// ohnehin nicht sehen darf, wäre für alle weg.
		if ( ! current_user_can( 'activate_plugins' ) ) {
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

		// Die Meldung kommt später im selben Seitenaufruf.
		add_action( 'admin_notices', array( $this, 'show_notice' ) );
	}

	/**
	 * Meldung im Backend. Ohne sie sähe es aus, als hätte das Aktivieren nicht
	 * funktioniert, weil das Plugin danach wieder auf "inaktiv" steht.
	 *
	 * Fehlgeschlagene Schritte werden benannt. Eine grüne Meldung erscheint nur,
	 * wenn wirklich alles geklappt hat.
	 *
	 * @return void
	 */
	public function show_notice() {
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

	/**
	 * Zaehlt, was schon auf der Website liegt.
	 *
	 * @return int
	 */
	private function existing_content_count() {
		$count = 0;

		foreach ( array( 'post', 'page' ) as $type ) {
			$counts = wp_count_posts( $type );

			if ( ! is_object( $counts ) ) {
				continue;
			}

			foreach ( get_object_vars( $counts ) as $status => $number ) {
				if ( 'auto-draft' === $status ) {
					continue;
				}

				$count += (int) $number;
			}
		}

		return $count;
	}

	/**
	 * @return bool True, wenn der Betreiber das Aufraeumen ausdruecklich erlaubt hat.
	 */
	private function override_confirmed() {
		return defined( self::OVERRIDE_CONSTANT ) && constant( self::OVERRIDE_CONSTANT );
	}

	/**
	 * Legt das Protokoll ab, damit der nächste Seitenaufruf es findet.
	 *
	 * @param ASU_Result $result Protokoll des Laufs.
	 * @return ASU_Result Dasselbe Protokoll, zur Weiterverwendung in den Tests.
	 */
	private function store( ASU_Result $result ) {
		// false = nicht autoladen. Der Wert wird genau einmal gebraucht.
		update_option( self::OPTION_RESULT, $result->to_array(), false );

		return $result;
	}

	/**
	 * @param bool $network_wide Was register_activation_hook gemeldet hat.
	 * @return bool
	 */
	private function is_multisite( $network_wide ) {
		if ( $network_wide ) {
			return true;
		}

		return function_exists( 'is_multisite' ) && is_multisite();
	}

	/**
	 * @return bool True, wenn hinter dem Aufruf kein Mensch im Backend sitzt.
	 */
	private function is_background_request() {
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
