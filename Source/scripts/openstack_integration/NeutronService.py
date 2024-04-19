'''
Created on Apr 25, 2014

@author: Avnet Inc.
'''
from OpenStackTemplate import OpenStackTemplate
from neutronclient.v2_0.client import Client
from KeystoneService import KeystoneService
from StaticConstants import StaticConstants
from osresponse import OSResponse
import json

class NeutronService(OpenStackTemplate):
    '''
    classdocs
    '''
    def __init__(self, server, username, password, projectName, proto):
        '''
        Constructor
        '''
        OpenStackTemplate.__init__(self, server, username, password, projectName, proto)
        try:
            if proto == 'SSL-Insecure':
                self.__neutronClient = Client(auth_url = self.get_admin_url(),
                                          username = username,
                                          password = password,
                                          tenant_name = projectName,
                                          insecure = True)
            else:
                self.__neutronClient = Client(auth_url = self.get_admin_url(),
                                          username = username,
                                          password = password,
                                          tenant_name = projectName)
        except Exception, e:
            return OSResponse().OSErrorResponse(e, None)
            
    def getClient(self):
        return self.__neutronClient
        
    def apply_private_address(self, instance_name, private_ip_address):
        raise NotImplementedError("neutronservice")
        return 0;
    
    def apply_public_ip_address(self, values):
        raise NotImplementedError("neutronservice")
        return 0;
    
    def createNetwork(self, values):
        
        try:
            _name = values[StaticConstants.NETWORK_NAME]
            _state = values['adminstate']
            _external = values['externalnetwork']
            _shared = values['shared']
            _project = values[StaticConstants.PROJECT_NAME]
        except KeyError as err:
            return OSResponse().InvalidRequest(err.args[0])
        
        network = {'name': _name, 'admin_state_up': _state, 'shared': _shared }
        try:
            self.__neutronClient.create_network({'network':network})
            return OSResponse().Success('""')
        except Exception as err:
            return OSResponse().OSErrorResponse(err, None)
        
    def deleteNetwork(self, values):
        
        try:
            _name = values[StaticConstants.NETWORK_NAME]        
        except KeyError as err:
            return OSResponse().InvalidRequest(err.args[0])  
        
        try:
            _id = self._getNetworkId(_name)
            self.__neutronClient.delete_network(_id)
            return OSResponse().Success();
        except Exception as err:
            return OSResponse().OSErrorResponse(err, None)       
        
    def getNetwork(self, netName):
        ''' get a network object from a name '''
        
        net = self.__neutronClient.list_networks(name = netName)
        return 'getNetwork()::-> ' , net
    
    def getListNetwork(self, values):
        '''get a list of networks '''
        '''
            * gets a list of all network and then augments data
            * for the subnets
            
            output: json 
            {"network": [
            {"status": "ACTIVE", "subnets": [{"cidr": "192.170.0.0/24", "name": "aaa"}], "adminState": true, "name": "private2", "external": false}, 
            {"status": "ACTIVE", "subnets": [{"cidr": "10.10.13.0/24", "name": "mSub02"}, {"cidr": "10.10.11.0/24", "name": "a"}], "adminState": true, "name": "public", "external": true}, 
            {"status": "ACTIVE", "subnets": [{"cidr": "10.10.12.0/24", "name": "ext-subnet"}], "adminState": true, "name": "public2", "external": true}, 
            {"status": "ACTIVE", "subnets": [{"cidr": "192.168.1.0/24", "name": "demo-subnet"}], "adminState": true, "name": "private", "external": false}
            ]}

        '''
        tmp = self.__neutronClient.list_networks(retrieve_all = True)
        
        d = {'network':[]}
        sb = []
       
        for x in tmp.values():
            for y in x:

                sb = []
                for s in y.get('subnets'):
                    '''print 'subnet:::-> ', s'''
                    xx = self.__neutronClient.show_subnet(subnet = s)
                    if xx:
                        sb.append({"name":xx.get('subnet').get('name'), "cidr": xx.get('subnet').get('cidr')})
                
                d['network'].append(
                                    {"status": y.get('status'), 
                                     "name": y.get('name'), 
                                     "adminState": y.get('admin_state_up'), 
                                     "external": y.get('router:external'), 
                                     "shared": y.get('shared'),
                                     "subnets": sb})
                
        return OSResponse().Success(json.dumps(d))
        
    def createSubnet(self, values):
        try:
            _name = values[StaticConstants.GATEWAY_NAME]
            _pool = values[StaticConstants.GATEWAY_ALLOC_POOLS]
            _ip = values[StaticConstants.GATEWAY_IP]
            _dhcp_enabled = values[StaticConstants.GATEWAY_DHCP_ENABLED]
            _host_routes = values[StaticConstants.GATEWAY_HOST_ROUTES]
            _dnsservers = values[StaticConstants.GATEWAY_DNS_SERVERS]
            _cidr = values[StaticConstants.GATEWAY_CIDR]
            _version = values[StaticConstants.GATEWAY_IP_VERSION]
            _netName = values[StaticConstants.NETWORK_NAME]

        except KeyError as err:
            return OSResponse().InvalidRequest(err.args[0])
        
        _netid = ''
        try:
            _netid = self._getNetworkId(_netName)
        except Exception as err:
            return OSResponse().OSErrorResponse(err, None)
        
        
        subnet = {  StaticConstants.GATEWAY_NAME: _name, 
                    StaticConstants.GATEWAY_DHCP_ENABLED: _dhcp_enabled,
                    StaticConstants.GATEWAY_IP_VERSION:_version,
                    StaticConstants.NETWORK_ID: _netid,
                    StaticConstants.GATEWAY_CIDR: _cidr}
        #populate the optionals if provided
        if(_pool):
            subnet[StaticConstants.GATEWAY_ALLOC_POOLS] = _pool
        if(_host_routes):
            subnet[StaticConstants.GATEWAY_HOST_ROUTES] = _host_routes
        if(_dnsservers):
            subnet[StaticConstants.GATEWAY_DNS_SERVERS] = [_dnsservers]
        if(_ip):
            subnet[StaticConstants.GATEWAY_IP] = _ip
        try:   
            self.__neutronClient.create_subnet({'subnet':subnet})
            return OSResponse().Success('""');
        except Exception as err:
            return OSResponse().OSErrorResponse(err, None)

    def deleteSubnet(self, values):
        
        try:
            _name = values[StaticConstants.NETWORK_NAME]        
        except KeyError as err:
            return OSResponse().InvalidRequest(err.args[0])  
        
        try:
            _id = self._getSubId(_name)
            pts = self.__neutronClient.list_ports(network_id = _id)
            #need to drop any associated ports
            for p in pts['ports']:
                for f in p['fixed_ips']:
                    if _id == f['subnet_id']:
                        self.__neutronClient.delete_port(p['id'])
            self.__neutronClient.delete_subnet(_id)

            return OSResponse().Success();
        except Exception as err:
            return OSResponse().OSErrorResponse(err, None)   
            
    def getSubnet(self, values):
        try:
            _name = values[StaticConstants.SUBNET_ID]
        except KeyError as err:
            return OSResponse().InvalidRequest(err.args[0])
        
        return self.__neutronClient.show_subnet(_name)
    
    def getListSubnets(self, values):
        
        ''' gets a list of subnets attached to a given network '''
        try:
            _name = values[StaticConstants.NETWORK_NAME]
        except Exception, err:
            return OSResponse().InvalidRequest(err.args[0])
        
        try:
            d = {'subnets':[]}
            net = self._getNetworkId(_name)
            net = self.__neutronClient.show_network(net)
            n = net.get('network')
            for s in n.get('subnets'):
                xx = self.__neutronClient.show_subnet(subnet = s)
                if xx:
                    _hr = ', '.join(xx.get('subnet').get('host_routes'))
                    _dns = ', '.join(xx.get('subnet').get('dns_nameservers'))

                    _ap = xx.get('subnet').get('allocation_pools')
                    _aptmp = []
                    for a in _ap:
                        _aptmp.append('Start:' + a['start'] + ' End:' + a['end'])
                        
                    _ap = ', '.join(_aptmp)
                    
                    d['subnets'].append({
                               "name":xx.get('subnet').get('name'), 
                               "cidr": xx.get('subnet').get('cidr'), 
                               "ipversion":xx.get('subnet').get('ip_version'), 
                               "gatewayip":xx.get('subnet').get('gateway_ip'),
                               "allocationpools":_ap,
                               "dhcpenabled":xx.get('subnet').get('enable_dhcp'),
                               "dnsservers":_dns,
                               "hostroutes":_hr,
                               "gatewaydisabled": not xx.get('subnet').get('gateway_ip')
                               })
            return OSResponse().Success(json.dumps(d))
               
        except Exception as err:
            return OSResponse().OSErrorResponse(err, None)
    
    def _getNetworkId(self, name):
        
        nets = self.__neutronClient.list_networks(retrieve_all = True)
        res = None
        for x in nets.values():
            for y in x:
                if y.get('name') == name:
                    res = y.get('id')
        if res:
            return res
        else:
            raise Exception('Unable to get the network id for ('+name+').')  

    def _getSubId(self, name):
        
        nets = self.__neutronClient.list_subnets(retrieve_all = True)
        res = None
        for x in nets.values():
            for y in x:
                if y.get('name') == name:
                    res = y.get('id')
        if res:
            return res
        else:
            raise Exception('Unable to get the subnet id for ('+name+').')  
            
    def createSecurityGroup(self, values):
        try:
            name = values['name']
            description = values['description']
        except KeyError as err:
            return OSResponse().InvalidRequest(err.args[0])
        
        try:
            body = {"security_group": {"name": name, 'description': description}}
            x = self.__neutronClient.create_security_group(body)
            _tid = x['security_group']['id']
            return OSResponse().Success('{"id":"' +_tid+'"}')

        except Exception as err:
            return OSResponse().OSErrorResponse(err, None)
            
            
    def createSecurityGroupRule(self, values):

        try:
            #required attributes
            security_group_id =  values['security_group_id']
            direction = values['direction'] #ingress or egress
            etherType = values['ether_type']#IPv4 or IPv6
        except KeyError as err:
            return OSResponse().InvalidRequest(err.args[0])
        
        body = {"security_group_rule": {'direction':direction,
                                        'ethertype':etherType,
                                        'security_group_id':security_group_id
                                        }}
            #optional attributes        
            #Only protocol values [None, 'tcp', 'udp', 'icmp'] and their integer representation (0 to 255) are supported.    
        optional_attributes = ['port_range_max',
                                'port_range_min',
                                'protocol',
                                'remote_group_id',
                                'tenant_id',
                                'remote_ip_prefix']
        for attr in optional_attributes:
            if attr in values.keys():
                body["security_group_rule"][attr] = values[attr]
                
        try:     
            x = self.__neutronClient.create_security_group_rule(body = body)
            _tid = x['security_group_rule']['id']
            return OSResponse().Success('{"id":"' +_tid+'"}')

        except Exception as err:
            return OSResponse().OSErrorResponse(err, None)
            
    def deleteSecurityGroup(self, values):
        ''' 
        This will delete an existing security group
        parameters: 
        Values - a dict of values. Uses 'groupId' to determine which group to delete
        Returns - 0
        '''
        try:
            _name = values['groupId']
        except KeyError as err:
            return OSResponse().InvalidRequest(err.args[0])
        try:
            self.__neutronClient.delete_security_group(values['groupId'])
            return OSResponse().Success();
        except Exception as err:
            return OSResponse().OSErrorResponse(err, None)
    
    def deleteSecurityRule(self, values):  
          
        try:
            _id = values['ruleId']

        except KeyError as err:
            return OSResponse().InvalidRequest(err.args[0])
        try:
            self.__neutronClient.delete_security_group_rule(values['ruleId'])
            return OSResponse().Success();
        except Exception as err:
            return OSResponse().OSErrorResponse(err, None)        
