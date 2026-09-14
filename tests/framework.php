<?php

final class ASU_Assertion_Failed extends Exception {
}

final class Assert {

	public static function same( $expected, $actual, string $message ): void {
		if ( $expected === $actual ) {
			return;
		}

		throw new ASU_Assertion_Failed(
			sprintf( '%s%s  erwartet: %s%s  bekommen: %s', $message, PHP_EOL, self::show( $expected ), PHP_EOL, self::show( $actual ) )
		);
	}

	public static function true( $value, string $message ): void {
		self::same( true, $value, $message );
	}

	public static function false( $value, string $message ): void {
		self::same( false, $value, $message );
	}

	public static function contains( $needle, array $haystack, string $message ): void {
		if ( in_array( $needle, $haystack, true ) ) {
			return;
		}

		throw new ASU_Assertion_Failed(
			sprintf( '%s%s  %s fehlt in: %s', $message, PHP_EOL, self::show( $needle ), self::show( $haystack ) )
		);
	}

	public static function missing( $needle, array $haystack, string $message ): void {
		if ( ! in_array( $needle, $haystack, true ) ) {
			return;
		}

		throw new ASU_Assertion_Failed(
			sprintf( '%s%s  %s haette nicht in der Liste sein duerfen: %s', $message, PHP_EOL, self::show( $needle ), self::show( $haystack ) )
		);
	}

	public static function text_contains( string $needle, $haystack, string $message ): void {
		if ( false !== strpos( (string) $haystack, $needle ) ) {
			return;
		}

		throw new ASU_Assertion_Failed(
			sprintf( '%s%s  "%s" kommt nicht vor in: %s', $message, PHP_EOL, $needle, $haystack )
		);
	}

	private static function show( $value ): string {
		return str_replace( PHP_EOL, ' ', var_export( $value, true ) );
	}
}

final class ASU_Tests {

	private static array $tests = array();

	private static array $failures = array();

	private static int $passed = 0;

	public static function add( string $name, callable $run ): void {
		self::$tests[] = array(
			'name' => $name,
			'run'  => $run,
		);
	}

	public static function run(): int {
		foreach ( self::$tests as $test ) {
			// Jeder Test startet auf einer leeren Attrappe, sonst haengt das
			// Ergebnis an der Reihenfolge.
			ASU_Fake_WP::reset();

			try {
				call_user_func( $test['run'] );

				++self::$passed;
				echo '  ok    ' . $test['name'] . PHP_EOL;
			} catch ( Throwable $e ) {
				self::$failures[] = $test['name'];
				echo '  FEHL  ' . $test['name'] . PHP_EOL;
				echo '        ' . str_replace( PHP_EOL, PHP_EOL . '        ', $e->getMessage() ) . PHP_EOL;
			}
		}

		echo PHP_EOL;

		if ( array() === self::$failures ) {
			echo sprintf( 'Alle %d Tests bestanden.', self::$passed ) . PHP_EOL;

			return 0;
		}

		echo sprintf( '%d bestanden, %d fehlgeschlagen:', self::$passed, count( self::$failures ) ) . PHP_EOL;

		foreach ( self::$failures as $failure ) {
			echo '  - ' . $failure . PHP_EOL;
		}

		return 1;
	}
}

function test( string $name, callable $run ): void {
	ASU_Tests::add( $name, $run );
}
