<?php
// Shortcode to display product price
function custom_product_price_shortcode($atts) {
    $atts = shortcode_atts(['id' => ''], $atts);
    return wc_price(get_post_meta($atts['id'], '_price', true));
}
add_shortcode('product_price', 'custom_product_price_shortcode');

// Shortcode to display product name
function custom_product_name_shortcode($atts) {
    $atts = shortcode_atts(['id' => ''], $atts);
    $product = wc_get_product($atts['id']);
    return $product ? $product->get_name() : 'Product not found';
}
add_shortcode('product_name', 'custom_product_name_shortcode');
?>
