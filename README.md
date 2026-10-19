# IPS2MessageDisplay

**Nachrichtenanzeige für IP-Symcon – Version 2.x.x (Fork)**

**Version:** 2.x.x · **Autor/Hersteller:** Loerdy (Fork) · **Basis:** Originalprojekt von Joey-1970 (Version 1.0)

IPS2MessageDisplay dient zur Anzeige und Verwaltung von Meldungen im IP-Symcon WebFront. Dieses Repository ist ein Fork des von **[Joey-1970](https://github.com/Joey-1970/IPS2MessageDisplay)** entwickelten Projekts.

- **Originalprojekt:** https://github.com/Joey-1970/IPS2MessageDisplay
- **Dieser Fork:** https://github.com/Loerdy/IPS2MessageDisplay
- **Änderungshistorie:** [CHANGELOG.md](CHANGELOG.md)

## Urheberschaft

Konzept, ursprüngliche Implementierung und Architektur stammen von **Joey-1970**. Vielen Dank für die Entwicklung und Veröffentlichung des Moduls. Der Fork wird von **Loerdy** unabhängig weitergepflegt, insbesondere für aktuelle IP-Symcon- und PHP-Versionen. Die ursprüngliche Urheberschaft bleibt davon unberührt.

**Lizenzhinweis:** Vor einer Weiterverbreitung sind die Lizenz- und Nutzungsbedingungen des Originalprojekts zu prüfen. Ein Fork und die Nennung des Urhebers ersetzen keine erforderliche Lizenz oder Zustimmung.

## Module und Funktionen

### IPS2MessageDisplay

Das Hauptmodul verwaltet und zeigt Nachrichten an:

- Mehrere gleichzeitig aktive Nachrichten im WebFront
- Datums- und Uhrzeitangaben sowie Sortierung
- Entfernen von Nachrichten über WebHook-Aktionen
- Schaltfunktion für entsprechend konfigurierte Nachrichten
- Optionale Ansteuerung einer Boolean-Aktorvariable abhängig vom Nachrichtenbestand
- Optionale zweite String-Variable `TileMessages` für das neue IP-Symcon-Kacheldesign

### IPS2MessageDisplaySupporter

Das Supporter-Modul überwacht konfigurierte Variablen beziehungsweise Instanzen und meldet Zustandsänderungen an das Hauptmodul:

- Ereignisbasierte Erzeugung und Entfernung von Nachrichten
- Verknüpfung mit einer Nachrichtenanzeige
- Konfiguration über die IP-Symcon-Verwaltungskonsole
- Statusverwaltung für aktive, deaktivierte und ungültig konfigurierte Instanzen

## Installation

1. In IP-Symcon die **Modulverwaltung** öffnen.
2. Das GitHub-Repository `https://github.com/Loerdy/IPS2MessageDisplay` hinzufügen.
3. Den gewünschten Zweig auswählen: `master` für den freigegebenen Stand oder `development` für Tests.
4. Eine Instanz von **IPS2MessageDisplay** erstellen und konfigurieren.
5. Bei Bedarf Instanzen von **IPS2MessageDisplaySupporter** erstellen und mit der Nachrichtenanzeige verbinden.

### Optionale Kachelansicht (ab Version 2.0.0)

Die bisherige Variable `Messages` mit dem Profil `~HTMLBox` bleibt für das klassische WebFront erhalten. Zusätzlich lässt sich in der Konfiguration des Hauptmoduls über die Boolean-Option `EnableTileView` (Standard: deaktiviert) die Variable `TileMessages` einschalten.

`TileMessages` nutzt die IP-Symcon-Präsentation **Webinhalt** und zeigt die vorhandenen Nachrichten in einer kacheloptimierten HTML-Tabelle an. Die Tabelle nutzt die Kachelbreite; die Nachrichten stehen oben, sodass darunter Platz für weitere Meldungen bleibt. Zeitstempel und Meldungstext werden hell dargestellt, die Zeilen besitzen eine einheitliche Mindesthöhe. Bei leerem Bestand erscheint „Keine aktiven Meldungen“.

Aktionsschaltflächen erscheinen nur bei Nachrichten mit entsprechender Aktion. Bestätigen beziehungsweise Entfernen über die bestehenden WebHooks wurde im Kacheldesign erfolgreich getestet. Die zweite Ansicht verwendet denselben Nachrichtenbestand; es werden keine zusätzlichen Timer, WebHooks oder Nachrichtenspeicher angelegt.

**Einrichtung:** `EnableTileView` aktivieren, die Instanzkonfiguration übernehmen und die neue Variable `TileMessages` in der Kachelvisualisierung einbinden. Die bestehende Variable `Messages` bleibt unverändert.

### Wechsel vom Originalmodul

Vor einem Wechsel empfiehlt sich eine Sicherung der IP-Symcon-Konfiguration. Bei einem durchgeführten Wechsel vom Originalprojekt auf diesen Fork blieben bestehende Instanzen und Einstellungen erhalten. Das ist keine Garantie für jede Installation; nach dem Wechsel sollten die Nachrichtenanzeige und die Supporter-Instanzen geprüft werden.

## Kompatibilität und Tests

Die Mindestversion ist **IP-Symcon 8.0**, da die optionale Kachelansicht die ab dieser Version verfügbare Variablenpräsentation **Webinhalt** verwendet. Die Änderungen wurden in einer Umgebung mit **IP-Symcon 9.1** und **PHP 8.5.8** erprobt.

Für PHP 8.5 wurden insbesondere UTF-8-, JSON- und `unserialize()`-Verarbeitung, WebHook-Parameter, Float-Zeitstempel sowie Zugriffe auf Variablen und Instanzen abgesichert. Getestet wurden die reguläre Nachrichtenanzeige, das Hinzufügen und Entfernen von Nachrichten, die Sortierung und die Statusanzeige mehrerer Supporter-Instanzen. Dies ist keine vollständige automatisierte Testabdeckung.

## Statuswerte des Supporter-Moduls

| Status | Bedeutung |
| --- | --- |
| `102` | Aktiv und korrekt konfiguriert |
| `104` | Deaktiviert |
| `200` | Konfiguriertes überwachtes Objekt fehlt oder ist ungültig |

Der Fehlerstatus `200` ist implementiert, wurde aber noch nicht in allen relevanten Situationen praktisch getestet.

## Verbesserungen dieses Forks

Die Weiterentwicklung umfasst Stabilität, PHP-Kompatibilität und die zusätzliche Kachelansicht:

- Korrektur einer fehlerhaften UTF-8-Umwandlung
- Absicherung der Semaphore-Verarbeitung
- Validierung eingehender und ausgehender JSON-Daten
- Schutz vor fehlerhaften serialisierten Nachrichtendaten, ohne diese automatisch zu überschreiben
- Validierung von WebHook-Parametern
- Robuste Sortierung bei ungültigen Zeitstempeln
- Beibehaltung des ursprünglichen Timestamps bei wiederholten `Add`-Aufrufen mit unveränderten Nachrichtendaten
- Prüfung der WebFront-Konfiguration
- Existenz- und Typprüfung für Aktorvariablen und überwachte Objekte
- Vermeidung von Sendeversuchen ohne aktive Parent-Verbindung
- Explizite Integer-Konvertierung bei der Datumsformatierung
- Optionale Kachelansicht `TileMessages` mit angepasster Darstellung und vorhandenen Bestätigungsaktionen
- Aktualisierte Bibliotheks- und Modulmetadaten: `Loerdy (Fork)`

Einzelheiten stehen im [Changelog](CHANGELOG.md).

## Bekannte Einschränkungen

- **Instanzsymbole:** Die Statussymbole `Ok`, `Power` und `Alert` werden als `ObjectIcon` gespeichert. In der getesteten IP-Symcon-Oberfläche zeigt der Objektbaum trotzdem weiterhin das Modulsymbol. Ob dies beabsichtigt ist oder ein Fehler von IP-Symcon, ist ungeklärt.
- **Fehlerfälle:** Die Behandlung gezielt beschädigter Daten und bestimmte Abläufe rund um gelöschte Objektreferenzen wurden nicht vollständig praktisch getestet.

## Fehler melden und mitarbeiten

Fehlerberichte und Vorschläge bitte über die [Issues dieses Forks](https://github.com/Loerdy/IPS2MessageDisplay/issues) einreichen. Hilfreich sind IP-Symcon-Version, PHP-Version, betroffene Instanz und relevante Logmeldungen ohne sensible Daten.

## Danksagung

**Originalautor:** [Joey-1970](https://github.com/Joey-1970)

**Weiterentwicklung dieses Forks:** [Loerdy](https://github.com/Loerdy)
