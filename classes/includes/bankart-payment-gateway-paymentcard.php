<?php

class WC_BankartPaymentGateway_PaymentCard extends WC_Payment_Gateway
{
    public $id = 'payment_cards';

    public $view_transaction_url = BANKART_PAYMENT_GATEWAY_EXTENSION_URL . 'en/transactions/show/%s';

    /**
     * @var false|WP_User
     */
    protected $user;

    /**
     * @var WC_Order
     */
    protected $order;

    /**
     * @var string
     */
    protected $callbackUrl;

    /**
     * @var WC_Logger
     */
    protected $log;

    public function __construct()
    {
        $this->id = BANKART_PAYMENT_GATEWAY_EXTENSION_UID_PREFIX . $this->id;

        $this->has_fields = is_checkout_pay_page();

        $this->init_form_fields();
        $this->init_settings();
        
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        
        $this->bankart_set_translated_text();

        $this->callbackUrl = add_query_arg('wc-api', 'wc_' . $this->id, home_url('/'));

        $this->log = new WC_Logger();

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('woocommerce_api_wc_' . $this->id, [$this, 'process_callback']);
        
        if (get_parent_class($this) == 'WC_Payment_Gateway') {
            add_action('wp_enqueue_scripts', function () {
                wp_enqueue_style( 'bankart_style', plugins_url('/woocommerce-bankart-payment-gateway/assets/css/bankart.css'), false, BANKART_PAYMENT_GATEWAY_EXTENSION_VERSION, 'all');
                wp_register_script('bankart_payment_js', BANKART_PAYMENT_GATEWAY_EXTENSION_URL . 'js/integrated/payment.min.js', [], BANKART_PAYMENT_GATEWAY_EXTENSION_VERSION, false);
                wp_register_script('bankart_payment_gateway_js', plugins_url('/woocommerce-bankart-payment-gateway/assets/js/bankart-payment-gateway.js'), ['wp-i18n'], BANKART_PAYMENT_GATEWAY_EXTENSION_VERSION, false);
                wp_set_script_translations( 'bankart_payment_gateway_js', 'woocommerce-bankart-payment-gateway', BANKART_PAYMENT_GATEWAY_EXTENSION_BASEDIR . 'languages' );
            }, 999);
            add_filter('script_loader_tag', [$this, 'bankart_add_data_main_js'], 10, 2);
            add_filter('woocommerce_available_payment_gateways', [$this, 'bankart_hide_payment_gateways_on_pay_for_order_page'], 100, 1);
        }
    }

    protected function bankart_set_translated_text()
    {
        $this->method_title = __('Payment cards', 'woocommerce-bankart-payment-gateway');
        $this->method_description = __('For accepting card payments using Bankart Payment Gateway', 'woocommerce-bankart-payment-gateway');
    }
    
    public function bankart_add_data_main_js($tag, $handle) 
    {
        if ($handle !== 'bankart_payment_js') {
            return $tag;
        }
        return str_replace(' src', ' data-main="payment-js" src', $tag);
    }
 
    public function bankart_hide_payment_gateways_on_pay_for_order_page($available_gateways)
    {
        if (is_checkout_pay_page()) {
            global $wp;
            $this->order = new WC_Order($wp->query_vars['order-pay']);
            foreach ($available_gateways as $gateways_id => $gateways) {
                if ($gateways_id !== $this->order->get_payment_method()) {
                    unset($available_gateways[$gateways_id]);
                }
            }
        }
        return $available_gateways;
    }

    private function encodeOrderId($orderId)
    {
        return $orderId . '-' . date('YmdHis') . substr(sha1(uniqid()), 0, 10);
    }

    private function decodeOrderId($orderId)
    {
        if (strpos($orderId, '-') === false) {
            return $orderId;
        }

        $orderIdParts = explode('-', $orderId);

        if(count($orderIdParts) === 2) {
            $orderId = $orderIdParts[0];
        }

        /**
         * void/capture will prefix the transaction id
         */
        if(count($orderIdParts) === 3) {
            $orderId = $orderIdParts[1];
        }

        return $orderId;
    }

    public function process_payment($orderId)
    {
        // sets order status to pending payment

        $this->order = new WC_Order($orderId);
        $this->order->update_status('pending', __('Awaiting payment', 'woocommerce-bankart-payment-gateway'));

        // if using seamless, redirect user to the pay page
        if ($this->get_option('integrationKey') && !is_checkout_pay_page()) {
            return [
                'result' => 'success',
                'redirect' => $this->order->get_checkout_payment_url(false),
            ];
        }
        /**
         * order & user
         */
        $this->user = $this->order->get_user();

        /**
         * gateway client
         */
        WC_BankartPaymentGateway_Provider::autoloadClient();
        BankartPaymentGateway\Client\Client::setApiUrl(BANKART_PAYMENT_GATEWAY_EXTENSION_URL);
        $client = new BankartPaymentGateway\Client\Client(
            $this->get_option('apiUser'),
            htmlspecialchars_decode($this->get_option('apiPassword')),
            $this->get_option('apiKey'),
            $this->get_option('sharedSecret')
        );

        /**
         * gateway customer
         */
        $customer = new BankartPaymentGateway\Client\Data\Customer();
        $customer
            ->setBillingAddress1($this->order->get_billing_address_1())
            ->setBillingAddress2($this->order->get_billing_address_2())
            ->setBillingCity($this->order->get_billing_city())
            ->setBillingCountry($this->order->get_billing_country())
            ->setBillingPhone($this->order->get_billing_phone())
            ->setBillingPostcode($this->order->get_billing_postcode())
            ->setBillingState($this->order->get_billing_state())
            ->setCompany($this->order->get_billing_company())
            ->setEmail($this->order->get_billing_email())
            ->setFirstName($this->order->get_billing_first_name())
            ->setIpAddress(WC_Geolocation::get_ip_address()) // $this->order->get_customer_ip_address()
            ->setLastName($this->order->get_billing_last_name());

        /**
         * add shipping data for non-digital goods
         */
        if ($this->order->get_shipping_country()) {
            $customer
                ->setShippingAddress1($this->order->get_shipping_address_1())
                ->setShippingAddress2($this->order->get_shipping_address_2())
                ->setShippingCity($this->order->get_shipping_city())
                ->setShippingCompany($this->order->get_shipping_company())
                ->setShippingCountry($this->order->get_shipping_country())
                ->setShippingFirstName($this->order->get_shipping_first_name())
                ->setShippingLastName($this->order->get_shipping_last_name())
                ->setShippingPostcode($this->order->get_shipping_postcode())
                ->setShippingState($this->order->get_shipping_state());
        }

        /**
         * transaction
         */
        $transactionRequest = $this->get_option('transactionRequest');
        $transaction = null;
        switch ($transactionRequest) {
            case 'debit':
                $transaction = new \BankartPaymentGateway\Client\Transaction\Debit();
                break;
            case 'preauthorize':
            default:
                $transaction = new \BankartPaymentGateway\Client\Transaction\Preauthorize();
                break;
        }

        $orderTxId = $this->encodeOrderId($orderId);
        // keep track of last tx id 
        $this->order->add_meta_data('_orderTxId', $orderTxId, true); 
        $this->order->save_meta_data();

        $transaction->setTransactionId($orderTxId)
                    ->setAmount(floatval($this->order->get_total()))
                    ->setCurrency($this->order->get_currency())
                    ->setCustomer($customer)
                    ->setCallbackUrl($this->callbackUrl)
                    ->setCancelUrl(wc_get_checkout_url())
                    ->setSuccessUrl($this->paymentSuccessUrl($this->order))
                    ->setErrorUrl(add_query_arg(['gateway_return_result' => 'error'], $this->get_option('integrationKey') ? $this->order->get_checkout_payment_url(false) : wc_get_checkout_url()));

        $extraData = $this->extraData3DS();

        // instalments - 2nd condition is reduntant, but play it safe and check the admin settings
        if (!empty( $_POST[$this->id . '-instalments'])) {

            $inst_num = sanitize_text_field($_POST[$this->id . '-instalments']);

            if (!in_array($inst_num, ['', '00', '0', '01', '1']) && $inst_num <= $this->get_max_instalments()) {

                update_post_meta( $orderId, '_bankart_instalments', $inst_num);
                $extraData = array_merge($extraData, ["userField1" => $inst_num]);
            }
        }
		$transaction->setExtraData($extraData);
		
        /**
         * integration key is set -> seamless
         * if there is no token, the tokenization failed...
         */
        if ($this->get_option('integrationKey')) {
            if (! empty( $_POST['bankart-token'])) $transaction->setTransactionToken($_POST['bankart-token']);
            else return $this->paymentFailedResponse();
        }

        /**
         * transaction
         */
        switch ($transactionRequest) {
            case 'debit':
                $result = $client->debit($transaction);
                break;
            case 'preauthorize':
            default:
                $result = $client->preauthorize($transaction);
                break;
        }
        
        if ($result->getReturnType() == BankartPaymentGateway\Client\Transaction\Result::RETURN_TYPE_ERROR) {
            $error = $result->getFirstError();

            $this->log->add($this->id, 'Order ID: ' . $orderId . ' Error: ' . $error->getMessage());

            return $this->paymentFailedResponse();
        }


        if ($result->isSuccess()) {       
            if ($result->getReturnType() == BankartPaymentGateway\Client\Transaction\Result::RETURN_TYPE_REDIRECT) {
                return [
                    'result' => 'success',
                    'redirect' => $result->getRedirectUrl(),
                ];
            } 

            WC()->cart->empty_cart();

            return [
                'result' => 'success',
                'redirect' => $this->paymentSuccessUrl($this->order),
            ];
        }

        /**
         * something went wrong
         */
        return $this->paymentFailedResponse();
    }

    private function paymentSuccessUrl($order)
    {
        $url = $this->get_return_url($order);
        return $url . '&empty-cart';
    }

    private function paymentFailedResponse()
    {
        $this->order->update_status('failed', __('Payment failed or was declined', 'woocommerce-bankart-payment-gateway'));
        wc_add_notice(__('Payment failed or was declined', 'woocommerce-bankart-payment-gateway'), 'error');
        return [
            'result' => 'error',
            'redirect' => $this->get_return_url($this->order),
        ];
    }

    public function process_callback()
    {
        WC_BankartPaymentGateway_Provider::autoloadClient();

        BankartPaymentGateway\Client\Client::setApiUrl(BANKART_PAYMENT_GATEWAY_EXTENSION_URL);
        $client = new BankartPaymentGateway\Client\Client(
            $this->get_option('apiUser'),
            htmlspecialchars_decode($this->get_option('apiPassword')),
            $this->get_option('apiKey'),
            $this->get_option('sharedSecret')
        );

        if (!$client->validateCallbackWithGlobals()) {
            if (!headers_sent()) {
                http_response_code(400);
            }
            die("OK");
        }        
        
        $callbackResult = $client->readCallback(file_get_contents('php://input'));
        $this->order = new WC_Order($this->decodeOrderId($callbackResult->getTransactionId()));

        
        if ($callbackResult->getResult() == \BankartPaymentGateway\Client\Callback\Result::RESULT_OK) {
            switch ($callbackResult->getTransactionType()) {
                case \BankartPaymentGateway\Client\Callback\Result::TYPE_DEBIT:
                    // check if callback data is coming from the last (=newest+relevant) tx attempt, otherwise ignore it
                    if ($this->order->get_meta('_orderTxId') == $callbackResult->getTransactionId()) {
                        $this->order->payment_complete($callbackResult->getReferenceId());
                    }
                    break;
                case \BankartPaymentGateway\Client\Callback\Result::TYPE_PREAUTHORIZE:
                    // check if callback data is coming from the last (=newest+relevant) tx attempt, otherwise ignore it
                    if ($this->order->get_meta('_orderTxId') == $callbackResult->getTransactionId()) {
                        $preauthorizeStatus = $this->get_option('preauthorizeSuccess');
                        $this->order->set_status($preauthorizeStatus, __('Payment authorized. Awaiting capture/void.', 'woocommerce-bankart-payment-gateway'));
                        $this->order->set_transaction_id($callbackResult->getReferenceId());
                        $this->order->save();
                    }
                    break;
                case \BankartPaymentGateway\Client\Callback\Result::TYPE_CAPTURE:
                    $this->order->payment_complete($callbackResult->getReferenceId());
                    break;
                case \BankartPaymentGateway\Client\Callback\Result::TYPE_VOID:
                    $this->order->set_status('cancelled', __('Payment voided', 'woocommerce-bankart-payment-gateway'));
                    $this->order->set_transaction_id($callbackResult->getReferenceId());
                    $this->order->save();
                    break;
            }
        } elseif ($callbackResult->getResult() == \BankartPaymentGateway\Client\Callback\Result::RESULT_ERROR ) {
            $error = $callbackResult->getFirstError();
            if ($error->getCode() != \BankartPaymentGateway\Client\Transaction\Error::TRANSACTION_EXPIRED) {
                $this->order->set_status('failed', __('Error', 'woocommerce-bankart-payment-gateway'));
                if(null !== $callbackResult->getReferenceId()) {
                    $this->order->set_transaction_id($callbackResult->getReferenceId());
                }
                $this->order->save();
            }
            else if ($this->order->get_status() == 'pending') {
                $this->order->update_status('failed', __('Error', 'woocommerce-bankart-payment-gateway'));
            }
        }

        do_action('bankart_callback', $callbackResult);

        die("OK");
    }

    public function init_form_fields()
    {
        //$orderStatusList = wc_get_order_statuses();
        $this->form_fields = [
            'title' => [
                'title' => __('Title', 'woocommerce-bankart-payment-gateway'),
                'type' => 'text',
                'description' => __('Text displayed to the customer on the payment selection menu', 'woocommerce-bankart-payment-gateway'),
                'default' => __('Payment card', 'woocommerce-bankart-payment-gateway'),
            ],
            'description' => [
                'title' => __('Description', 'woocommerce-bankart-payment-gateway'),
                'type' => 'textarea',
                'description' => __('Description of the payment method displayed to the customer', 'woocommerce-bankart-payment-gateway'),
                'default' => __('Pay securely for your order with a payment card.', 'woocommerce-bankart-payment-gateway') . $this->method_title,
            ],
            'apiUser' => [
                'title' => __('API User', 'woocommerce-bankart-payment-gateway'),
                'type' => 'text',
                'description' => __('Your API username credential', 'woocommerce-bankart-payment-gateway'),
                'default' => '',
            ],
            'apiPassword' => [
                'title' => __('API Password', 'woocommerce-bankart-payment-gateway'),
                'type' => 'text',
                'description' => __('Your API password', 'woocommerce-bankart-payment-gateway'),
                'default' => '',
            ],
            'apiKey' => [
                'title' => __('API Key', 'woocommerce-bankart-payment-gateway'),
                'type' => 'text',
                'description' => __('Your payment connector API key', 'woocommerce-bankart-payment-gateway'),
                'default' => '',
            ],
            'sharedSecret' => [
                'title' => __('Shared Secret', 'woocommerce-bankart-payment-gateway'),
                'type' => 'text',
                'description' => __('Your payment connector shared secret', 'woocommerce-bankart-payment-gateway'),
                'default' => '',
            ],
            'integrationKey' => [
                'title' => __('Integration Key', 'woocommerce-bankart-payment-gateway'),
                'type' => 'text',
                'description' => __('Integration key for Payment.js fields', 'woocommerce-bankart-payment-gateway'),
                'default' => '',
            ],
            'transactionRequest' => [
                'title' => __('Transaction type', 'woocommerce-bankart-payment-gateway'),
                'type' => 'select',
                'description' => __('Select the option based on the agreement with your acquiring bank', 'woocommerce-bankart-payment-gateway'),
                'default' => 'debit',
                'options' => [
                    'debit' => __('Debit', 'woocommerce-bankart-payment-gateway'),
                    'preauthorize' => __('Preauthorize/Capture/Void', 'woocommerce-bankart-payment-gateway'),
                ],
            ],
            'preauthorizeSuccess' => [
                'title' => __('Preauthorize approved', 'woocommerce-bankart-payment-gateway'),
                'type' => 'select',
                'description' => __('Select the order status for successful preauthorization', 'woocommerce-bankart-payment-gateway'),
                'default' => 'on-hold',
                'options' => [
                    'on-hold' => _x( 'On hold', 'Order status', 'woocommerce' ),
                    'processing' => _x( 'Processing', 'Order status', 'woocommerce' ),
                ],
            ],
            'max_instalments' => [
                'title' => __('Installments', 'woocommerce-bankart-payment-gateway'),
                'type' => 'select',
                'description' => __('Select the option based on the agreement with your acquiring bank', 'woocommerce-bankart-payment-gateway'),
                'default' => '12',
                'options' => [
                    '1' => __('Instalments disabled', 'woocommerce-bankart-payment-gateway'),
                    '12' => __('Up to 12 instalments', 'woocommerce-bankart-payment-gateway'),
                    '24' => __('Up to 24 instalments', 'woocommerce-bankart-payment-gateway'),
                ],
            ],
            'instalments-description' => [
                'title' => __('Describe instalment option', 'woocommerce-bankart-payment-gateway'),
                'type' => 'textarea',
                'description' => __('Description of the instalments option displayed to the customer', 'woocommerce-bankart-payment-gateway'),
                'default' => __('Select the number of instalments, if your payment card supports selecting instalments at the point of sale', 'woocommerce-bankart-payment-gateway'),
            ],
            'min_instalment' => [
                'title' => __('Minimum instalment amount', 'woocommerce-bankart-payment-gateway'),
                'type' => 'number',
                'description' => __('Based on this amount the maximum number of allowed instalments will be calculated', 'woocommerce-bankart-payment-gateway'),
                'default' => '50',
            ],
			'force_challenge' => [
                'title' => __('Merchant prefers challange', 'woocommerce-bankart-payment-gateway'),
                'type' => 'select',
                'description' => __('Challaenge requested: 3DS Requestor preference', 'woocommerce-bankart-payment-gateway'),
				'default' => '0',
				'options' => [
                    '0' => __('No', 'woocommerce-bankart-payment-gateway'),
                    '1' => __('Yes', 'woocommerce-bankart-payment-gateway'),
				],
            ],
        ];
    }

    // for custom validation for certain fields
    public function validate_text_field( $key, $value ) {
		if(in_array($key, array('apiUser', 'apiPassword', 'apiKey', 'sharedSecret', 'integrationKey'))) {
            trim($value);
        }
		return parent::validate_text_field( $key, $value);
    }

    private function get_max_instalments()
    {
        $max_instalments_limit = $this->get_option('max_instalments', 1);
        $min_inst_amt =  $this->get_option('min_instalment', 1);
        $max_calc = floor(WC()->cart->get_total('not-view')/$min_inst_amt);
        return ($max_instalments_limit <= $max_calc) ? $max_instalments_limit : $max_calc;
    }

    private function get_instalments_select($max_instalments)
    {
        $instalments = range(2, $max_instalments);
        $instalmentSelect = '<option value="" selected="selected">-</option>';
        foreach ($instalments as $instalment) {
            $instalmentSelect .= '<option>' . $instalment . '</option>';
        }
        return $instalmentSelect;                                                                                                                             
    }

    public function payment_fields()
    {
        $max_instalments = $this->get_max_instalments();

        if (is_checkout_pay_page())
        {
            wp_enqueue_script('bankart_style');
            wp_enqueue_script('bankart_payment_js');
            wp_enqueue_script('bankart_payment_gateway_js');

            $years = range(date('Y'), date('Y') + 15);
            $yearSelect = '';
            foreach ($years as $year) {
                $yearSelect .= '<option>' . $year . '</option>';
            }

            echo '<script>window.integrationKey="' . $this->get_option('integrationKey', 'woocommerce-bankart-payment-gateway') . '";
            window.bankartFormId="' . uniqid('', true) . '"</script>
            <div id="bankart-payment-form">
                <input type="hidden" name="bankart-token" id="bankart-token" />
                <div class="bankart-row">
                    <div class="bankart-col">
                        <label for="bankart-card_holder" class="bankart-label">' . esc_html__('Card holder', 'woocommerce-bankart-payment-gateway') . '</label>
                    </div>
                    <div class="bankart-col-2">
                        <input type="text" class="bankart-input-wrapper bankart-input" id="bankart-card_holder" maxlength="26" autocomplete="cc-name">
                        <div id="bankart-error-card_holder" class="bankart-error-text" style="display: none;"></div>
                    </div>
                </div>
                <div class="bankart-row">
                    <div class="bankart-col">
                        <label for="bankart-card-number" class="bankart-label">' . esc_html__('Card number', 'woocommerce-bankart-payment-gateway') . '</label>
                    </div>
                    <div class="bankart-col-2">
                        <div class="bankart-input-wrapper" id="bankart-card-number"></div>
                        <div id="bankart-error-card-number" class="bankart-error-text" style="display: none;"></div>
                    </div>
                </div>
                <div class="bankart-row">
                    <div class="bankart-col">
                        <label for="bankart-cvv" class="bankart-label">' . esc_html__('CVV2/CVC2', 'woocommerce-bankart-payment-gateway') . '</label>
                    </div>
                    <div class="bankart-col-2">
                        <div class="bankart-input-wrapper" id="bankart-cvv"></div>
                        <div id="bankart-error-cvv" class="bankart-error-text" style="display: none;"></div>
                    </div>
                </div>
                <div class="bankart-row">
                    <div class="bankart-col">
                        <label for="bankart-expiry" class="bankart-label">' . esc_html__('Expiration date', 'woocommerce-bankart-payment-gateway') . '</label>
                    </div>
                    <div class="bankart-col-2">
                        <select type="text" class="bankart-input-wrapper bankart-date" id="bankart-expiry-month" autocomplete="cc-exp-month">
                            <option value="" selected="selected">--</option>
                            <option>01</option>
                            <option>02</option>
                            <option>03</option>
                            <option>04</option>
                            <option>05</option>
                            <option>06</option>
                            <option>07</option>
                            <option>08</option>
                            <option>09</option>
                            <option>10</option>
                            <option>11</option>
                            <option>12</option>
                        </select>
                        <select type="text" class="bankart-input-wrapper bankart-date-2" id="bankart-expiry-year" autocomplete="cc-exp-year">
                            <option value="" selected="selected">----</option>'
                            . $yearSelect .
                        '</select>
                        <div id="bankart-error-expiry-month" class="bankart-error-text" style="display: none;"></div>
                        <div id="bankart-error-expiry-year" class="bankart-error-text" style="display: none;"></div>
                    </div>
                </div>
            </div>', 
                ($max_instalments > 1) ?
                    '<div id="bankart-instalments-form">
                        <div class="bankart-row">
                            <div class="bankart-text">' . wpautop(wptexturize($this->get_option('instalments-description'))) . '</div>
                        </div>
                        <div class="bankart-row">
                            <div class="bankart-col">
                                <label for="' . $this->id . '_instalments" class="bankart-label">' . esc_html__('Number of instalments', 'woocommerce-bankart-payment-gateway') . '</label>
                            </div>                        
                            <div class="bankart-col-2">
                                <select type="text" id="bankart-instalments" class="bankart-input-wrapper" name="' . $this->id . '-instalments">' . $this->get_instalments_select($max_instalments) . '</select>
                            </div>
                        </div>
                    </div>'
                : "",
                '<div class="clear"></div>
            </div>
            <script>
            if (typeof bankartInitEvent != "undefined") { 
                var bankartForm = document.getElementById("bankart-payment-form");
                bankartForm.dispatchEvent(bankartInitEvent);
              }
            </script>';
        }
        else {
            if(!empty($this->description)) echo wpautop(wptexturize($this->description));
            if(!$this->get_option('integrationKey') &&  $max_instalments > 1) {
                echo '<br>'. wpautop(wptexturize($this->get_option('instalments-description'))) . '<select type="text" name="' . $this->id . '-instalments">' . $this->get_instalments_select($max_instalments) . '</select>';
            }
        }
    }
    /**
     * @throws Exception
     * @return array
     */
    private function extraData3DS()
    {
        $extraData = [
            /**
             * Browser 3ds data injected by payment.js
             */
            // 3ds:browserAcceptHeader
            // 3ds:browserIpAddress
            // 3ds:browserJavaEnabled
            // 3ds:browserLanguage
            // 3ds:browserColorDepth
            // 3ds:browserScreenHeight
            // 3ds:browserScreenWidth
            // 3ds:browserTimezone
            // 3ds:browserUserAgent

            /**
             * force 3ds flow
             */
            // '3dsecure' => 'mandatory',

            /**
             * Additional 3ds 2.0 data
             */
            '3ds:addCardAttemptsDay' => $this->addCardAttemptsDay(),
            '3ds:authenticationIndicator' => $this->authenticationIndicator(),
            '3ds:billingAddressLine3' => $this->billingAddressLine3(),
            '3ds:billingShippingAddressMatch' => $this->billingShippingAddressMatch(),
            '3ds:browserChallengeWindowSize' => $this->browserChallengeWindowSize(),
            '3ds:cardholderAccountAgeIndicator' => $this->cardholderAccountAgeIndicator(),
            '3ds:cardHolderAccountChangeIndicator' => $this->cardHolderAccountChangeIndicator(),
            '3ds:cardholderAccountDate' => $this->cardholderAccountDate(),
            '3ds:cardholderAccountLastChange' => $this->cardholderAccountLastChange(),
            '3ds:cardholderAccountLastPasswordChange' => $this->cardholderAccountLastPasswordChange(),
            '3ds:cardholderAccountPasswordChangeIndicator' => $this->cardholderAccountPasswordChangeIndicator(),
            '3ds:cardholderAccountType' => $this->cardholderAccountType(),
            '3ds:cardHolderAuthenticationData' => $this->cardHolderAuthenticationData(),
            '3ds:cardholderAuthenticationDateTime' => $this->cardholderAuthenticationDateTime(),
            '3ds:cardholderAuthenticationMethod' => $this->cardholderAuthenticationMethod(),
            '3ds:challengeIndicator' => $this->challengeIndicator(),
            '3ds:channel' => $this->channel(),
            '3ds:deliveryEmailAddress' => $this->deliveryEmailAddress(),
            '3ds:deliveryTimeframe' => $this->deliveryTimeframe(),
            '3ds:giftCardAmount' => $this->giftCardAmount(),
            '3ds:giftCardCount' => $this->giftCardCount(),
            '3ds:giftCardCurrency' => $this->giftCardCurrency(),
            '3ds:homePhoneCountryPrefix' => $this->homePhoneCountryPrefix(),
            '3ds:homePhoneNumber' => $this->homePhoneNumber(),
            '3ds:mobilePhoneCountryPrefix' => $this->mobilePhoneCountryPrefix(),
            '3ds:mobilePhoneNumber' => $this->mobilePhoneNumber(),
            '3ds:paymentAccountAgeDate' => $this->paymentAccountAgeDate(),
            '3ds:paymentAccountAgeIndicator' => $this->paymentAccountAgeIndicator(),
            '3ds:preOrderDate' => $this->preOrderDate(),
            '3ds:preOrderPurchaseIndicator' => $this->preOrderPurchaseIndicator(),
            '3ds:priorAuthenticationData' => $this->priorAuthenticationData(),
            '3ds:priorAuthenticationDateTime' => $this->priorAuthenticationDateTime(),
            '3ds:priorAuthenticationMethod' => $this->priorAuthenticationMethod(),
            '3ds:priorReference' => $this->priorReference(),
            '3ds:purchaseCountSixMonths' => $this->purchaseCountSixMonths(),
            '3ds:purchaseDate' => $this->purchaseDate(),
            '3ds:purchaseInstalData' => $this->purchaseInstalData(),
            '3ds:recurringExpiry' => $this->recurringExpiry(),
            '3ds:recurringFrequency' => $this->recurringFrequency(),
            '3ds:reorderItemsIndicator' => $this->reorderItemsIndicator(),
            '3ds:shipIndicator' => $this->shipIndicator(),
            '3ds:shippingAddressFirstUsage' => $this->shippingAddressFirstUsage(),
            '3ds:shippingAddressLine3' => $this->shippingAddressLine3(),
            '3ds:shippingAddressUsageIndicator' => $this->shippingAddressUsageIndicator(),
            '3ds:shippingNameEqualIndicator' => $this->shippingNameEqualIndicator(),
            '3ds:suspiciousAccountActivityIndicator' => $this->suspiciousAccountActivityIndicator(),
            '3ds:transactionActivityDay' => $this->transactionActivityDay(),
            '3ds:transactionActivityYear' => $this->transactionActivityYear(),
            '3ds:transType' => $this->transType(),
            '3ds:workPhoneCountryPrefix' => $this->workPhoneCountryPrefix(),
            '3ds:workPhoneNumber' => $this->workPhoneNumber(),
        ];

        return array_filter($extraData, function ($data) {
            return $data !== null;
        });
    }

    /**
     * 3ds:addCardAttemptsDay
     * Number of Add Card attempts in the last 24 hours.
     *
     * @return int|null
     */
    private function addCardAttemptsDay()
    {
        return null;
    }

    /**
     * 3ds:authenticationIndicator
     * Indicates the type of Authentication request. This data element provides additional information to the ACS to determine the best approach for handling an authentication request.
     * 01 -> Payment transaction
     * 02 -> Recurring transaction
     * 03 -> Installment transaction
     * 04 -> Add card
     * 05 -> Maintain card
     * 06 -> Cardholder verification as part of EMV token ID&V
     *
     * @return string|null
     */
    private function authenticationIndicator()
    {
        return null;
    }

    /**
     * 3ds:billingAddressLine3
     * Line 3 of customer's billing address
     *
     * @return string|null
     */
    private function billingAddressLine3()
    {
        return null;
    }

    /**
     * 3ds:billingShippingAddressMatch
     * Indicates whether the Cardholder Shipping Address and Cardholder Billing Address are the same.
     * Y -> Shipping Address matches Billing Address
     * N -> Shipping Address does not match Billing Address
     *
     * @return string|null
     */
    private function billingShippingAddressMatch()
    {
        return null;
    }

    /**
     * 3ds:browserChallengeWindowSize
     * Dimensions of the challenge window that has been displayed to the Cardholder. The ACS shall reply with content that is formatted to appropriately render in this window to provide the best possible user experience.
     * 01 -> 250 x 400
     * 02 -> 390 x 400
     * 03 -> 500 x 600
     * 04 -> 600 x 400
     * 05 -> Full screen
     *
     * @return string|null
     */
    private function browserChallengeWindowSize()
    {
        return '05';
    }

    /**
     * 3ds:cardholderAccountAgeIndicator
     * Length of time that the cardholder has had the account with the 3DS Requestor.
     * 01 -> No account (guest check-out)
     * 02 -> During this transaction
     * 03 -> Less than 30 days
     * 04 -> 30 - 60 days
     * 05 -> More than 60 days
     *
     * @return string|null
     */
    private function cardholderAccountAgeIndicator()
    {
        return null;
    }

    /**
     * 3ds:cardHolderAccountChangeIndicator
     * Length of time since the cardholder’s account information with the 3DS Requestor waslast changed. Includes Billing or Shipping address, new payment account, or new user(s) added.
     * 01 -> Changed during this transaction
     * 02 -> Less than 30 days
     * 03 -> 30 - 60 days
     * 04 -> More than 60 days
     *
     * @return string|null
     */
    private function cardHolderAccountChangeIndicator()
    {
        return null;
    }

    /**
     * Date that the cardholder opened the account with the 3DS Requestor. Format: YYYY-MM-DD
     * Example: 2019-05-12
     *
     * @throws Exception
     * @return string|null
     */
    private function cardholderAccountDate()
    {
        if (!$this->user) {
            return null;
        }

        return $this->user->user_registered ? (new DateTime($this->user->user_registered))->format('Y-m-d') : null;
    }

    /**
     * 3ds:cardholderAccountLastChange
     * Date that the cardholder’s account with the 3DS Requestor was last changed. Including Billing or Shipping address, new payment account, or new user(s) added. Format: YYYY-MM-DD
     * Example: 2019-05-12
     *
     * @throws Exception
     * @return string|null
     */
    private function cardholderAccountLastChange()
    {
        if (!$this->user) {
            return null;
        }

        $lastUpdate = get_user_meta($this->user->ID, 'last_update', true);

        return $lastUpdate ? (new DateTime('@' . $lastUpdate))->format('Y-m-d') : null;
    }

    /**
     * 3ds:cardholderAccountLastPasswordChange
     * Date that cardholder’s account with the 3DS Requestor had a password change or account reset. Format: YYYY-MM-DD
     * Example: 2019-05-12
     *
     * @return string|null
     */
    private function cardholderAccountLastPasswordChange()
    {
        return null;
    }

    /**
     * 3ds:cardholderAccountPasswordChangeIndicator
     * Length of time since the cardholder’s account with the 3DS Requestor had a password change or account reset.
     * 01 -> No change
     * 02 -> Changed during this transaction
     * 03 -> Less than 30 days
     * 04 -> 30 - 60 days
     * 05 -> More than 60 days
     *
     * @return string|null
     */
    private function cardholderAccountPasswordChangeIndicator()
    {
        return null;
    }

    /**
     * 3ds:cardholderAccountType
     * Indicates the type of account. For example, for a multi-account card product.
     * 01 -> Not applicable
     * 02 -> Credit
     * 03 -> Debit
     * 80 -> JCB specific value for Prepaid
     *
     * @return string|null
     */
    private function cardholderAccountType()
    {
        return null;
    }

    /**
     * 3ds:cardHolderAuthenticationData
     * Data that documents and supports a specific authentication process. In the current version of the specification, this data element is not defined in detail, however the intention is that for each 3DS Requestor Authentication Method, this field carry data that the ACS can use to verify the authentication process.
     *
     * @return string|null
     */
    private function cardHolderAuthenticationData()
    {
        return null;
    }

    /**
     * 3ds:cardholderAuthenticationDateTime
     * Date and time in UTC of the cardholder authentication. Format: YYYY-MM-DD HH:mm
     * Example: 2019-05-12 18:34
     *
     * @return string|null
     */
    private function cardholderAuthenticationDateTime()
    {
        return null;
    }

    /**
     * 3ds:cardholderAuthenticationMethod
     * Mechanism used by the Cardholder to authenticate to the 3DS Requestor.
     * 01 -> No 3DS Requestor authentication occurred (i.e. cardholder "logged in" as guest)
     * 02 -> Login to the cardholder account at the 3DS Requestor system using 3DS Requestor's own credentials
     * 03 -> Login to the cardholder account at the 3DS Requestor system using federated ID
     * 04 -> Login to the cardholder account at the 3DS Requestor system using issuer credentials
     * 05 -> Login to the cardholder account at the 3DS Requestor system using third-party authentication
     * 06 -> Login to the cardholder account at the 3DS Requestor system using FIDO Authenticator
     *
     * @return string|null
     */
    private function cardholderAuthenticationMethod()
    {
        return null;
    }

    /**
     * 3ds:challengeIndicator
     * Indicates whether a challenge is requested for this transaction. For example: For 01-PA, a 3DS Requestor may have concerns about the transaction, and request a challenge.
     * 01 -> No preference
     * 02 -> No challenge requested
     * 03 -> Challenge requested: 3DS Requestor Preference
     * 04 -> Challenge requested: Mandate
     *
     * @return string|null
     */
    private function challengeIndicator()
    {
        if( $this->get_option('force_challenge') == '1'){
			return '03';
		}else{
			return null;
		}
    }

    /**
     * 3ds:channel
     * Indicates the type of channel interface being used to initiate the transaction
     * 01 -> App-based
     * 02 -> Browser
     * 03 -> 3DS Requestor Initiated
     *
     * @return string|null
     */
    private function channel()
    {
        return null;
    }

    /**
     * 3ds:deliveryEmailAddress
     * For electronic delivery, the email address to which the merchandise was delivered.
     *
     * @return string|null
     */
    private function deliveryEmailAddress()
    {
        return null;
    }

    /**
     * 3ds:deliveryTimeframe
     * Indicates the merchandise delivery timeframe.
     * 01 -> Electronic Delivery
     * 02 -> Same day shipping
     * 03 -> Overnight shipping
     * 04 -> Two-day or more shipping
     *
     * @return string|null
     */
    private function deliveryTimeframe()
    {
        return null;
    }

    /**
     * 3ds:giftCardAmount
     * For prepaid or gift card purchase, the purchase amount total of prepaid or gift card(s) in major units (for example, USD 123.45 is 123).
     *
     * @return string|null
     */
    private function giftCardAmount()
    {
        return null;
    }

    /**
     * 3ds:giftCardCount
     * For prepaid or gift card purchase, total count of individual prepaid or gift cards/codes purchased. Field is limited to 2 characters.
     *
     * @return string|null
     */
    private function giftCardCount()
    {
        return null;
    }

    /**
     * 3ds:giftCardCurrency
     * For prepaid or gift card purchase, the currency code of the card
     *
     * @return string|null
     */
    private function giftCardCurrency()
    {
        return null;
    }

    /**
     * 3ds:homePhoneCountryPrefix
     * Country Code of the home phone, limited to 1-3 characters
     *
     * @return string|null
     */
    private function homePhoneCountryPrefix()
    {
        return null;
    }

    /**
     * 3ds:homePhoneNumber
     * subscriber section of the number, limited to maximum 15 characters.
     *
     * @return string|null
     */
    private function homePhoneNumber()
    {
        return null;
    }

    /**
     * 3ds:mobilePhoneCountryPrefix
     * Country Code of the mobile phone, limited to 1-3 characters
     *
     * @return string|null
     */
    private function mobilePhoneCountryPrefix()
    {
        return null;
    }

    /**
     * 3ds:mobilePhoneNumber
     * subscriber section of the number, limited to maximum 15 characters.
     *
     * @return string|null
     */
    private function mobilePhoneNumber()
    {
        return null;
    }

    /**
     * 3ds:paymentAccountAgeDate
     * Date that the payment account was enrolled in the cardholder’s account with the 3DS Requestor. Format: YYYY-MM-DD
     * Example: 2019-05-12
     *
     * @return string|null
     */
    private function paymentAccountAgeDate()
    {
        return null;
    }

    /**
     * 3ds:paymentAccountAgeIndicator
     * Indicates the length of time that the payment account was enrolled in the cardholder’s account with the 3DS Requestor.
     * 01 -> No account (guest check-out)
     * 02 -> During this transaction
     * 03 -> Less than 30 days
     * 04 -> 30 - 60 days
     * 05 -> More than 60 days
     *
     * @return string|null
     */
    private function paymentAccountAgeIndicator()
    {
        return null;
    }

    /**
     * 3ds:preOrderDate
     * For a pre-ordered purchase, the expected date that the merchandise will be available.
     * Format: YYYY-MM-DD
     *
     * @return string|null
     */
    private function preOrderDate()
    {
        return null;
    }

    /**
     * 3ds:preOrderPurchaseIndicator
     * Indicates whether Cardholder is placing an order for merchandise with a future availability or release date.
     * 01 -> Merchandise available
     * 02 -> Future availability
     *
     * @return string|null
     */
    private function preOrderPurchaseIndicator()
    {
        return null;
    }

    /**
     * 3ds:priorAuthenticationData
     * Data that documents and supports a specfic authentication porcess. In the current version of the specification this data element is not defined in detail, however the intention is that for each 3DS Requestor Authentication Method, this field carry data that the ACS can use to verify the authentication process. In future versionsof the application, these details are expected to be included. Field is limited to maximum 2048 characters.
     *
     * @return string|null
     */
    private function priorAuthenticationData()
    {
        return null;
    }

    /**
     * 3ds:priorAuthenticationDateTime
     * Date and time in UTC of the prior authentication. Format: YYYY-MM-DD HH:mm
     * Example: 2019-05-12 18:34
     *
     * @return string|null
     */
    private function priorAuthenticationDateTime()
    {
        return null;
    }

    /**
     * 3ds:priorAuthenticationMethod
     * Mechanism used by the Cardholder to previously authenticate to the 3DS Requestor.
     * 01 -> Frictionless authentication occurred by ACS
     * 02 -> Cardholder challenge occurred by ACS
     * 03 -> AVS verified
     * 04 -> Other issuer methods
     *
     * @return string|null
     */
    private function priorAuthenticationMethod()
    {
        return null;
    }

    /**
     * 3ds:priorReference
     * This data element provides additional information to the ACS to determine the best approach for handling a request. The field is limited to 36 characters containing ACS Transaction ID for a prior authenticated transaction (for example, the first recurring transaction that was authenticated with the cardholder).
     *
     * @return string|null
     */
    private function priorReference()
    {
        return null;
    }

    /**
     * 3ds:purchaseCountSixMonths
     * Number of purchases with this cardholder account during the previous six months.
     *
     * @return int
     */
    private function purchaseCountSixMonths()
    {
        if (!$this->user) {
            return null;
        }

        $count = 0;
        foreach (['processing', 'completed', 'refunded', 'cancelled', 'authorization'] as $status) {
            $orders = wc_get_orders([
                'customer' => $this->user->ID,
                'limit' => -1,
                'status' => $status,
                'date_after' => '6 months ago',
            ]);
            $count += count($orders);
        }
        return $count;
    }

    /**
     * 3ds:purchaseDate
     * Date and time of the purchase, expressed in UTC. Format: YYYY-MM-DD
     **Note: if omitted we put in today's date
     *
     * @return string|null
     */
    private function purchaseDate()
    {
        return null;
    }

    /**
     * 3ds:purchaseInstalData
     * Indicates the maximum number of authorisations permitted for instalment payments. The field is limited to maximum 3 characters and value shall be greater than 1. The fields is required if the Merchant and Cardholder have agreed to installment payments, i.e. if 3DS Requestor Authentication Indicator = 03. Omitted if not an installment payment authentication.
     *
     * @return string|null
     */
    private function purchaseInstalData()
    {
        return null;
    }

    /**
     * 3ds:recurringExpiry
     * Date after which no further authorizations shall be performed. This field is required for 01-PA and for 02-NPA, if 3DS Requestor Authentication Indicator = 02 or 03.
     * Format: YYYY-MM-DD
     *
     * @return string|null
     */
    private function recurringExpiry()
    {
        return null;
    }

    /**
     * 3ds:recurringFrequency
     * Indicates the minimum number of days between authorizations. The field is limited to maximum 4 characters. This field is required if 3DS Requestor Authentication Indicator = 02 or 03.
     *
     * @return string|null
     */
    private function recurringFrequency()
    {
        return null;
    }

    /**
     * 3ds:reorderItemsIndicator
     * Indicates whether the cardholder is reoreding previously purchased merchandise.
     * 01 -> First time ordered
     * 02 -> Reordered
     *
     * @return string|null
     */
    private function reorderItemsIndicator()
    {
        return null;
    }

    /**
     * 3ds:shipIndicator
     * Indicates shipping method chosen for the transaction. Merchants must choose the Shipping Indicator code that most accurately describes the cardholder's specific transaction. If one or more items are included in the sale, use the Shipping Indicator code for the physical goods, or if all digital goods, use the code that describes the most expensive item.
     * 01 -> Ship to cardholder's billing address
     * 02 -> Ship to another verified address on file with merchant
     * 03 -> Ship to address that is different than the cardholder's billing address
     * 04 -> "Ship to Store" / Pick-up at local store (Store address shall be populated in shipping address fields)
     * 05 -> Digital goods (includes online services, electronic gift cards and redemption codes)
     * 06 -> Travel and Event tickets, not shipped
     * 07 -> Other (for example, Gaming, digital services not shipped, emedia subscriptions, etc.)
     *
     * @return string|null
     */
    private function shipIndicator()
    {
        return null;
    }

    /**
     * 3ds:shippingAddressFirstUsage
     * Date when the shipping address used for this transaction was first used with the 3DS Requestor. Format: YYYY-MM-DD
     * Example: 2019-05-12
     *
     * @throws Exception
     * @return string|null
     */
    private function shippingAddressFirstUsage()
    {
        if (!$this->user) {
            return null;
        }

        $orders = wc_get_orders([
            'customer' => $this->user->ID,
            'shipping_address_1' => $this->order->get_shipping_address_1(),
            'orderby' => 'date',
            'order' => 'ASC',
            'limit' => 1,
            'paginate' => false,
        ]);

        /** @var WC_Order $firstOrder */
        $firstOrder = reset($orders);
        $firstOrderDate = $firstOrder && $firstOrder->get_date_created() ? $firstOrder->get_date_created() : new WC_DateTime();
        return $firstOrderDate->format('Y-m-d');
    }

    /**
     * 3ds:shippingAddressLine3
     * Line 3 of customer's shipping address
     *
     * @return string|null
     */
    private function shippingAddressLine3()
    {
        return null;
    }

    /**
     * 3ds:shippingAddressUsageIndicator
     * Indicates when the shipping address used for this transaction was first used with the 3DS Requestor.
     * 01 -> This transaction
     * 02 -> Less than 30 days
     * 03 -> 30 - 60 days
     * 04 -> More than 60 days.
     *
     * @return string|null
     */
    private function shippingAddressUsageIndicator()
    {
        return null;
    }

    /**
     * 3ds:shippingNameEqualIndicator
     * Indicates if the Cardholder Name on the account is identical to the shipping Name used for this transaction.
     * 01 -> Account Name identical to shipping Name
     * 02 -> Account Name different than shipping Name
     *
     * @return string|null
     */
    private function shippingNameEqualIndicator()
    {
        return null;
    }

    /**
     * 3ds:suspiciousAccountActivityIndicator
     * Indicates whether the 3DS Requestor has experienced suspicious activity (including previous fraud) on the cardholder account.
     * 01 -> No suspicious activity has been observed
     * 02 -> Suspicious activity has been observed
     *
     * @return string|null
     */
    private function suspiciousAccountActivityIndicator()
    {
        return null;
    }

    /**
     * 3ds:transactionActivityDay
     * Number of transactions (successful and abandoned) for this cardholder account with the 3DS Requestor across all payment accounts in the previous 24 hours.
     *
     * @return string|null
     */
    private function transactionActivityDay()
    {
        return null;
    }

    /**
     * 3ds:transactionActivityYear
     * Number of transactions (successful and abandoned) for this cardholder account with the 3DS Requestor across all payment accounts in the previous year.
     *
     * @return string|null
     */
    private function transactionActivityYear()
    {
        return null;
    }

    /**
     * 3ds:transType
     * Identifies the type of transaction being authenticated. The values are derived from ISO 8583.
     * 01 -> Goods / Service purchase
     * 03 -> Check Acceptance
     * 10 -> Account Funding
     * 11 -> Quasi-Cash Transaction
     * 28 -> Prepaid activation and Loan
     *
     * @return string|null
     */
    private function transType()
    {
        return null;
    }

    /**
     * 3ds:workPhoneCountryPrefix
     * Country Code of the work phone, limited to 1-3 characters
     *
     * @return string|null
     */
    private function workPhoneCountryPrefix()
    {
        return null;
    }

    /**
     * 3ds:workPhoneNumber
     * subscriber section of the number, limited to maximum 15 characters.
     *
     * @return string|null
     */
    private function workPhoneNumber()
    {
        return null;
    }
}
