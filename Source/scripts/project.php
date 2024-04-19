<?php
#############################################################################################################################################
# We are using the standard logger function and APS runtime Libraries
#############################################################################################################################################
require_once "logger.php";
require_once "aps/2/runtime.php";
require_once "oshandler.php";

#############################################################################################################################################
# We are setting up a Quota object for the project.
#############################################################################################################################################
class OSQuota{
	
	/**
	 * @type("integer")
	 * @title("Cores")
	 * @description("Number of CPU cores")
	 */
	public $cores;
	
	/**
	 * @type("integer")
	 * @title("Number of Fixed IPs")
	 * @description("Number of Fixed IPs")
	 */
	public $fixed_ips;
	
	/**
	 * @type("integer")
	 * @title("Number of Floating IPs")
	 * @description("Number of Floating IPs")
	 */
	public $floating_ips;
	
	/**
	 * @type("integer")
	 * @title("File Content Bytes")
	 * @description("File Content Bytes")
	 */
	public $injected_file_content_bytes;
	
	/**
	 * @type("integer")
	 * @title("File Path Bytes")
	 * @description("File Path Bytes")
	 */
	public $injected_file_path_bytes;
	
	/**
	 * @type("integer")
	 * @title("Injected Files")
	 * @description("Injected Files")
	 */
	public $injected_files;
	
	/**
	 * @type("integer")
	 * @title("Instances")
	 * @description("Instances")
	 */
	public $instances;
	
	/**
	 * @type("integer")
	 * @title("Key Pairs")
	 * @description("Key Pairs")
	 */
	public $key_pairs;
	
	/**
	 * @type("integer")
	 * @title("Metadata Items")
	 * @description("Metadata Items")
	 */
	public $metadata_items;
	
	/**
	 * @type("integer")
	 * @title("Ram")
	 * @description("Ram")
	 */
	public $ram;
	
	/**
	 * @type("integer")
	 * @title("Security Group Rules")
	 * @description("Security Group Rules")
	 */
	public $security_group_rules;
	
	/**
	 * @type("integer")
	 * @title("Security Groups")
	 * @description("Security Groups")
	 */
	public $security_groups;
	
	/**
	 * @type("integer")
	 * @title("Networks")
	 * @description("Networks")
	 */
	public $networks;

	/**
	 * @type("integer")
	 * @title("Ports")
	 * @description("Ports")
	 */
	public $ports;

	/**
	 * @type("integer")
	 * @title("Subnets")
	 * @description("Subnets")
	 */
	public $subnets;

	/**
	 * @type("integer")
	 * @title("Routers")
	 * @description("Routers")
	 */
	public $routers;
	
	/**
	 * @type("integer")
	 * @title("Server Group Members")
	 * @description("Server Group Members")
	 */
	public $server_group_members;
	
	/**
	 * @type("integer")
	 * @title("Server Groups")
	 * @description("Server Groups")
	 */
	public $server_groups;
	
	function __construct(){
		$this->subnets = 						0;
		$this->metadata_items = 				0;
		$this->cores = 							0;
		$this->networks = 						0;
		$this->ports = 							0;
		$this->routers = 						0;
		$this->floating_ips = 					0;
		$this->fixed_ips = 						0;
		$this->security_group_rules = 			0;
		$this->security_groups = 				0;
		$this->ram = 							0;
		$this->injected_files = 				0;
		$this->instances = 						0;
		$this->key_pairs = 						0;
		$this->server_group_members = 			0;
		$this->server_groups = 					0;
		$this->injected_file_content_bytes =	0;
		$this->injected_file_path_bytes =		0;
	}
}
#############################################################################################################################################
# We are setting up a project which represents a customer project in OpenStack.  
#############################################################################################################################################

/**
* Class project
* @type("http://hp.com/project/3.0")
* @implements("http://aps-standard.org/types/core/resource/1.0")
* @implements("http://aps-standard.org/types/core/suspendable/1.0")
*/

class project extends APS\ResourceBase
{
	#############################################################################################################################################
	#  Link to helionglobals object type.   This gets the Primary Instance Description, LDAP Info / Credentials, and target Helion Server for Tenant
	#############################################################################################################################################

	/**
	* @link("http://hp.com/heliontenant/3.0")
	* @required
	*/
	public $heliontenant;

	#############################################################################################################################################
	# Link to users depending on this Project. 
	#############################################################################################################################################

	/**
	* @link("http://hp.com/users/1.0[]")
	*/
    public $projectusers;
    
    #############################################################################################################################################
    # Link to virtual machines depending on this Project.
    #############################################################################################################################################     
    
	/**
     * @link("http://hp.com/helioninstance/1.1[]")
     */
	 public $projectinstances;
    
    #############################################################################################################################################
    # Link to Object Storage Containers depending on this Project.
    #############################################################################################################################################     
	
	/**
     * @link("http://hp.com/helionobjectstorecontainer/1.0[]")
     */
    public $objectstorecontainers;

    #############################################################################################################################################
    # Link to Policies.
    #############################################################################################################################################
    
    /**
     * @link("http://hp.com/helionsecuritygroup/1.0[]")
     */
    public $policies;

    #############################################################################################################################################
    # Link to Networks.
    #############################################################################################################################################
    
    /**
     * @link("http://hp.com/helionnetwork/1.0[]")
     */
    public $networks;
    
    #############################################################################################################################################
    # Link to Flavors.
    #############################################################################################################################################
    
    /**
     * @link("http://hp.com/flavor/1.0[]")
     */
    public $flavors;
    
	###############################
	#  General Class Attributes
	###############################
	/**
	* @type(string)
	* @title("Project Name")
	* @required
	*/
	public $projectName;
	
	/**
	 * @type(string)
	 * @title("Description")
	 */
	public $description;

	/**
	 * @type("OSQuota")
	 * @title("OSQuota")
	 * @description("Project Quota")
	 */
	public $osquota;
	
	/**
	 * @type(string)
	 * @title("Status")
	 * @Description("Project Status")
	 */
	public $status;
	
	/**
	 * @type(string)
	 * @title("Openstack Id")
	 */
	public $openstackId;
	
	/**
	* @type("http://aps-standard.org/types/core/resource/1.0#Usage")
	* @description("Network Usage Only")
	*/
	//not used as it is collected at tenant level
	public $netusage;

	/**
	* @type(number)
	* @description("Flag for PBA Billing Verification.  If PBA Billing in Place we set to true, otherwise remains false")
	**/
	public $billing=0;
	
	/**added by Yixin Geng
	 * @type("OSQuota")
	 * @title("allProjsQuota")
	 * @description("All the Projects Quota")
	 */
	public $allProjsQuota;
	
	/**
	 * @verb(GET)
	 * @path("/getBalance")
	 * @param()
	 * @access(referrer, true)
	 */
	##############################
	#get current resource balance#
	##############################
	public function getBalance(){
		$this->logger("enter getBalance()");

		$this->getTotalProjectsQuota();
		$balance = array();
		
		if(is_null($this->heliontenant->subquota)){
			$this->logger("subquota for tenant not set!!!!");
			$this->heliontenant->setInitLimits();
			$this->logger("Set tenant resource limits");
		}
	
		$balance["subnets"] = 						$this->heliontenant->subquota->subnets - $this->allProjsQuota->subnets;
		if ($balance["subnets"] < 0){
			$balance["subnets"] = "Unlimited";
		}
		
		$balance["metadata_items"] = 				$this->heliontenant->subquota->metadata_items - $this->allProjsQuota->metadata_items;
		if ($balance["metadata_items"] < 0){
			$balance["metadata_items"] = "Unlimited";
		}
		
		$balance["cores"] = 						$this->heliontenant->subquota->cores - $this->allProjsQuota->cores;
		if ($balance["cores"] < 0){
			$balance["cores"] = "Unlimited";
		}
		
		$balance["networks"] = 						$this->heliontenant->subquota->networks - $this->allProjsQuota->networks;
		if ($balance["networks"] < 0){
			$balance["networks"] = "Unlimited";
		}
		
		$balance["ports"] = 						$this->heliontenant->subquota->ports - $this->allProjsQuota->ports;
		if ($balance["ports"] < 0){
			$balance["ports"] = "Unlimited";
		}
		
		$balance["routers"] = 						$this->heliontenant->subquota->routers - $this->allProjsQuota->routers;
		if ($balance["routers"] < 0){
			$balance["routers"] = "Unlimited";
		}
		
		$balance["floating_ips"] = 					$this->heliontenant->subquota->floating_ips - $this->allProjsQuota->floating_ips;
		if ($balance["floating_ips"] < 0){
			$balance["floating_ips"] = "Unlimited";
		}
		
		$balance["fixed_ips"] = 					$this->heliontenant->subquota->fixed_ips - $this->allProjsQuota->fixed_ips;
		if ($balance["fixed_ips"] < 0){
			$balance["fixed_ips"] = "Unlimited";
		}
		
		$balance["security_group_rules"] = 			$this->heliontenant->subquota->security_group_rules - $this->allProjsQuota->security_group_rules;
		if ($balance["security_group_rules"] < 0){
			$balance["security_group_rules"] = "Unlimited";
		}
		
		$balance["security_groups"] = 				$this->heliontenant->subquota->security_groups - $this->allProjsQuota->security_groups;
		if ($balance["security_groups"] < 0){
			$balance["security_groups"] = "Unlimited";
		}
		
		$balance["ram"] = 							$this->heliontenant->subquota->ram - $this->allProjsQuota->ram;
		if ($balance["ram"] < 0){
			$balance["ram"] = "Unlimited";
		}
		
		$balance["injected_files"] = 				$this->heliontenant->subquota->injected_files - $this->allProjsQuota->injected_files;
		if ($balance["injected_files"] < 0){
			$balance["injected_files"] = "Unlimited";
		}
		
		$balance["instances"] = 					$this->heliontenant->subquota->instances - $this->allProjsQuota->instances;
		if ($balance["instances"] < 0){
			$balance["instances"] = "Unlimited";
		}
		
		$balance["key_pairs"] = 					$this->heliontenant->subquota->key_pairs - $this->allProjsQuota->key_pairs;
		if ($balance["key_pairs"] < 0){
			$balance["key_pairs"] = "Unlimited";
		}
		
		$balance["server_group_members"] = 			$this->heliontenant->subquota->server_group_members - $this->allProjsQuota->server_group_members;
		if ($balance["server_group_members"] < 0){
			$balance["server_group_members"] = "Unlimited";
		}
		
		$balance["server_groups"] = 				$this->heliontenant->subquota->server_groups - $this->allProjsQuota->server_groups;
		if ($balance["server_groups"] < 0){
			$balance["server_groups"] = "Unlimited";
		}
		
		$balance["injected_file_content_bytes"] = 	$this->heliontenant->subquota->injected_file_content_bytes - $this->allProjsQuota->injected_file_content_bytes;
		if ($balance["injected_file_content_bytes"] < 0){
			$balance["injected_file_content_bytes"] = "Unlimited";
		}
		
		$balance["injected_file_path_bytes"] = 		$this->heliontenant->subquota->injected_file_path_bytes - $this->allProjsQuota->injected_file_path_bytes;
		if ($balance["injected_file_path_bytes"] < 0){
			$balance["injected_file_path_bytes"] = "Unlimited";
		}
		
		$this->logger("leave getBalance()");
		
		return (string)json_encode($balance);
	}
	

	public function heliontenantLink(){}
	public function heliongtenantUnlink(){}
	public function projectusersLink(){}
	public function projectusersUnlink(){}
	public function projectinstancesLink(){}
	public function projectinstancesUnLink(){}
	public function networksLink(){}
	public function networksUnLink(){}
	public function flavorsLink(){}
	public function flavorsUnLink(){}
	

	#############################################################################################################################################
	# CRUD Operations on APS Type
	#		* configure - Modify Existing Instance
	#		* provision - New object created
	#		* unprovision - Delete Object
	#		* retrieve - Read Object
	#############################################################################################################################################
	
	public function provision(){
		
		$this->logger("provision call provisionOpenstackProject()...");
		$this->provisionOpenStackProject();
		$this->logger("ended os provision, start getquota");
		if($this->status != "Error"){
			$this->logger("status not in error so running getOSQuota()");
			$this->logger("send update to OS project with all zero values");
			$this->setInitQuota(); 
			$this->getOSQuota();
			$this->logger("ended getOSQuota()");
		}
		$this->logger("Provision Complete :".$this->projectName);	
	}

	public function configure($new){

		$this->_copy($new);
	}

	public function unprovision(){
	
		$this->logger("unprovision call unprovisionOpenStackProject()");
		$this->unprovisionOpenStackProject();
		$this->logger("UnProvision Complete :".$this->projectName);	
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

		$this->logger("Enable Start :".$this->projectName);	
		
		//check it is in a state to allow state change
		if ($this->status == "Provisioning"){
			return ("The project has not been provisioned yet");
		}
		
		//enable to project
		$res_helion = $this->enableProject();
		
		if($res_helion->{'errorCode'} == "0"){
			$this->status = "Ready";
			$apsc = \APS\Request::getController();
			$apsc->updateResource($this);
			return("Project is Active");
		}
		else{
			return("Project ".$this->projectName." was unable to be activated. [".$res_helion{'errorMsg'}."]");
		}
	}

	/**
	* We define operation for disable
	* @verb(PUT)
	* @path("/disable")
	* @param()
	*/
	function disable(){

		$this->logger("Disable (Suspend) Start : ".$this->projectName);		
		//check it is in a state to allow state change
		if ($this->status == "Provisioning"){
			return ("The project has not been provisioned yet");
		}
		if($this->status == "Suspended"){
			$this->logger("Project [".$this->projectName."] already suspended");
			return;
		}		
		//disable to project
		$res_helion = $this->disableProject();
		
		if($res_helion->{'errorCode'} == "0"){
			$this->logger("Suspended!");
			$this->status = "Suspended";
			$apsc = \APS\Request::getController();
			$apsc->updateResource($this);
			$this->logger("Project [".$this->projectName."] is suspended");
			return("Project is Suspended");
		}
		else{
			$this->logger("Unable to suspend project.");
			return("Project ".$this->projectName." was unable to be suspended. [".$res_helion{'errorMsg'}."]");
		}
	}

	public function __construct(){}
	public function log($what){}
	public function count(){}
	public function retrieve(){}

	#############################################################################################################################################
	## Support functions for this class
	#############################################################################################################################################
	private $isDebugEnabled;
	private $logger;
	private $provisionOpenStackProject;
	private $unprovisionOpenStackProject;
	private $enableProject;
	private $disableProject;

	
	function isDebugEnabled(){}

	function logger($message){
        $requestor=$_SERVER['REMOTE_ADDR'];
        $log = new Logging();
        $log->lwrite($requestor.":".$message);
        $log->lclose();
    }

	/**
	 * Set values for allocated and return for collection
	 * @verb(POST)
	 * @param(string, body)
	 * @path("/updateResourceUsage")
	 */
	public function updateResourceUsage($param) {
		
		$this->logger("Started projectResourceUsage()");
		$json = json_decode($param);
		$usage = array();
	
		$usage['usedCPU'] 			= 0;
		$usage['usedRAM'] 			= 0;
		$usage['usedDISK'] 			= 0;
		$usage['usedInNetBytes'] 	= 0;
		$usage['usedOutNetBytes']	= 0;
		$usage['allocatedDISK']		= 0;
		$usage['allocatedRAM']		= 0;
		$usage['allocatedCPU']		= 0;
		$usage['usedObjectBytes']	= 0;
		$usage['allocatedCont']		= 0;
		$usage['allocatedObj']		= 0;
		$usage['allocatedVols']		= 0;
		$usage['allocatedFIP']		= 0;
		$dateStart					= $json->{'start'};
		$dateEnd					= $json->{'end'};
		
		//get mem stats
		$usage['usedRAM'] 			= 	$this->getProjStatUsage("memory.usage", $dateStart,$dateEnd,"resource_id");
		$usage['allocatedRAM']		=	$this->getProjStatUsage("memory", "","","resource_id");
		//get cpu stats
		$usage['usedCPU'] 			= 	$this->getProjStatUsage("cpu", $dateStart,$dateEnd,"resource_id");
		$usage['allocatedCPU'] 		= 	$this->getProjStatUsage("vcpus", "","","resource_id");
		//get disk stats
		$usage['usedDISK'] 			= 	$this->getProjStatUsage("disk.usage", $dateStart,$dateEnd,"resource_id");
		$usage['allocatedDISK'] 	= 	$this->getProjStatUsage("disk.allocation", "","","resource_id");
		//get network used bytes
		$usage['usedInNetBytes'] 	= 	$this->getProjStatUsage("network.outgoing.bytes", $dateStart,$dateEnd,"resource_id");
		$usage['usedOutNetBytes'] 	= 	$this->getProjStatUsage("network.incoming.bytes", $dateStart,$dateEnd,"resource_id");
		//get object stuff
		$usage['usedObjectBytes'] 	=	$this->getProjStatUsage("storage.objects.size", $dateStart,$dateEnd,"resource_id");
		$usage['allocatedCont'] 	=	$this->getProjStatUsage("storage.objects.containers", "","","resource_id");
		$usage['allocatedObj']		= 	$this->getProjStatUsage("storage.objects", "","","resource_id");
		//get volume stuff
		$usage['allocatedVols']		= 	$this->getProjStatUsage("volume.size", $dateStart,$dateEnd,"resource_id");
		//get float ip stuff
		$usage['allocatedFIP']		= 	$this->getProjStatUsage("ip.floating", $dateStart,$dateEnd,"resource_id");
		
		return $usage;  // Return resource usage
	}
	
	function getProjStatUsage($meter, $dateStart,$dateEnd, $groupBy){
		$this->logger("getProjStatUsage(".$meter.",".$dateStart.") start");
		//get meter stats from os ceilometer
		$ohandle = new oshandler();
		//setup for add ip to helion
		$values = 	'"meterName":"'.$meter.'",
					"meterPeriod":"0",
					"timeStart":"'.$dateStart.'",
					"timeEnd":"'.$dateEnd.'",
					"projectId":"'. $this->openstackId.'",
					"groupBy":["'.$groupBy.'"]';
		//make the call to the handler to deal with call to helion
		$this->logger("getProjectUsage(".$meter."): prepared to execute call to python");
		$res = $ohandle->callOSAdmin(
					$this->heliontenant->helionglobals,
					"getProjectStats",
					$this->projectName,
					$values,
					"CeilometerService");
		
		$json = json_decode($res);
		if($json->{'errorCode'} == "0"){
			$this->logger("getProjStatUsage(".$meter.",".$dateStart.") end success");
			return $json->{'result'}->average;
		}else{
			$this->logger("getProjStatUsage(".$meter.",".$dateStart.") end error from os");
			return 0;
		}
	}
	
	function provisionOpenStackProject() {
		
		$this->logger("provisionOpenStackProject()::Start...");
		$ohandle = new oshandler();
		//setup for create helion project
		$values = 	'"projectName": "'.$this->projectName.'", 
					"description":"'.$this->description.'"';
		$res = $ohandle->callOSAdmin(
					$this->heliontenant->helionglobals, 
					"createProject", 
					$this->heliontenant->tenantName, 
					$values, 
					"KeystoneService");

		$this->logger("provisionOpenStackProject():: executed call to python");
		$this->logger($res);
		$json = json_decode($res);
		if($json->{'errorCode'} == "0"){
			$this->openstackId = $json->{'result'}->id;
		}else{
			$this->status = "Error";
		}
		
		if($this->status != "Error"){
			//then associate the admin user to the new tenant to allow for provision of users
			$values = 	'"projectName": "'.$this->projectName.'",
						"name":"'.$this->heliontenant->helionglobals->HELIONUSER.'",
						"roleName":"admin"';
			$res = $ohandle->callOSAdmin(
						$this->heliontenant->helionglobals, 
						"associateUserToProject", 
						$this->heliontenant->tenantName, 
						$values, 
						"KeystoneService");
			$this->logger("provisionOpenStackProject():: AssocAdmin executed call to python");
			$this->status = "Ready";
		}
		$this->logger("provisionOpenStackProject():: End\n");		
		return;
	}
	
	function unprovisionOpenStackProject() {
		# Code to create OpenStack Project / Tenant - Will use the HELIONDESC (No Spaces) by default
		$this->logger("unprovisionOpenStackProject()::Start...\n");
		$ohandle = new oshandler();
		//setup for delete helion project
		$values = 	'"projectName": "'.$this->projectName.'"';
		$res = $ohandle->callOSAdmin(
					$this->heliontenant->helionglobals, 
					"deleteProject", 
					$this->heliontenant->tenantName, 
					$values, 
					"KeystoneService");
		$this->logger("unprovisionOpenStackProject()::End");
		return;
	}
	
	function enableProject() {
		# Code to suspend OpenStack Project / Tenant - Will use the HELIONDESC (No Spaces) by default
		$this->logger("Enable Openstack Project\n");
		$ohandle = new oshandler();
		//setup for enable helion project
		$values =	'"projectName": "'.$this->projectName.'"';
		$res = $ohandle->callOSAdmin(
					$this->heliontenant->helionglobals, 
					"enableProject", 
					$this->heliontenant->tenantName, 
					$values, 
					"KeystoneService");
		$json = json_decode($res);
		return $json;
	}
	
	function disableProject() {
		# Code to suspend OpenStack Project / Tenant - Will use the HELIONDESC (No Spaces) by default
		
		$this->logger("Suspending Openstack Project");
		$ohandle = new oshandler();
		//setup for enable helion project
		$values =	'"projectName": "'.$this->projectName.'"';
		$res = $ohandle->callOSAdmin(
					$this->heliontenant->helionglobals, 
					"disableProject", 
					$this->heliontenant->tenantName, 
					$values, 
					"KeystoneService");
		$json = json_decode($res);
		return $json;
	}
	
	/**
	 * @verb(POST)
	 * @path("/updateQuota")
	 * @param(string, body)
	 * @access(referrer, true)
	 */
	###########################
	## updateQuota
	###########################
	public function updateQuota($param){
		$this->logger("Enter updateQuota()");
		$this->logger("AddUserToProject::Start with:::> ". $param);
		$param = json_decode($param);
		$param = $param->osquota;
		$result = $this->checkQuotaUpdate($param);
		$this->logger("check result:".json_encode($result));
		
		if ($result['checkResult'] == False){
			$this->logger("UpdateQuota failed because of exceedings\n");
			throw new \Rest\RestException(400, "Update quota exceeds total limit in subscription.");
		}else{
			$arr = array();
	
			//figure out delta, update current vars and prep to update OS
			if($this->osquota->cores != intval($param->cores)){
				$this->osquota->cores = intval($param->cores);
				array_push($arr, '"cores":"'.$param->cores.'"');
			}
			if($this->osquota->fixed_ips != intval($param->fixed_ips)){
				$this->osquota->fixed_ips = intval($param->fixed_ips);
				array_push($arr, '"fixed_ips":"'.$param->fixed_ips.'"');
			}
			if($this->osquota->floating_ips != intval($param->floating_ips)){
				$this->osquota->floating_ips = intval($param->floating_ips);
				array_push($arr, '"floatingip":"'.$param->floating_ips.'"');
			}
			if($this->osquota->injected_file_content_bytes != intval($param->injected_file_content_bytes)){
				$this->osquota->injected_file_content_bytes = intval($param->injected_file_content_bytes);
				array_push($arr, '"injected_file_content_bytes":"'.$param->injected_file_content_bytes.'"');
			}
			if($this->osquota->injected_file_path_bytes != intval($param->injected_file_path_bytes)){
				$this->osquota->injected_file_path_bytes = intval($param->injected_file_path_bytes);
				array_push($arr, '"injected_file_path_bytes":"'.$param->injected_file_path_bytes.'"');
			}
			if($this->osquota->injected_files != intval($param->injected_files)){
				$this->osquota->injected_files = intval($param->injected_files);
				array_push($arr, '"injected_files":"'.$param->injected_files.'"');
			}
			if($this->osquota->instances != intval($param->instances)){
				$this->osquota->instances = intval($param->instances);
				array_push($arr, '"instances":"'.$param->instances.'"');
			}
			if($this->osquota->key_pairs != intval($param->key_pairs)){
				$this->osquota->key_pairs = intval($param->key_pairs);
				array_push($arr, '"key_pairs":"'.$param->key_pairs.'"');
			}
			if($this->osquota->metadata_items != intval($param->metadata_items)){
				$this->osquota->metadata_items = intval($param->metadata_items);
				array_push($arr, '"metadata_items":"'.$param->metadata_items.'"');
			}
			if($this->osquota->ram != intval($param->ram)){
				$this->osquota->ram = intval($param->ram);
				array_push($arr, '"ram":"'.$param->ram.'"');
			}
			if($this->osquota->security_group_rules != intval($param->security_group_rules)){
				$this->osquota->security_group_rules = intval($param->security_group_rules);
				array_push($arr, '"security_group_rule":"'.$param->security_group_rules.'"');
			}
			if($this->osquota->security_groups != intval($param->security_groups)){
				$this->osquota->security_groups = intval($param->security_groups);
				array_push($arr, '"security_group":"'.$param->security_groups.'"');
			}
			if($this->osquota->networks != intval($param->networks)){
				$this->osquota->networks = intval($param->networks);
				array_push($arr, '"network":"'.$param->networks.'"');
			}
			if($this->osquota->ports != intval($param->ports)){
				$this->osquota->ports = intval($param->ports);
				array_push($arr, '"port":"'.$param->ports.'"');
			}
			if($this->osquota->subnets != intval($param->subnets)){
				$this->osquota->subnets = intval($param->subnets);
				array_push($arr, '"subnet":"'.$param->subnets.'"');
			}
			if($this->osquota->routers != intval($param->routers)){
				$this->osquota->routers = intval($param->routers);
				array_push($arr, '"router":"'.$param->routers.'"');
			}
			if($this->osquota->server_group_members != intval($param->server_group_members)){
				$this->osquota->server_group_members = intval($param->server_group_members);
				array_push($arr, '"server_group_members":"'.$param->server_group_members.'"');
			}
			if($this->osquota->server_groups != intval($param->server_groups)){
				$this->osquota->server_groups = intval($param->server_groups);
				array_push($arr, '"server_groups":"'.$param->server_groups.'"');
			}
		
			//if have delta, do os update
			if(count($arr)>0){
				array_push($arr, '"project_id":"'.$this->openstackId.'"');
				$val = implode(",",$arr);
				$this->updateOSQuota($val);
			}
		}
	
	}
	
	private function updateOSQuota($val){
		$this->logger("Update OS Quota");
		$ohandle = new oshandler();
	
		$res = $ohandle->callOSAdmin(
				$this->heliontenant->helionglobals,
				"updateQuota",
				$this->heliontenant->tenantName,
				$val,
				"NovaService");
		$json = json_decode($res);
		if($json->{'errorCode'} != "0"){
			return $json->{'errorMsg'};
		}
		$apsc = \APS\Request::getController();
		$apsc->updateResource($this);
		return "Project Quota Updated";
	
	}
	
	private function getOSQuota(){
		$this->logger("enter getOSQuota()");
		$ohandle = new oshandler();
		$values = '"projectName":"'.$this->openstackId.'"';
		$res = $ohandle->callOSAdmin(
				$this->heliontenant->helionglobals,
				"getQuota",
				$this->projectName,
				$values,
				"NovaService");
		$json = json_decode($res);
		if($json->{'errorCode'} == "0"){
			$this->osquota->subnets = 						$json->{'result'}->quota->subnet;
			$this->osquota->metadata_items = 				$json->{'result'}->quota->metadata_items;
			$this->osquota->cores = 						$json->{'result'}->quota->cores;
			$this->osquota->networks = 						$json->{'result'}->quota->network;
			$this->osquota->ports = 						$json->{'result'}->quota->port;
			$this->osquota->routers = 						$json->{'result'}->quota->router;
			$this->osquota->floating_ips = 					$json->{'result'}->quota->floatingip;
			$this->osquota->fixed_ips = 					$json->{'result'}->quota->fixed_ips;
			$this->osquota->security_group_rules = 			$json->{'result'}->quota->security_group_rule;
			$this->osquota->security_groups = 				$json->{'result'}->quota->security_group;
			$this->osquota->ram = 							$json->{'result'}->quota->ram;
			$this->osquota->injected_files = 				$json->{'result'}->quota->injected_files;
			$this->osquota->instances = 					$json->{'result'}->quota->instances;
			$this->osquota->key_pairs = 					$json->{'result'}->quota->key_pairs;
			$this->osquota->server_group_members = 			$json->{'result'}->quota->server_group_members;
			$this->osquota->server_groups = 				$json->{'result'}->quota->server_groups;
			$this->osquota->injected_file_content_bytes =	$json->{'result'}->quota->injected_file_content_bytes;
			$this->osquota->injected_file_path_bytes =		$json->{'result'}->quota->injected_file_path_bytes;
		}else{
			//not set so no big overall deal
		}
	}
	/**
    * @verb(PUT)
    * @path("/upsell")
    */
	public function upsell(){
		$this->logger("The subscription ".$this->subscription->subscriptionId." did an in app purchase");
	}
	
	public function checkQuotaUpdate($param){
	
		$this->logger("enter the function checkquotaupdate");
	
	
		$this->getTotalProjectsQuota();
		$balance = array();
		$intendedChange = array();
		$exceedings = array();
		
		$balance["subnets"] = $this->heliontenant->subquota->subnets - $this->allProjsQuota->subnets;
		$intendedChange["subnets"] = intval($param->subnets) - $this->osquota->subnets;
		if ($balance["subnets"] >= 0 && $balance["subnets"] < $intendedChange["subnets"]){
			$exceedings["subnets"] = $intendedChange["subnets"] - $balance["subnets"];
		}
		
		$balance["metadata_items"] = $this->heliontenant->subquota->metadata_items - $this->allProjsQuota->metadata_items;
		$intendedChange["metadata_items"] = intval($param->metadata_items) - $this->osquota->metadata_items;
		if ($balance["metadata_items"] >= 0 && $balance["metadata_items"] < $intendedChange["metadata_items"]){
			$exceedings["metadata_items"] = $intendedChange["metadata_items"] - $balance["metadata_items"];
		}
		
		$balance["cores"] = $this->heliontenant->subquota->cores - $this->allProjsQuota->cores;
		$intendedChange["cores"] = intval($param->cores) - $this->osquota->cores;
		if ($balance["cores"] >= 0 && $balance["cores"] < $intendedChange["cores"]){
			$exceedings["cores"] = $intendedChange["cores"] - $balance["cores"];
		}
		
		$balance["networks"] = $this->heliontenant->subquota->networks - $this->allProjsQuota->networks;
		$intendedChange["networks"] = intval($param->networks) - $this->osquota->networks;
		if ($balance["networks"] >= 0 && $balance["networks"] < $intendedChange["networks"]){
			$exceedings["networks"] = $intendedChange["networks"] - $balance["networks"];
		}
		
		$balance["ports"] = $this->heliontenant->subquota->ports - $this->allProjsQuota->ports;
		$intendedChange["ports"] = intval($param->ports) - $this->osquota->ports;
		if ($balance["ports"] >= 0 && $balance["ports"] < $intendedChange["ports"]){
			$exceedings["ports"] = $intendedChange["ports"] - $balance["ports"];
		}
		
		$balance["routers"] = $this->heliontenant->subquota->routers - $this->allProjsQuota->routers;
		$intendedChange["routers"] = intval($param->routers) - $this->osquota->routers;
		if ($balance["routers"] >= 0 && $balance["routers"] < $intendedChange["routers"]){
			$exceedings["routers"] = $intendedChange["routers"] - $balance["routers"];
		}
		
		$balance["floating_ips"] = $this->heliontenant->subquota->floating_ips - $this->allProjsQuota->floating_ips;
		$intendedChange["floating_ips"] = intval($param->floating_ips) - $this->osquota->floating_ips;
		if ($balance["floating_ips"] >= 0 && $balance["floating_ips"] < $intendedChange["floating_ips"]){
			$exceedings["floating_ips"] = $intendedChange["floating_ips"] - $balance["floating_ips"];
		}
		
		$balance["fixed_ips"] = $this->heliontenant->subquota->fixed_ips - $this->allProjsQuota->fixed_ips;
		$intendedChange["fixed_ips"] = intval($param->fixed_ips) - $this->osquota->fixed_ips;
		if ($balance["fixed_ips"] >= 0 && $balance["fixed_ips"] < $intendedChange["fixed_ips"]){
			$exceedings["fixed_ips"] = $intendedChange["fixed_ips"] - $balance["fixed_ips"];
		}
		
		$balance["security_group_rules"] = $this->heliontenant->subquota->security_group_rules - $this->allProjsQuota->security_group_rules;
		$intendedChange["security_group_rules"] = intval($param->security_group_rules) - $this->osquota->security_group_rules;
		if ($balance["security_group_rules"] >= 0 && $balance["security_group_rules"] < $intendedChange["security_group_rules"]){
			$exceedings["security_group_rules"] = $intendedChange["security_group_rules"] - $balance["security_group_rules"];
		}
		
		$balance["security_groups"] = $this->heliontenant->subquota->security_groups - $this->allProjsQuota->security_groups;
		$intendedChange["security_groups"] = intval($param->security_groups) - $this->osquota->security_groups;
		if ($balance["security_groups"] >= 0 && $balance["security_groups"] < $intendedChange["security_groups"]){
			$exceedings["security_groups"] = $intendedChange["security_groups"] - $balance["security_groups"];
		}
		
		$balance["ram"] = $this->heliontenant->subquota->ram - $this->allProjsQuota->ram;
		$intendedChange["ram"] = intval($param->ram) - $this->osquota->ram;
		if ($balance["ram"] >= 0 && $balance["ram"] < $intendedChange["ram"]){
			$exceedings["ram"] = $intendedChange["ram"] - $balance["ram"];
		}
		
		$balance["injected_files"] = $this->heliontenant->subquota->injected_files - $this->allProjsQuota->injected_files;
		$intendedChange["injected_files"] = intval($param->injected_files) - $this->osquota->injected_files;
		if ($balance["injected_files"] >= 0 && $balance["injected_files"] < $intendedChange["injected_files"]){
			$exceedings["injected_files"] = $intendedChange["injected_files"] - $balance["injected_files"];
		}
		
		$balance["instances"] = $this->heliontenant->subquota->instances - $this->allProjsQuota->instances;
		$intendedChange["instances"] = intval($param->instances) - $this->osquota->instances;
		if ($balance["instances"] >= 0 && $balance["instances"] < $intendedChange["instances"]){
			$exceedings["instances"] = $intendedChange["instances"] - $balance["instances"];
		}
		
		$balance["key_pairs"] = $this->heliontenant->subquota->key_pairs - $this->allProjsQuota->key_pairs;
		$intendedChange["key_pairs"] = intval($param->key_pairs) - $this->osquota->key_pairs;
		if ($balance["key_pairs"] >= 0 && $balance["key_pairs"] < $intendedChange["key_pairs"]){
			$exceedings["key_pairs"] = $intendedChange["key_pairs"] - $balance["key_pairs"];
		}
		
		$balance["server_group_members"] = $this->heliontenant->subquota->server_group_members - $this->allProjsQuota->server_group_members;
		$intendedChange["server_group_members"] = intval($param->server_group_members) - $this->osquota->server_group_members;
		if ($balance["server_group_members"] >= 0 && $balance["server_group_members"] < $intendedChange["server_group_members"]){
			$exceedings["server_group_members"] = $intendedChange["server_group_members"] - $balance["server_group_members"];
		}
		
		$balance["server_groups"] = $this->heliontenant->subquota->server_groups - $this->allProjsQuota->server_groups;
		$intendedChange["server_groups"] = intval($param->server_groups) - $this->osquota->server_groups;
		if ($balance["server_groups"] >= 0 && $balance["server_groups"] < $intendedChange["server_groups"]){
			$exceedings["server_groups"] = $intendedChange["server_groups"] - $balance["server_groups"];
		}
		
		$balance["injected_file_content_bytes"] = $this->heliontenant->subquota->injected_file_content_bytes - $this->allProjsQuota->injected_file_content_bytes;
		$intendedChange["injected_file_content_bytes"] = intval($param->injected_file_content_bytes) - $this->osquota->injected_file_content_bytes;
		if ($balance["injected_file_content_bytes"] >= 0 && $balance["injected_file_content_bytes"] < $intendedChange["injected_file_content_bytes"]){
			$exceedings["injected_file_content_bytes"] = $intendedChange["injected_file_content_bytes"] - $balance["injected_file_content_bytes"];
		}
		
		$balance["injected_file_path_bytes"] = $this->heliontenant->subquota->injected_file_path_bytes - $this->allProjsQuota->injected_file_path_bytes;
		$intendedChange["injected_file_path_bytes"] = intval($param->injected_file_path_bytes) - $this->osquota->injected_file_path_bytes;
		if ($balance["injected_file_path_bytes"] >= 0 && $balance["injected_file_path_bytes"] < $intendedChange["injected_file_path_bytes"]){
			$exceedings["injected_file_path_bytes"] = $intendedChange["injected_file_path_bytes"] - $balance["injected_file_path_bytes"];
		}
		
		$result = array();
		$result['data'] = $balance;
		if (count($exceedings) > 0){
			$result['checkResult'] = False;
		}else{
			$result['checkResult'] = True;
		}
	
		$this->logger("leave the function checkquotaupdate");
		return $result;
	
	}
	
	private function getTotalProjectsQuota(){
		$this->logger("inside getTotalProjectsQuota()");
	
		foreach ($this->heliontenant->projects as $project){
			$this->allProjsQuota->subnets += 						$project->osquota->subnets;
			$this->allProjsQuota->metadata_items += 				$project->osquota->metadata_items;
			$this->allProjsQuota->cores += 							$project->osquota->cores;
			$this->allProjsQuota->networks += 						$project->osquota->networks;
			$this->allProjsQuota->ports += 							$project->osquota->ports;
			$this->allProjsQuota->routers += 						$project->osquota->routers;
			$this->allProjsQuota->floating_ips += 					$project->osquota->floating_ips;
			$this->allProjsQuota->fixed_ips += 						$project->osquota->fixed_ips;
			$this->allProjsQuota->security_group_rules += 			$project->osquota->security_group_rules;
			$this->allProjsQuota->security_groups += 				$project->osquota->security_groups;
			$this->allProjsQuota->ram += 							$project->osquota->ram;
			$this->allProjsQuota->injected_files += 				$project->osquota->injected_files;
			$this->allProjsQuota->instances += 						$project->osquota->instances;
			$this->allProjsQuota->key_pairs += 						$project->osquota->key_pairs;
			$this->allProjsQuota->server_group_members += 			$project->osquota->server_group_members;
			$this->allProjsQuota->server_groups += 					$project->osquota->server_groups;
			$this->allProjsQuota->injected_file_content_bytes +=	$project->osquota->injected_file_content_bytes;
			$this->allProjsQuota->injected_file_path_bytes +=		$project->osquota->injected_file_path_bytes;
		}
		
		
		$this->logger("leave getTotalProjectsQuota()");
	}
	
	private function setInitQuota(){
		$this->logger("enter setInitQuota()");
		$arr = array();
		array_push($arr, '"cores":"0"');
		array_push($arr, '"fixed_ips":"0"');
		array_push($arr, '"floatingip":"0"');
		array_push($arr, '"injected_file_content_bytes":"0"');
		array_push($arr, '"injected_file_path_bytes":"0"');
		array_push($arr, '"injected_files":"0"');
		array_push($arr, '"instances":"0"');
		array_push($arr, '"key_pairs":"0"');
		array_push($arr, '"metadata_items":"0"');
		array_push($arr, '"ram":"0"');
		array_push($arr, '"security_group_rule":"0"');
		array_push($arr, '"security_group":"0"');
		array_push($arr, '"network":"0"');
		array_push($arr, '"port":"0"');
		array_push($arr, '"subnet":"0"');
		array_push($arr, '"router":"0"');
		array_push($arr, '"server_group_members":"0"');
		array_push($arr, '"server_groups":"0"');
			
		array_push($arr, '"project_id":"'.$this->openstackId.'"');
		$val = implode(",",$arr);
		$this->updateOSQuota($val);
		$this->logger("leave setInitQuota()");
	}
	
}
?>
