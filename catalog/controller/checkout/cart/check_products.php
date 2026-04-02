<?php

use Dto\ProductDto;
use ResponseUtils\ResponseStatuses;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * Class <strong>ControllerCheckoutCartCheckProducts</strong> used for products check after in cart submit. <br>
 * Gets array {@lnik ProductDto products} in {@link ControllerCheckoutCartCheckProducts::index()}.
 */
class ControllerCheckoutCartCheckProducts extends Controller {

    private const ERROR_PRICE_TEXT = 'Цены на некоторые товары изменились!';
    private const ERROR_STOCK_TEXT = 'Некоторые товары отсутствуют в нужном количестве!';

    public function index() {
        $this->response->addHeader('Content-Type: application/json');

        if ($this->request->server['REQUEST_METHOD'] !== 'POST') {
            $this->response->setOutput(json_encode(
                ResponseStatuses::HTTP_METHOD_NOT_ALLOWED
            ));
            return;
        }

        $productsData = file_get_contents('php://input');

        $serializer = $this->getSerializer();

        /** @var ProductDto[] $products */
        $products = $serializer->deserialize($productsData, ProductDto::class . '[]', [
            ObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true
        ]);

        $this->updateCart($products);

        $errors = $this->checkProducts($products);
        if (!empty($errors)) {
            $this->response->setOutput(json_encode(
                array_merge(ResponseStatuses::HTTP_BAD_REQUEST, ['errors' => $errors])
            ));
            return;
        }

        $this->response->setOutput(json_encode(
            array_merge(ResponseStatuses::HTTP_OK, [
                'success' => true,
                'redirect' => $this->url->link('checkout/checkout', '', true)
            ])
        ));
    }

    /**
     * Returns json serializer from context.
     * @return JsonSerializer
     */
    private function getSerializer(): ?JsonSerializer
    {
        $this->load->library('jsonserializer');
        return $this->registry->get('jsonserializer');
    }

    /**
     * Updates products quantity in cart.
     * @param array<ProductDto> $products
     * @return void
     */
    private function updateCart(array $products): void
    {
        $storedProducts = $this->cart->getProducts();

        foreach ($products as $product) {
            foreach ($storedProducts as $storedProduct) {
                if ($product->id() !== $storedProduct['product_id']) {
                    continue;
                }

                $this->cart->update($storedProduct['cart_id'], $product->quantity());
            }
        }
    }

    /**
     * Checks products by price and quantity.
     * @param array<ProductDto> $products
     * @return array<string, array<string, string>>
     */
    private function checkProducts(array $products): array
    {
        $storedProducts = $this->cart->getProducts();
        $errors = [];

        foreach ($products as $product) {
            foreach ($storedProducts as $storedProduct) {
                if ($product->id() !== $storedProduct['product_id']) {
                    continue;
                }

                if ($product->price() !== $storedProduct['price']) {
                    // $errors['error_price'][$product->id()] = $storedProduct['price'];

                    $errors['errorPrice'][] = [
                        'id' => $product->id(),
                        'price' => $storedProduct['price']
                    ];
                }

                if (empty($storedProduct['stock'])) {
                    $errors['errorStock'][] = [
                        'id' => $product->id()
                    ];
                }
            }
        }

        if (isset($errors['errorPrice'])) {
            $errors['errorPriceText'] = self::ERROR_PRICE_TEXT;
        }

        if (isset($errors['errorStock'])) {
            $errors['errorStockText'] = self::ERROR_STOCK_TEXT;
        }

        return $errors;
    }
}