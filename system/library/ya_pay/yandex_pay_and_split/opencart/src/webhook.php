<?php
namespace YandexPayAndSplit\OpenCart;

use InvalidArgumentException;

class Webhook
{
    private $controller;
    private $validation_controller_path;
    private $settings_prefix;

    public function __construct(
        $controller,
        $validation_controller_path,
        $settings_prefix = ""
    ) {
        if (!is_object($controller)) {
            throw new InvalidArgumentException("The controller must be a object");
        }

        if (!is_string($validation_controller_path)) {
            throw new InvalidArgumentException("The validation_controller_path must be a string");
        }

        if (!is_string($settings_prefix)) {
            throw new InvalidArgumentException("The settings_prefix must be a string");
        }
        
        $this->controller = $controller;
        $this->validation_controller_path = $validation_controller_path;
        $this->settings_prefix = $settings_prefix;
    }

    public function index($addOrderHistory)
    {
        if (!is_callable($addOrderHistory)) {
            throw new InvalidArgumentException("The addOrderHistory must be a function");
        }

        $body = file_get_contents("php://input");

        $validated_token = $this->controller->load->controller(
            $this->validation_controller_path,
            $body
        );

        if ($validated_token["status"] === "success") {
            if ($validated_token["payload"]["event"] === "ORDER_STATUS_UPDATED") {
                $order = $validated_token["payload"]["order"];

                $order_status_id = "";

                switch ($order["paymentStatus"]) {
                    case "CAPTURED":
                        $order_status_id = (string) $this->controller->config->get(
                            $this->getSettingName("ya_pay_success_order_status_id")
                        );

                        break;

                    case "FAILED":
                        $order_status_id = (string) $this->controller->config->get(
                            $this->getSettingName("ya_pay_error_order_status_id")
                        );

                        break;

                    case "REFUNDED":
                        $order_status_id = (string) $this->controller->config->get(
                            $this->getSettingName("ya_pay_refunded_order_status_id")
                        );

                        break;
                }

                if (!empty($order_status_id)) {
                    $addOrderHistory(array(
                        "id" => (int) $order["orderId"],
                        "status_id" => (int) $order_status_id,
                    ));
                }
            }

            $this->controller->response->addHeader("Content-Type: application/json");
            $this->controller->response->setOutput(
                json_encode(array(
                    "status" => "success",
                ))
            );
            $this->controller->response->addHeader(
                $this->controller->request->server["SERVER_PROTOCOL"] . " 200 OK"
            );
        } else {
            $this->controller->response->addHeader("Content-Type: application/json");
            $this->controller->response->setOutput(
                json_encode(array(
                    "status" => "fail",
                    "reasonCode" => "FORBIDDEN",
                    "reason" => $validated_token["message"],
                ))
            );
            $this->controller->response->addHeader(
                $this->controller->request->server["SERVER_PROTOCOL"] . " 403 Forbidden"
            );
        }
    }

    private function getSettingName($name)
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException("The name must be a string");
        }

        return $this->settings_prefix . $name;
    }
}
