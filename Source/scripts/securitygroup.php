<?php
#############################################################################################################################################
# We are using the standard logger function and APS runtime Libraries
#############################################################################################################################################
require_once "logger.php";
require_once "aps/2/runtime.php";
require_once "oshandler.php";

#############################################################################################################################################
# We are setting up a security group.  
#############################################################################################################################################

/**
* Class security group
* @type("http://hp.com/helionsecuritygroup/1.0")
* @implements("http://aps-standard.org/types/core/resource/1.0")
*/

class securitygroup extends APS\ResourceBase
{

	#############################################################################################################################################
	# Link to tenant where the security policy is attached 
	#############################################################################################################################################
 
	/**
	* @link("http://hp.com/project/3.0")
	* @required(true)
	*/
	public $project;
	
	#############################################################################################################################################
	# Link to security group rules where the attached to this security group
	#############################################################################################################################################
	
	/**
	 * @link("http://hp.com/helionsecurityrule/1.0[]")
	 */
	public $groupRules;

	/**
	 * @type(string)
	 * @title("Policy Name")
	 */
	public $policyName;
	
	/**
	 * @type(string)
	 * @title("Description")
	 * 
	 */
	public $policyDescription;
	
	/**
	 * @type(string)
	 * @title("Openstack Id")
	 */
	public $openstackId;
	
	

	public function projectLink(){}
	public function projectUnlink(){}
	public function groupRulesLink(){}
	public function groupRulesUnlink(){}
	public function helionInstancesLink(){}
	public function helionInstancesUnLink(){}

	#############################################################################################################################################
	#############################################################################################################################################
	
	public function provision(){
		
		$this->logger("provision call provision group()...");
		$res = $this->provisionGroup();
		if(empty($this->openstackId)){
			$json = json_decode($res);
			throw new \Rest\Exception(400, "Unable to process: " . $json->{'errorMsg'});
		}else{
			return $res;
		}
		$this->logger("Provision group Complete");	
	}

	public function configure($new){

		$this->logger("Configure security group Called\n");
		$this->_copy($new);
	}

	public function unprovision(){
	
		$this->logger("unprovision():: call unprovisionGroup()");
		$this->unprovisionGroup();
		$this->logger("Unprovision Complete");	
	
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
	private $provisionGroup;
	private $unprovisionGroup;

	
	function isDebugEnabled(){
	}

	function logger($message){
        $requestor=$_SERVER['REMOTE_ADDR'];
        $log = new Logging();
        $log->lwrite($requestor.":".$message);
        $log->lclose();
    }
	
	function provisionGroup() {

		$this->logger("provisionGroup()::Start...\n");
		$ohandler = new oshandler();
		$values = '"name": "'.$this->policyName.'", 
							"description": "'.$this->policyDescription.'"';
		$res = $ohandler->callOSAdmin(	$this->project->heliontenant->helionglobals, 
								"createSecurityGroup", 
								$this->project->projectName, 
								$values, "NeutronService");
		$json = json_decode($res);
		if($json->{'errorCode'} == "0"){
			$this->openstackId = $json->{'result'}->id;
		}else{
			return $res;
		}
	}
	
	function unprovisionGroup() {
		# Code to remove OpenStack Subnet
		$this->logger("unprovisionGroup()::Start...\n");
		$ohandler = new oshandler();
		$values = '"groupId": "'.$this->openstackId.'"';
		$res = $ohandler->callOSAdmin(	$this->project->heliontenant->helionglobals,
				"deleteSecurityGroup",
				$this->project->projectName,
				$values, "NeutronService");
		return;
	}
}
?>
