## Audience

This guide contains detailed information the about the HP Helion OpenStack APS Module for Odin Service Automation. It provides instructions for installing, configuring, and using the APS module. It is intended to be used by service provider Odin Service Automation administrator to enable and configure the APS module. A recommendation would be to use this guide along with the appropriate user guides for your Odin Service Automation environment. This document assumes that you are familiar with the Odin Service Automation administration and customization interface in general, and makes some assumptions that you are familiar with some basic actions. You should know how to perform basic actions in the Windows and Linux environment, such as choosing menu commands, as well as interacting with a Linux system on a command line.

## Terms and Abbreviations

| Term   | Definition                                                                                                                                       |
| ------ | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| APS    | Application Packaging Standard, an open standard that was designed to simplify the delivery of SaaS applications in the cloud-computing industry |
| OA     | Odin Automation                                                                                                                                  |
| OSA    | Odin Service Automation                                                                                                                          |
| OBA    | OdinBusiness Automation                                                                                                                          |
| Helion | OpenStack                                                                                                                                        | Hewlett Packard Release of HP Helion based on OpenStack technology |

## Integration Workflow

The main purpose of this integration is to allow provider engineers to enable and administer HP Helion OpenStack services to their customers. In order to use the integration, it must first be implemented and configured in the Odin system. The general workflow for implementing the module is as follows:

- Configure the Odin system endpoint server with the appropriate third-party software and modules
- Import the HP Helion OpenStack application package.
- Create an application instance in the Provider Control Panel
- Create the required resource types for the application package
- Create a service template, and linking the service template to customer through a subscription within the Parallels system.

## Solution Overview

HP Helion OpenStack® is an open, scale-out, extensible cloud platform that makes it easier to build, manage, and consume hybrid clouds. HP Helion OpenStack accelerates cloud deployment and cloud application development for organizations providing cloud services (IaaS, PaaS, and/or SaaS) to multiple independent groups and their users across different sites.

The HP Helion OpenStack Odin Service Automation module will allow service providers to offer the services available on the HP Helion OpenStack instances to their customers without having to expose the HP Helion web interfaces. All automation and product interface calls between the Odin Service Automation and HP Helion Systems are done using secure API level access. Current provider functions are based on the available APIs and will continue to be enhanced as new HP Helion OpenStack API functionality is added. Current provider level functionality examples include:

- Add / Remove Projects to Helion OpenStack
- Add / Manage Project Quotas
- Add / Remove Users to Helion OpenStack and associated Projects
- Add / Remove Networks to Helion OpenStack
- Add / Remove Containers and Objects to Object Storage
- Add / Remove Security Policies to Helion OpenStack

Once the provider defines the Helion OpenStack resources, the end-user will then consume and interact with resources through the main Parallels Automation portal. End-user level functionality examples include:

- Creating new instances
- Power On / Power Off / Soft Reset instances / Hard Reset instances
- Creating a snapshot of instance
- Apply security policy to an instance
- Interact with Object Storage – Containers and Objects

## Resource Model

![alt text](/docs/images/resource_model.png "HP Helion OpenStack resource schema")

... etc, etc.
