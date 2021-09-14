<?
//include_once __DIR__ . '/../libs/DominoSwissBase.php';

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
		$this->RegisterTimer("SelectModeTimer", 0, 'BRELAG_SelectMode($_IPS[\'TARGET\']);');

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

		if($id == $this->ReadPropertyInteger("ID")) {
			$timerintervalSecond = $this->ReadPropertyInteger("TimerInterval");
			$timerintervalMillisecond = $timerintervalSecond * 1000;
			$value = $data->Values->Value;
			if($value > 0) {
				$this->SetTimerInterval("ClearCodeTimer", $timerintervalMillisecond);
				$typedCode = GetValue($this->GetIDForIdent("CODE"));
				switch($value) {
					case 1:
						$typedCode .= 1;
						SetValue($this->GetIDForIdent("CODE"), $typedCode);
					break;

					case 2:
						$typedCode .= 2;
						SetValue($this->GetIDForIdent("CODE"), $typedCode);
					break;

					case 4:
						$typedCode .= 3;
						SetValue($this->GetIDForIdent("CODE"), $typedCode);
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
						$securityGUID = "{17433113-1A92-45B3-F250-B5E426040E64}";
						$securityInstance = IPS_GetInstanceListByModuleID($securityGUID);
						$securityInstanceId = $securityInstance[0];
						$securityPassword = IPS_GetProperty($securityInstanceId, "Password");
						$securityEnterPasswordId = IPS_GetObjectIDByIdent("Password", $securityInstanceId);
						$securityModus = IPS_GetObjectIDByIdent("Mode", $securityInstanceId);

						if($typedCode == $securityPassword) {
							SetValue($this->GetIDForIdent("CODEOK"), true);
							SetValue($this->GetIDForIdent("CODE"), 0);
							$mode = GetValue($this->GetIDForIdent("CODE"));
							$codeOK = GetValue($this->GetIDForIdent("CODEOK"));
							if($codeOK) {
								switch($mode) {
									case 1:
										SetValue($securityEnterPasswordId, $securityPassword);
										SetValue($securityModus, 0);
										SetValue($this->GetIDForIdent("CODE"), 0);
										SetValue($this->GetIDForIdent("CODEOK"), false);
									break;
	
									case 2:
										SetValue($securityEnterPasswordId, $securityPassword);
										SetValue($securityModus, 1);
										SetValue($this->GetIDForIdent("CODE"), 0);
										SetValue($this->GetIDForIdent("CODEOK"), false);
									break;
	
									case 3:
										SetValue($securityEnterPasswordId, $securityPassword);
										SetValue($securityModus, 3);
										SetValue($this->GetIDForIdent("CODE"), 0);
										SetValue($this->GetIDForIdent("CODEOK"), false);
									break;
								}
							}
							

							//$this->SetTimerInterval("SelectModeTimer", 5);
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

	/*
	public function SelectMode() {
		$this->SetTimerInterval("SelectModeTimer", 0);
		$securityGUID = "{17433113-1A92-45B3-F250-B5E426040E64}";
		$securityInstance = IPS_GetInstanceListByModuleID($securityGUID);
		$securityInstanceId = $securityInstance[0];
		$securityPassword = IPS_GetProperty($securityInstanceId, "Password");
		$securityEnterPasswordId = IPS_GetObjectIDByIdent("Password", $securityInstanceId);
		$securityModus = IPS_GetObjectIDByIdent("Mode", $securityInstanceId);
		$mode = GetValue($this->GetIDForIdent("CODE"));
		switch($mode) {
			case 1:
				SetValue($securityEnterPasswordId, $securityPassword);
				SetValue($securityModus, 0);
				SetValue($this->GetIDForIdent("CODE"), 0);
			break;

			case 2:
				SetValue($securityEnterPasswordId, $securityPassword);
				SetValue($securityModus, 1);
				SetValue($this->GetIDForIdent("CODE"), 0);
			break;

			case 3:
				SetValue($securityEnterPasswordId, $securityPassword);
				SetValue($securityModus, 3);
				SetValue($this->GetIDForIdent("CODE"), 0);
			break;
		}
	}
	*/

	public function SetClearCodeTimer() {
		SetValue($this->GetIDForIdent("CODE"), 0);
		SetValue($this->GetIDForIdent("CODEOK"), false);
		$this->SetTimerInterval("ClearCodeTimer", 0);
	}

	/* 
		Um beim MaxFlex eine LED einzuschalten. Funktioniert nur wen der MaxFlex eine Stromspeisung beseitzt.
	*/
	/*public function PulseUp(int $Priority){

		$this->SendCommand( 1, 1, 0  , $Priority);

	}

	public function SendCommand(int $Instruction, int $Command, int $Value, int $Priority) {
		$id = $this->ReadPropertyInteger("ID");
		return $this->SendDataToParent(json_encode(Array("DataID" => "{C24CDA30-82EE-46E2-BAA0-13A088ACB5DB}", "Instruction" => $Instruction, "ID" => $id, "Command" => $Command, "Value" => $Value, "Priority" => $Priority)));

	}*/

}
?>


