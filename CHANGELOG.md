# Changelog

Neueste Einträge oben.

## 2026-08-06 – Umbau auf Klassen, eine Datei pro Aufgabe (Version 1.1.0)

- Die bisherige Einzeldatei `auto-setup.php` (434 Zeilen, rein prozedural) wurde in acht Klassen unter `includes/` aufgeteilt. `auto-setup.php` ist jetzt nur noch Startdatei: Plugin-Header, Klassen laden, Plugin starten.
- Klassen: `ASU_Plugin` (Ablaufsteuerung), `ASU_Cleanup`, `ASU_Site_Setup`, `ASU_Elementor_Container`, `ASU_Theme_Builder`, `ASU_Admin_Notices`, `ASU_Options` (Options-Namen), `ASU_Wp_Admin` (lädt Admin-Funktionen nach).
- **Verhalten unverändert.** Geprüft wurde: identische Hooks samt Prioritäten, identische Options-Namen, Meta-Keys und Hook-Namen. Einziger Unterschied nach außen: die Admin-Meldungen sagen jetzt "Auto Cleanup WP" statt "Auto Setup" und werden über `esc_html()` ausgegeben.
- README neu geschrieben, auf Deutsch. Sie nennt jetzt auch die drei Dinge, die vorher undokumentiert waren: Löschen der Themes, Löschen von Hello Dolly und Akismet, und das Setzen von "Suchmaschinen blockieren".

### Offen / bekannte Schwächen

- `php -l` konnte nicht laufen, auf dem Rechner ist kein PHP installiert. Der Umbau wurde stattdessen mechanisch geprüft (Klammern-Balance, alle Klassen- und Methodenverweise, Vergleich aller Options- und Hook-Namen mit der alten Version). **Vor dem produktiven Einsatz einmal auf einer Test-WordPress aktivieren.**
- Texte sind fest auf Deutsch verdrahtet, keine Übersetzungsfunktionen (`__()`, Textdomain).
- `date('Y')` im Footer-Template nutzt die Server-Zeitzone statt der WordPress-Zeitzone. Besser wäre `wp_date('Y')`.
- Der Container-Aktivierungsversuch läuft bei jedem Seitenaufruf, solange er ansteht. Das ist unkritisch, weil das Plugin sich danach selbst abschaltet.
- Keine automatisierten Tests.

## Davor (Version 1.0.6 und früher)

Entwicklung als einzelne Plugin-Datei. Letzte Schritte laut Git-Verlauf: Theme Builder für Header und Footer ergänzt, Plugin umbenannt und Beschreibung überarbeitet.
