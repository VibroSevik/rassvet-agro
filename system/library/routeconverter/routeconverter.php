<?php

namespace RouteConverter;

/**
 * Преобразует URL с дефисами в нижние подчёркивания. <br>
 * Например: checkout/cart/check-products -> checkout/cart/check_products <br>
 * Например: my-custom-page/my-action -> my_custom_page/my_action <br>
 * На данный момент реализует преобразование из дефиса в нижние подчёркивания, может быть расширяемым. <br>
 * See in {@link ControllerStartupRouter Router}
 */
class RouteConverter
{
    public function convert(string $route): string {
        return str_replace('-', '_', $route);
    }
}