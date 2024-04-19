'''
Created on May 30, 2014

@author: Avnet Inc.
'''

class StaticConstants(object):
    
    ECHO_MESSAGE = "message"
    SERVER_ID = "serverID"
    SECURITYGRP_ID = "securityGroupId"
    SECURITYGRP_NAME = "groupName"
    SECURITYGROUPS = "security_groups"
    NAME = "name"
    DESCRIPTION = "description"
    SNAPSHOT_NAME = "snapshotName"
    IMAGE_ID = "imageID"
    FLAVOR_ID = "flavorId"
    FLAVOR_NAME = "flavorName"
    FLAVOR_CPU = "cpu"
    FLAVOR_RAM = "ram"
    FLAVOR_DISK = "disk"
    FLAVOR_SWAP = "swap"
    FLAVOR_EPHEMERAL = "ephemeral"
    NETWORKS = "networks"
    NETWORK_NAME = "networkName"
    IMAGE_NAME = "imageName"
    PASSWORD = "password"
    USER_ID = "userId"
    EMAIL = "email"
    IPADDRESS = "ipAddress"
    AUTH_URL = 'http://10.10.11.120:35357/v2.0'
    NETWORK_ID = 'network_id'
    PROJECT_ID = 'projectId'
    PROJECT_NAME = 'projectName'
    USER_NAME = 'name'
    ROLE_NAME = 'roleName'
    ROLES_LIST = 'rolesList'
    SERVER_NAME = 'serverName'
    TENANT_NAME = 'tenantName'
    SUBNET_ID = 'subnetid'
    REBOOT_TYPE = 'rebootType'
    SECRULE_IPPROTOCOL = 'ipProtocol'
    SECRULE_FROMPORT = 'fromPort'
    SECRULE_TOPORT = 'toPort'
    SECRULE_DESTIPS = 'destinationIps'
    SECRULE_GROUPNAME = 'groupName'
    SECRULE_ID = "ruleId"
    GATEWAY_IP = 'gateway_ip'
    GATEWAY_NAME = 'name'
    GATEWAY_DHCP_ENABLED = 'enable_dhcp'
    GATEWAY_ALLOC_POOLS = 'allocation_pools'
    GATEWAY_HOST_ROUTES = 'host_routes'
    GATEWAY_DNS_SERVERS = 'dns_nameservers'
    GATEWAY_CIDR = 'cidr'
    GATEWAY_IP_VERSION = 'ip_version'
    METER_NAME = 'meterName'
    METER_PERIOD = 'meterPeriod'
    METER_QUAL = 'qual'
    METER_GROUP = 'groupBy'
    METER_TIMESTART = 'timeStart'
    METER_TIMEEND = 'timeEnd'
    QUOTA_NET_ATTRIBUTES = ('network', 'subnet', 'port', 'router', 
                            'security_group_rule', 'security_group',
                            'floatingip')

    QUOTA_COMP_ATTRIBUTES = ('cores', 'fixed_ips', 'injected_file_content_bytes',
                             'injected_file_path_bytes', 'injected_files', 'instances',
                             'key_pairs', 'metadata_items', 'ram',
                              'server_groups', 'server_group_members')