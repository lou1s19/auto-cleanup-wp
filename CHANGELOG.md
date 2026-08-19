# Changelog

Neueste Einträge oben.

## 1.1.0 – 2026-08-17

Kompletter Review durch Claude und Codex, danach alle Funde behoben und der Code durchgängig objektorientiert aufgebaut. Erstmals mit Tests und CI.

### Behobene Fehler

- **Falscher Elementor-Template-Slug.** `_wp_page_template` wurde auf `elementor_full_width` gesetzt. Diesen Slug gibt es in Elementor nicht, geprüft gegen `modules/page-templates/module.php`. Es gibt nur `elementor_canvas`, `elementor_header_footer` und `elementor_theme`. Der Anzeigename "Elementor Full Width" gehört zu `elementor_header_footer`. WordPress ignoriert unbekannte Slugs stillschweigend, die Startseite bekam also das Standard-Template, ohne dass es auffiel.
- **Tote Option `elementor_experimentation`.** Elementor liest sie nie, es bildet den Schlüssel als `elementor_experiment-` plus Name des Experiments. Die Zeilen schrieben nur eine Karteileiche in `wp_options`. Entfernt, übrig bleibt `elementor_experiment-container`.
- **Kein Schutz vor Multisite.** `delete_theme()` und `delete_plugins()` löschen Dateien, die sich alle Sites eines Netzwerks teilen. Eine Aktivierung hätte das ganze Netzwerk getroffen. Das Setup bricht jetzt ab und sagt im Backend, warum.
- **Parent-Theme konnte gelöscht werden.** Geschützt war nur das aktive Stylesheet. Fehlte Hello Elementor, wurde nicht umgeschaltet, und bei einem aktiven Child-Theme flog dessen Parent raus. Das Ergebnis wäre eine weisse Website gewesen. Jetzt sind das aktive Theme und sein Parent geschützt.
- **`try/catch` fing ins Leere.** `delete_theme()` und `delete_plugins()` liefern im Fehlerfall ein `WP_Error`, sie werfen keine Exception. Die Blöcke fingen also nie etwas, Fehler blieben unbemerkt, und die grüne Erfolgsmeldung erschien trotzdem. Jetzt wird der Rückgabewert geprüft, und der umschliessende `catch` fängt `Throwable` statt `Exception`, damit auch PHP-Fehler den Ablauf nicht stumm abbrechen.
- **`admin_init` feuert auch auf `admin-ajax.php`.** Traf dort eine Anfrage zuerst ein, und das können auch nicht eingeloggte Besucher auslösen, wurde die Notiz verbraucht. Das Plugin deaktivierte sich korrekt, aber niemand sah die Meldung. AJAX, Cron, REST und Autosave werden jetzt übersprungen.
- **Auto-Entwürfe blieben liegen.** `auto-draft` fehlte in der Liste der Post-Status.

### Nach der Codex-Zweitmeinung zusätzlich abgesichert

- **Sperre gegen eine zweite Aktivierung.** Das Setup hinterlässt die Option `asu_setup_ran` mit Datum. Sie bleibt für immer stehen. Wird das Plugin Monate später versehentlich noch einmal aktiviert, bricht es ab und löscht nichts. Das war der realistische Katastrophenfall: alle inzwischen entstandenen Inhalte wären weg gewesen. Wer den Lauf wirklich wiederholen will, löscht die Option vorher.
- **`finish()` prüft jetzt `activate_plugins`.** Auch ein Abonnent löst beim Aufruf seines Profils `admin_init` aus. Ohne die Prüfung hätte er das Protokoll verbraucht, das Plugin deaktiviert und die Meldung wäre für alle verschwunden. Dieselbe Fehlerklasse wie die AJAX-Falle.
- **Ein fremdes Theme im Ordner `hello` gilt nicht mehr als Hello Elementor.** Vorher entschied allein der Ordnername. Das Setup hätte auf ein wildfremdes Theme umgeschaltet und danach das eigentliche Theme endgültig gelöscht. Jetzt muss auch der Theme-Name passen.
- **Nach einem fehlgeschlagenen Theme-Wechsel wird kein Theme mehr gelöscht.** In dem Zustand ist unklar, was WordPress für aktiv hält, und ein falsch gelöschtes Theme kommt nicht zurück.
- **Eigene Post-Status von anderen Plugins werden mitgelöscht.** Die feste Statusliste deckte nur WordPress selbst ab, Seiten mit einem Plugin-Status wären liegen geblieben und später wieder aufgetaucht.

Bewusst nicht geändert: Anhänge und Mediendateien werden weiterhin nicht gelöscht, und nach einem einzelnen Fehlschlag läuft das Setup weiter, statt in der Mitte abzubrechen. Ein halb durchgelaufenes Setup ist schlechter als ein vollständiges mit einer Fehlermeldung.

### Umbau

- **Autoloader statt `require_once`-Liste.** `auto-setup.php` enthält nur noch den Plugin-Header und zwei Aufrufe. Keine globalen Konstanten (`ASU_VERSION`, `ASU_PATH`, `ASU_PLUGIN_FILE`) und keine globale `$asu_plugin`-Variable mehr.
- **Neue Klasse `ASU_Result`.** Jeder Schritt trägt ein, ob er geklappt hat. Das Protokoll überlebt als Option den Seitenaufruf. Die Meldung im Backend ist dadurch ehrlich: grün nur, wenn wirklich alles gelaufen ist, sonst gelb mit den fehlgeschlagenen Schritten im Klartext.
- **Alle Klassen `final`,** feste Listen als Klassenkonstanten statt als Instanzfelder. Die Bausteine dürfen in `ASU_Plugin` hereingereicht werden, damit die Tests eigene einsetzen können.
- Ausgaben sind escaped und über `__()` übersetzbar. Der Plugin-Header nennt jetzt `Requires at least`, `Requires PHP` und `Text Domain`.
- Das eigene Plugin steht nie auf der Löschliste, auch wenn jemand die Liste erweitert.

### Tests und CI

38 Tests in `tests/`, in reinem PHP. Kein Composer, kein PHPUnit, keine WordPress-Testsuite, passend zur Regel "keine Abhängigkeiten". Der Teil von WordPress, den das Plugin anfasst, ist in `tests/bootstrap.php` als Attrappe nachgebaut. Aufruf:

```
php tests/run.php
```

Jede der oben genannten Änderungen wurde einmal wieder rückgängig gemacht, um zu prüfen, dass auch wirklich ein Test rot wird. Alle zwölf wurden gefangen, jeweils von genau dem passenden Test.

`.github/workflows/ci.yml` lintet und testet auf PHP 7.4, 8.3 und 8.4 und vergleicht die Version im Plugin-Header mit der obersten Überschrift dieser Datei.

### Hinweise

- Die Option `asu_setup_done` heisst jetzt `asu_setup_result` und enthält das Protokoll statt einer 1. Sie wird nach dem Lesen gelöscht. Dauerhaft bleibt nur `asu_setup_ran` stehen, die Sperre gegen einen zweiten Lauf.
- **Mediendateien werden nicht gelöscht.** Auf einer frischen Installation gibt es keine, aber die README sagt es jetzt ausdrücklich.
- Alte Optionen aus früheren Versionen (`asu_base_done`, `asu_container_pending`, `asu_container_ok`, `asu_theme_builder_*`, `elementor_experimentation`) werden nicht mehr benutzt und bleiben auf Testinstallationen als Karteileichen liegen.
- Übersetzungsdateien gibt es keine. Die Texte sind Deutsch, aber über `__()` austauschbar.
- Auf einer echten WordPress-Installation ist diese Version noch nicht aktiviert worden. Die Tests laufen gegen eine Attrappe, sie ersetzen keinen Durchlauf auf einer Wegwerf-Installation.

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
- Texte sind fest auf Deutsch verdrahtet, ohne WordPress-Übersetzungsfunktionen.
- Keine automatisierten Tests.

---

## 2026-08-19 — Funde aus dem Sichtbarkeits-Check

- Erledigt am 19.08.2026: GPL-2.0-Lizenzdatei ergaenzt (WordPress-Pflicht, vorher lag das
  Plugin ohne Lizenz oeffentlich, also rechtlich unbenutzbar), dazu Repo-Topics gesetzt.
- Offen: Einreichung ins offizielle Verzeichnis auf wordpress.org. Dort gibt es eine
  eigene Suche mit Millionen Nutzern, auf GitHub sucht niemand nach WordPress-Plugins.
  Nötig sind eine `readme.txt` im WordPress-Format, ein Banner (1544x500), ein Icon
  (256x256) und eine Prüfung des Codes gegen die Plugin-Richtlinien.
- Wichtig bei der readme.txt: Der Warnhinweis, dass das Plugin beim Aktivieren alle
  Beiträge und Seiten endgültig loescht, muss ganz oben stehen. Sonst kommt das Plugin
  durch die Prüfung, aber die Bewertungen werden schlecht.
