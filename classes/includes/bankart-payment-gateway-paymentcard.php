<?php

use BankartPaymentGateway\Client\Client;
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
            ->setBillingAddress1(substr($this->order->get_billing_address_1(),0,50))
            ->setBillingAddress2($this->order->get_billing_address_2())
            ->setBillingCity($this->order->get_billing_city())
            ->setBillingCountry($this->order->get_billing_country())
            ->setBillingPhone($this->order->get_billing_phone())
            ->setBillingPostcode($this->order->get_billing_postcode())
            //->setBillingState($this->order->get_billing_state())
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
                ->setShippingAddress1(substr($this->order->get_shipping_address_1(),0,50))
                ->setShippingAddress2($this->order->get_shipping_address_2())
                ->setShippingCity($this->order->get_shipping_city())
                ->setShippingCompany($this->order->get_shipping_company())
                ->setShippingCountry($this->order->get_shipping_country())
                ->setShippingFirstName($this->order->get_shipping_first_name())
                ->setShippingLastName($this->order->get_shipping_last_name())
                ->setShippingPostcode($this->order->get_shipping_postcode());
                //->setShippingState($this->order->get_shipping_state());
                
            if ($this->order->get_shipping_postcode()) {
                $customer->setShippingPostcode($this->order->get_shipping_postcode());
            } else {
                $customer->setShippingPostcode('n/a');
            }
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

		$transaction->setMerchantTransactionId($orderTxId)
                    ->setAmount(floatval($this->order->get_total()))
                    ->setCurrency($this->order->get_currency())
                    ->setCustomer($customer)
                    ->setCallbackUrl($this->callbackUrl)
                    ->setCancelUrl(wc_get_checkout_url())
                    ->setSuccessUrl($this->paymentSuccessUrl($this->order))
                    ->setErrorUrl(add_query_arg(['gateway_return_result' => 'error'], $this->get_option('integrationKey') ? $this->order->get_checkout_payment_url(false) : wc_get_checkout_url()));
        
		//Set the 3DS object
		$threeDSdata = new \BankartPaymentGateway\Client\Data\ThreeDSecureData();
		
		/**
		 * challengeIndicator
		 * Indicates whether a challenge is requested for this transaction. For example: For 01-PA, a 3DS Requestor may have concerns about the transaction, and request a challenge.
		 * 01 -> No preference
		 * 02 -> No challenge requested
		 * 03 -> Challenge requested: 3DS Requestor Preference
		 * 04 -> Challenge requested: Mandate
		 */
		if( $this->get_option('force_challenge') == '1'){
				$threeDSdata->setChallengeIndicator('03');
		}
		
		/**
		 * browserChallengeWindowSize
		 * Dimensions of the challenge window that has been displayed to the Cardholder. 
		 * The ACS shall reply with content that is formatted to appropriately render in this window to provide the best possible user experience.
		 * 01 -> 250 x 400
		 * 02 -> 390 x 400
		 * 03 -> 500 x 600
		 * 04 -> 600 x 400
		 * 05 -> Full screen
		 */
		$threeDSdata->setBrowserChallengeWindowSize('05');
		
		/**
		 * cardholderAccountDate
		 * Date that the cardholder opened the account with the 3DS Requestor. Format: YYYY-MM-DD
		 * Example: 2019-05-12
		 */
		if ($this->user){ 
			$threeDSdata->setCardholderAccountDate($this->user->user_registered ? (new DateTime($this->user->user_registered))->format('Y-m-d') : null);
		}
		
		/**
		 * cardholderAccountLastChange
		 * Date that the cardholder’s account with the 3DS Requestor was last changed. Including Billing or Shipping address, new payment account, or new user(s) added. Format: YYYY-MM-DD
		 * Example: 2019-05-12
		*/
        if ($this->user) {
            $lastUpdate = get_user_meta($this->user->ID, 'last_update', true);

			$threeDSdata->setCardholderAccountLastChange($lastUpdate ? (new DateTime('@' . $lastUpdate))->format('Y-m-d') : null);
        }

		/**
		 * purchaseCountSixMonths
		 * Number of purchases with this cardholder account during the previous six months.
		 *
		 */
		if ($this->user) { 
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
			$threeDSdata->setPurchaseCountSixMonths($count);
		}
    
		/**
		 * shippingAddressFirstUsage
		 * Date when the shipping address used for this transaction was first used with the 3DS Requestor. Format: YYYY-MM-DD
		 * Example: 2019-05-12
		 */
		if ($this->user) {
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
			$threeDSdata->setShippingAddressFirstUsage($firstOrderDate->format('Y-m-d'));			 
		}
		
		$transaction->setThreeDSecureData($threeDSdata);
		
		
        // instalments - 2nd condition is reduntant, but play it safe and check the admin settings
        if (!empty( $_POST[$this->id . '-instalments'])) {

            $inst_num = sanitize_text_field($_POST[$this->id . '-instalments']);

            if (!in_array($inst_num, ['', '00', '0', '01', '1']) && $inst_num <= $this->get_max_instalments()) {
                #HPOS
				#instead
				#update_post_meta( $orderId, '_bankart_instalments', $inst_num);
				$order = wc_get_order($orderId);
				$order->update_meta_data('_bankart_instalments', $inst_num);
				$order->save();
				#END HPOS
				
				$transaction->addExtraData('userField1', $inst_num);
			}
        }
        $transaction->addExtraData('platform', Client::PLATFORM);
		
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

            $this->log->add($this->id, 'Order ID: ' . $orderId . ' Error1: ' . $error->getMessage());
			
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
        } else{
			$error = $result->getFirstError();
            $this->log->add($this->id, 'Order ID: ' . $orderId . ' Error2: ' . $error->getMessage());
            return $this->paymentFailedResponse();
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
	/*	if (!$client->validateCallbackWithGlobals()) {
            if (!headers_sent()) {
                http_response_code(400);
            }
            die("NOK");
        }
    */    
        $callbackResult = $client->readCallback(file_get_contents('php://input'));
        $this->order = new WC_Order($this->decodeOrderId($callbackResult->getMerchantTransactionId()));
        //new
        $callbackUuid = $callbackResult->getUuid();
        $merchantTransactionId = $callbackResult->getMerchantTransactionId();
        //end new
        if ($callbackResult->getResult() == \BankartPaymentGateway\Client\Callback\Result::RESULT_OK) {
            switch ($callbackResult->getTransactionType()) {
                case \BankartPaymentGateway\Client\Callback\Result::TYPE_DEBIT:
                    // check if callback data is coming from the last (=newest+relevant) tx attempt, otherwise ignore it
                    /*if ($this->order->get_meta('_orderTxId') == $callbackResult->getMerchantTransactionId()) {
						$this->order->payment_complete($callbackResult->getUuid());
                    }*/
                    //new
                    if ($this->order->get_meta('_orderTxId') == $callbackResult->getMerchantTransactionId()) {
                        if ($this->order->get_meta('_orderTxId') == $merchantTransactionId) {
                            // Update the UUID meta data
                            $this->order->update_meta_data('_orderTxId', $callbackUuid);
                            $this->order->save_meta_data();
                            
                            // Complete the payment
                            $this->order->payment_complete($callbackUuid);
                        }
                    }
                    //end new
                    break;
                case \BankartPaymentGateway\Client\Callback\Result::TYPE_PREAUTHORIZE:
                    // check if callback data is coming from the last (=newest+relevant) tx attempt, otherwise ignore it
                    if ($this->order->get_meta('_orderTxId') == $callbackResult->getMerchantTransactionId()) {
                        if ($this->order->get_meta('_orderTxId') == $merchantTransactionId) {
                            $this->order->update_meta_data('_orderTxId', $callbackUuid);
                            $this->order->save_meta_data();
                            $preauthorizeStatus = $this->get_option('preauthorizeSuccess');
                            $this->order->set_status($preauthorizeStatus, __('Payment authorized. Awaiting capture/void.', 'woocommerce-bankart-payment-gateway'));
                            $this->order->set_transaction_id($callbackResult->getUuid());
                            $this->order->save();
                        }
                    }
                    break;
                case \BankartPaymentGateway\Client\Callback\Result::TYPE_CAPTURE:
                    $this->order->payment_complete($callbackResult->getUuid());
                    break;
                case \BankartPaymentGateway\Client\Callback\Result::TYPE_VOID:
                    $this->order->set_status('cancelled', __('Payment voided', 'woocommerce-bankart-payment-gateway'));
                    $this->order->set_transaction_id($callbackResult->getUuid());
                    $this->order->save();
                    break;
            }
        } elseif ($callbackResult->getResult() == \BankartPaymentGateway\Client\Callback\Result::RESULT_ERROR ) {
            $error = $callbackResult->getFirstError();
            if ($error->getCode() != \BankartPaymentGateway\Client\Transaction\Error::TRANSACTION_EXPIRED) {
                $this->order->set_status('failed', __('Error', 'woocommerce-bankart-payment-gateway'));
                if(null !== $callbackResult->getUuid()) {
                    $this->order->set_transaction_id($callbackResult->getUuid());
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
                'default' => '1',
                'options' => [
                    '1' => __('Instalments disabled', 'woocommerce-bankart-payment-gateway'),
                    '12' => __('Up to 12 instalments', 'woocommerce-bankart-payment-gateway'),
                    '24' => __('Up to 24 instalments', 'woocommerce-bankart-payment-gateway'),
					'36' => __('Up to 36 instalments', 'woocommerce-bankart-payment-gateway'),
					'60' => __('Up to 60 instalments', 'woocommerce-bankart-payment-gateway'),
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


    //NEW END 
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
}
