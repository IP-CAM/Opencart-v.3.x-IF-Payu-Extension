<?php

class ControllerExtensionPaymentIfPayu extends Controller
{
    private $error = array();

    public function index()
    {
        $this->load->language('extension/payment/if_payu');

        $this->document->setTitle($this->language->get('IF Payu'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_if_payu', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['payment_method'])) {
            $data['error_payment_method'] = $this->error['payment_method'];
        } else {
            $data['error_payment_method'] = '';
        }

        if (isset($this->error['license_key'])) {
            $data['error_license_key'] = $this->error['license_key'];
        } else {
            $data['error_license_key'] = '';
        }

        if (isset($this->error['secret_key'])) {
            $data['error_secret_key'] = $this->error['secret_key'];
        } else {
            $data['error_secret_key'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/if_payu', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/payment/if_payu', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

        if (isset($this->request->post['payment_if_payu_payment_method'])) {
            $data['payment_if_payu_payment_method'] = $this->request->post['payment_if_payu_payment_method'];
        } else {
            $data['payment_if_payu_payment_method'] = $this->config->get('payment_if_payu_payment_method');
        }

        if (isset($this->request->post['payment_if_payu_license_key'])) {
            $data['payment_if_payu_license_key'] = $this->request->post['payment_if_payu_license_key'];
        } else {
            $data['payment_if_payu_license_key'] = $this->config->get('payment_if_payu_license_key');
        }

        if (isset($this->request->post['payment_if_payu_secret_key'])) {
            $data['payment_if_payu_secret_key'] = $this->request->post['payment_if_payu_secret_key'];
        } else {
            $data['payment_if_payu_secret_key'] = $this->config->get('payment_if_payu_secret_key');
        }

        if (isset($this->request->post['payment_if_payu_test'])) {
            $data['payment_if_payu_test'] = $this->request->post['payment_if_payu_test'];
        } else {
            $data['payment_if_payu_test'] = $this->config->get('payment_if_payu_test');
        }

        if (isset($this->request->post['payment_if_payu_transaction'])) {
            $data['payment_if_payu_transaction'] = $this->request->post['payment_if_payu_transaction'];
        } else {
            $data['payment_if_payu_transaction'] = $this->config->get('payment_if_payu_transaction');
        }

        if (isset($this->request->post['payment_if_payu_total'])) {
            $data['payment_if_payu_total'] = $this->request->post['payment_if_payu_total'];
        } else {
            $data['payment_if_payu_total'] = $this->config->get('payment_if_payu_total');
        }

        if (isset($this->request->post['payment_if_payu_order_status_id'])) {
            $data['payment_if_payu_order_status_id'] = $this->request->post['payment_if_payu_order_status_id'];
        } else {
            $data['payment_if_payu_order_status_id'] = $this->config->get('payment_if_payu_order_status_id');
        }

        $this->load->model('localisation/order_status');

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        if (isset($this->request->post['payment_if_payu_geo_zone_id'])) {
            $data['payment_if_payu_geo_zone_id'] = $this->request->post['payment_if_payu_geo_zone_id'];
        } else {
            $data['payment_if_payu_geo_zone_id'] = $this->config->get('payment_if_payu_geo_zone_id');
        }

        $this->load->model('localisation/geo_zone');

        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        if (isset($this->request->post['payment_if_payu_status'])) {
            $data['payment_if_payu_status'] = $this->request->post['payment_if_payu_status'];
        } else {
            $data['payment_if_payu_status'] = $this->config->get('payment_if_payu_status');
        }

        if (isset($this->request->post['payment_if_payu_sort_order'])) {
            $data['payment_if_payu_sort_order'] = $this->request->post['payment_if_payu_sort_order'];
        } else {
            $data['payment_if_payu_sort_order'] = $this->config->get('payment_if_payu_sort_order');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/if_payu', $data));
    }

    protected function validate()
    {
        if ( ! $this->user->hasPermission('modify', 'extension/payment/if_payu')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ( ! $this->request->post['payment_if_payu_payment_method']) {
            $this->error['payment_method'] = $this->language->get('error_payment_method');
        }

        if ( ! $this->request->post['payment_if_payu_license_key']) {
            $this->error['license_key'] = $this->language->get('error_license_key');
        }

        if ( ! $this->request->post['payment_if_payu_secret_key']) {
            $this->error['secret_key'] = $this->language->get('error_secret_key');
        }

        return ! $this->error;
    }
}