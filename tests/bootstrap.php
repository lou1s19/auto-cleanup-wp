<?php
/**
 * Nachbau des Teils von WordPress, den das Plugin anfasst. Kein PHPUnit und
 * keine WordPress-Testsuite, weil das Plugin selbst ohne Abhaengigkeiten auskommt.
 */

define( 'ABSPATH', __DIR__ . '/fake-wp/' );

final class ASU_Fake_WP {

	public static array $options = array();

	public static array $posts = array();

	public static array $post_meta = array();

	public static array $themes = array();

	public static array $plugins = array();

	public static array $deleted_themes = array();

	public static array $deleted_plugins = array();

	public static array $deactivated_plugins = array();

	public static array $deleted_posts = array();

	public static array $actions = array();

	public static array $activation_hooks = array();

	public static bool $is_multisite = false;

	public static bool $doing_ajax = false;

	public static bool $doing_cron = false;

	public static bool $can_manage_options = true;

	public static int $next_post_id = 1;

	public static bool $rules_flushed = false;

	// Ab hier: Schalter, mit denen ein Test einen Fehlerfall erzwingt.

	public static array $delete_theme_returns = array();

	public static $delete_plugins_returns = true;

	public static $insert_post_returns = null;

	public static array $undeletable_posts = array();

	// Haelt fest, was asu_setup_ran im Moment des ersten Loeschaufrufs enthielt.
	// Nur so laesst sich pruefen, dass die Notiz vor dem Loeschen steht.
	public static $ran_at_first_delete = 'nie geloescht';

	public static bool $switch_theme_works = true;

	public static array $extra_post_stati = array();

	public static function reset(): void {
		self::$options                = array();
		self::$posts                  = array();
		self::$post_meta              = array();
		self::$themes                 = array();
		self::$plugins                = array();
		self::$deleted_themes         = array();
		self::$deleted_plugins        = array();
		self::$deactivated_plugins    = array();
		self::$deleted_posts          = array();
		self::$ran_at_first_delete    = 'nie geloescht';
		self::$actions                = array();
		self::$activation_hooks       = array();
		self::$is_multisite           = false;
		self::$doing_ajax             = false;
		self::$doing_cron             = false;
		self::$can_manage_options     = true;
		self::$next_post_id           = 1;
		self::$rules_flushed          = false;
		self::$delete_theme_returns   = array();
		self::$delete_plugins_returns = true;
		self::$insert_post_returns    = null;
		self::$undeletable_posts      = array();
		self::$switch_theme_works     = true;
		self::$extra_post_stati       = array();

		$GLOBALS['wp_rewrite'] = new ASU_Fake_Rewrite();
	}

	public static function add_post( string $type, string $status ): int {
		$id                 = self::$next_post_id++;
		self::$posts[ $id ] = array(
			'post_type'   => $type,
			'post_status' => $status,
		);

		return $id;
	}

	public static function add_theme( string $stylesheet, string $template = '', string $name = '' ): void {
		self::$themes[ $stylesheet ] = new ASU_Fake_Theme(
			$stylesheet,
			'' === $template ? $stylesheet : $template,
			$name
		);
	}

	public static function add_hello_elementor( string $stylesheet = 'hello-elementor' ): void {
		self::add_theme( $stylesheet, '', 'Hello Elementor' );
	}
}

final class ASU_Fake_Theme {

	private string $stylesheet;

	private string $template;

	private string $name;

	public function __construct( string $stylesheet, string $template, string $name = '' ) {
		$this->stylesheet = $stylesheet;
		$this->template   = $template;
		$this->name       = '' === $name ? $stylesheet : $name;
	}

	public function get( string $header ): string {
		return 'Name' === $header ? $this->name : '';
	}

	public function exists(): bool {
		return isset( ASU_Fake_WP::$themes[ $this->stylesheet ] );
	}

	public function get_stylesheet(): string {
		return $this->stylesheet;
	}

	public function get_template(): string {
		return $this->template;
	}
}

final class ASU_Fake_Rewrite {

	public string $permalink_structure = '';

	public function set_permalink_structure( string $structure ): void {
		$this->permalink_structure                   = $structure;
		ASU_Fake_WP::$options['permalink_structure'] = $structure;
	}

	public function flush_rules(): void {
		ASU_Fake_WP::$rules_flushed = true;
	}
}

class WP_Error {

	private string $code;

	private string $message;

	public function __construct( string $code = '', string $message = '' ) {
		$this->code    = $code;
		$this->message = $message;
	}

	public function get_error_code(): string {
		return $this->code;
	}

	public function get_error_message(): string {
		return $this->message;
	}
}

function is_wp_error( $thing ): bool {
	return $thing instanceof WP_Error;
}

function get_option( string $option, $default = false ) {
	return array_key_exists( $option, ASU_Fake_WP::$options ) ? ASU_Fake_WP::$options[ $option ] : $default;
}

// Liefert wie das Original false, wenn sich der Wert nicht geaendert hat.
function update_option( string $option, $value, $autoload = null ): bool {
	$changed                         = ! array_key_exists( $option, ASU_Fake_WP::$options ) || ASU_Fake_WP::$options[ $option ] !== $value;
	ASU_Fake_WP::$options[ $option ] = $value;

	return $changed;
}

function delete_option( string $option ): bool {
	if ( ! array_key_exists( $option, ASU_Fake_WP::$options ) ) {
		return false;
	}

	unset( ASU_Fake_WP::$options[ $option ] );

	return true;
}

function get_posts( array $args ): array {
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

function wp_count_posts( string $type = 'post' ): object {
	$counts = array();

	foreach ( ASU_Fake_WP::$posts as $post ) {
		if ( $post['post_type'] !== $type ) {
			continue;
		}

		$status = $post['post_status'];

		$counts[ $status ] = isset( $counts[ $status ] ) ? $counts[ $status ] + 1 : 1;
	}

	return (object) $counts;
}

function wp_delete_post( $id, bool $force = false ): bool {
	if ( 'nie geloescht' === ASU_Fake_WP::$ran_at_first_delete ) {
		ASU_Fake_WP::$ran_at_first_delete = ASU_Fake_WP::$options['asu_setup_ran'] ?? false;
	}

	if ( in_array( (int) $id, ASU_Fake_WP::$undeletable_posts, true ) ) {
		return false;
	}

	unset( ASU_Fake_WP::$posts[ $id ] );
	ASU_Fake_WP::$deleted_posts[] = (int) $id;

	return true;
}

function wp_insert_post( array $data, bool $wp_error = false ) {
	if ( null !== ASU_Fake_WP::$insert_post_returns ) {
		return ASU_Fake_WP::$insert_post_returns;
	}

	$id = ASU_Fake_WP::$next_post_id++;

	ASU_Fake_WP::$posts[ $id ] = array(
		'post_type'   => $data['post_type'] ?? 'post',
		'post_status' => $data['post_status'] ?? 'draft',
		'post_title'  => $data['post_title'] ?? '',
	);

	return $id;
}

function update_post_meta( $id, string $key, $value ): bool {
	ASU_Fake_WP::$post_meta[ $id ][ $key ] = $value;

	return true;
}

function get_post_meta( $id, string $key = '', bool $single = false ) {
	return ASU_Fake_WP::$post_meta[ $id ][ $key ] ?? '';
}

function get_post_stati(): array {
	return array_merge(
		array( 'publish', 'future', 'draft', 'pending', 'private', 'trash', 'auto-draft', 'inherit' ),
		ASU_Fake_WP::$extra_post_stati
	);
}

function wp_get_themes(): array {
	return ASU_Fake_WP::$themes;
}

function wp_get_theme( string $stylesheet = '' ): ASU_Fake_Theme {
	if ( '' === $stylesheet ) {
		$stylesheet = get_option( 'stylesheet', '' );
	}

	if ( isset( ASU_Fake_WP::$themes[ $stylesheet ] ) ) {
		return ASU_Fake_WP::$themes[ $stylesheet ];
	}

	// Wie im Original: ein nicht installiertes Theme liefert ein Objekt,
	// dessen exists() false ist, nicht null.
	return new ASU_Fake_Theme( $stylesheet, $stylesheet );
}

function switch_theme( string $stylesheet ): void {
	if ( ! ASU_Fake_WP::$switch_theme_works ) {
		return;
	}

	ASU_Fake_WP::$options['stylesheet'] = $stylesheet;

	$theme = wp_get_theme( $stylesheet );

	ASU_Fake_WP::$options['template'] = $theme->get_template();
}

function delete_theme( string $stylesheet ) {
	if ( array_key_exists( $stylesheet, ASU_Fake_WP::$delete_theme_returns ) ) {
		return ASU_Fake_WP::$delete_theme_returns[ $stylesheet ];
	}

	unset( ASU_Fake_WP::$themes[ $stylesheet ] );
	ASU_Fake_WP::$deleted_themes[] = $stylesheet;

	return true;
}

function get_plugins(): array {
	return ASU_Fake_WP::$plugins;
}

function deactivate_plugins( $plugins, bool $silent = false ): void {
	foreach ( (array) $plugins as $plugin ) {
		ASU_Fake_WP::$deactivated_plugins[] = $plugin;
	}
}

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

function WP_Filesystem(): bool {
	return true;
}

function plugin_basename( string $file ): string {
	return basename( dirname( $file ) ) . '/' . basename( $file );
}

function register_activation_hook( string $file, $callback ): void {
	ASU_Fake_WP::$activation_hooks[ plugin_basename( $file ) ] = $callback;
}

function add_action( string $hook, $callback, int $priority = 10, int $args = 1 ): void {
	ASU_Fake_WP::$actions[ $hook ][] = $callback;
}

function asu_has_action( string $hook ): bool {
	return ! empty( ASU_Fake_WP::$actions[ $hook ] );
}

function asu_do_action( string $hook ): string {
	ob_start();

	foreach ( ASU_Fake_WP::$actions[ $hook ] ?? array() as $callback ) {
		call_user_func( $callback );
	}

	return (string) ob_get_clean();
}

function current_user_can( string $capability ): bool {
	return ASU_Fake_WP::$can_manage_options;
}

function is_multisite(): bool {
	return ASU_Fake_WP::$is_multisite;
}

function wp_doing_ajax(): bool {
	return ASU_Fake_WP::$doing_ajax;
}

function wp_doing_cron(): bool {
	return ASU_Fake_WP::$doing_cron;
}

function __( string $text, string $domain = 'default' ): string {
	return $text;
}

function esc_html( $text ): string {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function esc_attr( $text ): string {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function esc_html__( string $text, string $domain = 'default' ): string {
	return esc_html( $text );
}

require_once dirname( __DIR__ ) . '/includes/class-asu-autoloader.php';

ASU_Autoloader::register( dirname( __DIR__ ) . '/includes' );

ASU_Fake_WP::reset();
