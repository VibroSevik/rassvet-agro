


/* ----------------------------------------------------------------------------
 OpenCart part
 ---------------------------------------------------------------------------- */

function getURLVar(key) {
    var value = [];

    var query = String(document.location).split('?');

    if (query[1]) {
        var part = query[1].split('&');

        for (i = 0; i < part.length; i++) {
            var data = part[i].split('=');

            if (data[0] && data[1]) {
                value[data[0]] = data[1];
            }
        }

        if (value[key]) {
            return value[key];
        } else {
            return '';
        }
    }
}

$(document).ready(function() {
    // Highlight any found errors
    $('.text-danger').each(function() {
        var element = $(this).parent().parent();

        if (element.hasClass('form-group')) {
            element.addClass('has-error');
        }
    });

    // Currency
    $('#form-currency .currency-select').on('click', function(e) {
        e.preventDefault();

        $('#form-currency input[name=\'code\']').val($(this).attr('name'));

        $('#form-currency').submit();
    });

    // Language
    $('#form-language .top-dropdown__btn').on('click', function(e) {
        e.preventDefault();

        $('#form-language input[name=\'code\']').val($(this).attr('name'));

        $('#form-language').submit();
    });

    /* Search */
    $(document).on('click', '#search-btn', function() {
        var url = $('base').attr('href') + 'index.php?route=product/search';

        var value = $('header #search input[name=\'search\']').val();

        if (value) {
            url += '&search=' + encodeURIComponent(value);
        }

        var category = $('header #search [name=\'category_id\']').val();

        if (category) {
            url += '&category_id=' + encodeURIComponent(category);
        }

        location = url;
    });

    $('#search input[name=\'search\']').on('keydown', function(e) {
        if (e.keyCode == 13) {
            $('header #search input[name=\'search\']').parent().find('button').trigger('click');
        }
    });

    // Menu
    $('#menu-categories .dropdown-menu').each(function() {
        var menu = $('#menu-categories').offset();
        var dropdown = $(this).parent().offset();

        var i = (dropdown.left + $(this).outerWidth()) - (menu.left + $('#menu-categories').outerWidth());

        if (i > 0) {
            $(this).css('margin-left', '-' + (i + 10) + 'px');
        }
    });

    // Product List
    $('#list-view').click(function() {
        $('#content .product-grid > .clearfix').remove();

        //$('#content .row > .product-grid').attr('class', 'product-layout product-list col-xs-12');
        $('#content .product-grid').addClass('product-list');
        $('#content .product-grid').removeClass('product-grid');
        $('#grid-view').removeClass('active');
        $('#list-view').addClass('active');

        localStorage.setItem('display', 'list');
    });

    // Product Grid
    $('#grid-view').click(function() {
        // What a shame bootstrap does not take into account dynamically loaded columns
        var cols = $('#column-right, #column-left').length;

        if (cols == 2) {
            //$('#content .product-list').attr('class', 'product-layout product-grid col-lg-6 col-md-6 col-sm-12 col-xs-12');
            $('#content .product-list').addClass('product-grid');
            $('#content .product-list').removeClass('product-list');
        } else if (cols == 1) {
            //$('#content .product-list').attr('class', 'product-layout product-grid col-lg-4 col-md-4 col-sm-4 col-xs-6');
            $('#content .product-list').addClass('product-grid');
            $('#content .product-list').removeClass('product-list');
        } else {
            //$('#content .product-list').attr('class', 'product-layout product-grid col-lg-3 col-md-3 col-sm-4 col-xs-6');
            $('#content .product-list').addClass('product-grid');
            $('#content .product-list').removeClass('product-list');
        }

        $('#list-view').removeClass('active');
        $('#grid-view').addClass('active');

        localStorage.setItem('display', 'grid');
    });

    if (localStorage.getItem('display') == 'list') {
        $('#list-view').trigger('click');
        $('#list-view').addClass('active');
    } else {
        $('#grid-view').trigger('click');
        $('#grid-view').addClass('active');
    }

    // Checkout
    $(document).on('keydown', '#collapse-checkout-option input[name=\'email\'], #collapse-checkout-option input[name=\'password\']', function(e) {
        if (e.keyCode == 13) {
            $('#collapse-checkout-option #button-login').trigger('click');
        }
    });

    // tooltips on hover
    $('[data-toggle=\'tooltip\']').tooltip({container: 'body'});

    // Makes tooltips work on ajax generated content
    $(document).ajaxStop(function() {
        $('[data-toggle=\'tooltip\']').tooltip({container: 'body'});
    });
});

// Cart add remove functions
var cart = {
    'add': function(product_id, quantity) {
        $.ajax({
            url: 'index.php?route=checkout/cart/add',
            type: 'post',
            data: 'product_id=' + product_id + '&quantity=' + (typeof(quantity) != 'undefined' ? quantity : 1),
            dataType: 'json',
            beforeSend: function() {
                $('#cart > button').button('loading');
            },
            complete: function() {
                $('#cart > button').button('reset');
            },
            success: function(json) {
                $('.alert-dismissible, .text-danger').remove();

                if (json['redirect']) {
                    location = json['redirect'];
                }

                if (json['success']) {
                    $('#report-modal .modal-title').html(json['nice_text_modal_add_to_cart_title']);

                    $('#report-modal .modal-body').html('<div class="alert alert-success"><i class="fa fa-info-circle"></i>&nbsp;&nbsp;' + json['success'] + '</div>');

                    $('#report-modal .modal-footer').html('<button type="button" class="btn modal_btn-close margin-r-space-1" data-dismiss="modal">'+json['nice_text_modal_button_to_continue']+'</button>\n\
					<a href="' + json['button_to_cart_link'] + '" class="btn btn-primary modal_btn-to-cart">'+json['nice_text_modal_button_to_cart']+'</a>');

                    $('#report-modal').modal('show');

                    // Need to set timeout otherwise it wont update the total
                    setTimeout(function () {
                        $('#cart > button').html('<div class="cart-quantity-wrapper"><i class="fa fa-shopping-bag cart-icon"></i><span id="cart-quantity">' + json['quantity'] + '</div></span><div class="cart-total-wrapper"><span id="cart-total" class="hidden-xs hidden-sm">' + json['total'] + '</span></div>');
                    }, 100);

                    $('#cart > ul').load('index.php?route=common/cart/info ul li');
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    },
    'update': function(key, quantity) {
        $.ajax({
            url: 'index.php?route=checkout/cart/edit',
            type: 'post',
            data: 'key=' + key + '&quantity=' + (typeof(quantity) != 'undefined' ? quantity : 1),
            dataType: 'json',
            beforeSend: function() {
                $('#cart > button').button('loading');
            },
            complete: function() {
                $('#cart > button').button('reset');
            },
            success: function(json) {
                // Need to set timeout otherwise it wont update the total
                setTimeout(function () {
                    $('#cart > button').html('<div class="cart-quantity-wrapper"><i class="fa fa-shopping-bag cart-icon"></i><span id="cart-quantity">' + json['quantity'] + '</div></span><div class="cart-total-wrapper"><span id="cart-total" class="hidden-xs hidden-sm">' + json['total'] + '</span></div>');
                }, 100);

                if (getURLVar('route') == 'checkout/cart' || getURLVar('route') == 'checkout/checkout') {
                    location = 'index.php?route=checkout/cart';
                } else {
                    $('#cart > ul').load('index.php?route=common/cart/info ul li');
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    },
    'remove': function(key) {
        $.ajax({
            url: 'index.php?route=checkout/cart/remove',
            type: 'post',
            data: 'key=' + key,
            dataType: 'json',
            beforeSend: function() {
                $('#cart > button').button('loading');
            },
            complete: function() {
                $('#cart > button').button('reset');
            },
            success: function(json) {
                // Need to set timeout otherwise it wont update the total
                setTimeout(function () {
                    $('#cart > button').html('<div class="cart-quantity-wrapper"><i class="fa fa-shopping-bag cart-icon"></i><span id="cart-quantity">' + json['quantity'] + '</div></span><div class="cart-total-wrapper"><span id="cart-total" class="hidden-xs hidden-sm">' + json['total'] + '</span></div>');
                }, 100);

                if (getURLVar('route') == 'checkout/cart' || getURLVar('route') == 'checkout/checkout') {
                    location = 'index.php?route=checkout/cart';
                } else {
                    $('#cart > ul').load('index.php?route=common/cart/info ul li');
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    }
}

var voucher = {
    'add': function() {

    },
    'remove': function(key) {
        $.ajax({
            url: 'index.php?route=checkout/cart/remove',
            type: 'post',
            data: 'key=' + key,
            dataType: 'json',
            beforeSend: function() {
                $('#cart > button').button('loading');
            },
            complete: function() {
                $('#cart > button').button('reset');
            },
            success: function(json) {
                // Need to set timeout otherwise it wont update the total
                setTimeout(function () {
                    $('#cart > button').html('<div class="cart-quantity-wrapper"><i class="fa fa-shopping-bag cart-icon"></i><span id="cart-quantity">' + json['quantity'] + '</div></span><div class="cart-total-wrapper"><span id="cart-total" class="hidden-xs hidden-sm">' + json['total'] + '</span></div>');
                }, 100);

                if (getURLVar('route') == 'checkout/cart' || getURLVar('route') == 'checkout/checkout') {
                    location = 'index.php?route=checkout/cart';
                } else {
                    $('#cart > ul').load('index.php?route=common/cart/info ul li');
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    }
}

var wishlist = {
    'add': function(product_id) {
        $.ajax({
            url: 'index.php?route=account/wishlist/add',
            type: 'post',
            data: 'product_id=' + product_id,
            dataType: 'json',
            success: function(json) {
                $('.alert-dismissible').remove();

                if (json['redirect']) {
                    location = json['redirect'];
                }

                if (json['success']) {
                    $('#report-modal .modal-title').html(json['nice_text_modal_wishlist_title']);

                    $('#report-modal .modal-body').html('<div class="alert ' + (json['is_logged'] ? 'alert-success' : 'alert-warning') + '"><i class="fa fa-info-circle"></i>&nbsp;&nbsp;' + json['success'] + '</div>');

                    $('#report-modal .modal-footer').html('');
                    $('#report-modal .modal-footer').html('<a href="' + json['wishlist_create_account_link'] + '" role="button" class="btn btn-accent">'+json['nice_text_modal_button_to_create_account'] + '</a>');

                    $('#report-modal').modal('show');
                }

                $('#wishlist-total span').html(json['total']);
                $('#wishlist-total').attr('title', json['total']);

            },
            error: function(xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    },
    'remove': function() {

    }
}

var compare = {
    'add': function(product_id) {
        $.ajax({
            url: 'index.php?route=product/compare/add',
            type: 'post',
            data: 'product_id=' + product_id,
            dataType: 'json',
            success: function(json) {
                $('.alert-dismissible').remove();

                if (json['success']) {
                    $('#report-modal .modal-title').html(json['nice_text_modal_compare_title']);

                    $('#report-modal .modal-body').html('<div class="alert alert-success"><i class="fa fa-info-circle"></i>&nbsp;&nbsp;' + json['success'] + '</div>');

                    $('#report-modal .modal-footer').html('<button type="button" class="btn modal_btn-close" data-dismiss="modal">'+json['nice_text_modal_button_to_continue']+'</button>');

                    $('#report-modal').modal('show');

                    $('#compare-total').html(json['total']);

                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    },
    'remove': function() {

    }
}

/* Agree to Terms */
$(document).delegate('.agree', 'click', function(e) {
    e.preventDefault();

    $('#modal-agree').remove();

    var element = this;

    $.ajax({
        url: $(element).attr('href'),
        type: 'get',
        dataType: 'html',
        success: function(data) {
            const modalTitle = $(element).attr('data-title');

            html  = '<div id="modal-agree" class="modal">';
            html += '  <div class="modal-dialog">';
            html += '    <div class="modal-content">';
            html += '      <div class="modal-header">';
            html += '        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>';
            html += '        <h4 class="modal-title">' + modalTitle + '</h4>';
            html += '      </div>';
            html += '      <div class="modal-body">' + data + '</div>';
            html += '    </div>';
            html += '  </div>';
            html += '</div>';

            $('body').append(html);

            $('#modal-agree').modal('show');
        }
    });
});

// Autocomplete */
(function($) {
    $.fn.autocomplete = function(option) {
        return this.each(function() {
            this.timer = null;
            this.items = new Array();

            $.extend(this, option);

            $(this).attr('autocomplete', 'off');

            // Focus
            $(this).on('focus', function() {
                this.request();
            });

            // Blur
            $(this).on('blur', function() {
                setTimeout(function(object) {
                    object.hide();
                }, 200, this);
            });

            // Keydown
            $(this).on('keydown', function(event) {
                switch(event.keyCode) {
                    case 27: // escape
                        this.hide();
                        break;
                    default:
                        this.request();
                        break;
                }
            });

            // Click
            this.click = function(event) {
                event.preventDefault();

                value = $(event.target).parent().attr('data-value');

                if (value && this.items[value]) {
                    this.select(this.items[value]);
                }
            }

            // Show
            this.show = function() {
                var pos = $(this).position();

                $(this).siblings('ul.dropdown-menu').css({
                    top: pos.top + $(this).outerHeight(),
                    left: pos.left
                });

                $(this).siblings('ul.dropdown-menu').show();
            }

            // Hide
            this.hide = function() {
                $(this).siblings('ul.dropdown-menu').hide();
            }

            // Request
            this.request = function() {
                clearTimeout(this.timer);

                this.timer = setTimeout(function(object) {
                    object.source($(object).val(), $.proxy(object.response, object));
                }, 200, this);
            }

            // Response
            this.response = function(json) {
                html = '';

                if (json.length) {
                    for (i = 0; i < json.length; i++) {
                        this.items[json[i]['value']] = json[i];
                    }

                    for (i = 0; i < json.length; i++) {
                        if (!json[i]['category']) {
                            html += '<li data-value="' + json[i]['value'] + '"><a href="#">' + json[i]['label'] + '</a></li>';
                        }
                    }

                    // Get all the ones with a categories
                    var category = new Array();

                    for (i = 0; i < json.length; i++) {
                        if (json[i]['category']) {
                            if (!category[json[i]['category']]) {
                                category[json[i]['category']] = new Array();
                                category[json[i]['category']]['name'] = json[i]['category'];
                                category[json[i]['category']]['item'] = new Array();
                            }

                            category[json[i]['category']]['item'].push(json[i]);
                        }
                    }

                    for (i in category) {
                        html += '<li class="dropdown-header">' + category[i]['name'] + '</li>';

                        for (j = 0; j < category[i]['item'].length; j++) {
                            html += '<li data-value="' + category[i]['item'][j]['value'] + '"><a href="#">&nbsp;&nbsp;&nbsp;' + category[i]['item'][j]['label'] + '</a></li>';
                        }
                    }
                }

                if (html) {
                    this.show();
                } else {
                    this.hide();
                }

                $(this).siblings('ul.dropdown-menu').html(html);
            }

            $(this).after('<ul class="dropdown-menu"></ul>');
            $(this).siblings('ul.dropdown-menu').delegate('a', 'click', $.proxy(this.click, this));

        });
    }
})(window.jQuery);




/* ----------------------------------------------------------------------------
 Nice Theme Part
 ---------------------------------------------------------------------------- */


/* Menu
 ---------------------------------------------------------------------------- */
document.addEventListener('DOMContentLoaded', () => {
    const mainMenuToggle = document.querySelector('.main-menu__toggle');
    const mainMenuWrapper = document.querySelector('.main-menu__wrapper');
    const overlay = document.querySelector('.main-menu__overlay');


    // Open
    function handleMainMenuToggle(e) {
        // We have animation - so it is necessary to make initial state needed and then make effects
        document.body.style.overflow = 'hidden';
        overlay.style.display = 'block';
        mainMenuWrapper.style.display = 'block';

        setTimeout(() => {
            overlay.style.opacity = '1';
            mainMenuWrapper.style.opacity = '1';
            mainMenuWrapper.classList.add('active');
        }, 100);
    }

    mainMenuToggle.addEventListener('click', handleMainMenuToggle);


    // Close
    const closeButtons = document.querySelectorAll('.main-menu__close');

    closeButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();

            // We have animation - so it is necessary to make effects and then reset styles completely
            document.body.style.overflow = 'auto';
            overlay.style.opacity = '0';
            mainMenuWrapper.style.opacity = '0';

            setTimeout(() => {
                overlay.style.display = 'none';
                mainMenuWrapper.style.display = 'none';
                mainMenuWrapper.classList.remove('active');
            }, 200);

            // Close opened chilren container
            const activeSlidingElements = mainMenuWrapper.querySelectorAll('.sliding');

            activeSlidingElements.forEach(element => {
                element.classList.remove('sliding');
            });

        });
    });


    // Open children
    const mediaQuery = window.matchMedia('(max-width: 767px)');

    function handleScreenSizeChange(mediaQuery) {
        if (mediaQuery.matches) {
            mainMenuToggle.addEventListener('click', handleMainMenuClickOnMobile);
        }
    }

    handleScreenSizeChange(mediaQuery); // init on load
    mediaQuery.addListener(handleScreenSizeChange); // init on resize

    function handleMainMenuClickOnMobile() {
        const menuItems = document.querySelectorAll('.main-menu__item.-has-children');

        menuItems.forEach(item => {
            const link = item.querySelector('a');

            if (!link.hasEventListener) {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    item.classList.toggle('active');
                    item.classList.toggle('sliding');
                });

                link.hasEventListener = true;
            }
        });
    }


    // Back to parent
    const backButtons = document.querySelectorAll('.main-menu__back');

    backButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            button.parentElement.parentElement.previousElementSibling.click();
        });
    });


});


// todo...
// Менять distance при нажатии на табы...
// ААА!
// При этом надо идентифицировать, что это касается только страница товара!!!!
// А то валит ошибки в консоль


//$(window).scroll(function () {
//  if ($(this).scrollTop() > 265) {
//   $('.product-images').css({'position': 'fixed', 'top': '20px', 'z-index':'999'});
//  } else {
//   $('.product-images').attr('style','');
//  }
//
//  var distance = document.getElementById('product-row-2').getBoundingClientRect();
//
//  var widthInitial = $('.product-images').width();
//
//  if ($(this).scrollTop() > 265) {
//    $('.product-images').css({'position': 'fixed', 'top': '20px', 'z-index':'999', 'width': widthInitial});
//  } else {
//    $('.product-images').attr('style','');
//  }
//
//  if ($(this).scrollTop() > distance.top + 200) {
//    $('.product-images').attr('style','');
//  }
// });
