<?php
/**
 * Der Ablauf des Plugins. Hier steht, was wann passiert.
 * Die eigentliche Arbeit machen die drei anderen Klassen.
 */

if ( ! defined('ABSPATH') ) { exit; }

class ASU_Plugin {

	/** Notiz in der Datenbank: Setup ist gelaufen, das Plugin darf sich abschalten. */
	const SETUP_DONE = 'asu_setup_done';

	private $cleanup;
	private $site_setup;
	private $elementor;

	public function __construct() {
		$this->cleanup    = new ASU_Cleanup();
		$this->site_setup = new ASU_Site_Setup();
		$this->elementor  = new ASU_Elementor();
	}

	/**
	 * Sagt WordPress, wann es uns aufrufen soll.
	 */
	public function register() {
		// Einmalig, beim Klick auf "Aktivieren".
		register_activation_hook(ASU_PLUGIN_FILE, [ $this, 'run_setup' ]);

		// Beim nächsten Aufruf einer Backend-Seite.
		add_action('admin_init', [ $this, 'finish' ]);
	}

	/**
	 * Das komplette Setup. Läuft einmal, von oben nach unten.
	 */
	public function run_setup() {
		$this->cleanup->delete_all_posts_and_pages();

		$this->site_setup->create_static_home_page();
		$this->site_setup->set_permalink_structure();
		$this->site_setup->discourage_search_engines();

		$this->cleanup->remove_unused_themes();
		$this->cleanup->remove_unused_plugins();

		$this->elementor->enable_containers();

		// Notiz hinterlassen. Sie ist das Einzige, was diesen Seitenaufruf überlebt.
		update_option(self::SETUP_DONE, 1);
	}

	/**
	 * Aufräumen nach dem Setup: Erfolgsmeldung zeigen, Plugin abschalten.
	 *
	 * Warum nicht direkt oben in run_setup()? Weil ein Plugin sich nicht selbst
	 * abschalten kann, während WordPress es gerade einschaltet. Deshalb der Umweg
	 * über die Notiz und den nächsten Seitenaufruf.
	 */
	public function finish() {
		if ( ! get_option(self::SETUP_DONE) ) {
			return;
		}

		delete_option(self::SETUP_DONE);

		if ( function_exists('deactivate_plugins') ) {
			deactivate_plugins(plugin_basename(ASU_PLUGIN_FILE));
		}

		// Die Meldung kommt später im selben Seitenaufruf.
		add_action('admin_notices', [ $this, 'show_success' ]);
	}

	/**
	 * Grüne Meldung im Backend. Ohne sie sähe es aus, als hätte das Aktivieren
	 * nicht funktioniert, weil das Plugin danach wieder auf "inaktiv" steht.
	 */
	public function show_success() {
		if ( ! current_user_can('manage_options') ) {
			return;
		}

		echo '<div class="notice notice-success is-dismissible"><p><strong>Auto Cleanup WP:</strong> '
			. 'Setup fertig. Startseite, Permalinks und Elementor-Container sind gesetzt. '
			. 'Das Plugin hat sich selbst deaktiviert.</p></div>';
	}
}
