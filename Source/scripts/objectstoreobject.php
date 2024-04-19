<?php
#############################################################################################################################################
# We are using the standard logger function and APS runtime Libraries
#############################################################################################################################################
require_once "logger.php";
require_once "aps/2/runtime.php";
require_once "oshandler.php";

#############################################################################################################################################
# We are setting up a objectstore which represents customer object storage objects in OpenStack.  
#############################################################################################################################################

/**
* Class helionobjectstoreobject
* @type("http://hp.com/helionobjectstoreobject/1.0")
* @implements("http://aps-standard.org/types/core/resource/1.0")
* @implements("http://aps-standard.org/types/core/suspendable/1.0")
*/

class helionobjectstoreobject extends APS\ResourceBase
{
	#############################################################################################################################################
	# Link to project related to the object storage
	#############################################################################################################################################
	
	/**
	* @link("http://hp.com/helionobjectstorecontainer/1.0")
	* @required
	*/
	
	public $objectstorecontainer;

	/**
	* @type(string)
	* @title("Object Name")
	* @description("Object Name")
	* @required
	*/

	public $objectname;
			
	/**
	* @type(string)
	* @title("Object Meta Data One")
	* @description("1st Object Meta Data String")
	*/

	public $objectmetadataone;
	
	
	/**
	* @type(string)
	* @title("Object Meta Data Two")
	* @description("2nd Object Meta Data String")
	*/

	public $objectmetadatatwo;
	
	/**
	* @type(string)
	* @title("Object Meta Data Three")
	* @description("3rd Object Meta Data String")
	*/

	public $objectmetadatathree;
	
	/**
	* @type(string)
	* @title("Object Status")
	* @description("Object Status")
	*/

	public $objectstatus;

	/**
	* @type(string)
	* @title("Object Location")
	* @description("Object Location")
	*/

	public $objectlocation;

	
	public function objectstorecontainerLink(){}
	public function objectstorecontainerUnlink(){}

	#############################################################################################################################################
	# CRUD Operations on APS Type
	#		* configure - Modify Existing Instance
	#		* provision - New object created
	#		* unprovision - Delete Object
	#		* retrieve - Read Object
	#############################################################################################################################################
	
	public function provision(){
		
		$this->logger("Regular provision():: Start");
		$this->objectstatus = "Provisioning";
		$this->state = "Creating Object";
		$this->retry = 5;
		$this->logger("Provision Object - Name\n\t".$this->objectname);
		$this->logger("Provision Call provisionOpenstackObject()...");
		$this->provisionOpenStackObjectObject();
		$this->logger("Provision Object Complete :\n\t".$this->objectname);	

	//check back in 30 seconds for update
        throw new \Rest\Accepted($this, "Creating Object", 30);
	}

	public function provisionAsync() {
		
		$this->retry -=1;

		$this->logger("provisionAsynch()::Start");
		$objectStatus = $this->getObjectStatus();
		if($objectStatus == "Build" and $this->retry > 0){
			throw new \Rest\Accepted($this, "Still provisioning", 30);
		}
		elseif( $objectStatus == "Error" )		{
			
			$this->objectstatus = "Error";
			$this->state = "ready";
		}
		elseif( $objectStatus == Active ){
			$this->objectstatus = "ready";
			$this->state = "ready";
		}

    }
		
	public function configure($new){

		$this->logger("Configure Object Called\n");
		$this->_copy($new);
	}

	public function unprovision(){

		$this->logger("Unprovision Call unprovisionOpenStackObject()");
		$this->unprovisionOpenStackObject();
		$this->logger("Unprovision Object Complete :\n\t".$this->instancename);	
	}

	#############################################################################################################################################
	#
	#############################################################################################################################################
	/**
	 * We define operation for object size
	 * @verb(PUT)
	 * @path("/getobjectsize")
	 * @param()
	 */
	function getobjectsize(){
	
		/**$this->getObjectSize();*/
		$this->logger("get Object Size Complete :\n\t".$this->objectname);
	}
	
    #############################################################################################################################################
	#
    #############################################################################################################################################


	public function log($what){
		echo $what.": ".", netusage: ".$this->netusage->usage."\n";
	}
	
	public function count(){}

	public function retrieve(){
		/**$this->count();*/
		$this->log("Retrieve Object Usage");
	}

	#############################################################################################################################################
	## Support functions for this class
	#############################################################################################################################################
	private $isDebugEnabled;
	private $logger;
	private $provisionOpenStackObject;
	private $unprovisionOpenStackObject;
	private $getObjectStatus;
	
	function isDebugEnabled(){}

	function logger($message){
        $requestor=$_SERVER['REMOTE_ADDR'];
        $log = new Logging();
        $log->lwrite($requestor.":".$message);
        $log->lclose();
    }
    
	//get the status of the object from openstack
	private function getObjectStatus(){

		$this->logger("getObjectStatus()::Start...\n");
		$ohandle = new oshandler();
		$values = '"name": "'.$this->objectname.'"';
		$res = $ohandle->callOSAdmin($this->helionproject->heliontenant->helionglobals, "getObjectInformation", $this->helionproject->projectName, $values, "SwiftService");
		$json = json_decode($res);
		if($json->{'errorCode'} == "0"){
			return $json->{'result'}->status;
		}else{
			return "";
		}
	}
	
	function provisionOpenStackObject() {
		# Code to create OpenStack Object
		$this->logger("provisionOpenStackObject()::Start...\n");
		$ohandle = new oshandler();
		$values = '"name": "'.$this->objectname.'"';
		$res = $ohandle->callOSAdmin($this->helionproject->heliontenant->helionglobals, "createObject", $this->helionproject->projectName, $values, "SwiftService");
		$json = json_decode($res);
		if($json->{'errorCode'} == "0"){
			return;
		}else{
			//throw error here (to do)
			return "";
		}
	}
	
	function unprovisionOpenStackObject() {
		# Code to remove OpenStack Object
		$this->logger("unprovisionOpenStackObject()::Start...\n");
		$ohandle = new oshandler();
		$values = '"name": "'.$this->objectname.'"';
		$res = $ohandle->callOSAdmin($this->helionproject->heliontenant->helionglobals, "deleteObject", $this->helionproject->projectName, $values, "SwiftService");
		$json = json_decode($res);
		$this->logger("unprovisionOpenStackObject()::End\n");
		if($json->{'errorCode'} == "0"){
			return;
		}else{
			//throw error here (to do)
			return "";
		}
	}
}
?>
