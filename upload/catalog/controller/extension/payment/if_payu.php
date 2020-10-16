<?php

class ControllerExtensionPaymentIfPayu extends Controller
{
    public function index()
    {
        $this->load->language('extension/payment/if_payu');

        $data['payment_method'] = $this->config->get('payment_if_payu_payment_method');

        switch ($data['payment_method']) {

            case 'CREDIT_CARD':

                $data['months'] = array();

                for ($i = 1; $i <= 12; $i++) {

                    $data['months'][] = array(
                        'text'  => sprintf('%02d', $i),
                        'value' => sprintf('%02d', $i)
                    );

                }

                $today = getdate();

                $data['year_expire'] = array();

                for ($i = $today['year']; $i < $today['year'] + 11; $i++) {

                    $data['year_expire'][] = array(
                        'text'  => $i,
                        'value' => $i
                    );

                }

                break;

            case 'BKM':

                // settings not required

                break;

            default:

                return '<div class="text-center">Error: payment method not defined: ' . $this->config->get('payment_if_payu_payment_method') . '</div>';

        }

        return $this->load->view('extension/payment/if_payu', $data);
    }

    public function send()
    {
        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $order_products = $this->model_checkout_order->getOrderProducts($this->session->data['order_id']);

        $paymentMethod = $this->config->get('payment_if_payu_payment_method');

        $items = [];

        foreach ($order_products as $order_product) {

            $items[] = [
                'code'     => $order_product['product_id'],
                'name'     => $order_product['name'],
                'price'    => $order_product['price'],
                'quantity' => $order_product['quantity']
            ];

        }

        $moduleData = [

            'order_id'        => (int)$order_info['order_id'],
            'secret_key'      => urlencode($this->config->get('payment_if_payu_secret_key')),
            'currency_code'   => $order_info['currency_code'],
            'client_ip'       => empty($order_info['forwarded_ip']) ? $order_info['ip'] : $order_info['forwarded_ip'],
            'client_datetime' => date('Y-m-d H:i:s'),

            'invoice_first_name'   => urlencode($order_info['payment_firstname']),
            'invoice_last_name'    => urlencode($order_info['payment_lastname']),
            'invoice_email'        => urlencode($order_info['email']),
            'invoice_phone'        => urlencode($order_info['telephone']),
            'invoice_address'      => urlencode($order_info['payment_address_1']),
            'invoice_address2'     => urlencode($order_info['payment_address_2']),
            'invoice_zip_code'     => urlencode($order_info['payment_postcode']),
            'invoice_city_name'    => urlencode($order_info['payment_city']),
            'invoice_state_name'   => urlencode(($order_info['payment_iso_code_2'] != 'US') ? $order_info['payment_zone'] : $order_info['payment_zone_code']),
            'invoice_country_code' => urlencode($order_info['payment_iso_code_2']),

            'shipping_email' => urlencode($order_info['email']),
            'shipping_phone' => urlencode($order_info['telephone']),

            'items' => $items

        ];

        switch ($paymentMethod) {

            case 'CREDIT_CARD':

                $moduleData = array_merge($moduleData, [
                    'card_number'  => urlencode(str_replace(' ', '', $this->request->post['cc_number'])),
                    'expire_month' => urlencode($this->request->post['cc_expire_date_month']),
                    'expire_year'  => urlencode($this->request->post['cc_expire_date_year']),
                    'cvv'          => urlencode($this->request->post['cc_cvv2']),
                ]);

                break;

        }

        if ($this->cart->hasShipping()) {

            $moduleData = array_merge($moduleData, [
                'shipping_first_name'   => urlencode($order_info['shipping_firstname']),
                'shipping_last_name'    => urlencode($order_info['shipping_lastname']),
                'shipping_company_name' => urlencode($order_info['shipping_company']),
                'shipping_address'      => urlencode($order_info['shipping_address_1']),
                'shipping_address2'     => urlencode($order_info['shipping_address_2']),
                'shipping_zip_code'     => urlencode($order_info['shipping_postcode']),
                'shipping_city_name'    => urlencode($order_info['shipping_city']),
                'shipping_state_name'   => urlencode(($order_info['shipping_iso_code_2'] != 'US') ? $order_info['shipping_zone'] : $order_info['shipping_zone_code']),
                'shipping_country_code' => urlencode($order_info['shipping_iso_code_2']),
            ]);

        } else {

            $moduleData = array_merge($moduleData, [
                'shipping_first_name'   => urlencode($order_info['payment_firstname']),
                'shipping_last_name'    => urlencode($order_info['payment_lastname']),
                'shipping_company_name' => urlencode($order_info['payment_company']),
                'shipping_address'      => urlencode($order_info['payment_address_1']),
                'shipping_address2'     => urlencode($order_info['payment_address_2']),
                'shipping_zip_code'     => urlencode($order_info['payment_postcode']),
                'shipping_city_name'    => urlencode($order_info['payment_city']),
                'shipping_state_name'   => urlencode(($order_info['payment_iso_code_2'] != 'US') ? $order_info['payment_zone'] : $order_info['payment_zone_code']),
                'shipping_country_code' => urlencode($order_info['payment_iso_code_2']),
            ]);

        }

        $data = [
            'module'      => urlencode($this->config->get('payment_if_payu_payment_method')),
            'method'      => $paymentMethod,
            'license_key' => urlencode($this->config->get('payment_if_payu_license_key')),
            'ok_url'      => urlencode($this->url->link('extension/payment/if_payu/callback', ['status' => 'ok'], true)),
            'fail_url'    => urlencode($this->url->link('extension/payment/if_payu/callback', ['status' => 'fail'], true)),
            'test'        => ( ! ! $this->config->get('payment_if_payu_test')),
            'data'        => $moduleData
        ];

        $responseObject = $this->curl_request('INIT', $data);

        if ($responseObject === false) {

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode([
                'error' => 'Bilinmeyen bir hata meydana geldi.'
            ]));

        } else {

            if ($responseObject->success) {

                switch ($responseObject->type) {

                    case 'response':

                        $transactionId = $responseObject->transaction_id;
                        $transactionHash = $responseObject->transaction_hash;

                        $validate_response = $this->validate_transaction($paymentMethod, $transactionId, $transactionHash);

                        if ($validate_response === false) {

                            $this->response->addHeader('Content-Type: application/json');
                            $this->response->setOutput(json_encode([
                                'error' => 'Bilinmeyen bir hata meydana geldi.'
                            ]));

                        } else {

                            if ($validate_response->success) {

                                $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_if_payu_order_status_id'));

                                $this->response->addHeader('Content-Type: application/json');
                                $this->response->setOutput(json_encode([
                                    'redirect' => $this->url->link('checkout/success', '', true)
                                ]));

                            } else {

                                $this->response->addHeader('Content-Type: application/json');
                                $this->response->setOutput(json_encode([
                                    'error' => $validate_response->message ?? 'Bilinmeyen bir hata meydana geldi.'
                                ]));

                            }

                        }

                        break;

                    case 'redirect':

                        $this->response->addHeader('Content-Type: application/json');
                        $this->response->setOutput(json_encode([
                            'redirect' => $responseObject->url
                        ]));

                        break;

                    default:

                        $this->response->addHeader('Content-Type: application/json');
                        $this->response->setOutput(json_encode([
                            'error' => 'Bilinmeyen bir hata meydana geldi.'
                        ]));

                        break;

                }

            } else {

                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode([
                    'error' => $responseObject->message
                ]));

            }

        }
    }

    public function callback()
    {
        $this->load->model('checkout/order');

        $status = isset($this->request->get['status']) ? $this->request->get['status'] : null;

        switch ($status) {

            case 'ok':

                $transactionId = $this->request->post['transaction_id'];
                $transactionHash = $this->request->post['transaction_hash'];

                // @todo check order status from backend
                // $transactionId = $this->request->post['transaction_id'];
                // $transactionHash = $this->request->post['transaction_hash'];

                $validate_response = $this->validate_transaction($this->config->get('payment_if_payu_payment_method'), $transactionId, $transactionHash);

                if ($validate_response === false) {

                    $this->session->data['error'] = 'Bilinmeyen bir hata meydana geldi.';

                    $this->response->redirect($this->url->link('checkout/checkout', '', true));

                } else {

                    if ($validate_response->success) {

                        $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_if_payu_order_status_id'));

                        $this->response->redirect($this->url->link('checkout/success', '', true));

                    } else {

                        $this->session->data['error'] = isset($validate_response->message) ? $validate_response->message : 'Bilinmeyen bir hata meydana geldi.';

                        $this->response->redirect($this->url->link('checkout/checkout', '', true));

                    }

                }

                break;

            case 'fail':

                $this->session->data['error'] = isset($this->request->post['message']) ? $this->request->post['message'] : 'Bilinmeyen bir hata meydana geldi.';

                $this->response->redirect($this->url->link('checkout/checkout', '', true));

                break;

            default:

                $this->session->data['error'] = 'Bilinmeyen bir hata meydana geldi.';

                $this->response->redirect($this->url->link('checkout/checkout', '', true));

                break;

        }
    }

    private function validate_transaction($paymentMethod, $id, $hash)
    {
        return $this->curl_request('VALIDATE', [
            'module'           => urlencode($this->config->get('payment_if_payu_payment_method')),
            'method'           => $paymentMethod,
            'license_key'      => urlencode($this->config->get('payment_if_payu_license_key')),
            'test'             => ( ! ! $this->config->get('payment_if_payu_test')),
            'transaction_id'   => $id,
            'transaction_hash' => $hash
        ]);
    }

    private function curl_request($action, $data)
    {
        $curl = curl_init('https://backend.ifyazilim.com/payment/process');

        curl_setopt($curl, CURLOPT_PORT, 443);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, implode('&', [
            'action=' . $action,
            'data=' . json_encode($data)
        ]));

        $response = curl_exec($curl);

        curl_close($curl);

        if ( ! $response) {

            $this->log->write('IfPayuPayment failed: ' . curl_error($curl) . '(' . curl_errno($curl) . ')');

            return false;

        }

        return json_decode($response);
    }
}