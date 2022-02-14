require([
        'jquery',
        'mage/translate',
        'jquery/validate'],
    function($){
        $.validator.addMethod(
            'validate-oracle-custom-url', function (value) {
                return (value === '' || (value == null) || (value.length === 0))
                    || /^(http|https):\/\/[A-Z0-9_\-\.\/\?=:\d]+$/i.test(value)
            }, $.mage.__('Please enter a valid URL with scheme included. For example http://www.example.com.'));
    }
);