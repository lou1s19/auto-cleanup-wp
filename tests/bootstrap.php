<?php
/**
 * Attrappe von WordPress.
 *
 * Das Plugin hat bewusst keine Abhängigkeiten, also gibt es hier auch kein
 * PHPUnit und keine WordPress-Testsuite. Stattdessen wird genau der Teil von
 * WordPress nachgebaut, den das Plugin anfasst. Das reicht, um zu prüfen, was
 * hier wirklich zählt: welche Themes gelöscht werden, welche Optionen
 * geschrieben werden und was passiert, wenn WordPress einen Fehler meldet.
 *
 * @package AutoCleanupWP
 */

define( 'ABSPATH', __DIR__ . '/fake-wp/' );

/**
 * Der gesamte Zustand der Attrappe. Vor jedem Test zurückgesetzt.
 */
final class ASU_Fake_WP {

	/** @var array<string, mixed> */
	public static $options = array();

	/** @var array<int, array<string, string>> */
	public static $posts = array();

	/** @var array<int, array<string, mixed>> */
	public static $post_meta = array();

	/** @var array<string, ASU_Fake_Theme> */
	public static $themes = array();

	/** @var array<string, array<string, string>> */
	public static $plugins = array();

	/** @var array<int, string> */
	public static $deleted_themes = array();

	/** @var array<int, string> */
	public static $deleted_plugins = array();

	/** @var array<int, string> */
	public static $deactivated_plugins = array();

	/** @var array<int, int> */
	public static $deleted_posts = array();

	/** @var array<string, array<int, callable>> */
	public static $actions = array();

	/** @var array<string, callable> */
	public static $activation_hooks = array();

	/** @var bool */
	public static $is_multisite = false;

	/** @var bool */
	public static $doing_ajax = false;

	/** @var bool */
	public static $doing_cron = false;

	/** @var bool */
	public static $can_manage_options = true;

	/** @var int */
	public static $next_post_id = 1;

	/** @var bool Wurden die Rewrite-Regeln neu geschrieben? */
	public static $rules_flushed = false;

	// --- Steuerung von Fehlerfällen -------------------------------------

	/** @var array<string, mixed> Stylesheet => Rückgabewert für delete_theme(). */
	public static $delete_theme_returns = array();

	/** @var mixed Rückgabewert für delete_plugins(). */
	public static $delete_plugins_returns = true;

	/** @var mixed Rückgabewert für wp_insert_post(). Null = normal anlegen. */
	public static $insert_post_returns = null;

	/** @var array<int, int> Post-IDs, deren Löschen fehlschlägt. */
	public static $undeletable_posts = array();

	/** @var bool Darf switch_theme() das Stylesheet wirklich ändern? */
	public static $switch_theme_works = true;

	/**
	 * @return void
	 */
	public static function reset() {
		self::$options               = array();
		self::$posts                 = array();
		self::$post_meta             = array();
		self::$themes                = array();
		self::$plugins               = array();
		self::$deleted_themes        = array();
		self::$deleted_plugins       = array();
		self::$deactivated_plugins   = array();
		self::$deleted_posts         = array();
		self::$actions               = array();
		self::$activation_hooks      = array();
		self::$is_multisite          = false;
		self::$doing_ajax            = false;
		self::$doing_cron            = false;
		self::$can_manage_options    = true;
		self::$next_post_id          = 1;
		self::$rules_flushed         = false;
		self::$delete_theme_returns  = array();
		self::$delete_plugins_returns = true;
		self::$insert_post_returns   = null;
		self::$undeletable_posts     = array();
		self::$switch_theme_works    = true;

		$GLOBALS['wp_rewrite'] = new ASU_Fake_Rewrite();
	}

	/**
	 * Legt einen Beitrag oder eine Seite an.
	 *
	 * @param string $type   post oder page.
	 * @param string $status Post-Status.
	 * @return int
	 */
	public static function add_post( $type, $status ) {
		$id                 = self::$next_post_id++;
		self::$posts[ $id ] = array(
			'post_type'   => $type,
			'post_status' => $status,
		);

		return $id;
	}

	/**
	 * Legt ein Theme an.
	 *
	 * @param string $stylesheet Verzeichnisname.
	 * @param string $template   Parent-Theme, leer für ein eigenständiges Theme.
	 * @return void
	 */
	public static function add_theme( $stylesheet, $template = '' ) {
		self::$themes[ $stylesheet ] = new ASU_Fake_Theme( $stylesheet, '' === $template ? $stylesheet : $template );
	}
}

/**
 * Ersatz für WP_Theme.
 */
final class ASU_Fake_Theme {

	/** @var string */
	private $stylesheet;

	/** @var string */
	private $template;

	/**
	 * @param string $stylesheet Verzeichnisname.
	 * @param string $template   Parent-Theme.
	 */
	public function __construct( $stylesheet, $template ) {
		$this->stylesheet = $stylesheet;
		$this->template   = $template;
	}

	/** @return bool */
	public function exists() {
		return isset( ASU_Fake_WP::$themes[ $this->stylesheet ] );
	}

	/** @return string */
	public function get_stylesheet() {
		return $this->stylesheet;
	}

	/** @return string */
	public function get_template() {
		return $this->template;
	}
}

/**
 * Ersatz für WP_Rewrite.
 */
final class ASU_Fake_Rewrite {

	/** @var string */
	public $permalink_structure = '';

	/**
	 * @param string $structure Neue Struktur.
	 * @return void
	 */
	public function set_permalink_structure( $structure ) {
		$this->permalink_structure = $structure;
		ASU_Fake_WP::$options['permalink_structure'] = $structure;
	}

	/** @return void */
	public function flush_rules() {
		ASU_Fake_WP::$rules_flushed = true;
	}
}

/**
 * Ersatz für WP_Error.
 */
class WP_Error {

	/** @var string */
	private $code;

	/** @var string */
	private $message;

	/**
	 * @param string $code    Fehlercode.
	 * @param string $message Fehlertext.
	 */
	public function __construct( $code = '', $message = '' ) {
		$this->code    = $code;
		$this->message = $message;
	}

	/** @return string */
	public function get_error_code() {
		return $this->code;
	}

	/** @return string */
	public function get_error_message() {
		return $this->message;
	}
}

// --- Funktionen von WordPress ------------------------------------------

/**
 * @param mixed $thing Zu prüfender Wert.
 * @return bool
 */
function is_wp_error( $thing ) {
	return $thing instanceof WP_Error;
}

/**
 * @param string $option  Name.
 * @param mixed  $default Rückfallwert.
 * @return mixed
 */
function get_option( $option, $default = false ) {
	return array_key_exists( $option, ASU_Fake_WP::$options ) ? ASU_Fake_WP::$options[ $option ] : $default;
}

/**
 * @param string $option   Name.
 * @param mixed  $value    Wert.
 * @param mixed  $autoload Wird hier nicht gebraucht.
 * @return bool
 */
function update_option( $option, $value, $autoload = null ) {
	$changed                             = ! array_key_exists( $option, ASU_Fake_WP::$options ) || ASU_Fake_WP::$options[ $option ] !== $value;
	ASU_Fake_WP::$options[ $option ]     = $value;

	return $changed;
}

/**
 * @param string $option Name.
 * @return bool
 */
function delete_option( $option ) {
	if ( ! array_key_exists( $option, ASU_Fake_WP::$options ) ) {
		return false;
	}

	unset( ASU_Fake_WP::$options[ $option ] );

	return true;
}

/**
 * @param array<string, mixed> $args Abfrage.
 * @return array<int, int>
 */
function get_posts( array $args ) {
	$types    = isset( $args['post_type'] ) ? (array) $args['post_type'] : array( 'post' );
	$statuses = isset( $args['post_status'] ) ? (array) $args['post_status'] : array( 'publish' );
	$found    = array();

	foreach ( ASU_Fake_WP::$posts as $id => $post ) {
		if ( in_array( $post['post_type'], $types, true ) && in_array( $post['post_status'], $statuses, true ) ) {
			$found[] = $id;
		}
	}

	return $found;
}

/**
 * @param int  $id    Post-ID.
 * @param bool $force Endgültig löschen.
 * @return bool
 */
function wp_delete_post( $id, $force = false ) {
	if ( in_array( (int) $id, ASU_Fake_WP::$undeletable_posts, true ) ) {
		return false;
	}

	unset( ASU_Fake_WP::$posts[ $id ] );
	ASU_Fake_WP::$deleted_posts[] = (int) $id;

	return true;
}

/**
 * @param array<string, mixed> $data     Daten der Seite.
 * @param bool                 $wp_error WP_Error statt 0 im Fehlerfall.
 * @return int|WP_Error
 */
function wp_insert_post( array $data, $wp_error = false ) {
	if ( null !== ASU_Fake_WP::$insert_post_returns ) {
		return ASU_Fake_WP::$insert_post_returns;
	}

	$id = ASU_Fake_WP::$next_post_id++;

	ASU_Fake_WP::$posts[ $id ] = array(
		'post_type'   => isset( $data['post_type'] ) ? $data['post_type'] : 'post',
		'post_status' => isset( $data['post_status'] ) ? $data['post_status'] : 'draft',
		'post_title'  => isset( $data['post_title'] ) ? $data['post_title'] : '',
	);

	return $id;
}

/**
 * @param int    $id    Post-ID.
 * @param string $key   Meta-Schlüssel.
 * @param mixed  $value Wert.
 * @return bool
 */
function update_post_meta( $id, $key, $value ) {
	ASU_Fake_WP::$post_meta[ $id ][ $key ] = $value;

	return true;
}

/**
 * @param int    $id     Post-ID.
 * @param string $key    Meta-Schlüssel.
 * @param bool   $single Einzelwert.
 * @return mixed
 */
function get_post_meta( $id, $key = '', $single = false ) {
	return isset( ASU_Fake_WP::$post_meta[ $id ][ $key ] ) ? ASU_Fake_WP::$post_meta[ $id ][ $key ] : '';
}

/**
 * @return array<string, ASU_Fake_Theme>
 */
function wp_get_themes() {
	return ASU_Fake_WP::$themes;
}

/**
 * @param string $stylesheet Verzeichnisname, leer für das aktive Theme.
 * @return ASU_Fake_Theme
 */
function wp_get_theme( $stylesheet = '' ) {
	if ( '' === $stylesheet ) {
		$stylesheet = get_option( 'stylesheet', '' );
	}

	if ( isset( ASU_Fake_WP::$themes[ $stylesheet ] ) ) {
		return ASU_Fake_WP::$themes[ $stylesheet ];
	}

	// Ein nicht installiertes Theme: exists() liefert false.
	return new ASU_Fake_Theme( $stylesheet, $stylesheet );
}

/**
 * @param string $stylesheet Verzeichnisname.
 * @return void
 */
function switch_theme( $stylesheet ) {
	if ( ! ASU_Fake_WP::$switch_theme_works ) {
		return;
	}

	ASU_Fake_WP::$options['stylesheet'] = $stylesheet;

	$theme = wp_get_theme( $stylesheet );

	ASU_Fake_WP::$options['template'] = $theme->get_template();
}

/**
 * @param string $stylesheet Verzeichnisname.
 * @return bool|null|WP_Error
 */
function delete_theme( $stylesheet ) {
	if ( array_key_exists( $stylesheet, ASU_Fake_WP::$delete_theme_returns ) ) {
		return ASU_Fake_WP::$delete_theme_returns[ $stylesheet ];
	}

	unset( ASU_Fake_WP::$themes[ $stylesheet ] );
	ASU_Fake_WP::$deleted_themes[] = $stylesheet;

	return true;
}

/**
 * @return array<string, array<string, string>>
 */
function get_plugins() {
	return ASU_Fake_WP::$plugins;
}

/**
 * @param string|array<int, string> $plugins Plugin-Dateien.
 * @param bool                      $silent  Ohne Hooks.
 * @return void
 */
function deactivate_plugins( $plugins, $silent = false ) {
	foreach ( (array) $plugins as $plugin ) {
		ASU_Fake_WP::$deactivated_plugins[] = $plugin;
	}
}

/**
 * @param array<int, string> $plugins Plugin-Dateien.
 * @return bool|null|WP_Error
 */
function delete_plugins( array $plugins ) {
	if ( true !== ASU_Fake_WP::$delete_plugins_returns ) {
		return ASU_Fake_WP::$delete_plugins_returns;
	}

	foreach ( $plugins as $plugin ) {
		unset( ASU_Fake_WP::$plugins[ $plugin ] );
		ASU_Fake_WP::$deleted_plugins[] = $plugin;
	}

	return true;
}

/**
 * @return bool
 */
function WP_Filesystem() {
	return true;
}

/**
 * @param string $file Pfad zur Plugin-Datei.
 * @return string
 */
function plugin_basename( $file ) {
	return basename( dirname( $file ) ) . '/' . basename( $file );
}

/**
 * @param string   $file     Plugin-Datei.
 * @param callable $callback Rückruf.
 * @return void
 */
function register_activation_hook( $file, $callback ) {
	ASU_Fake_WP::$activation_hooks[ plugin_basename( $file ) ] = $callback;
}

/**
 * @param string   $hook     Name des Hooks.
 * @param callable $callback Rückruf.
 * @param int      $priority Priorität.
 * @param int      $args     Anzahl Argumente.
 * @return void
 */
function add_action( $hook, $callback, $priority = 10, $args = 1 ) {
	ASU_Fake_WP::$actions[ $hook ][] = $callback;
}

/**
 * @param string $hook Name des Hooks.
 * @return bool
 */
function asu_has_action( $hook ) {
	return ! empty( ASU_Fake_WP::$actions[ $hook ] );
}

/**
 * @param string $hook Name des Hooks.
 * @return string Alles, was die Rückrufe ausgegeben haben.
 */
function asu_do_action( $hook ) {
	ob_start();

	foreach ( ASU_Fake_WP::$actions[ $hook ] ?? array() as $callback ) {
		call_user_func( $callback );
	}

	return (string) ob_get_clean();
}

/**
 * @param string $capability Fähigkeit.
 * @return bool
 */
function current_user_can( $capability ) {
	return ASU_Fake_WP::$can_manage_options;
}

/** @return bool */
function is_multisite() {
	return ASU_Fake_WP::$is_multisite;
}

/** @return bool */
function wp_doing_ajax() {
	return ASU_Fake_WP::$doing_ajax;
}

/** @return bool */
function wp_doing_cron() {
	return ASU_Fake_WP::$doing_cron;
}

/**
 * @param string $text   Text.
 * @param string $domain Text Domain.
 * @return string
 */
function __( $text, $domain = 'default' ) {
	return $text;
}

/**
 * @param string $text Text.
 * @return string
 */
function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

/**
 * @param string $text Text.
 * @return string
 */
function esc_attr( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

/**
 * @param string $text   Text.
 * @param string $domain Text Domain.
 * @return string
 */
function esc_html__( $text, $domain = 'default' ) {
	return esc_html( $text );
}

// Der Autoloader des Plugins lädt die Klassen, genau wie im Echtbetrieb.
require_once dirname( __DIR__ ) . '/includes/class-asu-autoloader.php';

ASU_Autoloader::register( dirname( __DIR__ ) . '/includes' );

ASU_Fake_WP::reset();
