<?php
namespace YandexPayAndSplit\OpenCart;

use InvalidArgumentException;

class Validate
{
    private $merchant_id;
    private $jwk_url;

    public function __construct($merchant_id, $jwk_url)
    {
        if (!is_string($merchant_id)) {
            throw new InvalidArgumentException("The merchant_id must be a string");
        }

        if (!is_string($jwk_url)) {
            throw new InvalidArgumentException("The jwk_url must be a string");
        }

        $this->merchant_id = $merchant_id;
        $this->jwk_url = $jwk_url;
    }

    public function index($token, $parseKeySet, $decodeKeySet)
    {
        if (empty($token)) {
            return array("status" => "fail", "message" => "No JWT");
        }

        if (!is_string($token)) {
            throw new InvalidArgumentException("The JWT token must be a string");
        }

        if (!is_callable($parseKeySet)) {
            throw new InvalidArgumentException("The parseKeySet must be a function");
        }

        if (!is_callable($decodeKeySet)) {
            throw new InvalidArgumentException("The decodeKeySet must be a function");
        }

        $curl_session = curl_init($this->jwk_url);

        curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);

        $keys_json = curl_exec($curl_session);

        curl_close($curl_session);

        $keys_data = json_decode($keys_json, true);

        try {
            $keys = $parseKeySet(array("keys_data" => $keys_data));
            $payload = $decodeKeySet(array("token" => $token, "keys" => $keys));
        } catch (\Exception $e) {
            return array("status" => "fail", "message" => $e->getMessage());
        }

        if ($payload->merchantId === $this->merchant_id) {
            return array(
                "status" => "success",
                "payload" => json_decode(json_encode($payload), true),
            );
        } else {
            return array("status" => "fail", "message" => "Merchant id incorrect");
        }
    }
}
