<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ASU_Result {

	const OK      = 'ok';
	const FAILED  = 'failed';
	const SKIPPED = 'skipped';

	private array $steps = array();

	public function record( string $step, string $status, string $detail = '' ): void {
		$this->steps[] = array(
			'step'   => $step,
			'status' => $status,
			'detail' => $detail,
		);
	}

	public function ok( string $step, string $detail = '' ): void {
		$this->record( $step, self::OK, $detail );
	}

	public function fail( string $step, string $detail = '' ): void {
		$this->record( $step, self::FAILED, $detail );
	}

	public function skip( string $step, string $detail = '' ): void {
		$this->record( $step, self::SKIPPED, $detail );
	}

	// WordPress meldet Fehler nicht per Exception, sondern liefert ein WP_Error
	// zurueck. Ein try/catch faengt davon nichts. Diese Methode buendelt die
	// Pruefung, damit sie nicht in jedem Schritt einzeln steht.
	public function catch_wp_error( string $step, $value, string $label ): bool {
		if ( ! function_exists( 'is_wp_error' ) || ! is_wp_error( $value ) ) {
			return false;
		}

		$this->fail( $step, sprintf( '%s: %s', $label, $value->get_error_message() ) );

		return true;
	}

	public function steps(): array {
		return $this->steps;
	}

	public function failures(): array {
		$failures = array();

		foreach ( $this->steps as $step ) {
			if ( self::FAILED === $step['status'] ) {
				$failures[] = $step;
			}
		}

		return $failures;
	}

	public function has_failures(): bool {
		return array() !== $this->failures();
	}

	public function to_array(): array {
		return $this->steps;
	}

	// Ein kaputter Optionswert darf das Backend nicht lahmlegen, deshalb wird
	// alles Unbrauchbare zu einem leeren Protokoll.
	public static function from_array( $data ): self {
		$result = new self();

		if ( ! is_array( $data ) ) {
			return $result;
		}

		foreach ( $data as $step ) {
			if ( ! is_array( $step ) || ! isset( $step['step'], $step['status'] ) ) {
				continue;
			}

			$result->record(
				(string) $step['step'],
				(string) $step['status'],
				isset( $step['detail'] ) ? (string) $step['detail'] : ''
			);
		}

		return $result;
	}
}
