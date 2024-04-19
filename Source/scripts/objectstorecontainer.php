<?php
#############################################################################################################################################
# We are using the standard logger function and APS runtime Libraries
#############################################################################################################################################
require_once "logger.php";
require_once "aps/2/runtime.php";
require_once "oshandler.php";

#############################################################################################################################################
# We are setting up a objectstore which represents customer object storage containers in OpenStack.  
#############################################################################################################################################

/**
* Class helionobjectstorecontainer
* @type("http://hp.com/helionobjectstorecontainer/1.0")
* @implements("http://aps-standard.org/types/core/resource/1.0")
* @implements("http://aps-standard.org/types/core/suspendable/1.0")
*/

class helionobjectstorecontainer extends APS\ResourceBase
{
	#############################################################################################################################################
	# Link to project related to the object storage
	#############################################################################################################################################
	
	/**
	* @link("http://hp.com/project/3.0")
	* @required
	*/
	public $helionproject;

	/**
     * @link("http://hp.com/helionobjectstoreobjects/1.0[]")
     */
   public $containerobjectstoreobjects;
	
	/**
	* @type(string)
	* @title("Container Name")
	* @description("Container Name")
	* @required
	*/
	public $containername;
	
	/**
	* @type(string)
	* @title("Container Read ACL")
	* @description("Container Read ACL")
	*/
	public $containerreadacl;
	
	/**
	* @type(string)
	* @title("Container Write ACL")
	* @description("Container Write ACL")
	*/
	public $containerwriteacl;

	/**
	* @type(string)
	* @title("Container Meta Data One")
	* @description("1st Container Meta Data String")
	*/
	public $containermetadataone;
	
	/**
	* @type(string)
	* @title("Container Meta Data Two")
	* @description("2nd Container Meta Data String")
	*/
	public $containermetadatatwo;
	
	/**
	* @type(string)
	* @title("Container Meta Data Three")
	* @description("3rd Container Meta Data String")
	*/
	public $containermetadatathree;

	/**
	 * @type(integer)
	 * @title("Container Count")
	 * @description("Container File Count")
	 */
	 public $containercount;
	
	/**
	 * @type(integer)
	 * @title("Container Size MB")
	 * @description("Container Size in MB")
	 */
	 public $containermb;
	
	/**
	 * @type(string)
	 * @title("Container Status")
	 * @description("Container Status")
	 */
	public $containerstatus;
	
	/**
	 * @type(string)
	 * @title("Openstack Id")
	 */
	public $openstackId;
	
	public function helionprojectLink(){}
	public function helionprojectUnlink(){}
	public function containerobjectstoreobjectsLink(){}
	public function containerobjectstoreobjectsUnLink(){}
	

	#############################################################################################################################################
	# CRUD Operations on APS Type
	#		* configure - Modify Existing Instance
	#		* provision - New object created
	#		* unprovision - Delete Object
	#		* retrieve - Read Object
	#############################################################################################################################################
	
	public function provision(){
		
		$this->logger("Regular provision():: Start");
		$this->containerstatus = "Provisioning";
		$this->state = "Ready";
		$this->logger("Provision Container - Name ".$this->containername);
		$this->logger("Provision Call provisionOpenstackContainer()...");
		$this->provisionOpenStackContainer();
		$this->logger("Provision Container Complete : ".$this->containername);	

	}

	public function configure($new){

		$this->logger("Configure Container Called");
		$this->_copy($new);
	}

	public function unprovision(){

		$this->logger("Unprovision Call unprovisionOpenStackContainer()");
		$this->unprovisionOpenStackContainer();
		$this->logger("Unprovision Container Complete: ".$this->containername);	
	}

	#############################################################################################################################################
	#
	#############################################################################################################################################
	/**
	 * We define operation for container count
	 * @verb(PUT)
	 * @path("/getcontainercount")
	 * @param()
	 */
	function getcontainercount(){}
	
 	public function log($what){
		echo $what.": ".", netusage: ".$this->netusage->usage."\n";
	}
	
	public function count(){}


	#############################################################################################################################################
	## Support functions for this class
	#############################################################################################################################################
	private $isDebugEnabled;
	private $logger;
	private $provisionOpenStackContainer;
	private $unprovisionOpenStackContainer;
	private $getContainerStatus;
	
	function isDebugEnabled(){}

	function logger($message){
        $requestor=$_SERVER['REMOTE_ADDR'];
        $log = new Logging();
        $log->lwrite($requestor.":".$message);
        $log->lclose();
    }
    
	function provisionOpenStackContainer() {
		# Code to create OpenStack Container
		$this->logger("provisionOpenStackContainer()::Start...");
		//create new Openstack handler object
		$ohandle = new oshandler();
		//setup for gettoken
		$values = '"container_name": "'.$this->containername.'"';
		//make the call to the handler to deal with call to helion
		$res = $ohandle->callOSAdmin($this->helionproject->heliontenant->helionglobals, "create_container", $this->helionproject->projectName, $values, "SwiftService");
		$json = json_decode($res);
		if($json->{'errorCode'} == "0"){
			$this->openstackid = $json->{'result'}->id;
		}else{
			//throw exception here (to do)
			return "";
		}
	}
	
	function unprovisionOpenStackContainer() {
		# Code to remove OpenStack Container
		$this->logger("unprovisionOpenStackContainer()::Start...");
		//create new Openstack handler object
		$ohandle = new oshandler();
		//setup for gettoken
		$values = '"container_name": "'.$this->containername.'"';
		//make the call to the handler to deal with call to helion
		$res = $ohandle->callOSAdmin($this->helionproject->heliontenant->helionglobals, "delete_container", $this->helionproject->projectName, $values, "SwiftService");
		$json = json_decode($res);
		$this->logger("unprovisionOpenStackContainer()::End\n");
		if($json->{'errorCode'} == "0"){
			return;
		}else{
			//throw exception here (to do)
			return "";
		}
	}
}
?>
