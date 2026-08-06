# Changelog

Neueste Einträge oben.

## 2026-08-06 – Deutlich vereinfacht (Version 1.3.0)

Kernbefund: Die Prüfung `is_container_active()` las genau die Einträge zurück, die `activate()` zwei Zeilen vorher selbst geschrieben hatte. Sie konnte also nie fehlschlagen. Die komplette Warteschleife aus vier Hooks, zwei Merker-Optionen und einer Erfolgskontrolle war ohne Wirkung.

- **Vier Hooks auf einen reduziert.** Das ganze Setup läuft jetzt in `register_activation_hook` von oben nach unten durch, inklusive der Elementor-Container. Übrig bleibt `admin_init` für Erfolgsmeldung und Selbstabschaltung, denn ein Plugin kann sich nicht abschalten, während WordPress es gerade einschaltet.
- **Drei Optionen auf eine reduziert:** nur noch `asu_setup_done`. Die alten Namen (`asu_base_done`, `asu_container_pending`, `asu_container_ok`) werden nicht mehr benutzt. Auf einer Test-Installation, auf der eine ältere Version schon lief, bleiben sie als Karteileichen in der Datenbank liegen.
- **Sieben Klassen auf vier reduziert.** Entfallen: `ASU_Options` (war nur eine Namensliste), `ASU_Admin_Notices` (eine einzige Meldung, jetzt in `ASU_Plugin`), `ASU_Wp_Admin` (die zwei Nachlade-Helfer sind jetzt private Methoden in `ASU_Cleanup`, wo sie gebraucht werden). `ASU_Elementor_Container` heißt jetzt `ASU_Elementor`.
- **Elementor-Schnittstelle entfernt.** Der Aufruf über `\Elementor\Plugin::$instance->experiments` war 30 Zeilen Versionsraterei und schrieb am Ende dieselben Einträge, die jetzt direkt gesetzt werden. Bewusste Abwägung: einfacher Code gegen die theoretische Möglichkeit, dass Elementor die Speicherung umbaut. Falls das passiert, ist `ASU_Elementor::enable_containers()` die einzige anzupassende Stelle.
- Von 755 auf 371 Zeilen.

## 2026-08-06 – Theme Builder entfernt (Version 1.2.0)

- Die Erstellung der Header- und Footer-Templates im Elementor Pro Theme Builder wurde entfernt, weil sie in der Praxis nicht funktioniert hat. Header und Footer werden wieder von Hand angelegt.
- Entfallen: `ASU_Theme_Builder`, die Optionen `asu_theme_builder_done` und `asu_theme_builder_skipped`, die Warnmeldung "Elementor Pro war nicht aktiv", die Elementor-Pro-Voraussetzung in der README.
- Wahrscheinliche Ursache des Problems, falls es später doch gebraucht wird: Elementor Pro liest die Anzeigebedingungen nicht nur aus der Post-Meta `_elementor_conditions`, sondern hält sie zusätzlich in einem eigenen Zwischenspeicher. Wer nur die Meta schreibt, taucht dort nicht auf, das Template greift also nicht. Der alte Code steht im Git-Verlauf, Commit 8df7c15.

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
