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

	/** Header- und Footer-Template wurden angelegt (speichert die IDs). */
	const THEME_BUILDER_DONE = 'asu_theme_builder_done';

	/** Theme Builder wurde übersprungen, weil Elementor Pro fehlte. */
	const THEME_BUILDER_SKIPPED = 'asu_theme_builder_skipped';
}
