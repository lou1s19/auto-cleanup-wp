<?php
/**
 * Protokoll eines Setup-Laufs.
 *
 * Jeder Schritt trägt hier ein, ob er geklappt hat. Das Protokoll überlebt als
 * Option den Seitenaufruf und ist die Grundlage für die Meldung im Backend.
 * Ohne diese Klasse müsste das Plugin "fertig" melden, ohne zu wissen, ob es
 * stimmt: WordPress liefert bei `delete_theme()` oder `delete_plugins()` im
 * Fehlerfall ein WP_Error zurück, es wirft keine Exception.
 *
 * @package AutoCleanupWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ASU_Result {

	/** Schritt hat funktioniert. */
	const OK = 'ok';

	/** Schritt ist fehlgeschlagen. */
	const FAILED = 'failed';

	/** Schritt war nicht nötig oder nicht möglich, ist aber kein Fehler. */
	const SKIPPED = 'skipped';

	/** @var array<int, array{step: string, status: string, detail: string}> */
	private $steps = array();

	/**
	 * @param string $step   Kurzname des Schritts, z. B. "themes".
	 * @param string $status Einer der Werte OK, FAILED, SKIPPED.
	 * @param string $detail Ein Satz für den Menschen im Backend.
	 * @return void
	 */
	public function record( $step, $status, $detail = '' ) {
		$this->steps[] = array(
			'step'   => (string) $step,
			'status' => (string) $status,
			'detail' => (string) $detail,
		);
	}

	/**
	 * @param string $step   Kurzname des Schritts.
	 * @param string $detail Ein Satz für den Menschen im Backend.
	 * @return void
	 */
	public function ok( $step, $detail = '' ) {
		$this->record( $step, self::OK, $detail );
	}

	/**
	 * @param string $step   Kurzname des Schritts.
	 * @param string $detail Ein Satz für den Menschen im Backend.
	 * @return void
	 */
	public function fail( $step, $detail = '' ) {
		$this->record( $step, self::FAILED, $detail );
	}

	/**
	 * @param string $step   Kurzname des Schritts.
	 * @param string $detail Ein Satz für den Menschen im Backend.
	 * @return void
	 */
	public function skip( $step, $detail = '' ) {
		$this->record( $step, self::SKIPPED, $detail );
	}

	/**
	 * Trägt ein WP_Error als Fehlschlag ein und gibt zurück, ob es eines war.
	 * Damit steht die Prüfung an genau einer Stelle statt in jeder Methode.
	 *
	 * @param string $step  Kurzname des Schritts.
	 * @param mixed  $value Rückgabewert einer WordPress-Funktion.
	 * @param string $label Womit der Schritt beschrieben wird.
	 * @return bool True, wenn $value ein WP_Error war.
	 */
	public function catch_wp_error( $step, $value, $label ) {
		if ( ! function_exists( 'is_wp_error' ) || ! is_wp_error( $value ) ) {
			return false;
		}

		$this->fail( $step, sprintf( '%s: %s', $label, $value->get_error_message() ) );

		return true;
	}

	/**
	 * @return array<int, array{step: string, status: string, detail: string}> Alle Schritte in der Reihenfolge des Ablaufs.
	 */
	public function steps() {
		return $this->steps;
	}

	/**
	 * @return array<int, array{step: string, status: string, detail: string}> Nur die fehlgeschlagenen Schritte.
	 */
	public function failures() {
		$failures = array();

		foreach ( $this->steps as $step ) {
			if ( self::FAILED === $step['status'] ) {
				$failures[] = $step;
			}
		}

		return $failures;
	}

	/**
	 * @return bool True, wenn mindestens ein Schritt fehlgeschlagen ist.
	 */
	public function has_failures() {
		return array() !== $this->failures();
	}

	/**
	 * Form zum Ablegen in einer Option.
	 *
	 * @return array<int, array{step: string, status: string, detail: string}>
	 */
	public function to_array() {
		return $this->steps;
	}

	/**
	 * Gegenstück zu to_array(). Unbrauchbare Daten ergeben ein leeres Protokoll,
	 * damit ein kaputter Optionswert das Backend nicht lahmlegt.
	 *
	 * @param mixed $data Was in der Option stand.
	 * @return ASU_Result
	 */
	public static function from_array( $data ) {
		$result = new self();

		if ( ! is_array( $data ) ) {
			return $result;
		}

		foreach ( $data as $step ) {
			if ( ! is_array( $step ) || ! isset( $step['step'], $step['status'] ) ) {
				continue;
			}

			$result->record(
				$step['step'],
				$step['status'],
				isset( $step['detail'] ) ? $step['detail'] : ''
			);
		}

		return $result;
	}
}
