<?php
/* Template Name: Product Page */
get_header();

// Debugging: Check if the template is being loaded
echo '<h1>Product Page Loaded</h1>';
?>

<div class="pricing-container">
    <h1>Select Your Product</h1>
    <div class="pricing-options">
        <div class="product-option">
            <h2>Variable Product</h2>
            <p>Price: $xx.xx</p>
            <button class="add-to-cart" data-product-id="1">Buy Now</button>
        </div>
        <!-- Repeat for other products -->
    </div>
</div>

<?php
get_footer();
?>
