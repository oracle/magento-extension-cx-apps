# Magento Connector App
This is an extension project which allow Magento and Oracle CX Apps 
integration for two-way data ingestion. This project aim to add marketing 
capability through Magento e-commerce platform via Oracle CX Apps.

## Prerequisite
1. We need Magento e-commerce platform, and composer to use this 
extension.
2. This extension is used to leverage marketing capability with 
Oracle Responsys, so you must have a Responsys account to do the setup.

## Quick start
1. Clone this respository ```git clone https://github.com/oracle/magento-extension-cx-apps```
2. Run this command to build the deliverable out of this project. 
   ```php ci/build.php --environment prod --version ${your version}```
3. You can publish this deliverable to use via composer, or you can use this with 
your local Magento installation.

## Installation

The instructions outlined here will help you to install extension using composer as 
standard tool.

Please note that, if your Magento version is below 2.4.2, then the Composer 
version should be v1, for Magento 2.4.2 and above Composer v1 and v2 are allowed. 
Install the required Composer version following the guide on [getcomposer](https://getcomposer.org/).

Follow below steps to get the Magento extension installed.
1. Open the terminal, navigate to your Magento installation folder, and add 
   the following to the base Magento <code>composer.json</code> file

Array format:
```json
  "repositories": [{     
                 "type": "composer",     
                 "url":  "https://raw.githubusercontent.com/oracle/magento-extension-cx-apps/main/packages.json"      
   }]        
```

JSON Object Format:

```json
    "repositories": {     
         "oracle": {    
             "type": "composer",     
             "url": "https://raw.githubusercontent.com/oracle/magento-extension-cx-apps/main/packages.json"
         }    
    }  
```
2. Run below command
```composer log
   composer require oracle/magento-module-all
```
This command will get the specified component from the mentioned repositories url.

3. Run below command to enable these modules.
```composer log
  ./bin/magento module:enable Oracle_Browse Oracle_Cart Oracle_Connector 
        Oracle_Contact Oracle_Coupon Oracle_Email Oracle_Integration 
        Oracle_Inventory Oracle_M2 Oracle_Notification Oracle_Optin 
        Oracle_Order Oracle_Product Oracle_Rating Oracle_Redemption
```

4. Run below command to make sure that the enabled modules are properly 
   registered and their tables in the database are created properly
```composer log
  ./bin/magento setup:upgrade
```

5. Run below command to recompile entire Magento project to generate code 
   and configure dependency injection
```composer log
  ./bin/magento setup:di:compile
```

6. The final step is to clear all cache. Please run below command to flush the cache.
```composer log
  ./bin/magento cache:flush
```
Following above step will install the Magento extension. Post this follow
instruction from the CX Apps help page to establish connection.

## Documentation

See [Documentation for the Magento Connector App][1].

## Contributing

<!-- If your project has specific contribution requirements, update the
    CONTRIBUTING.md file to ensure those requirements are clearly explained. -->

We would love to learn more about your use-cases and how we can improve this
extension for all of its users. Before submitting code, we encourage you to
start a discussion here on GitHub so that we can work with you on your contribution.
If an existing feature isn't working as expected, please open an issue. Before 
submitting a pull request, please [review our contribution guide](./CONTRIBUTING.md).

## Security

Please consult the [security guide](./SECURITY.md) for our responsible
security vulnerability disclosure process.

## License

<!-- The correct copyright notice format for both documentation and software
    is "Copyright (c) [year,] year Oracle and/or its affiliates."
    You must include the year the content was first released (on any platform) 
    and the most recent year in which it was revised. -->

Copyright (c) 2021, 2022 Oracle and/or its affiliates.

<!-- Replace this statement if your project is not licensed under the UPL -->

Released under the Universal Permissive License v1.0 as shown at
<https://oss.oracle.com/licenses/upl/>.

[1]: https://docs.oracle.com/en/cloud/saas/marketing/responsys-user/#cshid=MAG_Overview