Risk Management Platform Integration for Magento 2
==================================================

This Extension integrates a credit check and more made by the LEONEX Risk Management Platform into your order process. Extensive configuration and evaluation options is provided in the Platform


Installation
------------

### Install via composer (recommend)

Run the following command in Magento 2 root folder:

```
composer require leonex/magento-module-rmp-connector
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
```

### Supported Environment

| RMP Extension | Magento   | PHP       |
|---------------|-----------|-----------|
| 2.2.0         | 2.2 - 2.4 | 7.1 - 8.2 |
| 1.2.3 - 2.1.1 | 2.2 - 2.4 | 7.1 - 7.3 |
| 1.0.0 - 1.2.2 | 2.3 - 2.4 | 7.1 - 7.3 |


Configuration
-------------

The configuration of this module is found at *Admin -> Stores -> Configuration ->
Sales > Risk Management Platform*.

This list explains the most important fields.

**Score rating enabled**:\
Enables the functionality of this module. This is disabled by default.

**API Key**:\
Enter the API key provided by LEONEX' Risk Management Platform. You will find it
in the webshops configuration in your customer account.

**API URL**:\
This is the URL to the API endpoint of the platform. Usually this value should
not be changed.

**Payment methods to check**:\
Select the payment methods which should be deactivated if customers have a bad score.
Usually you only have to select your invoice method.


Logging
-------

It is possible to enable a logging mechanism that collects information about the internal
processes of the extension. This means that you can see which requests to the Risk Management
Platform are performed, which data is transmitted and what the responses contained.

To enable the logging sign into the admin backend and go to *Stores -> Configuration ->
Sales -> Risk Management Platform* and enable the debug logging.

Then you will find all logs at *Sales -> Risk Management Platform Logs*.


Changelog
---------

Please refer to the [CHANGELOG.md](CHANGELOG.md).