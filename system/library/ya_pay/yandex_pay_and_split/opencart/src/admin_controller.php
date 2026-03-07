<?php

namespace YandexPayAndSplit\OpenCart;

use InvalidArgumentException;

class AdminController
{
    private $controller;
    private $marketplace_url;
    private $user_token_key;
    private $settings_prefix;

    private $log = null;

    // TODO: Find better way for $controller and $log type
    public function __construct(
        $controller,
        $log,
        $marketplace_url,
        $user_token_key,
        $settings_prefix = ""
    ) {
        if (!is_object($controller)) {
            throw new InvalidArgumentException("The controller must be a object");
        }

        if (!is_object($log)) {
            throw new InvalidArgumentException("The log must be a object");
        }

        if (!is_string($marketplace_url)) {
            throw new InvalidArgumentException("The marketplace_url must be a string");
        }

        if (!is_string($user_token_key)) {
            throw new InvalidArgumentException("The user_token_key must be a string");
        }

        if (!is_string($settings_prefix)) {
            throw new InvalidArgumentException("The settings_prefix must be a string");
        }

        $this->controller = $controller;
        $this->marketplace_url = $marketplace_url;
        $this->user_token_key = $user_token_key;
        $this->settings_prefix = $settings_prefix;

        $this->log = $log;
    }

    public function index(array $data = array())
    {
        $this->controller->document->setTitle($this->controller->language->get("heading_title"));

        $data["errors"] = array();
        $user_token = $this->getUserToken();

        if ($this->controller->request->server["REQUEST_METHOD"] == "POST") {
            $data["errors"] = $this->validate();

            if (!$data["errors"]) {
                $this->controller->load->model("setting/setting");

                $this->controller->model_setting_setting->editSetting(
                    $this->getSettingName("ya_pay"),
                    $this->controller->request->post
                );

                $this->controller->session->data["success"] = $this->controller->language->get(
                    "text_success"
                );

                $this->controller->response->redirect(
                    $this->controller->url->link(
                        $this->marketplace_url,
                        $user_token . "&type=payment",
                        true
                    )
                );
            }
        }

        $this->logging("info", "Settings page is loaded");

        if (isset($data["errors"]["warning"])) {
            $data["error_warning"] = $data["errors"]["warning"];
        } else {
            $data["error_warning"] = "";
        }

        $data["breadcrumbs"] = array();

        $data["breadcrumbs"][] = array(
            "text" => $this->controller->language->get("text_home"),
            "href" => $this->controller->url->link("common/dashboard", $user_token, true),
        );

        $data["breadcrumbs"][] = array(
            "text" => $this->controller->language->get("text_extension"),
            "href" => $this->controller->url->link(
                $this->marketplace_url,
                $user_token . "&type=payment",
                true
            ),
        );

        $data["breadcrumbs"][] = array(
            "text" => $this->controller->language->get("heading_title"),
            "href" => $this->controller->url->link("extension/payment/ya_pay", $user_token, true),
        );

        $data["save"] = $this->controller->url->link("extension/payment/ya_pay", $user_token, true);
        $data["back"] = $this->controller->url->link(
            $this->marketplace_url,
            $user_token . "&type=payment",
            true
        );

        $this->controller->load->model("localisation/order_status");

        $data[
            "order_statuses"
        ] = $this->controller->model_localisation_order_status->getOrderStatuses();

        $data[$this->getSettingName("ya_pay_payment_methods")] = $this->createSelectOptions(array(
            "CARD" => "text_available_payment_method_card",
            "SPLIT" => "text_available_payment_method_split",
        ));

        $data[$this->getSettingName("ya_pay_button_themes")] = $this->createSelectOptions(array(
            "BLACK" => "text_button_theme_black",
            "WHITE" => "text_button_theme_white",
            "WHITE-OUTLINED" => "text_button_theme_white_outlined",
        ));

        $data[$this->getSettingName("ya_pay_button_widths")] = $this->createSelectOptions(array(
            "AUTO" => "text_button_width_auto",
            "MAX" => "text_button_width_max",
        ));

        $data[$this->getSettingName("ya_pay_status")] = $this->retrieveSettingValue(
            "ya_pay_status"
        );
        $data[$this->getSettingName("ya_pay_order_status_id")] = $this->retrieveSettingValue(
            "ya_pay_order_status_id"
        );
        $data[
            $this->getSettingName("ya_pay_success_order_status_id")
        ] = $this->retrieveSettingValue("ya_pay_success_order_status_id");
        $data[$this->getSettingName("ya_pay_error_order_status_id")] = $this->retrieveSettingValue(
            "ya_pay_error_order_status_id"
        );
        $data[
            $this->getSettingName("ya_pay_refunded_order_status_id")
        ] = $this->retrieveSettingValue("ya_pay_refunded_order_status_id");

        $data[$this->getSettingName("ya_pay_sort_order")] = $this->retrieveSettingValue(
            "ya_pay_sort_order"
        );
        $data[$this->getSettingName("ya_pay_purpose")] = $this->retrieveSettingValue(
            "ya_pay_purpose"
        );
        $data[$this->getSettingName("ya_pay_merchant_id")] = $this->retrieveSettingValue(
            "ya_pay_merchant_id"
        );
        $data[$this->getSettingName("ya_pay_api_key")] = $this->retrieveSettingValue(
            "ya_pay_api_key"
        );
        $data[
            $this->getSettingName("ya_pay_available_payment_methods")
        ] = $this->retrieveSettingValue("ya_pay_available_payment_methods");

        $data[$this->getSettingName("ya_pay_test_environment")] = $this->retrieveSettingValue(
            "ya_pay_test_environment"
        );
        $data[$this->getSettingName("ya_pay_ttl")] = $this->retrieveSettingValue("ya_pay_ttl");

        $data[$this->getSettingName("ya_pay_is_pay_button_type")] = $this->retrieveSettingValue(
            "ya_pay_is_pay_button_type"
        );

        $data[$this->getSettingName("ya_pay_button_theme")] = $this->retrieveSettingValue(
            "ya_pay_button_theme"
        );
        $data[$this->getSettingName("ya_pay_button_width")] = $this->retrieveSettingValue(
            "ya_pay_button_width"
        );

        $data["header"] = $this->controller->load->controller("common/header");
        $data["column_left"] = $this->controller->load->controller("common/column_left");
        $data["footer"] = $this->controller->load->controller("common/footer");

        $this->controller->response->setOutput(
            $this->controller->load->view("extension/payment/ya_pay", $data)
        );
    }

    public function validate()
    {
        $error_map = array();

        if (!$this->controller->user->hasPermission("modify", "extension/payment/ya_pay")) {
            $error_map["warning"] = $this->controller->language->get("error_permission");
        }

        if (!$this->controller->request->post[$this->getSettingName("ya_pay_merchant_id")]) {
            $error_map["merchant_id"] = $this->controller->language->get("error_merchant_id");
        }

        if (!$this->controller->request->post[$this->getSettingName("ya_pay_api_key")]) {
            $error_map["api_key"] = $this->controller->language->get("error_api_key");
        }

        if (!isset(
            $this->controller->request->post[
                    $this->getSettingName("ya_pay_available_payment_methods")
                ]
        )
        ) {
            $error_map["available_payment_methods"] = $this->controller->language->get(
                "error_available_payment_methods"
            );
        }

        $this->logging("info", "Settings form is validated", json_encode($error_map));

        return $error_map;
    }

    public function install()
    {
        $default_settings = array(
            $this->getSettingName("ya_pay_sort_order") => 0,
            $this->getSettingName("ya_pay_available_payment_methods") => array("CARD", "SPLIT"),
            $this->getSettingName("ya_pay_is_pay_button_type") => 1,
            $this->getSettingName("ya_pay_button_theme") => "BLACK",
            $this->getSettingName("ya_pay_button_width") => "AUTO",
        );

        $this->controller->load->model("localisation/order_status");

        $order_statuses = $this->controller->model_localisation_order_status->getOrderStatuses();

        $pending_status = array_filter($order_statuses, function ($status) {
            return $status["name"] === "Pending";
        });

        if ($pending_status) {
            $pending_key = key($pending_status);
            $default_settings[$this->getSettingName("ya_pay_order_status_id")] =
                $pending_status[$pending_key]["order_status_id"];

            $this->logging(
                "info",
                "Order status: pending",
                (string) $pending_status[$pending_key]["order_status_id"]
            );
        }

        $complete_status = array_filter($order_statuses, function ($status) {
            return $status["name"] === "Complete";
        });

        if ($complete_status) {
            $complete_key = key($complete_status);
            $default_settings[$this->getSettingName("ya_pay_success_order_status_id")] =
                $complete_status[$complete_key]["order_status_id"];

            $this->logging(
                "info",
                "Order status: complete",
                (string) $complete_status[$complete_key]["order_status_id"]
            );
        }

        $failed_status = array_filter($order_statuses, function ($status) {
            return $status["name"] === "Failed";
        });

        if ($failed_status) {
            $failed_key = key($failed_status);
            $default_settings[$this->getSettingName("ya_pay_error_order_status_id")] =
                $failed_status[$failed_key]["order_status_id"];

            $this->logging(
                "info",
                "Order status: failed",
                (string) $failed_status[$failed_key]["order_status_id"]
            );
        }

        $refunded_status = array_filter($order_statuses, function ($status) {
            return $status["name"] === "Refunded";
        });

        if ($refunded_status) {
            $refunded_key = key($refunded_status);
            $default_settings[$this->getSettingName("ya_pay_refunded_order_status_id")] =
                $refunded_status[$refunded_key]["order_status_id"];

            $this->logging(
                "info",
                "Order status: refunded",
                (string) $refunded_status[$refunded_key]["order_status_id"]
            );
        }

        $this->controller->load->model("setting/setting");

        $this->controller->model_setting_setting->editSetting(
            $this->getSettingName("ya_pay"),
            $default_settings
        );

        $this->logging("info", "Module is installed");
    }

    public function uninstall()
    {
        $this->controller->load->model("setting/setting");

        $this->controller->model_setting_setting->deleteSetting($this->getSettingName("ya_pay"));

        $this->logging("info", "Module is uninstalled");
    }

    private function logging($level, $message, $context = "")
    {
        if (!is_string($level)) {
            throw new InvalidArgumentException("The level must be a string");
        }

        if (!is_string($message)) {
            throw new InvalidArgumentException("The message must be a string");
        }

        if (!is_string($context)) {
            throw new InvalidArgumentException("The context must be a string");
        }

        if ($level === "error") {
            $this->log->write("ERROR!" . $message . $context);
            return;
        } elseif ($this->controller->config->get($this->getSettingName("ya_pay_test_environment")) &&
            $level === "info"
        ) {
            $this->log->write("INFO:" . $message . $context);
        }
    }

    private function getUserToken()
    {
        $value = $this->controller->session->data[$this->user_token_key];
        return $this->user_token_key . "=" . $value;
    }

    private function getSettingName($name)
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException("The name must be a string");
        }

        return $this->settings_prefix . $name;
    }

    private function createSelectOptions(array $settings)
    {
        $options = array();

        foreach ($settings as $value => $name) {
            $options[] = array(
                "value" => $value,
                "name" => $this->controller->language->get($name),
            );
        }

        return $options;
    }

    private function retrieveSettingValue($name)
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException("The name must be a string");
        }

        $settingName = $this->getSettingName($name);

        if (isset($this->controller->request->post[$settingName])) {
            return $this->controller->request->post[$settingName];
        }

        return $this->controller->config->get($settingName);
    }
}
