<?php
#############################################################################################################################################
# We are using the standard logger function and APS runtime Libraries
#############################################################################################################################################
require_once "logger.php";
require_once "oshandler.php";
require_once "aps/2/runtime.php";

class TOSQuota{

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

/**
* Class tenant
* @type("http://hp.com/heliontenant/3.0")
* @implements("http://aps-standard.org/types/core/resource/1.0")
* @implements("http://aps-standard.org/types/core/suspendable/1.0")
*/

class tenant extends APS\ResourceBase
{
	#############################################################################################################################################
	#  Link to helionglobals object type.   This gets the Primary Instance Description, LDAP Info / Credentials, and target Helion Server for Tenant
	#############################################################################################################################################

	/**
	* @link("http://hp.com/helionglobals/2.0")
	* @required
	*/
	public $helionglobals;

	#############################################################################################################################################
	# Link to subscription information for this tenant
	#############################################################################################################################################

	/**
	* @link("http://aps-standard.org/types/core/subscription/1.0")
	* @required
	*/
	public $subscription;

	#############################################################################################################################################
	# Link to account type to gather information for this tenant
	#############################################################################################################################################

	/**
	* @link("http://aps-standard.org/types/core/account/1.0")
    * @required
    */
	public $account;
	
	#############################################################################################################################################
	# Link to projects depending on this tenant.   Not a dependency since this tenant can exist without projects. (note: tenant
	#  creates a base project as part of provision of tenant
	#############################################################################################################################################
	
	/**
	 * @link("http://hp.com/project/3.0[]")
	*/
	public $projects;

	#############################################################################################################################################
	# Link to users depending on this tenant.   Not a dependency since this tenant can exist without users.
	#############################################################################################################################################

	/**
	* @link("http://hp.com/users/1.0[]")
	*/
    public $users;
    
    #############################################################################################################################################
    # Link to flavors depending on this tenant.   Not a dependency since this tenant can exist without flavors.
    #############################################################################################################################################
    
    /**
     * @link("http://hp.com/flavor/1.0[]")
     */
    public $flavors;
    

	#############################################################################################################################################
	# Non-linked Variables for the tenant.
	#############################################################################################################################################

	/**
	* @type(string)
	* @title("Tenant ID")
	* @readonly
	*/
	public $TENANTID;
	
	/**
	 * @type(string)
	 * @title("Tenant Name")
	 * @readonly
	 */
	public $tenantName;

	/**
	 * @type(string)
	 * @title("openstackId")
	 * @readonly
	 */
	public $openstackId;
	
	/**
	* @type(string)
	* @title("Group Password")
	* @readonly
	* @encrypted
	*/
	public $GROUPPASS;
	
	/**
	 * @type(string)
	 * @title("lastPoll")
	 * @readonly
	 */
	public $lastPoll;

	#############################################################################################################################################
	# Defined counters for billing model
	#############################################################################################################################################

	/**
	* @type("http://aps-standard.org/types/core/resource/1.0#Counter")
	* @description("Network Usage")
	* @unit("gb")
	*/
	///not using this at the moment
	public $netusage;

	/**
	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
	 * @description("Network Usage Inbound")
	 * @unit("mb-h")
	 */
	public $netusageIn;
	
	/**
	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
	 * @description("Network Usage Inbound (GB)")
	 * @unit("gb-h")
	 */
	public $netusageInGb;
	
	/**
	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
	 * @description("Network Usage Outbound")
	 * @unit("mb-h")
	 */
	public $netusageOut;
	
	/**
	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
	 * @description("Network Usage Outbound(GB)")
	 * @unit("gb-h")
	 */
	public $netusageOutGb;
	
	/**
	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
	 * @description("Network Usage Combined")
	 * @unit("mb-h")
	 */
	public $netusageCombined;
	
	/**
	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
	 * @description("Network Usage Combined(GB)")
	 * @unit("mb-h")
	 */
	public $netusageCombinedGb;
	
	/**
	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
	 * @description("Disk Space Usage")
	 * @unit("mb-h")
	 */
	public $diskusage;
	
	/**
	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
	 * @description("Disk Space Usage 25 Block")
	 * @unit("mb-h")
	 */
	public $diskusageBlock;
	
	/**
	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
	 * @description("Total Disk Space Allocated")
	 * @unit("gb")
	 */
	public $diskallocated;
	
	/**
	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
	 * @description("Disk Allocated 25 Block")
	 * @unit("gb")
	 */
	public $diskallocatedBlock;
	
	/**
	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
	 * @description("RAM Usage")
	 * @unit("mb-h")
	 */
	public $ramusage;
	
	/**
	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
	 * @description("RAM Allocated")
	 * @unit("gb")
	 */
	public $ramallocated;
	
	/**
	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
	 * @description("Object Usage")
	 * @unit("kb-h")
	 */
	public $objectusage;
	
	/**
	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
	 * @description("Objects Allocated")
	 * @unit("unit")
	 */
	public $objectallocated;
	
	/**
	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
	 * @description("Object Containers Allocated")
	 * @unit("unit")
	 */
	public $objectcontainerallocated;
	
	/**
	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
	 * @description("Total CPU Usage")
	 * @unit("unit-h")
	 */
	public $cpuusage;
	
	/**
	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
	 * @description("CPU Allocated")
	 * @unit("unit")
	 */
	public $cpuallocated;
	
	/**
	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
	 * @description("Floating IP Usage")
	 * @unit("unit-h")
	 */
	public $floatipusage;

	/**
	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
	 * @description("Volumes Allocated")
	 * @unit("gb")
	 */
	public $volumesallocated;
	
	/**
	* @type(number)
	* @description("Flag for PBA Billing Verification.  If PBA Billing in Place we set to true, otherwise remains false")
	**/
	public $billing=0;
	
	########################################################
	#attributes used for checking quota when update project#
	########################################################
	
	/**
	 * @type("TOSQuota")
	 * @title("SubQuota")
	 * @description("Subscription Resources Quota")
	 *
	 */
	public $subquota;
	
	/**
	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
	 * @title("limit_cores")
	 * @description("Cores Limit")
	 * @unit("unit")
	 */
	public $limit_cores;
	
	/**
	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
	 * @title("limit_fixed_ips")
	 * @description("Fixed IPs Limit")
	 * @unit("unit")
	 */
	public $limit_fixed_ips;
	
	/**
	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
	 * @title("limit_floating_ips")
	 * @description("Floating IPs Limit")
	 * @unit("unit")
	 */
	public $limit_floating_ips;
	
	/**
	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
	 * @title("limit_injected_file_content_bytes")
	 * @description("Injected File Content Bytes Limit")
	 * @unit("unit")
	 */
	public $limit_injected_file_content_bytes;
	
	/**
	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
	 * @title("limit_injected_file_path_bytes")
	 * @description("Injected File Path Bytes Limit")
	 * @unit("unit")
	 */
	public $limit_injected_file_path_bytes;
	
	/**
	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
	 * @title("limit_injected_files")
	 * @description("Injected Files Limit")
	 * @unit("unit")
	 */
	public $limit_injected_files;
	
// 	/**
// 	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
// 	 * @title("limit_instances")
// 	 * @description("Instances Limit")
// 	 * @unit("unit")
// 	 */
// 	public $limit_instances;
	
	/**
	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
	 * @title("limit_key_pairs")
	 * @description("Key Pairs Limit")
	 * @unit("unit")
	 */
	public $limit_key_pairs;
	
	/**
	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
	 * @title("limit_metadata_items")
	 * @description("Metadata Items Limit")
	 * @unit("unit")
	 */
	public $limit_metadata_items;
	
	/**
	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
	 * @title("limit_ram")
	 * @description("Ram Limit")
	 * @unit("unit")
	 */
	public $limit_ram;
	
// 	/**
// 	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
// 	 * @title("limit_security_group_rules")
// 	 * @description("Security Group Rules Limit")
// 	 * @unit("unit")
// 	 */
// 	public $limit_security_group_rules;
	
// 	/**
// 	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
// 	 * @title("limit_security_groups")
// 	 * @description("Security Groups Limit")
// 	 * @unit("unit")
// 	 */
// 	public $limit_security_groups;
	
// 	/**
// 	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
// 	 * @title("limit_networks")
// 	 * @description("Networks Limit")
// 	 * @unit("unit")
// 	 */
// 	public $limit_networks;
	
	/**
	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
	 * @title("limit_ports")
	 * @description("Ports Limit")
	 * @unit("unit")
	 */
	public $limit_ports;
	
// 	/**
// 	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
// 	 * @title("limit_subnets")
// 	 * @description("Subnets Limit")
// 	 * @unit("unit")
// 	 */
// 	public $limit_subnets;
	
	/**
	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
	 * @title("limit_routers")
	 * @description("Routers Limit")
	 * @unit("unit")
	 */
	public $limit_routers;
	
	/**
	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
	 * @title("limit_server_group_members")
	 * @description("Server Group Members Limit")
	 * @unit("unit")
	 */
	public $limit_server_group_members;
	
	/**
	 * @type("http://aps-standard.org/types/core/resource/1.0#Counter")
	 * @title("limit_server_groups")
	 * @description("Server Groups Limit")
	 * @unit("unit")
	 */
	public $limit_server_groups;

	#############################################################################################################################################
	#  Other APS Operations Place Holders - Not Used in this Release
	#############################################################################################################################################

	public function subscriptionLink(){}
	public function subscriptionUnlink(){}
	public function helionglobalsLink(){}
	public function helionglobalsUnlink(){}
	public function usersLink(){}
	public function usersUnLink(){}
	public function accountLink(){}
	public function accountUnLink(){}
	public function projectsLink(){}
	public function projectsUnlink(){}
	
	public function retrieve() {
		$this->logger("Started retrieve()");
		$this->getProjectUsage();	
		$this->logger("retrieve() complete");
	}
	
	#############################################################################################################################################
	# CRUD Operations on APS Type
	#		* configure - Modify Existing Instance
	#		* provision - New object created
	#		* unprovision - Delete Object
	#		* retrieve - Read Object
	#############################################################################################################################################
	
	public function provision()	{

		$this->tenantName = $this->helionglobals->HELIONDESC.$this->subscription->subscriptionId;
		
		//initialize last poll
		$this->lastPoll = $this->getNow();

		//deal with ldap provision if enabled
		if($this->helionglobals->ldapEnabled){
			$this->logger("provision call provisionLDAP()...");
			$this->provisionLDAP();
			$this->logger("provision call provisionLDAP():: Complete");
		}else{
			$this->logger("Ldap not enabled under this context.");
		}

		//get the resource limit in subscription
		$this->logger("start getting the resource limit in subscription");
		$this->getSubscriptionResourceLimit();
		$this->logger("finish getting the resource limit in subscription");
		
		//deal with helion provision
		$this->logger("provision call provisionOpenstackTenant()...");
		$this->provisionOpenStackTenant();
		$this->logger("Provision Complete :".$this->tenantName);	
	}

	public function configure($new){

		$this->_copy($new);
	}

	public function unprovision(){
		
		//deal with ldap unprovision if using ldap
		if($this->helionglobals->ldapEnabled){
			$this->logger("Unprovision LDAP...");
			$this->unprovisionLDAP();
			$this->logger("Unprovision LDAP complete.");
		}
		
		//deal with helion unprovision
		$this->logger("unprovision call unprovisionOpenstackTenant()");
		$this->unprovisionOpenStackTenant();
		$this->logger("UnProvision Complete: ". $this->tenantName);
	}

	#############################################################################################################################################
	#
	#############################################################################################################################################

	## Need to have enable() call enable on lower hierarhcy objects
	## such as projects, instances, etc. TO-DO
	/**
	* We define operation for enable
	* @verb(PUT)
	* @path("/enable")
	* @param()
	*/
	function enable(){
		
		//enable ldap tenant if using ldap
		if($this->helionglobals->ldapEnabled){
			$this->enableLDAPTenantUsers();	
		}
		
		//endable helion tenant
		$this->enableOpenStackTenant();
		$this->logger("Enable Tenant Complete : ".$this->tenantName);
	}

	/**
	* We define operation for disable
	* @verb(PUT)
	* @path("/disable")
	* @param()
	*/
	function suspend(){
		
		//disable ldap group if using ldap
		if($this->helionglobals->ldapEnabled){
			$this->suspendLDAPTenantUsers();
		}
		$this->logger("Disable associated projects to the tenant first");
		//disable associated projects first otherwise chicken eats egg
		foreach ($this->projects as $p){
			$this->logger("start suspendOSTenant for project: " . $p->projectName);
			$p->disable();
			$this->logger("end suspendOSTenant for project: " . $p->projectName);
		}
		$this->logger("Disable associated users to the tenant next");
		//disable the users
		foreach($this->users as $usr){
			$this->logger("start suspendUsrTenent for user: " . $usr->helionusername);
			$usr->disableuser();
			$this->logger("end suspendUsrTenent for user: " . $usr->helionusername);
		}
		
		//disable helion tenant
		$this->suspendOpenStackTenant();
		$this->logger("Disable (Suspend) Complete :".$this->tenantName);
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



	#############################################################################################################################################
	## Support functions for this class
	#############################################################################################################################################
	private $isDebugEnabled;
	private $logger;
    private $ldap_binda;
	private $make_ssha_password;
	private $provisionLDAP;
	private $provisionOpenStackTenant;
	private $unprovisionLDAP;
	private $unprovisionOpenStackTenant;
	private $enableLDAPTenantUsers;
	private $enableOpenStackTenant;
	private $suspendLDAPTenantUsers;
	private $suspendOpenStackTenant;
	private $getSubNetwork;
	private $getImageList;
	private $getNet;
	private $getInstance;
	
	function isDebugEnabled(){
	}

	//logger method
	function logger($message){
        $requestor=$_SERVER['REMOTE_ADDR'];
        $log = new Logging();
        $log->lwrite($requestor.":".$message);
        $log->lclose();
    }

    //setup ldap info
	function ldap_binda()	{

		$ldap_addr = 'ldaps://'.$this->helionglobals->LDAPIP;
		$ldap_conn = ldap_connect($ldap_addr) or die("Couldn't connect!");
    		ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
    		$ldap_rdn = $this->helionglobals->LDAPUSER;
    		$ldap_pass = $this->helionglobals->LDAPPASS;
    		$flag_ldap = ldap_bind($ldap_conn,"cn=$ldap_rdn,dc=".$this->helionglobals->DC1.",dc=".$this->helionglobals->DC2,$ldap_pass);
    	return $ldap_conn;
	}

	function make_ssha_password($password){
		mt_srand((double)microtime()*1000000);
		$salt = pack("CCCC", mt_rand(), mt_rand(), mt_rand(), mt_rand());
		$hash = "{SSHA}" . base64_encode(pack("H*", sha1($password . $salt)) . $salt);
		return $hash;
	}
	
	//provision ldap stuff
	function provisionLDAP() {
		
		
		if($this->helionglobals->ldapEnabled){
			$this->logger("provisionLDAP() start bindA");
			$ldap_conn = $this->ldap_binda();
			$this->logger("provisionLDAP() binda complete");
			if($ldap_conn){
				$this->TENANTID = $this->subscription->subscriptionId;
	    			$this->GROUPPASS = \APS\generatePassword(12);
	    			$add["objectClass"][0]="posixGroup";
	    			$add["objectClass"][1]="top";
					$add["cn"]=$this->tenantName;
	    			$add["userPassword"]=$this->make_ssha_password($this->GROUPPASS);
	    			$add["gidNumber"]=$this->subscription->subscriptionId;
	    			$add["description"]="Tenant SubscriptionId in Parallels ".$this->subscription->subscriptionId;
	    			$adding=ldap_add($ldap_conn,"cn=".$this->tenantName.",ou=Group,dc=".$this->helionglobals->DC1.",dc=".$this->helionglobals->DC2,$add);
	    			if($adding==true){
					### This is a new code, we will check if PBA exists or not and set a flag called billing if so, that will enable upsells from our UI
					## First we get connection to the controller
					$apsc=\APS\Request::getController();
					###In order to check resources NOT owned by subscription, we must impersonate to resource of the subscription
					### the best one in our case is ourselves (the tenant present in the subscription)
					\APS\Request::getController()->setResourceId($this->aps->id);
					### Now now acting as tenant, need to reset impersonation later
					### Let's dig into the subscription if there is or not billing
					### For that we must search on the subscription resources for one of type http://www.parallels.com/pba/1.0
					$mysubscription=$apsc->getResource($this->subscription->aps->id);
					foreach($mysubscription->resources() as $thesubresource){
						if($thesubresource->apsType=="http://www.parallels.com/pba/1.0"){
							##Billing exists....but...is enabled in the subscription? That will mean that limit > 0
							if(array_key_exists("limit", $thesubresource)){
								if($thesubresource->limit > 0){
									## There is billing indeed, let's flag it to 1
									$this->billing=1;
								}
							}else{
								$this->billing=1;
							}

						}
					}
					#### Since we maybe modified the resource from original input (maybe we touched the billing flag, we must store the changes into the controller before it since we want to do it
					#### as same transaction as before, we reset the impersonation to get to the previous transaction
					\APS\Request::getController()->setResourceId(null);
					$apsc->updateResource($this);
				}
	    			else{
	    				throw new Exception("Error Adding in LDAP for Tenant", true);
	    			}
	    		}
		}
		return;	
	}
	
	//create helion tenant and associate the admin user to it
	function provisionOpenStackTenant() {
		
		$this->logger("provisionOpenStackTenant()::Start...");
		
		#create the parameter string to pass to the script
		$subID = $this->subscription->subscriptionId;
		
		//create new Openstack handler object
		$ohandle = new oshandler();
		//setup for add project to helion
		$values = '"projectName": "'.$this->tenantName.'", 
					"description":"Tenant SubscriptionId in Parallels '.$subID.'"';
		//make the call to the handler to deal with call to helion
		$res = $ohandle->callOSAdmin($this->helionglobals, "createProject", "admin", $values, "KeystoneService");
		$json = json_decode($res);
		if($json->{'errorCode'} == "0"){
			$this->openstackId = $json->{'result'}->id;
			$this->logger("setTenantOsId: ". $this->openstackId);
		}
		//setup for add user to project
		$this->logger("provisionOpenStackTenant():: AssocAdmin");
		$values = 	'"projectName": "'.$this->tenantName.'",
					"name":"admin",
					"roleName":"admin"';
		$ohandle->callOSAdmin($this->helionglobals, "associateUserToProject", "admin", $values, "KeystoneService");
		$this->logger("provisionOpenStackTenant()::End\n");
		return;
	}
	
	function unprovisionLDAP() {
	
		if($this->helionglobals->ldapEnabled){
			$this->logger("Unprovision Tenant with Subscription (Related TenantId):".$this->TENANTID);
			$ldap_conn = $this->ldap_binda();
			try{
				$adding=ldap_delete($ldap_conn,"cn=".$this->tenantName.",ou=Group,dc=".$this->helionglobals->DC1.",dc=".$this->helionglobals->DC2);
			}
			catch(Exception $e){
				//let this fall through as if it is already removed then it's good
			}
			}
		return;
	}

	function unprovisionOpenStackTenant() {
		# Code to create OpenStack Project / Tenant - Will use the HELIONDESC (No Spaces) by default
		$this->logger("unprovisionOpenStackTenant()::Start...\n");
		
		//create new Openstack handler object
		$ohandle = new oshandler();
		//stack the values to pass
		$values = '"projectName": "'.$this->tenantName.'"';
		//farm out to the os handler
		$ohandle->callOSAdmin($this->helionglobals, "deleteProject", "admin", $values, "KeystoneService");
				
		$this->logger("unprovisionOpenStackTenant()::End\n");
		return;
	}
	
	function enableLDAPTenantUsers() {
	
		if($this->helionglobals->ldapEnabled){
			$this->logger("Enabling Tenant:".$this->TENANTID);
			$ldap_conn = $this->ldap_binda();
			if($ldap_conn){
				$fields=array("memberUid");
				$ldapSearch=ldap_search($ldap_conn,"ou=Group,dc=".$this->helionglobals->DC1.",dc=".$this->helionglobals->DC2,"cn=".$this->tenantName,$fields);
				$entries = ldap_get_entries($ldap_conn,$ldapSearch);
				if(isset($entries[0]["memberuid"])){
					for($i=0;$i < $entries[0]["memberuid"]["count"]; $i++){
						$old="uid=disabled_".$entries[0]["memberuid"][$i].",ou=users,dc=".$this->helionglobals->DC1.",dc=".$this->helionglobals->DC2;
						$newname=str_replace("disabled_","",$entries[0]["memberuid"][$i]);
						$new="uid=".$newname;
						$scope="ou=users,dc=".$this->helionglobals->DC1.",dc=".$this->helionglobals->DC2;
						$adding=ldap_rename($ldap_conn,$old,$new,$scope,TRUE);
						if($adding==false){
							throw new Exception("Error enabling users in subscription ".$this->TENANTID);
						}
					}
				}
			}
			else{
				throw new Exception("Error connecting to Ldap when enabling users");
			}
		}
		return;
	}
	
	function enableOpenStackTenant() {

		$ohandler = new oshandler();
		$this->logger("Enabling Openstack Project: ". $this->tenantName);
		$values = '"projectName": "'.$this->tenantName.'"';
		$ohandler->callOSAdmin($this->helionglobals, "enableProject", "admin", $values, "KeystoneService");

		return;
	}
	
	function suspendLDAPTenantUsers() {
	
		if($this->helionglobals->ldapEnabled){
			$this->logger("Disabling Tenant:".$this->TENANTID);
			$ldap_conn = $this->ldap_binda();
			if($ldap_conn){
				$fields=array("memberUid");
				#$ldapSearch=ldap_search($ldap_conn,"ou=Group,dc=".$this->helionglobals->dc1.",dc=".$this->helionglobals->dc2,"cn=".$this->subscription->subscriptionId,$fields);
				$ldapSearch=ldap_search($ldap_conn,"ou=Group,dc=".$this->helionglobals->DC1.",dc=".$this->helionglobals->DC2,"cn=".$this->tenantName,$fields);
				$entries = ldap_get_entries($ldap_conn,$ldapSearch);
				if(isset($entries[0]["memberuid"])){
					for($i=0;$i < $entries[0]["memberuid"]["count"]; $i++){
						$old="uid=".$entries[0]["memberuid"][$i].",ou=users,dc=".$this->helionglobals->DC1.",dc=".$this->helionglobals->DC2;
						$new="uid=disabled_".$entries[0]["memberuid"][$i];
						$scope="ou=users,dc=".$this->helionglobals->DC1.",dc=".$this->helionglobals->DC2;
						$adding=ldap_rename($ldap_conn,$old,$new,$scope,TRUE);
						if($adding==false){
							throw new Exception("Error disabling users");
						}
					}
				}
			}
			else{
				throw new Exception("Error connecting to Ldap when disabling users");
			}
	}
	return;
	}
	
	function suspendOpenStackTenant() {
		# Code to suspend OpenStack Project / Tenant - Will use the HELIONDESC (No Spaces) by default
		
		$this->logger("Suspending Openstack Project\n");
		#create the parameter string to pass to the script
		$ohandler = new oshandler();
		$values = '"projectName": "'.$this->tenantName.'"';
		$ohandler->callOSAdmin($this->helionglobals, "disableProject", "admin", $values, "KeystoneService");
		return;
	}
	
	#############################################################################################################################################
	#
	#############################################################################################################################################
	
	/**
    * @verb(PUT)
    * @path("/upsell")
    */
	
	public function upsell(){
		$this->logger("The subscription ".$this->subscription->subscriptionId." did an in app purchase");
	}
	
	/**
	 * @verb(GET)
	 * @path("/getUserPolicies")
	 * @param(string, body)
	 * @access(referrer, true)
	 */
	###########################
	## getUserPolicies
	###########################
	public function getUserPolicies($pol){
		
		//array to hold all policies to return
		$list = array();
		$apsc = \APS\Request::getController();
		//get the user's projects as policies are directly linked to projects
		$projs = $apsc->getIo()->sendRequest(\APS\Proto::GET, "/aps/2/resources/" . $pol . "/projects");

		$projs = json_decode($projs);
		if(count($projs)>0){
			for($i=0;$i<count($projs);$i++){
				//get the policies for each project
				$res = $apsc->getIo()->sendRequest(\APS\Proto::GET, "/aps/2/resources/" . $projs[$i]->aps->id . "/policies");
				$res = json_decode($res);
				if(count($res)>0){
					$list = array_merge($list, $res);
				}
			}
		}
		return $list;
	}

	/**
	 * @verb(GET)
	 * @path("/getListFlavors")
	 * @param
	 */
	public function getListFlavors(){
	
		$this->logger("getListFlavors()::Start");
		
		$ohandle = new oshandler();
		$values = '';
		$res = $ohandle->callOSAdmin($this->helionglobals, "listFlavors", $this->tenantName, $values, "NovaService");
		return $res;

	}
	
	/**
	 * @verb(GET)
	 * @path("/getListInstances")
	 * @param
	 */
	public function getListInstances(){
	//gets all instances regardless of project
		$this->logger("getListInstances()::Start");
		$apsc = \APS\Request::getController();
		//array to hold all instances to return
		$list = array();
		for($i=0;$i<count($this->projects);$i++){
			$resList = $apsc->getIo()->sendRequest(\APS\Proto::GET, "/aps/2/resources/" . $this->projects[$i]->aps->id . "/projectinstances");
			//turn string response to array
			$x = json_decode($resList, true);
			//iterate and push to response array
			foreach($x as $val){
				array_push($list, $val);
			}
		}
		return $list;
	}
	
	/**
	 * @verb(GET)
	 * @path("/getListNetworks")
	 * @param
	 */
	public function getListNetworks(){
		
		$list = array();
		//see if we need to show OS networks too 
		
		//get the networks from OSA
		$apsc = \APS\Request::getController();
		//array to hold all instances to return
		$list = array();
		for($i=0;$i<count($this->projects);$i++){
			$resList = $apsc->getIo()->sendRequest(\APS\Proto::GET, "/aps/2/resources/" . $this->projects[$i]->aps->id . "/networks");
			//turn string response to array
			$x = json_decode($resList, true);
			//iterate and push to response array
			foreach($x as $val){
				array_push($list, $val);
			}
		}
#############  For Show only OSA networks logic --> Not using currently
#		if(!$this->helionglobals->showOsNetworks){
#					//get the networks direct from OS put into apsish format and add merge
#			$n = $this->getNetsFromOS();
#			if($n != ""){
#				$tmparr = array();
#				$json = json_decode($n); 
#				
#				$nmatch = 0;
#				for($t=0;$t<count($json->{'network'});$t++){
#					$nmatch = 0;
#					//$this->logger("VAL in Dict: " . $val["networkName"]);
#					foreach($list as $val){
#						//$this->logger("TYPE: " . $json->{'network'}[$t]->name);
#						if ($json->{'network'}[$t]->name == $val["networkName"]) {
#							//$this->logger("found it: ");
#							$nmatch++;
#							break;
#						}
#					}
#					if($nmatch == 0){
#						//convert to apsish format
#						$adminstate = $json->{'network'}[$t]->adminState  ? "true":"false";
#						$external = $json->{'network'}[$t]->external ? "true":"false";
#						$name = $json->{'network'}[$t]->name;
#						$shared = $json->{'network'}[$t]->shared ? "true":"false";
#						//$this->logger("BOOLEAN: " . $shared);
#						$tmp = '{"aps":{"id":"openstack-'.$name.'"},
#								"adminstate":"'.$adminstate.'",
#								"externalnetwork":"'.$external.'",
#								"networkName":"'.$name.'",
#								"shared":"'.$shared.'",
#								"managed":"Helion"}';
#						
#						$tmp = json_decode($tmp);
#						array_push($tmparr,$tmp);
#					}
#				}
#				if(count($tmparr)>0){
#					$list = array_merge($list, $tmparr);
#				}
#			}
#		}
		//put in format for use with widget store
		return json_decode(json_encode($list));
	}
	
	private function getNetsFromOS(){
		
		$ohandle = new oshandler();
		$values = '';
		$res = $ohandle->callOSAdmin($this->helionglobals, "getListNetwork", $this->tenantName, $values, "NeutronService");
		$json = json_decode($res);
		if($json->{'errorCode'} == "0"){
			return json_encode($json->{'result'});
		}else{
			$this->logger("getNetsFromOS(pvt)()::End\n");
			throw new \Rest\RestException(400, "Unable to get networks from Helion. " .$json->{'errorMsg'}); 
		}
	}
	
	/**
	* @verb(GET)
	* @path("/getListImagesByProject")
	* @param
	*/
	public function getListImagesByProject(){

		$images ="";
		$tmp = $this->getImageList($this->tenantName);

		if($tmp != ""){
			$images = json_decode($tmp);
			for($i=0;$i<count($images->{'image'});$i++){
				$images->{'image'}[$i]->projectName = $this->tenantName;
			}
		}
		//stack in info from other projects
		for($i = 0;$i<count($this->projects);$i++){
			//$this->logger("PROJECT:::-> ". $this->projects[$i]->projectName);
			$x = $this->getImageList($this->projects[$i]->projectName);
			
			if($x != ""){
				$d = json_decode($x);
				for($t=0;$t<count($d->{'image'});$t++){
					$cnt = count($images->{'image'});
					$images->{'image'}[$cnt]->name = $d->{'image'}[$t]->name;
					$images->{'image'}[$cnt]->projectName = $this->projects[$i]->projectName;
					$cnt++;
				}
	  		}
		}
		return json_decode(json_encode($images->{'image'}));
	}
	
	/**
	* @verb(GET)
	* @path("/getListImages")
	* @param
	*/
	public function getListImages(){
		
		$this->logger("getListImages():: Start");
		$images ="";
		$tmp = $this->getImageList($this->tenantName);
	
		if($tmp != ""){
			$images = json_decode($tmp);
		}
		//$this->logger("Also get list for [".count($this->projects) ."] projects");
	
		for($i = 0;$i<count($this->projects);$i++){
			$x = $this->getImageList($this->projects[$i]->projectName);
			if($x != ""){
				$d = json_decode($x);
				for($t=0;$t<count($d->{'image'});$t++){
					if(!in_array($d->{'image'}[$t], $images->{'image'})){
						array_push($images->{'image'},$d->{'image'}[$t]);
					}
				}
			}
		}
		return json_encode($images);
	}
	
	//get a list of images from openstack
	private function getImageList($project){

		$this->logger("getImageList(pvt):: Started");
		$ohandle = new oshandler();
		$values = '';
		$res = $ohandle->callOSAdmin($this->helionglobals, "listImages", $project, $values, "NovaService");
		$json = json_decode($res);
		if($json->{'errorCode'} == "0"){
			//$this->logger("Found image result");
			return json_encode($json->{'result'});
		}else{
			throw new \Rest\RestException(400, $json->{'errorMsg'});
		}
	}
	
	/**
	 * @verb(POST)
	 * @path("/getListSubnets")
	 * @param(string, body)
	 * @access(referrer, true)
	 */
	###########################
	## getListSubnets
	###########################
	public function getListSubnets($param){
		
		/*gets a list of subnets for a given network. The network
			passed from ui could be the aps id or network name depending
			on whether it is managed in osa or os. Get from OS for those
			managed in osa and from os where managed there. 
		*/
			
		$this->logger("getListSubnets()::Start");
		if(empty($param)){
			$msg = "This function expects a json object containing the network aps id for".
				" OSA managed networks or networkName for OpenStack managed networks.";
			return $msg;
		}
		$json = json_decode($param);
		$id = $json->aps->id;
		$netName = $json->networkName;

		if($id != ""){
			$apsc = \APS\Request::getController();
			$res = $apsc->getIo()->sendRequest(\APS\Proto::GET, "/aps/2/resources/" . $id . "/subnets");
			return json_decode($res);
		}else{
			$tmparr = array();
			if($netName != ""){
				$s = $netName;
				$subs = $this->getSubs($s);
				if($subs != ""){
					$json = json_decode($subs);
					for($i=0;$i<count($json->{'subnets'});$i++){
						$dhcpenabled = $json->{'subnets'}[$i]->dhcpenabled  ? "true":"false";
						$gateway= $json->{'subnets'}[$i]->gatewaydisabled ? "true":"false";
						$sname = $json->{'subnets'}[$i]->name;
						
						$tmp = '{"aps":{"id":"openstack-'.$sname.'"},
								"dhcpEnabled":"'.$dhcpenabled.'",
								"allocationPools":"'.$json->{'subnets'}[$i]->allocationpools.'",
								"dnsServers":"'.$json->{'subnets'}[$i]->dnsservers.'",
								"gatewayDisabled":"'.$gateway.'",
								"gatewayIp":"'.$json->{'subnets'}[$i]->gatewayip.'",
								"hostRoutes":"'.$json->{'subnets'}[$i]->hostroutes.'",
								"ipVersion":"'.$json->{'subnets'}[$i]->ipversion.'",
								"networkAddress":"'.$json->{'subnets'}[$i]->cidr.'",
								"subnetName":"'.$json->{'subnets'}[$i]->name.'",
								"managed":"Helion"}';
						$tmp = json_decode($tmp);
						array_push($tmparr,$tmp);
					}
				}
				///call os to get subnets based on the network name
				return json_decode(json_encode($tmparr));
			}
		}
		return ""; 
	}
	
	private function getSubs($net){
		//gets subnets for a given network from openstack
		$this->logger("getting subnets for network: ". $net);
		$ohandle = new oshandler();
		$values = '"networkName":"'.$net.'"';
		$res = $ohandle->callOSAdmin($this->helionglobals, "getListSubnets", $this->tenantName, $values, "NeutronService");
		$json = json_decode($res);
		if($json->{'errorCode'} == "0"){
			//$this->logger("Found image result");
			return json_encode($json->{'result'});
		}else{
			throw new \Rest\RestException(400, "Unable to get subnets from Helion. " .$json->{'errorMsg'});
		}
	}
	
	private function getNow(){
		$date = date_create();
		return $this->formatDate($date);
	}
	
	private function formatDate($date){
		$format = 'Y-m-d\TH:i:s';
		return $date->format($format);
	}
	
	//function to get usage information from all projects under the tenant
	private function getProjectUsage(){
		
		$this->logger("getProjectUsageStats start");
		## the instance usage
		$disk		= 0.0;
		$diskAloc	= 0.0;
		$ram		= 0.0;
		$ramAloc	= 0.0;
		$cpu		= 0.0;
		$cpuAloc	= 0.0;
		$netin		= 0.0;
		$netout		= 0.0;
		$objCount 	= 0.0;
		$objBytes 	= 0.0;
		$contCount 	= 0.0;
		$volAloc	= 0.0;
		$fipAloc	= 0.0;
		$diskBlock	= 0.0;
		
		$oldNow = $this->lastPoll;
		$now = $this->getNow();
		$this->lastPoll = $now;
		$x = '{"start":"'.$oldNow.'","end":"'.$now.'"}';

		$this->logger("Getting stats for [".count($this->projects)."]");
		$apsc = \APS\Request::getController();
		foreach ($this->projects as $project){
			$this->logger("sending update to ". $project->aps->id);
			//$usage = $apsc->getIo()->sendRequest(\APS\Proto::GET,
			//		$apsc->getIo()->resourcePath($project->aps->id, 'updateResourceUsage'),$param);
			$usage = $project->updateResourceUsage($x);
			$this->logger("update request sent");
			$usage 	= json_decode($usage);

			$disk 		= $disk 		+ floatval($usage->usedDISK);
			$diskAloc	= $diskAloc		+ floatval($usage->allocatedDISK);
			$ram 		= $ram 			+ floatval($usage->usedRAM);
			$ramAloc	= $ramAloc		+ floatval($usage->allocatedRAM);
			$cpu 		= $cpu 			+ floatval($usage->usedCPU);
			$cpuAloc	= $cpuAloc		+ floatval($usage->allocatedCPU);
			$netin 		= $netin 		+ floatval($usage->usedInNetBytes);
			$netout		= $netout		+ floatval($usage->usedOutNetBytes);
			$contCount 	= $contCount 	+ floatval($usage->allocatedCont);
			$objBytes 	= $objBytes 	+ floatval($usage->usedObjectBytes);
			$objCount 	= $objCount 	+ floatval($usage->allocatedObj);
			$volAloc	= $volAloc		+ floatval($usage->allocatedVols);
			$fipAloc	= $fipAloc		+ floatval($usage->allocatedFIP);
			//$diskBlock 	= $diskBlock	+ floatval($usage->usedDISK);

		}
		## Update the APS resource counters
		$this->diskusage->usage				+= intval(round($disk/1024));	//B to MB
		$this->diskallocated->usage			= intval(round($diskAloc/1024/1024));  //B to GB

		//block 25
		$this->diskusageBlock->usage		+= intval(ceil(floatval($disk)/1024/1024/25)); //B to MB for 25 per block

		$this->ramusage->usage				+= intval(round($ram));  //MB

		$this->ramallocated->usage			= intval(round($ramAloc/1024)); //MB
		
		$this->cpuusage->usage				+= intval(round($cpu/3.6E+12)); //ns to hr

		$this->cpuallocated->usage			= intval(round($cpuAloc));  //count of cpus
		
		$this->netusageIn->usage			+= intval(round($netin/1024)); //B to GB
		$this->netusageInGb->usage			+= intval(round($netin/1024/1024)); //B to GB
				
		$this->netusageOut->usage			+= intval(round($netout/1024));  //B to GB
		$this->netusageOutGb->usage			+= intval(round($netout/1024/1024));  //B to GB
		
		//combined net
		$this->netusageCombined->usage		+= intval(round($netout/1024)) + intval(round($netin/1024));
		$this->netusageCombinedGb->usage		+= intval(round($netout/1024/1024)) + intval(round($netin/1024/1024));
		
		$this->objectusage->usage 			+= intval(round($objBytes/1024/1024));  //B to MB

		$this->objectallocated->usage 		= intval(round($objCount));  //count objects

		$this->objectcontainerallocated->usage 	= intval(round($contCount));  //count container

		$this->volumesallocated->usage		= intval(round($volAloc)); //GB
		$this->floatipusage->usage			+= intval(round($fipAloc)); //count
		$this->logger("getProjectUsageStats end");
		return;
	}
	
	/**
	 * @verb(POST)
	 * @path("/upgradeFlavors")
	 * @param
	 */
	public function upgradeFlavors(){
		$this->logger("starting upgrade flavors");
		$flavs = Array();
		$instassoc = Array();
		$projassoc = Array();
		
		//iterate list of projects to get instances
		foreach($this->projects as $p){
			//iterate the instances to get unique flavor names
			foreach($p->projectinstances as $instance){
				//flavor already found... add instance to composite
				if (in_array($instance->flavor, $flavs)){
					$c = $instassoc[$instance->flavor];
					//instance already exists in composite
					if(in_array($c, $instance->aps->id)){
						$this->logger("instance [".$instance->aps->id."] already exists in composite");
					//add instance to composite
					}else{
						array_push($instassoc[$instance->flavor],$instance->aps->id);
						$this->logger("adding [".$instance-aps-id."] to the array under key [".$instance->flavor."]");
					}
				}
				else{
					//flavor doesn't yet exist in flavs array so add it
					array_push($flavs, $instance->flavor);
					//add flavor/instance to composite
					array_push($instassoc[$instance->flavor],$instance->aps->id);
				}
			}
			$projassoc[$p] = Array();
			foreach($flavs as $f){
				array_push($projassoc[$p],$f);
			}
		}
		//iterate composite result to create the flavors in aps
		
		//first get list of flavors and details from OS
		$res = $this->getFlavDetailsFromOS();
		$json = json_decode($res);
		
		//then create the aps flavor resource
		$apsc = \APS\Request::getController();
		
		foreach($json as $j){
			$resource = \APS\TypeLibrary::newResourceByTypeId('http://hp.com/flavor/1.0');
			
			$resource->flavorName 		= $json[$i]->name;
			$resource->cpus 			= $json[$i]->cpu;
			$resource->ram 				= $json[$i]->ram;
			$resource->rootdisk 		= $json[$i]->disk;
			$resource->ephemeraldisk 	= $json[$i]->ephemeral;
			$resource->swapdisk 		= $json[$i]->swap;
			$resource->openstackId 		= $json[$i]->id;
			
			$apsc->linkResource($this, 'flavors', $resource);
		}
	}
	
	function getFlavDetailsFromOS(){
		
		$ohandle = new oshandler();
		//setup for create helion project
		$values = 	"";
		$res = $ohandle->callOSAdmin(
				$this->heliontenant->helionglobals,
				"listFlavorDetails",
				$this->tenantName,
				$values,
				"NovaService");
		
		$this->logger("getFlavorDetails():: executed call to python");
		$json = json_decode($res);
		if($json->{'errorCode'} == "0"){
			return $json->{'result'};
		}else{
			throw new \Rest\Exception(400, "Unable to process: " . $json->{'errorMsg'});
		}
	}
	
	/**
	 * @verb(GET)
	 * @path("/setInitLimits")
	 * @param()
	 * @access(referrer, true)
	 */
	
	public function setInitLimits(){
	
		$this->getSubscriptionResourceLimit();
		$apsc = \APS\Request::getController();
		$apsc->updateResource($this);
	}
	
	#############################################################################################################################################
	#the following 2 functions are used to check the total quota for each project not exceeding the subscription's limit
	#############################################################################################################################################
	
	private function getSubscriptionResourceLimit(){
		$this->logger("Enter getSubscriptionResourceLimit()");
		$apsc = \APS\Request::getController();
		\APS\Request::getController()->setResourceId($this->aps->id);
		$mySubResources = $apsc->getResource($this->subscription->aps->id)->resources();
		if(is_null($this->subquota)){
			$this->subquota = new TOSQuota();
		}
		
		foreach($mySubResources as $subResource){

			if (strpos($subResource->apsType, 'helioninstance') !== FALSE){
				if (array_key_exists('limit', $subResource)){
					$this->subquota->instances = 						$subResource->limit;
				}else{
					$this->subquota->instances = -1;
				}
			}
			if (strpos($subResource->apsType, 'helionsubnet') !== FALSE){
				if (array_key_exists('limit', $subResource)){
					$this->subquota->subnets = 							$subResource->limit;
				}else{
					$this->subquota->subnets = -1;
				}
			}
			if (strpos($subResource->apsType, 'helionsecurityrule') !== FALSE){
				if (array_key_exists('limit', $subResource)){
					$this->subquota->security_group_rules = 			$subResource->limit;
				}else{
					$this->subquota->security_group_rules = -1;
				}
			}
			if (strpos($subResource->apsType, 'helionnetwork') !== FALSE){
				if (array_key_exists('limit', $subResource)){
					$this->subquota->networks = 						$subResource->limit;
				}else{
					$this->subquota->networks = -1;
				}
			}
			if (strpos($subResource->apsType, 'helionsecuritygroup') !== FALSE){
				if (array_key_exists('limit', $subResource)){
					$this->subquota->security_groups = 					$subResource->limit;
				}else{
					$this->subquota->security_groups = -1;
				}
			}
				

		}
	
		foreach($mySubResources as $subResource){
			if (array_key_exists('property', $subResource)){
				if ($subResource->property == 'limit_cores'){
					if (array_key_exists('limit', $subResource)){
						$this->subquota->cores = 							$subResource->limit;
					}else{
						$this->subquota->cores = -1;
					}
				}
				if ($subResource->property == 'limit_fixed_ips'){
					if (array_key_exists('limit', $subResource)){
						$this->subquota->fixed_ips = 						$subResource->limit;
					}else{
						$this->subquota->fixed_ips = -1;
					}
				}
				if ($subResource->property == 'limit_injected_file_content_bytes'){
					if (array_key_exists('limit', $subResource)){
						$this->subquota->injected_file_content_bytes = 		$subResource->limit;
					}else{
						$this->subquota->injected_file_content_bytes = -1;
					}
				}
				if ($subResource->property == 'limit_injected_file_path_bytes'){
					if (array_key_exists('limit', $subResource)){
						$this->subquota->injected_file_path_bytes = 		$subResource->limit;
					}else{
						$this->subquota->injected_file_path_bytes = -1;
					}
				}
				if ($subResource->property == 'limit_injected_files'){
					if (array_key_exists('limit', $subResource)){
						$this->subquota->injected_files = 					$subResource->limit;
					}else{
						$this->subquota->injected_files = -1;
					}
				}
				if ($subResource->property == 'limit_key_pairs'){
					if (array_key_exists('limit', $subResource)){
						$this->subquota->key_pairs = 						$subResource->limit;
					}else{
						$this->subquota->key_pairs = -1;
					}
				}
				if ($subResource->property == 'limit_metadata_items'){
					if (array_key_exists('limit', $subResource)){
						$this->subquota->metadata_items = 					$subResource->limit;
					}else{
						$this->subquota->metadata_items = -1;
					}
				}
				if ($subResource->property == 'limit_ram'){
					if (array_key_exists('limit', $subResource)){
						$this->subquota->ram = 								$subResource->limit;
					}else{
						$this->subquota->ram = -1;
					}
				}
				if ($subResource->property == 'limit_ports'){
					if (array_key_exists('limit', $subResource)){
						$this->subquota->ports = 							$subResource->limit;
					}else{
						$this->subquota->ports = -1;
					}
				}
				if ($subResource->property == 'limit_routers'){
					if (array_key_exists('limit', $subResource)){
						$this->subquota->routers = 							$subResource->limit;
					}else{
						$this->subquota->routers = -1;
					}
				}
				if ($subResource->property == 'limit_server_group_members'){
					if (array_key_exists('limit', $subResource)){
						$this->subquota->server_group_members = 			$subResource->limit;
					}else{
						$this->subquota->server_group_members = -1;
					}
				}
				if ($subResource->property == 'limit_server_groups'){
					if (array_key_exists('limit', $subResource)){
						$this->subquota->server_groups = 					$subResource->limit;
					}else{
						$this->subquota->server_groups = -1;
					}
				}
				if ($subResource->property == 'limit_floating_ips'){
					if (array_key_exists('limit', $subResource)){
						$this->subquota->floating_ips = 					$subResource->limit;
					}else{
						$this->subquota->floating_ips = -1;
					}
				}
			}
		}
		$this->logger("Leave getSubscriptionResourceLimit()");
	}
}
?>
