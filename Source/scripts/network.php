<?php
#############################################################################################################################################
# We are using the standard logger function and APS runtime Libraries
#############################################################################################################################################
require_once "logger.php";
require_once "aps/2/runtime.php";
require_once "oshandler.php";

#############################################################################################################################################
# We are setting up a network which represents a customer network in OpenStack.  
#############################################################################################################################################

/**
* Class network
* @type("http://hp.com/helionnetwork/1.0")
* @implements("http://aps-standard.org/types/core/resource/1.0")
* @implements("http://aps-standard.org/types/core/suspendable/1.0")
*/

class network extends APS\ResourceBase
{

	/**
	* @link("http://hp.com/project/3.0")
	* @required(true)
	*/
	public $project;

	/**
	 * @link("http://hp.com/helionsubnet/1.0[]")
	 */
	public $subnets;
	
	/**
	* @type(string)
	* @title("Network Name")
	* @required
	*/
	public $networkName;
	
	/**
	 * @type(boolean)
	 * @title("Admin State")
	 * 
	 */
	public $adminstate;
		
	/**
	 * @type(boolean)
	 * @title("Shared")
	 */
	public $shared;

	/**
	 * @type(boolean)
	 * @title("External Network")
	 */
	public $externalnetwork;
	
	
	
	################
	# links
	################

	public function projectLink(){}

	public function projectUnlink(){}
	
	public function subnetsLink(){}
	
	public function subnetsUnLink(){}
	
	#############################################################################################################################################
	#############################################################################################################################################
	
	public function provision(){

		$this->logger("provision call provisionOpenstackNetwork()...");
		$this->provisionOpenStackNetwork();
		$this->logger("Provision Network Complete :\n\t".$this->networkName);	
	}

	public function configure($new){

		$this->logger("Configure Network Called\n");
		$this->_copy($new);
	}

	public function unprovision(){
	
		$this->logger("unprovision call unprovisionOpenStackNetwork()");
		$this->unprovisionOpenStackNetwork();
		$this->logger("Unprovision Network Complete :\n\t".$this->networkName);	
	}

	/**
	* We define operation for enable
	* @verb(PUT)
	* @path("/enable")
	* @param()
	*/
	function enable(){

		$this->enableOpenStackNetwork();
		$this->logger("Enable Network Complete: ".$this->networkName);		
	}

	/**
	* We define operation for disable
	* @verb(PUT)
	* @path("/disable")
	* @param()
	*/
	function suspend(){

		$this->suspendOpenStackNetwork();
		$this->logger("Disable Network (Suspend) Complete: ".$this->networkName);		
	}

    #############################################################################################################################################
	#
    #############################################################################################################################################

	public function __construct(){
		# Construct a new resource counter for 
		$this->netusage=new \org\standard\aps\types\core\resource\Usage();
	}

	public function log($what){
		echo $what.": ".", netusage: ".$this->netusage->usage."\n";
	}
	
	public function count(){
	}

	public function retrieve(){
		$this->count();
		$this->log("retrieve");
	}

	#############################################################################################################################################
	## Support functions for this class
	#############################################################################################################################################
	private $isDebugEnabled;
	private $logger;
	private $provisionOpenStackNetwork;
	private $unprovisionOpenStackNetwork;
	private $enableOpenStackNetwork;
	private $suspendOpenStackNetwork;
	
	function isDebugEnabled(){
	}

	function logger($message){
        $requestor=$_SERVER['REMOTE_ADDR'];
        $log = new Logging();
        $log->lwrite($requestor.":".$message);
        $log->lclose();
    }
	
	function provisionOpenStackNetwork() {
		# Code to create OpenStack Network
		
		$this->logger("provisionOpenStackNetwork()::Start...\n");
		$ohandle = new oshandler();
		$values = '"networkName": "'.$this->networkName.'", 
					"projectName": "'.$this->project->projectName.'", 
					"adminstate": "'.(int)$this->adminstate.'", 
					"shared": "'.(int)$this->shared.'", 
					"externalnetwork": "'.(int)$this->externalnetwork.'"';
		$res = $ohandle->callOSAdmin($this->project->heliontenant->helionglobals, "createNetwork", $this->project->projectName, $values, "NeutronService");
		$json = json_decode($res);
		if($json->{'errorCode'} == "0"){
			return json_encode($json->{'result'});
		}else{
			return $json;
		}
	}
	
	function unprovisionOpenStackNetwork() {
		# Code to remove OpenStack Network
		$this->logger("unprovisionOpenStackNetwork()::Start...\n");
		$ohandle = new oshandler();
		$values = '"networkName": "'. $this->networkName.'"';
		$res = $ohandle->callOSAdmin($this->project->heliontenant->helionglobals, "deleteNetwork", $this->project->projectName, $values, "NeutronService");
		$json = json_decode($res);
		if($json->{'errorCode'} == "0"){
			return json_encode($json->{'result'});
		}else{
			return $json;
		}
	}
	
	function enableOpenStackNetwork() {
		# Code to enable OpenStack Network (Non-Active if possible)
		
		$this->logger("Enabling Openstack Network\n");
		$ohandle = new oshandler();
		$values = '"networkName": "'. $this->networkName.'"';
		$res = $ohandle->callOSAdmin($this->project->heliontenant->helionglobals, "enableNetwork", $this->project->projectName, $values, "NeutronService");
		$json = json_decode($res);
		if($json->{'errorCode'} == "0"){
			$this->adminState = false;
			$apsc = \APS\Request::getController();
			$apsc->updateResource($this);
			$this->logger("Network Enabled");
			return json_encode($json->{'result'});
		}else{
			$this->logger("Network ". $this->networkName. " was not enabled due to error");
			return $json;
		}
	}
	
	function suspendOpenStackNetwork() {
		# Code to suspend OpenStack Network (Active is possible))
		
		$this->logger("Disabling Openstack Network\n");
		$ohandle = new oshandler();
		$values = '"networkName": "'. $this->networkName.'"';
		$res = $ohandle->callOSAdmin($this->project->heliontenant->helionglobals, "disableNetwork", $this->project->projectName, $values, "NeutronService");
		$json = json_decode($res);
		if($json->{'errorCode'} == "0"){
			$this->adminState = false;
			$apsc = \APS\Request::getController();
			$apsc->updateResource($this);
			$this->logger("Network ". $this->networkName. " is Enabled");
			return json_encode($json->{'result'});
		}else{
			$this->logger("Network ". $this->networkName. " was not disabled due to error");
			return $json;
		}
	}
}
?>
