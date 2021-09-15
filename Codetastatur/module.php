<?php

class MaxFlexCodepanel extends IPSModule {
	
	public function Create(){
		//Never delete this line!
		parent::Create();
		
		//These lines are parsed on Symcon Startup or Instance creation
		//You cannot use variables here. Just static values.
		
		$this->RegisterPropertyInteger("ID", 1);
		$this->RegisterPropertyInteger("TimerInterval", 15);

		$this->RegisterVariableInteger("CODE", "Code", "", 1);
		$this->RegisterVariableBoolean("CODEOK", "Ist Code Ok?", "", 2);

		$this->RegisterTimer("ClearCodeTimer", 0, 'BRELAG_SetClearCodeTimer($_IPS[\'TARGET\']);');
		$this->RegisterTimer("FirstLEDoff", 0, 'BRELAG_TurnOffFirstLED($_IPS[\'TARGET\']);');
		$this->RegisterTimer("SecondLEDoff", 0, 'BRELAG_TurnOffSecondLED($_IPS[\'TARGET\']);');
		$this->RegisterTimer("ThirdLEDoff", 0, 'BRELAG_TurnOffThirdLED($_IPS[\'TARGET\']);');

		$this->ConnectParent("{1252F612-CF3F-4995-A152-DA7BE31D4154}"); //DominoSwiss eGate
	}

	public function Destroy() {
		//Never delete this line!
		parent::Destroy();
		
	}
	
	public function ApplyChanges() {
		//Never delete this line!
		parent::ApplyChanges();

	}

	public function ReceiveData($JSONString) {

		$data = json_decode($JSONString);
		
		$this->SendDebug("BufferIn", print_r($data->Values, true), 0);
		$id = $data->Values->ID;
		$command = $data->Values->Command;

		if($id == $this->ReadPropertyInteger("ID")) {
			// Hole das Passwort vom Alarmanlage Modul.
				$securityGUID = "{17433113-1A92-45B3-F250-B5E426040E64}";
				$securityInstance = IPS_GetInstanceListByModuleID($securityGUID);
				$securityInstanceId = $securityInstance[0];
				$securityPassword = IPS_GetProperty($securityInstanceId, "Password");
				$securityEnterPasswordId = IPS_GetObjectIDByIdent("Password", $securityInstanceId);
				$securityModus = IPS_GetObjectIDByIdent("Mode", $securityInstanceId);
			// Hole aus der Konfiguration den Timer interval und rechne in Millisekunden um.	
				$timerintervalSecond = $this->ReadPropertyInteger("TimerInterval");
				$timerintervalMillisecond = $timerintervalSecond * 1000;

			$value = $data->Values->Value;

			if($command == 42) {
				if($value > 0) {
					$this->SetTimerInterval("ClearCodeTimer", $timerintervalMillisecond);
					$typedCode = GetValue($this->GetIDForIdent("CODE"));
					$codeOK = GetValue($this->GetIDForIdent("CODEOK"));
					switch($value) {
						case 1:
							if($codeOK) {
								SetValue($securityEnterPasswordId, $securityPassword);
								SetValue($securityModus, 0);
								SetValue($this->GetIDForIdent("CODE"), 0);
								SetValue($this->GetIDForIdent("CODEOK"), false);
								$this->SetLED(8);
								$this->SetTimerInterval("SecondLEDoff", 1000);
								$this->SetTimerInterval("ThirdLEDoff", 2000);
							} else{
								$typedCode .= 1;
								SetValue($this->GetIDForIdent("CODE"), $typedCode);
							}
						break;
	
						case 2:
							if($codeOK) {
								SetValue($securityEnterPasswordId, $securityPassword);
								SetValue($securityModus, 1);
								SetValue($this->GetIDForIdent("CODE"), 0);
								SetValue($this->GetIDForIdent("CODEOK"), false);
								$this->SetLED(9);
								$this->SetTimerInterval("FirstLEDoff", 1000);
								$this->SetTimerInterval("ThirdLEDoff", 2000);
							} else{
								$typedCode .= 2;
								SetValue($this->GetIDForIdent("CODE"), $typedCode);
							}
						break;
	
						case 4:
							if($codeOK) {
								SetValue($securityEnterPasswordId, $securityPassword);
								SetValue($securityModus, 2);
								SetValue($this->GetIDForIdent("CODE"), 0);
								SetValue($this->GetIDForIdent("CODEOK"), false);
								$this->SetLED(10);
								$this->SetTimerInterval("FirstLEDoff", 1000);
								$this->SetTimerInterval("SecondLEDoff", 2000);
							} else{
								$typedCode .= 3;
								SetValue($this->GetIDForIdent("CODE"), $typedCode);
							}
						break;
	
						case 8:
							$typedCode .= 4;
							SetValue($this->GetIDForIdent("CODE"), $typedCode);
						break;
	
						case 16:
							$typedCode .= 5;
							SetValue($this->GetIDForIdent("CODE"), $typedCode);
						break;
	
						case 32:
							$typedCode .= 6;
							SetValue($this->GetIDForIdent("CODE"), $typedCode);
						break;
	
						case 64:
							if($typedCode == $securityPassword) {
								SetValue($this->GetIDForIdent("CODE"), 0);
								SetValue($this->GetIDForIdent("CODEOK"), true);
							} else {
								SetValue($this->GetIDForIdent("CODE"), 0);
								SetValue($this->GetIDForIdent("CODEOK"), false);
								$this->SetTimerInterval("ClearCodeTimer", 0);
							}
							
						break;
	
						case 128:
							SetValue($this->GetIDForIdent("CODE"), 0);
							SetValue($this->GetIDForIdent("CODEOK"), false);
							$this->SetTimerInterval("ClearCodeTimer", 0);
						break;
					}
				}
			}

			
		}
	}

	public function SetClearCodeTimer() {
		SetValue($this->GetIDForIdent("CODE"), 0);
		SetValue($this->GetIDForIdent("CODEOK"), false);
		$this->SetTimerInterval("ClearCodeTimer", 0);
	}

	public function TurnOffFirstLED() {
		$this->SetLED(0);
		$this->SetTimerInterval("FirstLEDoff", 0);
	}

	public function TurnOffSecondLED() {
		$this->SetLED(1);
		$this->SetTimerInterval("SecondLEDoff", 0);
	}

	public function TurnOffThirdLED() {
		$this->SetLED(2);
		$this->SetTimerInterval("ThirdLEDoff", 0);
	}


	/* 
		Um beim MaxFlex eine LED einzuschalten. Funktioniert nur wen der MaxFlex eine Stromspeisung beseitzt.
	*/
	public function SetLED(int $Value){
		$this->SendCommand( 1, 43, $Value, 3);
	}

	public function SendCommand(int $Instruction, int $Command, int $Value, int $Priority) {
		// CheckNr 2942145
		$id = $this->ReadPropertyInteger("ID");
		return $this->SendDataToParent(json_encode(Array("DataID" => "{C24CDA30-82EE-46E2-BAA0-13A088ACB5DB}", "Instruction" => $Instruction, "ID" => $id, "Command" => $Command, "Value" => $Value, "Priority" => $Priority)));
	}

}
?>