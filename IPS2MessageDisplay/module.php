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
		$this->RegisterAttributeString("MessageData", ""); 
		$this->RegisterPropertyInteger("AutoRemove", 1000);
		$this->RegisterTimer("AutoRemove", 0, 'IPS2MessageDisplay_AutoRemove($_IPS["TARGET"]);');
		$this->RegisterPropertyInteger("ActuatorID", 0);
		
		//Status-Variablen anlegen
		$this->RegisterVariableString("Messages", "Meldungen", "~HTMLBox", 10);
		$this->RegisterVariableInteger("MessageCount", "Anzahl", "", 20);
		
		$MessageData = array();
		$this->WriteAttributeString("MessageData", serialize($MessageData)); 

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
			$MessageData = array();
			set_error_handler(function ($severity, $message, $file, $line) {
				If ($severity == E_WARNING) {
					throw new ErrorException($message, 0, $severity, $file, $line);
				}
				return false;
			});
			try {
				$MessageData = unserialize($this->ReadAttributeString("MessageData"), array("allowed_classes" => false));
			}
			catch (ErrorException $Exception) {
				$MessageData = false;
			}
			finally {
				restore_error_handler();
			}
			If (is_array($MessageData) == false) {
				$this->SendDebug("MessageData", "Gespeicherte Nachrichtendaten sind ungueltig", 0);
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
			return $Result;
		}
	 	switch ($data->Function) {
		    	// Maximale Bewässerungszeit
			case "Add":
				if ((isset($data->MessageID, $data->Text, $data->Expires, $data->Removable, $data->Type, $data->Image, $data->WebfrontID, $data->Page) == false)
					OR (is_int($data->MessageID) == false) OR (is_string($data->Text) == false) OR (is_int($data->Expires) == false)
					OR (is_bool($data->Removable) == false) OR (is_int($data->Type) == false) OR (is_string($data->Image) == false)
					OR (is_int($data->WebfrontID) == false) OR (is_string($data->Page) == false)) {
					break;
				}
	 			$this->WorkProcess("Add", $data->MessageID, $data->Text, $data->Expires, $data->Removable, $data->Type, $data->Image, $data->WebfrontID, $data->Page);
				break;
			case "Remove":
				if ((isset($data->MessageID) == false) OR (is_int($data->MessageID) == false)) {
					break;
				}
				$this->WorkProcess("Remove", $data->MessageID, "", 0, false, 0, "", 0, "");		 
				break;
		}
	return $Result;
	}    
	    
	    
	// Beginn der Funktionen
	private function WorkProcess(string $Activity, int $MessageID, string $Text, int $Expires, bool $Removable, int $Type, string $Image, int $WebfrontID, string $Page) 
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			if (IPS_SemaphoreEnter("WorkProcess", 2000))
			{
				$MessageData = array();
				set_error_handler(function ($severity, $message, $file, $line) {
					If ($severity == E_WARNING) {
						throw new ErrorException($message, 0, $severity, $file, $line);
					}
					return false;
				});
				try {
					$MessageData = unserialize($this->ReadAttributeString("MessageData"), array("allowed_classes" => false));
				}
				catch (ErrorException $Exception) {
					$MessageData = false;
				}
				finally {
					restore_error_handler();
				}
				If (is_array($MessageData) == false) {
					IPS_SemaphoreLeave("WorkProcess");
					$this->SendDebug("MessageData", "Gespeicherte Nachrichtendaten sind ungueltig", 0);
					return;
				}
				switch ($Activity) {
					case 'Add':
						$MessageData[$MessageID]["MessageID"] = $MessageID;
						$MessageData[$MessageID]["Text"] = $Text;
						$MessageData[$MessageID]["Expires"] = $Expires;
						$MessageData[$MessageID]["Removable"] = $Removable;
						$MessageData[$MessageID]["Type"] = $Type;
						$MessageData[$MessageID]["Image"] = $Image;
						$MessageData[$MessageID]["WebfrontID"] = $WebfrontID;
						$MessageData[$MessageID]["Page"] = $Page;
						$MessageData[$MessageID]["Timestamp"] = microtime(true);
						$this->SendDebug("WorkProcess", "Message ".$MessageID." wurde hinzugefuegt", 0);
						break;
					case 'Remove':
						If (is_array($MessageData)) {
							if (array_key_exists($MessageID, $MessageData)) {
								unset($MessageData[$MessageID]);
								$this->SendDebug("WorkProcess", "Message ".$MessageID." wurde entfernt", 0);
							}
							else {
								$this->SendDebug("WorkProcess", "Message ".$MessageID." wurde nicht gefunden", 0);
							}
						}
						break;
					case 'RemoveAll':
						$MessageData = array();
						$this->SendDebug("WorkProcess", "Alle Messages wurde entfernt", 0);
						break;
					case 'RemoveType':
						foreach ($MessageData as $MessageID => $Message) {
							If ($Message["Type"] == $Type) {
								unset($MessageData[$MessageID]);
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
										$this->SendDebug("WorkProcess", "Message ".$MessageID." wurde entfernt", 0);
										$this->RenderData($MessageData);
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
				$this->WriteAttributeString("MessageData", serialize($MessageData));
				IPS_SemaphoreLeave("WorkProcess");
				If ($Activity <> "AutoRemove") {
					$this->RenderData($MessageData);
				}
			}
		}
		else {
			$this->SendDebug("WorkProcess", "Semaphore Abbruch!", 0);
		}
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
		
		// Etwas CSS und HTML
		$style = "";
		$style .= '<style type="text/css">';
		$style .= 'table { width:100%; border-collapse: collapse; }';
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

		$content = $style;
		$content .= '<table>';
		
		if (count($MessageData) == 0) {
			$content .= '<tr>';
			If ($ShowIcon == true) {
				$Icon = "Ok";
				$content .= '<td class="iconMediumSpinner ipsIcon' .$Icon. '"></td>';
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
				$TypeImage = array("Ok", "Alert", "Warning", "Clock");
				$Message['Type'] = min(3, max(0, $Message['Type']));
						
				if ($Message['Image'] <> "") {
					$Image = $Message['Image'];
				}
				else {
					$Image = $TypeImage[$Message['Type']];
				}

				$content .= '<tr>';
				If ($ShowIcon == true) {
					$content .= '<td class="iconMediumSpinner ipsIcon' .$Image. '"></td>';
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
		$content = '<style type="text/css">';
		$content .= 'html, body { margin: 0; padding: 0; background: transparent; color: inherit; font-family: Arial, sans-serif; font-size: 14px; }';
		$content .= 'table { width: 100%; border-collapse: collapse; background: rgba(0, 0, 0, 0.12); }';
		$content .= 'tr { border-bottom: 1px solid rgba(255, 255, 255, 0.12); }';
		$content .= 'tr:last-child { border-bottom: 0; }';
		$content .= 'td { padding: 7px 8px; vertical-align: middle; }';
		$content .= 'td.time { width: 64px; white-space: nowrap; opacity: 0.72; font-size: 12px; color: #E5E7EB; }';
		$content .= 'td.message { text-align: left; overflow-wrap: anywhere; color: #E5E7EB; }';
		$content .= 'td.actions { width: 1%; height: 36px; box-sizing: border-box; white-space: nowrap; text-align: right; }';
		$content .= 'button { margin-left: 5px; padding: 4px 8px; border: 0; border-radius: 4px; color: #fff; background: rgba(100, 100, 100, 0.75); cursor: pointer; font: inherit; font-size: 12px; }';
		$content .= 'button.switch { background: rgba(230, 140, 30, 0.9); }';
		$content .= 'button.remove { background: rgba(45, 145, 90, 0.9); }';
		$content .= 'td.empty { text-align: left; opacity: 0.72; color: #E5E7EB; }';
		$content .= '</style>';
		$content .= '<table>';
		If (count($MessageData) == 0) {
			$content .= '<tr><td class="empty">Keine aktiven Meldungen</td></tr>';
		}
		else {
			foreach ($MessageData as $Message) {
				$content .= '<tr>';
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
					$content .= '<button type="button" class="switch" onclick="window.xhrGet=function xhrGet(o) {var HTTP = new XMLHttpRequest();HTTP.open(\'GET\',o.url,true);HTTP.send();};window.xhrGet({ url: \'hook/IPS2MessageDisplay_'.$this->InstanceID.'?ts=\' + (new Date()).getTime() + \'&action=switch&MessageID='.(int) $Message['MessageID'].'\' });">WF</button>';
				}
				If ($Message['Removable'] == true) {
					$content .= '<button type="button" class="remove" onclick="window.xhrGet=function xhrGet(o) {var HTTP = new XMLHttpRequest();HTTP.open(\'GET\',o.url,true);HTTP.send();};window.xhrGet({ url: \'hook/IPS2MessageDisplay_'.$this->InstanceID.'?ts=\' + (new Date()).getTime() + \'&action=remove&MessageID='.(int) $Message['MessageID'].'\' });">OK</button>';
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
