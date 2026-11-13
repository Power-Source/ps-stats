=== PS Stats ===
* Contributors: DerN3rd
* Tags: analytics, dashboard, pageviews, statistics, stats, widget
* Requires CP: 2.7
* Requires PHP: 8.0
* Tested up to: 7.1.4
* ClassicPress: 2.7.4
* Stable tag: 1.0.2
* License: GPLv3 or later
* License URI: https://www.gnu.org/licenses/gpl-3.0.html

Lokale Seitenaufruf-Statistik mit Dashboard, erweiterter Auswertung und Widgets.

== Beschreibung ==

Mit PS Stats siehst Du, wie häufig Deine Seiten aufgerufen werden, welche Inhalte beliebt sind und von welchen Websites Aufrufe kommen. Die Statistiken werden lokal auf Deiner Website gespeichert.

Das Dashboard-Widget zeigt Dir die aktuellen Aufrufe auf einen Blick. Unter „PS-Stats“ findest Du Monats- und Jahresdiagramme sowie Auswertungen zu Inhalten und Herkunftsseiten mit Zeitraumwahl.

Mit dem Frontend-Widget „PS-Stats Widget“ kannst Du beliebte Inhalte auf Deiner Website anzeigen. Die Shortcodes [psstats-count] und [psstats-count-sum] zeigen Aufrufzahlen in Beiträgen und Seiten an.

== Einstellungen und Aufbewahrung ==

Unter „Einstellungen → PS-Stats“ legst Du fest, wie lange Aufrufe gespeichert werden, was das Dashboard anzeigt und wie Aufrufe gezählt werden. Angemeldete Nutzer kannst Du von der Zählung ausschließen. Eine Referrer-Blacklist hilft Dir, unerwünschte Herkunftsseiten auszuschließen.

Standardmäßig werden Aufrufe 14 Tage aufbewahrt und angemeldete Nutzer nicht gezählt. Ältere Aufrufe werden täglich automatisch gelöscht. Die geplanten Aufgaben Deiner Website müssen dafür zuverlässig ausgeführt werden.

Wenn Du einen Seitencache verwendest, kannst Du in den Einstellungen auf JavaScript-Tracking umstellen. Die Bot-Erkennung ist nicht lückenlos. Die Statistik zeigt Seitenaufrufe, nicht die Anzahl einzelner Besucher.

== Datenschutz ==

Zu jedem Aufruf werden das Datum, die aufgerufene Adresse und gegebenenfalls die vollständige externe Herkunftsadresse gespeichert. Bei einfachen Permalinks können aufgerufene Adressen auch Abfrageparameter enthalten. Für die Zählung werden keine Besucher-Cookies gesetzt. IP-Adressen und Browserkennungen werden nicht in der Statistik gespeichert; die Browserkennung wird zur Bot-Erkennung verarbeitet.

Aufgerufene Adressen und Herkunftsadressen können personenbezogene Daten enthalten. Lokale Speicherung allein garantiert keine DSGVO-Konformität oder Einwilligungsfreiheit. Prüfe die Rechtsgrundlage für Deine Website, passe Deine Datenschutzhinweise an und wähle eine passende Aufbewahrungsdauer. Beachte dabei auch Deine Sicherungskopien.

== Installation ==

1. Das Plugin nach wp-content/plugins/ps-stats kopieren.
2. PS Stats unter „Plugins“ aktivieren.
3. Einstellungen und Trackingmethode prüfen.

== PSOURCE Manager ==

Mit dem [PSOURCE Manager](https://psource.eimen.net/psource-manager/) kannst Du Plugins und Themes installieren, aktualisieren und auf Hilferessourcen zugreifen.

== Unterstützung ==

Fehler bitte unter https://github.com/Power-Source/ps-stats/issues melden. Keine Zugangsdaten oder personenbezogenen Statistikdaten veröffentlichen.

== PSOURCE unterstützen ==

Du möchtest PSOURCE unterstützen? Mit einer [Spende](https://psource.eimen.net/spendenaktionen/psource-unterstuetzen/) hilfst Du, die Weiterentwicklung und Pflege unserer Projekte zu ermöglichen. Vielen Dank!

== Changelog ==

= 1.0.2 =
* Fehlende Statistiktabelle wird automatisch angelegt, ohne das Plugin erneut aktivieren zu müssen. Bestehende Aufrufe und Einstellungen bleiben erhalten.
* Tabellenanlage für Datenbanken mit strenger Datumsprüfung korrigiert.
* Bei fehlgeschlagener Einrichtung erscheint ein verständlicher Fehlerhinweis; beim nächsten Laden wird die Einrichtung erneut versucht.
* Fehlende tägliche Datenbereinigung wird automatisch wieder eingerichtet, ohne doppelte Aufgaben anzulegen.
* Veraltete zwischengespeicherte Statistiken werden nach dem Wiederanlegen der Tabelle verworfen.
* Nicht mehr verwendete Bestandteile entfernt; die bisherige Speicherung und Auswertung bleiben erhalten.
* Anleitungen bereinigt und Funktionen, Einrichtung sowie Datenschutzhinweise verständlicher beschrieben.
* Hinweise zum PSOURCE Manager für Installation, Updates und Hilferessourcen sowie zur Unterstützung von PSOURCE ergänzt.

= 1.0.1 =
* Dashboard, erweiterte Auswertung und Widgets überarbeitet.
* Update-Manager-Hinweis und Widget-Ausgaben gegen unsichere HTML-Ausgabe abgesichert.

= 1.0.0 =
* Erstveröffentlichung.
