<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ASU_Autoloader {

	const PREFIX = 'ASU_';

	private string $directory;

	private function __construct( string $directory ) {
		$this->directory = rtrim( $directory, '/\\' );
	}

	public static function register( string $directory ): self {
		$loader = new self( $directory );

		spl_autoload_register( array( $loader, 'load' ) );

		return $loader;
	}

	public function load( string $class ): void {
		if ( 0 !== strpos( $class, self::PREFIX ) ) {
			return;
		}

		$file = $this->directory . '/class-' . str_replace( '_', '-', strtolower( $class ) ) . '.php';

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
}
