# Auto Cleanup WP

Räumt eine frische WordPress-Installation auf und richtet sie für Elementor ein. Einmal aktivieren statt zwanzig Klicks durchs Backend. Danach schaltet sich das Plugin selbst ab.

> ## Achtung, das Plugin löscht endgültig
>
> Beim Aktivieren werden **alle Beiträge und Seiten** gelöscht, dazu **alle Themes außer Hello Elementor** sowie **Hello Dolly und Akismet**. Nichts landet im Papierkorb.
>
> Nur auf einer frischen Installation oder nach einem vollständigen Backup einsetzen. Nie auf einer Live-Website.

## Was es macht

Beim Aktivieren, in dieser Reihenfolge:

1. Löscht alle Beiträge und Seiten, auch Entwürfe und Papierkorb. Damit sind "Hallo Welt" und die Beispielseite weg.
2. Legt eine leere Seite "Startseite" an und setzt sie als statische Startseite. Mit Elementor bekommt sie das Template "Elementor Full Width".
3. Setzt die Permalinks auf `/%postname%/`.
4. Blockiert Suchmaschinen. Vor dem Livegang wieder freigeben.
5. Löscht alle Themes außer Hello Elementor, nach einem Wechsel auf Hello Elementor.
6. Löscht Hello Dolly und Akismet.
7. Aktiviert die Elementor Flexbox Container.

Beim nächsten Backend-Aufruf zeigt es eine Meldung und deaktiviert sich selbst. Grün, wenn alles geklappt hat, sonst gelb mit der Liste der Probleme.

Mediathek, andere Inhaltstypen, Benutzer und Kommentare bleiben unangetastet. Header und Footer legt das Plugin nicht an, das macht man im Elementor Theme Builder.

## Wo es abbricht

Das Plugin löscht nicht, wenn eine dieser Bedingungen zutrifft. Es verändert dann gar nichts und sagt im Backend, warum:

- **Multisite.** Theme- und Plugin-Dateien gehören dort allen Sites gemeinsam.
- **Mehr als 5 Beiträge und Seiten.** Das sieht nicht nach einer frischen Installation aus. Wer es trotzdem braucht, setzt `define( 'ASU_ALLOW_ON_EXISTING_SITE', true );` in die `wp-config.php`, nach einem vollständigen Backup.
- **Zweiter Lauf.** Die Option `asu_setup_ran` bleibt dauerhaft stehen und verhindert, dass eine gewachsene Website Monate später versehentlich leergeräumt wird.
- **Theme-Wechsel misslungen.** Dann wird kein einziges Theme gelöscht.

## Installation

Voraussetzungen: WordPress 6.0, PHP 7.4, Hello Elementor und Elementor. Elementor muss vorher aktiv sein, sonst lassen sich die Container nicht einschalten.

1. Repository herunterladen, Ordner nach `/wp-content/plugins/` kopieren.
2. Im Backend unter *Plugins* aktivieren.
3. Das Setup läuft sofort. Danach steht das Plugin wieder auf inaktiv.

## Aufbau

```
auto-setup.php                Plugin-Header, Autoloader, Start
includes/
  class-asu-autoloader.php    lädt die Klassen bei Bedarf
  class-asu-plugin.php        der Ablauf: was passiert wann
  class-asu-result.php        Protokoll des Laufs
  class-asu-cleanup.php       löscht Inhalte, Themes, Plugins
  class-asu-site-setup.php    Startseite, Permalinks, Sichtbarkeit
  class-asu-elementor.php     alles Elementor-Wissen an einer Stelle
```

`ASU_Plugin` ist der Einstieg zum Lesen, dort steht der komplette Ablauf auf einer Seite. Das Plugin hängt an genau zwei Stellen in WordPress: `register_activation_hook` und `admin_init`.

## Tests

```
php tests/run.php
```

Kein Composer, kein PHPUnit. Der Teil von WordPress, den das Plugin anfasst, ist in `tests/bootstrap.php` nachgebaut. Geprüft wird vor allem, was beim Löschen schiefgehen kann: welche Themes geschützt bleiben, was bei einem `WP_Error` passiert, und dass bei Multisite oder einem zweiten Lauf nichts angefasst wird.

Details zum Aufbau stehen in `DEVELOPMENT.md`.

## Lizenz

GPL-2.0-or-later, siehe `LICENSE`.
