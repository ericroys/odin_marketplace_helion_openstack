'''
Created on Apr 24, 2014

@author: 039732
'''
from OpenStackTemplate import OpenStackTemplate
from novaclient.client import Client
from StaticConstants import StaticConstants
from NeutronService import NeutronService
from KeystoneService import KeystoneService
from osresponse import OSResponse
import datetime

import json

class NovaService(OpenStackTemplate):
    '''
    classdocs
    '''
    __novaClient = None
    ni = None
    ks = None
    
    def __init__(self, server, username, password, projectName, proto):
        OpenStackTemplate.__init__(self, server, username, password, projectName, proto)
        try:
            if proto == 'SSL-Insecure':
                self.__novaClient = Client(version = "2",
                                       auth_url=self.get_admin_url(),
                                       username = username,
                                       api_key = password,
                                       project_id = projectName,
                                       insecure = True)
            else:
                self.__novaClient = Client(version = "2",
                                       auth_url=self.get_admin_url(),
                                       username = username,
                                       api_key = password,
                                       project_id = projectName)
            
            self.ni = NeutronService(server, 
                                     username, 
                                     password,
                                     projectName=projectName,
                                     proto=proto)    
            
            self.ks = KeystoneService(server,
                                      username,
                                      password,
                                      projectName=projectName,
                                      proto=proto)
        except Exception, err:
            return OSResponse().OSErrorResponse(err, None)
        
    def rebootVM(self, values):
        '''
        rebootVM: This will reboot a server.
        Parameters: 
        Values - A dictionary of values to use. 
            serverId: The id from the server that was created.
            rebootType: a value of 'SOFT' or 'HARD' (determines which to perform)
        '''         
        try:
            _name = values[StaticConstants.SERVER_NAME]
            _type = values[StaticConstants.REBOOT_TYPE]
        except KeyError as err:
            return OSResponse().InvalidRequest(err.args[0])
        
        try:
            _sid = self.__novaClient.servers.find(name = _name)
            _sid.reboot(reboot_type=_type)
        except Exception, err:
            return OSResponse().OSErrorResponse(err, None)

        return OSResponse().Success();
        
    def turnOnVM(self, values):
        '''
        turnOnVM: This will boot a server.
        Parameters: 
        Values - A dictionary of values to use. serverId: The id from the server that was created.
        ''' 
        try:
            _name = values[StaticConstants.SERVER_NAME]
        except KeyError as err:
            return OSResponse().InvalidRequest(err.args[0])
        
        try:
            _sid = self.__novaClient.servers.find(name = _name)
            _sid.start()
        except Exception, err:
            return OSResponse().OSErrorResponse(err, None)

        return OSResponse().Success();
    
    def turnOffVM(self, values):
        '''
        turnOnVM: This will shutdown a server.
        Parameters: 
        Values - A dictionary of values to use. serverId: The id from the server that was created.
        ''' 
        try:
            _name = values[StaticConstants.SERVER_NAME]
        except KeyError as err:
            return OSResponse().InvalidRequest(err.args[0])
        
        try:
            _sid = self.__novaClient.servers.find(name = _name)
            _sid.stop()
        except Exception as err:
            #if 'in task_state powering-off. Cannot stop while the instance is in this state' in err.message:
            _sid.reset_state(state = 'active')
            try:
                _sid.stop()
            except Exception, err:
                return OSResponse().OSErrorResponse(err, None)

        return OSResponse().Success();
    
    def reset(self, values):
        
        try:
            _name = values[StaticConstants.SERVER_NAME]
        except KeyError as err:
            return OSResponse().InvalidRequest(err.args[0])
        
        try:
            _sid = self.__novaClient.servers.find(name = _name)
            _sid.reset_state(state = 'active')
        except Exception, err:
            return OSResponse().OSErrorResponse(err, None)
        
        return OSResponse().Success();
        
    def deleteVM(self, values):
        '''
        deleteVM: This will delete a service.
        Parameters: 
        Values - A dictionary of values to use. serverId: The id from the server that was created.
        '''
        try:
            _name = values[StaticConstants.SERVER_NAME]
        except KeyError as err:
            return OSResponse().InvalidRequest(err.args[0])
        
        try: 
            _sid = self.__novaClient.servers.find(name = _name)
            self.__novaClient.servers.delete(_sid.id);
        except Exception, err:
            return OSResponse().OSErrorResponse(err, None)
        
        return OSResponse().Success();
    
    def restartVM(self, values):
        '''
        restartVM: This will stop and start an instance on .
        Parameters: 
        Values - A dictionary of values to use. serverId: The id from the server that was created.
        '''
        try:
            _name = values[StaticConstants.SERVER_NAME]
        except KeyError as err:
            return OSResponse().InvalidRequest(err.args[0])
        
        try:
            _sid = self.__novaClient.servers.find(name = _name)
            self.__novaClient.servers.stop(_sid.id);
            self.__novaClient.servers.start(_sid.id);
        except Exception, err:
            return OSResponse().OSErrorResponse(err, None)
        
        return OSResponse().Success();
            
    def addFloatIp(self, values): #svr, ip):
        ''' add a floating ip to a server
        Parameters:
        svr - A server id
        ip  - The ip address to associate
        '''
        try:
            _name = values[StaticConstants.SERVER_NAME]
            _ip = values[StaticConstants.IPADDRESS]
        except KeyError as err:
            return OSResponse().InvalidRequest(err.args[0])
        
        try:
            _sid = self.__novaClient.servers.find(name = _name)
            _sid.add_floating_ip(_ip)
        except Exception, err:
            return OSResponse().OSErrorResponse(err, None)
        
        return OSResponse().Success()      
    
    def Neutron(self) :
        
        c = NeutronService(server=self.server, username=self.username, password=self.password, projectName=self.projectName, proto = self.proto).getClient()
        return c
    
    def Keystone(self):
        c = KeystoneService(server=self.server, username=self.username, password=self.password, projectName=self.projectName, proto=self.proto).getClient()
        return c

    def getProjectStats(self, values):
        
        try:
            _name = values[StaticConstants.PROJECT_NAME]
        except KeyError as err:
            raise Exception('Expected [' + err.args[0] + '] input value')
        #get project id instead of name
        try:
            projid = self.Keystone().tenants.find(name = _name).id   #           .__getTenant(_name)try:
        except Exception:
            raise Exception('Unable to find the project specified [' + _name + ']')
        
        now = datetime.datetime.now()
        start = now - datetime.timedelta(days=100)
        stats = self.__novaClient.usage.get( projid, start, now )
        # define out output vars so at least always returns a value as integer
        rammbhours = 0
        vcpuhours = 0
        diskgbhours = 0
        
        if stats:
            rammbhours = stats.total_memory_mb_usage
            vcpuhours = stats.total_vcpus_usage
            diskgbhours = stats.total_local_gb_usage

        outvals = {"rammbhours" : int(round(rammbhours)), "vcpuhour" : int(round(vcpuhours)), "diskgbhours" : int(round(diskgbhours))}
        return OSResponse().Success(json.dumps(outvals))
    
    def getVMInformation(self, values):
        '''
        getVMInformation 
        :param vmName : Name of the instance/VM that information will be returned on. Uses: serverId.
        :rtype A map that has the following items: ram, cpu, name, disk, os, status, powerState
            addresses, and taskState
        '''
        try:
            _name = values[StaticConstants.SERVER_NAME]
        except KeyError as err:
            return OSResponse().InvalidRequest(err.args[0])
        try:
            serverValue = self.__novaClient.servers.find(name = _name);
            flavorInformation = self.__novaClient.flavors.get(serverValue.flavor["id"])
            imageInformation = self.__novaClient.images.get(serverValue.image["id"])
        except Exception, err:
            return OSResponse().OSErrorResponse(err, None)        
        tv = []
        
        for k in serverValue.networks.items():
            tv.append(k)
         
        inputValues = { "ram" : flavorInformation.ram, "cpu": flavorInformation.vcpus, "name": serverValue.name , 
                       "disk": flavorInformation.disk, "os": imageInformation.name, "status": serverValue.status,
                       "powerState": serverValue.__dict__['OS-EXT-STS:power_state'], "addresses": tv,
                       "taskState": serverValue.__dict__['OS-EXT-STS:task_state'] }
        
        return OSResponse().Success(json.dumps(inputValues))
    
    def getFlavor(self, values):
        ''' get flavor information'''
        
        try:
            _name = values[StaticConstants.FLAVOR_NAME]
        except KeyError as err:
            return OSResponse().InvalidRequest(err.args[0])

        try:
            flav = self.__novaClient.flavors.find(name = _name)
        except Exception, err:
            return OSResponse().OSErrorResponse(err, None)   
       
        return OSResponse().Success(json.dumps( {"name": flav.name, "cpu":flav.vcpus, "ram":flav.ram, "disk": flav.disk, "swap":flav.swap }))
        
        
    def listFlavors(self, values):
        '''
        listFlavors: This will give you a list of flavors, by name, available within the openstack instance. 
        Returns: OSSuccess response with json flavor object
        '''        
        flav = {'flavor':[]}
        try:
            res = self.__novaClient.flavors.list()
        except Exception, err:
            return OSResponse().OSErrorResponse(err, None)
            
        for i in res:
            flav['flavor'].append({"name": i.name})
        
        return OSResponse().Success(json.dumps(flav))
    
    def listFlavorDetails(self):
        '''
        listFlavorDetails: This will give you a list of flavors plus additional details. 
        
        Parameters: 
        None
        
        Returns: OSSuccess response with json flavor object
        '''        
        
        flav = {'flavor':[]}
        try:
            res = self.__novaClient.flavors.list()
        except Exception, err:
            return OSResponse().OSErrorResponse(err, None)
        
        for i in res:
            flav['flavor'].append({"name": i.name,"cpu":i.vcpus,"ram":i.ram,"id":i.id,"swap":i.swap,"disk":i.disk,"ephemeral":0})
        
        return OSResponse().Success(json.dumps(flav))   
    
    def listImages(self, values):
        '''
        listImages: This will give you a list of images, by name, available within the openstack instance. 
        
        Parameters: 
        None
        
        Returns: OSSuccess response with json image object
        '''       
        img = {'image':[]}
        try:
            res = self.__novaClient.images.list()
        except Exception, err:
            return OSResponse().OSErrorResponse(err, None)

        for i in res:
            img['image'].append({"name": i.name})
            
        return OSResponse().Success(json.dumps(img))
    
    def createSnapShot(self, values):
        '''
        createSnapshot: This creates a snapshot from an active instance. 
        
        Parameters: 
        Values - A dictionary of values to use. Uses: snapshotName - Name of the new snapshot to create. serverId:  
        '''
        
        try:
            _name = values[StaticConstants.SERVER_NAME]
            _snapName = values[StaticConstants.SNAPSHOT_NAME]

        except KeyError as err:
            return OSResponse().InvalidRequest(err.args[0])
        
        try:
            _sid = self.__novaClient.servers.find(name = _name)
            self.__novaClient.servers.create_image(_sid,_snapName)
        except Exception, err:
            return OSResponse().OSErrorResponse(err, None)
         
        return OSResponse().Success()   
    
    def createVM(self, values):
        '''
        createVM: This creates a new instance. 
        
        Parameters: 
        Values - A dictionary of values to use. 
            network: A comma seperated list of network names. 
            imageName: the name of the image to use
            flavorName: name of the flavor that is used on the image. 
            name: Name of the new instance. 
        
        Returns: Instance id [aka the serverId to use later].
        ''' 
        
        try:
            _n = values[StaticConstants.NETWORKS].split(",")
            _img = values[StaticConstants.IMAGE_NAME]
            _flav = values[StaticConstants.FLAVOR_NAME]
            _name = values[StaticConstants.NAME]
            _sec = values[StaticConstants.SECURITYGROUPS].split(",")
            
        except KeyError as err:
            return OSResponse().InvalidRequest(err.args[0])
        #find network IDs
        try:
            nlist = self.Neutron().list_networks(retrieve_all = True)
            ns = []
            
            for a in _n:
                for net in nlist['networks']:
                    if a.strip() == net['name']:
                        ns.append({"net-id": net['id']})
            #deal with security groups
            _secgr = []
            for p in _sec:
                d = self.Neutron().list_security_groups(name=p)
                for p in d['security_groups']:
                    _secgr.append(p['id'])
            
            _nics = []        
            for n in ns:
                _pname = values[StaticConstants.NAME] +'_prt'            
                body = {"port": {"name": _pname, "network_id": n['net-id'], "security_groups": _secgr}}
                _port = self.Neutron().create_port(body)
                _port = _port['port']['id']
                _nics.append({'net-id':n['net-id'], 'port-id':_port})
                    
            #find image by name
            image = self.__novaClient.images.find(name=_img)
            #find flavor by name
            flavor = self.__novaClient.flavors.find(name=_flav)
            _value = self.__novaClient.servers.create(name = _name, image = image.id, flavor = flavor.id, nics = _nics).id
            return OSResponse().Success('{"id":"' +_value+'"}')
            
        except Exception, err:
            return OSResponse().OSErrorResponse(err, None)
        
    def createFlavor(self, values):
        '''Create a flavor '''
        try:
            #required
            _name = values[StaticConstants.NAME]
            _ram = values[StaticConstants.FLAVOR_RAM]
            _cpu = values[StaticConstants.FLAVOR_CPU]
            _disk = values[StaticConstants.FLAVOR_DISK]
            _id = "auto"
            _swap = values[StaticConstants.FLAVOR_SWAP]
            _ephemeral = values[StaticConstants.FLAVOR_EPHEMERAL]
            #set defaults if no value provided
            if not _ephemeral:
                _ephemeral = 0
            if not _swap:
                _swap = 0
        except KeyError as err:
            return OSResponse().InvalidRequest(err.args[0])
        try:
            # set public to false as implementation should always include a project
            #for access
            _sid = self.__novaClient.flavors.create(name=_name, 
                                                   ram = _ram, 
                                                   disk=_disk,
                                                   vcpus=_cpu,
                                                   flavorid=_id,
                                                   swap=_swap,
                                                   ephemeral=_ephemeral,
                                                   is_public=False).id
        
        except Exception, err:
            return OSResponse().OSErrorResponse(err, None)

        return OSResponse().Success('{"id":"' +_sid+'"}')
    
    def setFlavorAccess(self, values):
        '''Associates a project to a flavor'''
        try:
            _id = values[StaticConstants.FLAVOR_ID]
            _proj = values[StaticConstants.PROJECT_NAME]
        except KeyError as err:
            return OSResponse().InvalidRequest(err.args[0])
        try:    
            _x = self.__novaClient.flavor_access.add_tenant_access(_id,_proj)
        except Exception, err:
            return OSResponse().OSErrorResponse(err, None)
        return OSResponse().Success()
    
    def deleteFlavor(self, values):    
        '''Removes a flavor by id'''
        try:
            _id = values[StaticConstants.FLAVOR_ID]
        except KeyError as err:
            return OSResponse().InvalidRequest(err.args[0])
        try:    
            _x = self.__novaClient.flavors.get(_id).delete()
            
        except Exception, err:
            return OSResponse().OSErrorResponse(err, None)
        return OSResponse().Success()    
 
    def removeFlavorAccess(self, values):
        '''Removes association of a project to a flavor'''
        try:
            _id = values[StaticConstants.FLAVOR_ID]
            _proj = values[StaticConstants.PROJECT_NAME]
        except KeyError as err:
            return OSResponse().InvalidRequest(err.args[0])
        try:    
            _x = self.__novaClient.flavor_access.remove_tenant_access(_id,_proj)
        except Exception, err:
            return OSResponse().OSErrorResponse(err, None)
        return OSResponse().Success()   
    
    def getQuota(self, values):
        
        try:   
            _name = values[StaticConstants.PROJECT_NAME]
        except Exception as err:
            return OSResponse().InvalidRequest(err.args[0])
        x = {}
        try:
            q = self.__novaClient.quotas.get(tenant_id=_name)
        
            for t in StaticConstants.QUOTA_COMP_ATTRIBUTES:
                x[t] = getattr(q, t)
        
            q = self.Neutron().show_quota(_name)
            p = q['quota']
            for t in StaticConstants.QUOTA_NET_ATTRIBUTES:
                x[t] = p[t]
        except Exception, e:
                return OSResponse().OSErrorResponse(e)
        return OSResponse().Success(json.dumps({'quota':x}))
    
    def updateQuota(self, values):
        
        try:
            proj = values['project_id']
        except Exception as err:
            return OSResponse().InvalidRequest(err.args[0])
        
        if 'project_id' in values:
            del values['project_id']
        #separate the neutron stuff
        nets = {}
        comp = {}
        for key in values:
            if key in StaticConstants.QUOTA_NET_ATTRIBUTES:
                nets[key] = values[key]
            else:
                comp[key] = values[key]
        
        if len(comp)>0:
            kwargs = comp
            #print 'COMP: ', comp
            try:
                self.__novaClient.quotas.update(proj, **kwargs)
            except Exception as err:
                return OSResponse().OSErrorResponse(err, None)
        if len(nets)>0:
            kwargs = nets
            #print 'NETS: ', kwargs
            try:
                body = {'quota':kwargs}
                self.Neutron().update_quota(proj, body)
            except Exception as err:
                return OSResponse().OSErrorResponse(err, None)

        return OSResponse().Success()