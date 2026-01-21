<?php
/**
 * Plugin Name: Auto Setup (Cleanup von WP)
 * Description: Löscht Standard-Beiträge/Seiten, erstellt eine statische „Startseite“ (Elementor Full Width), setzt Permalinks auf /%postname%/ und aktiviert den Elementor-Container. Deaktiviert sich nach erfolgreichem Container-Setup selbst.
 * Version: 1.0.5
 * Author: Louis
 */

if ( ! defined('ABSPATH') ) { exit; }

define('ASU_BASE_DONE',                 'asu_base_done');
define('ASU_CONTAINER_OK',              'asu_container_ok');
define('ASU_CONTAINER_PENDING',         'asu_container_pending');

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
	if ( ! function_exists('wp_get_themes') ) {
		require_once ABSPATH . 'wp-includes/theme.php';
	}
	if ( ! function_exists('delete_theme') ) {
		require_once ABSPATH . 'wp-admin/includes/theme.php';
	}
	if ( ! function_exists('switch_theme') ) {
		require_once ABSPATH . 'wp-includes/theme.php';
	}

	$keep_stylesheets = ['hello-elementor','hello','hello-child','hello-elementor-child'];
	$current_stylesheet = get_option('stylesheet');
	$hello_preferred = wp_get_theme('hello-elementor');
	$hello_fallback = wp_get_theme('hello');
	$hello_available_stylesheet = '';
	if ( $hello_preferred && $hello_preferred->exists() ) {
		$hello_available_stylesheet = 'hello-elementor';
	} elseif ( $hello_fallback && $hello_fallback->exists() ) {
		$hello_available_stylesheet = 'hello';
	}
	if ( $hello_available_stylesheet && $current_stylesheet !== $hello_available_stylesheet ) {
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
		try { delete_theme($stylesheet); } catch ( \Throwable $e ) { /* ignore */ }
	}

	// (f) Plugins bereinigen: Hello Dolly + Akismet entfernen
	if ( ! function_exists('get_plugins') ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	if ( ! function_exists('delete_plugins') ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$targets = ['hello.php', 'akismet/akismet.php'];
	$installed = function_exists('get_plugins') ? get_plugins() : [];
	foreach ( $installed as $plugin_file => $data ) {
		if ( in_array($plugin_file, $targets, true) ) {
			// zuerst deaktivieren, dann löschen
			try { deactivate_plugins($plugin_file, true); } catch ( \Throwable $e ) { /* ignore */ }
			try { delete_plugins([ $plugin_file ]); } catch ( \Throwable $e ) { /* ignore */ }
		}
	}

    // Flags
    update_option(ASU_BASE_DONE,         1);
    update_option(ASU_CONTAINER_PENDING, 1);
});

/**
 * 2) Elementor: Container wirklich aktivieren (über API + Fallback)
 */
function asu_is_container_active(): bool {
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

function asu_activate_container(): void {
    // 1) Elementor-API
    if ( did_action('elementor/loaded') ) {
        try {
            // Neuer Weg über Plugin-Instanz
            if ( class_exists('\Elementor\Plugin') && isset(\Elementor\Plugin::$instance->experiments) ) {
                \Elementor\Plugin::$instance->experiments->set_feature_state('container', 'active');
            }
            // Alternativ: direkter Experiments-Manager
            if ( class_exists('\Elementor\Core\Experiments\Manager') ) {
                \Elementor\Core\Experiments\Manager::get_instance()->set_feature_state('container', 'active');
            }
        } catch (\Throwable $e) {
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

function asu_maybe_finish_and_self_deactivate() {
    if ( asu_is_container_active() ) {
        update_option(ASU_CONTAINER_OK, 1);
        delete_option(ASU_CONTAINER_PENDING);

        // Plugin selbst deaktivieren
        if ( ! function_exists('deactivate_plugins') ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        deactivate_plugins(plugin_basename(__FILE__));
    }
}

/**
 * 3) Hooks: so spät wie möglich nach Elementor + Fallbacks
 */
function asu_try_set_container() {
    if ( get_option(ASU_CONTAINER_PENDING) && ! get_option(ASU_CONTAINER_OK) ) {
        asu_activate_container();
        asu_maybe_finish_and_self_deactivate();
    }
}
add_action('elementor/init', 'asu_try_set_container', 999);
add_action('plugins_loaded', 'asu_try_set_container', 999);
add_action('admin_init', 'asu_try_set_container', 1);
add_action('init', 'asu_try_set_container', 1);

/**
 * Nachricht: alles geklappt
 */
add_action('admin_notices', function () {
    if ( current_user_can('manage_options') && get_option(ASU_CONTAINER_OK) ) {
        echo '<div class="notice notice-success is-dismissible">
                <p><strong>Auto Setup:</strong> Setup abgeschlossen – Container ist aktiv, Permalinks und Startseite gesetzt.</p>
              </div>';
        delete_option(ASU_CONTAINER_OK);
    }
});
