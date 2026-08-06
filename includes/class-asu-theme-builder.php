<?php
/**
 * Legt im Elementor Pro Theme Builder ein globales Header- und Footer-Template an.
 *
 * Beide Templates werden auf "gesamte Website" gestellt und enthalten eine einfache
 * Startstruktur, die danach in Elementor bearbeitet wird.
 */

if ( ! defined('ABSPATH') ) { exit; }

class ASU_Theme_Builder {

	/**
	 * Erstellt Header und Footer. Ohne Elementor Pro passiert nichts,
	 * stattdessen wird ein Hinweis für den Admin-Bereich gemerkt.
	 */
	public function create_header_and_footer() {
		if ( ! $this->is_elementor_pro_active() ) {
			update_option(ASU_Options::THEME_BUILDER_SKIPPED, 1);
			return;
		}

		$header_id = $this->create_template('header', 'Global Header');
		$footer_id = $this->create_template('footer', 'Global Footer');

		if ( $header_id && $footer_id ) {
			update_option(ASU_Options::THEME_BUILDER_DONE, [
				'header_id' => $header_id,
				'footer_id' => $footer_id,
			]);
			delete_option(ASU_Options::THEME_BUILDER_SKIPPED);
		}
	}

	/**
	 * Ist Elementor Pro aktiv?
	 *
	 * @return bool
	 */
	public function is_elementor_pro_active() {
		return defined('ELEMENTOR_PRO_VERSION') || class_exists('\ElementorPro\Plugin');
	}

	/**
	 * Legt ein Template an oder aktualisiert ein vorhandenes gleichen Typs.
	 *
	 * @param string $type  'header' oder 'footer'.
	 * @param string $title Titel des Templates.
	 * @return int ID des Templates, 0 bei Fehler.
	 */
	private function create_template($type, $title) {
		$template_id = $this->find_existing_template($type);

		if ( ! $template_id ) {
			$template_id = wp_insert_post([
				'post_title'  => $title,
				'post_status' => 'publish',
				'post_type'   => 'elementor_library',
			]);
		}

		if ( ! $template_id || is_wp_error($template_id) ) {
			return 0;
		}

		update_post_meta($template_id, '_elementor_edit_mode', 'builder');
		update_post_meta($template_id, '_elementor_template_type', $type);
		update_post_meta($template_id, '_elementor_location', $type);
		update_post_meta($template_id, '_elementor_data', wp_slash(wp_json_encode($this->get_template_structure($type))));
		update_post_meta($template_id, '_elementor_page_settings', []);

		// Anzeigebedingung im Theme Builder: auf der gesamten Website.
		update_post_meta($template_id, '_elementor_conditions', ['include/general']);

		if ( defined('ELEMENTOR_VERSION') ) {
			update_post_meta($template_id, '_elementor_version', ELEMENTOR_VERSION);
		}

		return (int) $template_id;
	}

	/**
	 * Sucht ein vorhandenes Theme-Builder-Template dieses Typs,
	 * damit bei erneutem Lauf kein Duplikat entsteht.
	 *
	 * @param string $type 'header' oder 'footer'.
	 * @return int ID oder 0.
	 */
	private function find_existing_template($type) {
		$templates = get_posts([
			'post_type'      => 'elementor_library',
			'post_status'    => ['publish', 'draft', 'pending', 'future', 'private'],
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => [
				[
					'key'   => '_elementor_template_type',
					'value' => $type,
				],
			],
		]);

		return ! empty($templates) ? (int) $templates[0] : 0;
	}

	/**
	 * Der Inhalt des Templates als Elementor-Struktur.
	 * Elementor speichert seine Inhalte als JSON in der Datenbank, nicht als HTML.
	 *
	 * @param string $type 'header' oder 'footer'.
	 * @return array
	 */
	private function get_template_structure($type) {
		$site_name = get_bloginfo('name') ?: 'Website';

		if ( $type === 'header' ) {
			return [
				[
					'id'       => $this->new_element_id(),
					'elType'   => 'container',
					'settings' => [
						'content_width'         => 'boxed',
						'flex_direction'        => 'row',
						'justify_content'       => 'space-between',
						'align_items'           => 'center',
						'padding'               => $this->padding('20', '20', '20', '20'),
						'background_background' => 'classic',
						'background_color'      => '#ffffff',
					],
					'elements' => [
						$this->widget('heading', [
							'title'       => $site_name,
							'header_size' => 'div',
						]),
						$this->widget('text-editor', [
							'editor' => 'Navigation hier bearbeiten',
						]),
					],
				],
			];
		}

		return [
			[
				'id'       => $this->new_element_id(),
				'elType'   => 'container',
				'settings' => [
					'content_width'         => 'boxed',
					'flex_direction'        => 'column',
					'justify_content'       => 'center',
					'align_items'           => 'center',
					'padding'               => $this->padding('36', '20', '36', '20'),
					'background_background' => 'classic',
					'background_color'      => '#111111',
				],
				'elements' => [
					$this->widget('text-editor', [
						'editor' => '(c) ' . date('Y') . ' ' . $site_name . ' - Footer hier bearbeiten',
					]),
				],
			],
		];
	}

	/**
	 * Baut einen einzelnen Elementor-Widget-Eintrag.
	 *
	 * @param string $widget_type Elementor-Widgetname, z. B. 'heading'.
	 * @param array  $settings    Einstellungen des Widgets.
	 * @return array
	 */
	private function widget($widget_type, array $settings) {
		return [
			'id'         => $this->new_element_id(),
			'elType'     => 'widget',
			'widgetType' => $widget_type,
			'settings'   => $settings,
			'elements'   => [],
		];
	}

	/**
	 * Innenabstand im Format, das Elementor erwartet.
	 *
	 * @return array
	 */
	private function padding($top, $right, $bottom, $left) {
		return [
			'unit'     => 'px',
			'top'      => $top,
			'right'    => $right,
			'bottom'   => $bottom,
			'left'     => $left,
			'isLinked' => false,
		];
	}

	/**
	 * Elementor gibt jedem Element eine eigene, zufällige ID aus 7 Zeichen.
	 *
	 * @return string
	 */
	private function new_element_id() {
		return substr(md5(wp_rand() . microtime()), 0, 7);
	}
}
