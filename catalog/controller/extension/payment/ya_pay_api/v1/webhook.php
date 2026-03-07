<?php
// phpcs:ignore
class ControllerExtensionPaymentYaPayApiV1Webhook extends Controller
{
    private $webhook;

    public function __construct($registry)
    {
        parent::__construct($registry);
        require_once DIR_SYSTEM . "library/ya_pay/autoload.php";

        $this->webhook = new YandexPayAndSplit\OpenCart\Webhook(
            $this,
            "extension/payment/ya_pay_api/v1/validate",
            "payment_"
        );
    }

    public function index()
    {
        $that = $this;

        $this->webhook->index(function (array $order_data) use ($that) {
            $that->load->model("checkout/order");

            $that->model_checkout_order->addOrderHistory(
                $order_data["id"],
                $order_data["status_id"]
            );
        });
    }
}
