Risk Management Platform Integration for Magento 2
==================================================

This Extension integrates a credit check and more made by the LEONEX Risk Management Platform into your order process. Extensive configuration and evaluation options is provided in the Platform

How to install
--------------

### Install via composer (recommend)

Run the following command in Magento 2 root folder:

```
composer require leonex/magento-module-rmp-connector
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
```

Supported Environment
---------------------

| RMP Extension | Magento   | PHP       |
|---------------|-----------|-----------|
| 2.2.0         | 2.2 - 2.4 | 7.1 - 7.4 |
| 1.2.3 - 2.1.1 | 2.2 - 2.4 | 7.1 - 7.3 |
| 1.0.0 - 1.2.2 | 2.3 - 2.4 | 7.1 - 7.3 |

Logging
-------

It is possible to enable a logging mechanism that collects information about the internal
processes of the extension. This means that you can see which requests to the Risk Management
Platform are performed, which data is transmitted and what the responses contained.

To enable the logging sign into the admin backend and go to *Stores -> Configuration ->
Sales -> Risk Management Platform* and enable the debug logging.

Then you will find all logs at *Sales -> Risk Management Platform Logs*.
