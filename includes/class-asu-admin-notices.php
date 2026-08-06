<?php
/**
 * Meldungen im WordPress-Adminbereich.
 *
 * Jede Meldung wird genau einmal gezeigt: nach der Ausgabe wird die zugehörige
 * Option gelöscht.
 */

if ( ! defined('ABSPATH') ) { exit; }

class ASU_Admin_Notices {

	/**
	 * Gibt die anstehenden Meldungen aus. Hängt am Hook 'admin_notices'.
	 */
	public function render() {
		// Nur Administratoren sollen die Meldungen sehen.
		if ( ! current_user_can('manage_options') ) {
			return;
		}

		if ( get_option(ASU_Options::CONTAINER_OK) ) {
			$this->success('Setup abgeschlossen. Startseite, Permalinks und Elementor-Container sind gesetzt, das Plugin hat sich selbst deaktiviert.');
			delete_option(ASU_Options::CONTAINER_OK);
		}
	}

	/** Grüne Erfolgsmeldung. */
	private function success($message) {
		$this->notice('notice-success', $message);
	}

	/**
	 * Gibt eine Meldung im WordPress-Standardformat aus.
	 *
	 * @param string $type    CSS-Klasse von WordPress, z. B. 'notice-success'.
	 * @param string $message Text der Meldung.
	 */
	private function notice($type, $message) {
		printf(
			'<div class="notice %1$s is-dismissible"><p><strong>Auto Cleanup WP:</strong> %2$s</p></div>',
			esc_attr($type),
			esc_html($message)
		);
	}
}
