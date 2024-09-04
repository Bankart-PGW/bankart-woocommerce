const flikSettings = window.wc.wcSettings.getSetting('bankart_payment_gateway_flik_payments_data', {});
const flikLabel = window.wp.htmlEntities.decodeEntities(flikSettings.title) || window.wp.i18n.__( 'Flik payments', 'woocommerce-bankart-payment-gateway' );
const FlikContent = () => {
    return window.wp.htmlEntities.decodeEntities(flikSettings.description || '');
};

const FlikBlockGateway = {
    name: 'bankart_payment_gateway_flik_payments',
    label: flikLabel,
    content: Object(window.wp.element.createElement)( FlikContent, null),
    edit: Object(window.wp.element.createElement)( FlikContent, null),
    canMakePayment: () => true,
    ariaLabel: flikLabel,
    supports: {
        features: flikSettings.supports,
    },
};

window.wc.wcBlocksRegistry.registerPaymentMethod( FlikBlockGateway );