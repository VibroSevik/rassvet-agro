window.addEventListener('load', () => {
    const cartManager = new CartManager();
    cartManager.addProductsIntoReactiveContext();
    cartManager.addProductsCheckAfterSubmitForm();
});

class CartManager {
    _cartTemplateBridge = null;

    constructor() {
        this._cartTemplateBridge = new CartTemplateBridge();
    }

    /**
     * add all products in cart into reactive context e.g. changes total price on product quantity change
     */
    addProductsIntoReactiveContext() {
        const productNodes = this._cartTemplateBridge.getProductNodes();

        productNodes.forEach(productNode => {
            const { productQuantityInput, productPriceElement, productTotalPriceElement } = productNode;

            productQuantityInput.addEventListener('input', (event) =>
                this._productQuantityInputHandler(
                    event,
                    this._cartTemplateBridge.getPrice(productPriceElement),
                    productTotalPriceElement,
                    this._cartTemplateBridge.getTotalPrices()
                )
            );
        });
    }

    /**
     * send check request for all products after form submit e.g. for checking product price
     */
    addProductsCheckAfterSubmitForm() {
        const productContainers = document.getElementsByClassName('product-container');

        const form = document.querySelector('#content > form');
        form.addEventListener('submit', (event) => {
            this._productsSubmitButtonHandler(event, [...productContainers])
        });
    }

    /**
     * handler for reactive changing product total price and total price in cart when product quantity is changed
     * @param event product quantity input event
     * @param productPrice product price
     * @param productTotalPriceElement product total price HtmlElement
     * @param totalPriceElements total price HtmlElement[]
     */
    _productQuantityInputHandler(event, productPrice, productTotalPriceElement, totalPriceElements) {
        const quantity = event.target.value;
        const currencyFormat = this._cartTemplateBridge.getCurrency(productTotalPriceElement);

        const oldProductTotalPrice = this._cartTemplateBridge.getPrice(productTotalPriceElement);
        const oldTotalPrice = this._cartTemplateBridge.getPrice(totalPriceElements[0]);

        const newProductTotalPrice = Math.round(quantity) * productPrice;
        const newTotalPrice = oldTotalPrice + (newProductTotalPrice - oldProductTotalPrice);

        productTotalPriceElement.innerText = newProductTotalPrice + currencyFormat;

        // оказывается в середине может быть стоимость доставки, ее не трогать
        totalPriceElements[0].innerText = newTotalPrice + currencyFormat;
        totalPriceElements[totalPriceElements.length - 1].innerText = newTotalPrice + currencyFormat;
    }

    _productsSubmitButtonHandler(event, productContainers) {
        const formData = new FormData();

        const requestBody = productContainers.map(productContainer => {
            return {
                'id': productContainer.id,
                'price': this._cartTemplateBridge.getPrice(productContainer.children[4])
            };
        });

        formData.append('products', JSON.stringify(requestBody));

        fetch('/cart', {
            method: 'post',
            body: formData
        });
    }
}

class CartTemplateBridge {
    /**
     * returns all info about products in cart as ProductNode array.
     * @returns ProductNode[]
     */
    getProductNodes() {
        const productQuantityInputs = this._getProductsQuantityInputs();
        return productQuantityInputs.map(quantityInput => {
            const productPriceElement = quantityInput.parentNode.parentNode.nextElementSibling;
            const productTotalPriceElement = productPriceElement.nextElementSibling;
            return new ProductNode(
                quantityInput,
                productPriceElement,
                productTotalPriceElement
            );
        });
    }

    /**
     * returns all total prices HtmlElements
     * @returns Element[]
     */
    getTotalPrices() {
        return [...document.getElementsByClassName('total')];
    }

    /**
     * returns all products quantity inputs
     * @returns Element[]
     */
    _getProductsQuantityInputs() {
        const formControls = document.getElementsByClassName('form-control')
        return [...formControls].filter(formControl => {
            return formControl.name.includes('quantity[');
        });
    }

    /**
     * @param priceElement element which has product price inside as text
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
     * @param priceElement element which has product price inside as text
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

class ProductNode {
    _productQuantityInput = null;
    _productPriceElement = null;
    _productTotalPriceElement = null;

    constructor(productQuantityInput, productPriceElement, productTotalPriceElement) {
        this._productQuantityInput = productQuantityInput;
        this._productPriceElement = productPriceElement;
        this._productTotalPriceElement = productTotalPriceElement;
    }

    get productQuantityInput() {
        return this._productQuantityInput;
    }

    get productPriceElement() {
        return this._productPriceElement;
    }

    get productTotalPriceElement() {
        return this._productTotalPriceElement;
    }
}