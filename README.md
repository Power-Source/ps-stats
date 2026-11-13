# PS Stats

**Deutsch** | [English](README.en.md)

[![Version](https://img.shields.io/badge/Version-1.0.2-2271b1?style=flat-square)](readme.txt)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb4?style=flat-square&logo=php&logoColor=white)
![WordPress](https://img.shields.io/badge/WordPress-bis%207.1.4-21759b?style=flat-square&logo=wordpress&logoColor=white)
![ClassicPress](https://img.shields.io/badge/ClassicPress-2.7.4-03768e?style=flat-square)
[![Lizenz](https://img.shields.io/badge/Lizenz-GPL--3.0--or--later-2ea44f?style=flat-square)](https://www.gnu.org/licenses/gpl-3.0.html)

PS Stats erfasst Seitenaufrufe lokal und zeigt sie im Dashboard sowie in der erweiterten Auswertung an.

## Funktionen und Einstellungen

- Dashboard-Widget mit Tagesaufrufen, beliebten Zielen und Referrern
- Monats- und Jahresauswertungen unter **PS-Stats**, Inhalte und Referrer mit Zeitraumwahl
- Frontend-Widget „PS-Stats Widget“ sowie `[psstats-count]` und `[psstats-count-sum]`
- Einstellungen für Aufbewahrungsdauer, Dashboard-Anzeige, Trackingmethode und Ausschluss angemeldeter Nutzer
- Integrierte Referrer-Blacklist

Die Einstellungen befinden sich unter **Einstellungen → PS-Stats**. Standardmäßig werden Daten 14 Tage aufbewahrt und angemeldete Nutzer nicht gezählt. Ältere Aufrufe werden täglich automatisch gelöscht. Die geplanten Aufgaben Deiner Website müssen dafür zuverlässig ausgeführt werden.

## Installation

1. Das Plugin nach `wp-content/plugins/ps-stats` kopieren.
2. PS Stats unter **Plugins** aktivieren.
3. Einstellungen, Aufbewahrung und Trackingmethode prüfen.

## PSOURCE Manager

Mit dem [PSOURCE Manager](https://psource.eimen.net/psource-manager/) kannst Du Plugins und Themes installieren, aktualisieren und auf Hilferessourcen zugreifen.

## Tracking und Datenschutz

Standardmäßig zählt PS Stats Aufrufe direkt beim Laden einer Seite. Wenn Du einen Seitencache verwendest, kannst Du in den Einstellungen auf JavaScript-Tracking umstellen. Die Bot-Erkennung ist nicht lückenlos. Die Statistik zeigt Seitenaufrufe, nicht die Anzahl einzelner Besucher.

Zu jedem Aufruf werden das Datum, die aufgerufene Adresse und gegebenenfalls die vollständige externe Herkunftsadresse gespeichert. Bei einfachen Permalinks können aufgerufene Adressen auch Abfrageparameter enthalten. Für die Zählung werden keine Besucher-Cookies gesetzt. IP-Adressen und Browserkennungen werden nicht in der Statistik gespeichert; die Browserkennung wird zur Bot-Erkennung verarbeitet.

Aufgerufene Adressen und Herkunftsadressen können personenbezogene Daten enthalten. Lokale Speicherung allein garantiert keine DSGVO-Konformität oder Einwilligungsfreiheit. Prüfe die Rechtsgrundlage für Deine Website, passe Deine Datenschutzhinweise an und wähle eine passende Aufbewahrungsdauer. Beachte dabei auch Deine Sicherungskopien.

## Änderungen

### 1.0.2

- Fehlende Statistiktabelle wird automatisch angelegt, ohne das Plugin erneut aktivieren zu müssen. Bestehende Aufrufe und Einstellungen bleiben erhalten.
- Tabellenanlage für Datenbanken mit strenger Datumsprüfung korrigiert.
- Bei fehlgeschlagener Einrichtung erscheint ein verständlicher Fehlerhinweis; beim nächsten Laden wird die Einrichtung erneut versucht.
- Fehlende tägliche Datenbereinigung wird automatisch wieder eingerichtet, ohne doppelte Aufgaben anzulegen.
- Veraltete zwischengespeicherte Statistiken werden nach dem Wiederanlegen der Tabelle verworfen.
- Nicht mehr verwendete Bestandteile entfernt; die bisherige Speicherung und Auswertung bleiben erhalten.
- Anleitungen bereinigt und Funktionen, Einrichtung sowie Datenschutzhinweise verständlicher beschrieben.
- Hinweise zum PSOURCE Manager für Installation, Updates und Hilferessourcen sowie zur Unterstützung von PSOURCE ergänzt.

## Unterstützung und Lizenz

Fehler bitte im [GitHub-Issue-Tracker](https://github.com/Power-Source/ps-stats/issues) melden, ohne Zugangsdaten oder personenbezogene Statistikdaten.

Du möchtest PSOURCE unterstützen? Mit einer [Spende](https://psource.eimen.net/spendenaktionen/psource-unterstuetzen/) hilfst Du, die Weiterentwicklung und Pflege unserer Projekte zu ermöglichen. Vielen Dank!

Lizenz: **GPLv3 oder später**, siehe [LICENSE](./LICENSE).
