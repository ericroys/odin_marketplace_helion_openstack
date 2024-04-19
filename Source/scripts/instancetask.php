<?php
#############################################################################################################################################
# We are using the standard logger function and APS runtime Libraries
#############################################################################################################################################
require_once "logger.php";
require_once "aps/2/runtime.php";
require_once "oshandler.php";

#############################################################################################################################################
# We are setting up a objectstore which represents customer object storage objects in OpenStack.  
#############################################################################################################################################

/**
* Class instancetask
* @type("http://hp.com/instancetask/1.0")
* @implements("http://aps-standard.org/types/core/resource/1.0")

*/

class instancetask extends APS\ResourceBase
{
	#############################################################################################################################################
	# Link to project related to the object storage
	#############################################################################################################################################
	
	/**
	* @link("http://hp.com/helioninstance/1.1")
	* @required
	*/
	public $instance;

	/**
	* @type(string)
	* @title("task Name")
	* @description("Task Name")
	* @required
	*/
	public $taskname;

	/**
	 * @type(string)
	 * @title("Current State")
	 * @description("Current State")
	 * @required
	 */
	public $currentState;
	
	/**
	 * @type(string)
	 * @title("Instance State APS")
	 * @description("Instance State APS")
	 * @required
	 */
	public $expectedState;
	
	/**
	 * @type("integer")
	 * @title("Processed")
	 * @description("Flag for processing asynch")
	 */
	public $processed = 0;
	
	
	public function instanceLink(){}

	public function instanceUnlink(){}

	public function provision(){
		
		$this->logger("Regular provision():: Start");
		$this->logger("Provision- Name\n\t".$this->taskname." Current[".$this->currentState."] Expected[".$this->expectedState."]");
		
	//check back in 30 seconds for update
        throw new \Rest\Accepted($this, "Instance State Synch", 20);
	}

	public function provisionAsync() {
		
		$this->processed +=1;

		$this->logger("provisionAsynch()::Start [count:".$this->processed."]");
		
		//automatically wait as first asynch is immediate and not enough time for the 
		//states on OS to be set
		if($this->processed == 1){
			$this->logger("First Asynch ...sleep for 5...");
			throw new \Rest\Accepted($this, "Synch Instance State", 5);
		}
		
		//get the current state from OS and see if we need to wait longer for synch
		$osStatus = $this->getVMStatus();
		//$this->logger("XXXXXXXXXX: ". $osStatus);
		if($osStatus != $this->expectedState and $this->processed > 1){
			$this->instance->instancestatus = $osStatus;
			$this->logger("Still not ready. [count:".$this->processed."]...sleep for 5...");
			throw new \Rest\Accepted($this, "Synch Instance State", 5);
		}
		else{
			$this->logger("Completed synch state task");
			$apsc = \APS\Request::getController();

			$tmp = $apsc->getResource($this->instance->aps->id);

			$tmp->instancestatus = $osStatus;
			$apsc->updateResource($tmp);

			$this->unprovision();
		}
    }
		
	public function configure($new){}

	public function unprovision(){

		$this->logger("Unprovision Call()");
	}

	public function log($what){
		echo $what.": ".", netusage: ".$this->netusage->usage."\n";
	}
	
	public function count(){}

	public function retrieve(){

	}

	#############################################################################################################################################
	## Support functions for this class
	#############################################################################################################################################
	private $isDebugEnabled;
	private $logger;
	private $getVMStatus;
	
	function isDebugEnabled(){}

	function logger($message){
        $requestor=$_SERVER['REMOTE_ADDR'];
        $log = new Logging();
        $log->lwrite($requestor.":".$message);
        $log->lclose();
    }
    
    private function getVMStatus(){
    
    	$this->logger("getVMStatus()::Start...\n");
    	//create new Openstack handler object
    	$ohandle = new oshandler();
    	//setup for add ip to helion
    	$values = 	'"serverName": "'.$this->instance->instancename.'"';
    	//make the call to the handler to deal with call to helion
    	$this->logger("startVM(): prepared to execute call to python");
    	$res = $ohandle->callOSAdmin(	$this->instance->helionproject->heliontenant->helionglobals,
    									"getVMInformation",
    									$this->instance->helionproject->projectName,
    									$values,
    									"NovaService");
    
    	$json = json_decode($res);
    	if($json->{'errorCode'} == "0"){
    		$this->logger("POWERSTATE:: ".$json->{'result'}->powerState." STATUS:: ".$json->{'result'}->status." ADDRESSES:: ".json_encode($json->{'result'}->addresses));
    		///deal with the various states of the os instance including the task state which defined intermediate states
    		if(!is_null($json->{'result'}->taskState)){
    			return $json->{'result'}->taskState;
    		}
    		elseif($json->{'result'}->powerState == "1"){
    			return "Running";
    		}
    		elseif($json->{'result'}->status == "ERROR"){
    			return "Error";
    		}
    		elseif($json->{'result'}->powerState == "3"){
    			return "Paused";
    		}
    		elseif($json->{'result'}->powerState == "4"){
    			return "Shutdown";
    		}
    		elseif($json->{'result'}->status == "6"){
    			return "powerState";
    		}
    		elseif($json->{'result'}->status == "7"){
    			return "powerState";
    		}
    		return "";
    	}else{
    		$this->logger("getVMStatus failed. ". $json->{'errorMsg'});
    		return "";
    	}
    }
}
?>
