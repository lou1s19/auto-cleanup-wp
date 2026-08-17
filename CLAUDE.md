# Auto Cleanup WP

WordPress-Plugin, das eine frische Installation einrichtet und sich danach selbst deaktiviert. Siehe `README.md` für den vollständigen Funktionsumfang.

- **Stack:** PHP, WordPress-Plugin-API. Keine Build-Tools, keine Abhängigkeiten, kein Composer, kein npm.
- **Zielumgebung:** WordPress 6.x, PHP 7.4 oder neuer, Hello Elementor und Elementor. Elementor Pro wird nicht vorausgesetzt.
- **Tests:** `php tests/run.php`. Reines PHP, WordPress ist in `tests/bootstrap.php` als Attrappe nachgebaut.
- **Syntaxprüfung:** `find . -name '*.php' | xargs -n1 php -l`
- **PHP auf diesem Mac:** nicht im PATH, aber die App Local bringt eines mit. Finden mit
  `find ~/Library/Application\ Support/Local/lightning-services -name php -type f -perm +111 | head -1`
- **CI:** `.github/workflows/ci.yml`, lintet und testet auf PHP 7.4, 8.3 und 8.4 und vergleicht die Version im Plugin-Header mit der obersten Überschrift in `CHANGELOG.md`.

## Aufbau

`auto-setup.php` ist nur Startdatei: Plugin-Header, Autoloader anmelden, Plugin starten. Keine globalen Konstanten, keine globalen Variablen. Die Logik liegt in `includes/`, eine Klasse pro Datei, Präfix `ASU_`.

`ASU_Plugin` steuert den Ablauf und meldet die Hooks an. Die anderen Klassen machen jeweils eine Sache und kennen einander nicht, `ASU_Plugin` reicht durch, was gebraucht wird. `ASU_Result` ist das Protokoll, das jeder Schritt füllt.

## Regeln für dieses Projekt

- **Einfach halten hat Vorrang.** Keine Abstraktion einbauen, die nur einen einzigen Aufrufer hat und nichts einspart. Der Autoloader und `ASU_Result` sind die Ausnahmen: der eine ersetzt eine Pflegeliste, der andere macht die Erfolgsmeldung überhaupt erst ehrlich.
- **Eine Klasse pro Datei**, Dateiname `class-asu-<name>.php`, Klassenname `ASU_<Name>`. Der Autoloader leitet den Dateinamen aus dem Klassennamen ab, andere Namen werden nicht gefunden.
- **Nur zwei Einstiegspunkte:** `register_activation_hook` für das Setup, `admin_init` für Meldung und Selbstabschaltung. Weitere Hooks nur mit gutem Grund.
- **Kommentare auf Deutsch**, wie im restlichen Code. Nutzertexte durch `__()` mit der Text Domain `auto-cleanup-wp`.
- **Das Plugin löscht endgültig.** Jede Änderung an `ASU_Cleanup` sehr genau prüfen, Tests dazu schreiben und nur auf einer Wegwerf-Installation testen.
- **Rückgabewerte von WordPress prüfen, nicht auf Exceptions warten.** `delete_theme()`, `delete_plugins()` und `wp_insert_post()` liefern im Fehlerfall ein `WP_Error`, sie werfen nichts. Dafür gibt es `ASU_Result::catch_wp_error()`.
- **Fremde Bezeichner nachschlagen, nicht raten.** Der Slug `elementor_full_width` sah plausibel aus, existierte aber nie, und WordPress ignoriert unbekannte Template-Slugs stillschweigend. Alles, was aus Elementor stammt, steht als Konstante in `ASU_Elementor`.
- **Neue Funktion oder Fehlerbehebung heißt: Test dazu.** Ein Test, der nicht rot wird, wenn man den Fehler wieder einbaut, zählt nicht.
- Das automatische Anlegen von Theme-Builder-Templates wurde bewusst entfernt (siehe CHANGELOG 1.0.3). Nicht ohne Not wieder einbauen. Der alte Code steht in Commit 8df7c15.
