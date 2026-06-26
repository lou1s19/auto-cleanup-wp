# Auto Cleanup WP

Automatically prepares a fresh WordPress installation by removing default content and applying common initial settings.

> ⚠️ **Warning**
> This plugin is intended for **new WordPress installations only**.
> Activating it on an existing website **will delete all pages and posts**.
> **Always create a full backup before using this plugin.** Deleted content cannot be recovered.

## Features

- 🗑️ Deletes all existing WordPress pages
- 🗑️ Deletes all existing WordPress posts
- 🏠 Creates a new static **Home** page
- 🎨 Sets the page template to **Elementor Full Width**
- 🧩 Creates **Elementor Pro Theme Builder** templates for global header and footer
- 🌐 Sets header and footer display conditions to the entire site
- 🔗 Changes the permalink structure to `/%postname%/`
- 📦 Enables **Elementor Flexbox Containers**
- ✅ Automatically deactivates itself after successfully enabling the Elementor Container feature

## Requirements

- WordPress 6.x or newer
- Elementor installed
- Elementor Pro installed and activated (recommended before running this plugin)

## Recommended Setup Order

For the cleanest setup, install and activate **Elementor** and **Elementor Pro** before activating this plugin.

Recommended order:

1. Install WordPress.
2. Install and activate the **Hello Elementor** theme.
3. Install and activate **Elementor**.
4. Install and activate **Elementor Pro**.
5. Activate **Auto Cleanup WP**.

This order is recommended because the plugin prepares the homepage for Elementor, creates global Theme Builder header and footer templates, and enables Elementor container settings.

## Installation

1. Download or clone this repository.
2. Upload the plugin to `/wp-content/plugins/`.
3. Install and activate **Elementor** and **Elementor Pro**.
4. Activate **Auto Cleanup WP** from the WordPress Plugins page.
5. The plugin will perform its setup automatically.
6. Once finished, it will deactivate itself.

## Header and Footer

After the plugin has finished, it creates the global header and footer in **Elementor Pro Theme Builder**:

1. Go to **Templates > Theme Builder**.
2. Open **Header** and edit **Global Header**.
3. Open **Footer** and edit **Global Footer**.
4. Confirm that both templates are published with the display condition **Entire Site**.

Using Elementor Pro templates is recommended because the header and footer can be edited in one place and automatically applied across the whole website.

The plugin creates simple starter templates. For a final branded layout, edit those templates directly in Elementor Pro Theme Builder or replace the starter structure with prepared Elementor template JSON files.

## Important

This plugin performs **destructive operations**.

It is designed for:
- ✅ Fresh WordPress installations
- ✅ Local development
- ✅ Staging environments

It is **not recommended** for:
- ❌ Existing production websites
- ❌ Websites containing important pages or posts

Always create a full backup before activation.

## License

MIT
