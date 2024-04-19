from OpenStackTemplate import OpenStackTemplate
import ceilometerclient.client

from StaticConstants import StaticConstants
from osresponse import OSResponse

class CeilometerService(OpenStackTemplate):
    
    def __init__(self, server, username, password, projectName, proto):

        OpenStackTemplate.__init__(self, server, username, password, projectName, proto)
        
        if proto == 'SSL-Insecure':
            self.cclient = ceilometerclient.client.get_client("2", os_username=username, os_password=password, os_tenant_name=projectName, os_auth_url=self.get_admin_url())
        else:
            self.cclient = ceilometerclient.client.get_client("2", os_username=username, os_password=password, os_tenant_name=projectName, os_auth_url=self.get_admin_url())

            
    def getClient(self):
        return self.cclient
    
    def getProjectStats(self, values):
        '''gets statistics for a given project
            :param values dictionary containing:
            * project (projectId) -> required
            * meter (meterName) -> required
            * period (meterPeriod) -> an integer (optional)
            * timeStart (timeStart) -> a formatted time (optional)
            * timeEnd (timeEnd)    -> a formatted time (optional)
            * groupby (groupBy) -> should be an array (optional)
        '''
        try:
            _pid = values[StaticConstants.PROJECT_ID]
            _m = values[StaticConstants.METER_NAME]

        except Exception as err:
            return OSResponse().InvalidRequest(err.args[0])
        
        if not _pid == "":
            #define query by project
            _q = [dict(field='project_id', op='eq', value=_pid)]
        else:
            return OSResponse().InvalidRequest('projectId must be specified!')
        if _m == "":
            return OSResponse().InvalidRequest('meterName must be specified!')
        #deal with optional parameters
        if StaticConstants.METER_PERIOD in values:
            _p = values[StaticConstants.METER_PERIOD]
        else:
            _p = 0
        if StaticConstants.METER_GROUP in values:
            _gby = values[StaticConstants.METER_GROUP]
        else:
            _gby = []
        if StaticConstants.METER_TIMESTART in values:
            _ts = values[StaticConstants.METER_TIMESTART]
        else:
            _ts = ""
        if StaticConstants.METER_TIMEEND in values:
            _te = values[StaticConstants.METER_TIMEEND]
        else:
            _te = ""
        if not _ts == "":
            _q.append(dict(field='timestamp',op='ge', value=_ts))
        if not _te == "":
            _q.append(dict(field='timestamp',op='le', value=_te))            
        return self.getStatistic({'meterName':_m, 'meterPeriod':_p, 'qual': _q, 'groupBy': _gby})
    
    def getStatistic(self,values):
        '''gets a statistic from ceilometer based on provided input
            :param values dictionary containing:
            * query (qual)
            * meter (meterName)
            * period (meterPeriod)
            * group by (groupBy)
        '''
        try:
            _meter = values[StaticConstants.METER_NAME]
            _period = values[StaticConstants.METER_PERIOD]
            _query = values[StaticConstants.METER_QUAL]
            _gby = values[StaticConstants.METER_GROUP]
        except KeyError as err:
            return OSResponse().InvalidRequest(err.args[0])

        try:
            _res = self.cclient.statistics.list(_meter, _query, _period, _gby)

            _min = 0.0
            _max = 0.0
            _avg = 0.0 
            for r in _res:
                if r:
                    _min = _min + r.min
                    _max = _max + r.max
                    _avg = _avg + r.avg
            
            return OSResponse().Success('{"minimum":"' + str(_min) +
                                    '","maximum":"' + str(_max) +
                                    '","average":"' + str(_avg) +'"}')
        except Exception as err:
            return OSResponse().OSErrorResponse(err, None)
        
