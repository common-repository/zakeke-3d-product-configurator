function zakekeDesigner(config) {
    if (!config) {
        return;
    }

    function emitProductDataEvent(productData) {
        iframe.contentWindow.postMessage(productData, '*');
    }

    function isWCAttribute(attribute) {
        try {
            return JSON.parse(attribute.attributeCode).zakekePlatform && JSON.parse(attribute.optionCode).zakekePlatform;
        } catch (e) {
            return false;
        }
    }

    function toWCAttribute(attribute) {
        return {[JSON.parse(attribute.attributeCode).id]: JSON.parse(attribute.optionCode).id};
    }

    function toZakekeAttribute(attribute, option) {
        return [
            {
                id: attribute,
                isGlobal: true,
                zakekePlatform: true
            },
            {
                id: option,
                zakekePlatform: true
            }
        ];
    }

    function updatedAttributes(attributes) {
        var wcAttributes = attributes.filter(isWCAttribute).reduce(function (acc, attribute) {
            return Object.assign(acc, toWCAttribute(attribute));
        }, {});

        return Object.assign(config.attributes, wcAttributes);
    }

    function asAddToCartAttributes(attributes) {
        return Object.keys(attributes).reduce(function (acc, attribute) {
            acc['attribute_' + attribute] = attributes[attribute];
            return acc;
        }, {});
    }

    function productData(messageId, attributes, compositionPrice, quantity) {
        var params = Object.assign({
            'product_id': config.modelCode,
            'zakeke_price': compositionPrice
        }, config.request, asAddToCartAttributes(updatedAttributes(attributes)));

        var queryString = jQuery.param(params),
            cached = productDataCache[queryString];

        if (cached !== undefined) {
            emitProductDataEvent(Object.assign(cached, {
                messageId: messageId
            }));
            return;
        }

        if (pendingProductDataRequests.indexOf(queryString) !== -1) {
            return;
        }

        pendingProductDataRequests.push(queryString);

        jQuery.ajax({
            url: config.priceAjaxUrl,
            type: 'POST',
            data: params
        })
            .done(function (product) {
                var productData = {
                    messageId: messageId,
                    zakekeMessageType: "Price",
                    message: product.price_including_tax
                };
                productDataCache[queryString] = productData;
                emitProductDataEvent(productData);
            })
            .fail(function (request, status, error) {
                console.error(request + ' ' + status + ' ' + error);
            })
            .always(function () {
                var index = pendingProductDataRequests.indexOf(queryString);
                if (index !== -1) {
                    pendingProductDataRequests.splice(index, 1);
                }
            });
    }

    var productDataCache = {},
        pendingProductDataRequests = [],
        container = document.getElementById('zakeke-configurator-container'),
        iframe = container.querySelector('iframe'),
        sendIframeParamsInterval = null,
        createCartSubInput = function (form, value, key, prevKey) {
            if (value instanceof String || typeof(value) !== 'object') {
                createCartInput(form, prevKey ? prevKey + '[' + key + ']' : key, value);
            } else {
                Object.keys(value).forEach(function (subKey) {
                    createCartSubInput(form, value[subKey], subKey, prevKey ? prevKey + '[' + key + ']' : key);
                });
            }
        },
        createCartInput = function (form, key, value) {
            var input = document.createElement('INPUT');
            input.type = 'hidden';
            input.name = key;
            input.value = value.toString().replace(/\\/g, '');
            form.appendChild(input);
        },
        addToCart = function (composition, attributes, preview, quantity) {
            var params = Object.assign({
                    'add-to-cart': config.modelCode,
                    'product_id': config.modelCode
                },
                config.request,
                asAddToCartAttributes(updatedAttributes(attributes)),
                {
                    'quantity': quantity,
                    'zakeke_configuration': composition,
                });

            var form = document.createElement('FORM');
            form.style.display = 'none';
            form.method = 'POST';

            delete params['variation_id'];
            Object.keys(params).filter(function (x) {
                return params[x] != null;
            }).forEach(function (key) {
                createCartSubInput(form, params[key], key);
            });
            document.body.appendChild(form);
            jQuery(form).submit();
        };

    window.addEventListener('message', function (event) {
        if (event.origin !== config.zakekeUrl) {
            return;
        }

        if (event.data.zakekeMessageType === 'AddToCart') {
            addToCart(event.data.message.composition, event.data.message.attributes, event.data.message.preview, event.data.message.quantity);
        } else if (event.data.zakekeMessageType === 'Price') {
            productData(event.data.messageId, event.data.message.attributes, event.data.message.compositionPrice, event.data.message.quantity);
        }
    }, false);

    jQuery.ajax({
        url: config.authAjaxUrl,
        type: 'POST'
    })
        .done(function (data) {
            if (data.error) {
                console.error(data.error);
                return;
            }

            sendIframeParamsInterval = setInterval(function () {
                iframe.contentWindow.postMessage({
                    type: 'load',
                    parameters: Object.assign({}, data, config, {
                        attributes: Object.keys(config.attributes).map(function (attribute) {
                            return toZakekeAttribute(attribute, config.attributes[attribute]);
                        })
                    })
                }, '*');
            }, 500);
        })
        .fail(function (request, status, error) {
            console.log(request + ' ' + status + ' ' + error);
        });
}

if (document.readyState === 'complete'
    || document.readyState === 'loaded'
    || document.readyState === 'interactive') {
    zakekeDesigner(window.zakekeConfiguratorConfig);
} else {
    document.addEventListener('DOMContentLoaded', function () {
        zakekeDesigner(window.zakekeConfiguratorConfig);
    });
}