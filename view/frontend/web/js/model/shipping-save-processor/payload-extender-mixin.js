define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote'
], function ($, wrapper, quote) {
    'use strict';

    return function (payloadExtender) {
        return wrapper.wrap(payloadExtender, function (originalAction, payload) {

            payload = originalAction(payload);

            if (payload.addressInformation['extension_attributes'] === undefined) {
                payload.addressInformation['extension_attributes'] = {};
            }

            // Add your values to the payload here
            var shippingAddress = quote.shippingAddress();
            var value = null;
            for(var i in shippingAddress.customAttributes) {
                if (!shippingAddress.customAttributes.hasOwnProperty(i)) {
                    continue;
                }
                if (shippingAddress.customAttributes[i].attribute_code === 'edob') {
                    value = shippingAddress.customAttributes[i].value;
                    break;
                }
            }

            payload.addressInformation['extension_attributes']['edob'] = value;

            return payload;
        });
    };
});
