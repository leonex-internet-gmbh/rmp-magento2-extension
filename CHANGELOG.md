CHANGELOG
=========

Next release
------------



Release 2.4.3
-------------

* Normalize shipping address to null if only country is defined
* Setup of phpunit and tests for QuoteSerializer

### Deprecations

* Usage of `\Leonex\RiskManagementPlatform\Component\Quote` is deprecated. Use `\Leonex\RiskManagementPlatform\Helper\QuoteSerializer` instead.


Release 2.4.2
-------------
* Adjusted naming of deprecated customerSessionId param to quoteId in API request
* Fixed retrieval of quote Id in cache usage log from deprecated method


Release 2.4.1
-------------

### Deprecations

* `Leonex\RiskManagementPlatform\Helper\CheckoutStatus::hasBillingAddressReallyBeenSet`
  Calling the method without passing the quote is deprecated and will lead to an error in 3.0
* `Leonex\RiskManagementPlatform\Model\Component\Connector::checkPaymentPre`
  Calling the method without passing the quote is deprecated and will lead to an error in 3.0
* `Leonex\RiskManagementPlatform\Model\Component\Quote`
  * `isAddressProvided` is deprecated - use `\Leonex\RiskManagementPlatform\Helper\CheckoutStatus::isAddressProvided($quote)` instead.
  * `getGrandTotal` is deprecated - use the getGrandTotal of the quote model directly.
  * Calling the following methods without passing Magento's quote model is deprecated:
    * `getNormalizedQuote`
    * `getQuoteItems`
    * `getCustomerData`
    * `getCustomerEmail`
    * `getOrderHistory`
    * `getQuoteHash`
    * `getNumberOfCanceledOrders`
    * `getNumberOfCompletedOrders`
    * `getNumberOfUnpaidOrders`
    * `getNumberOf`
  * The following methods are deprecated without replacement:
    * `getQuoteId`
    * `getQuote`

Release 2.4.0
-------------

* Added new configuration option to select the time of the check:
  "Before order placement". At this time all order information are available.


Release 2.3.0
-------------

* Docs: Added paragraph about the most important configuration fields
* The first request to the platform should not happen before shipping
  address was entered
* Send the billing address not before the customer has really entered it.
  Magento stores shipping address data in billing address, but this must
  not be sent as billing address to the platform.


Release 2.2.3
-------------

* Adjusted PHP version constaint (support for PHP >= 8.0)


Release 2.2.2
-------------

* Bug: Fixed issue in upgrade process for Magento >= 2.4.2


Release 2.2.1
-------------

* Bug: Fixed issue #2 occuring when module is used in admin or webapi area

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
