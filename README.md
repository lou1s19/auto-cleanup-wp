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
- 🔗 Changes the permalink structure to `/%postname%/`
- 📦 Enables **Elementor Flexbox Containers**
- ✅ Automatically deactivates itself after successfully enabling the Elementor Container feature

## Requirements

- WordPress 6.x or newer
- Elementor installed (recommended)

## Installation

1. Download or clone this repository.
2. Upload the plugin to `/wp-content/plugins/`.
3. Activate **Auto Cleanup WP** from the WordPress Plugins page.
4. The plugin will perform its setup automatically.
5. Once finished, it will deactivate itself.

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
