// Retrieve the settings for the payment gateway from WooCommerce settings
const cardSettings = window.wc.wcSettings.getSetting('bankart_payment_gateway_payment_cards_data', {});
// Retrieve the label for the payment method, using a default if not set
// This should use the plugin's text domain for translation
const cardLabel = window.wp.htmlEntities.decodeEntities(cardSettings.title) || window.wp.i18n.__( 'Payment cards', 'woocommerce-bankart-payment-gateway' );
// Define the content component for the payment method, which will display the description
const CardContent = () => {
    return window.wp.htmlEntities.decodeEntities(cardSettings.description || '');
};

// Define the Block_Gateway object, which contains the properties and methods required for the payment method
const CardBlockGateway = {

    // This name should match the name used in your PHP class for the payment method
    name: 'bankart_payment_gateway_payment_cards', 
    
    // The label that will be displayed for the payment method
    label: cardLabel,

    // The content that will be displayed when the payment method is selected
    content: Object(window.wp.element.createElement)( CardContent, null),

    // The edit component, which is typically the same as the content for simple payment methods
    edit: Object(window.wp.element.createElement)( CardContent, null),
    
     // Function to determine if the payment method can be used for the current payment
    canMakePayment: () => true,
   
    // The ARIA label for accessibility
    ariaLabel: cardLabel,

    // The features that the payment method supports, retrieved from the settings
    supports: {
        features: cardSettings.supports,
    },
};

// Register the payment method with the WooCommerce Blocks registry
window.wc.wcBlocksRegistry.registerPaymentMethod( CardBlockGateway );

