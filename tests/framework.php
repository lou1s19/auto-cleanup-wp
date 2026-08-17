<?php
/**
 * Winziger Testläufer. Kein PHPUnit, weil das Plugin ohne Abhängigkeiten auskommt.
 *
 * @package AutoCleanupWP
 */

/**
 * Eine fehlgeschlagene Zusicherung.
 */
final class ASU_Assertion_Failed extends Exception {
}

/**
 * Zusicherungen.
 */
final class Assert {

	/**
	 * @param mixed  $expected Erwarteter Wert.
	 * @param mixed  $actual   Tatsächlicher Wert.
	 * @param string $message  Was geprüft wird.
	 * @return void
	 * @throws ASU_Assertion_Failed Wenn die Werte nicht gleich sind.
	 */
	public static function same( $expected, $actual, $message ) {
		if ( $expected === $actual ) {
			return;
		}

		throw new ASU_Assertion_Failed(
			sprintf( '%s%s  erwartet: %s%s  bekommen: %s', $message, PHP_EOL, self::show( $expected ), PHP_EOL, self::show( $actual ) )
		);
	}

	/**
	 * @param mixed  $value   Wert.
	 * @param string $message Was geprüft wird.
	 * @return void
	 * @throws ASU_Assertion_Failed Wenn der Wert nicht true ist.
	 */
	public static function true( $value, $message ) {
		self::same( true, $value, $message );
	}

	/**
	 * @param mixed  $value   Wert.
	 * @param string $message Was geprüft wird.
	 * @return void
	 * @throws ASU_Assertion_Failed Wenn der Wert nicht false ist.
	 */
	public static function false( $value, $message ) {
		self::same( false, $value, $message );
	}

	/**
	 * @param mixed  $needle   Gesuchter Wert.
	 * @param array  $haystack Liste.
	 * @param string $message  Was geprüft wird.
	 * @return void
	 * @throws ASU_Assertion_Failed Wenn der Wert fehlt.
	 */
	public static function contains( $needle, array $haystack, $message ) {
		if ( in_array( $needle, $haystack, true ) ) {
			return;
		}

		throw new ASU_Assertion_Failed(
			sprintf( '%s%s  %s fehlt in: %s', $message, PHP_EOL, self::show( $needle ), self::show( $haystack ) )
		);
	}

	/**
	 * @param mixed  $needle   Wert, der fehlen muss.
	 * @param array  $haystack Liste.
	 * @param string $message  Was geprüft wird.
	 * @return void
	 * @throws ASU_Assertion_Failed Wenn der Wert vorhanden ist.
	 */
	public static function missing( $needle, array $haystack, $message ) {
		if ( ! in_array( $needle, $haystack, true ) ) {
			return;
		}

		throw new ASU_Assertion_Failed(
			sprintf( '%s%s  %s hätte nicht in der Liste sein dürfen: %s', $message, PHP_EOL, self::show( $needle ), self::show( $haystack ) )
		);
	}

	/**
	 * @param string $needle   Teilzeichenkette.
	 * @param string $haystack Text.
	 * @param string $message  Was geprüft wird.
	 * @return void
	 * @throws ASU_Assertion_Failed Wenn der Text nicht vorkommt.
	 */
	public static function text_contains( $needle, $haystack, $message ) {
		if ( false !== strpos( (string) $haystack, $needle ) ) {
			return;
		}

		throw new ASU_Assertion_Failed(
			sprintf( '%s%s  "%s" kommt nicht vor in: %s', $message, PHP_EOL, $needle, $haystack )
		);
	}

	/**
	 * @param mixed $value Wert.
	 * @return string
	 */
	private static function show( $value ) {
		return str_replace( PHP_EOL, ' ', var_export( $value, true ) );
	}
}

/**
 * Sammelt und startet die Tests.
 */
final class ASU_Tests {

	/** @var array<int, array{name: string, run: callable}> */
	private static $tests = array();

	/** @var array<int, string> */
	private static $failures = array();

	/** @var int */
	private static $passed = 0;

	/**
	 * @param string   $name Beschreibung des Tests.
	 * @param callable $run  Der Test selbst.
	 * @return void
	 */
	public static function add( $name, callable $run ) {
		self::$tests[] = array(
			'name' => $name,
			'run'  => $run,
		);
	}

	/**
	 * Führt alle gesammelten Tests aus.
	 *
	 * @return int Exit-Code: 0, wenn alles grün ist.
	 */
	public static function run() {
		foreach ( self::$tests as $test ) {
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

/**
 * Kurzschreibweise zum Anmelden eines Tests.
 *
 * @param string   $name Beschreibung.
 * @param callable $run  Der Test.
 * @return void
 */
function test( $name, callable $run ) {
	ASU_Tests::add( $name, $run );
}
