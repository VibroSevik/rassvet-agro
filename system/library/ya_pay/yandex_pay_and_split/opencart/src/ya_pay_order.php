<?php
namespace YandexPayAndSplit\OpenCart;

use InvalidArgumentException;

class YaPayOrder
{
    private $version;
    private $plugin_version;
    private $controller;
    private $settings_prefix;

    // TODO: Find better way for $controller type
    public function __construct(
        $controller,
        $version,
        $plugin_version,
        $settings_prefix = ""
    ) {
        if (!is_object($controller)) {
            throw new InvalidArgumentException("The controller must be a object");
        }

        if (!is_string($version)) {
            throw new InvalidArgumentException("The version must be a string");
        }

        if (!is_string($plugin_version)) {
            throw new InvalidArgumentException("The plugin_version must be a string");
        }

        if (!is_string($settings_prefix)) {
            throw new InvalidArgumentException("The settings_prefix must be a string");
        }

        $this->version = $version;
        $this->plugin_version = $plugin_version;
        $this->controller = $controller;
        $this->settings_prefix = $settings_prefix;
    }

    private function formatTotalByCurrency($number, $currency)
    {
        if (!is_float($number)) {
            throw new InvalidArgumentException("The number must be a float number");
        }

        if (!is_string($currency)) {
            throw new InvalidArgumentException("The currency must be a string");
        }

        return $this->controller->currency->format($number, $currency, "", false);
    }

    private function getOrderItemsWithShipping(array $products, $shipping_id)
    {
        if (!is_int($shipping_id)) {
            throw new InvalidArgumentException("The shipping_id must be a integer number");
        }

        if (isset($this->controller->session->data["shipping_method"])) {
            $current_currency_code = $this->controller->session->data["currency"];

            $shipping_method = $this->controller->session->data["shipping_method"];

            $products["shipping"] = array(
                "productId" => "shipping-" . $shipping_id,
                "quantity" => array(
                    "count" => "1",
                ),
                "title" => $shipping_method["title"],
                "total" => $this->formatTotalByCurrency(
                    (float) $shipping_method["cost"],
                    $current_currency_code
                ),
            );
        }

        return $products;
    }

    private function getOrderItems()
    {
        $products = $this->controller->cart->getProducts();

        $that = $this;

        $transformed_products = array_map(function ($item) use ($that) {
            $current_currency_code = $that->controller->session->data["currency"];

            $that->controller->load->model("catalog/product");

            $product_info = $that->controller->model_catalog_product->getProduct(
                $item["product_id"]
            );

            return array(
                "productId" => $item["product_id"],
                "quantity" => array(
                    "count" => $item["quantity"],
                    "available" => $product_info["quantity"],
                ),
                "title" => $item["name"],
                "total" => $that->formatTotalByCurrency($item["total"], $current_currency_code),
                "discountedUnitPrice" => $that->formatTotalByCurrency(
                    $item["price"],
                    $current_currency_code
                ),
                "subtotal" => $that->formatTotalByCurrency(
                    $product_info["price"] * $item["quantity"],
                    $current_currency_code
                ),
                "unitPrice" => $that->formatTotalByCurrency(
                    (float) $product_info["price"],
                    $current_currency_code
                ),
            );
        }, $products);

        foreach ($products as $key => $value) {
            $products_first_key = $key;
            break;
        }

        return $this->getOrderItemsWithShipping(
            $transformed_products,
            (int) $products[$products_first_key]["shipping"]
        );
    }

    private function getOrderShippingAddress(array $order_data)
    {
        $shipping_address_format = "%s, %s, address 1: %s";

        $shipping_address = sprintf(
            $shipping_address_format,
            $order_data["shipping_country"],
            $order_data["shipping_city"],
            $order_data["shipping_address_1"]
        );

        if (!empty($order_data["shipping_address_2"])) {
            $shipping_address .= ", address 2: " . $order_data["shipping_address_2"];
        }

        return $shipping_address;
    }

    public function createOrder(array $order_data)
    {
        $ttl = $this->controller->config->get($this->getSettingName("ya_pay_ttl"));

        $purpose = $this->controller->config->get($this->getSettingName("ya_pay_purpose"));

        $metadata_format = "cms_name:%s;cms_version:%s;version:%s";
        $metadata = sprintf($metadata_format, "opencart", $this->version, $this->plugin_version);

        $order = array(
            "availablePaymentMethods" => $this->controller->config->get(
                $this->getSettingName("ya_pay_available_payment_methods")
            ),
            "cart" => array(
                "items" => array_values($this->getOrderItems()),
                "total" => array(
                    "amount" => $this->formatTotalByCurrency(
                        (float) $order_data["total"],
                        $order_data["currency_code"]
                    ),
                ),
            ),
            "currencyCode" => $order_data["currency_code"],
            "isPrepayment" => false,
            "metadata" => $metadata,
            "orderId" => (string) $order_data["order_id"],
            "orderSource" => "CMS_PLUGIN",
            "preferredPaymentMethod" => "FULLPAYMENT",
            "redirectUrls" => array(
                "onError" => $this->controller->url->link("account/order", array(), true),
                "onSuccess" => str_replace(
                    "&amp;",
                    "&",
                    $this->controller->url->link(
                        "checkout/success",
                        "order_id=" . $order_data["order_id"],
                        true
                    )
                ),
                "onAbort" => $this->controller->url->link("checkout/checkout", array(), true),
            ),
            "risk" => array(
                "shippingAddress" => $this->getOrderShippingAddress($order_data),
            ),
        );

        if (!empty($ttl)) {
            $order["ttl"] = $ttl;
        }

        if (!empty($order_data["telephone"])) {
            $order["billingPhone"] = $order_data["telephone"];
            $order["risk"]["shippingPhone"] = $order_data["telephone"];
        }

        if (!empty($purpose)) {
            $order["purpose"] = $purpose;
        }

        return $order;
    }

    private function getSettingName($name)
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException("The name must be a string");
        }

        return $this->settings_prefix . $name;
    }
}
