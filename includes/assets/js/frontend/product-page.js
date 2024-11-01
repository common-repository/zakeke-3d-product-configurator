(function () {
    'use strict';

    var zakekeProductPage = function() {
        var cart = document.querySelector('.cart');
        if (!cart) {
            return;
        }

        var cartSubmit = cart.querySelector('button[type=submit]');
        var zakekeInput = document.querySelector('input[name=zakeke_configuration]');

        var customizeElement = document.querySelector('.zakeke-configurator-customize-button');

        if (customizeElement) {
            customizeElement.addEventListener('click', function (e) {
                e.preventDefault();

                if (!cartSubmit.classList.contains('disabled')) {
                    zakekeInput.value = 'new';

                    cartSubmit.addEventListener('click', function (e) {
                        e.stopPropagation();
                    });
                }

                cartSubmit.click();
            });
        } else if (cartSubmit) {
            cartSubmit.addEventListener('click', function (e) {
                if (cartSubmit.classList.contains('disabled')) {
                    return;
                }
                zakekeInput.value = 'new';
                e.stopPropagation();
            });
        }
    };

    if (document.readyState === 'complete'
        || document.readyState === 'loaded'
        || document.readyState === 'interactive') {
        zakekeProductPage();
    } else {
        document.addEventListener('DOMContentLoaded', zakekeProductPage);
    }
})();
