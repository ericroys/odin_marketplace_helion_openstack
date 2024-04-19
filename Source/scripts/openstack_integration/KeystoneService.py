'''
Created on Apr 25, 2014

@author: 039732
'''
from OpenStackTemplate import OpenStackTemplate
from keystoneclient.v2_0.client import Client
from StaticConstants import StaticConstants
from osresponse import OSResponse

class KeystoneService(OpenStackTemplate):
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
                self.__keystoneservice = Client(auth_url = self.get_admin_url(),
                                                username = username, 
                                                password = password,
                                                tenant_name = projectName,
                                                insecure = True)
            else:
                self.__keystoneservice = Client(auth_url = self.get_admin_url(),
                                                username = username, 
                                                password = password,
                                                tenant_name = projectName)
        except Exception, e:
            return OSResponse().OSErrorResponse(e, None)
           

    def getClient(self):
        return self.__keystoneservice
    
    def getToken(self, values):
        try:
            sid = self.__keystoneservice.get_token(self.__keystoneservice.session)
            return OSResponse().Success('{"token":"' + sid +'"}')
        except Exception, err:
            return OSResponse().OSErrorResponse(err)

    def createUser(self, values):
        '''
        create_user : This creates a user within the original tenant that the instance is 
        configured.
        
        Parameters: 
        Values - A dictionary of values to use. This is expecting the keys name, password, and email as
        
        Returns: id value of the user 
        '''

        tenant = None
        uid = None
        try:
            _n = values[StaticConstants.NAME]
            _p = values[StaticConstants.PASSWORD]
            _e = values[StaticConstants.EMAIL]

        except KeyError as err:
            return OSResponse().InvalidRequest(self, err.args[0])
        
        try:
            tenant = self.__keystoneservice.tenants.find(name = self.projectName)
        except Exception, e:
            return OSResponse.ErrorResponse(e, 'Unable to find tenant (' +self.projectName+')')

        try:
            uid = self.__keystoneservice.users.create(name = _n, 
                                                      password = _p,
                                                      email = _e,
                                                      tenant_id = tenant.id 
                                                      ).id
        except Exception, e:
            return OSResponse().OSErrorResponse(e)

            
        return OSResponse().Success('{"id":"' +uid+'"}')
        
    def deleteUser(self, values):
        '''
        deleteUser : This deletes a user.
        
        Parameters: 
        Values - A dictionary of values to use. This is expecting the keys name
        
        Returns: osresponse 
        '''
        try:
            _u = values[StaticConstants.NAME]
        except KeyError as err:
            return OSResponse().InvalidRequest(err.args[0])
        try:
            usr = self.__getUser(user = _u)
            usr.delete()
        except Exception, err:
            if not str(err[0]).startswith('Unable to find'):
                return OSResponse().OSErrorResponse(err)
            
        return OSResponse().Success()
    
    def disableProject(self, values):
        
        try:
            _t = values[StaticConstants.PROJECT_NAME]

        except KeyError as err:
            return OSResponse().InvalidRequest(err.args[0])
        try:
            t = self.__getTenant(projectName = _t)
            if(t):
                t.update(enabled = False)
        except Exception, err:
            if not str(err[0]).startswith('Unable to find'):
                return OSResponse().OSErrorResponse(err)
            
        return OSResponse().Success()

    def enableProject(self, values):
        
        try:
            _t = values[StaticConstants.PROJECT_NAME]

        except KeyError as err:
            OSResponse().InvalidRequest(err.args[0])
        try:
            t = self.__getTenant(projectName = _t)
            if(t):
                t.update(enabled = True)
        except Exception, err:
            if not str(err[0]).startswith('Unable to find'):
                return OSResponse().OSErrorResponse(err)
            
        return OSResponse().Success()  
        
    def deleteProject(self, values):
        '''    
        deleteProject : This deletes a tenant.
        
        Parameters: 
        Values - A dictionary of values to use. This is expecting the keys name
        
        Returns: osresponse 
        '''
        try:
            _t = values[StaticConstants.PROJECT_NAME]

        except KeyError as err:
            return OSResponse().InvalidRequest(err.args[0])
        try:
            tenant = self.__getTenant(projectName = _t)
            tenant.delete()
        except Exception, err:
            if not str(err[0]).startswith('Unable to find'):
                return OSResponse().OSErrorResponse(err)
            
        return OSResponse().Success()
    
    def createProject(self, values):
        '''
        createProject: This creates a new tenant within the openstack instance.
        
        Parameters: 
        Values - A dictionary of values to use. This is expecting the keys name, description
        
        Returns: The tenant ID
        '''
        _tid = None
        
        try:
            _t = values[StaticConstants.PROJECT_NAME]
            _d = values[StaticConstants.DESCRIPTION]

        except KeyError as err:
            return OSResponse().InvalidRequest(err.args[0])
        
        ''' check to see if the tenant already exists and then provision or not '''
        try:
            self.__getTenant(projectName = _t)
        except Exception:
            try:
                _tid = self.__keystoneservice.tenants.create(_t,_d,enabled=True).id
            except Exception, e:
                return OSResponse().OSErrorResponse(e)

        return OSResponse().Success('{"id":"' +_tid+'"}')

    def removeUserFromProject(self, values):
        '''
        Remove a user from a project(tenant)
        '''
        try:
            _t = values[StaticConstants.PROJECT_NAME]
            _u = values[StaticConstants.NAME]
        except KeyError as err:
            return OSResponse().InvalidRequest(err.args[0])  
        
        try:
            tenant = self.__getTenant(projectName = _t)
            usr = self.__getUser(user = _u)
            roles = self.__keystoneservice.users.list_roles(usr, tenant)
        
            for r in roles:
                self.__keystoneservice.tenants.remove_user(tenant.id, usr.id, r.id)
        except Exception, err:
            return OSResponse().OSErrorResponse(err)
        
        return OSResponse().Success()
                                    
    def associateUserToProject(self, values):
        '''
        Associate a user with a project (tenant) and assign one or more roles
        :param values - A dictionary of values to use. This is expecting the keys: projectName, 
                        name, roleName
        :rtype integer (0) when no exceptions occur
        '''
        try:
            _t = values[StaticConstants.PROJECT_NAME]
            _u = values[StaticConstants.NAME]
            _r = values[StaticConstants.ROLE_NAME].split(",")
        except KeyError as err:
            return OSResponse().InvalidRequest(err.args[0])

        try:
            tenant = self.__getTenant(projectName = _t)
            usr = self.__getUser(user = _u)
            role = self.__getRole(role = _r[0])
            self.__keystoneservice.tenants.add_user(tenant.id, usr.id, role.id)
            ''' iterate the remaining roles (if any) and associate '''
            if len(_r)>1:
                for r in _r[1:]:
                    role = self.__getRole(role = r)
                    self.__keystoneservice.roles.add_user_role(usr.id, role.id, tenant.id)

        except Exception, err:
            if not str(err[0]).startswith('Conflict'):
                return OSResponse().OSErrorResponse(err)
        
        return OSResponse().Success()
    
    def disableUser(self, values):
        try:
            _u = values[StaticConstants.NAME]

        except KeyError as err:
            return OSResponse().InvalidRequest(err.args[0])
        
        try:
            usr = self.__getUser(user = _u)
            if(usr):
                self.__keystoneservice.users.update_enabled(user = usr, enabled = False)
        except Exception, err:
            return OSResponse().OSErrorResponse(err) 
           
        return OSResponse().Success()
    
    def enableUser(self, values):
        try:
            _u = values[StaticConstants.NAME]

        except KeyError as err:
            return OSResponse().InvalidRequest(err.args[0])
        try:
            usr = self.__getUser(user = _u)
            if(usr):
                self.__keystoneservice.users.update_enabled(user = usr, enabled = True)
        except Exception, err:
            return OSResponse().OSErrorResponse(err) 
           
        return OSResponse().Success()
    
    def getEndpoints(self, values):
        
        try:
            x = self.__keystoneservice.endpoints.list()
            ep = ''
            for i in x:
                if i.publicurl.endswith('/v1/AUTH_%(tenant_id)s'):
                    ep = i.publicurl
                    break
            if ep:
                return OSResponse().Success('{"endpoint":"' +ep.split('%',1)[0]+'"}')
            else:
                return OSResponse.ErrorResponse(self, 'Error: Endpoint not found!')
        except Exception, err:
            return OSResponse().OSErrorResponse(err)
        
    
    def __getTenant(self, projectName):
        
        try:
            return self.__keystoneservice.tenants.find(name = projectName)
        except Exception:
            raise Exception('Unable to find the project specified (' + projectName + ')')
    
    def __getUser(self, user):
        try:
            return self.__keystoneservice.users.find(username = user)
        except Exception:
            raise Exception('Unable to find the user specified (' + user + ')')

    def __getRole(self, role):
        try:
            return self.__keystoneservice.roles.find(name = role)
        except Exception:
            raise Exception('Unable to find the role specified (' + role + ')')
