# Odin Service Automation

## APS Package for HP Helion OpenStack

![alt text](/Source/images/odin.png "Odin Logo") <span style="display:inline-block; font-size: 34px; margin-left:12px; margin-right:12px;">+ </span>![alt text](/Source/images/Helion.png "HP Helion OpenStack Logo")

A deployable proto-type package for the Ingram Micro <a href="https://be.ingrammicro.eu/en/impartner/cloud/odin-service-platform-automation">Odin Service Automation</a> platform to deliver consumable services for HP Helion Openstack.

## Solution Overview

HP Helion OpenStack® is an open, scale-out, extensible cloud platform that makes it easier to build, manage, and consume hybrid clouds. HP Helion OpenStack accelerates cloud deployment and cloud application development for organizations providing cloud services (IaaS, PaaS, and/or SaaS) to multiple independent groups and their users across different sites.

The HP Helion OpenStack Odin Service Automation module will allow service providers to offer the services available on the HP Helion OpenStack instances to their customers without having to expose the HP Helion web interfaces. All automation and product interface calls between the Odin Service Automation and HP Helion Systems are done using secure API level access. Current provider functions are based on the available APIs and will continue to be enhanced as new HP Helion OpenStack API functionality is added. Current provider level functionality examples include:

- Add / Remove Projects to Helion OpenStack
- Add / Manage Project Quotas
- Add / Remove Users to Helion OpenStack and associated Projects
- Add / Remove Networks to Helion OpenStack
- Add / Remove Containers and Objects to Object Storage
- Add / Remove Security Policies to Helion OpenStack

Once the provider defines the Helion OpenStack resources, the end-user will then consume and interact with resources through the main Odin Automation portal. End-user level functionality examples include:

- Create new instances
- Power On / Power Off / Soft Reset instances / Hard Reset instances
- Create a snapshot of an instance
- Apply security policy to an instance
- Interact with Object Storage Containers and Objects

## Tech Stack

In order to plug into the Odin system with available SDK, the project uses a combination of technologies including Javascript/AJAX for the front-end, and Apache, PHP, and python are used for the back-end. Used in the development an HP Helion Openstack system and supporting components (i.e. LDAP) were installed in a lab running on CentOS.

The project was a bit challenging with the various moving components and learning how to tie into the Odin framewark to support the billing platform's pay-as-you-go model with Openstack's ceilometer metric collection. Ultimately the project remained in proto-type stage due to limitations in available and timely metrics of ceilometer (at the time) along with the <a href="https://www.datacenterdynamics.com/en/news/ingram-micro-acquires-odin-service-automation-platform-from-parallels/">sale of the Odin platform to Ingram Micro from Parallels</a>

## Organization

The code is organized for creation of an APS package per specifications.

### **_Source_**

Contains the code for the project, including UI and back-end code.

#### **_UI_**

Contains all the html and javascript to provide the UI functionality within the Odin Service Automation cloud marketplace.

#### **_Scripts_**

Contains all the back-end code for consumption of open stack services, with PHP providing the interface between the Odin deployed UI html/javascript and the python middle-ware layer.

##### **_openstack_integration_**

Contains all the python code that is the middleware between PHP services and HP Helion Openstack python API.

### **_Docs_**

Contains the user and provider guides, although the original docs are removed for dissemination and replaced with a singular reduced provider guide.
