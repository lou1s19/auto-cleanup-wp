<?php
/**
 * Schaltet die Elementor Flexbox Container ein.
 */

if ( ! defined('ABSPATH') ) { exit; }

class ASU_Elementor {

	/**
	 * Elementor merkt sich zuschaltbare Funktionen in eigenen Einträgen in der
	 * Datenbank und liest sie beim nächsten Seitenaufruf. Wir schreiben sie also
	 * einfach hin.
	 *
	 * Die Einträge heißen je nach Elementor-Version anders, deshalb werden alle
	 * bekannten Schreibweisen gesetzt. Falls Elementor das irgendwann umbaut,
	 * ist diese Methode die einzige Stelle, die angepasst werden muss.
	 */
	public function enable_containers() {
		$experiments = get_option('elementor_experimentation', []);

		if ( ! is_array($experiments) ) {
			$experiments = [];
		}

		$experiments['container']           = 'active'; // neuere Versionen
		$experiments['elementor_container'] = 'active'; // ältere Versionen
		update_option('elementor_experimentation', $experiments);

		// Derselbe Schalter, wie ihn die Elementor-Oberfläche schreibt.
		update_option('elementor_experiment-container', 'active');
	}
}
