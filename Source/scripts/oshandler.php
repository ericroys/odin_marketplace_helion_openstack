<?php
require_once "logger.php";
/**
 * OSHandler class manages interaction between php
 * and the python openstack clients. The class is responsible
 * for execution of command line calls to the os python clients
 * and handling the results. 
 */

class oshandler{
	
	/**
	 * Funtion to call python openstack api. Uses globals to determine
	 * the connection information when operating under the context of 
	 * the admin user defined
	 * 
	 * @param unknown $globals  - The globals object from which to reference
	 * @param unknown $action	- Action for python to perform
	 * @param unknown $project	- The project the action will operate within
	 * @param unknown $values	- The values to pass to the command
	 * @param unknown $class	- The class to use (i.e. Keystone, Neutron, etc)
	 */
	public function callOSAdmin($globals, $action, $project, $values, $class){
		
		return $this->callOS(	$globals->HELIONUSER, 
								$action, 
								$project, 
								$globals->HELIONIP, 
								$values, 
								$class, 
								$globals->HELIONPASS,
								$globals->helionProtocol);
	}
	
	/**
	 * Function to call python openstack api. Uses globals to determine
	 * the connection information but uses passed in values for authentication
	 * usually for user based context.
	 * @param unknown $globals
	 * @param unknown $usr
	 * @param unknown $action
	 * @param unknown $project
	 * @param unknown $values
	 * @param unknown $class
	 * @param unknown $pwd
	 */
	public function callOSUser($globals, $usr, $action, $project, $values, $class, $pwd){
		return $this->callOS($usr, $action, $project, $globals->HELIONIP, $values, $class, $pwd, $globals->helionProtocol);
		
	}
	
	/**
	 * Function to call python openstack api.
	 * @param unknown $usr
	 * @param unknown $action
	 * @param unknown $project
	 * @param unknown $server
	 * @param unknown $values
	 * @param unknown $class
	 * @param unknown $pwd
	 * @param unknown $proto
	 */
	function callOS($usr, $action, $project, $server, $values, $class, $pwd, $proto){
		
		//generate the parameter string from passed vars
		$param = '{"username": "'.$usr.'",
				   "actionItem":"'.$action.'",
				   "projectName":"'.$project.'",
				   "serverName":"'.$server.'",
				   "values":{'.$values.'},
				   "actionClass":"'.$class.'",
				   "password":"'.$pwd.'",
					"proto": "'.$proto.'"}';
		//$this->logger("unclean params to pass:\n".$param);
		$this->logger("parameters to pass to python:\n".$this->noPass($param));
		$param = escapeshellarg($param);
// 		exec('/usr/bin/python openstack_integration/ -o '. $param, $output);
		exec('/usr/local/bin/python2.7 openstack_integration/ -o '. $param, $output);		
		//deal with output results. If less than 1 we got bad results from the call
		// and therefore an error condition. Else we should have a json formatted
		// response in the form of {"errorCode":"0","errorMsg":"","result":""}
		if(count($output) < 1){
			return '{"errorCode":"1","errorMsg":"Unable to communicate with Helion API","result":""}';
		}else{
			return $output[0];
		}
	}
	
	//function to remove clear text pwd for logging purposes
	function noPass($ins){
		
		$ps = '"password":"'; //12
		$fill = "********";
		
		if(strpos($ins, $ps)){
			$json = json_decode($ins);
			$json->{'password'} = $fill;
			//$this->logger("EXISTS!! " . property_exists($json->{'values'}, 'password'));
			if(property_exists($json,'values')){
				if(property_exists($json->{'values'}, 'password')){
					$json->{'values'}->password = $fill;
				}
			}	
			return json_encode($json);
		}else{
			return $ins;
		}
	}
	
	//for logging
	function logger($message){
		$requestor="OSHandler";
		$log = new Logging();
		$log->lwrite($requestor.":".$message);
		$log->lclose();
	}
}
?>