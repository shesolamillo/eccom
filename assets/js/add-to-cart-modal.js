// Add to Cart Modal functionality
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('addToCartModal');
    const quantityInput = document.getElementById('quantity');
    const quantityPlus = document.getElementById('quantityPlus');
    const quantityMinus = document.getElementById('quantityMinus');
    const confirmBtn = document.getElementById('confirmAddToCartBtn');
    const totalPriceDisplay = document.getElementById('totalPrice');

    if (!modal) return;

    let currentProductPrice = 0;

    // Handle quantity plus button
    if (quantityPlus) {
        quantityPlus.addEventListener('click', function() {
            if (quantityInput) {
                const maxStock = parseInt(quantityInput.getAttribute('data-max-stock')) || 999;
                const currentValue = parseInt(quantityInput.value) || 1;
                if (currentValue < maxStock) {
                    quantityInput.value = currentValue + 1;
                    updateTotalPrice();
                }
            }
        });
    }

    // Handle quantity minus button
    if (quantityMinus) {
        quantityMinus.addEventListener('click', function() {
            if (quantityInput) {
                const currentValue = parseInt(quantityInput.value) || 1;
                if (currentValue > 1) {
                    quantityInput.value = currentValue - 1;
                    updateTotalPrice();
                }
            }
        });
    }

    // Handle quantity input change
    if (quantityInput) {
        quantityInput.addEventListener('change', updateTotalPrice);
    }

    // Update total price based on quantity
    function updateTotalPrice() {
        if (quantityInput && totalPriceDisplay) {
            const quantity = parseInt(quantityInput.value) || 1;
            const total = currentProductPrice * quantity;
            totalPriceDisplay.textContent = '₱' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    }

    // Handle modal open - populate product data
    if (modal) {
        modal.addEventListener('show.bs.modal', function(e) {
            const button = e.relatedTarget;
            if (button) {
                const productId = button.getAttribute('data-product-id');
                const productName = button.getAttribute('data-product-name');
                const productPrice = parseFloat(button.getAttribute('data-product-price'));
                const productImage = button.getAttribute('data-product-image');
                const productStock = parseInt(button.getAttribute('data-product-stock')) || 0;

                // Set modal data
                document.getElementById('modalProductId').value = productId;
                document.getElementById('modalProductName').textContent = productName;
                document.getElementById('modalProductPrice').textContent = '₱' + productPrice.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                document.getElementById('modalProductImage').src = productImage;
                
                // Set quantity input
                if (quantityInput) {
                    quantityInput.value = 1;
                    quantityInput.setAttribute('data-max-stock', productStock);
                    quantityInput.setAttribute('max', productStock);
                }

                // Update stock status
                const stockStatus = document.getElementById('stockStatus');
                if (stockStatus) {
                    if (productStock <= 0) {
                        stockStatus.textContent = '❌ Out of stock';
                        stockStatus.style.color = '#dc3545';
                    } else if (productStock < 5) {
                        stockStatus.textContent = `⚠️ Only ${productStock} left`;
                        stockStatus.style.color = '#ffc107';
                    } else {
                        stockStatus.textContent = `✓ ${productStock} in stock`;
                        stockStatus.style.color = '#198754';
                    }
                }

                currentProductPrice = productPrice;
                updateTotalPrice();
            }
        });
    }

    // Handle confirm add to cart
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            const productId = document.getElementById('modalProductId').value;
            const quantity = parseInt(quantityInput.value) || 1;

            if (!productId) {
                alert('Product not selected');
                return;
            }

            // Disable button during submission
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Adding...';

            // Create and submit form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/cart/add/${productId}`;
            form.innerHTML = `<input type="hidden" name="quantity" value="${quantity}">`;
            document.body.appendChild(form);
            
            // Show success feedback
            setTimeout(function() {
                form.submit();
            }, 300);
        });
    }
});
