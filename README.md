# Auto Cleanup WP

Ein WordPress-Plugin, das eine frische Installation in einem Rutsch einrichtet: Standard-Inhalte raus, Startseite rein, Elementor vorbereitet. Danach schaltet es sich selbst ab.

Gedacht für den immer gleichen Handgriff am Anfang eines Projekts. Statt zwanzig Klicks durch das WordPress-Backend einmal aktivieren und weiterarbeiten.

> ## Achtung, das Plugin löscht endgültig
>
> Beim Aktivieren werden **alle Beiträge und Seiten** gelöscht, dazu **alle Themes außer Hello Elementor** sowie die Plugins **Hello Dolly und Akismet**. Nichts davon landet im Papierkorb, es ist sofort weg.
>
> Nur auf einer frischen Installation oder nach einem vollständigen Backup einsetzen. Nie auf einer Live-Website.

## Was das Plugin macht

Beim Aktivieren, in dieser Reihenfolge:

1. **Löscht alle Beiträge und Seiten**, auch Entwürfe und den Papierkorb. Damit sind der Standard-Beitrag "Hallo Welt" und die Beispiel-Seite weg.
2. **Legt eine leere Seite "Startseite" an** und stellt WordPress so ein, dass diese Seite die Startseite ist. Die Seite bekommt das Elementor-Template "Full Width".
3. **Setzt die Permalinks auf `/%postname%/`**, also saubere URLs ohne Datum und Zahlen.
4. **Setzt das Häkchen "Suchmaschinen davon abhalten, diese Website zu indexieren".** Sinnvoll, solange die Seite im Bau ist. Vor dem Livegang unbedingt wieder entfernen.
5. **Löscht alle Themes außer Hello Elementor.** Ist gerade ein anderes Theme aktiv, wird vorher auf Hello Elementor umgeschaltet, damit das aktive Theme nicht gelöscht wird.
6. **Löscht Hello Dolly und Akismet.**

7. **Aktiviert die Elementor Flexbox Container.**

Beim nächsten Aufruf einer Backend-Seite:

8. **Zeigt eine grüne Erfolgsmeldung und deaktiviert sich selbst.** Das passiert erst danach, weil ein Plugin sich nicht abschalten kann, während WordPress es gerade einschaltet.

Header und Footer legt das Plugin **nicht** an. Das macht man von Hand im Elementor Theme Builder.

## Voraussetzungen

- WordPress 6.x oder neuer
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
4. Das Setup läuft sofort los. Nach kurzer Zeit erscheint die Erfolgsmeldung und das Plugin steht wieder auf inaktiv.

## Aufbau des Codes

Eine Klasse pro Datei, jede Klasse hat genau eine Aufgabe:

```
auto-setup.php                Startdatei: Plugin-Header, lädt die Klassen, startet das Plugin
includes/
  class-asu-plugin.php        der Ablauf: was passiert wann
  class-asu-cleanup.php       löscht Inhalte, Themes und Plugins
  class-asu-site-setup.php    Startseite, Permalinks, Sichtbarkeit
  class-asu-elementor.php     schaltet die Elementor Flexbox Container ein
```

`class-asu-plugin.php` ist der Einstiegspunkt zum Lesen. Dort steht der komplette Ablauf auf einer Seite, die eigentliche Arbeit steckt in den drei anderen Klassen.

Das Plugin hängt an genau zwei Stellen in WordPress: `register_activation_hook` für das Setup und `admin_init` für die Erfolgsmeldung und die Selbstabschaltung.

## Wofür es nicht gedacht ist

- Bestehende Websites
- Produktivsysteme
- Alles, wo Inhalte drin sind, die noch gebraucht werden

## Lizenz

MIT
