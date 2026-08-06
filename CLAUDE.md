# Auto Cleanup WP

WordPress-Plugin, das eine frische Installation einrichtet und sich danach selbst deaktiviert. Siehe `README.md` für den vollständigen Funktionsumfang.

- **Stack:** PHP, WordPress-Plugin-API. Keine Build-Tools, keine Abhängigkeiten, kein Composer, kein npm.
- **Zielumgebung:** WordPress 6.x mit Hello Elementor und Elementor. Elementor Pro wird nicht vorausgesetzt.
- **Testen:** Plugin nach `/wp-content/plugins/` kopieren und auf einer frischen lokalen WordPress aktivieren. Es gibt keine automatisierten Tests.
- **Syntaxprüfung:** `php -l auto-setup.php && for f in includes/*.php; do php -l "$f"; done`

## Aufbau

`auto-setup.php` ist nur Startdatei: Plugin-Header, Konstanten, `require_once` der Klassen, Plugin starten. Die Logik liegt in `includes/`, eine Klasse pro Datei, Präfix `ASU_`.

`ASU_Plugin` steuert den Ablauf und meldet die Hooks an. Die anderen Klassen machen jeweils eine Sache und kennen einander nicht.

## Regeln für dieses Projekt

- **Einfach halten hat Vorrang.** Das Plugin wurde bewusst von sieben auf vier Klassen und von vier Hooks auf einen zurückgebaut (siehe CHANGELOG 1.3.0). Keine Abstraktion einbauen, die nur einen einzigen Aufrufer hat.
- **Eine Klasse pro Datei**, Dateiname `class-asu-<name>.php`, Klassenname `ASU_<Name>`.
- **Nur zwei Einstiegspunkte:** `register_activation_hook` für das Setup, `admin_init` für Erfolgsmeldung und Selbstabschaltung. Weitere Hooks nur mit gutem Grund.
- **Kommentare auf Deutsch**, wie im restlichen Code.
- **Das Plugin löscht endgültig.** Jede Änderung an `ASU_Cleanup` sehr genau prüfen und nur auf einer Wegwerf-Installation testen.
- Das automatische Anlegen von Theme-Builder-Templates wurde bewusst entfernt (siehe CHANGELOG). Nicht ohne Not wieder einbauen. Der alte Code steht in Commit 8df7c15.
