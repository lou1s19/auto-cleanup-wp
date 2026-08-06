<?php
/**
 * Die Hauptklasse. Sie enthält selbst keine Logik, sondern steuert nur den Ablauf:
 * sie meldet die WordPress-Hooks an und ruft in der richtigen Reihenfolge
 * die zuständigen Klassen auf.
 */

if ( ! defined('ABSPATH') ) { exit; }

class ASU_Plugin {

	/** @var ASU_Cleanup */
	private $cleanup;

	/** @var ASU_Site_Setup */
	private $site_setup;

	/** @var ASU_Elementor_Container */
	private $container;

	/** @var ASU_Theme_Builder */
	private $theme_builder;

	/** @var ASU_Admin_Notices */
	private $notices;

	public function __construct() {
		$this->cleanup       = new ASU_Cleanup();
		$this->site_setup    = new ASU_Site_Setup();
		$this->container     = new ASU_Elementor_Container();
		$this->theme_builder = new ASU_Theme_Builder();
		$this->notices       = new ASU_Admin_Notices();
	}

	/**
	 * Meldet alle Hooks bei WordPress an.
	 * Ein Hook ist ein Zeitpunkt, an dem WordPress unsere Funktion aufruft.
	 */
	public function register() {
		// Läuft genau einmal: beim Aktivieren des Plugins.
		register_activation_hook(ASU_PLUGIN_FILE, [ $this, 'run_setup' ]);

		// Elementor ist nicht bei jedem Ladevorgang gleich früh da.
		// Deshalb wird an drei Stellen versucht, den Container zu aktivieren.
		add_action('elementor/init', [ $this, 'ensure_container_active' ], 999);
		add_action('plugins_loaded', [ $this, 'ensure_container_active' ], 999);
		add_action('init', [ $this, 'ensure_container_active' ], 1);

		// Im Adminbereich ist alles vollständig geladen. Erst hier wird geprüft,
		// ob es geklappt hat, und das Plugin schaltet sich selbst ab.
		add_action('admin_init', [ $this, 'finish_setup' ], 1);

		add_action('admin_notices', [ $this->notices, 'render' ]);
	}

	/**
	 * Das einmalige Setup beim Aktivieren.
	 * Reihenfolge ist wichtig: erst aufräumen, dann neu aufbauen.
	 */
	public function run_setup() {
		// 1. Alte Inhalte weg.
		$this->cleanup->delete_all_posts_and_pages();

		// 2. Grundeinstellungen setzen.
		$this->site_setup->create_static_home_page();
		$this->site_setup->set_permalink_structure();
		$this->site_setup->discourage_search_engines();

		// 3. Überflüssige Themes und Plugins entfernen.
		$this->cleanup->remove_unused_themes();
		$this->cleanup->remove_unused_plugins();

		// 4. Header und Footer im Elementor Pro Theme Builder anlegen.
		$this->theme_builder->create_header_and_footer();

		// Merken: Grund-Setup fertig, Container steht noch aus.
		update_option(ASU_Options::BASE_DONE, 1);
		update_option(ASU_Options::CONTAINER_PENDING, 1);
	}

	/**
	 * Versucht, den Elementor-Container zu aktivieren, solange das noch offen ist.
	 */
	public function ensure_container_active() {
		if ( ! get_option(ASU_Options::CONTAINER_PENDING) || get_option(ASU_Options::CONTAINER_OK) ) {
			return;
		}

		$this->container->activate();
	}

	/**
	 * Läuft im Adminbereich: letzter Aktivierungsversuch, danach Erfolgskontrolle.
	 * Hat es geklappt, deaktiviert sich das Plugin selbst.
	 */
	public function finish_setup() {
		$this->ensure_container_active();

		if ( ! $this->container->is_active() ) {
			return;
		}

		update_option(ASU_Options::CONTAINER_OK, 1);
		delete_option(ASU_Options::CONTAINER_PENDING);

		ASU_Wp_Admin::load_plugin_functions();
		if ( function_exists('deactivate_plugins') ) {
			deactivate_plugins(plugin_basename(ASU_PLUGIN_FILE));
		}
	}
}
