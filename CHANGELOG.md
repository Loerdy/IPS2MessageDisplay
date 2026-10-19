# Changelog

Dieses Dokument beschreibt die im Fork [Loerdy/IPS2MessageDisplay](https://github.com/Loerdy/IPS2MessageDisplay) vorgenommenen Änderungen gegenüber dem [Originalprojekt von Joey-1970](https://github.com/Joey-1970/IPS2MessageDisplay).

Die Änderungen sind thematisch zusammengefasst. Version **2.0.0** bezeichnet die neue Hauptversion des Forks gegenüber dem Originalstand 1.0.

## [Unreleased]

### Geändert

- **Debug-Ausgaben:** `ForwardData()` protokolliert verworfene Nachrichten mit Aktion, betroffenem Feld und Fehlergrund, ohne vollständige Nachrichtendaten auszugeben.
- **Semaphore:** Der Schutz von `WorkProcess()` verwendet einen instanzbezogenen Namen, sodass unabhängige Modulinstanzen einander nicht blockieren.
- **Instanzicons:** Das Supporter-Modul setzt keine statusabhängigen Instanzicons mehr automatisch; die Statuswerte `102`, `104` und `200` bleiben unverändert.

## [2.0.0] – Weiterentwicklung des Forks

### Geändert

- **Mindestversion:** Die erforderliche IP-Symcon-Version wurde wegen der ab Version 8.0 verfügbaren Variablenpräsentation „Webinhalt“ auf 8.0 angehoben.
- **PHP-Kompatibilität:** Verarbeitung und Typprüfungen wurden für den Betrieb mit PHP 8.5 aktualisiert und unter PHP 8.5.8 getestet.
- **Autorenkennung:** Bibliothek und Module weisen den Fork als `Loerdy (Fork)` aus; Joey-1970 bleibt als ursprünglicher Entwickler genannt.

### Behoben

- **Zeichenkodierung:** Fehlerhafte `utf8_decode()`-Umwandlung bei der Darstellung von Nachrichtentexten entfernt; verhindert Warnungen über ungültiges UTF-8.
- **Semaphore-Verarbeitung:** Bei fehlgeschlagenem `IPS_SemaphoreEnter()` werden nicht initialisierte Nachrichtendaten nicht weiterverarbeitet und der Semaphore nicht unberechtigt freigegeben.
- **Parent-Verbindung:** Nachrichten werden nur bei bereitem Kernel und aktiver Parent-Verbindung an das Hauptmodul gesendet. Dadurch werden Warnungen wie „Cannot find interface for instance“ vermieden.
- **JSON-Eingang:** `ForwardData()` prüft das dekodierte Objekt, die Aktion und die für `Add` beziehungsweise `Remove` erforderlichen Felder und Typen.
- **Serialisierte Daten:** Beide `unserialize()`-Pfade prüfen die Rückgabe auf ein Array; PHP-Warnungen bei beschädigten Daten werden gezielt behandelt. Ungültige gespeicherte Daten werden nicht durch eine leere Nachrichtenliste überschrieben.
- **Persistente Meldungen:** `MessageData` wird in `Create()` nicht mehr bedingungslos geleert. Gespeicherte Meldungen bleiben dadurch nach einem Neustart von IP-Symcon erhalten.
- **WebHook-Parameter:** `ProcessHookData()` validiert `action` und eine positive ganzzahlige `MessageID`, bevor Aktionen ausgeführt werden.
- **Sortierung:** `MessageSort()` behandelt fehlende oder ungültige Zeitstempel mit einem Ersatzwert ausschließlich für den Sortierschlüssel; gespeicherte Nachrichten bleiben unverändert.
- **Zeitstempel bei Aktualisierungen:** Bei einem erneuten `Add`-Aufruf mit derselben `MessageID` und unveränderten Nachrichtendaten bleibt der ursprüngliche Timestamp erhalten. Neue oder tatsächlich geänderte Meldungen erhalten einen neuen Timestamp. Die Korrektur wirkt sich auf die klassische Anzeige und die Kachelansicht aus.
- **Kernelregistrierung:** Die Registrierung des Supporter-Moduls auf `IPS_KERNELMESSAGE` wurde wiederhergestellt, da `IPS_KERNELSTARTED` beim Neustart unerwünschte Meldungen für Türklingel und Postmelder auslösen konnte.
- **WebFront-Konfiguration:** JSON-Struktur und Einträge werden geprüft; ungültige Daten ergeben eine leere Seitenliste, ohne gespeicherte Einstellungen zurückzusetzen.
- **Aktorvariable:** Vor Boolean-Lese- und Schreibzugriffen werden Existenz und Variablentyp geprüft.
- **JSON-Ausgang:** `Add()` und `Remove()` des Supporter-Moduls prüfen, ob `json_encode()` fehlgeschlagen ist, und senden in diesem Fall keine ungültigen Daten.
- **Datumsformatierung:** Float-Zeitstempel werden für `date()` ausdrücklich nach Integer konvertiert, ohne gespeicherte Zeitstempel zu verändern.
- **Objektreferenzen:** Zusätzliche Existenzprüfungen in `RequestAction()`, `MessageSink()` und `GetWebfrontPages()` verhindern Zugriffe auf nicht mehr vorhandene Variablen oder Instanzen.

### Hinzugefügt

- **Optionale Kachelansicht:** Boolean-Property `EnableTileView` (standardmäßig deaktiviert) und zusätzliche String-Variable `TileMessages` mit der Präsentation „Webinhalt“. Die klassische `Messages`-Variable bleibt unverändert.
- **Kacheloptimiertes Layout:** Volle Tabellenbreite, helle Texte, dezente Zeilenhintergründe, einheitliche Mindestzeilenhöhe, freie Fläche für weitere Nachrichten und heller Hinweis „Keine aktiven Meldungen“.
- **Bestehende Bedienung:** Aktionsbuttons erscheinen nur bei entsprechenden Nachrichten und verwenden die bisherigen `remove`-/`switch`-WebHooks. Keine zweite Speicherung und keine zusätzlichen Timer oder WebHooks.

- **Supporter-Statusverwaltung:** Status `102` (aktiv), `104` (deaktiviert) und `200` (überwachtes Objekt fehlt/ungültig).
- **Reaktion auf Löschereignisse:** Behandlung von `VM_DELETE` und `IM_DELETE` für überwachte Objekte.
- **Statussymbole:** Setzen der gespeicherten Instanzsymbole `Ok`, `Power` und `Alert` entsprechend dem Status.

### Getestet

- Kachelansicht im neuen IP-Symcon-Design, einschließlich leerem Zustand, Nachrichten mit und ohne Aktionsbutton sowie erfolgreicher Bestätigung einer Nachricht.

- Reguläre Nachrichtenanzeige und Verarbeitung neuer Nachrichten in IP-Symcon 9.1 / PHP 8.5.8.
- Hinzufügen und Entfernen von Nachrichten, Sortierung und Datumsdarstellung.
- Supporter-Instanzen mit Status `102` und `104`.
- Übernahme bestehender Instanzen beim Wechsel vom Originalmodul auf den Fork in einer konkreten Installation.

### Noch nicht vollständig getestet / bekannte Einschränkungen

- Status `200` und zeitlich verzögerte Ereignisse nach dem Löschen überwachter Objekte.
- Verhalten bei absichtlich beschädigten serialisierten Daten und ungültigen WebHook- beziehungsweise JSON-Eingaben.
- Die in `ObjectIcon` gespeicherten Statussymbole werden im getesteten IP-Symcon-Objektbaum nicht entsprechend dargestellt; Ursache ungeklärt.

## Originalprojekt

Das ursprüngliche Modul wurde von **[Joey-1970](https://github.com/Joey-1970)** entwickelt. Dieser Changelog dokumentiert nur die hier bekannte Weiterentwicklung des Forks und ersetzt nicht die Änderungshistorie des Originals.
