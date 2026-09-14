# Changelog

Neueste Einträge oben.

## 1.2.1 (2026-09-14)

Aufräumarbeit, Verhalten unverändert.

- Doc-Blöcke durch echte PHP-Typen ersetzt: typisierte Eigenschaften, Parameter- und Rückgabetypen.
- Nur noch Kommentare, die eine Begründung liefern.
- Lizenz vereinheitlicht, `LICENSE` war GPL-2.0, Header und README nannten MIT.
- Entwicklerdoku heisst jetzt `DEVELOPMENT.md`.

## 1.2.0 (2026-09-02)

Zwei Bremsen für den Fall, dass das Plugin auf einer nicht mehr frischen Website landet.

- Setup bricht ab, wenn mehr als 5 Beiträge und Seiten vorliegen. Freigabe über `define( 'ASU_ALLOW_ON_EXISTING_SITE', true );`.
- `asu_setup_ran` wird vor dem Löschen geschrieben, nicht danach. Bricht PHP mittendrin ab, war die Notiz sonst nie gesetzt, und der nächste Versuch löschte den Rest.
- Vier Tests dazu, beide Bremsen per absichtlicher Sabotage gegengeprüft.

## 1.1.0 (2026-08-17)

Review, alle Funde behoben, Code durchgängig objektorientiert. Erstmals mit Tests und CI.

Behobene Fehler:

- Template-Slug `elementor_full_width` existiert in Elementor nicht, richtig ist `elementor_header_footer`. WordPress ignoriert unbekannte Slugs stillschweigend.
- Option `elementor_experimentation` wurde nie gelesen. Elementor bildet den Schlüssel als `elementor_experiment-` plus Name.
- Kein Schutz vor Multisite. Dort teilen sich alle Sites dieselben Theme- und Plugin-Dateien.
- Bei aktivem Child-Theme konnte das Parent gelöscht werden, die Website wäre weiss geworden.
- `try/catch` fing ins Leere, WordPress liefert `WP_Error` statt zu werfen. Jetzt wird der Rückgabewert geprüft, der äussere `catch` fängt `Throwable`.
- `admin_init` feuert auch bei AJAX, Cron und REST. Dort wurde das Protokoll verbraucht, bevor es jemand sehen konnte. Dasselbe bei angemeldeten Nutzern ohne Adminrechte.
- `auto-draft` fehlte in der Liste der Post-Status, ebenso von Plugins angemeldete Status.
- Ein fremdes Theme im Ordner `hello` galt als Hello Elementor. Jetzt wird der Theme-Name geprüft.
- Nach fehlgeschlagenem Theme-Wechsel wird kein Theme mehr gelöscht.
- Sperre gegen eine zweite Aktivierung über `asu_setup_ran`.

Umbau:

- Autoloader statt `require_once`-Liste, keine globalen Konstanten und Variablen mehr.
- Neue Klasse `ASU_Result`. Die Meldung im Backend ist dadurch ehrlich: grün nur, wenn wirklich alles lief.
- Alle Klassen `final`, Bausteine für die Tests hereinreichbar, Ausgaben escaped und über `__()` übersetzbar.
- 38 Tests, `.github/workflows/ci.yml` lintet und testet auf PHP 7.4, 8.3 und 8.4.

Bewusst nicht geändert: Mediendateien bleiben stehen, und nach einem einzelnen Fehlschlag läuft das Setup weiter. Ein halb durchgelaufenes Setup ist schlechter als ein vollständiges mit Fehlermeldung.

## 1.0.3 (2026-08-06)

Von einer Datei auf vier Klassen umgebaut, von 755 auf 371 Zeilen.

- Theme-Builder-Erstellung entfernt, sie hat in der Praxis nie funktioniert. Elementor Pro liest die Anzeigebedingungen nicht nur aus `_elementor_conditions`.
- Die alte Erfolgskontrolle las genau die Optionen zurück, die zwei Zeilen vorher geschrieben wurden, konnte also nie fehlschlagen. Samt Warteschleife aus vier Hooks entfernt.
- Elementor-API-Aufruf entfernt, er schrieb dieselben Optionen, die jetzt direkt gesetzt werden.
- README auf Deutsch neu geschrieben, nennt jetzt auch das Löschen der Themes und Plugins.
