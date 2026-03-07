<?php

namespace YandexPayAndSplit\OpenCart;

use InvalidArgumentException;

class CatalogController
{
    private $controller;
    private $order_total_model;
    private $create_link_url;
    private $settings_prefix;

    // TODO: Find better way for $controller and $order_total_model type
    public function __construct(
        $controller,
        $order_total_model,
        $create_link_url,
        $settings_prefix = ""
    ) {
        if (!is_object($controller)) {
            throw new InvalidArgumentException("The controller must be a object");
        }

        if (!is_object($order_total_model)) {
            throw new InvalidArgumentException("The order_total_model must be a object");
        }

        if (!is_string($create_link_url)) {
            throw new InvalidArgumentException("The create_link_url must be a string");
        }

        if (!is_string($settings_prefix)) {
            throw new InvalidArgumentException("The settings_prefix must be a string");
        }

        $this->controller = $controller;
        $this->order_total_model = $order_total_model;
        $this->create_link_url = $create_link_url;
        $this->settings_prefix = $settings_prefix;
    }

    public function index(array $data = array())
    {
        $is_pay_button_type_name = $this->getSettingName("ya_pay_is_pay_button_type");
        $data[$is_pay_button_type_name] = $this->controller->config->get($is_pay_button_type_name);

        return $this->controller->load->view("extension/payment/ya_pay", $data);
    }

    private function getOrderTotal()
    {
        $current_currency_code = $this->controller->session->data["currency"];

        $order_totals = $this->order_total_model->getOrderTotals(
            $this->controller->session->data["order_id"]
        );

        foreach ($order_totals as $order_total) {
            if ($order_total["code"] === "total") {
                return $this->controller->currency->format(
                    (float) $order_total["value"],
                    $current_currency_code,
                    0,
                    false
                );
            }
        }

        return 0;
    }

    public function getPaymentData()
    {
        $is_test_env = $this->controller->config->get(
            $this->getSettingName("ya_pay_test_environment")
        );

        $env = $is_test_env ? "SANDBOX" : "PRODUCTION";

        $current_currency_code = $this->controller->session->data["currency"];
        $merchant_id = $this->controller->config->get($this->getSettingName("ya_pay_merchant_id"));

        $available_payment_methods = $this->controller->config->get(
            $this->getSettingName("ya_pay_available_payment_methods")
        );

        $button_theme = $this->controller->config->get(
            $this->getSettingName("ya_pay_button_theme")
        );
        $button_width = $this->controller->config->get(
            $this->getSettingName("ya_pay_button_width")
        );

        $response = json_encode(array(
            "theme" => $button_theme,
            "width" => $button_width,

            "env" => $env,
            "merchant_id" => $merchant_id,
            "total_amount" => (string) $this->getOrderTotal(),
            "current_currency_code" => $current_currency_code,
            "available_payment_methods" => $available_payment_methods,
        ));

        $this->controller->response->addHeader("Content-Type: application/json");
        $this->controller->response->setOutput($response);
    }

    private function createLink(array $payload)
    {
        $is_test_env = $this->controller->config->get(
            $this->getSettingName("ya_pay_test_environment")
        );

        $api_key = $this->controller->config->get(
            $this->getSettingName($is_test_env ? "ya_pay_merchant_id" : "ya_pay_api_key")
        );

        $curl_session = curl_init($this->create_link_url);

        curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_session, CURLOPT_POST, true);
        curl_setopt($curl_session, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Authorization: Api-key $api_key",
        ));
        curl_setopt($curl_session, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($curl_session);

        curl_close($curl_session);

        return $response;
    }

    public function getPaymentLink(array $payload, $addOrderHistory)
    {
        if (!is_callable($addOrderHistory)) {
            throw new InvalidArgumentException("The addOrderHistory must be a function");
        }

        $response = $this->createLink($payload);

        $decoded_response = json_decode($response, true);

        if ($decoded_response["status"] == "success") {
            $addOrderHistory();
        }

        $this->controller->response->addHeader("Content-Type: application/json");
        $this->controller->response->setOutput($response);
    }

    private function getSettingName($name)
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException("The name must be a string");
        }

        return $this->settings_prefix . $name;
    }
}
