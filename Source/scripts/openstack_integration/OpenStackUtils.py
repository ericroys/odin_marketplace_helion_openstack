import requests, json

class OpenStackUtils:
    @staticmethod
    def createAuthenticationToken(server, username, password):
        '''
        createAuthenticationToken
        
        This method's intention is to manually grab an authentication token from
        the OpenStack server. 
        
        server : The location of the server. The full address is generated from this variable.
        username : The username that exists on the openstack server.
        password : The password of the user (username). 
        
        This method returns a single string of the authentication token if the user was valid. If 
        the user is not valid: a custom exception of InvalidCredientals" is thrown. 
        '''
        result = ""
        url = 'http://'+server+':5000/v2.0/tokens'
        form_data = json.dumps({ "auth":{ "passwordCredentials":{ "username":username, "password":password } } })
        headers = {'Accept': 'application/json', 'Content-Type': 'application/json'}
        response =  requests.post(url, form_data, headers=headers )
        
        # some reason requests.codes.ok does not resolve
        if response.status_code == 200:
            json_response = json.loads(response.content); 
            result = json_response['access']['token']['id']
        else:
            raise InvalidCredientals(username)
        
        return result 
        

class InvalidCredientals(Exception):
    '''
    This exception is used to identify invalid credientals for a user.
    This exception is typically thrown when an authetnication session fails due to 
    account issues. 
    '''
    def __init__(self, user):
        self.user = user
    
    def __str__(self):
        return repr('The user: '+ self.user +' was either invalid or unable to authenticate')