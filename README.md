# PS Stats

**Deutsch** | [English](README.en.md)

[![Version](https://img.shields.io/badge/Version-1.0.1-2271b1?style=flat-square)](readme.txt)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb4?style=flat-square&logo=php&logoColor=white)
![WordPress](https://img.shields.io/badge/WordPress-bis%207.1.4-21759b?style=flat-square&logo=wordpress&logoColor=white)
![ClassicPress](https://img.shields.io/badge/ClassicPress-2.7.4-03768e?style=flat-square)
[![Lizenz](https://img.shields.io/badge/Lizenz-GPL--2.0--or--later-2ea44f?style=flat-square)](https://www.gnu.org/licenses/gpl-2.0.html)

PS Stats ist ein Analyse-Plugin für ClassicPress, das Seitenaufrufe lokal und als Tagesaggregate erfasst. Es benötigt weder Cookies noch externe Statistikdienste.

## Funktionen

- Monats- und Jahresdiagramme mit Tagesdetails und Kennzahlen
- Auswertungen beliebter Inhalte und Referrer-Domains
- Filter nach Zeitraum und Zielpfad sowie CSV-Export für Inhalts- und Referrer-Tabellen
- Konfigurierbare Aufbewahrung, Ausschluss angemeldeter Personen und Ausschluss bestimmter Pfade
- Dashboard-Widget, Frontend-Widget „PS-Stats Widget“ und die Shortcodes `[psstats-count]` und `[psstats-count-sum]`
- Optionale, nur lesende Monatsauswertung vorhandener MarketPress-Statistiken
- Lokale Datenhaltung ohne Übertragung der PS-Stats-Statistik an externe Dienste

## Voraussetzungen

- ClassicPress 2.7 oder neuer
- PHP 8.0 oder neuer
- Datenbanktabellen mit InnoDB für sichere Migration und Datenpflege

## Installation

1. Das Plugin in das Verzeichnis `wp-content/plugins/ps-stats` installieren oder über die Plugin-Verwaltung hochladen.
2. PS Stats unter **Plugins** aktivieren. Bei der Aktivierung werden die Statistik-Tabellen angelegt und gegebenenfalls vorhandene ältere PS-Stats-Daten zur Migration vorbereitet.
3. Die Auswertungen über den Menüpunkt **PS Stats** öffnen.
4. Aufbewahrung, Pfadausschlüsse und optionale Referrer-Erfassung unter **Einstellungen → PS Stats** konfigurieren.

Migrationen älterer Daten laufen in Stapeln. Die alte Quelltabelle wird nach erfolgreicher Migration nicht automatisch gelöscht. Prüfe sie auf mögliche personenbezogene Altbestände, bevor Du sie gegebenenfalls separat entfernst.

## Verwendung und Einstellungen

Die **Übersicht** bietet Monats- und Jahresansichten. Unter **Inhalte** und **Referrer** lassen sich Zeiträume und Zielpfade auswählen; die Tabellen können als CSV exportiert werden. Die Auswertungen berücksichtigen alle noch gespeicherten Aggregate.

Unter **Einstellungen → PS Stats** kannst Du unter anderem:

- eine Aufbewahrung von 7, 14, 30, 90, 180 oder 365 Tagen einstellen oder die automatische Löschfrist deaktivieren;
- angemeldete Personen von der Zählung ausschließen (standardmäßig aktiviert);
- lokale Pfade von der Erfassung ausschließen;
- die Referrer-Erfassung aktivieren und dafür externe Domains ausdrücklich freigeben;
- die vorhandene MarketPress-Statistik optional als nur lesende Quelle anzeigen.

Bei einer Verkürzung der Aufbewahrung ist eine ausdrückliche Bestätigung erforderlich. Die automatische Bereinigung läuft geplant; für zuverlässige Ausführung empfiehlt sich ein korrekt eingerichteter Server-Cron. Der Systemstatus zeigt den letzten erfolgreichen Lauf und erkannte Probleme.

### MarketPress

Die optionale MarketPress-Auswertung zeigt Administratoren Monatsaggregate der vorhandenen Bestellwerte und Gratis-Download-Ereignisse. PS Stats erfasst dafür keine zusätzlichen Shop-Ereignisse und speichert keine Kopien der MarketPress-Quelldaten. Das MarketPress-Statistikmodul und seine Downloadtabelle müssen bereits aktiviert beziehungsweise initialisiert sein.

Bestellwertsummen entsprechen den vorhandenen Quelldaten und sind nicht notwendigerweise bestätigter Zahlungsumsatz; Erstattungen werden nicht gesondert korrigiert. Downloads sind Ereignisse, keine eindeutigen Nutzer oder bestätigten Dateitransfers. MarketPress-Daten und deren Aufbewahrung müssen unabhängig geprüft werden.

## Was gezählt wird – und was nicht

PS Stats zählt geeignete **GET-Anfragen**, die ClassicPress und PHP erreichen, nicht eindeutige Besucher. Erfasst werden die Startseite und kanonische Pfade veröffentlichter, öffentlicher Inhalte ohne Passwort. Dynamische oder private Bereiche wie Konten, Bestellungen, Checkout, Mitgliederbereiche und Downloads werden ausgeschlossen. Eigene Pfade kannst Du zusätzlich ausschließen.

Einfache Permalinks wie `?p=` oder `?page_id=` werden nicht als Aufruf der Startseite gezählt. Für inhaltsbezogene Pfadstatistiken sind sprechende Permalinks erforderlich.

Es werden keine Cookies gesetzt oder Besucher- beziehungsweise Sitzungskennungen erstellt. Die PS-Stats-Statistiktabellen speichern keine IP-Adressen, User-Agents oder vollständigen Referrer-URLs. HTTP-Header können während einer Anfrage zur Bot-Erkennung und – wenn aktiviert – zur Referrer-Zuordnung verarbeitet werden. Referrer sind standardmäßig deaktiviert; bei Aktivierung werden nur ausdrücklich freigegebene externe Domains gespeichert.

Vollseiten-Caches, Reverse-Proxies und CDNs können Anfragen an PHP vorbeileiten. Solche Aufrufe fehlen dann in der Statistik; PS Stats installiert keinen Cache-Bypass, Tracking-Pixel oder Browser-Beacon. Bot-Filter sind heuristisch, daher sind die Werte weder Besucherzahlen noch garantiert vollständig.

## Datenschutz

Lokale Speicherung und der Verzicht auf Cookies sind keine Garantie für DSGVO-Konformität oder Einwilligungsfreiheit. Öffentliche URL-Pfade können Namen oder andere sensible Inhalte enthalten. Prüfe Deine Website und konfiguriere passende Pfadausschlüsse. Deaktivierte Erfassungsoptionen löschen keine bereits gespeicherten Daten; für Referrer und Statistikdaten gibt es gesonderte Löschfunktionen.

Unter **Einstellungen → PS Stats** wird ein konfigurationsabhängiger Datenschutztext-Vorschlag bereitgestellt. Er ist keine Rechtsberatung und muss an den konkreten Betrieb angepasst werden. Prüfe insbesondere Zweck, Rechtsgrundlage, mögliche Einwilligungspflichten, Hosting, Serverprotokolle, Altbestände, Betroffenenrechte und Sicherungskopien. Sicherungskopien und optionale MarketPress-Quelldaten unterliegen eigenen Löschregeln.

## Entwickler

Andere vertrauenswürdige Plugins können die PHP-Funktion `ps_stats_provider()` verwenden, um aggregierte Aufrufdaten abzufragen. Sie stellt keinen öffentlichen REST-Endpunkt dar. Aufrufender Code ist selbst für Berechtigungen und den Datenschutz bei der Weiterverarbeitung verantwortlich. Ungültige Zeiträume und Datenbankfehler werden als Exceptions gemeldet.

Die vorhandenen Testskripte lassen sich in einer ClassicPress-Installation mit WP-CLI ausführen:

```sh
wp --skip-plugins --skip-themes eval-file tests/evaluation.php
wp --skip-plugins --skip-themes eval-file tests/storage-privacy.php
wp --skip-plugins --skip-themes eval-file tests/marketpress-source.php
```

## Fehler melden

Fehler und Vorschläge bitte im [GitHub-Issue-Tracker](https://github.com/Power-Source/ps-stats/issues) melden. Bitte keine personenbezogenen Daten, Zugangsdaten oder vertraulichen Statistikexporte in Issues veröffentlichen.

## Lizenz

PS Stats ist unter **GPLv3 oder später** lizenziert. Siehe [LICENSE](./LICENSE).
