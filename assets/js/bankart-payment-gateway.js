function bankartInit($){
    var $paymentForm = $('#bankart-payment-form').closest('form');
    var $paymentFormSubmitButton = $("#place_order");
    var $paymentFormTokenInput = $('#bankart-token');
    var integrationKey = window.integrationKey;
    var bankartFormId = window.bankartFormId;
    var initialized = false;

    //const { __ } = wp.i18n;
    "use strict";
    var _wp$i18n = wp.i18n, __ = _wp$i18n.__;

    var errorMsgTranslation = {
        'first_name' : {
            'errors.blank' : __('First name must not be empty', 'woocommerce-bankart-payment-gateway'),
        },
        'last_name' : {
            'errors.blank' : __('Last name must not be empty', 'woocommerce-bankart-payment-gateway'),
        },
        'card_holder' : {
            'errors.blank' : __('Cardholder must not be empty', 'woocommerce-bankart-payment-gateway'),
        },
        'month' : {
            'errors.blank' : __('Expiration month must not be empty', 'woocommerce-bankart-payment-gateway'),
            'errors.invalid' : __('Invalid expiration month', 'woocommerce-bankart-payment-gateway'),
        },
        'year' : {
            'errors.blank' : __('Expiration year must not be empty' , 'woocommerce-bankart-payment-gateway'),
            'errors.invalid' : __('Invalid expiration year', 'woocommerce-bankart-payment-gateway'),
            'errors.expired' : __('Card expired', 'woocommerce-bankart-payment-gateway'),
        },
        'number' : {
            'errors.blank' : __('Card number must not be empty', 'woocommerce-bankart-payment-gateway'),
            'errors.invalid' : __('Invalid card number', 'woocommerce-bankart-payment-gateway'),
        },
        'cvv' : {
            'errors.blank' : __('CVV code must not be empty', 'woocommerce-bankart-payment-gateway'),
            'errors.invalid' : __('Invalid CVV code', 'woocommerce-bankart-payment-gateway'),
        }
    };

    var init = function() {
        if (integrationKey && !initialized) {
            $paymentFormSubmitButton.prop("disabled", true);
            bankartPaymentGatewaySeamless.init(integrationKey);
        }
    };  

    $paymentForm.on('submit', function (event) {
        if(bankartFormId == window.bankartFormId) {
            $('.bankart-error-text').hide();
            $('.bankart-input-wrapper').removeClass('bankart-error');
            bankartPaymentGatewaySeamless.submit(
                function (token) {
                    $paymentForm.off('submit');
                    $paymentFormTokenInput.val(token);
                    $paymentForm.submit();
                },
                function (errors) {
                    event.stopImmediatePropagation(); // cancels the blockui from woocommerce from executing
                    $paymentForm.unblock(); // in case the form is aleady blocked
    
                    errors.forEach(function(error) {
                        switch(error.attribute) {
                            case 'card_holder':
                                $('#bankart-' + error.attribute).addClass('bankart-error');
                                $('#bankart-error-' + error.attribute).html('<p><small>' + errorMsgTranslation[error.attribute][error.key] + '</small></p>');
                                $('#bankart-error-' + error.attribute).show();
                                break;
                            case 'number':
                                $('#bankart-card-' + error.attribute).addClass('bankart-error');
                                $('#bankart-error-card-' + error.attribute).html('<p><small>' + errorMsgTranslation[error.attribute][error.key] + '</small></p>');
                                $('#bankart-error-card-' + error.attribute).show();
                                break;
                            case 'cvv':
                                $('#bankart-' + error.attribute).addClass('bankart-error');
                                $('#bankart-error-' + error.attribute).html('<p><small>' + errorMsgTranslation[error.attribute][error.key] + '</small></p>');
                                $('#bankart-error-' + error.attribute).show();
                                break;
                            case 'month':
                                $('#bankart-expiry-' + error.attribute).addClass('bankart-error');
                                $('#bankart-error-expiry-' + error.attribute).html('<p><small>' + errorMsgTranslation[error.attribute][error.key] + '</small></p>');
                                $('#bankart-error-expiry-' + error.attribute).show();
                                break;
                            case 'year':
                                switch(error.key) {
                                    case 'errors.expired':
                                        $('#bankart-expiry-month').addClass('bankart-error');
                                        break;
                                    default:
                                        $('#bankart-expiry-' + error.attribute).addClass('bankart-error');
                                        $('#bankart-error-expiry-' + error.attribute).html('<p><small>' + errorMsgTranslation[error.attribute][error.key] + '</small></p>');
                                        $('#bankart-error-expiry-' + error.attribute).show();
                                }
                        }
                    });  
                });
                return false;
        }
    });

    var bankartPaymentGatewaySeamless = function () {
        var payment;
        var $seamlessForm = $('#bankart-payment-form');
        var $seamlessCardHolderInput = $('#bankart-card_holder', $seamlessForm);
        var $seamlessExpiryMonthInput = $('#bankart-expiry-month', $seamlessForm);
        var $seamlessExpiryYearInput = $('#bankart-expiry-year', $seamlessForm);
        var $seamlessCardNumberInput = $('#bankart-card-number', $seamlessForm);
        var $seamlessCvvInput = $('#bankart-cvv', $seamlessForm);

        var init = function (integrationKey) {
            if($seamlessForm.length > 0) {
                initialized = true;
            } else {
                return;
            }
            var style = {
                'background' : 'transparent',
                'height': '100%',
                'border': 'none',
                'border-radius': '3px',
                'font-family': '"Arial", sans-serif',
                'font-weight': 'bold',
                'color': '#555',
                'padding': '6px',
                'padding-left': '8px',
                'padding-right': '8px',                
                'display': 'inline',
                'line-height': '1.42857143',
                'font-size': '14px',
                'box-sizing': 'border-box',
                'margin': '0',
                'outline' : '0',
                'box-shadow': 'inset 0 0px 0px rgba(0, 0, 0, .0)', 
            };
            payment = new PaymentJs("1.2");
            payment.init(integrationKey, $seamlessCardNumberInput.prop('id'), $seamlessCvvInput.prop('id'), function (payment) {
                payment.setNumberStyle(style);
                payment.setCvvStyle(style);
                // Focus events
                payment.numberOn('focus', function() {
                    $seamlessCardNumberInput.addClass('bankart-focus');
                });
                payment.cvvOn('focus', function() {
                    $seamlessCvvInput.addClass('bankart-focus');
                });
                // Blur events
                payment.numberOn('blur', function() {
                    $seamlessCardNumberInput.removeClass('bankart-focus');
                });
                payment.cvvOn('blur', function() {
                    $seamlessCvvInput.removeClass('bankart-focus');
                });
            });

            $seamlessCardNumberInput.mousedown(function() {
                $seamlessCardNumberInput.children("iframe")[0].contentWindow.focus();
            });
            $seamlessCvvInput.click(function() {
                $seamlessCvvInput.children("iframe")[0].contentWindow.focus();
            });

            $paymentFormSubmitButton.prop("disabled", false);
        };

        var submit = function (success, error) {
            payment.tokenize(
                {
                    card_holder: $seamlessCardHolderInput.val(),
                    month: $seamlessExpiryMonthInput.val(),
                    year: $seamlessExpiryYearInput.val(),
                },
                function (token, cardData) {
                    success.call(this, token);
                },
                function (errors) {
                    error.call(this, errors);
                }
            );
        };

        return {
            init: init,
            submit: submit,
        };
    }();
    init();
    $paymentForm.show();
};

bankartInit(jQuery);

var bankartInitEvent = new Event("initBankart", {bubbles: true});
document.addEventListener("initBankart", function(event) {
    bankartInit(jQuery);
});
