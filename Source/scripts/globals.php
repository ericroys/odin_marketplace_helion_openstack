<?php
#############################################################################################################################################
#  This Class is root of HP Helion OpenStack service
#  Class is responsible for defining the global settings and settings for the context service.
#
# Additional Classes:
#   logger - allows writing of runtime log files
#	APS PHP Runtime Libraries - The APS PHP runtime is required on the Linux endpoint server within the environment
#############################################################################################################################################

require "logger.php";
require "aps/2/runtime.php";

#############################################################################################################################################
#  Class implements the instance, so named for HP Helion naming conventions.   Global Settings for implementing application core type
#############################################################################################################################################

/**
* Class helion
* @type("http://hp.com/helionglobals/2.0")
* @implements("http://aps-standard.org/types/core/application/1.0")
*/
class helion_globals extends APS\ResourceBase 
{

	#############################################################################################################################################
	# 
	#############################################################################################################################################

	/**
    * @link("http://hp.com/heliontenant/3.0[]")
	*/
	public $tenant;

	#############################################################################################################################################
	# Generic User Profile with Quotas Associated 
	# Primary place holder for Monthly Network Bandwidth Usage Quota - Perhaps others
	#############################################################################################################################################

	/**
    * @link("http://hp.com/helionquotas/1.0[]")
	*/
	public $quotas;

	#############################################################################################################################################
	# 
	#############################################################################################################################################
	
	/**
	 * @type(boolean)
	 * @title("Use LDAP")
	 */
	public $ldapEnabled;
	
	/**
	* @type(string)
	* @title("LDAP IP")
	* @access(referrer, false)
	* @required
	*/
	public $LDAPIP;
	
    /**
    * @type(string)
    * @title("LDAP Username")
	* @access(referrer, false)
	*/
	public $LDAPUSER;

    /**
    * @type(string)
    * @title("LDAP Password")
	* @access(referrer, false)
	* @encrypted
	*/
	public $LDAPPASS;

	/**
    * @type(string)
    * @title("DC1")
	* @description("Domain Component 1 (e.g. someco)")
	* @access(referrer, false)
	*/
	public $DC1;
	
	/**
    * @type(string)
    * @title("DC2")
	* @description("Domain Component 2 (e.g. com)")
	* @access(referrer, false)
    */
    public $DC2;
    
	/**
	* @type(string)
	* @title("Helion Instance Description")
	* @description("Helion OpenStack Base Project")
	* @access(referrer, false)
	*/
    public $HELIONDESC;
    
	/**
    * @type(string)
    * @title("Helion IP Address")
	* @access(referrer, false)
	*/
	public $HELIONIP;
		
    /**
    * @type(string)
    * @title("Helion Username")
	* @access(referrer, false)
    * @description("Helion OpenStack Admin User")
    * @required
	*/
	public $HELIONUSER;

    /**
    * @type(string)
    * @title("Helion Password")
	* @access(referrer, false)
	* @encrypted
	* @required
	*/
	public $HELIONPASS;
	
	/**
	 * @type(string)
	 * @option("Http", "Http")
	 * @option("SSL-Insecure", "SSL-Insecure")
	 * @option("SSL", "SSL")
	 * @title("Connection Protocol")
	 * @access(referrer, false)
	 * @description("Connection Protocol")
	 * @required
	 */
	public $helionProtocol;
	
#	**
#	 * type(boolean)
#	 * title("Show OpenStack Networks")
#	 * description("Show networks not provisioned via OSA")
#	 *
#	public $showOsNetworks;

	#############################################################################################################################################
	# Defined as place holders for APS PHP Runtime Environment
	#############################################################################################################################################

	public function tenantLink(){ }
	
    public function tenantUnLink(){ }
	
	public function subscriptionUnlink(){ }
	
	public function upgrade(){
		$this->logger("upgrade started");
		if(!isset($this->helionProtocol)){
			$this->helionProtocol = "SSL-Insecure";
		}
		$this->logger("upgrade finished");
	}

	#############################################################################################################################################
	# CRUD Operations on APS Type
	#		* configure - Modify Existing Instance
	#		* provision - New object created
	#		* unprovision - Delete Object
	#		* retrieve - Read Object
	#############################################################################################################################################

	public function configure($new=null){
	$this->logger("configure Function Called");

		$this->_copy($new);
			
	}
	
	public function provision()	{
		$this->logger("provision Function Called");
		//if(!isset($this->showOsNetworks)){ $this->showOsNetworks = false; }
	
	}
	
	public function unprovision(){
		$this->logger("unprovision function called\n");
	}
	
	public function _getDefault(){
		
		#############################################################################################################################################
		# Function to return default values for globals setup
		#############################################################################################################################################
		
		$this->logger("_getDefault Function Called (globals.php)");
		$initvals=array(
                        'GLOBAL' => array(
								'HELIONDESC' => 'Development Instance',
                                'LDAPIP' => '127.0.0.1',
                                'LDAPUSER' => 'admin',
                                'LDAPPASS' => 'password',
                                'DC1' => 'somecompany',
                                'DC2' => 'com',
                                'HELIONIP' => '127.0.0.1',
								'HELIONUSER' => 'admin',
								'HELIONPASS' => 'password'
                        ),
                );
		return $initvals['GLOBAL'];
	}

	public function retrieve(){}
	    
	
	#############################################################################################################################################
	# Additional Functions
	# logger - Write Output to log file
	# write_ini_fille - Used to Store the Configuraiton in a file on endpoint server   
	#############################################################################################################################################
	private $logger;

	function logger($message){
		## Function to write to logging file using Logger class
                $requestor=$_SERVER['REMOTE_ADDR'];
                $log = new Logging();
                $log->lwrite($requestor.":".$message);
                $log->lclose();
    }

} ## Close of the Class
?>
