'''
Created on May 28, 2014

@author: 039732
'''
from OpenStackTemplate import OpenStackTemplate
from StaticConstants import StaticConstants

class EchoService(OpenStackTemplate):
    '''
    The echo service is meant for confirming that the echo module works. This also confirms that the application is working.
    '''


    def __init__(self, server, username, password, projectName):
        '''
        Constructor
        '''
        OpenStackTemplate.__init__(self, server, username, password)
  
    def echo(self, values):
        '''
        echo (values)
        values: A dictionary of items to contain. This must contain a "message" key with a stored message as the message.
        Returns: Returns the input with "Hello: " prepended to the message. 
        '''
        return "Hello: " + str(values[StaticConstants.ECHO_MESSAGE])