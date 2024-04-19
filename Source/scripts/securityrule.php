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
* Class security group rule
* @type("http://hp.com/helionsecurityrule/1.0")
* @implements("http://aps-standard.org/types/core/resource/1.0")
*/

class securityrule extends APS\ResourceBase
{

	/**
	* @link("http://hp.com/helionsecuritygroup/1.0")
	* @required(true)
	*/
	public $securityGroup;
	
	/**
	 * @type(string)
	 * @title("Ip Protocol")
	 */
	public $ipProtocol;
	
	/**
	 * @type(integer)
	 * @title("From Port")
	 */
	public $fromPort;

	/**
	 * @type(integer)
	 * @title("To Port")
	 */
	public $toPort;
	
	/**
	 * @type(string)
	 * @title("Destination IPs (in cidr notation)")
	 */
	public $destinationIps;
	
	/**
	 * @type(string)
	 * @title("Openstack Id")
	 */
	public $openstackId;
	
	/**
	 * @type(string)
	 * @title("Direction")
	 */
	public $direction;
	
	/**
	 * @type(string)
	 * @title("Ethernet Type")
	 */
	public $etherType;
	
	

	public function securityGroupLink(){}
	public function securityGroupUnlink(){}
	
	#############################################################################################################################################
	#############################################################################################################################################
	
	public function provision(){

		$this->logger("provision call provision rule()...");
		$this->provisionRule();
		if(empty($this->openstackId)){
			$this->logger("Couldn't get the Rule Id during creation");
			throw new \Rest\Exception(400, "Creating Security Rule", "Unable to process the request via HP Helion due to error");
		}
		$this->logger("Provision rule Complete");	
	}

	public function configure($new){

		$this->logger("Configure security rule Called\n");
		$this->_copy($new);
	}

	public function unprovision(){
	
		$this->logger("unprovision():: call unprovisionRule()");
		$this->unprovisionRule();
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
	private $provisionRule;
	private $unprovisionRule;

	
	function isDebugEnabled(){
	}

	function logger($message){
        $requestor=$_SERVER['REMOTE_ADDR'];
        $log = new Logging();
        $log->lwrite($requestor.":".$message);
        $log->lclose();
    }
	
	function provisionRule() {

		$this->logger("provisionRule()::Start...\n");
		$ohandler = new oshandler();
		$values = '"protocol": "'.$this->ipProtocol.'", 
							"direction": "' .$this->direction. '",
							"ether_type": "' . $this->etherType. '",
							"port_range_min": "'.$this->fromPort.'",
							"port_range_max": "'.$this->toPort.'",
							"security_group_id":"'.$this->securityGroup->openstackId.'"';
		
		$res = $ohandler->callOSAdmin(	$this->securityGroup->project->heliontenant->helionglobals,
				"createSecurityGroupRule",
				$this->securityGroup->project->projectName,
				$values, "NeutronService");
		
		$json = json_decode($res);
		if($json->{'errorCode'} == "0"){
			$this->openstackId = $json->{'result'}->id;
		}else{
			$this->status = "Error";
			return $res;
		}
	}
	
	function unprovisionRule() {

		$this->logger("unprovisionRule()::Start...\n");
		
		$ohandler = new oshandler();
		$values = '"ruleId": "'.$this->openstackId.'"';
		$res = $ohandler->callOSAdmin(	$this->securityGroup->project->heliontenant->helionglobals,
				"deleteSecurityRule",
				$this->securityGroup->project->projectName,
				$values, "NeutronService");
		
		return;
	}
}
?>
