'''
Created on Oct 13, 2014

@author: 040790
'''
from OpenStackTemplate import OpenStackTemplate
from swiftclient import client
from osresponse import OSResponse
import json


class SwiftService(OpenStackTemplate):
    '''
    classdocs
    '''
    def __init__(self, server, username, password, projectName, proto):
        '''
        Constructor
        '''
        OpenStackTemplate.__init__(self, server, username, password, projectName, proto)
        '''print self.get_admin_url()'''
        try:
            if proto == 'SSL-Insecure':
                self._swift = client.Connection(authurl=self.get_admin_url(),
                                                user = username,
                                                key = password,
                                                tenant_name = projectName,
                                                auth_version="2",
                                                insecure = True)
            else:
                self._swift = client.Connection(authurl=self.get_admin_url(),
                                                user = username,
                                                key = password,
                                                tenant_name = projectName,
                                                auth_version="2")
        except Exception, e:
            return OSResponse().OSErrorResponse(e, None)
        
    #account level operations    
    def list_containers(self, values):
        try:
            return self._swift.get_account(full_listing=True)
        except Exception as err:
            return OSResponse().OSErrorResponse(err, None)
    
    def get_account_stat(self, values):
        try:
            return self._swift.head_account()
        except Exception as err:
            return OSResponse().OSErrorResponse(err, None)
    
    def create_container(self, values):
        try:
            container_name = values["container_name"]
            cid = self._swift.put_container(container = container_name) 
            return OSResponse().Success('{"id":"' +cid+'"}')     
        except Exception as err:
            return OSResponse().OSErrorResponse(err, None)
    
    def delete_container(self, values):
        try:
            container_name = values["container_name"]
            self._swift.delete_container(container = container_name)
            return OSResponse().Success()
        except Exception as err:
            return OSResponse().OSErrorResponse(err, None)
    
    #container level operations
    def list_objects(self, values):
        try:
            container_name = values["container_name"]
            
            '''
            format the results into something more manageable to parse from ui
            '''
            res = []
            x = self._swift.get_container(container = container_name)
            if x:
                for y in x[1]:
                    ks = y.keys()
                    x = {}
                    for t in ks:
                        x[t]=y[t]
                    
                    res.append(x)
    
                
            return OSResponse().Success(json.dumps({'objects': res}))
                   
        except Exception as err:
            return OSResponse().OSErrorResponse(err, None)
    
    def get_container_stat(self, values):
        try:
            container_name = values["container_name"]
            return self._swift.head_container(container = container_name)
        except Exception as err:
            return OSResponse().OSErrorResponse(err, None)
    
    def get_container_stats(self, values):
        '''Get the count of objects for the container
            along with the sizes of individual objects
        '''
        try:
            container_name = values["container_name"]
        except KeyError as err:
            return OSResponse().InvalidRequest(err.args[0])
        try:
            x = self._swift.get_container(container = container_name)
            _count = 0
            _bytes = 0
            if x:
                _count = x[0]['x-container-object-count']
                _bytes = x[0]['x-container-bytes-used']
            inputValues = { "stats" : {"count": _count, "bytes": _bytes}}
            return OSResponse().Success(json.dumps(inputValues))
 
        except Exception as err:
            return OSResponse().OSErrorResponse(err, None)
        
    #object level operations
    def get_object(self, values):
        try:
            container_name = values["container_name"]
            object_name = values["object_name"]
            return self._swift.get_object(container = container_name, obj = object_name)
        except Exception as err:
            return OSResponse().OSErrorResponse(err, None)
    
    def get_object_info(self, values):
        try:
            container_name = values["container_name"]
            object_name = values["object_name"]
            return self._swift.head_object(container = container_name, obj = object_name)
        except Exception as err:
            return OSResponse().OSErrorResponse(err, None)
    
    def upload_object(self, values):
        try:
            container_name = values["container_name"]
            object_name = values["object_name"]
            content_file = values["content_file"]
            return self._swift.put_object(container = container_name, obj = object_name, contents = content_file)
        except Exception as err:
            return OSResponse().OSErrorResponse(err, None)
        
    def delete_object(self, values):
        try:
            container_name = values["container_name"]
            object_name = values["object_name"]
            self._swift.delete_object(container = container_name, obj = object_name)
            return OSResponse().Success()
        except Exception as err:
            return OSResponse().OSErrorResponse(err, None)
        
    def enableProjectContainers(self, values):
        
        ercont = []
        cs = self._swift.get_account(full_listing=True)    
        if len(cs)>0:
            n = None
            for c in cs[1]:
                n= c['name']
                try:
                    self.enableContainer({'container_name':n})
                except:
                    ercont.append(n)
        if len(ercont)>0:
            j = str(', '.join(ercont))
            return OSResponse().ErrorResponse('WARNING: Enable Containers failed for [' + j + ']')
        else:
            return OSResponse().Success()        
    
    def disableProjectContainers(self, values):
        
        ercont = []
        cs = self._swift.get_account(full_listing=True)    
        if len(cs)>0:
            n = None
            for c in cs[1]:
                n= c['name']
                try:
                    self.disableContainer({'container_name':n})
                except:
                    ercont.append(n)
        if len(ercont)>0:
            j = str(', '.join(ercont))
            return OSResponse().ErrorResponse('WARNING: Disable Containers failed for [' + j + ']')
        else:
            return OSResponse().Success()               
        
    def disableContainer(self, values):
        
        container_name = values["container_name"]
        header = {'X-Container-Read': ".r:-*"}
        c = self._swift.head_container(container_name)
        if 'x-container-read' in c:
            _tmp = c['x-container-read']
            header['X-Container-Meta-ReadBK'] = _tmp
        return self._swift.post_container(container = container_name, headers=header) 
        
    def enableContainer(self, values):
        
        container_name = values["container_name"]
        header = {}
        _tmp = ""
        c = self._swift.head_container(container_name)
        if 'x-container-meta-readbk' in c:
            _tmp = c['x-container-meta-readbk']
            header['X-Container-Meta-ReadBK'] = ""
            if _tmp != "":
                header['X-Container-Read'] = _tmp
        
        return self._swift.post_container(container = container_name, headers=header)
    
