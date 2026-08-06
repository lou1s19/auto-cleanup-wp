# Changelog

Neueste Einträge oben.

## 1.0.3 – 2026-08-06

Auf einer lokalen Test-WordPress geprüft, läuft.

- **Von einer Datei auf vier Klassen umgebaut.** `auto-setup.php` ist nur noch Startdatei. Die Logik liegt in `includes/`: `ASU_Plugin` (Ablauf), `ASU_Cleanup`, `ASU_Site_Setup`, `ASU_Elementor`.
- **Theme-Builder-Erstellung entfernt.** Das automatische Anlegen von Header- und Footer-Templates hat in der Praxis nicht funktioniert. Wahrscheinliche Ursache: Elementor Pro liest die Anzeigebedingungen nicht nur aus der Post-Meta `_elementor_conditions`, sondern hält sie zusätzlich in einem eigenen Zwischenspeicher. Alter Code in Commit 8df7c15.
- **Ablauf stark vereinfacht.** Die frühere Erfolgskontrolle las genau die Optionen zurück, die zwei Zeilen vorher selbst geschrieben wurden, konnte also nie fehlschlagen. Die darauf aufgebaute Warteschleife aus vier Hooks und drei Merker-Optionen ist ersatzlos weg. Übrig: `register_activation_hook` für das Setup, `admin_init` für Erfolgsmeldung und Selbstabschaltung, eine Option `asu_setup_done`.
- **Elementor-API-Aufruf entfernt.** Er schrieb dieselben Optionen, die jetzt direkt gesetzt werden. Falls Elementor die Speicherung umbaut, ist `ASU_Elementor::enable_containers()` die einzige anzupassende Stelle.
- README auf Deutsch neu geschrieben. Sie nennt jetzt auch, was vorher undokumentiert war: Löschen der Themes, Löschen von Hello Dolly und Akismet, Setzen von "Suchmaschinen blockieren".
- Von 755 auf 371 Zeilen.

### Hinweise

- Die Versionsnummer wurde bewusst auf 1.0.3 gesetzt. Die alte Einzeldatei stand auf 1.0.6.
- Alte Optionen aus früheren Versionen (`asu_base_done`, `asu_container_pending`, `asu_container_ok`, `asu_theme_builder_*`) werden nicht mehr benutzt und bleiben auf Testinstallationen als Karteileichen liegen.
- Texte sind fest auf Deutsch verdrahtet, ohne WordPress-Übersetzungsfunktionen.
- Keine automatisierten Tests.
