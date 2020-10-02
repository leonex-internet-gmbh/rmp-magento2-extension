CHANGELOG
=========

Release 2.2.0
-------------

* Feature: Support of 'diverse' as third gender. Magento value '3' is mapped
  to RMP API value 'd'.
* Feature: Added configurable mapping between address prefix and gender


Release 2.1.1
-------------

* Bug: Fixed check for payment selection: every second call returned true


Release 2.1.0
-------------

* Improvement: Added index for creation timestamp in log table for faster removal
* Improvement: Cleanup logs by cronjob


Release 2.0.2
-------------

* Feature: Implemented structured logging to database
* Feature: Added grid to view logs made by the module
* Feature: Implemented CSV export for logs via UI component
* Improvement: Use billing address only after customer really chosen or entered it
* Improvement: Improved checking for payment method selection, by checking for
  payment availability on the quote
* Improvement: Added log if RMP is offline
* Improvement: Added payment method code to selection in admin configuration
* Bug: Changes in shipping address did not result in cache refresh


Release 1.2.4
-------------

* Bug: Fixed collection of order history for guest customers
* Bug: Safe guest email address in session


Release 1.2.3
-------------

* Bug: Removed usage of SessionStartChecker, because it's not available in Magento 2.2.2
* Bug: Removed the payment selection for Magento 2.2 data update scripts, because
  it triggers the session start in CLI during upgrade process, which leads to errors
* Bug: If rating is enabled but no payment methods are selected, fallback to check
  all methods.


Release 1.2.2
-------------

* Bug: Fixed issue in data upgrade script for Magento 2.3


Release 1.2.1
-------------

* Bug: UpdateData script was not compatible to Magento 2.2


Release 1.2.0
-------------

* Feature: Added fallback mechanism in case the RMP is not available
* Improvement: Increased allowed PHP version to 7.3 in composer.json
* Improvement: Improved system configuration labels and added german localization
* Improvement: Only call the API if the address has been provided
* Improvement: Fixed API result cache misses caused by changing session IDs
* Improvement: Added logging on multiple places to improve debugging capabilities
* Bug: Check if extension attributes are available before accessing
* Bug: Fixed mistake in payment availability check
* Bug: Prevent response caching if RMP is not available


Release 1.1.5
-------------

* Improvement: Added Default Date and YearRange to DatePicker
