<?php
#############################################################################################################################################
# We are using the standard logger function and APS runtime Libraries
#############################################################################################################################################
require_once "logger.php";
require_once "aps/2/runtime.php";
require_once "oshandler.php";

#############################################################################################################################################
# We are setting up a vm which represents customer vms (instances) in OpenStack.  
#############################################################################################################################################

/**
* Class vms
* @type("http://hp.com/helioninstance/1.1")
* @implements("http://aps-standard.org/types/core/resource/1.0")
* @implements("http://aps-standard.org/types/core/suspendable/1.0")
*/

class helioninstance extends APS\ResourceBase
{
	#############################################################################################################################################
	# Link to project related to the vm 
	#############################################################################################################################################
	/**
	* @link("http://hp.com/project/3.0")
	* @required
	*/
	public $helionproject;

	#############################################################################################################################################
	# Link to instance tasks
	#############################################################################################################################################
	/**
	 * @link("http://hp.com/instancetask/1.0")
	 */
	public $tasks;
	
	/**
	* @type(string)
	* @title("Availability Zone")
	* @description("Instance Availability Zone")
	*/
	public $zone;
	
	/**
	* @type(string)
	* @title("Instance Name")
	* @description("Instance Name")
	* @required
	*/
	public $instancename;
	
	/**
	* @type(string)
	* @title("Instance Flavor")
	* @description("Instance Flavor")
	* @required
	*/
	public $flavor;
	
	/**
	* @type(integer)
	* @title("Instance Count")
	* @description("Instance Count")
	* @required
	*/
	public $instancecount;
	
	/**
	 * @type(string)
	 * @title("Status")
	 * @description("Instance Status")
	 */
	public $instancestatus;  // this is the power state shown in os
	
	/**
	 * @type(string)
	 * @title("Instance Network")
	 * @description("Instance Network")
	 * @required
	 */
	public $network;
	
	/**
	* @type(string)
	* @title("Instance Size")
	* @description("Instance Size")
	*/
	public $size;
	
	/**
	* @type(string)
	* @title("Instance Image")
	* @description("Instance Image")
	*/
	public $image;
	
	/**
	 * @type(string)
	 * @title("RAM")
	 * @description("RAM")
	 */
	public $ram;
	
	/**
	 * @type(string)
	 * @title("Disk Space")
	 * @description("Disk Space")
	 */
	public $disk;
	
	/**
	 * @type(string)
	 * @title("CPU")
	 * @description("CPU")
	 */
	public $cpu;
	
	/**
	 * @type(string)
	 * @title("Addresses")
	 * @description("Network Addresses")
	 */
	//updated only from OS after instance is provisioned
	public $networkAddresses;  
	
	/**
	 * @type(string)
	 * @title("Policies")
	 * @description("Security Policies")
	 */
	//update for os only -> a csv list of policies to associate
	public $opolicies;  
	
	/**
	 * @type("integer")
	 * @title("Counter for async operation")
	 */
	public $retry = 5;
	
	public function helionprojectLink(){}
	public function helionprojectUnlink(){}
	public function tasksLink(){}
	public function tasksUnLink(){}
	public function policyGroupsLink(){}
	public function policyGroupsUnlink(){}
	

	/**
	 * Set values for allocated and return for collection
	 * @verb(GET)
	 * @path("/updateResourceUsage")
	 */
	public function updateResourceUsage(){}
	
	public function provision(){
		
		$this->logger("Regular provision():: Start");
		$this->instancestatus = "Provisioning";
		$this->state = "Creating";
		//$retry = 5;
		$this->logger("retry level is: ". $this->retry);
		$this->logger("Provision VM - Name\n\t".$this->instancename);
		$this->logger("Provision Call provisionOpenstackVM()...");
		$this->provisionOpenStackVM();
		$this->logger("Provision VM Complete :\n\t".$this->instancename);	

	//check back in 30 seconds for update
        throw new \Rest\Accepted($this, "Creating Instance", 30);
	}


	public function provisionAsync(){
		
		$this->retry -= 1;

		$this->logger("provisionAsynch()::Start");
		$this->logger("retry level is: ". $this->retry);
		if($this->instancestatus != "Error"){
			$osStatus = $this->getVMStatus();
			if($osStatus != "ready" and $this->retry > 0 and $osStatus != "error"){
				$this->logger("Still not ready...sleep for 30...");
				throw new \Rest\Accepted($this, "Still provisioning", 30);
			}
			elseif( $osStatus == "error" )		{
				$this->logger("There was an error while provisioning the instance!");
				$this->instancestatus = "Error";
				$this->state = "ready";
			}
			elseif( $osStatus == "ready"){ 
				$this->logger("Instance is READY!");
				$this->instancestatus = "Running";
				$this->state = "ready";
			}
		}
    }
	
	public function configure($new){

		$this->logger("Configure VM Called\n");
		$this->_copy($new);
	}

	public function unprovision(){

		$this->logger("Unprovision Call unprovisionOpenStackVM()");
		$this->unprovisionOpenStackVM();
		$this->logger("Unprovision VM Complete :\n\t".$this->instancename);	
	}

	#############################################################################################################################################
	#
	#############################################################################################################################################

	/**
	* We define operation for enable
	* @verb(PUT)
	* @path("/enable")
	* @param()
	*/
	function enable(){

		$this->enableOpenStackVM();
		$this->logger("Enable VM Complete :\n\t".$this->instancename);	
	}

	/**
	* We define operation for disable
	* @verb(PUT)
	* @path("/disable")
	* @param()
	*/
	function suspend(){

		$this->suspendOpenStackVM();
		$this->logger("Disable VM (Suspend) Complete :\n\t".$this->instancename);	
	}
	
	/**
	 * We define operation for stop of vm
	 * @verb(PUT)
	 * @path("/stop")
	 * @param()
	 */
	function stop(){
	
		$this->stopVM();
		$this->logger("Stop VM (Stop) Complete :\n\t".$this->instancename);
	}
	
	/**
	 * We define operation for start of vm
	 * @verb(PUT)
	 * @path("/start")
	 * @param()
	 */
	function start(){
	
		$this->startVM();
		$this->logger("Start VM (Start) Complete :\n\t".$this->instancename);
	}
	
	/**
	 * We define operation for getState of vm
	 * @verb(GET)
	 * @path("/getState")
	 * @param()
	 */
	function getState(){
	
		$this->logger("getState Complete :\n\t".$this->instancename);
		return $this->getVMState();
	}
	
	/**
	 * We define operation for add floating ip to vm
	 * @verb(PUT)
	 * @path("/addFloatIp")
	 * @param(string, body)
	 */
	function addFloatIp($ip){
	
		$this->addIp($ip);
		$this->logger("Add Ip Complete :\n\t".$this->instancename);
	}

	/**
	 * We define operation for restart of vm
	 * @verb(PUT)
	 * @path("/restart")
	 * @param()
	 */
	function restart(){
	
		if($this->instancestatus != "Running"){
			return "The instance is not in a state that allows for a restart. [".$this->instancestatus."]";
		}
		$this->restartVM();
		$this->logger("restart VM Complete :\n\t".$this->instancename);
	}
	
	/**
	 * We define operation for soft reset of vm
	 * @verb(PUT)
	 * @path("/softreset")
	 * @param()
	 */
	function softreset(){
	
		$this->softResetVM();
		$this->logger("Soft Reset VM (reset) Complete :\n\t".$this->instancename);
	}
	
	/**
	 * We define operation for hard reset of vm
	 * @verb(PUT)
	 * @path("/hardreset")
	 * @param()
	 */
	function hardreset(){
	
		$this->hardResetVM();
		$this->logger("Hard Reset VM (reset) Complete :\n\t".$this->instancename);
	}
	
	/**
	 * We define operation for stop of vm
	 * @verb(PUT)
	 * @path("/createSnapshot")
	 * @param(string, body)
	 */
	function createSnapshot($snapshotName){
	
		$this->createSnapshotVM($snapshotName);
		$this->logger("createSnapshot() complete :\n\t".$this->instancename);
	}
	
    #############################################################################################################################################
    #############################################################################################################################################

	public function __construct(){
		# Construct a new resource counter for 
		$this->netusage=new \org\standard\aps\types\core\resource\Usage();
	}

	public function log($what){
		echo $what.": ".", netusage: ".$this->netusage->usage."\n";
	}
	
	public function count(){}
	public function retrieve(){}

	#############################################################################################################################################
	## Support functions for this class
	#############################################################################################################################################
	private $isDebugEnabled;
	private $logger;
	private $provisionOpenStackVM;
	private $unprovisionOpenStackVM;
	private $enableOpenStackVM;
	private $suspendOpenStackVM;
	private $hardResetVM;
	private $softResetVM;
	private $stopVM;
	private $startVM;
	private $restartVM;
	private $getVMStatus;
	private $applyFlavor;
	private $createSnapshotVM;
	private $addIp;
	private $getVMState;
	
	function isDebugEnabled(){}

	private function addIp($address){
	
		$this->logger("addIp()::Start...\n");
		//create new Openstack handler object
		$ohandle = new oshandler();
		//setup for add ip to helion
		$values = 	'"serverName": "'.$this->instancename.'",
					"ipAddress": "'.$address.'"';
		//make the call to the handler to deal with call to helion
		$this->logger("addIp(): prepared to execute call to python");
		$ohandle->callOSAdmin(	$this->helionproject->heliontenant->helionglobals, 
								"addFloatIp", 
								$this->helionproject->projectName, 
								$values, 
								"NovaService");
		$this->logger("addIp(): end");
		
	}
	
	private function stopVM(){
		
		$this->logger("stopVM()::Start...\n");
		//create new Openstack handler object
		$ohandle = new oshandler();
		//setup for add ip to helion
		$values = 	'"serverName": "'.$this->instancename.'"';
		//make the call to the handler to deal with call to helion
		$this->logger("stopVM(): prepared to execute call to python");
		$ohandle->callOSAdmin(	$this->helionproject->heliontenant->helionglobals,
								"turnOffVM",
								$this->helionproject->projectName,
								$values,
								"NovaService");
		
		$this->logger("stopVM():: end");
	}
	
	private function startVM(){

		$this->logger("startVM()::Start...\n");
		//create new Openstack handler object
		$ohandle = new oshandler();
		//setup for add ip to helion
		$values = 	'"serverName": "'.$this->instancename.'"';
		//make the call to the handler to deal with call to helion
		$this->logger("startVM(): prepared to execute call to python");
		$ohandle->callOSAdmin(	$this->helionproject->heliontenant->helionglobals,
								"turnOnVM",
								$this->helionproject->projectName,
								$values,
								"NovaService");
		
		$this->logger("startVM():: executed call to python");
	}
	
	private function restartVM(){
		
		$this->instancestatus = "Restarting";
		$this->logger("restartVM()::Start...\n");
		//create new Openstack handler object
		$ohandle = new oshandler();
		//setup for add ip to helion
		$values = 	'"serverName": "'.$this->instancename.'"';
		//make the call to the handler to deal with call to helion
		$this->logger("restartVM(): prepared to execute call to python");
		$ohandle->callOSAdmin(	$this->helionproject->heliontenant->helionglobals,
								"restartVM",
								$this->helionproject->projectName,
								$values,
								"NovaService");
		
		$this->logger("restartVM():: executed call to python");
	}
	
	private function softResetVM(){
		
		$this->logger("softReset()::Start...\n");
		//create new Openstack handler object
		$ohandle = new oshandler();
		//setup for add ip to helion
		$values = 	'"serverName": "'.$this->instancename.'",
					"rebootType": "SOFT"';
		//make the call to the handler to deal with call to helion
		$this->logger("softReset(): prepared to execute call to python");
		$ohandle->callOSAdmin(	$this->helionproject->heliontenant->helionglobals,
								"rebootVM",
								$this->helionproject->projectName,
								$values,
								"NovaService");
		
		$this->logger("softReset():: executed call to python");
	}
	
	private function createSnapshotVM($name){
	
		$this->logger("createSnapshotVM::Start...\n");
		//create new Openstack handler object
		$ohandle = new oshandler();
		//setup for add ip to helion
		$values = 	'"serverName": "'.$this->instancename.'",
					"snapshotName": "'.$name.'"';
		//make the call to the handler to deal with call to helion
		$this->logger("createSnapshotVM: prepared to execute call to python");
		$ohandle->callOSAdmin(	$this->helionproject->heliontenant->helionglobals,
								"createSnapShot",
								$this->helionproject->projectName,
								$values,
								"NovaService");

		$this->logger("createSnapshotVM:: executed call to python");
	}
	
	private function hardResetVM(){

		$this->logger("Hard Reset()::Start...\n");
		//create new Openstack handler object
		$ohandle = new oshandler();
		//setup for add ip to helion
		$values = 	'"serverName": "'.$this->instancename.'",
					"rebootType": "HARD"';
		//make the call to the handler to deal with call to helion
		$this->logger("Hard Reset(): prepared to execute call to python");
		$ohandle->callOSAdmin(	$this->helionproject->heliontenant->helionglobals,
								"rebootVM",
								$this->helionproject->projectName,
								$values,
								"NovaService");

		$this->logger("Hard Reset():: executed call to python");
	}
	
	function logger($message){
        $requestor=$_SERVER['REMOTE_ADDR'];
        $log = new Logging();
        $log->lwrite($requestor.":".$message);
        $log->lclose();
    }
    
	//get the status of the vm from openstack
	private function getVMStatus(){

		$this->logger("getVMStatus()::Start...\n");
		
		//create new Openstack handler object
		$ohandle = new oshandler();
		//setup for add ip to helion
		$values = 	'"serverName": "'.$this->instancename.'"';
		//make the call to the handler to deal with call to helion
		$this->logger("getVMStatus(): prepared to execute call to python");
		$res = $ohandle->callOSAdmin(	$this->helionproject->heliontenant->helionglobals,
										"getVMInformation",
										$this->helionproject->projectName,
										$values,
										"NovaService");

		$this->logger("getVMStatus():: executed call to python");
		$json = json_decode($res);
		if($json->{'errorCode'} == "0"){
			$this->logger("POWERSTATE:: ".$json->{'result'}->powerState." STATUS:: ".$json->{'result'}->status." ADDRESSES:: ".json_encode($json->{'result'}->addresses));
			
			//update the network stuff while where are at it
			$_tmpA = json_encode($json->{'result'}->addresses);
			if($_tmpA != $this->networkAddresses){
				$this->networkAddresses = $_tmpA;
			}
			
			///deal with the various states of the os instance
			if($json->{'result'}->powerState == "1"){
				return "ready";
			}
			elseif($json->{'result'}->status == "ERROR"){
				return "error";
			}
			elseif($json->{'result'}->powerState == "3"){
				return "paused";
			}
			elseif($json->{'result'}->powerState == "4"){
				return "shutdown";
			}
			elseif($json->{'result'}->status == "6"){
				return "powerState";
			}
			elseif($json->{'result'}->status == "7"){
				return "powerState";
			}
			return "notReady";
		}else{
			return "";
		}
	}
	
	function provisionOpenStackVM() {
		
		# Code to create OpenStack VM
		$this->logger("provisionOpenStackVM()::Start...\n");
		//create new Openstack handler object
		$ohandle = new oshandler();
		
		$values = 	'"networks": "'.$this->network.'", 
					"imageName": "'.$this->image.'", 
					"flavorName": "'.$this->flavor.'", 
					"name": "'.$this->instancename.'",
					"security_groups":"'. $this->opolicies.'"';
		//make the call to the handler to deal with call to helion
		$this->logger("provisionOpenStackVM(): prepared to execute call to python");
		$res = $ohandle->callOSAdmin(	$this->helionproject->heliontenant->helionglobals,
										"createVM",
										$this->helionproject->projectName,
										$values,
										"NovaService");
		$json = json_decode($res);
		if($json->{'errorCode'} == "0"){
			$this->applyFlavor();
		}else{
			$this->instancestatus = "Error";
			$this->logger("Error provisioning instance. " . $json->{'errorMsg'});
		}
		return;
	}
	
	function unprovisionOpenStackVM() {
		# Code to remove OpenStack Network
		$this->logger("unprovisionOpenStackVM()::Start...\n");
		//create new Openstack handler object
		$ohandle = new oshandler();
		//setup for add ip to helion
		$values = 	'"serverName": "'.$this->instancename.'"';
		//make the call to the handler to deal with call to helion
		$this->logger("unprovisionOpenStackVM(): prepared to execute call to python");
		$res = $ohandle->callOSAdmin(	$this->helionproject->heliontenant->helionglobals,
										"deleteVM",
										$this->helionproject->projectName,
										$values,
										"NovaService");
		
		$this->logger("unprovisionOpenStackVM()::End\n");
		return;
	}
	
	function enableOpenStackVM() {
		# Code to enable OpenStack VM (Non-Active if possible)
		$this->logger("Enabling Openstack VM\n");
		//create new Openstack handler object
		$ohandle = new oshandler();
		//setup for add ip to helion
		$values = 	'"serverName": "'.$this->instancename.'"';
		//make the call to the handler to deal with call to helion
		$this->logger("enableOpenStackVM(): prepared to execute call to python");
		$res = $ohandle->callOSAdmin(	$this->helionproject->heliontenant->helionglobals,
										"enableVM",
										$this->helionproject->projectName,
										$values,
										"NovaService");

		return "Success";
	}
	
	function suspendOpenStackVM() {
		# Code to suspend OpenStack VM  (Active is possible))
		$this->logger("Suspending Openstack VM\n");
		//create new Openstack handler object
		$ohandle = new oshandler();
		//setup for add ip to helion
		$values = 	'"serverName": "'.$this->instancename.'"';
		//make the call to the handler to deal with call to helion
		$this->logger("suspendOpenStackVM(): prepared to execute call to python");
		$res = $ohandle->callOSAdmin(	$this->helionproject->heliontenant->helionglobals,
										"suspendVM",
										$this->helionproject->projectName,
										$values,
										"NovaService");
		
		return "Success";
	}
	
	private function getVMState(){
	
		$this->logger("getVMState()::Start...\n");
		//create new Openstack handler object
		$ohandle = new oshandler();
		//setup for add ip to helion
		$values = 	'"serverName": "'.$this->instancename.'"';
		//make the call to the handler to deal with call to helion
		$this->logger("getVMState(): prepared to execute call to python");
		$res = $ohandle->callOSAdmin(	$this->helionproject->heliontenant->helionglobals,
										"getVMInformation",
										$this->helionproject->projectName,
										$values,
										"NovaService");
	
		$this->logger("getVMState():: executed call to python");
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
				return "unknown";
			}
			elseif($json->{'result'}->status == "7"){
				return "unknown";
			}
			return "";
		
		}else{
			throw new \Rest\RestException(400, "Unable to get instance state from Helion. " .$json->{'errorMsg'});
		}
	}
	
	private function applyFlavor(){
		$this->logger("getFlavor()::Start...\n");
		//create new Openstack handler object
		$ohandle = new oshandler();
		//setup for add ip to helion
		$values = 	'"flavorName": "'.$this->flavor.'"';
		//make the call to the handler to deal with call to helion
		$this->logger("suspendOpenStackVM(): prepared to execute call to python");
		$res = $ohandle->callOSAdmin(	$this->helionproject->heliontenant->helionglobals,
				"getFlavor",
				$this->helionproject->projectName,
				$values,
				"NovaService");
	
		$json = json_decode($res);
		if($json->{'errorCode'} == "0"){
			$this->cpu = $json->{'result'}->cpu;
			$this->ram = $json->{'result'}->ram;
			$this->disk = $json->{'result'}->disk;
	
		}else{
			return;
		}
	}
}
?>
