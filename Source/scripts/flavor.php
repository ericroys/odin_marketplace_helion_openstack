<?php
#############################################################################################################################################
# We are using the standard logger function and APS runtime Libraries along with handler for os calls/responses
#############################################################################################################################################
require_once "logger.php";
require_once "aps/2/runtime.php";
require_once "oshandler.php";
#############################################################################################################################################
# We are setting up a flavor which represents a flavor in openstack.  
#############################################################################################################################################

/**
* Class flavor
* @type("http://hp.com/flavor/1.0")
* @implements("http://aps-standard.org/types/core/resource/1.0")
*/

class flavor extends APS\ResourceBase
{
	#############################################################################################################################################
	#  Link to projects and virtual instance object types. Linked to tenant for overall scope.  
	#############################################################################################################################################

	/**
	* @link("http://hp.com/project/3.0[]")
	*/
	public $projects;

	/**
	 * @link("http://hp.com/heliontenant/3.0")
	 * @required
	 */
	public $heliontenant;
	
###############################
#  General Class Attributes
###############################
	/**
	* @type(string)
	* @title("Flavor Name")
	* @required
	*/
	public $flavorName;
	
	/**
	 * @type("integer")
	 * @title("cpus")
	 * @required
	 */
	public $cpus;
		
	/**
	 * @type("integer")
	 * @title("Ram(MB)")
	 * @Description("Ram(MB)")
	 * @required
	 */
	public $ram;
	
	/**
	 * @type("integer")
	 * @title("Root Disk(GB)")
	 * @Description("Root Disk(GB)")
	 * @required
	 */
	public $rootdisk;
	
	/**
	 * @type("integer")
	 * @title("Ephemeral Disk (GB)")
	 * @Description("Ephemeral Disk (GB)")
	 * @required
	 */
	public $ephemeraldisk;
	
	/**
	 * @type("integer")
	 * @title("Swap Disk (MB)")
	 * @Description("Swap Disk (MB)")
	 * @required
	 */
	public $swapdisk;
	
	/**
	 * @type(string)
	 * @title("Openstack Id")
	 * @Description("Openstack Identifier")
	 */
	public $openstackId;
	
	/**
	 * @type("integer")
	 * @title("Create Flag")
	 * @Description("Flag to bypass OS provision")
	 */
	public $zzBypassOsProvision;

	/**
	* @type(number)
	* @description("Flag for PBA Billing Verification.  If PBA Billing in Place we set to true, otherwise remains false")
	**/
	public $billing=0;
	

	public function projectLink(){}
	public function projectUnlink(){}
	public function instancesLink(){}
	public function instancesUnlink(){}


	#############################################################################################################################################
	# CRUD Operations on APS Type
	#		* configure - Modify Existing Instance
	#		* provision - New object created
	#		* unprovision - Delete Object
	#		* retrieve - Read Object
	#############################################################################################################################################
	
	public function provision(){
		
		$this->logger("provision call provisionOpenstackFlavor()...");
		$this->provisionOpenStackFlavor();
		$this->logger("Provision Complete: ".$this->flavorName);	
	}

	public function configure($new){
		$this->_copy($new);
	}

	public function unprovision(){
	
		$this->logger("unprovision call unprovisionOpenStackFlavor()");
		$this->unprovisionOpenStackFlavor();
		$this->logger("UnProvision Complete :\n\t".$this->projectName);	
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
	function enable(){}

	/**
	* We define operation for disable
	* @verb(PUT)
	* @path("/disable")
	* @param()
	*/
	function disable(){}

	public function __construct(){}
	public function log($what){}
	public function count(){}

	public function retrieve(){
		$this->count();
		$this->log("retrieve");
	}

	#############################################################################################################################################
	## Support functions for this class
	#############################################################################################################################################
	private $isDebugEnabled;
	private $logger;
	private $provisionOpenStackFlavor;
	private $unprovisionOpenStackFlavor;
	
	function isDebugEnabled(){}

	function logger($message){
        $requestor=$_SERVER['REMOTE_ADDR'];
        $log = new Logging();
        $log->lwrite($requestor.":".$message);
        $log->lclose();
    }

	function provisionOpenStackFlavor() {
		# Code to create OpenStack Project / Tenant - Will use the tenantName (No Spaces) by default
		
		$this->logger("provisionOpenStackFlavor()::Start...");
		$ohandle = new oshandler();
		//setup for create helion project
		$values = '"name": "'.$this->flavorName.'", 
					"cpu": "'.$this->cpus.'",
					"disk": "'.$this->rootdisk.'",
					"swap": "'.$this->swapdisk.'",
					"ephemeral": "'.$this->ephemeraldisk.'",
					"ram":"'.$this->ram.'"';
		$res = $ohandle->callOSAdmin($this->heliontenant->helionglobals, "createFlavor", $this->heliontenant->tenantName, $values, "NovaService");

		$this->logger("provisionOpenStackFlavor():: executed call to python");
		$json = json_decode($res);
		if($json->{'errorCode'} == "0"){
			$this->openstackId = $json->{'result'}->id;
		}else{
			$this->status = "Error";
			$this->logger("provisionOpenStackFlavor():: End Error");
			throw new \Rest\Exception(400, "Unable to process: " . $json->{'errorMsg'});
		}
		$this->logger("provisionOpenStackFlavor():: End");		
		return;
	}
	
	function unprovisionOpenStackFlavor() {
		# Code to create OpenStack Project / Tenant - Will use the HELIONDESC (No Spaces) by default
		$this->logger("unprovisionOpenStackFlavor()::Start...\n");
		$ohandle = new oshandler();
		//setup for delete helion project
		$values = '"flavorId": "'.$this->openstackId.'"';
		$res = $ohandle->callOSAdmin($this->heliontenant->helionglobals, "deleteFlavor", $this->heliontenant->tenantName, $values, "NovaService");
		$this->logger("unprovisionOpenStackFlavor()::End\n");
		return;
	}
	
	/**
	 * @verb(POST)
	 * @path("/associateProject")
	 * @param(string, body)
	 * @access(referrer, true)
	 */
	###########################
	## associateProject
	###########################
	public function associateProject($param){
	
		$this->logger("AssociateProject::Start with:::> ". $param);
		$param = json_decode($param);
		$tmp = $param->project;
		$apsc = \APS\Request::getController();
		$project = $apsc->getResource($tmp);
		$this->createProjectFlavorLinkOS($project->openstackId);
		$apsc->linkResource($project, 'flavors', $this);
	
		return "Added project to flavor access";
	}
	
	/**
	 * @verb(POST)
	 * @path("/createProjectFlavorLinkOS")
	 * @param(string,body)
	 */
	###########################
	## createProjectFlavorLinkOS
	###########################
	private function createProjectFlavorLinkOS($param){
	
		$this->logger("createProjectFlavorLinkOS():: Start");
		$tmp = $param;
		//create new Openstack handler object
		$ohandle = new oshandler();
		//setup for add project to helion
		$values =	'"flavorId": "'.$this->openstackId.'",
					"projectName":"'.$tmp.'"';
		//make the call to the handler to deal with call to helion
		$this->logger("createProjectFlavorLinkOS():: prepared to execute call to python");
		$res = $ohandle->callOSAdmin($this->heliontenant->helionglobals, "setFlavorAccess", $this->heliontenant->tenantName, $values, "NovaService");
		$json = json_decode($res);
	
		if($json->{'errorCode'} == "0"){
			$this->logger("createProjectFlavorLinkOS() Successful::End\n");
			return;
		}else{
			$this->logger("createProjectFlavorLinkOS() Failed::End\n");
			throw new \Rest\RestException(400, "Unable to add project to flavor access. " .$json->{'errorMsg'});
		}
	}
	
	/**
	 * @verb(POST)
	 * @path("/unlinkProject")
	 * @param(string, body)
	 * @access(referrer, true)
	 */
	###########################
	## unlinkProjectFlavor
	###########################
	public function unlinkProject($param){
	
		$this->logger("unlinkProject()::Start with:::> ". $param);
		$param = json_decode($param);
		$tmp = $param->project;
	
		$apsc = \APS\Request::getController();
		$project = $apsc->getResource($tmp);
	
		$this->removeFlavorProjectLinkOS($project->openstackId);
		$apsc->getIo()->sendRequest(\APS\Proto::DELETE, "/aps/2/resources/" . $tmp. "/flavors/".$this->aps->id);
	
		return "Removed project from flavor access";
	}
	
	/**
	 * @verb(POST)
	 * @path("/removeFlavorProjectLinkOS")
	 * @param(string,body)
	 */
	###########################
	## removeFlavorProjectLinkOS
	###########################
	private function removeFlavorProjectLinkOS($param){
	
		//create new Openstack handler object
		$ohandle = new oshandler();
		//setup for add project to helion
		$values = '"projectName": "'.$param.'",
					"flavorId":"'.$this->openstackId.'"';
		//make the call to the handler to deal with call to helion
		$this->logger("removeFlavorProjectLinkOS():: prepared to execute call to python");
		$res = $ohandle->callOSAdmin($this->heliontenant->helionglobals, "removeFlavorAccess", $this->heliontenant->tenantName, $values, "NovaService");
		$json = json_decode($res);
		if($json->{'errorCode'} == "0"){
			$this->logger("removeFlavorProjectLinkOS()::End\n");
			return;
		}else{
			$this->logger("removeFlavorProjectLinkOS()::End\n");
			throw new \Rest\RestException(400, "Unable to remove project from flavor access. " .$json->{'errorMsg'});
		}
	}
}
?>
