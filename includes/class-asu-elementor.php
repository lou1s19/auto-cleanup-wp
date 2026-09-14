<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ASU_Elementor {

	// Elementor bildet den Schluessel als "elementor_experiment-" plus Name des
	// Experiments. Ein selbst ausgedachter Name landet als Karteileiche in wp_options.
	const OPTION_CONTAINER = 'elementor_experiment-container';

	// Im Backend heisst dieses Template "Elementor Full Width", intern aber
	// elementor_header_footer. Einen unbekannten Slug ignoriert WordPress
	// stillschweigend, der Fehler faellt also nie auf.
	const TEMPLATE_FULL_WIDTH = 'elementor_header_footer';

	private ?bool $active;

	public function __construct( ?bool $active = null ) {
		$this->active = $active;
	}

	public function is_active(): bool {
		if ( null !== $this->active ) {
			return $this->active;
		}

		return defined( 'ELEMENTOR_VERSION' ) || class_exists( '\Elementor\Plugin' );
	}

	public function page_template(): string {
		return $this->is_active() ? self::TEMPLATE_FULL_WIDTH : '';
	}

	public function enable_containers( ASU_Result $result ): void {
		if ( ! $this->is_active() ) {
			$result->skip( 'container', 'Elementor ist nicht aktiv, die Container wurden nicht angefasst.' );

			return;
		}

		if ( 'active' === get_option( self::OPTION_CONTAINER ) ) {
			$result->skip( 'container', 'Die Flexbox Container waren schon aktiv.' );

			return;
		}

		// Elementors eigene API steht beim Aktivieren eines fremden Plugins noch
		// nicht bereit, die Option wird deshalb direkt geschrieben.
		update_option( self::OPTION_CONTAINER, 'active' );

		// update_option() liefert auch dann false, wenn der Wert nur unveraendert
		// war. Nur der gelesene Wert sagt, ob es wirklich steht.
		if ( 'active' !== get_option( self::OPTION_CONTAINER ) ) {
			$result->fail( 'container', 'Die Flexbox Container liessen sich nicht aktivieren.' );

			return;
		}

		$result->ok( 'container', 'Flexbox Container aktiviert.' );
	}
}
