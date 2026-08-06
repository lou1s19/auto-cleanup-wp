# Auto Cleanup WP

WordPress-Plugin, das eine frische Installation einrichtet und sich danach selbst deaktiviert. Siehe `README.md` für den vollständigen Funktionsumfang.

- **Stack:** PHP, WordPress-Plugin-API. Keine Build-Tools, keine Abhängigkeiten, kein Composer, kein npm.
- **Zielumgebung:** WordPress 6.x mit Hello Elementor, Elementor und Elementor Pro.
- **Testen:** Plugin nach `/wp-content/plugins/` kopieren und auf einer frischen lokalen WordPress aktivieren. Es gibt keine automatisierten Tests.
- **Syntaxprüfung:** `php -l auto-setup.php && for f in includes/*.php; do php -l "$f"; done`

## Aufbau

`auto-setup.php` ist nur Startdatei: Plugin-Header, Konstanten, `require_once` der Klassen, Plugin starten. Die Logik liegt in `includes/`, eine Klasse pro Datei, Präfix `ASU_`.

`ASU_Plugin` steuert den Ablauf und meldet die Hooks an. Die anderen Klassen machen jeweils eine Sache und kennen einander nicht.

## Regeln für dieses Projekt

- **Eine Klasse pro Datei**, Dateiname `class-asu-<name>.php`, Klassenname `ASU_<Name>`. Neue Funktionalität kommt als neue Klasse dazu, nicht als weitere Methode in einer bestehenden, die schon eine andere Aufgabe hat.
- **Options-Namen nur über `ASU_Options`-Konstanten**, nie als Zeichenkette im Code. Bestehende Namen nicht ändern, sonst verliert ein bereits aktiviertes Plugin seinen Zustand.
- **Kommentare auf Deutsch**, wie im restlichen Code.
- **Das Plugin löscht endgültig.** Jede Änderung an `ASU_Cleanup` sehr genau prüfen und nur auf einer Wegwerf-Installation testen.
- Bei Änderungen an den Elementor-Templates: Elementor speichert Inhalte als JSON in der Meta `_elementor_data`, nicht als HTML. Struktur ist `container` > `widget`.
