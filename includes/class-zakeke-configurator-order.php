<?php

if ( ! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class ZakekeConfigurator_Order
{

    /**
     * Setup class.
     */
    public static function init()
    {
        add_filter('woocommerce_checkout_create_order_line_item_object', array(__CLASS__, 'create_order_line_item_object'), 20, 4);
        add_action('woocommerce_new_order_item', array(__CLASS__, 'new_order_item'), 20, 3);
        add_action('woocommerce_order_item_get_formatted_meta_data', array(__CLASS__, 'order_item_get_formatted_meta_data'), 10, 2);
        add_action('woocommerce_thankyou', array(__CLASS__, 'new_order'), 20);
        add_action('woocommerce_order_status_processing', array(__CLASS__, 'new_order'));
        add_action('woocommerce_before_order_object_save', array(__CLASS__, 'update_order'));
    }

    /**
     * Add the Zakeke data from the cart item to the order item
     *
     * @param WC_Order_Item $line_item
     * @param string $cart_item_key
     * @param array $values
     * @param WC_Order $order
     * @return WC_Order_Item
     */
    public static function create_order_line_item_object($line_item, $cart_item_key, $values, $order) {
        if (isset($values['zakeke_configurator_data'])) {
            $line_item->zakeke_configurator_data = $values['zakeke_configurator_data'];
        }

        return $line_item;
    }

    /**
     * @param int $item_id
     * @param WC_Order_Item $item
     * @param int $order_id
     * @throws Exception
     */
    public static function new_order_item($item_id, $item, $order_id)
    {
        if (isset($item->zakeke_configurator_data)) {
            wc_add_order_item_meta( $item_id, 'zakeke_configurator_data', array(
                'composition' => $item->zakeke_configurator_data['composition'],
                'preview'     => $item->zakeke_configurator_data['preview']
            ) );
        }
    }

    public static function order_item_get_formatted_meta_data( $formatted_meta, $order_item ) {
        $zakeke_data = $order_item->get_meta( 'zakeke_configurator_data' );
        if ( $zakeke_data ) {
            $webservice = new ZakekeConfigurator_Webservice();
            try {
                $info = $webservice->cart_info($zakeke_data['composition'], 1);
                foreach ($info['items'] as $item) {
                    if (strpos($item['attributeCode'], 'zakekePlatform') !== false) {
                        continue;
                    }
                    $formatted_meta[$item['itemGuid']] = (object)array(
                        'key' => $item['attributeName'],
                        'value' => $item['selectedOptionName'],
                        'display_key' => $item['attributeName'],
                        'display_value' => wpautop($item['selectedOptionName']),
                    );
                }
            } catch (Exception $e) {

            }
        }

        return $formatted_meta;
    }

    /**
     * @param WC_Order $order
     */
    public static function update_order($order)
    {
        if ($order->has_status('processing')) {
            self::new_order($order->get_id());
        }
    }

    public static function new_order($order_id)
    {
        if (get_post_meta($order_id, 'zakeke_placed_order', true)) {
            return;
        }

        $order = wc_get_order($order_id);

        $data = array(
            'orderCode'            => $order_id,
            'sessionID'            => get_current_user_id(),
            'total'                => $order->get_total(),
            'compositionDetails'   => array()
        );

        foreach ( $order->get_items( 'line_item' ) as $order_item_id => $item ) {
            $zakeke_data = $item->get_meta( 'zakeke_configurator_data' );

            if ( ! $zakeke_data ) {
                continue;
            }

            $maybe_discounted_price_without_tax = max(0, $item->get_total());

            $quantity = max(1, absint($item->get_quantity()));

            if ($maybe_discounted_price_without_tax) {
                $maybe_discounted_price_without_tax = $maybe_discounted_price_without_tax / $quantity;
            }

            $unitPrice = min($maybe_discounted_price_without_tax, $zakeke_data['original_final_excl_tax_price']);

            $item_data = array(
                'composition'     => $zakeke_data['composition'],
                'orderDetailCode' => $order_item_id,
                'quantity'        => $quantity,
                'unitPrice'       => $unitPrice
            );

            $data['compositionDetails'][] = $item_data;
        }

        if ( count( $data['compositionDetails'] ) > 0 ) {
            $webservice = new ZakekeConfigurator_Webservice();
            $webservice->place_order( $data );

            update_post_meta( $order_id, 'zakeke_placed_order', true );
        }
    }
}

ZakekeConfigurator_Order::init();
