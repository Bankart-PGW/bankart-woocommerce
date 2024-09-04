const mcVisaSettings = window.wc.wcSettings.getSetting('bankart_payment_gateway_mcvisa_cards_data', {});
const mcVisaLabel = window.wp.htmlEntities.decodeEntities(mcVisaSettings.title) || window.wp.i18n.__( 'Maestro Mastercard VISA cards', 'woocommerce-bankart-payment-gateway' );
const mcVisaContent = () => {
    return window.wp.htmlEntities.decodeEntities(mcVisaSettings.description || '');
};

const mcVisaBlockGateway = {
    name: 'bankart_payment_gateway_mcvisa_cards',
    label: mcVisaLabel,
    content: Object(window.wp.element.createElement)( mcVisaContent, null),
    edit: Object(window.wp.element.createElement)( mcVisaContent, null),
    canMakePayment: () => true,
    ariaLabel: mcVisaLabel,
    supports: {
        features: mcVisaSettings.supports,
    },
};

window.wc.wcBlocksRegistry.registerPaymentMethod( mcVisaBlockGateway );
