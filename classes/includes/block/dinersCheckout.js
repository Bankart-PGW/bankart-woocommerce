const dinersSettings = window.wc.wcSettings.getSetting('bankart_payment_gateway_diners_cards_data', {});
const dinersLabel = window.wp.htmlEntities.decodeEntities(dinersSettings.title) || window.wp.i18n.__( 'Diners cards', 'woocommerce-bankart-payment-gateway' );
const DinersContent = () => {
    return window.wp.htmlEntities.decodeEntities(dinersSettings.description || '');
};

const DinersBlockGateway = {
    name: 'bankart_payment_gateway_diners_cards', 
    label: dinersLabel,
    content: Object(window.wp.element.createElement)( DinersContent, null),
    edit: Object(window.wp.element.createElement)( DinersContent, null),
    canMakePayment: () => true,
    ariaLabel: dinersLabel,
    supports: {
        features: dinersSettings.supports,
    },
};

window.wc.wcBlocksRegistry.registerPaymentMethod( DinersBlockGateway );