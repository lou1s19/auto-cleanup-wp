# Auto Cleanup WP

Räumt eine frische WordPress-Installation auf und richtet sie für Elementor ein. Einmal aktivieren statt zwanzig Klicks durchs Backend. Danach schaltet sich das Plugin selbst ab.

> ## Achtung, das Plugin löscht endgültig
>
> Beim Aktivieren werden **alle Beiträge und Seiten** gelöscht, dazu **alle Themes außer Hello Elementor** sowie die Plugins **Hello Dolly und Akismet**. Nichts davon landet im Papierkorb, es ist sofort weg.
>
> Nur auf einer frischen Installation oder nach einem vollständigen Backup einsetzen. Nie auf einer Live-Website.

## Was das Plugin macht

Beim Aktivieren, in dieser Reihenfolge:

1. **Löscht alle Beiträge und Seiten**, auch Entwürfe, Auto-Entwürfe und den Papierkorb. Damit sind der Standard-Beitrag "Hallo Welt" und die Beispiel-Seite weg.
2. **Legt eine leere Seite "Startseite" an** und stellt WordPress so ein, dass diese Seite die Startseite ist. Ist Elementor aktiv, bekommt sie das Template "Elementor Full Width". Ohne Elementor bleibt das Standard-Template stehen.
3. **Setzt die Permalinks auf `/%postname%/`**, also saubere URLs ohne Datum und Zahlen.
4. **Setzt das Häkchen "Suchmaschinen davon abhalten, diese Website zu indexieren".** Sinnvoll, solange die Seite im Bau ist. Vor dem Livegang unbedingt wieder entfernen.
5. **Löscht alle Themes außer Hello Elementor.** Ist gerade ein anderes Theme aktiv, wird vorher auf Hello Elementor umgeschaltet. Fehlt Hello Elementor, bleiben das aktive Theme und sein Parent-Theme stehen, damit die Website nicht weiß wird.
6. **Löscht Hello Dolly und Akismet.**
7. **Aktiviert die Elementor Flexbox Container.**

Beim nächsten Aufruf einer Backend-Seite:

8. **Zeigt eine Meldung und deaktiviert sich selbst.** Das passiert erst danach, weil ein Plugin sich nicht abschalten kann, während WordPress es gerade einschaltet. Die Meldung ist grün, wenn alles geklappt hat, und gelb mit einer Liste der Probleme, wenn ein Schritt fehlgeschlagen ist.

Header und Footer legt das Plugin **nicht** an. Das macht man von Hand im Elementor Theme Builder.

## Wo das Plugin abbricht

- **Multisite.** In einem Netzwerk teilen sich alle Sites dieselben Theme- und Plugin-Dateien. Ein Setup für eine einzelne Site darf sie nicht löschen. Das Plugin bricht ab, verändert nichts und sagt im Backend, warum.

## Voraussetzungen

- WordPress 6.0 oder neuer
- PHP 7.4 oder neuer
- Theme **Hello Elementor**
- **Elementor**

## Empfohlene Reihenfolge

Elementor sollte vor diesem Plugin installiert sein, sonst kann der Container nicht aktiviert werden.

1. WordPress installieren
2. Theme **Hello Elementor** installieren und aktivieren
3. **Elementor** installieren und aktivieren
4. **Auto Cleanup WP** aktivieren

## Installation

1. Repository herunterladen oder klonen.
2. Ordner nach `/wp-content/plugins/` kopieren.
3. Im Backend unter *Plugins* aktivieren.
4. Das Setup läuft sofort los. Nach kurzer Zeit erscheint die Meldung und das Plugin steht wieder auf inaktiv.

## Aufbau des Codes

Eine Klasse pro Datei, jede Klasse hat genau eine Aufgabe:

```
auto-setup.php                Plugin-Header, Autoloader anmelden, Plugin starten
includes/
  class-asu-autoloader.php    lädt die Klassen bei Bedarf nach
  class-asu-plugin.php        der Ablauf: was passiert wann
  class-asu-result.php        Protokoll des Laufs, Grundlage der Meldung
  class-asu-cleanup.php       löscht Inhalte, Themes und Plugins
  class-asu-site-setup.php    Startseite, Permalinks, Sichtbarkeit
  class-asu-elementor.php     alles, was das Plugin über Elementor weiß
tests/                        Tests in reinem PHP, ohne Composer
```

`class-asu-plugin.php` ist der Einstiegspunkt zum Lesen. Dort steht der komplette Ablauf auf einer Seite, die eigentliche Arbeit steckt in den anderen Klassen. Sie kennen einander nicht, `ASU_Plugin` reicht durch, was gebraucht wird.

Das Plugin hängt an genau zwei Stellen in WordPress: `register_activation_hook` für das Setup und `admin_init` für die Meldung und die Selbstabschaltung.

## Tests

```
php tests/run.php
```

Kein Composer, kein PHPUnit. Der Teil von WordPress, den das Plugin anfasst, ist in `tests/bootstrap.php` als Attrappe nachgebaut. Getestet wird vor allem das, was beim Löschen schiefgehen kann: welche Themes geschützt sind, was passiert, wenn WordPress ein `WP_Error` liefert, und dass in einem Multisite-Netzwerk nichts angefasst wird.

Nur Syntax prüfen:

```
find . -name '*.php' | xargs -n1 php -l
```

## Wofür es nicht gedacht ist

- Bestehende Websites
- Produktivsysteme
- Multisite-Netzwerke
- Alles, wo Inhalte drin sind, die noch gebraucht werden

## Lizenz

MIT
