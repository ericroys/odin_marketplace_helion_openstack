from OpenStackUtils import OpenStackUtils

'''
Created on Apr 24, 2014

@author: Steven Hicks
'''
class OpenStackTemplate:
    '''
    OpenStackTemplate is a universal template that handles all of the JSON calls for the openstack environment.
    This is to minimize code duplication throughout the OpenStack SDK.
    '''
    username = "";
    password = "";
    server = "";
    projectName = "";
    proto = "";
    
    def __init__(self, server, username, password, projectName = "admin", proto = "SSL-Insecure"):
        '''
        Constructor
        '''
        self.username = username;
        self.password = password;
        self.server = server;
        self.projectName = projectName;
        self.proto = proto
    
    def get_openstack_url(self):
        '''
        get_openstack_url
        This class method generates an Openstack URL from it's given components.
        
        Returns a string representing a URL for accessing the Openstack 2.0 API.
        '''

        if self.proto.startswith('SSL'):
            return "https://"+ self.server+":5000/v2.0"
        else:
            return "http://"+ self.server+":5000/v2.0"
        
    def get_admin_url(self):
        '''
        get_admin_url() 
        This class method generates an Admin Openstack URL from it's given components.
        
        Returns a string representing a URL for accessing the Openstack Admin REST API.
        '''
        if self.proto.startswith('SSL'):
            return "https://"+ self.server+":35357/v2.0"
        else:
            return "http://"+ self.server+":35357/v2.0"    
