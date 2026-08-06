<?php
/**
 * Namen der Optionen, die das Plugin in der WordPress-Datenbank speichert.
 *
 * Das Plugin merkt sich damit, welcher Schritt schon erledigt ist.
 * Alle Namen stehen an einer Stelle, damit sich niemand vertippt.
 */

if ( ! defined('ABSPATH') ) { exit; }

class ASU_Options {

	/** Grund-Setup beim Aktivieren ist durchgelaufen. */
	const BASE_DONE = 'asu_base_done';

	/** Elementor-Container ist bestätigt aktiv. */
	const CONTAINER_OK = 'asu_container_ok';

	/** Elementor-Container soll noch aktiviert werden. */
	const CONTAINER_PENDING = 'asu_container_pending';
}
