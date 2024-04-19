'''
'''

class OSResponse(object):
    '''
        Class to handle all responses from the Openstack Python client calls.
        All calls output a standard JSON result that is much easier to consume
        from an external client perspective. 
        
        The standard response JSON looks like: 
            {
                "errorCode":"<0 for success, greater than 0 for all errors>", 
                "errorMsg":"<an error message or empty if no error>", 
                "result":"<a json formatted message>"
            }
        
        example: for successful call response...
            {
                "errorCode":"0",
                "errorMsg":"", 
                "result":"{"vps:{"name":"devtraining01", "os":"windows 2010", "disk":"500"}}
            }
        example: for error condition...
            {
                "errorCode":"201",
                "errorMsg":"Authentication Failed",
                "result":""
            }
    '''


    def __init__(self):
        '''
        Constructor
        '''
    def Success(self, msg = '""'):
        ''' Success
            JSON formatted response object for use with response
            messages that have no error condition (hence no
            errorCode or errorMsg required)
        '''
        return('{"errorCode":"0","errorMsg":"","result":'+msg+'}')
    
    def OSErrorResponse(self, error, errmsg = None):
        ''' Create OS Error Response
            JSON formatted response object for use with 
            response messages that have an error type
            as provided by one of the OS client Exceptions
            
            input:
            error - an Openstack api Exception
            ermsg - an optional user defined message to be used instead of
                    the error message provided by the exception
        '''
        return self.__err(error, errmsg)
    
    def ErrorResponse(self, msg):
        ''' Create Error Response
            JSON formatted response object for used with
            response messages that have an exception not of the
            Openstack Exception type (a "catch all" for python exceptions)
            
            input: 
            msg - an error message string
        '''
        
        return '{"errorCode":"1","errorMsg":"'+msg +'","result":""}'
    
    def InvalidRequest(self, param):
        ''' Create error response for requests that had invalid
            key(s) in the request.
            JSON formatted response object used with
            response messages that have an exception of KeyType
            
            input:
            param - the missing key name
        '''
        return '{"errorCode":"1","errorMsg":"Invalid Command Parameters. Expected (' + param + ') input value)"}'
    
    def __err(self, e, errmsg):
        ''' for handling OS Exception responses
            
            input: 
            Exception - an Openstack Exception
            errmsg - an optional user provided error message that will
                override the message provided by the Exception
        '''
        _msg = None
        ecode = '1' # default error code
        
        #see if user provided message, if so use it, otherwise get it from exception
        if errmsg:
            _msg = errmsg
        else:
            _msg = None

        if _msg is None:
            _msg = e.message
        
        # if we have a status code in the exception object, use it
        if 'http_status' in e.__dict__:
            ecode = str(e.http_status)

            
        return '{"errorCode":"' +ecode +'","errorMsg":"'+_msg+'","result":""}'

