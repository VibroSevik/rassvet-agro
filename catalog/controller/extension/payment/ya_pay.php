<?php

// phpcs:ignore
class ControllerExtensionPaymentYaPay extends Controller
{
    private $ya_pay_controller;

    private $ya_pay_order;

    public function __construct($registry)
    {
        parent::__construct($registry);
        require_once DIR_SYSTEM . "library/ya_pay/autoload.php";

        $this->load->model("checkout/order");

        $is_test_env = $this->config->get("payment_ya_pay_test_environment");

        $url = $is_test_env ? "https://sandbox.pay.yandex.ru/" : "https://pay.yandex.ru/";

        $this->ya_pay_controller = new YandexPayAndSplit\OpenCart\CatalogController(
            $this,
            $this->model_checkout_order,
            $url . "api/merchant/v1/orders",
            "payment_"
        );

        $this->ya_pay_order = new YandexPayAndSplit\OpenCart\YaPayOrder(
            $this,
            VERSION,
            "1.1.3",
            "payment_"
        );
    }

    public function index()
    {
        $data = $this->load->language("extension/payment/ya_pay");

        return $this->ya_pay_controller->index($data);
    }

    public function getPaymentData()
    {
        $this->ya_pay_controller->getPaymentData();
    }

    public function getPaymentLink()
    {
        $this->load->model("checkout/order");

        $order_data = $this->model_checkout_order->getOrder($this->session->data["order_id"]);

        $payload = $this->ya_pay_order->createOrder($order_data);

        $that = $this;

        $this->ya_pay_controller->getPaymentLink($payload, function () use ($that) {
            $order_id = $that->session->data["order_id"];
            $order_status_id = $that->config->get("payment_ya_pay_order_status_id");

            $that->model_checkout_order->addOrderHistory($order_id, $order_status_id);
        });
    }

    public function headerBefore()
    {
        $this->document->addScript(
            "catalog/view/javascript/extension/payment/yandex-pay-and-split.yapay.js"
        );
    }
}
