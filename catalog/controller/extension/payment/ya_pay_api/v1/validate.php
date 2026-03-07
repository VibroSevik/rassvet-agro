<?php

// phpcs:ignore
class ControllerExtensionPaymentYaPayApiV1Validate extends Controller
{
    private $validate;

    public function __construct($registry)
    {
        parent::__construct($registry);
        require_once DIR_SYSTEM . "library/ya_pay/autoload.php";

        $is_test_env = $this->config->get("payment_ya_pay_test_environment");

        $jwk_url = $is_test_env ? "https://sandbox.pay.yandex.ru/" : "https://pay.yandex.ru/";

        $this->validate = new YandexPayAndSplit\OpenCart\Validate(
            $this->config->get("payment_ya_pay_merchant_id"),
            $jwk_url . "api/jwks"
        );
    }

    public function index($token)
    {
        if (!is_string($token)) {
            throw new InvalidArgumentException("The JWT token must be a string");
        }

        return $this->validate->index(
            $token,
            function (array $data) {
                return YandexPayAndSplit\JWT\JWK::parseJWKSet($data["keys_data"]);
            },
            function (array $data) {
                return YandexPayAndSplit\JWT\JWT::decode($data["token"], $data["keys"]);
            }
        );
    }
}
