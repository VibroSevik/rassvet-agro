window.addEventListener('load', () => {
    const cartManager = new CartManager();
    cartManager.addProductsIntoReactiveContext();
    cartManager.addProductsCheckAfterSubmitForm();
});

class CartManager {
    /**
     * @type {CartTemplateService}
     * @private
     */
    _cartTemplateService = null;

    /**
     * @type {CartTemplateBridge}
     * @private
     */
    _cartTemplateBridge = null;

    /**
     * @type {CartHttpClient}
     * @private
     */
    _httpClient = null;

    /**
     * @type {ProductNode[]}
     * @private
     */
    _productNodes = null;

    constructor() {
        this._cartTemplateService = new CartTemplateService();
        this._cartTemplateBridge = new CartTemplateBridge();
        this._httpClient = new CartHttpClient();
    }

    /**
     * Добавляет все товары в корзине в реактивный контекст, например,
     * общая стоимость будет изменяться в зависимости от изменения количества товара.
     *
     * Также добавляет удаление товаров из корзины по нажатии на кнопку удаления.
     */
    addProductsIntoReactiveContext() {
        this._productNodes = this._cartTemplateService.getProductNodes();
        const cartQuantityElement = this._cartTemplateBridge.getCartQuantityElement();

        this._productNodes.forEach(productNode => {
            const { productQuantityInput, productPriceElement, productTotalPriceElement } = productNode;

            productQuantityInput.addEventListener('input', (event) => {
                this._productQuantityInputHandler(
                    event,
                    this._cartTemplateService.getPrice(productPriceElement),
                    productTotalPriceElement,
                    this._cartTemplateBridge.getTotalPrices(),
                    cartQuantityElement
                );
            });

            const productDeleteButton = productQuantityInput.nextElementSibling.children[0];
            productDeleteButton.addEventListener('click', () => {
                this._productButtonDeleteHandler(productPriceElement);
            });
        });
    }

    /**
     * Обрабатывает нажатие по кнопке подтверждения товаров в корзине.
     */
    addProductsCheckAfterSubmitForm() {
        const button = document.getElementById('button-checkout');

        button.addEventListener('click', (event) => {
            event.preventDefault();
            this._productsSubmitButtonHandler(this._productNodes);
        });
    }

    /**
     * Обрабатывает нажатие по кнопке удаления товара в корзине.
     * @param productPriceElement
     * @private
     */
    _productButtonDeleteHandler(productPriceElement) {
        const productContent = productPriceElement.parentNode;
        const cartContent = productContent.parentNode;

        cartContent.removeChild(productContent);

        if (cartContent.children.length === 0) {
            this._cartTemplateService.clearCart();
        }

        const tooltips = document.getElementsByClassName('tooltip');
        [...tooltips].forEach(tooltip => {
            tooltip.parentNode.removeChild(tooltip);
        });
    }

    /**
     * Обработчик для реактивного изменения стоимости товара и общей стоимости в корзине в момент изменения количества товара. <br>
     * Обновляет поле "Всего" у товара и поля "Сумма" и "Итого" у всей корзины. <br>
     * Также обновляет количество товаров рядом с иконкой корзины в правом верхнем углу страницы.
     * @param event событие ввода нового количества товара
     * @param productPrice стоимость товара за одну штуку
     * @param productTotalPriceElement элемент общей стоимости товара
     * @param totalPriceElements элемент общей стоимости всех товаров
     * @param cartQuantityElement элемент количества товаров в правом верхнем углу страницы
     * @private
     */
    _productQuantityInputHandler(event, productPrice, productTotalPriceElement, totalPriceElements, cartQuantityElement) {
        const quantity = event.target.value;

        const [newProductTotalPrice, newTotalPrice] = this._cartTemplateService.getNewTotalPrice(
            quantity,
            productPrice,
            productTotalPriceElement,
            totalPriceElements[0]
        );

        productTotalPriceElement.innerText = newProductTotalPrice;

        // оказывается, в середине может быть стоимость доставки, ее не трогать
        totalPriceElements[0].innerText = newTotalPrice;
        totalPriceElements[totalPriceElements.length - 1].innerText = newTotalPrice;

        // обновление товаров рядом с иконкой корзины
        cartQuantityElement.innerText = this._cartTemplateService.getTotalQuantities(this._productNodes);
    }

    /**
     * Отправляет статистику по товарам из корзины в сервисы яндекс метрики и отправляет запрос с товарами на сервер
     * для проверки, например, корректный ли ценник или количество.
     * Удаляет существующие оповещения об ошибках из корзины.
     * Если сервер пришлёт новые ошибки - добавляет их в шапку корзины, т.е. в место, где были старые удалённые ошибки.
     * @private
     */
    _productsSubmitButtonHandler() {
        this._cartTemplateService.sendDataForEcommerceYandexMetrica(this._productNodes);

        this._cartTemplateService.removeErrorsFromCart();

        // убираем красный цвет ошибки со всех полей для ввода и цен на товары
        this._productNodes.forEach(productNode => {
            const { productQuantityInput, productPriceElement } = productNode;
            this._cartTemplateService.removeStockErrorFromInput(productQuantityInput);
            this._cartTemplateService.removeStockErrorFromInput(productPriceElement);
        });

        const requestBody = this._productNodes.map(productNode => {
            const { productQuantityInput, productPriceElement } = productNode;

            return {
                'id': productPriceElement.parentNode.id,
                'quantity': productQuantityInput.value,
                'price': this._cartTemplateService.getPrice(productPriceElement)
            };
        });

        this._httpClient.sendProducts(requestBody)
            .then(responseData => {
                if (responseData.success || responseData.redirect) {
                    window.location.href = responseData.redirect;
                    return;
                }

                this._cartTemplateService.addErrors(responseData.errors, this._productNodes)
            });
    }
}

class CartHttpClient {
    /**
     * Отправляет запрос на сервер для проверки товаров в корзине, например, чё по количеству и цене.
     * @param requestBody
     * @returns {Promise<any>}
     */
    async sendProducts(requestBody) {
        const response = await fetch('/index.php?route=checkout/cart/check-products', {
            method: 'post',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestBody)
        });

        return await response.json();
    }
}

class YandexMetricaService {
    static YANDEX_METRICA_EVENT_ADD = 'add';
    static YANDEX_METRICA_EVENT_REMOVE = 'remove';

    /**
     * Отправляет данные в сервисы яндекс метрики.
     * @param id
     * @param event
     * @param idType
     * @param quantity
     * @param async
     */
    sendData(id, event, idType = 'product_id', quantity = null, async = true) {
        $.ajax({
            url: 'index.php?route=product/product/getProductDataForYandexMetrica',
            async: async,
            type: 'post',
            data: 'id=' + id + '&event=' + event + '&id_type=' + idType + '&quantity=' + quantity,
            dataType: 'json',
            success: function(json) {
                if (event === YandexMetricaService.YANDEX_METRICA_EVENT_ADD) {
                    dataLayer.push({"ecommerce": {"currencyCode": json['currency_code'], "add": {"products": [json['product']]}}});
                    return;
                }

                if (event === YandexMetricaService.YANDEX_METRICA_EVENT_REMOVE) {
                    dataLayer.push({"ecommerce": {"currencyCode": json['currency_code'], "remove": {"products": [json['product']]}}});
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    }
}

class CartTemplateService {
    /**
     * @type {CartTemplateBridge}
     * @private
     */
    _cartTemplateBridge = null;

    /**
     * @type {YandexMetricaService}
     * @private
     */
    _yandexMetricaSerivce = null;

    constructor() {
        this._cartTemplateBridge = new CartTemplateBridge();
        this._yandexMetricaSerivce = new YandexMetricaService();
    }

    /**
     * Создаёт корзину без товаров. <br>
     * Нужно когда в корзине удаляется последний товар подчистить ненужные элементы и кнопки.
     *
     * Все классы взяты с инспектора со страницы /error/not_found.twig
     */
    clearCart() {
        const cart = this._cartTemplateBridge.getCart();
        const cartContent = cart.getElementsByClassName('row')[0].children[0];
        const cartTitle = cartContent.children[0];

        const cartButtons = cart.getElementsByClassName('buttons clearfix')[0];

        [...cartContent.children].forEach(cartElement => {
            if (cartElement === cartTitle) {
                return;
            }

            if (cartElement === cartButtons) {
                return;
            }

            cartContent.removeChild(cartElement);
        });

        cartContent.removeChild(cartButtons);

        const [p, div] = this._cartTemplateBridge.createClearCart();

        cartContent.appendChild(p);

        cartButtons.removeChild(cartButtons.children[0]);
        cartButtons.removeChild(cartButtons.children[0]);

        cartButtons.appendChild(div);

        cartContent.appendChild(cartButtons);
    }

    /**
     * Отправляет данные в сервисы яндекс метрики.
     * @param productNodes
     */
    sendDataForEcommerceYandexMetrica(productNodes) {
        productNodes.forEach(productNode => {
            const { productQuantityInput } = productNode;

            const newQuantity = productQuantityInput.value;
            const oldQuantity = productQuantityInput.attributes.value.value;

            const quantityDifference = oldQuantity - newQuantity;
            if (quantityDifference === 0) {
                return;
            }

            const key = productQuantityInput.attributes.name.value.match(/\d+/g)[0];

            console.log(newQuantity, oldQuantity, quantityDifference, key);

            if (quantityDifference < 0) {
                this._yandexMetricaSerivce.sendData(
                    key,
                    YandexMetricaService.YANDEX_METRICA_EVENT_ADD,
                    'key',
                    Math.abs(quantityDifference),
                    false
                );
                return;
            }

            this._yandexMetricaSerivce.sendData(
                key,
                YandexMetricaService.YANDEX_METRICA_EVENT_REMOVE,
                'key',
                Math.abs(quantityDifference),
                false
            );
        })
    }

    /**
     * Возвращает новую стоимость товара и новую стоимость всех товаров.
     * @param quantity
     * @param productPrice
     * @param productTotalPriceElement
     * @param totalPriceElement
     * @returns array
     */
    getNewTotalPrice(quantity, productPrice, productTotalPriceElement, totalPriceElement) {
        const newProductTotalPrice = Math.round(quantity) * productPrice;

        const oldProductTotalPrice = this.getPrice(productTotalPriceElement);
        const oldTotalPrice = this.getPrice(totalPriceElement);

        const newTotalPrice = oldTotalPrice + (newProductTotalPrice - oldProductTotalPrice);

        const currencyFormat = this.getCurrency(productTotalPriceElement);
        return [newProductTotalPrice + currencyFormat, newTotalPrice + currencyFormat];
    }

    /**
     * Возвращает массив HTML-элементов товаров.
     * @returns ProductNode[]
     */
    getProductNodes() {
        const productQuantityInputs = this._cartTemplateBridge.getProductsQuantityInputs();

        return productQuantityInputs.map(productQuantityInput => {
            this._setupInput(productQuantityInput);

            const productPriceElement = productQuantityInput.parentNode.parentNode.nextElementSibling;
            const productTotalPriceElement = productPriceElement.nextElementSibling;
            const productTitleElement = productPriceElement.previousElementSibling.previousElementSibling.previousElementSibling;

            return new ProductNode(
                productTitleElement,
                productQuantityInput,
                productPriceElement,
                productTotalPriceElement
            );
        });
    }

    /**
     * Запрещает ввод пробела, нечисловых значений, точки и запятой с нампада (она странно работает). <br>
     * Также запрещает копирование текста через ctrl+v и через ПКМ на мыши вставить. <br>
     * Вместо ввода запрещённого символа вводится пустая строка. <br>
     * Максимальное количество товара ограничивается трёхзначным числом.
     * @param productQuantityInput
     * @private
     */
    _setupInput(productQuantityInput) {
        productQuantityInput.addEventListener('keydown', (event) => {
            if (event.code === 'NumpadDecimal' && (event.key === '.' || event.key === ',')) {
                event.preventDefault();
            }

            if (event.code === 'Space') {
                event.preventDefault();
            }
        });

        productQuantityInput.addEventListener('beforeinput', (event) => {
            if (event.inputType === 'insertFromPaste') {
                const pastedText = event.data;
                if (pastedText && /[^0-9.,]/.test(pastedText)) {
                    event.preventDefault();
                }
            }
        });

        productQuantityInput.addEventListener('input', (event) => {
            productQuantityInput.value = productQuantityInput.value.replace(/[^0-9.,]/g, '');
            productQuantityInput.value = productQuantityInput.value.slice(0, 3);
        });
    }

    /**
     * Возвращает округлённое в большую сторону количество всех товаров в корзине.
     * @returns number
     */
    getTotalQuantities(productNodes) {
        const quantities = productNodes.map(productNode => {
            const { productQuantityInput} = productNode;
            return Number(productQuantityInput.value);
        });

        return Math.round(quantities.reduce((sum, quantity) => {
            return sum + quantity;
        }));
    }

    /**
     * Удаляет все ошибки в шапке корзины. <br>
     * Ошибки расположены в корзине между элементом breadcrumb и cartContent.
     */
    removeErrorsFromCart() {
        const cart = this._cartTemplateBridge.getCart();
        const breadcrumb = cart.getElementsByClassName('breadcrumb')[0];
        const cartContent = cart.getElementsByClassName('row')[0];

        [...cart.children].forEach(cartElement => {
            if (cartElement === breadcrumb) {
                return;
            }

            if (cartElement === cartContent) {
                return;
            }

            cart.removeChild(cartElement);
        });
    }

    /**
     * Добавляет ошибки в шапку корзины и помечает все элементы с ошибкой.
     * @param errors
     * @param productNodes
     */
    addErrors(errors, productNodes) {
        const cart = this._cartTemplateBridge.getCart();
        const breadcrumb = cart.getElementsByClassName('breadcrumb')[0];

        if (('errorStockText' in errors) && ('errorStock' in errors)) {
            const errorElement = this._cartTemplateBridge.createError(errors.errorStockText);
            breadcrumb.after(errorElement);

            errors.errorStock.forEach(({id}) => {
                const productNode = productNodes.find(productNode => {
                    const { productTitleElement } = productNode;

                    return productTitleElement.parentNode.id === id;
                });

                const { productQuantityInput } = productNode;
                this._addStockErrorIntoInput(productQuantityInput);
            });
        }

        if (('errorPriceText' in errors) && ('errorPrice' in errors)) {
            const errorElement = this._cartTemplateBridge.createError(errors.errorPriceText);
            breadcrumb.after(errorElement);

            errors.errorPrice.forEach(({id, price}) => {

                const productNode = productNodes.find(productNode => {
                    const { productTitleElement } = productNode;

                    return productTitleElement.parentNode.id === id;
                });

                const { productPriceElement } = productNode;
                this._addPriceErrorIntoText(productPriceElement, price);

                productPriceElement.innerText = price + this.getCurrency(productPriceElement);
            });
        }
    }

    /**
     * Помечает элемент ввода количества товаров с ошибкой.
     * @param productQuantityInput
     * @private
     */
    _addStockErrorIntoInput(productQuantityInput) {
        productQuantityInput.style.color = 'red';
        productQuantityInput.style.borderColor = 'red';
    }

    /**
     * Убирает пометку с ошибкой с элемента ввода количества товаров. <br>
     * Цвета взяты с инспектора.
     * @param productQuantityInput
     */
    removeStockErrorFromInput(productQuantityInput) {
        productQuantityInput.style.color = '#555';
        productQuantityInput.style.borderColor = '#E9E9E9';
    }

    /**
     * Помечает стоимость товара с ошибкой.
     * @param productPriceElement
     * @param newPrice
     * @private
     */
    _addPriceErrorIntoText(productPriceElement, newPrice) {
        const oldPrice = this.getPrice(productPriceElement);
        productPriceElement.style.color = newPrice > oldPrice ? 'red' : 'green';
    }

    /**
     * Возвращает стоимость товара из элемента стоимости товара.
     * @param priceElement
     * @returns Number
     */
    getPrice(priceElement) {
        // must satisfy template like '#<currencyName>'
        // where # - price and <currencyName> is currency name e.g. 'руб.'
        const prettyPrice = priceElement.innerText;

        for (let i = 0; i < prettyPrice.length; i++) {
            const symbol = prettyPrice.charAt(i);

            if (symbol === '.') {
                continue;
            }

            const digit = Number.parseInt(symbol);
            // is alphabetical
            if (Number.isNaN(digit)) {
                return Number(prettyPrice.substring(0, i));
            }
        }

        throw new Error('Invalid product price.');
    }

    /**
     * Возвращает название валюты из элемента стоимости товара.
     * @param priceElement
     * @returns string
     */
    getCurrency(priceElement) {
        // must satisfy template like '#<currencyName>'
        // where # - price and <currencyName> is currency name e.g. 'руб.'
        const prettyPrice = priceElement.innerText;

        for (let i = 0; i < prettyPrice.length; i++) {
            const symbol = prettyPrice.charAt(i);

            if (symbol === '.') {
                continue;
            }

            const digit = Number.parseInt(symbol);

            if (Number.isInteger(digit)) {
                continue;
            }

            // is alphabetical
            if (Number.isNaN(digit)) {
                return prettyPrice.substring(i);
            }
        }

        throw new Error('Invalid product price.');
    }
}

class CartTemplateBridge {
    /**
     * Возвращает элемент корзины
     * @returns HTMLElement
     */
    getCart() {
        return document.getElementById('checkout-cart');
    }

    /**
     * Возвращает элемент с количеством товаров в корзине в правом верхнем углу страницы
     * @returns HTMLElement
     */
    getCartQuantityElement() {
        return document.getElementById('cart-quantity');
    }

    /**
     * Создаёт элемент ошибки
     * @param text текст ошибки
     * @returns HTMLElement
     */
    createError(text) {
        const errorContainer = document.createElement('div');
        errorContainer.innerHTML = `
            <div class="alert alert-danger alert-dismissible">
                <i class="fa fa-exclamation-circle"></i>
                ${text}
                <button type="button" class="close" data-dismiss="alert">×</button>
            </div>`;
        return errorContainer.children[0];
    }

    /**
     * Создаёт элементы пустой корзины
     * @returns HTMLElement[]
     */
    createClearCart() {
        const p = document.createElement('p');
        p.innerHTML= `Корзина пуста!`;

        const div = document.createElement('div');
        div.className = 'pull-right';
        div.innerHTML = '<a class="btn btn-primary" href="/">Продолжить</a>';

        return [p, div];
    }

    /**
     * Возвращает все HTML-элементы итоговой цены в корзине.
     * @returns HTMLElement[]
     */
    getTotalPrices() {
        return [...document.getElementsByClassName('total')];
    }

    /**
     * Возвращает все элементы полей ввода количества товаров
     * @returns HTMLElement[]
     */
    getProductsQuantityInputs() {
        const formControls = document.getElementsByClassName('form-control')
        return [...formControls].filter(formControl => {
            return formControl.name.includes('quantity[');
        });
    }
}

class ProductNode {
    /**
     * @type {HTMLElement}
     * @private
     */
    _productTitleElement = null;

    /**
     * @type {HTMLElement}
     * @private
     */
    _productQuantityInput = null;

    /**
     * @type {HTMLElement}
     * @private
     */
    _productPriceElement = null;

    /**
     * @type {HTMLElement}
     * @private
     */
    _productTotalPriceElement = null;

    constructor(productTitleElement, productQuantityInput, productPriceElement, productTotalPriceElement) {
        this._productTitleElement = productTitleElement;
        this._productQuantityInput = productQuantityInput;
        this._productPriceElement = productPriceElement;
        this._productTotalPriceElement = productTotalPriceElement;
    }

    /**
     * @returns {HTMLElement}
     */
    get productTitleElement() {
        return this._productTitleElement;
    }

    /**
     * @returns {HTMLElement}
     */
    get productQuantityInput() {
        return this._productQuantityInput;
    }

    /**
     * @returns {HTMLElement}
     */
    get productPriceElement() {
        return this._productPriceElement;
    }

    /**
     * @returns {HTMLElement}
     */
    get productTotalPriceElement() {
        return this._productTotalPriceElement;
    }
}