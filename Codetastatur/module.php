<?
//include_once __DIR__ . '/../libs/DominoSwissBase.php';

class MaxFlexCodepanel extends IPSModule {
	
	public function Create(){
		//Never delete this line!
		parent::Create();
		
		//These lines are parsed on Symcon Startup or Instance creation
		//You cannot use variables here. Just static values.
		
		$this->RegisterPropertyInteger("ID", 1);

		$this->RegisterVariableInteger("CODE", "Code", "", 1);

		$this->RegisterTimer("SetClearCodeTimer", 0, 'BRELAG_SetClearCodeTimer($_IPS[\'TARGET\']);');

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
			$value = $data->Values->Value;
			if($value > 0) {
				$typedCode = GetValue($this->GetIDForIdent("CODE"));
				$typedCode += $value;
				SetValue($this->GetIDForIdent("CODE"), $typedCode);
			}
		}
	}
	
	public function SetClearCodeTimer() {
		SetValue($this->GetIDForIdent("Status"), false);
		$this->SetTimerInterval("SetClearCodeTimer", 0);
	}

	
	
	public function SetRocker($Value) {

		$oldValue = GetValue($this->GetIDForIdent("RockerControl"));

		if ($Value > $oldValue) {
			for($i = 0; $i < ($Value - $oldValue); $i++) {
				$this->PulseUp(GetValue($this->GetIDForIdent("SendingOnLockLevel")));
			}
		}
		else {
			for($i = 0; $i < abs($oldValue - $Value); $i++) {
				$this->PulseDown(GetValue($this->GetIDForIdent("SendingOnLockLevel")));
			}
		}

		SetValue($this->GetIDForIdent("RockerControl"), $Value);
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