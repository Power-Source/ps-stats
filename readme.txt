=== PS Stats ===
* Contributors:      DerN3rd
* Tags:              analytics, dashboard, pageviews, privacy, statistics, stats, visits, web stats, widget
* Requires CP:       2.7
* Requires PHP:      8.0
* Stable tag:        2.0.0
* License:           GPLv3 or later
* License URI:       https://www.gnu.org/licenses/gpl-3.0.html

Datenschutzfreundliche, vollständig lokale Seitenaufruf-Statistik für ClassicPress.


## Beschreibung ##
PS Stats bietet eine einfache, vollständig lokale Übersicht über Seitenaufrufe. Es ist datenschutzfreundlich, da es weder Cookies noch eine dritte Partei verwendet.

Die Übersicht zeigt tägliche Aufrufe, häufige Zielpfade und externe Referrer-Domains. Aufbewahrungsdauer, ausgeschlossene lokale Pfade und das Ausschließen angemeldeter Personen können in den Einstellungen konfiguriert werden.

###Datenschutz ###
PS Stats zählt Seitenaufrufe, nicht Besucher. Das Plugin setzt für die Zählung keine Cookies, erstellt keine Besucherkennungen und speichert in seinen Statistiktabellen keine IP-Adressen, User-Agents oder vollständigen Referrer-URLs. Vorhandene HTTP-Header werden während einer Anfrage zur Bot-Erkennung und optionalen Referrer-Zuordnung verarbeitet; das ist von der Speicherung zu unterscheiden.

Neue Aufrufe werden nur für die Startseite und kanonische, veröffentlichte öffentliche Einzelinhalte ohne Passwort gezählt. Konto-, Bestell-, Checkout-, Mitglieder-, Autoren-, Nachrichten- und Download-Pfade sowie unbekannte dynamische Routen werden ausgeschlossen. Öffentliche Pfade können dennoch Namen oder andere sensible Inhalte enthalten: Prüfe Deine Website und ergänze die Ausschlussliste. Die Regeln gelten auch bei der Migration, nicht rückwirkend für bereits gespeicherte Aggregate. Bestehende historische Daten und deren Exporte müssen gesondert geprüft werden.

Inhaltsaufrufe über einfache Permalinks wie `?p=` oder `?page_id=` werden ausgeschlossen, nicht fälschlich der Startseite zugeordnet. Für eine inhaltsbezogene Pfadauswertung sind sprechende Permalinks erforderlich.

Referrer-Erfassung ist standardmäßig aus. Wenn Du sie aktivierst, werden ausschließlich exakt freigegebene externe Domains gespeichert; keine personenbezogenen oder kundenspezifischen Subdomains freigeben. Andere Herkünfte werden ohne Domain gezählt. Deaktivieren löscht keine vorhandenen Referrer; dafür gibt es eine gesonderte Löschfunktion.

Eine pauschale DSGVO-Garantie oder Einwilligungsfreiheit wird nicht zugesagt. Aggregation und Cookie-Verzicht allein beweisen keine Anonymität. Entscheidend sind der konkrete Datenumfang, mögliche Verknüpfungen und der Betrieb. Unter „Einstellungen → PS Stats“ und in den Datenschutztext-Vorschlägen von ClassicPress findest Du einen konfigurationsabhängigen Textentwurf. Ergänze und prüfe Zweck, Rechtsgrundlage, gegebenenfalls Einwilligung, Empfänger, Betroffenenrechte, Hosting, Serverprotokolle, Altbestände und Sicherungskopien mit Deiner Datenschutzberatung. Eine juristische Prüfung ist nicht Bestandteil des Plugins.

### Übersicht ###
Die Statistikübersicht ist über den eigenen Hauptmenüpunkt „PS Stats“ erreichbar. Konfiguration, Migration und Datenpflege befinden sich getrennt davon unter „Einstellungen → PS Stats“. Unter „Dashboard“ wird kein zusätzlicher PS-Stats-Menüeintrag angelegt.

Unter „PS Stats → Übersicht“ stehen Monats- und Jahresdiagramme sowie Jahresregister mit Tagesdetails, Summen, Durchschnitt, Minimum und Maximum zur Verfügung. Die Auswertung kann auf einen einzelnen Zielpfad eingeschränkt werden.

„PS Stats → Inhalte“ zeigt die beliebtesten Inhalte und Auswertungen für Beiträge, Seiten und öffentliche eigene Inhaltstypen. „PS Stats → Referrer“ bietet externe Referrer-Domains, auf Wunsch für einen einzelnen Zielpfad. Beide Ansichten unterstützen frei wählbare Zeiträume und Zeitraum-Vorgaben. Tabellen lassen sich als CSV exportieren. Ausgewertet werden sämtliche noch gespeicherten aggregierten Aufrufe, nicht nur die letzten 30 Tage.

### Zeitraum der Datenspeicherung ###
*PS Stats* speichert Daten standardmäßig 30 Tage. Daten, die älter als der konfigurierte Zeitraum sind, werden durch einen täglichen Cron-Job gelöscht. Die Aufbewahrung kann auch auf unbegrenzt gestellt werden.

Bestehende Aufbewahrungswerte werden beim Upgrade erhalten. Eine Verkürzung verlangt eine ausdrückliche Bestätigung und wirkt beim nächsten Bereinigungslauf auf vorhandene Daten. Der Systemstatus zeigt den letzten erfolgreichen Lauf und Warnungen bei ausgebliebenem Cron. Ein zuverlässiger Server-Cron ist empfehlenswert; das Plugin kann keine pünktliche Löschung garantieren, wenn geplante Aufgaben nicht laufen. Sicherungskopien und MarketPress-Quelldaten haben eigene Löschfristen.

Migrationen laufen stapelweise mit Datenbanksperre und gemeinsamem Commit von Aggregaten und Fortschritt. Ungültige Datumswerte erzeugen einen sichtbaren Fehler statt einer Umdeutung auf heute. Während einer offenen oder fehlgeschlagenen Migration ist die Bereinigung pausiert. Die alte Quelltabelle wird nach erfolgreicher Migration nicht automatisch gelöscht; Du kannst sie nach Prüfung ausdrücklich entfernen. Sie kann personenbezogene Altdaten enthalten. Sichere Migration und Pflege benötigen InnoDB-Tabellen.

### Zählgrenzen und Cache ###
Die Zählung erfasst nur geeignete GET-Anfragen, die ClassicPress/PHP erreichen. Vollseiten-Caches, Reverse-Proxies und CDNs können PHP umgehen; solche Treffer fehlen dann. Der Systemstatus erkennt lokale Cache-Hinweise, kann aber vorgeschaltete Infrastruktur nicht zuverlässig erkennen. Es wird kein Browser-Beacon, Tracking-Pixel oder Cache-Bypass automatisch eingebaut. Bot-Filter sind heuristisch; die Zahlen sind keine eindeutigen Besucherzahlen.

### Integrationen ###
PS Stats funktioniert vollständig eigenständig. Andere vertrauenswürdige Plugins können optional `ps_stats_provider()` für aggregierte Aufrufdaten verwenden. Die API ist kein öffentlicher REST-Endpunkt; der aufrufende Code ist für Zugriffsrechte und Datenschutz bei weiterer Verwendung verantwortlich. Ungültige Zeiträume und Datenbankfehler werden ausdrücklich als Exceptions gemeldet. Historische `product_metrics()` bleiben lesbar, werden aber nicht neu befüllt.

Optional kannst Du die vorhandene MarketPress-Statistik als nur lesende Quelle aktivieren. Unter „PS Stats → MarketPress“ sehen ausschließlich Administratoren Monatsaggregate der Bestellwerte und Gratis-Download-Ereignisse für den gewählten Zeitraum. PS Stats registriert keine zusätzlichen Verkaufs- oder Download-Tracker und übernimmt keine Bestell-/Benutzerkennungen oder Einzelzeitstempel. Die Quelldaten bleiben in MarketPress; dessen Datenschutz- und Löschregeln gelten unabhängig von der PS-Stats-Aufbewahrung.

Die Bestellwert-Summe entspricht der bestehenden Quelle: `mp_order_total` nach Bestelldatum, ohne Zahlungsstatusfilter oder gesonderte Erstattungskorrektur. Sie ist kein bestätigter Zahlungsumsatz; Beträge werden in der Shop-Währung angezeigt. Downloads sind Ereignisse, keine eindeutigen Nutzer oder bestätigten Dateitransfers. Diese Zusammenfassungen dürfen nicht ohne Prüfung als anonym bezeichnet werden. Bei fehlender Quelle erscheint ein Fehler statt erfundener Nullwerte.

Das MarketPress-Statistikmodul muss aktiviert und seine Downloadtabelle vollständig initialisiert sein. PS Stats legt diese Quelltabelle nicht selbst an und repariert sie nicht automatisch. Eine erstmalige Initialisierung in MarketPress erzeugt keine historischen Download-Ereignisse. Die dortige Speicherung von Bestell-/Benutzerkennungen und Downloadzeitpunkten muss unabhängig geprüft werden.

Das Dashboard-Widget, das Frontend-Widget „PS-Stats Widget“, die Shortcodes `[psstats-count]` und `[psstats-count-sum]` sowie die bisherigen Theme-Hilfsfunktionen nutzen die Aggregate. Öffentliche Rankings geben keine privaten oder ausgeschlossenen Inhalte aus.

### Entwicklung ###
Die Auswertung kann mit `wp --skip-plugins --skip-themes eval-file tests/evaluation.php` geprüft werden. Die Tests verwenden eine verbindungslokale temporäre Statistiktabelle; bestehende Statistikdaten bleiben unverändert.

Weitere isolierte Suiten: `wp --skip-plugins --skip-themes eval-file tests/storage-privacy.php` und `wp --skip-plugins --skip-themes eval-file tests/marketpress-source.php`. Sie prüfen unter anderem Routenregeln, Transaktions-Rollback, Migrationswiederholung, Datumsfehler, Sperren, Bereinigung, Widgets/Shortcodes und die nur lesende MarketPress-Quelle. Die CI-Matrix prüft PHP 8.0 bis 8.4 mit ClassicPress 2.7. Eine lokal bestandene Suite ersetzt keine juristische Prüfung.

### Unterstützung ###
Wenn Du Probleme hast oder glaubst, einen Fehler gefunden zu haben (z.B. ein unerwartetes Verhalten), lass uns Bitte eine [Fehlermeldung] (https://github.com/Power-Source/ps-stats/issues) zukommen.
