<?php
/**
 * Lädt die Klassen des Plugins erst dann, wenn sie wirklich gebraucht werden.
 *
 * @package AutoCleanupWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ASU_Autoloader {

	/** Präfix, an dem die Klassen dieses Plugins zu erkennen sind. */
	const PREFIX = 'ASU_';

	/** @var string Ordner, in dem die Klassendateien liegen. */
	private $directory;

	/**
	 * @param string $directory Ordner mit den Klassendateien.
	 */
	private function __construct( $directory ) {
		$this->directory = rtrim( $directory, '/\\' );
	}

	/**
	 * Meldet den Autoloader bei PHP an.
	 *
	 * @param string $directory Ordner mit den Klassendateien.
	 * @return ASU_Autoloader
	 */
	public static function register( $directory ) {
		$loader = new self( $directory );

		spl_autoload_register( array( $loader, 'load' ) );

		return $loader;
	}

	/**
	 * Übersetzt einen Klassennamen in einen Dateinamen und lädt die Datei.
	 * Aus ASU_Site_Setup wird class-asu-site-setup.php.
	 *
	 * @param string $class Angeforderter Klassenname.
	 * @return void
	 */
	public function load( $class ) {
		if ( 0 !== strpos( $class, self::PREFIX ) ) {
			return;
		}

		$slug = str_replace( '_', '-', strtolower( $class ) );
		$file = $this->directory . '/class-' . $slug . '.php';

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
}
