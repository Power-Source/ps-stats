---
layout: psource-theme
title: "PS Stats"
---

<h2 align="center" style="color:#38c2bb;">📚 PS Stats</h2>

<div class="menu">
  <a href="https://github.com/cp-psource/ps-stats/discussions" style="color:#38c2bb;">💬 Forum</a>
  <a href="https://github.com/cp-psource/ps-stats/releases" style="color:#38c2bb;">📝 Download</a>
</div>

Besucherstatistik für WordPress mit Fokus auf Datenschutz, Transparenz und Übersichtlichkeit. Perfekt als Widget in Deinem WordPress Dashboard.


## Beschreibung ##
PS Stats bietet einen einfachen und kompakten Zugriff auf die Anzahl der Seitenaufrufe. Es ist datenschutzfreundlich, da es weder Cookies noch eine dritte Partei verwendet.

Auf ein interaktives Diagramm folgen Listen mit den häufigsten Verweisquellen und Zielseiten. Der Zeitraum der Statistik und die Länge der Listen können direkt im Dashboard-Widget eingestellt werden.

###Datenschutz ###
Im direkten Vergleich zu Statistikdiensten wie *Google Analytics*, *WordPress.com Stats* und *Matomo (Piwik)* verarbeitet und speichert *PS Stats* keine personenbezogenen Daten wie z.B. IP-Adressen - *PS Stats* zählt Seitenaufrufe, nicht Besucher.

Absolute Datenschutzkonformität gepaart mit transparenten Verfahren: Eine lokal in WordPress angelegte Datenbanktabelle besteht aus nur vier Feldern (ID, Datum, Quelle, Ziel) und kann vom Administrator jederzeit eingesehen, bereinigt und gelöscht werden.

Durch diesen Tracking-Ansatz ist PS Stats 100% konform mit GDPR und dient als leichtgewichtige Alternative zu anderen Tracking-Diensten.

### Anzeige des Widgets ###
Die Konfiguration des Plugins kann direkt im *PS Stats* Widget auf dem Dashboard über den Link *Konfigurieren* geändert werden.

Die Anzahl der im *PS Stats* Widget angezeigten Links kann ebenso eingestellt werden wie die Option, nur Aufrufe von heute zu zählen. Ältere Einträge werden bei der Änderung dieser Einstellung natürlich nicht gelöscht.

Die Statistiken für das Dashboard-Widget werden für vier Minuten zwischengespeichert.

### Zeitraum der Datenspeicherung ###
*PS Stats* speichert die Daten nur für einen begrenzten Zeitraum (Standard: zwei Wochen), längere Intervalle können als Option im Widget ausgewählt werden. Daten, die älter sind als der gewählte Zeitraum, werden durch einen täglichen Cron-Job gelöscht.

Da alle statistischen Werte in der lokalen WordPress-Datenbank gesammelt und verwaltet werden, ist mit einer Erhöhung des Datenbankvolumens zu rechnen (insbesondere wenn Sie den Zeitraum der Datenspeicherung erhöhen).

### JavaScript-Tracking für Caching-Kompatibilität ###
Für die Kompatibilität mit Caching-Plugins wie [Cachify](http://cachify.de) bietet *PSStats* ein optional zuschaltbares Tracking via JavaScript. Diese Funktion ermöglicht eine zuverlässige Zählung der gecachten Blogseiten.

Damit dies korrekt funktioniert, muss das aktive Theme `wp_footer()` aufrufen, typischerweise in einer Datei namens `footer.php`.

### Tracking für Spam-Referrer überspringen ###
Die Kommentar-Blacklist kann aktiviert werden, um das Tracking für Aufrufe zu überspringen, deren Referrer-URL in der Kommentar-Blacklist aufgeführt ist, d. h. die als Spam angesehen werden.

### Unterstützung ###
Wenn Du Probleme hast oder glaubst, einen Fehler gefunden zu haben (z.B. ein unerwartetes Verhalten), lass uns Bitte eine [Fehlermeldung] (https://github.com/cp-psource/ps-stats/issues) zukommen.