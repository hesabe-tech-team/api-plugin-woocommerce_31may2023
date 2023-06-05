<?php 

class HesabePayment extends WC_Payment_Gateway {
        public function __construct() {
            $this->id = 'HesabePayment';
            $this->icon = '';
			$this->method_title = __('Hesabe Payment Gateway', 'Hesabe-Payment-Gateway');
            $this->method_description = __('Version 3.0 | By HesabeTeam', 'Hesabe-Payment-Gateway');
            $this->has_fields = true;
            $this->supports = array(
                'products',
                'refunds'
            );

            $this->init_form_fields();
            $this->init_settings();

            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->instructions = $this->get_option('instructions');
			$this->merchantCode = $this->settings['merchantCode'];
            $this->sandbox = $this->settings['sandbox'];
            $this->secretKey = $this->settings['secretKey'];
            $this->ivKey = $this->settings['ivKey'];
            $this->accessCode = $this->settings['accessCode'];
            $this->currencyConvert = (!empty($this->settings['currencyConvert']) && 'yes' === $this->settings['currencyConvert']) ? true : false;
	        $this->Hdirect = (!empty($this->settings['Hdirect']) && 'yes' === $this->settings['Hdirect']) ? true : false;
	        $this->KNET = (!empty($this->settings['KNET']) && 'yes' === $this->settings['KNET']) ? true : false;
            $this->MPGS = (!empty($this->settings['MPGS']) && 'yes' === $this->settings['MPGS']) ? true : false;
			if ($this->sandbox == 'yes') {
                $this->apiUrl = WC_HESABE_TEST_URL;
            } else {
                $this->apiUrl = WC_HESABE_LIVE_URL;
            }
			$this->notify_url = home_url('/wc-api/wc_hesabe');
		    $this->msg['message'] = "";
            $this->msg['class'] = "";
            if (!is_admin()) {
			// Save custom radio button field value as order meta data
            add_action( 'woocommerce_checkout_create_order', array( $this, 'save_custom_radio_button_field' ) );
			// Display custom radio button field value in order details in the admin panel
            add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'display_custom_radio_button_field_admin_order_meta' ), 10, 1 );
            }
			add_action('woocommerce_api_wc_hesabe', array($this, 'check_hesabe_response'));
			add_action('valid-hesabe-request', array($this, 'successful_request'));
			$woocommerce_version = WC()->version;
			if (version_compare($woocommerce_version, '2.0.0', '>=')) 
			{
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            } 
			else 
			{
                add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
            }
			add_action('woocommerce_receipt_HesabePayment', array($this, 'receipt_page'));
        }

        public function init_form_fields() {
            $this->form_fields = array(
                
				'enabled' => array('title' => __('Enable/Disable', 'Hesabe-Payment-Gateway'),'type' => 'checkbox','label' => __('Enable Hesabe Online Payment Module.', 'Hesabe-Payment-Gateway'),'default' => 'no'),
		    				
		        'Hdirect' => array('title' => __('Enable/Disable', 'Hesabe-Payment-Gateway'),'type' => 'checkbox','label' => __('Enable Hesabe Hdirect.', 'Hesabe-Payment-Gateway'),'default' => 'no'),
                
				'KNET' => array('title' => __('Enable/Disable', 'Hesabe-Payment-Gateway'),'type' => 'checkbox','label' => __('Enable KNET', 'Hesabe-Payment-Gateway'),'default' => 'no'),
		        
		    	'MPGS' => array('title' => __('Enable/Disable', 'Hesabe-Payment-Gateway'),'type' => 'checkbox','label' => __('Enable MPGS', 'Hesabe-Payment-Gateway'),'default' => 'no'),
		    	
                'sandbox' => array('title' => __('Enable Demo?', 'Hesabe-Payment-Gateway'),'type' => 'checkbox','label' => __('Enable Demo Hesabe OnlinePayment.', 'Hesabe-Payment-Gateway'),'default' => 'no'),
		    
                'currencyConvert' => array('title' => __('Enable Currency Converter?', 'Hesabe-Payment-Gateway'),'type' => 'checkbox','label' => __('Enable Hesabe Online Payment Currency Converter', 'Hesabe-Payment-Gateway'),'default' => 'no'),
		    
                'title' => array('title' => __('Title:', 'Hesabe-Payment-Gateway'),'type' => 'text','description' => __('This controls the title which the user sees during checkout.', 'Hesabe-Payment-Gateway'),'default' => __('Hesabe Payments', 'Hesabe-Payment-Gateway')),
		    
                'description' => array('title' => __('Description:', 'Hesabe-Payment-Gateway'),'type' => 'textarea','description' => __('This controls the description which the user sees during checkout.', 'Hesabe-Payment-Gateway'),'default' => __('Best payment gateway provider in Kuwait for e-payment through credit card & debit card', 'Hesabe-Payment-Gateway')),
		    
                'merchantCode' => array('title' => __('Merchant Code:', 'Hesabe-Payment-Gateway'),'type' => 'text','description' => __('This is Merchant Code.', 'Hesabe-Payment-Gateway')),
		    
                'accessCode' => array('title' => __('Access Code:', 'Hesabe-Payment-Gateway'),'type' => 'text','description' => __('Access Code', 'Hesabe-Payment-Gateway')),
				
                'secretKey' => array('title' => __('Secret Key:', 'Hesabe-Payment-Gateway'),'type' => 'text','description' => __('Secret Key', 'Hesabe-Payment-Gateway')),
		    
                'ivKey' => array('title' => __('IV:', 'Hesabe-Payment-Gateway'),'type' => 'text','description' => __('IV of Secret Key', 'Hesabe-Payment-Gateway'))
            );
        }

        public function admin_options()
        {
	    	
            echo '<h3>' . __('Hesabe Payment Gateway') . '</h3>';
            echo '<p>' . __('Kuwait online payment solutions for all your transactions by Hesabe') . '</p>';
            echo '<table class="form-table">';
            $this->generate_settings_html();
            echo '</table>';
	    
        }
		
		public function payment_fields() {
			 echo '<fieldset>';
			if($this->sandbox== 'yes')
			{
            echo '<legend>Demo Mode</legend>';
			}
			else
			{
            echo '<legend>Select Payment</legend>';
            }
            echo '<div id="custom_radio_button_field">';
			if($this->Hdirect == 'yes')
		    {
				if($this->KNET == false AND $this->MPGS == false )
			    {
					echo '<p>Gateway Activation Required</p>';
				}
				else
				{
				    if($this->KNET == 'yes')
			        {
                        echo '<p><input type="radio" name="custom_radio_button_field" value="HD_KNET">Hesabe Knet</p>';
				    }
				    if($this->MPGS == 'yes' )
				    {
                        echo '<p><input type="radio" name="custom_radio_button_field" value="HD_MPGS">Hesabe MPGS</p>';
				    }
				}
			}
			else
			{
              echo '<p><input type="radio" name="custom_radio_button_field" value="HD_default" checked="checked"> Hesabe Payment</p>';
			}
            echo '</div>';
            echo '</fieldset>';  	
		}
		
		// Save custom radio button field value as order meta data
        public function save_custom_radio_button_field( $order ) {
            if ( isset( $_POST['custom_radio_button_field'] ) ) {
                $order->update_meta_data( 'custom_radio_button_field', sanitize_text_field( $_POST['custom_radio_button_field'] ) );
            }
        }
		
        public function process_payment($orderid) {
            $woocommerce_version = WC()->version;
			if (version_compare($woocommerce_version, '2.0.0', '>=')) {
            $order = new WC_Order($orderid);
            } 
			else {
                $order = new woocommerce_order($orderid);	
            }
			
            return array('result' => 'success', 'redirect' => add_query_arg('order',
                $order->id, add_query_arg('key', $order->order_key, $order->get_checkout_payment_url(true)))
            ); 
        }
		
	    function check_hesabe_response()
        {
	    	
            global $woocommerce;
            $msg['class'] = 'error';
            $msg['message'] = "This transaction has been declined. Please attempt your purchase again. d";
            $responseData = $_REQUEST['data'];
            $decryptedResponse = WC_Hesabe_Crypt::decrypt($responseData, $this->secretKey, $this->ivKey);
            $jsonDecode = json_decode($decryptedResponse);
            if (isset($jsonDecode->response)) {
                $orderInfo = $jsonDecode->response;
                $orderId = $orderInfo->variable1;
                if ($orderId != '') {
                    try {
	    				 $woocommerce_version = WC()->version;
                        if (version_compare($woocommerce_version, '2.0.0', '>=')) {
                            $order = new WC_Order($orderId);
                        } else {
                            $order = new woocommerce_order($orderId);
                        }
                        $orderStatus = $orderInfo->resultCode;
                        $order->add_order_note("Status: " . $orderStatus . " Amount: " . $orderInfo->amount);
                        if ($jsonDecode->status == true && ($orderStatus == "CAPTURED" || $orderStatus == "ACCEPT" || $orderStatus == "AUTHORIZED" || $orderStatus == "PARTIALLY_CAPTURED")) {
                            $msg['message'] = "Thank you for shopping with us. Your account has been charged and your transaction is successful. ";
                            $msg['class'] = 'success';
                            if ($order->status != 'processing') {
                                $order->payment_complete();
                                $order->add_order_note('Hesabe payment successful<br/> Payment Ref Number: ' . $orderInfo->paymentId . ' Payment Token :' . $orderInfo->paymentToken . ' PaidOn :' . $orderInfo->paidOn . ' Amount : ' . $orderInfo->amount);
								
								if($orderInfo->method == 1 )
								{
								    $order->add_order_note("Payment Method: KNET (" . $orderInfo->method .")" );
								}
								elseif($orderInfo->method == 2 )
								{
								    $order->add_order_note("Payment Method: MPGS (" . $orderInfo->method .")" );
								}
								elseif($orderInfo->method == 5 )
								{
								    $order->add_order_note("Payment Method: Cybersource (" . $orderInfo->method .")" );
								}
								elseif($orderInfo->method == 7 )
								{
								    $order->add_order_note("Payment Method: Cybersource Amex (" . $orderInfo->method .")" );
								}
								elseif($orderInfo->method == 9 )
								{
								    $order->add_order_note("Payment Method: Apple Pay (" . $orderInfo->method .")" );
								}
								else
								{
									$order->add_order_note("Payment Method: " . $orderInfo->method);
								}
                                $woocommerce->cart->empty_cart();
                            }
                        }
                        else {
                            $order->update_status('failed');
                            $order->add_order_note('Hesabe payment<br/>Payment Ref Number: ' . $orderInfo->paymentId . ' Payment Token : ' . $orderInfo->paymentToken . ' PaidOn :' . $orderInfo->paidOn . ' Amount : ' . $orderInfo->Amount);
                            $order->add_order_note($msg['message']);
                        }
                    } catch (Exception $e) {
                        $msg['class'] = 'error';
                        $msg['message'] = "Thank you for shopping with us. However, the transaction has been declined.";
                    }
                }
            }
	    
            if (function_exists('wc_add_notice')) {
                wc_add_notice($msg['message'], $msg['class']);
            } else {
                if ($msg['class'] == 'success') {
                    $woocommerce->add_message($msg['message']);
                } else {
                    $woocommerce->add_error($msg['message']);
                }
                $woocommerce->set_messages();
            }
	    
            if (!isset($order)) {
                $redirect_url = home_url('/checkout');
            } else {
                $redirect_url = $this->get_return_url($order);
            }
            wp_redirect($redirect_url);
            exit;
        }

        public function receipt_page($order) {
			 echo $this->generate_hesabe_form($order);
        }

        public function generate_hesabe_form($order_id)
        {
	    	$woocommerce_version = WC()->version;
            if (version_compare($woocommerce_version, '2.0.0', '>=')) {
                $order = new WC_Order($order_id);
	    		
            } else {
                $order = new woocommerce_order($order_id);
            }
            $order_data = $order->get_data();
            $order_version = $order_data['version']??0;
            $order_billing_first_name = $order_data['billing']['first_name']??"";
            $order_billing_last_name = $order_data['billing']['last_name']??"";
            $order_billing_phone = trim($order_data['billing']['phone'],"+")??"";
            $order_billing_email = $order_data['billing']['email']??"";
            $orderAmount = number_format((float)$order->order_total, 3, '.', '');
	    	$metadata = $order->meta_data;
	    	if($order_data['meta_data'][0]->{'key'} == 'custom_radio_button_field' AND $order_data['meta_data'][0]->value == 'HD_KNET')
	    	{
	    		$Paymenttype=1;
	    	}
	    	elseif($order_data['meta_data'][1]->{'key'} == 'custom_radio_button_field' AND $order_data['meta_data'][1]->value == 'HD_KNET')
	    	{
	    		$Paymenttype=1;
	    	}
	    	elseif($order_data['meta_data'][0]->{'key'} == 'custom_radio_button_field' AND $order_data['meta_data'][0]->value == 'HD_MPGS')
	    	{ 
	    		$Paymenttype=2;
	    	}
	    	elseif($order_data['meta_data'][1]->{'key'} == 'custom_radio_button_field' AND $order_data['meta_data'][1]->value == 'HD_MPGS')
	    	{
	    		$Paymenttype=2;
	    	}
	    	elseif($order_data['meta_data'][0]->{'key'} == 'custom_radio_button_field' AND $order_data['meta_data'][0]->value == 'HD_default')
	    	{ 
	    		$Paymenttype=0;
	    	}
	    	elseif($order_data['meta_data'][1]->{'key'} == 'custom_radio_button_field' AND $order_data['meta_data'][1]->value == 'HD_default')
	    	{
	    		$Paymenttype=0;
	    	}
	    	else
	    	{
	    		echo "Error";
	    		exit;
	    	}
	    
            $post_values = array(
                "merchantCode" => $this->merchantCode,
                "amount" => $orderAmount,
                "responseUrl" => $this->notify_url,
                "failureUrl" => $this->notify_url,
                "paymentType" => $Paymenttype,
                "orderReferenceNumber" =>$order_id, 
                "variable1" => $order_id,
                "variable2" => $order_version,
                "variable3" => $order_billing_first_name." ".$order_billing_last_name,
                "variable4" => $order_billing_phone,
                "variable5" => $order_billing_email,
                "version" => '2.0',
                "name" => $order_billing_first_name." ".$order_billing_last_name,
                "mobile_number" => $order_billing_phone
            );
            $pattern = "(^[a-zA-Z0-9_.]+[@]{1}[a-z0-9]+[\.][a-z]+$)";
            if (preg_match($pattern, $order_data['billing']['email'])) {
                $post_values['email'] = $order_billing_email;
            }
            if ($this->currencyConvert && $order->get_currency() !== 'KWD') {
                $post_values['currency'] = $order->get_currency();
            }
	    	else
	    	{
	    		$post_values['currency'] = $order->get_currency();
	    	}
            $post_string = json_encode($post_values);
            $encrypted_post_string = WC_Hesabe_Crypt::encrypt($post_string, $this->secretKey, $this->ivKey);
            $header = array();
            $header[] = 'accessCode: ' . $this->accessCode;
            $checkOutUrl = $this->apiUrl . '/checkout'; 
	    	$curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $checkOutUrl,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_SSL_VERIFYPEER => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => array('data' => $encrypted_post_string),
                    CURLOPT_HTTPHEADER => $header,
                ));
                $response = curl_exec($curl);
            curl_close($curl); 
	    	$hresponse=json_decode($response);
	    	if($hresponse->status==false AND $hresponse->code==501 AND $hresponse->message=="Authentication failed." || $hresponse->message=="Authentication failed" )
	    	{
	    		$responseMessage = "Error Code: " . $hresponse->code;
                $order->add_order_note('<br/> ' . $responseMessage);
                echo $responseMessage;
	    		echo "</br><strong>Opps! ".$hresponse->message."
	    		</br>
	    		Before going live make sure Demo mode is disbled</strong>";
	    		echo "</br>";
	    	}
	    	else
	    	{
                $decrypted_post_response = WC_Hesabe_Crypt::decrypt($response, $this->secretKey, $this->ivKey);
                $decode_response = json_decode($decrypted_post_response);
                $paymentData = $decode_response->response->data;
                header('Location:' . $this->apiUrl . '/payment?data=' . $paymentData);
	    	}
        } 		

    }

?>