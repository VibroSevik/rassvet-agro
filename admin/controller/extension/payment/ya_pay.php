<?php

// phpcs:ignore
class ControllerExtensionPaymentYaPay extends Controller
{
    private $ya_pay_controller;

    public function __construct($registry)
    {
        parent::__construct($registry);
        require_once DIR_SYSTEM . "library/ya_pay/autoload.php";
        $this->ya_pay_controller = new YandexPayAndSplit\OpenCart\AdminController(
            $this,
            new \Log("ya_pay.log"),
            "marketplace/extension",
            "user_token",
            "payment_"
        );
    }

    public function index()
    {
        $this->load->language("extension/payment/ya_pay");

        $this->ya_pay_controller->index();
    }

    public function install()
    {
        $this->load->model("setting/event");

        // Event method in "catalog/controller/extension/payment/ya_pay.php"
        $this->model_setting_event->deleteEventByCode("yapay_sdk_connect");
        $this->model_setting_event->addEvent(
            "yapay_sdk_connect",
            "catalog/controller/common/header/before",
            "extension/payment/ya_pay/headerBefore"
        );

        $this->ya_pay_controller->install();
    }

    public function uninstall()
    {
        $this->load->model("setting/event");

        $this->model_setting_event->deleteEventByCode("yapay_sdk_connect");

        $this->ya_pay_controller->uninstall();
    }
}
