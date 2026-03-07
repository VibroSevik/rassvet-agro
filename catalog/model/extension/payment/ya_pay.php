<?php
// phpcs:ignore
class ModelExtensionPaymentYaPay extends Model
{
    public function getMethod()
    {
        $this->load->language("extension/payment/ya_pay");

        $method_data = array(
            "code" => "ya_pay",
            "title" => $this->language->get("heading_title"),
            "terms" => "",
            "sort_order" => $this->config->get("payment_ya_pay_sort_order"),
        );

        return $method_data;
    }
}
