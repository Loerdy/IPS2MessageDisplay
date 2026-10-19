<?
// Klassendefinition
class IPS2MessageDisplay extends IPSModule 
{
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->RegisterMessage(0, IPS_KERNELSTARTED);
		
		$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyInteger("Sorting", 3);
		$this->RegisterPropertyBoolean("ShowTime", false);
		$this->RegisterPropertyBoolean("ShowIcon", true);
		$this->RegisterPropertyBoolean("EnableTileView", false);
		$this->RegisterAttributeString("MessageData", "[]");
		$this->RegisterPropertyInteger("AutoRemove", 1000);
		$this->RegisterTimer("AutoRemove", 0, 'IPS2MessageDisplay_AutoRemove($_IPS["TARGET"]);');
		$this->RegisterPropertyInteger("ActuatorID", 0);
		
		//Status-Variablen anlegen
		$this->RegisterVariableString("Messages", "Meldungen", "~HTMLBox", 10);
		$this->RegisterVariableInteger("MessageCount", "Anzahl", "", 20);
		
        }
 	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
				
		$arrayElements = array(); 
		
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv");
		$arrayElements[] = array("type" => "Label", "caption" => "_____________________________________________________________________________________________________");
 
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "Neuste Nachricht zuerst", "value" => SORT_DESC);
		$arrayOptions[] = array("label" => "Älteste Nachricht zuerst", "value" => SORT_ASC);
		$arrayElements[] = array("type" => "Select", "name" => "Sorting", "caption" => "Sortierung in der Darstellung", "options" => $arrayOptions );
		
		$arrayElements[] = array("name" => "ShowTime", "type" => "CheckBox",  "caption" => "Uhrzeit anzeigen");
		$arrayElements[] = array("name" => "ShowIcon", "type" => "CheckBox",  "caption" => "Icon anzeigen");
		$arrayElements[] = array("name" => "EnableTileView", "type" => "CheckBox", "caption" => "Kachelansicht aktivieren");
		$arrayElements[] = array("type" => "Label", "caption" => "Boolean-Variable die anzeigt, das Meldungen vorhanden sind");
 		$arrayElements[] = array("type" => "SelectVariable", "name" => "ActuatorID", "caption" => "Anzeige-Variablen ID");
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements)); 		 
 	}       
	   
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
		$this->MaintainVariable("TileMessages", "Meldungen Kachel", VARIABLETYPE_STRING, array(
			"PRESENTATION" => VARIABLE_PRESENTATION_WEB_CONTENT,
			"HTML_TYPE" => 0,
			"PADDING" => true
		), 30, $this->ReadPropertyBoolean("EnableTileView"));
		
		if (IPS_GetKernelRunlevel() == KR_READY) {
			// Webhook einrichten
			$this->RegisterHook("/hook/IPS2MessageDisplay_".$this->InstanceID);
		}
		
		If ($this->ReadPropertyBoolean("Open") == true) {
			$SemaphoreName = "IPS2MessageDisplay_WorkProcess_".$this->InstanceID;
			If (IPS_SemaphoreEnter($SemaphoreName, 2000) == false) {
				$this->SendDebug("MessageData", "Semaphore Abbruch beim Lesen der Nachrichtendaten", 0);
				return;
			}
			try {
				$MessageData = $this->ReadMessageData();
			}
			finally {
				IPS_SemaphoreLeave($SemaphoreName);
			}
			If (is_array($MessageData) == false) {
				return;
			}
			$this->RenderData($MessageData);
			$this->SetTimerInterval("AutoRemove", 1000);
			If ($this->GetStatus() <> 102) {
				$this->SetStatus(102);
			}
			
		}
		else {
			If ($this->GetStatus() <> 104) {
				$this->SetStatus(104);
			}
			$this->SetTimerInterval("AutoRemove", 0);
		}	
	}
	
	public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    	{
		switch ($Message) {
			case IPS_KERNELSTARTED:
				// IPS_KERNELSTARTED
				$this->RegisterHook("/hook/IPS2MessageDisplay_".$this->InstanceID);
				break;
			
		}
    	}        
	 
	public function ForwardData($JSONString) 
	 {
	 	// Empfangene Daten von der Device Instanz
	    	$data = json_decode($JSONString);
	    	$Result = 0;
		if ((is_object($data) == false) OR (isset($data->Function) == false) OR (is_string($data->Function) == false)) {
			if (is_object($data) == false) {
				$this->SendDebug("ForwardData", "Nachricht verworfen: ungueltiges JSON oder kein Objekt", 0);
			}
			elseif (isset($data->Function) == false) {
				$this->SendDebug("ForwardData", "Nachricht verworfen: Feld Function fehlt", 0);
			}
			else {
				$this->SendDebug("ForwardData", "Nachricht verworfen: Feld Function muss eine Zeichenkette sein", 0);
			}
			return $Result;
		}
	 	switch ($data->Function) {
		    	// Maximale Bewässerungszeit
			case "Add":
				if ((isset($data->MessageID, $data->Text, $data->Expires, $data->Removable, $data->Type, $data->Image, $data->WebfrontID, $data->Page) == false)
					OR (is_int($data->MessageID) == false) OR (is_string($data->Text) == false) OR (is_int($data->Expires) == false)
					OR (is_bool($data->Removable) == false) OR (is_int($data->Type) == false) OR (is_string($data->Image) == false)
					OR (is_int($data->WebfrontID) == false) OR (is_string($data->Page) == false)) {
					if (isset($data->MessageID) == false) {
						$this->SendDebug("ForwardData", "Aktion Add verworfen: Feld MessageID fehlt", 0);
					}
					elseif (is_int($data->MessageID) == false) {
						$this->SendDebug("ForwardData", "Aktion Add verworfen: Feld MessageID muss eine Ganzzahl sein", 0);
					}
					elseif (isset($data->Text) == false) {
						$this->SendDebug("ForwardData", "Aktion Add verworfen: Feld Text fehlt", 0);
					}
					elseif (is_string($data->Text) == false) {
						$this->SendDebug("ForwardData", "Aktion Add verworfen: Feld Text muss eine Zeichenkette sein", 0);
					}
					elseif (isset($data->Expires) == false) {
						$this->SendDebug("ForwardData", "Aktion Add verworfen: Feld Expires fehlt", 0);
					}
					elseif (is_int($data->Expires) == false) {
						$this->SendDebug("ForwardData", "Aktion Add verworfen: Feld Expires muss eine Ganzzahl sein", 0);
					}
					elseif (isset($data->Removable) == false) {
						$this->SendDebug("ForwardData", "Aktion Add verworfen: Feld Removable fehlt", 0);
					}
					elseif (is_bool($data->Removable) == false) {
						$this->SendDebug("ForwardData", "Aktion Add verworfen: Feld Removable muss Boolean sein", 0);
					}
					elseif (isset($data->Type) == false) {
						$this->SendDebug("ForwardData", "Aktion Add verworfen: Feld Type fehlt", 0);
					}
					elseif (is_int($data->Type) == false) {
						$this->SendDebug("ForwardData", "Aktion Add verworfen: Feld Type muss eine Ganzzahl sein", 0);
					}
					elseif (isset($data->Image) == false) {
						$this->SendDebug("ForwardData", "Aktion Add verworfen: Feld Image fehlt", 0);
					}
					elseif (is_string($data->Image) == false) {
						$this->SendDebug("ForwardData", "Aktion Add verworfen: Feld Image muss eine Zeichenkette sein", 0);
					}
					elseif (isset($data->WebfrontID) == false) {
						$this->SendDebug("ForwardData", "Aktion Add verworfen: Feld WebfrontID fehlt", 0);
					}
					elseif (is_int($data->WebfrontID) == false) {
						$this->SendDebug("ForwardData", "Aktion Add verworfen: Feld WebfrontID muss eine Ganzzahl sein", 0);
					}
					elseif (isset($data->Page) == false) {
						$this->SendDebug("ForwardData", "Aktion Add verworfen: Feld Page fehlt", 0);
					}
					else {
						$this->SendDebug("ForwardData", "Aktion Add verworfen: Feld Page muss eine Zeichenkette sein", 0);
					}
					break;
				}
	 			$this->WorkProcess("Add", $data->MessageID, $data->Text, $data->Expires, $data->Removable, $data->Type, $data->Image, $data->WebfrontID, $data->Page);
				break;
			case "Remove":
				if ((isset($data->MessageID) == false) OR (is_int($data->MessageID) == false)) {
					if (isset($data->MessageID) == false) {
						$this->SendDebug("ForwardData", "Aktion Remove verworfen: Feld MessageID fehlt", 0);
					}
					else {
						$this->SendDebug("ForwardData", "Aktion Remove verworfen: Feld MessageID muss eine Ganzzahl sein", 0);
					}
					break;
				}
				$this->WorkProcess("Remove", $data->MessageID, "", 0, false, 0, "", 0, "");		 
				break;
			default:
				$this->SendDebug("ForwardData", "Nachricht verworfen: unbekannte Aktion ".$data->Function, 0);
				break;
		}
	return $Result;
	}    
	    
	    
	// Beginn der Funktionen
	private function WorkProcess(string $Activity, int $MessageID, string $Text, int $Expires, bool $Removable, int $Type, string $Image, int $WebfrontID, string $Page) 
	{
		$SemaphoreName = "IPS2MessageDisplay_WorkProcess_".$this->InstanceID;
		If ($this->ReadPropertyBoolean("Open") == true) {
			if (IPS_SemaphoreEnter($SemaphoreName, 2000))
			{
				try {
					$MessageData = $this->ReadMessageData();
					If (is_array($MessageData) == false) {
						return;
					}
					$MessageDataChanged = false;
					switch ($Activity) {
					case 'Add':
						$NewMessageData = array(
							"MessageID" => $MessageID,
							"Text" => $Text,
							"Expires" => $Expires,
							"Removable" => $Removable,
							"Type" => $Type,
							"Image" => $Image,
							"WebfrontID" => $WebfrontID,
							"Page" => $Page
						);
						$MessageChanged = !array_key_exists($MessageID, $MessageData) || !is_array($MessageData[$MessageID]);
						if ($MessageChanged == false) {
							foreach ($NewMessageData as $Field => $Value) {
								if (!array_key_exists($Field, $MessageData[$MessageID]) || $MessageData[$MessageID][$Field] !== $Value) {
									$MessageChanged = true;
									break;
								}
							}
						}
						if ($MessageChanged) {
							$NewMessageData["Timestamp"] = microtime(true);
							$MessageData[$MessageID] = $NewMessageData;
							$MessageDataChanged = true;
						}
						$this->SendDebug("WorkProcess", "Message ".$MessageID." wurde hinzugefuegt", 0);
						break;
					case 'Remove':
						If (is_array($MessageData)) {
							if (array_key_exists($MessageID, $MessageData)) {
								unset($MessageData[$MessageID]);
								$MessageDataChanged = true;
								$this->SendDebug("WorkProcess", "Message ".$MessageID." wurde entfernt", 0);
							}
							else {
								$this->SendDebug("WorkProcess", "Message ".$MessageID." wurde nicht gefunden", 0);
							}
						}
						break;
					case 'RemoveAll':
						If (count($MessageData) > 0) {
							$MessageData = array();
							$MessageDataChanged = true;
						}
						$this->SendDebug("WorkProcess", "Alle Messages wurde entfernt", 0);
						break;
					case 'RemoveType':
						foreach ($MessageData as $MessageID => $Message) {
							If ($Message["Type"] == $Type) {
								unset($MessageData[$MessageID]);
								$MessageDataChanged = true;
								$this->SendDebug("WorkProcess", "Message ".$MessageID." wurde entfernt", 0);
							}
						}
						break;
					case 'AutoRemove':
						if (count($MessageData) > 0) {
							foreach ($MessageData as $MessageID => $Message) {
								If ($Message["Expires"] > 0) {
									If ($Message["Expires"] + $Message["Timestamp"] <= microtime(true) ) {
										unset($MessageData[$MessageID]);
										$MessageDataChanged = true;
										$this->SendDebug("WorkProcess", "Message ".$MessageID." wurde entfernt", 0);
									}
								}
							}
						}
						break;
					case 'Switch':
						If (is_array($MessageData)) {
							if (array_key_exists($MessageID, $MessageData)) {
								If ((intval($MessageData[$MessageID]["WebfrontID"]) >= 10000) AND (strlen($MessageData[$MessageID]["Page"]) > 0)) {
									$this->SendDebug("WorkProcess", "Switch Webfront: ".$MessageData[$MessageID]["WebfrontID"]." Item: ".$MessageData[$MessageID]["Page"], 0);
									WFC_SwitchPage (intval($MessageData[$MessageID]["WebfrontID"]), $MessageData[$MessageID]["Page"]);
								}
							}
						}
						break;
				}
					If (($MessageDataChanged == true) AND ($this->WriteMessageData($MessageData) == false)) {
						return;
					}
				}
				finally {
					IPS_SemaphoreLeave($SemaphoreName);
				}
				If (($Activity <> "AutoRemove") OR ($MessageDataChanged == true)) {
					$this->RenderData($MessageData);
				}
			}
			else {
				$this->SendDebug("WorkProcess", "Semaphore Abbruch!", 0);
			}
		}
	}

	private function ReadMessageData()
	{
		$RawMessageData = $this->ReadAttributeString("MessageData");
		try {
			$MessageData = json_decode($RawMessageData, true, 512, JSON_THROW_ON_ERROR);
			If (is_array($MessageData) == false) {
				$this->ReportMessageDataReadError("Gespeicherte JSON-Nachrichtendaten sind kein Array");
				return false;
			}
			If ($this->ValidateMessageData($MessageData) == false) {
				$this->ReportMessageDataReadError("Gespeicherte JSON-Nachrichtendaten haben eine ungueltige Struktur");
				return false;
			}
			return $MessageData;
		}
		catch (JsonException $Exception) {
		}

		set_error_handler(function ($severity, $message, $file, $line) {
			throw new ErrorException($message, 0, $severity, $file, $line);
		});
		try {
			$MessageData = unserialize($RawMessageData, array("allowed_classes" => false));
		}
		catch (Throwable $Exception) {
			$MessageData = false;
		}
		finally {
			restore_error_handler();
		}
		If ((is_array($MessageData) == false) OR ($this->ValidateMessageData($MessageData) == false)) {
			$this->ReportMessageDataReadError("Gespeicherte Nachrichtendaten sind weder gueltiges JSON noch gueltige Legacy-Daten");
			return false;
		}
		If ($this->WriteMessageData($MessageData) == false) {
			$this->ReportMessageDataReadError("Migration der Legacy-Nachrichtendaten ist fehlgeschlagen");
			return false;
		}
		$this->SendDebug("MessageData", "Legacy-Nachrichtendaten wurden erfolgreich nach JSON migriert", 0);
		return $MessageData;
	}

	private function ReportMessageDataReadError($DebugMessage)
	{
		$this->SendDebug("MessageData", $DebugMessage, 0);
		$this->LogMessage("Gespeicherte Nachrichtendaten konnten nicht gelesen oder migriert werden und wurden nicht veraendert.", KL_ERROR);
	}

	private function WriteMessageData($MessageData)
	{
		If ($this->ValidateMessageData($MessageData) == false) {
			$this->SendDebug("MessageData", "Nachrichtendaten wurden wegen ungueltiger Struktur nicht gespeichert", 0);
			return false;
		}
		try {
			$JsonMessageData = json_encode($MessageData, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
			$VerifiedMessageData = json_decode($JsonMessageData, true, 512, JSON_THROW_ON_ERROR);
		}
		catch (JsonException $Exception) {
			$this->SendDebug("MessageData", "JSON-Verarbeitung der Nachrichtendaten ist fehlgeschlagen", 0);
			return false;
		}
		If ((is_array($VerifiedMessageData) == false) OR ($VerifiedMessageData !== $MessageData)) {
			$this->SendDebug("MessageData", "JSON-Pruefung der Nachrichtendaten ist fehlgeschlagen", 0);
			return false;
		}
		$this->WriteAttributeString("MessageData", $JsonMessageData);
		return true;
	}

	private function ValidateMessageData($MessageData)
	{
		If (is_array($MessageData) == false) {
			return false;
		}
		foreach ($MessageData as $Message) {
			If ((is_array($Message) == false)
				OR (isset($Message["MessageID"]) == false) OR (is_int($Message["MessageID"]) == false)
				OR (isset($Message["Text"]) == false) OR (is_string($Message["Text"]) == false)
				OR (isset($Message["Expires"]) == false) OR (is_int($Message["Expires"]) == false)
				OR (array_key_exists("Removable", $Message) == false) OR (is_bool($Message["Removable"]) == false)
				OR (isset($Message["Type"]) == false) OR (is_int($Message["Type"]) == false)
				OR (array_key_exists("Image", $Message) == false) OR (is_string($Message["Image"]) == false)
				OR (isset($Message["WebfrontID"]) == false) OR (is_int($Message["WebfrontID"]) == false)
				OR (array_key_exists("Page", $Message) == false) OR (is_string($Message["Page"]) == false)
				OR (isset($Message["Timestamp"]) == false)
				OR ((is_int($Message["Timestamp"]) == false) AND (is_float($Message["Timestamp"]) == false))) {
				return false;
			}
		}
		return true;
	}
	    
	public function Add(int $MessageID, string $Text, int $Expires, bool $Removable, int $Type, string $Image, int $WebfrontID, string $Page) 
	{
		$this->WorkProcess("Add", $MessageID, $Text, $Expires, $Removable, $Type, $Image, $WebfrontID, $Page);
	}
	    
	public function Remove(int $MessageID) 
	{
		$this->WorkProcess("Remove", $MessageID, "", 0, false, 0, "", 0, "");
	}
	    
	public function RemoveAll() 
	{
		$this->WorkProcess("RemoveAll", 0, "", 0, false, 0, "", 0, "");
	}    
	    
	public function RemoveType(int $Type) 
	{
		$Type = min(3, max(0, $Type));
		$this->WorkProcess("RemoveType", 0, "", 0, false, $Type, "", 0, "");
	}
	    
	public function AutoRemove() 
	{
		$this->WorkProcess("AutoRemove", 0, "", 0, false, 0, "", 0, "");
	}
	    
	public function Switch(int $MessageID) 
	{
		$this->WorkProcess("Switch", $MessageID, "", 0, false, 0, "", 0, "");
	}
	    
	protected function ProcessHookData() 
	{		
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("ProcessHookData", "Ausfuehrung: ".$_SERVER['HOOK'], 0);
			If ((isset($_GET['action'], $_GET['MessageID']) == false) OR (is_string($_GET['action']) == false) OR (is_string($_GET['MessageID']) == false)) {
				$this->SendDebug("ProcessHookData", "Ungueltige WebHook-Parameter!", 0);
				return;
			}
			$Action = $_GET['action'];
			$MessageID = filter_var($_GET['MessageID'], FILTER_VALIDATE_INT, array("options" => array("min_range" => 1)));
			If ((($Action <> "remove") AND ($Action <> "switch")) OR ($MessageID === false)) {
				$this->SendDebug("ProcessHookData", "Ungueltige WebHook-Parameter!", 0);
				return;
			}
			switch ($Action) {
			    	case 'remove':
					$this->WorkProcess("Remove", $MessageID, "", 0, false, 0, "", 0, "");
				  		//$this->Remove($MessageID);
			      		break;
			    case 'switch':
					$this->WorkProcess("Switch", $MessageID, "", 0, false, 0, "", 0, "");
				  		//$this->Switch($MessageID);
			      		break;
			}
		}
	}       
	    
	private function RenderData($MessageData)		
	{
		$ShowTime = $this->ReadPropertyBoolean("ShowTime");
		$ShowIcon = $this->ReadPropertyBoolean("ShowIcon");
		$Sorting = $this->ReadPropertyInteger("Sorting");
		$TypeImage = array("fa-circle-check", "fa-circle-exclamation", "fa-triangle-exclamation", "fa-clock");
		$ImageMapping = array("Ok" => "fa-circle-check", "Alert" => "fa-circle-exclamation", "Warning" => "fa-triangle-exclamation", "Clock" => "fa-clock");
		
		// Etwas CSS und HTML
		$style = "";
		$style .= '<style type="text/css">';
		$style .= 'table { width:100%; border-collapse: collapse; }';
		$style .= 'td.icon { border-right: 1px solid rgba(255, 255, 255, 0.2); border-top: 1px solid rgba(255, 255, 255, 0.1); }';
		$style .= 'td.fst { width: 36px; padding: 2px; border-left: 1px solid rgba(255, 255, 255, 0.2); border-top: 1px solid rgba(255, 255, 255, 0.1); }';
		$style .= 'td.mid { padding: 2px;  border-top: 1px solid rgba(255, 255, 255, 0.1); }';
		$style .= 'td.lst { width: 42px; text-align:center; padding: 2px;  border-right: 1px solid rgba(255, 255, 255, 0.2); border-top: 1px solid rgba(255, 255, 255, 0.1); }';
		$style .= 'tr:last-child { border-bottom: 1px solid rgba(255, 255, 255, 0.2); }';
		$style .= '.blue { padding: 5px; color: rgb(255, 255, 255); background-color: rgb(0, 0, 255); background-image: linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -o-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -moz-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -webkit-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -ms-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); }';
		$style .= '.red { padding: 5px; color: rgb(255, 255, 255); background-color: rgb(255, 0, 0); background-image: linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -o-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -moz-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -webkit-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -ms-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); }';
		$style .= '.green { padding: 5px; color: rgb(255, 255, 255); background-color: rgb(0, 255, 0); background-image: linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -o-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -moz-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -webkit-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -ms-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); }';
		$style .= '.yellow { padding: 5px; color: rgb(255, 255, 255); background-color: rgb(255, 255, 0); background-image: linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -o-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -moz-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -webkit-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -ms-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); }';
		$style .= '.orange { padding: 5px; color: rgb(255, 255, 255); background-color: rgb(255, 160, 0); background-image: linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -o-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -moz-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -webkit-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); background-image: -ms-linear-gradient(top,rgba(0,0,0,0) 0,rgba(0,0,0,0.3) 50%,rgba(0,0,0,0.3) 100%); }';
		$style .= '</style>';

		$content = '<script src="/icons.js"></script>';
		$content .= $style;
		$content .= '<table>';
		
		if (count($MessageData) == 0) {
			$content .= '<tr>';
			If ($ShowIcon == true) {
				$content .= '<td class="icon"><i class="fa-light fa-circle-check"></i></td>';
			}
			if ($ShowTime == true) {
				$content .= '<td class="lst">'.date("d.m.Y H:i", time() ).'</td>';
			}
			$content .= '<td class="mid">Keine Meldungen vorhanden!</td>';
			$content .= '<td class="mid"></td>';
			$content .= '<td class=\'lst\'><div class=\'green\' onclick=\'alert("Nachricht kann nicht bestätigt werden.");\'>...</div></td>';
			$content .= '</tr>';
	  	}
	  	else {
	    		$MessageData =  $this->MessageSort($MessageData, 'Timestamp',  $Sorting);
			foreach ($MessageData as $Number => $Message) {
	      			$TypeColor = array("green", "red", "yellow", "blue");
				$Message['Type'] = min(3, max(0, $Message['Type']));
				$MessageImage = is_string($Message['Image'] ?? null) ? $Message['Image'] : "";

				if (array_key_exists($MessageImage, $ImageMapping)) {
					$Icon = $ImageMapping[$MessageImage];
				} elseif (($MessageImage <> "") AND (preg_match('/^[a-z0-9-]+$/', $MessageImage) === 1)) {
					$Icon = "fa-".$MessageImage;
				}
				else {
					$Icon = $TypeImage[$Message['Type']];
				}

				$content .= '<tr>';
				If ($ShowIcon == true) {
					$content .= '<td class="icon"><i class="fa-light '.htmlspecialchars($Icon, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'"></i></td>';
				}
				if ($ShowTime == true) {
					$SecondsToday= date('H') * 3600 + date('i') * 60 + date('s');
					If ($Message['Timestamp'] <= (time() - $SecondsToday)) {
						$content .= '<td class="lst">'.date("d.m.Y H:i", (int) $Message['Timestamp']).'</td>';
					}
					else {
						$content .= '<td class="lst">'.date("H:i:s", (int) $Message['Timestamp']).'</td>';
					}
				}
				$content .= '<td class="mid">'.$Message['Text'].'</td>';
				
				if ($Message['Page'] <> "") {
					$TypeWF = 'orange';
					$content .= '<td class=\'lst\'><div class=\''.$TypeWF.'\' onclick="window.xhrGet=function xhrGet(o) {var HTTP = new XMLHttpRequest();HTTP.open(\'GET\',o.url,true);HTTP.send();};window.xhrGet({ url: \'hook/IPS2MessageDisplay_'.$this->InstanceID.'?ts=\' + (new Date()).getTime() + \'&action=switch&MessageID='.$Message['MessageID'].'\' });">WF</div></td>';
					
				}
				else {
					$content .= '<td class="mid"></td>';
				}
				
				if ($Message['Removable'] == true) {
					$content .= '<td class=\'lst\'><div class=\''.$TypeColor[$Message['Type']].'\' onclick="window.xhrGet=function xhrGet(o) {var HTTP = new XMLHttpRequest();HTTP.open(\'GET\',o.url,true);HTTP.send();};window.xhrGet({ url: \'hook/IPS2MessageDisplay_'.$this->InstanceID.'?ts=\' + (new Date()).getTime() + \'&action=remove&MessageID='.$Message['MessageID'].'\' });">OK</div></td>';
					
				}
				else {
					$content .= '<td class=\'lst\'><div class=\''.$TypeColor[$Message['Type']].'\' onclick=\'alert("Nachricht kann nicht bestätigt werden.");\'>...</div></td>';
				}
				$content .= '</tr>';
			}
	  	}
	  	$content .= '</table>';
	  	If (GetValueString($this->GetIDForIdent("Messages")) <> $content) {
			SetValueString($this->GetIDForIdent("Messages"), $content);
		}
		If ($this->ReadPropertyBoolean("EnableTileView") == true) {
			$this->RenderTileData($MessageData);
		}
		If (GetValueInteger($this->GetIDForIdent("MessageCount")) <> count($MessageData)) {
			SetValueInteger($this->GetIDForIdent("MessageCount"), count($MessageData));
		}
		
		$ActuatorID = $this->ReadPropertyInteger("ActuatorID");
		If ((count($MessageData) == 0) AND ($ActuatorID > 0) AND (IPS_VariableExists($ActuatorID) == true)
			AND (IPS_GetVariable($ActuatorID)['VariableType'] == VARIABLETYPE_BOOLEAN)) {
			If (GetValueBoolean($ActuatorID) == true) {
				SetValueBoolean($ActuatorID, false);
			}
		}
		elseif ((count($MessageData) > 0) AND ($ActuatorID > 0) AND (IPS_VariableExists($ActuatorID) == true)
			AND (IPS_GetVariable($ActuatorID)['VariableType'] == VARIABLETYPE_BOOLEAN)) {
			If (GetValueBoolean($ActuatorID) == false) {
				SetValueBoolean($ActuatorID, true);
			}
		}
		
	}    

	private function RenderTileData($MessageData)
	{
		$ShowTime = $this->ReadPropertyBoolean("ShowTime");
		$ShowIcon = $this->ReadPropertyBoolean("ShowIcon");
		$TypeImage = array("fa-circle-check", "fa-circle-exclamation", "fa-triangle-exclamation", "fa-clock");
		$ImageMapping = array("Ok" => "fa-circle-check", "Alert" => "fa-circle-exclamation", "Warning" => "fa-triangle-exclamation", "Clock" => "fa-clock");
		$TypeColor = array("green", "red", "yellow", "blue");
		$content = '<script src="/icons.js"></script>';
		$content .= '<style type="text/css">';
		$content .= 'html, body { margin: 0; padding: 0; background: transparent; font-family: Arial, sans-serif; font-size: 14px; scrollbar-width: none; }';
		$content .= 'html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }';
		$content .= 'table { width: 100%; border-collapse: collapse; background: transparent; }';
		$content .= 'tr { border-bottom: 1px solid rgba(255, 255, 255, 0.12); }';
		$content .= 'tr:last-child { border-bottom: 0; }';
		$content .= 'td { padding: 7px 8px; vertical-align: middle; }';
		$content .= 'td.icon { width: 18px; text-align: center; }';
		$content .= 'td.time { width: 64px; white-space: nowrap; opacity: 0.72; font-size: 12px; }';
		$content .= 'td.message { text-align: left; overflow-wrap: anywhere; }';
		$content .= 'td.actions { width: 1%; height: 36px; box-sizing: border-box; white-space: nowrap; text-align: right; }';
		$content .= 'button { margin-left: 5px; padding: 4px 8px; border: 0; border-radius: 4px; color: #fff; background: rgba(100, 100, 100, 0.75); cursor: pointer; font: inherit; font-size: 12px; }';
		$content .= 'button.green { color: #FFFFFF; background: #16803C; }';
		$content .= 'button.red { color: #FFFFFF; background: #DC2626; }';
		$content .= 'button.yellow { color: #1F2937; background: #FACC15; }';
		$content .= 'button.blue { color: #FFFFFF; background: #1D4ED8; }';
		$content .= 'td.empty { text-align: left; opacity: 0.72; }';
		$content .= '</style>';
		$content .= '<table>';
		If (count($MessageData) == 0) {
			$content .= '<tr><td class="empty">Keine aktiven Meldungen</td></tr>';
		}
		else {
			foreach ($MessageData as $Message) {
				$MessageType = min(3, max(0, $Message['Type']));
				$content .= '<tr>';
				If ($ShowIcon == true) {
					if (array_key_exists($Message['Image'], $ImageMapping)) {
						$Icon = $ImageMapping[$Message['Image']];
					} elseif (($Message['Image'] <> "") AND (preg_match('/^[a-z0-9-]+$/', $Message['Image']) === 1)) {
						$Icon = "fa-".$Message['Image'];
					} else {
						$Icon = $TypeImage[$MessageType];
					}
					$content .= '<td class="icon"><i class="fa-light '.htmlspecialchars($Icon, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'"></i></td>';
				}
				If ($ShowTime == true) {
					$SecondsToday = date('H') * 3600 + date('i') * 60 + date('s');
					If ($Message['Timestamp'] <= (time() - $SecondsToday)) {
						$content .= '<td class="time">'.date("d.m. H:i", (int) $Message['Timestamp']).'</td>';
					}
					else {
						$content .= '<td class="time">'.date("H:i:s", (int) $Message['Timestamp']).'</td>';
					}
				}
				$content .= '<td class="message">'.htmlspecialchars($Message['Text'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</td>';
				$content .= '<td class="actions">';
				If ($Message['Page'] <> "") {
					$content .= '<button type="button" class="switch '.$TypeColor[$MessageType].'" onclick="window.xhrGet=function xhrGet(o) {var HTTP = new XMLHttpRequest();HTTP.open(\'GET\',o.url,true);HTTP.send();};window.xhrGet({ url: \'hook/IPS2MessageDisplay_'.$this->InstanceID.'?ts=\' + (new Date()).getTime() + \'&action=switch&MessageID='.(int) $Message['MessageID'].'\' });">WF</button>';
				}
				If ($Message['Removable'] == true) {
					$content .= '<button type="button" class="remove '.$TypeColor[$MessageType].'" onclick="window.xhrGet=function xhrGet(o) {var HTTP = new XMLHttpRequest();HTTP.open(\'GET\',o.url,true);HTTP.send();};window.xhrGet({ url: \'hook/IPS2MessageDisplay_'.$this->InstanceID.'?ts=\' + (new Date()).getTime() + \'&action=remove&MessageID='.(int) $Message['MessageID'].'\' });">OK</button>';
				}
				$content .= '</td>';
				$content .= '</tr>';
			}
		}
		$content .= '</table>';

		If (GetValueString($this->GetIDForIdent("TileMessages")) <> $content) {
			SetValueString($this->GetIDForIdent("TileMessages"), $content);
		}
	}
	
	private function MessageSort($MessageData, $DataField, $SortOrder) 
	{
		${$DataField} = array();
    		if(is_array($MessageData)==true) {
            		foreach ($MessageData as $key => $value) {
				if ((is_array($value) == true) AND (array_key_exists($DataField, $value) == true)
					AND ((is_int($value[$DataField]) == true) OR (is_float($value[$DataField]) == true))) {
					${$DataField}[$key] = strtolower($value[$DataField]);
				}
				else {
					${$DataField}[$key] = 0;
				}
        		}
    		}
    		array_multisort(${$DataField}, $SortOrder, $MessageData);
    	return $MessageData;
	}
	    
	private function GetWebfrontID()
	{
    		$guid = "{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}"; // Webfront Konfigurator
    		//Auflisten
    		$WebfrontArray = (IPS_GetInstanceListByModuleID($guid));
    		$Result = array();
    		foreach ($WebfrontArray as $Webfront) {
        		$Result[$Webfront] = IPS_GetName($Webfront);
    		}
	return $Result;   
	}    
	    
	private function RegisterHook($WebHook)
    	{
        	$ids = IPS_GetInstanceListByModuleID('{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}');
        	if (count($ids) > 0) {
            		$hooks = json_decode(IPS_GetProperty($ids[0], 'Hooks'), true);
            		$found = false;
            		foreach ($hooks as $index => $hook) {
                		if ($hook['Hook'] == $WebHook) {
                    			if ($hook['TargetID'] == $this->InstanceID) {
                        			return;
                    			}
                    			$hooks[$index]['TargetID'] = $this->InstanceID;
                    			$found = true;
                		}
            		}
            		if (!$found) {
                		$hooks[] = ['Hook' => $WebHook, 'TargetID' => $this->InstanceID];
            		}
            		IPS_SetProperty($ids[0], 'Hooks', json_encode($hooks));
            		IPS_ApplyChanges($ids[0]);
		}
        }
}
