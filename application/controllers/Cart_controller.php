<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Cart_controller extends Home_Core_Controller
{
    /*
     * Payment Types
     *
     * 1. sale: Product purchases
     * 2. promote: Promote purchases
     *
     */

    public function __construct()
    {
        parent::__construct();

        $this->session_cart_items = $this->cart_model->get_sess_cart_items();
        $this->cart_model->calculate_cart_total();
    }

    /**
     * Cart
     */
    public function cart()
    {
        $data['title'] = trans("shopping_cart");
        $data['description'] = trans("shopping_cart") . " - " . $this->app_name;
        $data['keywords'] = trans("shopping_cart") . "," . $this->app_name;

        $data['cart_items'] = $this->session_cart_items;
        $data['cart_total'] = $this->cart_model->get_sess_cart_total();
        $data['cart_has_physical_product'] = $this->cart_model->check_cart_has_physical_product();

        $this->load->view('partials/_header', $data);
        $this->load->view('cart/cart', $data);
        $this->load->view('partials/_footer');
    }

    /**
     * Add to Cart
     */
    public function add_to_cart()
    {
        $product_id = $this->input->post('product_id', true);
        $product = $this->product_model->get_active_product($product_id);
        if (!empty($product)) {
            if ($product->status != 1) {
                $this->session->set_flashdata('product_details_error', trans("msg_error_cart_unapproved_products"));
            } else {
                $this->cart_model->add_to_cart($product);
                redirect(generate_url("cart"));
            }
        }
        redirect($this->agent->referrer());
    }

    /**
     * Add to Cart qQuote
     */
    public function add_to_cart_quote()
    {
        $quote_request_id = $this->input->post('id', true);
        if (!empty($this->cart_model->add_to_cart_quote($quote_request_id))) {
            redirect(generate_url("cart"));
        }
        redirect($this->agent->referrer());
    }

    /**
     * Remove from Cart
     */
    public function remove_from_cart()
    {
        $cart_item_id = $this->input->post('cart_item_id', true);
        $this->cart_model->remove_from_cart($cart_item_id);
    }

    /**
     * Update Cart Product Quantity
     */
    public function update_cart_product_quantity()
    {
        $product_id = $this->input->post('product_id', true);
        $cart_item_id = $this->input->post('cart_item_id', true);
        $quantity = $this->input->post('quantity', true);
        $this->cart_model->update_cart_product_quantity($product_id, $cart_item_id, $quantity);
    }

    /**
     * Shipping
     */
    public function shipping()
    {
        $this->cart_model->validate_cart();
        $data['title'] = trans("shopping_cart");
        $data['description'] = trans("shopping_cart") . " - " . $this->app_name;
        $data['keywords'] = trans("shopping_cart") . "," . $this->app_name;
        $data['cart_items'] = $this->cart_model->get_sess_cart_items();
        $data['mds_payment_type'] = 'sale';

        if (empty($data['cart_items'])) {
            redirect(generate_url("cart"));
        }
        //check shipping status
        if ($this->form_settings->shipping != 1) {
            redirect(generate_url("cart"));
            exit();
        }
        //check guest checkout
        if (empty($this->auth_check) && $this->general_settings->guest_checkout != 1) {
            redirect(generate_url("cart"));
            exit();
        }

        //check physical products
        if ($this->cart_model->check_cart_has_physical_product() == false) {
            redirect(generate_url("cart"));
            exit();
        }
        $data['cart_total'] = $this->cart_model->get_sess_cart_total();
        if ($data['cart_total']->is_stock_available != 1) {
            redirect(generate_url("cart"));
            exit();
        }

        $data["shipping_address"] = $this->cart_model->get_sess_cart_shipping_address();

        $this->load->view('partials/_header', $data);
        $this->load->view('cart/shipping', $data);
        $this->load->view('partials/_footer');

    }

    /**
     * Shipping Post
     */
    public function shipping_post()
    {
        $this->cart_model->set_sess_cart_shipping_address();
        redirect(generate_url("cart", "payment_method") . "?payment_type=sale");
    }

    /**
     * Payment Method
     */
    public function payment_method()
    {
        $data['title'] = trans("shopping_cart");
        $data['description'] = trans("shopping_cart") . " - " . $this->app_name;
        $data['keywords'] = trans("shopping_cart") . "," . $this->app_name;

        $payment_type = input_get('payment_type');
        if ($payment_type != "membership" && $payment_type != "promote") {
            $payment_type = "sale";
        }

        if ($payment_type == "sale") {
            $this->cart_model->validate_cart();
            //sale payment
            $data['cart_items'] = $this->cart_model->get_sess_cart_items();
            $data['mds_payment_type'] = "sale";
            if ($data['cart_items'] == null) {
                redirect(generate_url("cart"));
            }
            //check auth for digital products
            if (!$this->auth_check && $this->cart_model->check_cart_has_digital_product() == true) {
                $this->session->set_flashdata('error', trans("msg_digital_product_register_error"));
                redirect(generate_url("register"));
                exit();
            }
            $data['cart_total'] = $this->cart_model->get_sess_cart_total();
            $user_id = null;
            if ($this->auth_check) {
                $user_id = $this->auth_user->id;
            }

            $data['cart_has_physical_product'] = $this->cart_model->check_cart_has_physical_product();
            $data['cart_has_digital_product'] = $this->cart_model->check_cart_has_digital_product();
            $this->cart_model->unset_sess_cart_payment_method();
        } elseif ($payment_type == 'membership') {
            //membership payment
            if ($this->general_settings->membership_plans_system != 1) {
                redirect(lang_base_url());
                exit();
            }
            $data['mds_payment_type'] = 'membership';
            $plan_id = $this->session->userdata('modesy_selected_membership_plan_id');
            if (empty($plan_id)) {
                redirect(lang_base_url());
                exit();
            }
            $data['plan'] = $this->membership_model->get_plan($plan_id);
            if (empty($data['plan'])) {
                redirect(lang_base_url());
                exit();
            }
        } elseif ($payment_type == 'promote') {
            //promote payment
            if ($this->general_settings->promoted_products != 1) {
                redirect(lang_base_url());
            }
            $data['mds_payment_type'] = 'promote';
            $data['promoted_plan'] = $this->session->userdata('modesy_selected_promoted_plan');
            if (empty($data['promoted_plan'])) {
                redirect(lang_base_url());
            }
        }

        $this->load->view('partials/_header', $data);
        $this->load->view('cart/payment_method', $data);
        $this->load->view('partials/_footer');
    }

    /**
     * Payment Method Post
     */
    public function payment_method_post()
    {
        $this->cart_model->set_sess_cart_payment_method();

        $mds_payment_type = $this->input->post('mds_payment_type', true);
        if ($mds_payment_type == "sale") {
            redirect(generate_url("cart", "payment"));
        } elseif ($mds_payment_type == 'membership') {
            $transaction_number = 'bank-' . generate_transaction_number();
            $this->session->set_userdata('mds_membership_bank_transaction_number', $transaction_number);
            redirect(generate_url("cart", "payment") . "?payment_type=membership");
        } elseif ($mds_payment_type == 'promote') {
            $transaction_number = 'bank-' . generate_transaction_number();
            $this->session->set_userdata('mds_promote_bank_transaction_number', $transaction_number);
            redirect(generate_url("cart", "payment") . "?payment_type=promote");
        }
        redirect(lang_base_url());
    }

    /**
     * Payment
     */
    public function payment()
    {
        $data['title'] = trans("shopping_cart");
        $data['description'] = trans("shopping_cart") . " - " . $this->app_name;
        $data['keywords'] = trans("shopping_cart") . "," . $this->app_name;
        $data['mds_payment_type'] = "sale";

        //check guest checkout
        if (empty($this->auth_check) && $this->general_settings->guest_checkout != 1) {
            redirect(generate_url("cart"));
            exit();
        }

        //check is set cart payment method
        $data['cart_payment_method'] = $this->cart_model->get_sess_cart_payment_method();
        if (empty($data['cart_payment_method'])) {
            redirect(generate_url("cart", "payment_method"));
        }

        $payment_type = input_get('payment_type');
        if ($payment_type != "membership" && $payment_type != "promote") {
            $payment_type = "sale";
        }

        if ($payment_type == "sale") {
            $this->cart_model->validate_cart();
            //sale payment
            $data['cart_items'] = $this->cart_model->get_sess_cart_items();
            if ($data['cart_items'] == null) {
                redirect(generate_url("cart"));
            }
            $data['cart_total'] = $this->cart_model->get_sess_cart_total();
            $data["shipping_address"] = $this->cart_model->get_sess_cart_shipping_address();
            $data['cart_has_physical_product'] = $this->cart_model->check_cart_has_physical_product();
            //total amount
            $data['total_amount'] = $data['cart_total']->total;
            $data['currency'] = $this->payment_settings->default_currency;
        } elseif ($payment_type == 'membership') {
            //membership payment
            if ($this->general_settings->membership_plans_system != 1) {
                redirect(lang_base_url());
                exit();
            }
            $data['mds_payment_type'] = 'membership';
            $plan_id = $this->session->userdata('modesy_selected_membership_plan_id');
            if (empty($plan_id)) {
                redirect(lang_base_url());
                exit();
            }
            $data['plan'] = $this->membership_model->get_plan($plan_id);
            if (empty($data['plan'])) {
                redirect(lang_base_url());
                exit();
            }
            //total amount
            $data['total_amount'] = $data['plan']->price;
            $data['currency'] = $this->payment_settings->default_currency;
            $data['transaction_number'] = $this->session->userdata('mds_membership_bank_transaction_number');
            $data['cart_total'] = null;
        } elseif ($payment_type == 'promote') {
            //promote payment
            if ($this->general_settings->promoted_products != 1) {
                redirect(lang_base_url());
            }
            $data['mds_payment_type'] = 'promote';
            $data['promoted_plan'] = $this->session->userdata('modesy_selected_promoted_plan');
            if (empty($data['promoted_plan'])) {
                redirect(lang_base_url());
            }
            //total amount
            $data['total_amount'] = $data['promoted_plan']->total_amount;
            $data['currency'] = $this->payment_settings->default_currency;
            $data['transaction_number'] = $this->session->userdata('mds_promote_bank_transaction_number');
            $data['cart_total'] = null;
        }

        //check pagseguro
        if ($data['cart_payment_method']->payment_option == 'pagseguro') {
            $this->load->library('pagseguro');
            $data['session_code'] = $this->pagseguro->get_session_code();
        }

        $this->load->view('partials/_header', $data);
        $this->load->view('cart/payment', $data);
        $this->load->view('partials/_footer');
    }

    /**
     * Payment with Paypal
     */
    public function paypal_payment_post()
    {
        $payment_id = $this->input->post('payment_id', true);
        $this->load->library('paypal');

        //validate the order
        if ($this->paypal->get_order($payment_id)) {
            $data_transaction = array(
                'payment_method' => "PayPal",
                'payment_id' => $payment_id,
                'currency' => $this->input->post('currency', true),
                'payment_amount' => $this->input->post('payment_amount', true),
                'payment_status' => $this->input->post('payment_status', true),
            );
            $mds_payment_type = $this->input->post('mds_payment_type', true);

            //add order
            $response = $this->execute_payment($data_transaction, $mds_payment_type, lang_base_url());
            if ($response->result == 1) {
                $this->session->set_flashdata('success', $response->message);
                echo json_encode([
                    'result' => 1,
                    'redirect_url' => $response->redirect_url
                ]);
            } else {
                $this->session->set_flashdata('error', $response->message);
                echo json_encode([
                    'result' => 0
                ]);
            }
        } else {
            $this->session->set_flashdata('error', trans("msg_error"));
            echo json_encode([
                'result' => 0
            ]);
        }
    }

    /**
     * Payment with Stripe
     */
    public function stripe_payment_post()
    {
        require_once APPPATH . "third_party/stripe/vendor/autoload.php";
        \Stripe\Stripe::setApiKey($this->payment_settings->stripe_secret_key);
        header('Content-Type: application/json');

        $stripe_session = $this->session->userdata('mds_stripe_cart');
        if (empty($stripe_session)) {
            $this->session->set_flashdata('error', trans("invalid_attempt"));
            echo json_encode([
                'result' => 0
            ]);
            exit();
        }

        $json_str = file_get_contents('php://input');
        $json_obj = json_decode($json_str);
        $intent = null;
        try {
            if (isset($json_obj->payment_method_id)) {
                //create the PaymentIntent
                $intent = \Stripe\PaymentIntent::create([
                    'payment_method' => $json_obj->payment_method_id,
                    'amount' => $stripe_session->total_amount,
                    'currency' => $stripe_session->currency,
                    'confirmation_method' => 'manual',
                    'confirm' => true,
                ]);
            }
            if (isset($json_obj->payment_intent_id)) {
                $intent = \Stripe\PaymentIntent::retrieve(
                    $json_obj->payment_intent_id
                );
                $intent->confirm();
            }
            //complete payment
            if ($intent->status == 'requires_action' && $intent->next_action->type == 'use_stripe_sdk') {
                echo json_encode([
                    'requires_action' => true,
                    'payment_intent_client_secret' => $intent->client_secret
                ]);
            } else if ($intent->status == 'succeeded') {
                //add to database
                $data_transaction = array(
                    'payment_method' => "Stripe",
                    'payment_id' => $intent->id,
                    'currency' => $stripe_session->currency,
                    'payment_amount' => get_price($stripe_session->total_amount, 'decimal'),
                    'payment_status' => "Succeeded",
                );

                //add order
                $response = $this->execute_payment($data_transaction, $stripe_session->payment_type, lang_base_url());
                if ($response->result == 1) {
                    @$this->session->unset_userdata('mds_stripe_cart');
                    $this->session->set_flashdata('success', $response->message);
                    echo json_encode([
                        'result' => 1,
                        'redirect_url' => $response->redirect_url
                    ]);
                } else {
                    $this->session->set_flashdata('error', $response->message);
                    echo json_encode([
                        'result' => 0
                    ]);
                }
            } else {
                //invalid status
                http_response_code(500);
                $this->session->set_flashdata('error', 'Invalid PaymentIntent status');
                echo json_encode([
                    'result' => 0
                ]);
            }
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $this->session->set_flashdata('error', $e->getMessage());
            echo json_encode([
                'result' => 0
            ]);
        }
    }

    /**
     * Payment with PayStack
     */
    public function paystack_payment_post()
    {
        $this->load->library('paystack');

        $data_transaction = array(
            'payment_method' => "PayStack",
            'payment_id' => $this->input->post('payment_id', true),
            'currency' => $this->input->post('currency', true),
            'payment_amount' => get_price($this->input->post('payment_amount', true), 'decimal'),
            'payment_status' => $this->input->post('payment_status', true),
        );

        if (empty($this->paystack->verify_transaction($data_transaction['payment_id']))) {
            $this->session->set_flashdata('error', 'Invalid transaction code!');
            echo json_encode([
                'result' => 0
            ]);
        } else {
            $mds_payment_type = $this->input->post('mds_payment_type', true);

            //add order
            $response = $this->execute_payment($data_transaction, $mds_payment_type, lang_base_url());
            if ($response->result == 1) {
                $this->session->set_flashdata('success', $response->message);
                echo json_encode([
                    'result' => 1,
                    'redirect_url' => $response->redirect_url
                ]);
            } else {
                $this->session->set_flashdata('error', $response->message);
                echo json_encode([
                    'result' => 0
                ]);
            }
        }
    }

    /**
     * Payment with Razorpay
     */
    public function razorpay_payment_post()
    {
        $this->load->library('razorpay');

        $data_transaction = array(
            'payment_method' => "Razorpay",
            'payment_id' => $this->input->post('payment_id', true),
            'razorpay_order_id' => $this->input->post('razorpay_order_id', true),
            'razorpay_signature' => $this->input->post('razorpay_signature', true),
            'currency' => $this->input->post('currency', true),
            'payment_amount' => get_price($this->input->post('payment_amount', true), 'decimal'),
            'payment_status' => 'succeeded',
        );

        if (empty($this->razorpay->verify_payment_signature($data_transaction))) {
            $this->session->set_flashdata('error', 'Invalid signature passed!');
            echo json_encode([
                'result' => 0
            ]);
        } else {
            $mds_payment_type = $this->input->post('mds_payment_type', true);
            //add order
            $response = $this->execute_payment($data_transaction, $mds_payment_type, lang_base_url());
            if ($response->result == 1) {
                $this->session->set_flashdata('success', $response->message);
                echo json_encode([
                    'result' => 1,
                    'redirect_url' => $response->redirect_url
                ]);
            } else {
                $this->session->set_flashdata('error', $response->message);
                echo json_encode([
                    'result' => 0
                ]);
            }
        }
    }

    /**
     * Payment with Iyzico
     */
    public function iyzico_payment_post()
    {
        require_once(APPPATH . 'third_party/iyzipay/vendor/autoload.php');
        require_once(APPPATH . 'third_party/iyzipay/vendor/iyzico/iyzipay-php/IyzipayBootstrap.php');

        $token = $this->input->post('token', true);
        $conversation_id = $this->input->get('conversation_id', true);
        $lang = $this->input->get('lang', true);
        $payment_type = $this->input->get('payment_type', true);

        $lang_base_url = lang_base_url();
        if ($lang != $this->selected_lang->short_form) {
            $lang_base_url = base_url() . $lang . "/";
        }

        IyzipayBootstrap::init();
        $options = new \Iyzipay\Options();
        $options->setApiKey($this->payment_settings->iyzico_api_key);
        $options->setSecretKey($this->payment_settings->iyzico_secret_key);
        if ($this->payment_settings->iyzico_mode == "sandbox") {
            $options->setBaseUrl("https://sandbox-api.iyzipay.com");
        } else {
            $options->setBaseUrl("https://api.iyzipay.com");
        }

        $request = new \Iyzipay\Request\RetrieveCheckoutFormRequest();
        $request->setLocale(\Iyzipay\Model\Locale::TR);
        $request->setConversationId($conversation_id);
        $request->setToken($token);

        $checkoutForm = \Iyzipay\Model\CheckoutForm::retrieve($request, $options);

        if ($checkoutForm->getPaymentStatus() == "SUCCESS") {
            $data_transaction = array(
                'payment_method' => "Iyzico",
                'payment_id' => $checkoutForm->getPaymentId(),
                'currency' => $checkoutForm->getCurrency(),
                'payment_amount' => $checkoutForm->getPrice(),
                'payment_status' => "Succeeded"
            );
            //add order
            $response = $this->execute_payment($data_transaction, $payment_type, $lang_base_url);
            if ($response->result == 1) {
                $this->session->set_flashdata('success', $response->message);
                redirect($response->redirect_url);
            } else {
                $this->session->set_flashdata('error', $response->message);
                redirect($response->redirect_url);
            }
        } else {
            $this->session->set_flashdata('error', trans("msg_error"));
            redirect($lang_base_url . get_route("cart", true) . get_route("payment"));
        }
    }

    /**
     * Payment with PagSeguro
     */
    public function pagseguro_payment_post()
    {
        $this->load->library('pagseguro');
        $inputs = array(
            'payment_type' => $this->input->post('payment_type', true),
            'token' => htmlspecialchars($this->input->post('token', true)),
            'senderHash' => htmlspecialchars($this->input->post('senderHash', true)),
            'cardNumber' => $this->input->post('cardNumber', true),
            'cardExpiry' => $this->input->post('cardExpiry', true),
            'cardCVC' => $this->input->post('cardCVC', true),
            'total_amount' => $this->input->post('total_amount', true),
            'full_name' => $this->input->post('full_name', true),
            'cpf' => $this->input->post('cpf', true),
            'phone' => $this->input->post('phone', true),
            'email' => $this->input->post('email', true),
            'date_of_birth' => $this->input->post('date_of_birth', true),
            'postal_code' => $this->input->post('postal_code', true),
            'city' => $this->input->post('city', true),
        );

        $result = null;
        $payment_method = 'PagSeguro - Credit Card';
        if ($this->input->post('payment_type', true) == 'credit_card') {
            $result = $this->pagseguro->pay_with_credit_card($inputs);
            if (empty($result)) {
                $this->session->set_flashdata('form_data', $inputs);
                redirect($this->agent->referrer());
            }
        } else {
            $payment_method = 'PagSeguro - Boleto';
            $result = $this->pagseguro->pay_with_boleto($inputs);
            if (empty($result)) {
                $this->session->set_flashdata('form_data', $inputs);
                redirect($this->agent->referrer());
            }
        }

        if (!empty($result->code)) {
            $data_transaction = array(
                'payment_method' => $payment_method,
                'payment_id' => $result->code,
                'currency' => 'BRL',
                'payment_amount' => $inputs['total_amount'],
                'payment_status' => "succeeded",
            );
            $mds_payment_type = $this->input->post('mds_payment_type', true);
            //add order
            $response = $this->execute_payment($data_transaction, $mds_payment_type, lang_base_url());
            if ($response->result == 1) {
                $this->session->set_flashdata('success', $response->message);
                redirect($response->redirect_url);
            } else {
                $this->session->set_flashdata('error', $response->message);
                redirect($response->redirect_url);
            }
        }
    }

    /**
     * Payment with Bank Transfer
     */
    public function bank_transfer_payment_post()
    {
        $mds_payment_type = $this->input->post('mds_payment_type', true);

        if ($mds_payment_type == 'membership') {
            $plan_id = $this->session->userdata('modesy_selected_membership_plan_id');
            $plan = null;
            if (!empty($plan_id)) {
                $plan = $this->membership_model->get_plan($plan_id);
            }
            if (!empty($plan)) {
                $data_transaction = array(
                    'payment_method' => 'Bank Transfer',
                    'payment_status' => 'awaiting_payment',
                    'payment_id' => $this->session->userdata('mds_membership_bank_transaction_number')
                );
                //add user membership plan
                $this->membership_model->add_user_plan($data_transaction, $plan, $this->auth_user->id);
                //add transaction
                $this->membership_model->add_membership_transaction_bank($data_transaction, $plan);
                redirect(generate_url("membership_payment_completed") . "?method=bank_transfer&transaction_number=" . $data_transaction['payment_id']);
            }
            $this->session->set_flashdata('error', trans("msg_error"));
            redirect(generate_url("cart", "payment") . "?payment_type=membership");
        } elseif ($mds_payment_type == 'promote') {
            $promoted_plan = $this->session->userdata('modesy_selected_promoted_plan');
            if (!empty($promoted_plan)) {
                $transaction_number = $this->session->userdata('mds_promote_bank_transaction_number');
                //add transaction
                $this->promote_model->add_promote_transaction_bank($promoted_plan, $transaction_number);

                $type = $this->session->userdata('mds_promote_product_type');

                if (empty($type)) {
                    $type = "new";
                }
                redirect(generate_url("promote_payment_completed") . "?method=bank_transfer&transaction_number=" . $transaction_number . "&product_id=" . $promoted_plan->product_id);
            }
            $this->session->set_flashdata('error', trans("msg_error"));
            redirect(generate_url("cart", "payment") . "?payment_type=promote");
        } else {
            //add order
            $order_id = $this->order_model->add_order_offline_payment("Bank Transfer");
            $order = $this->order_model->get_order($order_id);
            if (!empty($order)) {
                //decrease product quantity after sale
                $this->order_model->decrease_product_stock_after_sale($order->id);
                //send email
                if ($this->general_settings->send_email_buyer_purchase == 1) {
                    $email_data = array(
                        'email_type' => 'new_order',
                        'order_id' => $order_id
                    );
                    $this->session->set_userdata('mds_send_email_data', json_encode($email_data));
                }

                if ($order->buyer_id == 0) {
                    $this->session->set_userdata('mds_show_order_completed_page', 1);
                    redirect(generate_url("order_completed") . "/" . $order->order_number);
                } else {
                    $this->session->set_flashdata('success', trans("msg_order_completed"));
                    redirect(generate_url("order_details") . "/" . $order->order_number);
                }
            }

            $this->session->set_flashdata('error', trans("msg_error"));
            redirect(generate_url("cart", "payment"));
        }
    }

    /**
     * Cash on Delivery
     */
    public function cash_on_delivery_payment_post()
    {
        //add order
        $order_id = $this->order_model->add_order_offline_payment("Cash On Delivery");
        $order = $this->order_model->get_order($order_id);
        if (!empty($order)) {
            //decrease product quantity after sale
            $this->order_model->decrease_product_stock_after_sale($order->id);
            //send email
            if ($this->general_settings->send_email_buyer_purchase == 1) {
                $email_data = array(
                    'email_type' => 'new_order',
                    'order_id' => $order_id
                );
                $this->session->set_userdata('mds_send_email_data', json_encode($email_data));
            }

            if ($order->buyer_id == 0) {
                $this->session->set_userdata('mds_show_order_completed_page', 1);
                redirect(generate_url("order_completed") . "/" . $order->order_number);
            } else {
                $this->session->set_flashdata('success', trans("msg_order_completed"));
                redirect(generate_url("order_details") . "/" . $order->order_number);
            }
        }

        $this->session->set_flashdata('error', trans("msg_error"));
        redirect(generate_url("cart", "payment"));
    }

    /**
     * Execute Sale Payment
     */
    public function execute_payment($data_transaction, $payment_type, $base_url)
    {
        //response object
        $response = new stdClass();
        $response->result = 0;
        $response->message = "";
        $response->redirect_url = "";
        $data_transaction["payment_status"] = "payment_received";
        if ($payment_type == 'sale') {
            //add order
            $order_id = $this->order_model->add_order($data_transaction);
            $order = $this->order_model->get_order($order_id);
            if (!empty($order)) {
                //decrease product quantity after sale
                $this->order_model->decrease_product_stock_after_sale($order->id);
                //send email
                if ($this->general_settings->send_email_buyer_purchase == 1) {
                    $email_data = array(
                        'email_type' => 'new_order',
                        'order_id' => $order_id
                    );
                    $this->session->set_userdata('mds_send_email_data', json_encode($email_data));
                }
                //set response and redirect URLs
                $response->result = 1;
                $response->redirect_url = $base_url . get_route("order_details", true) . $order->order_number;
                if ($order->buyer_id == 0) {
                    $this->session->set_userdata('mds_show_order_completed_page', 1);
                    $response->redirect_url = $base_url . get_route("order_completed", true) . $order->order_number;
                } else {
                    $response->message = trans("msg_order_completed");
                }
            } else {
                //could not added to the database
                $response->message = trans("msg_payment_database_error");
                $response->result = 0;
                $response->redirect_url = $base_url . get_route("cart", true) . get_route("payment");
            }
        } elseif ($payment_type == 'membership') {
            $plan_id = $this->session->userdata('modesy_selected_membership_plan_id');
            $plan = null;
            if (!empty($plan_id)) {
                $plan = $this->membership_model->get_plan($plan_id);
            }
            if (!empty($plan)) {
                //add user membership plan
                $this->membership_model->add_user_plan($data_transaction, $plan, $this->auth_user->id);
                //add transaction
                $this->membership_model->add_membership_transaction($data_transaction, $plan);
                //set response and redirect URLs
                $response->result = 1;
                $response->redirect_url = $base_url . get_route("membership_payment_completed") . "?method=gtw";
            } else {
                //could not added to the database
                $response->message = trans("msg_payment_database_error");
                $response->result = 0;
                $response->redirect_url = $base_url . get_route("cart", true) . get_route("payment") . "?payment_type=membership";
            }
        } elseif ($payment_type == 'promote') {
            $promoted_plan = $this->session->userdata('modesy_selected_promoted_plan');
            if (!empty($promoted_plan)) {
                //add to promoted products
                $this->promote_model->add_to_promoted_products($promoted_plan);
                //add transaction
                $this->promote_model->add_promote_transaction($data_transaction);
                //reset cache
                reset_cache_data_on_change();
                reset_user_cache_data($this->auth_user->id);
                //set response and redirect URLs
                $response->result = 1;
                $response->redirect_url = $base_url . get_route("promote_payment_completed") . "?method=gtw&product_id=" . $promoted_plan->product_id;
            } else {
                //could not added to the database
                $response->message = trans("msg_payment_database_error");
                $response->result = 0;
                $response->redirect_url = $base_url . get_route("cart", true) . get_route("payment") . "?payment_type=promote";
            }
        }
        return $response;
    }

    /**
     * Order Completed
     */
    public function order_completed($order_number)
    {
        $data['title'] = trans("msg_order_completed");
        $data['description'] = trans("msg_order_completed") . " - " . $this->app_name;
        $data['keywords'] = trans("msg_order_completed") . "," . $this->app_name;

        $data['order'] = $this->order_model->get_order_by_order_number($order_number);

        if (empty($data['order'])) {
            redirect(lang_base_url());
        }

        if (empty($this->session->userdata('mds_show_order_completed_page'))) {
            redirect(lang_base_url());
        }

        $this->load->view('partials/_header', $data);
        $this->load->view('cart/order_completed', $data);
        $this->load->view('partials/_footer');
    }

    /**
     * Membership Payment Completed
     */
    public function membership_payment_completed()
    {
        $data['title'] = trans("msg_payment_completed");
        $data['description'] = trans("msg_payment_completed") . " - " . $this->app_name;
        $data['keywords'] = trans("payment") . "," . $this->app_name;
        $transaction_insert_id = $this->session->userdata('mds_membership_transaction_insert_id');
        if (empty($transaction_insert_id)) {
            redirect(lang_base_url());
        }
        $data["transaction"] = $this->membership_model->get_membership_transaction($transaction_insert_id);
        if (empty($data["transaction"])) {
            redirect(lang_base_url());
            exit();
        }

        $data["method"] = $this->input->get('method');
        $data["transaction_number"] = $this->input->get('transaction_number');


        $this->load->view('partials/_header', $data);
        $this->load->view('cart/membership_payment_completed', $data);
        $this->load->view('partials/_footer');
    }

    /**
     * Promote Payment Completed
     */
    public function promote_payment_completed()
    {
        $data['title'] = trans("msg_payment_completed");
        $data['description'] = trans("msg_payment_completed") . " - " . $this->app_name;
        $data['keywords'] = trans("payment") . "," . $this->app_name;
        $transaction_insert_id = $this->session->userdata('mds_promoted_transaction_insert_id');
        if (empty($transaction_insert_id)) {
            redirect(lang_base_url());
        }
        $data["transaction"] = $this->promote_model->get_promotion_transaction($transaction_insert_id);
        if (empty($data["transaction"])) {
            redirect(lang_base_url());
            exit();
        }
        $data["method"] = $this->input->get('method');
        $data["transaction_number"] = $this->input->get('transaction_number');

        $this->load->view('partials/_header', $data);
        $this->load->view('cart/promote_payment_completed', $data);
        $this->load->view('partials/_footer');
    }

}
