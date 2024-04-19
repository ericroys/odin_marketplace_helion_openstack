<?php

require_once "logger.php";
require_once "aps/2/runtime.php";
require_once "oshandler.php";

/**
* Class users
* @type("http://hp.com/users/1.0")
* @implements("http://aps-standard.org/types/core/resource/1.0")
* @implements("http://aps-standard.org/types/core/suspendable/1.0")
*/

class users extends APS\ResourceBase {

	/**
	* @link("http://hp.com/heliontenant/3.0")
	* @required
	*/
	public $tenant;
	
	/**
	 * @link("http://hp.com/project/3.0[]")
	 */
	public $projects;

	/**
	* @link("http://aps-standard.org/types/core/service-user/1.0")
	* @required
	*/
	public $user;

	/**
	 * @type(string)
	 * @title("User Name")
	 * @Description("Helion Username")
	 * @readonly
	 */
	public $helionusername;

	/**
	* @type(string)
	* @title("Status")
	* @Description("User Status")
	*/
	public $userstatus;

	/**
	 * @type(number)
	 * @title("Disk Quota Assigned")
	 */
	public $heliondiskquota;
	
	/**
	 * @type(number)
	 * @title("Net Quota Assigned")
	 */
	public $helionnetquota;
	
	/**
	 * @type(number)
	 * @title("Cpu Quota Assigned")
	 */
	public $helioncpuquota;
	/**
	* @type(number)
	* @title("Current Usage")
	* @readonly
	*/
	public $userusage;
	
	/**
    * @type(string)
    * @title("Service User ID")
    **/
	public $serviceUserId;


	public function tenantlink(){}
	
	public function tenantUnlink(){}
	
	public function userLink(){}
	
	public function userUnLink(){}
	
	public function projectsLink(){}
	
	public function projectsUnlink(){}


	###########################
	## Provison Synchronously
	###########################
	public function provision(){
		$this->logger("Regular provision():: Start");
		$this->serviceUserId = $this->user->aps->id;
		$this->userstatus = "Provisioning";
		$this->helionusername=$this->user->login;
		$this->logger("provision()::Called");
		throw new \Rest\Accepted($this, "Creating user ".$this->user->login." on Helion");
	}

	function isDebugEnabled(){
	}
	
	###########################
	## Provison Asynchronously
	###########################
	public function provisionAsync(){
		$this->logger("provisionAsync():: create service for user".$this->user->login);
		if($this->tenant->helionglobals->ldapEnabled){
			$this->logger("ldap is enabled. provision user in ldap");
			$this->provisionLdap();
		}
		$this->logger("async provision():: Start");
		$this->logger("provisionHelion():: Called");
		$this->provisionHelion();
		$this->logger("provisionHelion():: Returned");
	}
	
	###########################
	## Unprovision
	###########################	
	public function unprovision(){
		$this->logger("unprovision():: Start");
		//drop the helion user from the tenant
		$this->logger("unprovisionHelion():: Called");
		//make the call to unprovision
		$this->unprovisionHelion();
		$this->logger("unprovisionHelion():: Returned");
		//drop the user from ldap group
		if($this->tenant->helionglobals->ldapEnabled){
			$this->logger("ldap is enabled. unprovision ldap user");
			$this->unprovisionLdap();
		}
	}
	
	/**
	 * We define the operation on post event
	 * @verb(POST)
	 * @path("/onUsrChange")
	 * @param("http://aps-standard.org/types/core/resource/1.0#Notification",body)
	 */
	###########################
	## User Change
	###########################
	public function onUserChange($event){
		$this->logger("onUserChange event has been triggered");
		$apsc = \APS\Request::getController();
		$serviceuser = $apsc->getResource($event->source->id);
		//$this->logger("onUserChange now knows that what's actual for user is: ".print_r($serviceuser,true));
		//$this->logger("we must update the login of this object:".$this->aps->id);
		$tenantinfo = $apsc->getResource($this->tenant->aps->id);
		//$this->logger("Information retrived by onUserChange relative to tenant of changed user".print_r($tenantinfo,true));
	
		if($this->tenant->helionglobals->ldapEnabled){
			$ldap_conn = $this->ldap_binda();
			if(!$ldap_conn){
				throw new Exception("Error connecting to ldap");
			}
			if($this->helionusername != $serviceuser->login){
				$oldname="uid=".$this->helionusername.",ou=users,dc=".$this->tenant->helionglobals->DC1.",dc=".$this->tenant->helionglobals->DC2;
				$newname="uid=".$serviceuser->login;
				$scope="ou=users,dc=".$this->tenant->helionglobals->DC1.",dc=".$this->tenant->helionglobals->DC2;
				$adding=ldap_rename($ldap_conn,$oldname,$newname,$scope,TRUE);
				if(ldap_error($ldap_conn) != "Success"){
					$oldname="uid=suspended_".$this->helionusername.",ou=users,dc=".$this->tenant->helionglobals->DC1.",dc=".$this->tenant->helionglobals->DC2;
					$newname="uid=suspended_".$serviceuser->login;
					$adding=ldap_rename($ldap_conn,$oldname,$newname,$scope,TRUE);
					if(ldap_error($ldap_conn) != "Success"){
						$this->logger("Seams we was't able to found the user ".$this->helionusername." while performing onUserChange due changes performed by Configure function");
					}
					else{
						$add2["memberUid"]=$this->helionusername;
						$adding2=ldap_mod_del($ldap_conn,"cn=".$this->tenant->tenantName.",ou=Group,dc=".$this->tenant->helionglobals->DC1.",dc=".$this->tenant->helionglobals->DC2, $add2);
						$add2["memberUid"]=$serviceuser->login;
						ldap_mod_add($ldap_conn,"cn=".$this->tenant->tenantName.",ou=Group,dc=".$this->tenant->helionglobals->DC1.",dc=".$this->tenant->helionglobals->DC2, $add2);
						$this->logger(print_r(ldap_error($ldap_conn),true));
					}
				}
				else{
					$add2["memberUid"]=$this->helionusername;
					$adding2=ldap_mod_del($ldap_conn,"cn=".$this->tenant->tenantName.",ou=Group,dc=".$this->tenant->helionglobals->DC1.",dc=".$this->tenant->helionglobals->DC2, $add2);
					$add2["memberUid"]=$serviceuser->login;
					ldap_mod_add($ldap_conn,"cn=".$this->tenant->tenantName.",ou=Group,dc=".$this->tenant->helionglobals->DC1.",dc=".$this->tenant->helionglobals->DC2, $add2);
				}
			}
			$add["uid"]=$serviceuser->login;
			$add["cn"]=$serviceuser->login." ".$serviceuser->login;
			$add["objectClass"][0]="account";
			$add["objectClass"][1]="posixAccount";
			$add["objectClass"][2]="top";
			$password=$this->make_ssha_password($serviceuser->password);
			$add["userPassword"]=$password;
			$add["description"]=$serviceuser->displayName;
			if(!$serviceuser->email || strlen($serviceuser->email) < 3 ){
				$add["emailAddress"]=$serviceuser->login;
			}
			else{
				$add["emailAddress"]=$serviceuser->email;
			}
			$add["HelionDiskQuota"]=0;
			$add["HelionNetQuota"]=0;
			$add["HelionCpuQuota"]=0;
			if($this->userstatus=="Ready"){
				try{
					ldap_modify($ldap_conn,"uid=".$serviceuser->login.",ou=users,dc=".$this->tenant->helionglobals->DC1.",dc=".$this->tenant->helionglobals->DC2, $add);
				}
				catch(Exception $e){
					$this->logger("Seams we tried to add the same as it was do action enable/disable $e");
				}
			}
			else{
				try{
					ldap_modify($ldap_conn,"uid=suspended_".$serviceuser->login.",ou=users,dc=".$this->tenant->helionglobals->DC1.",dc=".$this->tenant->helionglobals->DC2, $add);
				}
				catch(Exception $e){
					$this->logger("Seams we tried to add the same as it was do action enable/disable $e");
				}
			}
		}
	
		$this->logger("we must update the login of this object:".$this->aps->id);
		$helionuser=$apsc->getResource($this->aps->id);
		$helionuser->helionusername=$serviceuser->login;
		$apsc->updateResource($helionuser);
		return;
	}
	
	###########################
	## Configure New
	###########################
	public function configure($new=null){
		if(!$new){
			return;
		}
		if($this->tenant->helionglobals->ldapEnabled){
			//$this->logger("Configure has been called, old values was for Tenant: ".print_r($this->tenant,true)."\nuser: ".print_r($this->user,true)."\nUsername:".$this->helionusername);
			//$this->logger("New values are for Tenant: ".print_r($new->tenant,true)."\nuser: ".print_r($new->user,true)."\nUsername:".$new->helionusername);
			if($this->userstatus != $new->userstatus){
				return;
			}
			$this->_copy($new);
			$apsc = \APS\Request::getController();
			$userinfo = $apsc->getResource($new->user->aps->id);
			//$this->logger("Configure has obtained the user info: ".print_r($userinfo,true));
			$tenantinfo = $apsc->getResource($new->tenant->aps->id);
			$ldap_conn = $this->ldap_binda();
			if(!$ldap_conn){
				throw new Exception("Error connecting to ldap");
			}
			$changes = "no";
			if($this->helionusername != $new->helionusername){
				$oldname="uid=".$this->helionusername.",ou=users,dc=".$this->tenant->helionglobals->DC1.",dc=".$this->tenant->helionglobals->DC2;
				$newname="uid=".$new->helionusername;
				$scope="ou=users,dc=".$this->tenant->helionglobals->DC1.",dc=".$this->tenant->helionglobals->DC2;
				$adding=ldap_rename($ldap_conn,$oldname,$newname,$scope,TRUE);
				if(ldap_error($ldap_conn) != "Success"){
					$oldname="uid=suspended_".$this->helionusername.",ou=users,dc=".$this->tenant->helionglobals->DC1.",dc=".$this->tenant->helionglobals->DC2;
					$newname="uid=suspended_".$new->helionusername;
					$adding=ldap_rename($ldap_conn,$oldname,$newname,$scope,TRUE);
					if(ldap_error($ldap_conn) != "Success"){
						logger("Seams we was't able to found the user ".$this->helionusername." while performing reconfigure since was changed by onUserChange");
					}
					else{
						$add2["memberUid"]=$this->helionusername;
						$adding2=ldap_mod_del($ldap_conn,"cn=".$this->tenant->tenantName.",ou=Group,dc=".$this->tenant->helionglobals->DC1.",dc=".$this->tenant->helionglobals->DC2, $add2);
						$add2["memberUid"]=$new->helionusername;
						ldap_mod_add($ldap_conn,"cn=".$this->tenant->tenantName.",ou=Group,dc=".$this->tenant->helionglobals->DC1.",dc=".$this->tenant->helionglobals->DC2, $add2);
					}
				}
				else{
					$add2["memberUid"]=$this->helionusername;
					$adding2=ldap_mod_del($ldap_conn,"cn=".$this->tenant->tenantName.",ou=Group,dc=".$this->tenant->helionglobals->DC1.",dc=".$this->tenant->helionglobals->DC2, $add2);
					$add2["memberUid"]=$new->helionusername;
					ldap_mod_add($ldap_conn,"cn=".$this->tenant->tenantName.",ou=Group,dc=".$this->tenant->helionglobals->DC1.",dc=".$this->tenant->helionglobals->DC2, $add2);
				}
				$changes="yes";
			}
			if($this->helionusername != $userinfo->login){
				$oldname="uid=".$this->helionusername.",ou=users,dc=".$this->tenant->helionglobals->DC1.",dc=".$this->tenant->helionglobals->DC2;
				$newname="uid=".$userinfo->login;
				$scope="ou=users,dc=".$this->tenant->helionglobals->DC1.",dc=".$this->tenant->helionglobals->DC2;
				$adding=ldap_rename($ldap_conn,$oldname,$newname,$scope,TRUE);
				if(ldap_error($ldap_conn) != "Success"){
				  	$oldname="uid=suspended_".$this->helionusername.",ou=users,dc=".$this->tenant->helionglobals->DC1.",dc=".$this->tenant->helionglobals->DC2;
				  	$newname="uid=suspended_".$userinfo->login;
					$adding=ldap_rename($ldap_conn,$oldname,$newname,$scope,TRUE);
					if(ldap_error($ldap_conn) != "Success"){
						$this->logger("Seams we was't able to found the user ".$this->helionusername." while performing reconfigure since was changed by onUserChange");
					}
					else{
						$add2["memberUid"]=$this->helionusername;
						$adding2=ldap_mod_del($ldap_conn,"cn=".$this->tenant->tenantName.",ou=Group,dc=".$this->tenant->helionglobals->DC1.",dc=".$this->tenant->helionglobals->DC2, $add2);
						$add2["memberUid"]=$userinfo->login;
						ldap_mod_add($ldap_conn,"cn=".$this->tenant->tenantName.",ou=Group,dc=".$this->tenant->helionglobals->DC1.",dc=".$this->tenant->helionglobals->DC2, $add2);
					}
				}
				else{
					$add2["memberUid"]=$this->helionusername;
					$adding2=ldap_mod_del($ldap_conn,"cn=".$this->tenant->tenantName.",ou=Group,dc=".$this->tenant->helionglobals->DC1.",dc=".$this->tenant->helionglobals->DC2, $add2);
					$add2["memberUid"]=$userinfo->login;
					ldap_mod_add($ldap_conn,"cn=".$this->tenant->tenantName.",ou=Group,dc=".$this->tenant->helionglobals->DC1.",dc=".$this->tenant->helionglobals->DC2, $add2);
				}
				$new->helionusername=$userinfo->login;
				$changes="yes";	
			}
			$add["uid"]=$new->helionusername;
			$add["cn"]=$new->helionusername." ".$new->helionusername;
			$add["objectClass"][0]="account";
			$add["objectClass"][1]="posixAccount";
			$add["objectClass"][2]="top";
			$password=$this->make_ssha_password($this->user->password);
			 ###$password=$this->make_ssha_password('1q2w3e');
			$add["userPassword"]=$password;
			$add["description"]=$userinfo->displayName;
			if(!$userinfo->email || strlen($this->user->email) < 3 ){
				$add["emailAddress"]=$userinfo->login;
			}
			else{
				$add["emailAddress"]=$userinfo->email;
			}
			$add["HelionDiskQuota"]=0;
			$add["HelionNetQuota"]=0;
			$add["HelionCpuQuota"]=0;
			if($this->userstatus=="Ready"){
				try{
					ldap_modify($ldap_conn,"uid=".$new->helionusername.",ou=users,dc=".$this->tenant->helionglobals->DC1.",dc=".$this->tenant->helionglobals->DC2, $add);
				}
				catch(Exception $e){
					$this->logger("Seams we tried to add the same as it was do action enable/disable $e");
				}
			}
			else{
				try{
				ldap_modify($ldap_conn,"uid=suspended_".$new->helionusername.",ou=users,dc=".$this->tenant->helionglobals->DC1.",dc=".$this->tenant->helionglobals->DC2, $add);
				}
				catch(Exception $e){
					$this->logger("Seams we tried to add the same as it was do action enable/disable $e");
				}
			}
		}
		$this->helionusername=$new->helionusername;
		return;
	}
	
	/**
	 * We define operation for enable
	 * @verb(POST)
	 * @path("/disable")
	 * @param()
	 */
	###########################
	## Disable User
	###########################
	public function disableuser(){
	
		$this->logger("A Customer is requesting to disable the user".$this->user->login);
		if ($this->userstatus == "Provisioning"){
			return ("User has not been provisioned yet");
		}
		if($this->userstatus == "Suspended"){
			$this->logger("user [". $this->helionusername."] already suspended");
			return;
		}
		if($this->tenant->helionglobals->ldapEnabled){
			$this->logger("use ldap enabled. disabling user in ldap");
			$res_ldap = $this->disableLdap();
		}
		$res_helion = $this->disableHelion();
		$this->userstatus = "Suspended";
		$apsc = \APS\Request::getController();
		$apsc->updateResource($this);
		return("User ".$this->user->login." has been Suspended");
	}
	
	/**
	 * We define operation for enable
	 * @verb(POST)
	 * @path("/enable")
	 * @param()
	 */
	###########################
	## Enable User
	###########################
	public function enableuser(){
		if ($this->userstatus == "Provisioning"){
			return ("User has not been provisioned yet");
		}
		$res_helion = $this->enableHelion();
		if($this->tenant->helionglobals->ldapEnabled){
			$res_ldap = $this->enableLdap();
		}
		$this->userstatus = "Ready";
		$apsc = \APS\Request::getController();
		$apsc->updateResource($this);
		return("User ".$this->user->login." has been Activated");
	}
	
	/**
	 * @verb(POST)
	 * @path("/addUserToProject")
	 * @param(string, body)
	 * @access(referrer, true)
	 */
	###########################
	## addUserToProject
	###########################
	public function addUserToProject($param){
	
		$this->logger("AddUserToProject::Start with:::> ". $param);
		$param = json_decode($param);
		$tmp = $param->project[0];
		$apsc = \APS\Request::getController();
		$project = $apsc->getResource($tmp);
		$user = $apsc->getResource($param->user);
		$user->createProjectUserLinkOS($project->projectName);
		$apsc->linkResource($project, 'projectusers', $user);

		return "Added user to project";
	}
	
	/**
	 * @verb(POST)
	 * @path("/createProjectUserLinkOS")
	 * @param(string,body)
	 */
	###########################
	## createProjectUserLinkOS
	###########################
	public function createProjectUserLinkOS($param){
		
		//if user is being added to a regular project then they can be
		//removed from the os project for the overall tenant created during subscription
		//do not remove user from tenant on OSA side else lose all hierarchy
		try {
			$this->logger("Remove user from main tenant...");
			$this->removeProjectUserLinkOS($this->tenant->tenantName);
		} catch (Exception $e) {
			$this->logger("already removed");
			//do nothing as assumed failed due to already being removed
		}
		$this->logger("createProjectUserLinkOS():: Start");
		//create new Openstack handler object
		$ohandle = new oshandler();
		//setup for add project to helion
		$values =	'"projectName": "'.$param.'",
					"name":"'.$this->helionusername.'",
					"roleName":"_member_"';
		//make the call to the handler to deal with call to helion
		$this->logger("createProjectUserLinkOS():: AssocAdmin prepared to execute call to python");
		$res = $ohandle->callOSAdmin($this->tenant->helionglobals, "associateUserToProject", $this->tenant->tenantName, $values, "KeystoneService");
		$json = json_decode($res);
	
		if($json->{'errorCode'} == "0"){
			$this->logger("addProjectUserLinkOS()::End\n");
			return;
		}else{
			$this->logger("addProjectUserLinkOS()::End\n");
			throw new \Rest\RestException(400, "Unable to add user to project. " .$json->{'errorMsg'}); 
		}
	}
	
	/**
	 * @verb(POST)
	 * @path("/removeUserFromProject")
	 * @param(string, body)
	 * @access(referrer, true)
	 */
	###########################
	## removeUserFromProject
	###########################
	public function removeUserFromProject($param){
	
		$this->logger("removeUserFromProject()::Start with:::> ". $param);
		$param = json_decode($param);
		$tmp = $param->project[0];
	
		$apsc = \APS\Request::getController();
		$project = $apsc->getResource($tmp);
		$user = $apsc->getResource($param->user);
		
		$user->removeProjectUserLinkOS($project->projectName);
		$apsc->getIo()->sendRequest(\APS\Proto::DELETE, "/aps/2/resources/" . $tmp. "/projectusers/".$param->user);

		return "Removed user from project";
	}
	
	/**
	 * @verb(POST)
	 * @path("/removeProjectUserLinkOS")
	 * @param(string,body)
	 */
	###########################
	## removeProjectUserLinkOS
	###########################
	public function removeProjectUserLinkOS($param){
	
		//create new Openstack handler object
		$ohandle = new oshandler();
		//setup for add project to helion
		$values = '"projectName": "'.$param.'",
						"name":"'.$this->helionusername.'"';
		//make the call to the handler to deal with call to helion
		$this->logger("removeProjectUserLinkOS():: AssocAdmin prepared to execute call to python");
		$res = $ohandle->callOSAdmin($this->tenant->helionglobals, "removeUserFromProject", $this->tenant->tenantName, $values, "KeystoneService");
		$json = json_decode($res);
		if($json->{'errorCode'} == "0"){
			$this->logger("removeProjectUserLinkOS()::End\n");
			return;
		}else{
			$this->logger("removeProjectUserLinkOS()::End\n");
			throw new \Rest\RestException(400, "Unable to remove user from project. " .$json->{'errorMsg'}); 
		}
	}
	
	###########################
	## getContainerProjectId
	###########################
	private function getContainerProjectId($container){
		$this->logger("getContainerProjectId()::Start with:::> ". $container);
	
		$apsc = \APS\Request::getController();
		$res = $apsc->getResource($container);
		return $res->helionproject->aps->id;
	}
	
	###########################
	## getEndpoint
	###########################
	public function getEndPoint(){
	
		$this->logger("getEndpoint()::Start");
		//create new Openstack handler object
		$ohandle = new oshandler();
		//setup for getendpoint
		$values = '';
		//make the call to the handler to deal with call to helion
		$res = $ohandle->callOSAdmin($this->tenant->helionglobals, "getEndpoints", $this->tenant->tenantName, $values, "KeystoneService");
		$json = json_decode($res);
		if($json->{'errorCode'} == "0"){
			return $json->{'result'}->endpoint;
		}else{
			return "";
		}
	}
	
	/**
	 * @verb(GET)
	 * @path("/getFlavors")
	 * @param
	 */
	###########################
	## getFlavors
	###########################
	public function getFlavors(){
	
		$apsc = \APS\Request::getController();
		$resList = $apsc->getIo()->sendRequest(\APS\Proto::GET, "/aps/2/resources/" . $this->tenant->aps->id . "/getListFlavors");
		return $resList;
	}

	/**
	 * @verb(POST)
	 * @path("/getProjectFlavors")
	 * @param(string, body)
	 * @access(referrer, true)
	 */
	###########################
	## getProjectFlavors
	###########################
	public function getProjectFlavors($param){
		if(empty($param)){
			return "This function expects a json object containing the project aps id.";
		}
		$json = json_decode($param);
		$id = $json->aps->id;
		$apsc = \APS\Request::getController();
		$resList = $apsc->getIo()->sendRequest(\APS\Proto::GET, "/aps/2/resources/" . $id . "/flavors");
		$resList = json_decode($resList);
		return $resList;
	}
	
	/**
	 * @verb(GET)
	 * @path("/getImages")
	 * @param
	 */
	###########################
	## getImages
	###########################
	public function getImages(){
	
		$apsc = \APS\Request::getController();
		$resList = $apsc->getIo()->sendRequest(\APS\Proto::GET, "/aps/2/resources/" . $this->tenant->aps->id . "/getListImages");
	
		return $resList;
	}
	
	/**
	 * @verb(GET)
	 * @path("/getListImagesByProject")
	 * @param
	 */
	public function getListImagesByProject(){
		$apsc = \APS\Request::getController();
		$resList = $apsc->getIo()->sendRequest(\APS\Proto::GET, "/aps/2/resources/" . $this->tenant->aps->id . "/getListImagesByProject");
	
		return $resList;
	}
	
	/**
	 * @verb(GET)
	 * @path("/getNetworks")
	 * @param
	 */
	###########################
	## getNetworks 
	###########################
	public function getNetworks(){
		$apsc = \APS\Request::getController();
	
		$list = array();
	
		//iterate and get the instances associated
		for($i=0;$i<count($this->projects);$i++){
			$resList = $apsc->getIo()->sendRequest(\APS\Proto::GET, "/aps/2/resources/" . $this->projects[$i]->aps->id . "/networks");
			//turn string response to array
			$x = json_decode($resList, true);
			//iterate and push to response array
			foreach($x as $val){
				array_push($list, $val);
			}
		}
###### For show only OSA networks logic -> perhaps needed in the future but disabled for now
#		if(!$this->tenant->helionglobals->showOsNetworks){
#			$this->logger("Get networks from OS");
#			//get the networks direct from OS put into apsish format and add merge
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
		//encode back to json, then decode so it's in proper response
		return json_decode(json_encode($list));
	}
	
	private function getNetsFromOS(){
	
		$ohandle = new oshandler();
		$values = '';
		$res = $ohandle->callOSAdmin($this->tenant->helionglobals, "getListNetwork", $this->tenant->tenantName, $values, "NeutronService");
		$json = json_decode($res);
		if($json->{'errorCode'} == "0"){
			return json_encode($json->{'result'});
		}else{
			return "";
		}
	}
	
	/**
	 * @verb(POST)
	 * @path("/getProjectNetworks")
	 * @param(string, body)
	 * @access(referrer, true)
	 */
	###########################
	## getProjectNetworks
	###########################
	public function getProjectNetworks($param){
		if(empty($param)){
			return "This function expects a json object containing the project aps id.";
		}
		$json = json_decode($param);
		$id = $json->aps->id;
		$apsc = \APS\Request::getController();
		$resList = $apsc->getIo()->sendRequest(\APS\Proto::GET, "/aps/2/resources/" . $id . "/networks");
		$resList = json_decode($resList);
		return $resList;
	}
	
	/**
	 * @verb(POST)
	 * @path("/getProjectPolicies")
	 * @param(string, body)
	 * @access(referrer, true)
	 */
	###########################
	## getProjectPolicies
	###########################
	public function getProjectPolicies($param){
		if(empty($param)){
			return "This function expects a json object containing the project aps id.";
		}
		$json = json_decode($param);
		$id = $json->aps->id;
		$apsc = \APS\Request::getController();
		$resList = $apsc->getIo()->sendRequest(\APS\Proto::GET, "/aps/2/resources/" . $id . "/policies");
		$resList = json_decode($resList);
		return $resList;
	}
	
	/**
	 * @verb(GET)
	 * @path("/getPolicies")
	 */
	###########################
	## getPolicies
	###########################
	public function getPolicies(){
	
		$this->logger("getPolicies()::Start");
		$apsc = \APS\Request::getController();
		$resList = array();
		$tenant = $apsc->getResource($this->tenant->aps->id);
		$res = $tenant->getUserPolicies($this->aps->id);
	
		return json_decode(json_encode($res));
	}
	
	/**
	 * @verb(GET)
	 * @path("/getProjects")
	 * @param
	 */
	###########################
	## getProjects
	###########################
	public function getProjects(){

		$apsc = \APS\Request::getController();
      	$resList = $apsc->getIo()->sendRequest(\APS\Proto::GET, "/aps/2/resources/". $this->aps->id ."/projects");
		$resList = json_decode($resList);

		return $resList;
	}

	/**
	 * @verb(GET)
	 * @path("/getssoparam")
	 * @param
	 */
	public function getssoparam(){
		return $this->user->password;
	}
	
	/**
	 * @verb(POST)
	 * @path("/getUploadCommand")
	 * @param(string, body)
	 * @access(referrer, true)
	 */
	###########################
	## getUploadCommand
	###########################
	public function getUploadCommand($param){
		//here we are constructing a curl command from the container name,
		//project id, token code, and endpoint. It will look a bit like:
		//curl -X GET -i -H "X-Auth-Token: <token>" <endpoint><project id>/<container name>
	
		$this->logger("getUploadCommand()::Start with:::> ". $param);
		if(empty($param)){
			return "This function expects a json object containing the container aps id.";
		}
	
		$json = json_decode($param);
		$id = $json->aps->id;
		$s = "";
		if(!is_null($id)){
			$s = $id[0];
		}
		$apsc = \APS\Request::getController();
		$res = $apsc->getResource($s);
		if(empty($res)){
			return "Error: Unable to get the container resource.";
		}
		$pn = $res->helionproject->projectName;
	
		$cnt_name = urlencode($res->containername);
		if(preg_match('/\s/',$res->containername)){
			$cnt_name = str_replace(" ", "%20", $res->containername);
		}
		return "curl -X PUT -i -H \"X-Auth-Token: " .$this->getToken($pn)."\"  -T <your_file> ". $this->getEndPoint().$res->helionproject->openstackId."/".$cnt_name."/<your_file_name>";
	}
	
	/**
	 * @verb(GET)
	 * @path("/getVMS")
	 * @param
	 */
	###########################
	## getVMList
	###########################
	public function getVMS(){
	
		$this->logger("getVMS()::Start");
		$apsc = \APS\Request::getController();
		
		//get the user's projects
		$projs = $this->getProjects();
		//array to hold all instances to return
		$list = array();
 
		//iterate and get the instances associated
		for($i=0;$i<count($projs);$i++){
			$resList = $apsc->getIo()->sendRequest(\APS\Proto::GET, "/aps/2/resources/" . $projs[$i]->aps->id . "/projectinstances");
			//turn string response to array
			$x = json_decode($resList, true);
			//iterate and push to response array
			foreach($x as $val){
				array_push($list, $val);
			}
		}
		//encode back to json, then decode so it's in proper response
		return json_decode(json_encode($list)); 
	}

	/**
	 * @verb(POST)
	 * @path("/deleteVM")
	 * @param(string, body)
	 * @access(referrer, true)
	 */
	###########################
	## deleteVM
	###########################
	public function deleteVM($vm){
	
		$this->logger("deleteVM()::Start with:::> ". $vm);
		if(empty($vm)){
			return "This function expects a json object containing the id of the virtual intance.";
		}
		
		$msg = "";
		$param = json_decode($vm);
		$apsc = \APS\Request::getController();

		$res = $apsc->getResource($param->aps->id);
		if(empty($res)){
			return "Unable to access the id from the provided input.";
		}
		$x = $apsc->getIo()->sendRequest(\APS\Proto::DELETE, "/aps/2/resources/" . $param->aps->id);

		return "Deletion of the virtual instance completed";
	}

	/**
	 * @verb(POST)
	 * @path("/createVM")
	 * @param(string, body)
	 * @access(referrer, true)
	 */
	###########################
	## createVM
	###########################
	public function createVM($vm){
	
		$this->logger("createVM()::Start with:::> ". $vm);
		$vm = json_decode($vm);
	
		$apsc = \APS\Request::getController();
		$project = $apsc->getResource($vm->helionproject->aps->id);
	 	$resource = \APS\TypeLibrary::newResourceByTypeId('http://hp.com/helioninstance/1.1');
	
		$resource->instancename = $vm->instancename;
		$resource->image = $vm->image;
		$resource->flavor = $vm->flavor;
		$resource->zone = "nova";
		$resource->instancecount = 1;
		$resource->network = $vm->network;
		$resource->opolicies = $vm->opolicies;
	
		$apsc2 = $apsc->impersonate($this->tenant->aps->id);
		$apsc2->linkResource($project, 'projectinstances', $resource);
	
		return 0;
	}

	/**
	 * @verb(POST)
	 * @path("/getVmState")
	 * @param(string, body)
	 * @access(referrer, true)
	 */
	###########################
	## getVMState
	###########################
	public function getVmState($param){
	
		$this->logger("getVmState()::Start with:::> ". $param);
		if(empty($param)){
			return "This function expects a json object containing the id of the virtual intance.";
		}
	
		$msg = "";
		$param = json_decode($param);
		$apsc = \APS\Request::getController();
		$res = $apsc->getResource($param->aps->id);
		if(empty($res)){
			return "Unable to access the id from the provided input.";
		}
		$tmp = $res->getState();
		$this->logger("Returning [". $tmp. "] to the client");
	
		return $res->getState();
	}
	
	/**
	 * @verb(POST)
	 * @path("/restartVM")
	 * @param(string, body)
	 * @access(referrer, true)
	 */
	###########################
	## restartVM
	###########################
	public function restartVM($param){
	
		$this->logger("restartVM()::Start with:::> ". $param);
		if(empty($param)){
			return "This function expects a json object containing the id of the virtual intance.";
		}
		$msg = "";
		$param = json_decode($param);
		$apsc = \APS\Request::getController();
		$res = $apsc->getResource($param->aps->id);
		if(empty($res)){
			return "Unable to access the id from the provided input.";
		}
			
		$res->restart();
		//create the synch state task
		$resource = \APS\TypeLibrary::newResourceByTypeId('http://hp.com/instancetask/1.0');
		$resource->taskname = "restart vm";
		$resource->currentState = $res->instancestatus;
		$resource->expectedState = "Running";
		//and link instance with task
		$apsc2 = $apsc->impersonate($this->tenant->aps->id);
		$apsc2->linkResource($res, 'tasks', $resource);
		return "Restart of the virtual instance has been requested.";
	}

	/**
	 * @verb(POST)
	 * @path("/startVM")
	 * @param(string, body)
	 * @access(referrer, true)
	 */
	###########################
	## startVM
	###########################
	public function startVM($param){
	
		$this->logger("startVM()::Start with:::> ". $param);
		if(empty($param)){
			return "This function expects a json object containing the id of the virtual intance.";
		}

		$param = json_decode($param);
		$apsc = \APS\Request::getController();
		$res = $apsc->getResource($param->aps->id);
		if(empty($res)){
			return "Unable to access the id from the provided input.";
		}
			
		$apsc->getIo()->sendRequest(\APS\Proto::PUT, "/aps/2/resources/" . $param->aps->id. "/start");
		//create the synch state task
		$resource = \APS\TypeLibrary::newResourceByTypeId('http://hp.com/instancetask/1.0');
		$resource->taskname = "start vm";
		$resource->currentState = $res->instancestatus;
		$resource->expectedState = "Running";
		//and link instance with task
		$apsc2 = $apsc->impersonate($this->tenant->aps->id);
		$apsc2->linkResource($res, 'tasks', $resource);
		
		return "Start of the virtual instance has been requested.";
	}
	
	/**
	 * @verb(POST)
	 * @path("/stopVM")
	 * @param(string, body)
	 * @access(referrer, true)
	 */
	###########################
	## stopVM
	###########################
	public function stopVM($param){
	
		$this->logger("stop()::Start with:::> ". $param);
		if(empty($param)){
			return "This function expects a json object containing the id of the virtual intance.";
		}
		$param = json_decode($param);
		$apsc = \APS\Request::getController();
		$res = $apsc->getResource($param->aps->id);
		if(empty($res)){
			return "Unable to access the id from the provided input.";
		}
		$res->stop();
		
		//create the synch state task
		$resource = \APS\TypeLibrary::newResourceByTypeId('http://hp.com/instancetask/1.0');
		$resource->taskname = "stop vm";
		$resource->currentState = $res->instancestatus;
		$resource->expectedState = "Shutdown";
		//and link instance with task
		$apsc2 = $apsc->impersonate($this->tenant->aps->id);
		$apsc2->linkResource($res, 'tasks', $resource);
		
		return "Stop of the virtual instance has been requested.";
	}
	
	/**
	 * @verb(POST)
	 * @path("/softresetVM")
	 * @param(string, body)
	 * @access(referrer, true)
	 */
	###########################
	## softresetVM
	###########################
	public function softresetVM($param){
	
		$this->logger("softresetVM()::Start with:::> ". $param);
		if(empty($param)){
			return "This function expects a json object containing the id of the virtual intance.";
		}
		$param = json_decode($param);
		$apsc = \APS\Request::getController();
		$res = $apsc->getResource($param->aps->id);
		if(empty($res)){
			return "Unable to access the id from the provided input.";
		}
		$res->softreset();	
		
		//create the synch state task
		$resource = \APS\TypeLibrary::newResourceByTypeId('http://hp.com/instancetask/1.0');
		$resource->taskname = "softreset vm";
		$resource->currentState = $res->instancestatus;
		$resource->expectedState = "Running";
		//and link instance with task
		$apsc2 = $apsc->impersonate($this->tenant->aps->id);
		$apsc2->linkResource($res, 'tasks', $resource);

		return "Soft reset of the virtual instance has been requested.";
	}
	
	/**
	 * @verb(POST)
	 * @path("/hardresetVM")
	 * @param(string, body)
	 * @access(referrer, true)
	 */
	###########################
	## hardresetVM
	###########################
	public function hardresetVM($param){
	
		$this->logger("hardresetVM()::Start with:::> ". $param);
		if(empty($param)){
			return "This function expects a json object containing the id of the virtual intance.";
		}
		$msg = "";
		$param = json_decode($param);
		$apsc = \APS\Request::getController();
		$res = $apsc->getResource($param->aps->id);
		if(empty($res)){
			return "Unable to access the id from the provided input.";
		}
		$res->hardreset();	
		
		//create the synch state task
		$resource = \APS\TypeLibrary::newResourceByTypeId('http://hp.com/instancetask/1.0');
		$resource->taskname = "hardreset vm";
		$resource->currentState = $res->instancestatus;
		$resource->expectedState = "Running";
		//and link instance with task
		$apsc2 = $apsc->impersonate($this->tenant->aps->id);
		$apsc2->linkResource($res, 'tasks', $resource);
		
		return "Hard reset of the virtual instance has been requested.";
	}
	
	/**
	 * @verb(POST)
	 * @path("/createSnapshotVM")
	 * @param(string, body)
	 * @access(referrer, true)
	 */
	###########################
	## createSnapshotVM
	###########################
	public function createSnapshotVM($param){

		$this->logger("createSnapshotVM()::Start with:::> ". $param);	
		if(empty($param)){
			return "This function expects a json object containing the id of the virtual intance and a snapshot name.";
		}
		$msg = "";
		$param = json_decode($param);
		$apsc = \APS\Request::getController();
		$res = $apsc->getResource($param->aps->id);
		if(empty($res)){
			return "Unable to access the id from the provided input.";
		}
		$res->createSnapshot($param->snapshotName);	
				
		return "Create snapshot of virtual instance has been requested.";
	}
	 
	/**
	 * @verb(POST)
	 * @path("/createContainer")
	 * @param(string, body)
	 * @access(referrer, true)
	 */
	###########################
	## createContainer - Object Storage
	###########################
	public function createContainer($containername){
	
		$this->logger("createContainer()::Start with:::> ". $containername);
		$containername = json_decode($containername);
		$apsc = \APS\Request::getController();
		$project = $apsc->getResource($containername->helionproject->aps->id);
	 	$resource = \APS\TypeLibrary::newResourceByTypeId('http://hp.com/helionobjectstorecontainer/1.0');
	
		$resource->containername = $containername->containername;
		$resource->containerreadacl = $containername->containerreadacl;
		$resource->containerwriteacl = $containername->containerwriteacl;
		$resource->containermetadataone = $containername->containermetadataone;
		$resource->containermetadatatwo = $containername->containermetadatatwo;
		$resource->containermetadatathree = $containername->containermetadatathree;
		$resource->containerstatus = "Provisioning";
	
		$apsc2 = $apsc->impersonate($this->tenant->aps->id);
		$apsc2->linkResource($project, 'objectstorecontainers', $resource);
	
		return 0;
	}
	
	/**
	 * @verb(POST)
	 * @path("/deleteContainer")
	 * @param(string, body)
	 * @access(referrer, true)
	 */
	###########################
	## deleteContainer - Object Storage
	###########################
	public function deleteContainer($containername){
	
		$this->logger("deleteContainer()::Start with:::> ". $containername);
		$msg = "";
		$param = json_decode($containername);
		$id = $param->aps->id;
		$s = "";
		if(!is_null($id)){
			$s = $id[0];
		}
		$apsc = \APS\Request::getController();
		$res = $apsc->getResource($s);
		if(empty($res)){
			return "Unable to access the id from the provided input.";
		}
		$x = $apsc->getIo()->sendRequest(\APS\Proto::DELETE, "/aps/2/resources/" . $s);

		return "Deletion of the object container completed";
	}
	
	/**
	 * @verb(POST)
	 * @path("/createObject")
	 * @param(string, body)
	 * @access(referrer, true)
	 */
	###########################
	## createObject - Object Storage
	###########################
	public function createObject($objectname){
	
		$this->logger("createObject()::Start with:::> ". $objectname);
		$objectname = json_decode($objectname);
		$apsc = \APS\Request::getController();
		$project = $apsc->getResource($objectname->helionproject->aps->id);
	 	$resource = \APS\TypeLibrary::newResourceByTypeId('http://hp.com/helionobjectstoreobject/1.0');
		$resource->objectname = $containername->objectname;
		$apsc2 = $apsc->impersonate($this->tenant->aps->id);
		$apsc2->linkResource($project, 'projectinstances', $resource);
	
		return 0;
	}
	
	/**
	 * @verb(POST)
	 * @path("/deleteObject")
	 * @param(string, body)
	 * @access(referrer, true)
	 */
	###########################
	## deleteObject - Object Storage
	###########################
	public function deleteObject($objectname){
	
		$this->logger("deleteObject()::Start with:::> ". $objectname);
		$objectname = json_decode($objectname);
		$apsc = \APS\Request::getController();
		$project = $apsc->getResource($objectname->helionproject->aps->id);
	 	$resource = \APS\TypeLibrary::newResourceByTypeId('http://hp.com/helionobjectstoreobject/1.0');
		$resource->objectname = $containername->objectname;
		$apsc2 = $apsc->impersonate($this->tenant->aps->id);
		$apsc2->unregisterResource($resource);
	
		return 0;
	}
	
	/**
	 * @verb(GET)
	 * @path("/getContainers")
	 * @param
	 */
	###########################
	## getContainers
	###########################
	public function getContainers(){
	
	//to do iterate users list of projects and return only containers within
		$this->logger("getContainers()::Start");
		$apsc = \APS\Request::getController();
		//get the user's projects
		$projs = $this->getProjects();
		//array to hold all instances to return
		$list = array();
		
		//iterate and get the containers associated per project
		for($i=0;$i<count($projs);$i++){
			$resList = $apsc->getIo()->sendRequest(\APS\Proto::GET, "/aps/2/resources/" . $projs[$i]->aps->id . "/objectstorecontainers");
			//turn string response to array
			$x = json_decode($resList, true);
			//iterate and push to response array
			foreach($x as $val){
				array_push($list, $val);
			}
		}
		$this->logger("getContainers()::end");
		//encode back to json, then decode so it's in proper response
		return json_decode(json_encode($list));
	}
	
	/**
	 * @verb(POST)
	 * @path("/getObjects")
	 * @param(string, body)
	 * @access(referrer, true)
	 */
	###########################
	## getObjects
	###########################
	public function getObjects($container){
		
		$this->logger("getObjects()::Start with:::> ". $container);
		$json = json_decode($container);
		$id = $json->aps->id;
		$s = "";
		if(!is_null($id)){
			$s = $id[0];
		}
		$apsc = \APS\Request::getController();
		$cont = $apsc->getResource($s);
		//$this->logger("Project Name: " . $cont->helionproject->projectName);
		//create new Openstack handler object
		$ohandle = new oshandler();
		//setup for add project to helion
		$values = '"container_name": "'.$cont->containername.'"';
		//make the call to the handler to deal with call to helion
		$res = $ohandle->callOSAdmin($this->tenant->helionglobals, "list_objects", $cont->helionproject->projectName, $values, "SwiftService");
		$json = json_decode($res);
		if($json->{'errorCode'} == "0"){
			return json_encode($json->{'result'});
		}else{
			return "";
		}
	}
	
	###########################
	## getToken
	###########################
	private function getToken($project){
		
		//create new Openstack handler object
		$ohandle = new oshandler();
		//setup for enable helion user
		$values = '';
		//make the call to the handler to deal with call to helion
		$res = $ohandle->callOSAdmin($this->tenant->helionglobals, "getToken", $project, $values, "KeystoneService");
			
		$json = json_decode($res);
		if($json->{'errorCode'} == "0"){
			return $json->{'result'}->token;
		}else{
			throw Exception("Unable to get token from Openstack");
		}
	}
	
	#############################################################################################################################################
    # Private Methods
        private $logger;
        private $ldap_binda;
        private $make_ssha_password;
        private $returnbytesquota;
        private $provisionLdap;
        private $provisionHelion;
        private $unprovisionLdap;
        private $unprovisionHelion;
        private $enableHelion;
        private $disableHelion;
        private $enableLdap;
        private $disableLdap;
        private $getToken;
        private $getContainerProjectId;
        private $getEndPoint;
	################################################################################################################################################

	function logger($message){
			 ## Function to write to logging file using Logger class
			 $requestor=$_SERVER['REMOTE_ADDR'];
			 $log = new Logging();
			 $log->lwrite($requestor.":".$message);
			 $log->lclose();
	   }

	function ldap_binda(){
		putenv('LDAPTLS_REQCERT=never');

		$ldap_addr = 'ldaps://'.$this->tenant->helionglobals->LDAPIP;
		$ldap_conn = ldap_connect($ldap_addr) or die("Couldn't connect!");
		ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
		$ldap_rdn = $this->tenant->helionglobals->LDAPUSER;
		$ldap_pass = $this->tenant->helionglobals->LDAPPASS;
		$flag_ldap = ldap_bind($ldap_conn,"cn=$ldap_rdn,dc=".$this->tenant->helionglobals->DC1.",dc=".$this->tenant->helionglobals->DC2,$ldap_pass);
		return $ldap_conn;
	}

	function make_ssha_password($password){
		mt_srand((double)microtime()*1000000);
		$salt = pack("CCCC", mt_rand(), mt_rand(), mt_rand(), mt_rand());
		$hash = "{SSHA}" . base64_encode(pack("H*", sha1($password . $salt)) . $salt);
		return $hash;
	}

	###########################
	## enable Helion
	###########################
	private function enableHelion(){
		
		//create new Openstack handler object
		$ohandle = new oshandler();
		//setup for enable helion user
		$values = '"name": "'. $this->user->login.'"';
		//make the call to the handler to deal with call to helion
		$res = $ohandle->callOSAdmin($this->tenant->helionglobals, "enableUser", $this->tenant->tenantName, $values, "KeystoneService");
		 
		$json = json_decode($res);
		if($json->{'errorCode'} == "0"){
			$this->logger("enableOpenStackUser was successful");
		}else{
			$this->logger("enableOpenstackUser was not successful. ". $json->{'errorMsg'});
		}
		$this->logger("enableOpenStackUser()::End\n");
	}
	
	###########################
	## disable Helion
	###########################
	private function disableHelion(){
		
		//create new Openstack handler object
		$ohandle = new oshandler();
		//setup for disable helion user
		$values = '"name": "'. $this->user->login.'"';
		//make the call to the handler to deal with call to helion
		$res = $ohandle->callOSAdmin($this->tenant->helionglobals, "disableUser", $this->tenant->tenantName, $values, "KeystoneService");
		$json = json_decode($res);
		if($json->{'errorCode'} == "0"){
			$this->logger("disableOpenStackUser was successful");
		}else{
			$this->logger("disableOpenstackUser was not successful. ". $json->{'errorMsg'});
		}
		$this->logger("disableOpenStackUser()::End\n");
	}
	
	###########################
	## enable Ldap
	###########################
	private function enableLdap(){
		
		if($this->tenant->helionglobals->ldapEnabled){
			$this->logger("A Customer is requesting to enable the user".$this->user->login);

			$ldap_conn = $this->ldap_binda();
			if($ldap_conn){
				$old="uid=suspended_".$this->user->login.",ou=users,dc=".$this->tenant->helionglobals->DC1.",dc=".$this->tenant->helionglobals->DC2;
				$new="uid=".$this->user->login;
				$scope="ou=users,dc=".$this->tenant->helionglobals->DC1.",dc=".$this->tenant->helionglobals->DC2;
				$adding=ldap_rename($ldap_conn,$old,$new,$scope,TRUE);
				return ldap_error($ldap_conn);

			}
			else{
				throw new Exception("Error connecting to Ldap when disabling user");
			}
		}
	}
	
	###########################
	## disable Ldap
	###########################
	private function disableLdap(){

		if($this->tenant->helionglobals->ldapEnabled){
			$ldap_conn = $this->ldap_binda();
			if($ldap_conn){
				$old="uid=".$this->user->login.",ou=users,dc=".$this->tenant->helionglobals->DC1.",dc=".$this->tenant->helionglobals->DC2;
				$new="uid=suspended_".$this->user->login;
				$scope="ou=users,dc=".$this->tenant->helionglobals->DC1.",dc=".$this->tenant->helionglobals->DC2;
				$adding=ldap_rename($ldap_conn,$old,$new,$scope,TRUE);
				return ldap_error($ldap_conn);
			}
			else{
				$this->logger("Error connecting to LDAP when disabling user ".$this->user->login);
				throw new Exception("Error connecting to Ldap when disabling user");
			}
		}
	}
	
	###########################
	## UnProvison Helion
	###########################
	function unprovisionHelion(){
		
		//create new Openstack handler object
		$ohandle = new oshandler();
		//setup for remove helion user
		$values = '"name": "'. $this->user->login.'"';
		//make the call to the handler to deal with call to helion
		$this->logger("unprovisionOpenStackUser():: prepared to execute call to python");
		$ohandle->callOSAdmin($this->tenant->helionglobals, "deleteUser", $this->tenant->tenantName, $values, "KeystoneService");
		$this->logger("unprovisionOpenStackUser()::End\n");
	}

	###########################
	## UnProvison LDAP
	###########################
	function unprovisionLdap(){

		if($this->tenant->helionglobals->ldapEnabled){
			$this->logger("We are requiered to unprovision the user".$this->user->login);
		
			if ($this->userstatus == "Provisioning"){
				throw new Exception ("User has not been provisioned yet");
			}

			$ldap_conn = $this->ldap_binda();
			if($ldap_conn){
				$wasdeleted=0;
				try{
					$adding=ldap_delete($ldap_conn,"uid=".$this->user->login.",ou=users,dc=".$this->tenant->helionglobals->DC1.",dc=".$this->tenant->helionglobals->DC2);
				}
				catch(Exception $e){
					$this->logger("Seams user was already deleted");
					$wasdeleted=1;
				}
				$allok=0;
				if(ldap_error($ldap_conn) == "Success"){
					$allok=1;
				}
				else{
					try{
						$adding=ldap_delete($ldap_conn,"uid=suspended_".$this->user->login.",ou=users,dc=".$this->tenant->helionglobals->DC1.",dc=".$this->tenant->helionglobals->DC2);
					}
					catch(Exception $e){
						$this->logger("Seams user was already deleted");
					}
					if(ldap_error($ldap_conn) == "Success"){
						$allok=1;
					}
				}
				if($allok==1){
					$add2["memberUid"]=$this->user->login;
					try{
						$adding2=ldap_mod_del($ldap_conn,"cn=".$this->tenant->tenantName.",ou=Group,dc=".$this->tenant->helionglobals->DC1.",dc=".$this->tenant->helionglobals->DC2,$add2);
					}
					catch(Exception $e){
						if($wasdeleted=0){
							$this->logger("For some reason the user was deleted and was not existing in group");
						}
						else{
							$this->logger("User was deleted in another place....");
						}
					}
				}
				else{
					$this->logger("Error deleting user ".$this->user->login);
				}
			}
			else{
				$this->logger("Unprovision had issue to connect to LDAP");
				throw new Exception("Error while deleting user, please contact your provider");
			}	
		}
	}
	
	###########################
	## Provison Helion
	###########################
	function provisionHelion(){
		
		//create new Openstack handler object
		$ohandle = new oshandler();
		//setup for remove helion user
		$values = '"name": "'. $this->user->login. '", 
							"password": "'. $this->user->password. '", 
							"email": "'. $this->user->email . '"';
		//make the call to the handler to deal with call to helion
		$this->logger("provisionOpenStackUser():: prepared to execute call to python");
		$ohandle->callOSAdmin($this->tenant->helionglobals, "createUser", $this->tenant->tenantName, $values, "KeystoneService");
		
		$this->logger("provisionOpenStackUser()::End\n");
		
		$this->userusage=0;
		$this->userstatus="Ready";
		$this->serviceUserId=$this->user->aps->id;
		
		$sub = new \APS\EventSubscription(\APS\EventSubscription::Changed, "onUserChange");
		$sub->source->id=$this->user->aps->id;
		$apsc = \APS\Request::getController();
		$subscriptionnotifications = $apsc->subscribe($this, $sub);
		$this->logger("We Subscribed to notifications for user change: ".print_r($subscriptionnotifications,true));
		$this->userstatus="Ready";
		$this->userusage=0;
		return;
	}

	###########################
	## Provison LDAP
	###########################
	function provisionLdap(){

		if($this->tenant->helionglobals->ldapEnabled){
			$ldap_conn = $this->ldap_binda();
			if($ldap_conn){
				$add["uid"]=$this->user->login;
				$add["cn"]=$this->user->login." ".$this->user->login;
				$add["objectClass"][0]="account";
				$add["objectClass"][1]="posixAccount";
				$add["objectClass"][2]="top";
				$password=$this->make_ssha_password($this->user->password);
				$add["userPassword"]=$password;
				$add["description"]=$this->user->displayName;
					
				if(!$this->user->email || strlen($this->user->email) < 3){
					$add["emailAddress"]=$this->user->login;
				}
				else{
					$add["emailAddress"]=$this->user->email;
				}
			
				$add["HelionDiskQuota"]=0;
				$add["HelionNetQuota"]=0;
				$add["HelionCpuQuota"]=0;
	
				$home = substr($this->user->login, 0, strrpos($this->user->login, "@"));
				$add["homeDirectory"]="/users/".$home;
				$adding=ldap_add($ldap_conn,"uid=".$this->user->login.",ou=users,dc=".$this->tenant->helionglobals->DC1.",dc=".$this->tenant->helionglobals->DC2,$add);
				if($adding==true){
					$add2["memberUid"]=$this->user->login;
					$adding2=ldap_mod_add($ldap_conn,"cn=".$this->tenant->tenantName.",ou=Group,dc=".$this->tenant->helionglobals->DC1.",dc=".$this->tenant->helionglobals->DC2,$add2);
					if($adding2==true){
						$this->logger("User ".$this->user->login." has been provisioned");
						$this->logger("Returning: ".print_r(array($this->user->login,'helionusername'=>$this->user->login, 'Status' => 'Ready', ),true));
						$this->helionusername=$this->user->login;
						$this->heliondiskquota=0;
						$this->helionnetquota=0;
						$this->helioncpuquota=0;
						$this->userusage=0;
						$this->userstatus="Ready";
						$this->serviceUserId=$this->user->aps->id;
			
						$sub = new \APS\EventSubscription(\APS\EventSubscription::Changed, "onUserChange");
						$sub->source->id=$this->user->aps->id;
						$apsc = \APS\Request::getController();
						$subscriptionnotifications = $apsc->subscribe($this, $sub);
						$this->logger("We Subscribed to notifications, the result of sending this to controller returned: ".print_r($subscriptionnotifications,true));
						$this->userstatus="Ready";
						$this->userusage=0;
						return;
					}
					else{
						$this->logger("We had a problem trying to add the user".$this->user->login." to it's group ".$this->tenant->tenantName);
						throw new Exception("Error adding user to group");
					}
				}
				else{
					$this->logger("We had a problem trying to add user".$this->user->login);
					throw new Exception("Error adding user");
				}
			}
			else{
				$this->logger("Provision function had issue connecting to ldap while provisioning user ".$this->user->login);
				throw new Exception("Error Connecting to LDAP");
			}
		}
	}

	###########################
	## ReturnBytes QUOTA
	###########################
	function returnbytesquota($gigas){
		$output=$gigas*1024*1024*1024;
		return $output;
	}
}
?>
