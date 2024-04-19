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
* @type("http://hp.com/helionsubnet/1.0")
* @implements("http://aps-standard.org/types/core/resource/1.0")
* @implements("http://aps-standard.org/types/core/suspendable/1.0")
*/

class subnet extends APS\ResourceBase
{

	/**
	* @link("http://hp.com/helionnetwork/1.0")
	* @required(true)
	*/
	public $network;

	/**
	 * @type(string)
	 * @title("Subnet Name")
	 */
	public $subnetName;
	
	/**
	 * @type(string)
	 * @title("Network Address")
	 */
	public $networkAddress;
		
	/**
	 * @type(string)
	 * @title("IP Version")
	 */
	public $ipVersion;

	/**
	 * @type(string)
	 * @title("Gateway Ip")
	 */
	public $gatewayIp;
	
	/**
	 * @type(boolean)
	 * @title("Disable Gateway")
	 */
	public $gatewayDisabled;
	
	/**
	 * @type(boolean)
	 * @title("Enable DHCP")
	 */
	public $dhcpEnabled;
	
	/**
	 * @type(string)
	 * @title("Allocation Pools")
	 */
	public $allocationPools;
	
	/**
	 * @type(string)
	 * @title("DNS Name Servers")
	 */
	public $dnsServers;
	
	/**
	 * @type(string)
	 * @title("Host Routes")
	 */
	public $hostRoutes;
	
	
	public function networkLink(){}

	public function networkUnlink(){}

	#############################################################################################################################################
	#############################################################################################################################################
	
	public function provision()	{

		$this->logger("provision call provisionSubnet()...");
		$this->provisionSubnet();
		$this->logger("Provision Subnet Complete");	
	}

	public function configure($new){

		$this->logger("Configure Subnet Called\n");
		$this->_copy($new);
	}

	public function unprovision(){
	
		$this->logger("unprovision():: call unprovisionSubnet()");
		$this->unprovisionSubnet();
		$this->logger("Unprovision Complete");	
	
	}

	/**
	* We define operation for enable
	* @verb(PUT)
	* @path("/enable")
	* @param()
	*/
	function enable(){

		$this->enableSubnet();
		$this->logger("Enable subnet Complete");	
	}

	/**
	* We define operation for disable
	* @verb(PUT)
	* @path("/disable")
	* @param()
	*/
	function suspend(){

		$this->suspendSubnet();
		$this->logger("Disable subnet (Suspend) Complete");	
	}

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
	private $provisionSubnet;
	private $unprovisionSubnet;
	private $enableSubnet;
	private $suspendSubnet;
	
	function isDebugEnabled(){
	}

	function logger($message){
        $requestor=$_SERVER['REMOTE_ADDR'];
        $log = new Logging();
        $log->lwrite($requestor.":".$message);
        $log->lclose();
    }
	
	function provisionSubnet() {

		# Code to create OpenStack Subnet
		$this->logger("provisionOpenStackSubnet()::Start...\n");
		$ohandle = new oshandler();
		$values = '"name": "'.$this->subnetName.'", 
					"cidr": "'.$this->networkAddress.'",
					"networkName": "'.$this->network->networkName.'", 
					"ip_version": "'.$this->ipVersion.'", 
					"gateway_ip": "'.$this->gatewayIp.'", 
					"enable_dhcp": "'.(int)$this->dhcpEnabled.'",
					"allocation_pools": "'.$this->allocationPools.'",
					"dns_nameservers": "'.$this->dnsServers.'",
					"host_routes": "'.$this->hostRoutes.'"';
		
		$res = $ohandle->callOSAdmin(	$this->network->project->heliontenant->helionglobals, 
										"createSubnet", 
										$this->network->project->projectName, 
										$values, "NeutronService");
		$json = json_decode($res);
		if($json->{'errorCode'} == "0"){
			return json_encode($json->{'result'});
		}else{
			$this->status = "Error";
			throw new \Rest\Exception(400, "Unable to process: " . $json->{'errorMsg'});
		}
	}
	
	function unprovisionSubnet() {
		# Code to remove OpenStack Subnet
		$this->logger("unprovisionOpenStackSubnet()::Start...");
		$ohandle = new oshandler();
		$values = '"networkName": "'. $this->subnetName.'"';
		
		$res = $ohandle->callOSAdmin(	$this->network->project->heliontenant->helionglobals,
										"deleteSubnet",
										$this->network->project->projectName,
										$values, "NeutronService");
		$json = json_decode($res);
		if($json->{'errorCode'} == "0"){
			return json_encode($json->{'result'});
		}else{
			$this->logger("Unable to unprovision os subnet due to error" . $json->{'errorMsg'});
		}
	}
	
	function enableSubnet() {
		return;
	}
	
	function suspendSubnet() {
		return;
	}
}
?>
