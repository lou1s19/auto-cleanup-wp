# Changelog

Neueste Einträge oben.

## Unveröffentlicht – 2026-08-17

- `.gitignore` und `.github/workflows/ci.yml` ergänzt. Die CI lintet alle PHP-Dateien mit PHP 7.4 und 8.3 und prüft, dass Plugin-Header und `ASU_VERSION` dieselbe Nummer tragen. Noch nicht gepusht.
- Kompletter Review durchgeführt (Claude + Codex-Zweitmeinung). Syntax auf PHP 8.2 sauber. Offene Funde, noch nicht behoben:
  - `class-asu-site-setup.php:32` setzt `_wp_page_template` auf `elementor_full_width`. Diesen Slug gibt es in Elementor nicht, richtig wäre `elementor_header_footer`. Die Startseite bekommt dadurch stillschweigend das Standard-Template.
  - `class-asu-elementor.php:20-28` schreibt die Option `elementor_experimentation`. Elementor liest sie nie, es zählt nur `elementor_experiment-container` (Zeile 31). Toter Code plus Karteileiche in `wp_options`.
  - Kein Multisite-Schutz: `delete_theme()` und `delete_plugins()` löschen netzwerkweit, obwohl das Setup nur für eine Site gedacht ist.
  - Ist Hello Elementor nicht installiert, wird nicht umgeschaltet und das Parent-Theme eines aktiven Child-Themes kann gelöscht werden.
  - Die `try/catch` in `ASU_Cleanup` fangen ins Leere: `delete_theme()` und `delete_plugins()` liefern `WP_Error`, sie werfen keine Exception. Fehler bleiben unbemerkt, die Erfolgsmeldung erscheint trotzdem.
  - `admin_init` feuert auch auf `admin-ajax.php`. Trifft es dort zuerst, deaktiviert sich das Plugin korrekt, aber die Erfolgsmeldung sieht niemand.
- Branch `origin/refactor/oop-struktur` zeigt auf denselben Commit wie `main`, ist also vollständig gemergt und kann weg.

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
