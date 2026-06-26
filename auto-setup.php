<?php
/**
 * Plugin Name: Auto Cleanup WP
 * Description: Automatically removes the default WordPress post and page, creates a static "Home" page (Elementor Full Width), creates Elementor Pro Theme Builder header/footer templates, sets permalinks to /%postname%/, and enables Elementor Flexbox Containers. After successfully enabling the container feature, the plugin automatically deactivates itself.
 * WARNING: This plugin permanently deletes the default WordPress content (including the default post and page). Depending on your setup or modifications, existing content may also be affected. Use only on a fresh WordPress installation or after creating a full backup. This action cannot be undone.
 * Version: 1.0.6
 * Author: Louis
 */

if ( ! defined('ABSPATH') ) { exit; }

if ( ! defined('ASU_BASE_DONE') ) {
	define('ASU_BASE_DONE', 'asu_base_done');
}
if ( ! defined('ASU_CONTAINER_OK') ) {
	define('ASU_CONTAINER_OK', 'asu_container_ok');
}
if ( ! defined('ASU_CONTAINER_PENDING') ) {
	define('ASU_CONTAINER_PENDING', 'asu_container_pending');
}
if ( ! defined('ASU_THEME_BUILDER_DONE') ) {
	define('ASU_THEME_BUILDER_DONE', 'asu_theme_builder_done');
}
if ( ! defined('ASU_THEME_BUILDER_SKIPPED') ) {
	define('ASU_THEME_BUILDER_SKIPPED', 'asu_theme_builder_skipped');
}

/**
 * 1) Einmal-Setup beim Aktivieren: Cleanup, Startseite, Permalinks
 */
register_activation_hook(__FILE__, function () {

    // (a) Beiträge/Seiten entfernen
    $items = get_posts([
        'post_type'   => ['post','page'],
        'numberposts' => -1,
        'post_status' => ['publish','draft','pending','future','private','trash'],
    ]);
    foreach ( $items as $it ) {
        wp_delete_post($it->ID, true);
    }

    // (b) Startseite erstellen & als statische Homepage setzen
    $home_id = wp_insert_post([
        'post_title'  => 'Startseite',
        'post_status' => 'publish',
        'post_type'   => 'page',
    ]);
    if ( $home_id && ! is_wp_error($home_id) ) {
        update_option('show_on_front', 'page');
        update_option('page_on_front', $home_id);

        // Hello/Elementor: Full-Width-Template setzen
        update_post_meta($home_id, '_wp_page_template', 'elementor_full_width');
    }

    // (c) Permalinks = /%postname%/
    global $wp_rewrite;
    if ( $wp_rewrite ) {
        $wp_rewrite->set_permalink_structure('/%postname%/');
        $wp_rewrite->flush_rules();
    }

	// (d) Sichtbarkeit: Suchmaschinen davon abhalten, diese Website zu indexieren (Häkchen setzen)
	update_option('blog_public', '0');

		// (e) Themes bereinigen: nur Hello + Hello Child behalten
		asu_include_admin_theme_functions();

	$keep_stylesheets = ['hello-elementor','hello','hello-child','hello-elementor-child'];
	$current_stylesheet = get_option('stylesheet');
		$hello_preferred = function_exists('wp_get_theme') ? wp_get_theme('hello-elementor') : null;
		$hello_fallback = function_exists('wp_get_theme') ? wp_get_theme('hello') : null;
	$hello_available_stylesheet = '';
	if ( $hello_preferred && $hello_preferred->exists() ) {
		$hello_available_stylesheet = 'hello-elementor';
	} elseif ( $hello_fallback && $hello_fallback->exists() ) {
		$hello_available_stylesheet = 'hello';
	}
		if ( $hello_available_stylesheet && $current_stylesheet !== $hello_available_stylesheet && function_exists('switch_theme') ) {
		// vor Löschen auf Hello umschalten, um aktives Theme nicht zu löschen
		switch_theme($hello_available_stylesheet);
		$current_stylesheet = $hello_available_stylesheet;
	}

	$themes = function_exists('wp_get_themes') ? wp_get_themes() : [];
	foreach ( $themes as $stylesheet => $theme_obj ) {
		$template = method_exists($theme_obj, 'get_template') ? $theme_obj->get_template() : '';
		if ( in_array($stylesheet, $keep_stylesheets, true) || in_array($template, $keep_stylesheets, true) ) {
			continue;
		}
		// aktives Theme nicht löschen
		if ( $stylesheet === $current_stylesheet ) {
			continue;
		}
		// Theme löschen
			if ( function_exists('delete_theme') ) {
				try { delete_theme($stylesheet); } catch ( \Exception $e ) { /* ignore */ }
			}
		}

		// (f) Plugins bereinigen: Hello Dolly + Akismet entfernen
		asu_include_admin_plugin_functions();
		$targets = ['hello.php', 'akismet/akismet.php'];
		$installed = function_exists('get_plugins') ? get_plugins() : [];
		foreach ( $installed as $plugin_file => $data ) {
			if ( in_array($plugin_file, $targets, true) ) {
				// zuerst deaktivieren, dann löschen
				if ( function_exists('deactivate_plugins') ) {
					try { deactivate_plugins($plugin_file, true); } catch ( \Exception $e ) { /* ignore */ }
				}
				if ( function_exists('delete_plugins') ) {
					try { delete_plugins([ $plugin_file ]); } catch ( \Exception $e ) { /* ignore */ }
				}
			}
		}

	// (g) Elementor Pro Theme Builder: Header + Footer erstellen und auf Entire Site setzen
	asu_create_theme_builder_header_footer();

    // Flags
    update_option(ASU_BASE_DONE,         1);
    update_option(ASU_CONTAINER_PENDING, 1);
});

/**
 * 2) Elementor: Container wirklich aktivieren (über API + Fallback)
 */
if ( ! function_exists('asu_is_container_active') ) {
function asu_is_container_active() {
    // zentrale Options-Struktur (neu/alt)
    $experiments = get_option('elementor_experimentation', []);
    if ( is_array($experiments) ) {
        if ( isset($experiments['container']) && $experiments['container'] === 'active' ) return true;
        if ( isset($experiments['elementor_container']) && $experiments['elementor_container'] === 'active' ) return true;
    }
    // single option (Select-Name in Elementor UI)
    $single = get_option('elementor_experiment-container');
    return ($single === 'active');
}
}

if ( ! function_exists('asu_activate_container') ) {
function asu_activate_container() {
    // 1) Elementor-API
    if ( did_action('elementor/loaded') ) {
        try {
            // Neuer Weg über Plugin-Instanz
            if (
				class_exists('\Elementor\Plugin')
					&& isset(\Elementor\Plugin::$instance->experiments)
					&& is_callable([ \Elementor\Plugin::$instance->experiments, 'set_feature_state' ])
			) {
	                \Elementor\Plugin::$instance->experiments->set_feature_state('container', 'active');
	            }
	            // Alternativ: direkter Experiments-Manager
	            if (
					class_exists('\Elementor\Core\Experiments\Manager')
						&& is_callable([ '\Elementor\Core\Experiments\Manager', 'get_instance' ])
					) {
						$experiments_manager = \Elementor\Core\Experiments\Manager::get_instance();
						if ( $experiments_manager && is_callable([ $experiments_manager, 'set_feature_state' ]) ) {
		                $experiments_manager->set_feature_state('container', 'active');
					}
	            }
	        } catch (\Exception $e) {
	            // Falls API nicht greift, übernimmt der Fallback unten
	        }
	    }

    // 2) Fallback: Optionen direkt setzen (versions-tolerant)
    $experiments = get_option('elementor_experimentation', []);
    if ( ! is_array($experiments) ) { $experiments = []; }
    $experiments['container']           = 'active'; // neuere Versionen
    $experiments['elementor_container'] = 'active'; // ältere Versionen
    update_option('elementor_experimentation', $experiments);

    // Einzel-Option wie im <select name="elementor_experiment-container">
    update_option('elementor_experiment-container', 'active');
}
}

if ( ! function_exists('asu_include_admin_plugin_functions') ) {
function asu_include_admin_plugin_functions() {
	if ( ! function_exists('deactivate_plugins') || ! function_exists('get_plugins') || ! function_exists('delete_plugins') ) {
		$plugin_file = ABSPATH . 'wp-admin/includes/plugin.php';
		if ( file_exists($plugin_file) ) {
			require_once $plugin_file;
		}
	}
}
}

if ( ! function_exists('asu_include_admin_theme_functions') ) {
function asu_include_admin_theme_functions() {
	if ( ! function_exists('wp_get_themes') || ! function_exists('switch_theme') ) {
		$theme_file = ABSPATH . 'wp-includes/theme.php';
		if ( file_exists($theme_file) ) {
			require_once $theme_file;
		}
	}
	if ( ! function_exists('delete_theme') ) {
		$admin_theme_file = ABSPATH . 'wp-admin/includes/theme.php';
		if ( file_exists($admin_theme_file) ) {
			require_once $admin_theme_file;
		}
	}
}
}

if ( ! function_exists('asu_is_elementor_pro_active') ) {
function asu_is_elementor_pro_active() {
	return defined('ELEMENTOR_PRO_VERSION') || class_exists('\ElementorPro\Plugin');
}
}

if ( ! function_exists('asu_elementor_element_id') ) {
function asu_elementor_element_id() {
	return substr(md5(wp_rand() . microtime()), 0, 7);
}
}

if ( ! function_exists('asu_get_existing_theme_builder_template') ) {
function asu_get_existing_theme_builder_template($type) {
	$templates = get_posts([
		'post_type'      => 'elementor_library',
		'post_status'    => ['publish','draft','pending','future','private'],
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
}

if ( ! function_exists('asu_get_theme_builder_template_data') ) {
function asu_get_theme_builder_template_data($type) {
	if ( $type === 'header' ) {
		return [
			[
				'id'       => asu_elementor_element_id(),
				'elType'   => 'container',
				'settings' => [
					'content_width'      => 'boxed',
					'flex_direction'     => 'row',
					'justify_content'    => 'space-between',
					'align_items'        => 'center',
					'padding'            => [
						'unit'     => 'px',
						'top'      => '20',
						'right'    => '20',
						'bottom'   => '20',
						'left'     => '20',
						'isLinked' => false,
					],
					'background_background' => 'classic',
					'background_color'      => '#ffffff',
				],
				'elements' => [
					[
						'id'         => asu_elementor_element_id(),
						'elType'     => 'widget',
						'widgetType' => 'heading',
						'settings'   => [
							'title'       => get_bloginfo('name') ?: 'Website',
							'header_size' => 'div',
						],
						'elements'   => [],
					],
					[
						'id'         => asu_elementor_element_id(),
						'elType'     => 'widget',
						'widgetType' => 'text-editor',
						'settings'   => [
							'editor' => 'Navigation hier bearbeiten',
						],
						'elements'   => [],
					],
				],
			],
		];
	}

	return [
		[
			'id'       => asu_elementor_element_id(),
			'elType'   => 'container',
			'settings' => [
				'content_width'          => 'boxed',
				'flex_direction'         => 'column',
				'justify_content'        => 'center',
				'align_items'            => 'center',
				'padding'                => [
					'unit'     => 'px',
					'top'      => '36',
					'right'    => '20',
					'bottom'   => '36',
					'left'     => '20',
					'isLinked' => false,
				],
				'background_background'  => 'classic',
				'background_color'       => '#111111',
			],
			'elements' => [
				[
					'id'         => asu_elementor_element_id(),
					'elType'     => 'widget',
					'widgetType' => 'text-editor',
					'settings'   => [
						'editor' => '(c) ' . date('Y') . ' ' . (get_bloginfo('name') ?: 'Website') . ' - Footer hier bearbeiten',
					],
					'elements'   => [],
				],
			],
		],
	];
}
}

if ( ! function_exists('asu_create_theme_builder_template') ) {
function asu_create_theme_builder_template($type, $title) {
	$template_id = asu_get_existing_theme_builder_template($type);
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
	update_post_meta($template_id, '_elementor_data', wp_slash(wp_json_encode(asu_get_theme_builder_template_data($type))));
	update_post_meta($template_id, '_elementor_page_settings', []);

	// Elementor Pro Theme Builder condition: display on the entire site.
	update_post_meta($template_id, '_elementor_conditions', ['include/general']);

	if ( defined('ELEMENTOR_VERSION') ) {
		update_post_meta($template_id, '_elementor_version', ELEMENTOR_VERSION);
	}

	return (int) $template_id;
}
}

if ( ! function_exists('asu_create_theme_builder_header_footer') ) {
function asu_create_theme_builder_header_footer() {
	if ( ! asu_is_elementor_pro_active() ) {
		update_option(ASU_THEME_BUILDER_SKIPPED, 1);
		return;
	}

	$header_id = asu_create_theme_builder_template('header', 'Global Header');
	$footer_id = asu_create_theme_builder_template('footer', 'Global Footer');

	if ( $header_id && $footer_id ) {
		update_option(ASU_THEME_BUILDER_DONE, [
			'header_id' => $header_id,
			'footer_id' => $footer_id,
		]);
		delete_option(ASU_THEME_BUILDER_SKIPPED);
	}
}
}

if ( ! function_exists('asu_maybe_finish_and_self_deactivate') ) {
function asu_maybe_finish_and_self_deactivate() {
    if ( asu_is_container_active() ) {
        update_option(ASU_CONTAINER_OK, 1);
        delete_option(ASU_CONTAINER_PENDING);

        // Plugin selbst deaktivieren
		asu_include_admin_plugin_functions();
		if ( function_exists('deactivate_plugins') ) {
	        deactivate_plugins(plugin_basename(__FILE__));
		}
	    }
	}
}

/**
 * 3) Hooks: so spät wie möglich nach Elementor + Fallbacks
 */
if ( ! function_exists('asu_try_set_container') ) {
function asu_try_set_container() {
    if ( get_option(ASU_CONTAINER_PENDING) && ! get_option(ASU_CONTAINER_OK) ) {
        asu_activate_container();
        // Selbst-Deaktivierung NICHT hier aufrufen – zu früh (plugins_loaded/elementor/init)
        // Wird stattdessen über admin_init erledigt, wenn alles geladen ist
    }
}
}
add_action('elementor/init', 'asu_try_set_container', 999);
add_action('plugins_loaded', 'asu_try_set_container', 999);
add_action('init', 'asu_try_set_container', 1);

// Selbst-Deaktivierung erst bei admin_init: alle Plugins & Hooks sind vollständig geladen
add_action('admin_init', function() {
    if ( get_option(ASU_CONTAINER_PENDING) && ! get_option(ASU_CONTAINER_OK) ) {
        asu_activate_container();
    }
    asu_maybe_finish_and_self_deactivate();
}, 1);


/**
 * Nachricht: alles geklappt
 */
add_action('admin_notices', function () {
    if ( current_user_can('manage_options') && get_option(ASU_CONTAINER_OK) ) {
        echo '<div class="notice notice-success is-dismissible">
                <p><strong>Auto Setup:</strong> Setup abgeschlossen – Container ist aktiv, Permalinks, Startseite und Theme-Builder-Templates sind gesetzt.</p>
              </div>';
        delete_option(ASU_CONTAINER_OK);
    }
	if ( current_user_can('manage_options') && get_option(ASU_THEME_BUILDER_SKIPPED) ) {
		echo '<div class="notice notice-warning is-dismissible">
				<p><strong>Auto Setup:</strong> Elementor Pro war nicht aktiv. Header und Footer wurden deshalb nicht im Theme Builder erstellt.</p>
			  </div>';
		delete_option(ASU_THEME_BUILDER_SKIPPED);
	}
});
