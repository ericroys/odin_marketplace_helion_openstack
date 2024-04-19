'''
Created on May 28, 2014

@author: 039732
'''
import json
from osresponse import OSResponse
#from unittest.test.test_result import __init__
from CommonEqualityMixin import CommonEqualityMixin

class ActionDataItem(CommonEqualityMixin):
    '''
    classdocs
    '''
    username = ""
    password = ""
    serverName = ""
    projectName = ""
    actionClass = ""
    actionItem = ""
    values = dict()
    proto = ""


    def __init__(self, actionClass, actionItem, user, password, serverName, projectName, values, proto):
        '''
        Constructor
        '''
        self.actionClass = actionClass
        self.actionItem = actionItem
        self.values = values
        self.username = user
        self.password = password
        self.projectName = projectName
        self.serverName = serverName
        self.proto = proto
        
        
class SerializeActionDataItem(object):
    @staticmethod 
    def serialize(actionData):
        return json.dumps(actionData.__dict__)
        
    @staticmethod
    def deserialize(jsonString):
        dictValue = json.loads(jsonString);
#        def __init__(self, actionClass, actionItem, user, password, serverName, projectName, values):

        return ActionDataItem(dictValue["actionClass"], 
                              dictValue["actionItem"], 
                              dictValue["username"], 
                              dictValue["password"], 
                              dictValue["serverName"], 
                              dictValue["projectName"], 
                              dictValue["values"],
                              dictValue["proto"])
    
    
class ActionProcessor(object): 
    
    def __init__(self, jsonCommandAction):
        self.actionDataItem = SerializeActionDataItem.deserialize(jsonCommandAction)
        
    def process(self):
        '''
        Process the request. Initialize the "actionClass" request, and call the "actionItem" method with the values keyword.
        '''
        moduleName = self.actionDataItem.actionClass;
        module = __import__(moduleName, fromlist=[self.actionDataItem.actionClass])
        class_ = getattr(module, self.actionDataItem.actionClass)
        instance = class_(
                          self.actionDataItem.serverName, 
                          self.actionDataItem.username, 
                          self.actionDataItem.password, 
                          self.actionDataItem.projectName,
                          self.actionDataItem.proto);
   
        try:
            methodToCall = getattr(instance, self.actionDataItem.actionItem);
        
            return methodToCall(self.actionDataItem.values)
        except Exception, e:
            return OSResponse().OSErrorResponse(e, None)
            
        