<?php
/**
 * Alles, was dieses Plugin über Elementor wissen muss.
 *
 * Die beiden Konstanten sind bewusst hier gebündelt. Sie sind die einzigen
 * Stellen, an denen fremde Bezeichner aus Elementor auftauchen. Ändert Elementor
 * etwas daran, wird nur diese Datei angefasst.
 *
 * @package AutoCleanupWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ASU_Elementor {

	/**
	 * Optionsschlüssel für den Zustand des Container-Experiments.
	 * Elementor bildet ihn als OPTION_PREFIX . $feature_name, also
	 * "elementor_experiment-" . "container".
	 */
	const OPTION_CONTAINER = 'elementor_experiment-container';

	/**
	 * Slug des Seiten-Templates, das im Backend "Elementor Full Width" heisst.
	 * Der Anzeigename und der Slug sind nicht dasselbe: intern heisst es
	 * elementor_header_footer, weil das Template Header und Footer des Themes
	 * behält und nur die Sidebar entfernt. Ein Slug, den Elementor nicht kennt,
	 * wird von WordPress stillschweigend ignoriert.
	 */
	const TEMPLATE_FULL_WIDTH = 'elementor_header_footer';

	/** @var bool|null Fest vorgegebener Zustand, sonst wird selbst nachgesehen. */
	private $active;

	/**
	 * @param bool|null $active Nur für Tests. Null bedeutet: selbst nachsehen.
	 */
	public function __construct( $active = null ) {
		$this->active = $active;
	}

	/**
	 * @return bool True, wenn Elementor geladen ist.
	 */
	public function is_active() {
		if ( null !== $this->active ) {
			return (bool) $this->active;
		}

		return defined( 'ELEMENTOR_VERSION' ) || class_exists( '\Elementor\Plugin' );
	}

	/**
	 * Das Template, das die Startseite bekommen soll.
	 * Ohne Elementor bleibt es leer, sonst stünde in der Seite ein Template,
	 * das niemand rendern kann.
	 *
	 * @return string
	 */
	public function page_template() {
		return $this->is_active() ? self::TEMPLATE_FULL_WIDTH : '';
	}

	/**
	 * Schaltet die Flexbox Container ein.
	 *
	 * Elementor merkt sich zuschaltbare Funktionen als eigene Optionen und liest
	 * sie beim nächsten Seitenaufruf. Wir schreiben die Option also einfach hin,
	 * statt Elementors API zu bemühen, die beim Aktivieren eines fremden Plugins
	 * noch gar nicht bereitsteht.
	 *
	 * @param ASU_Result $result Protokoll des Laufs.
	 * @return void
	 */
	public function enable_containers( ASU_Result $result ) {
		if ( ! $this->is_active() ) {
			$result->skip( 'container', 'Elementor ist nicht aktiv, die Container wurden nicht angefasst.' );

			return;
		}

		if ( 'active' === get_option( self::OPTION_CONTAINER ) ) {
			$result->skip( 'container', 'Die Flexbox Container waren schon aktiv.' );

			return;
		}

		update_option( self::OPTION_CONTAINER, 'active' );

		// Nachlesen, weil update_option() auch dann false liefert, wenn der Wert
		// nur unverändert war. Nur der gelesene Wert sagt, ob es wirklich steht.
		if ( 'active' !== get_option( self::OPTION_CONTAINER ) ) {
			$result->fail( 'container', 'Die Flexbox Container liessen sich nicht aktivieren.' );

			return;
		}

		$result->ok( 'container', 'Flexbox Container aktiviert.' );
	}
}
