<?php
/**
 * Schaltet die Elementor-Funktion "Flexbox Container" ein.
 *
 * Elementor verwaltet solche Funktionen als "Experiments". Je nach Elementor-Version
 * heißen die Einträge in der Datenbank unterschiedlich, deshalb prüft und setzt
 * diese Klasse mehrere Varianten.
 */

if ( ! defined('ABSPATH') ) { exit; }

class ASU_Elementor_Container {

	/**
	 * Ist der Container bereits aktiv?
	 *
	 * @return bool
	 */
	public function is_active() {
		// Sammel-Option, in der Elementor alle Experiments speichert.
		$experiments = get_option('elementor_experimentation', []);

		if ( is_array($experiments) ) {
			if ( isset($experiments['container']) && $experiments['container'] === 'active' ) {
				return true;
			}
			if ( isset($experiments['elementor_container']) && $experiments['elementor_container'] === 'active' ) {
				return true;
			}
		}

		// Einzel-Option, wie sie das Auswahlfeld in der Elementor-Oberfläche schreibt.
		return get_option('elementor_experiment-container') === 'active';
	}

	/**
	 * Aktiviert den Container.
	 *
	 * Erst über die offizielle Elementor-Schnittstelle, danach zusätzlich direkt
	 * über die Optionen. Der zweite Weg ist die Absicherung, falls die Schnittstelle
	 * in der installierten Elementor-Version anders heißt.
	 */
	public function activate() {
		$this->activate_via_elementor_api();
		$this->activate_via_options();
	}

	/**
	 * Weg 1: Elementor selbst umschalten lassen.
	 */
	private function activate_via_elementor_api() {
		if ( ! did_action('elementor/loaded') ) {
			return;
		}

		try {
			// Neuere Elementor-Versionen: über die Plugin-Instanz.
			if (
				class_exists('\Elementor\Plugin')
				&& isset(\Elementor\Plugin::$instance->experiments)
				&& is_callable([ \Elementor\Plugin::$instance->experiments, 'set_feature_state' ])
			) {
				\Elementor\Plugin::$instance->experiments->set_feature_state('container', 'active');
			}

			// Alternativer Weg: direkt über den Experiments-Manager.
			if (
				class_exists('\Elementor\Core\Experiments\Manager')
				&& is_callable([ '\Elementor\Core\Experiments\Manager', 'get_instance' ])
			) {
				$manager = \Elementor\Core\Experiments\Manager::get_instance();
				if ( $manager && is_callable([ $manager, 'set_feature_state' ]) ) {
					$manager->set_feature_state('container', 'active');
				}
			}
		} catch ( \Exception $e ) {
			// Greift die Schnittstelle nicht, übernimmt Weg 2.
		}
	}

	/**
	 * Weg 2: Die Optionen direkt setzen, in allen bekannten Schreibweisen.
	 */
	private function activate_via_options() {
		$experiments = get_option('elementor_experimentation', []);
		if ( ! is_array($experiments) ) {
			$experiments = [];
		}

		$experiments['container']           = 'active'; // neuere Versionen
		$experiments['elementor_container'] = 'active'; // ältere Versionen
		update_option('elementor_experimentation', $experiments);

		update_option('elementor_experiment-container', 'active');
	}
}
