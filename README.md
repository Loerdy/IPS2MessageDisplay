# IPS2MessageDisplay

**Nachrichtenanzeige für IP-Symcon – weitergepflegter Fork**

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

### Wechsel vom Originalmodul

Vor einem Wechsel empfiehlt sich eine Sicherung der IP-Symcon-Konfiguration. Bei einem durchgeführten Wechsel vom Originalprojekt auf diesen Fork blieben bestehende Instanzen und Einstellungen erhalten. Das ist keine Garantie für jede Installation; nach dem Wechsel sollten die Nachrichtenanzeige und die Supporter-Instanzen geprüft werden.

## Kompatibilität und Tests

Die Änderungen wurden in einer Umgebung mit **IP-Symcon 9.1** und **PHP 8.5.8** erprobt. Getestet wurden insbesondere die reguläre Nachrichtenanzeige, das Hinzufügen und Entfernen von Nachrichten, die Sortierung und die Statusanzeige mehrerer Supporter-Instanzen. Dies ist keine vollständige automatisierte Testabdeckung.

## Statuswerte des Supporter-Moduls

| Status | Bedeutung |
| --- | --- |
| `102` | Aktiv und korrekt konfiguriert |
| `104` | Deaktiviert |
| `200` | Konfiguriertes überwachtes Objekt fehlt oder ist ungültig |

Der Fehlerstatus `200` ist implementiert, wurde aber noch nicht in allen relevanten Situationen praktisch getestet.

## Verbesserungen dieses Forks

Die bisherige Weiterentwicklung konzentriert sich auf Stabilität und PHP-Kompatibilität:

- Korrektur einer fehlerhaften UTF-8-Umwandlung
- Absicherung der Semaphore-Verarbeitung
- Validierung eingehender und ausgehender JSON-Daten
- Schutz vor fehlerhaften serialisierten Nachrichtendaten, ohne diese automatisch zu überschreiben
- Validierung von WebHook-Parametern
- Robuste Sortierung bei ungültigen Zeitstempeln
- Prüfung der WebFront-Konfiguration
- Existenz- und Typprüfung für Aktorvariablen und überwachte Objekte
- Vermeidung von Sendeversuchen ohne aktive Parent-Verbindung
- Explizite Integer-Konvertierung bei der Datumsformatierung

Einzelheiten stehen im [Changelog](CHANGELOG.md).

## Bekannte Einschränkungen

- **Instanzsymbole:** Die Statussymbole `Ok`, `Power` und `Alert` werden als `ObjectIcon` gespeichert. In der getesteten IP-Symcon-Oberfläche zeigt der Objektbaum trotzdem weiterhin das Modulsymbol. Ob dies beabsichtigt ist oder ein Fehler von IP-Symcon, ist ungeklärt.
- **Fehlerfälle:** Die Behandlung gezielt beschädigter Daten und bestimmte Abläufe rund um gelöschte Objektreferenzen wurden nicht vollständig praktisch getestet.

## Fehler melden und mitarbeiten

Fehlerberichte und Vorschläge bitte über die [Issues dieses Forks](https://github.com/Loerdy/IPS2MessageDisplay/issues) einreichen. Hilfreich sind IP-Symcon-Version, PHP-Version, betroffene Instanz und relevante Logmeldungen ohne sensible Daten.

## Danksagung

**Originalautor:** [Joey-1970](https://github.com/Joey-1970)  
**Weiterentwicklung dieses Forks:** [Loerdy](https://github.com/Loerdy)
