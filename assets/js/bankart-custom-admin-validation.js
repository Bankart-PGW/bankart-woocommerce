const formIds = [
    'input[name="woocommerce_bankart_payment_gateway_diners_cards_min_instalment"]',
    'input[name="woocommerce_bankart_payment_gateway_mcvisa_cards_min_instalment"]',
    'input[name="woocommerce_bankart_payment_gateway_payment_cards_min_instalment"]',
];

jQuery(document).ready(function($) {
    $('#mainform').on('submit', function(event) {
        for (const formId of formIds) {
            var minInstalment = $(formId).val();
            if (minInstalment < 1) {
                alert(wpTranslations.minInstalmentError);
                event.preventDefault(); // Prevent form submission
                return false;
            }
        }
    });
});