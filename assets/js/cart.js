// Cart functionality
document.addEventListener('DOMContentLoaded', function() {
    
    // ==================== CSRF Token Helper ====================
    function getCsrfTokenFromForm() {
        const form = document.querySelector('[name="_token"]');
        return form ? form.value : '';
    }

    // ==================== QUANTITY CONTROLS ====================
    function setupQuantityControls() {
        // Quantity increment buttons
        document.querySelectorAll('.btn-increment').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const productId = this.dataset.productId;
                const inputSelector = `.qty-input[data-product-id="${productId}"]`;
                const input = document.querySelector(inputSelector);
                
                if (input) {
                    const currentValue = parseInt(input.value) || 1;
                    updateCartQuantity(productId, currentValue + 1);
                }
            });
        });

        // Quantity decrement buttons
        document.querySelectorAll('.btn-decrement').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const productId = this.dataset.productId;
                const inputSelector = `.qty-input[data-product-id="${productId}"]`;
                const input = document.querySelector(inputSelector);
                
                if (input) {
                    const currentValue = parseInt(input.value) || 1;
                    if (currentValue > 1) {
                        updateCartQuantity(productId, currentValue - 1);
                    }
                }
            });
        });
    }

    // ==================== UPDATE QUANTITY ====================
    function updateCartQuantity(productId, quantity) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/cart/update/${productId}`;
        form.innerHTML = `<input type="hidden" name="quantity" value="${quantity}">`;
        document.body.appendChild(form);
        form.submit();
    }

    // ==================== REMOVE ITEM ====================
    function setupRemoveButtons() {
        document.querySelectorAll('.btn-remove').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                if (confirm('Remove this item from cart?')) {
                    const productId = this.dataset.productId;
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/cart/remove/${productId}`;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    }

    // ==================== CLEAR CART ====================
    function setupClearCart() {
        const clearBtn = document.getElementById('cancelCartBtn');
        if (clearBtn) {
            clearBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (confirm('Clear entire cart?')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '/cart/clear';
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    }

    // ==================== COUPON HANDLING ====================
    function setupCoupon() {
        const applyCouponBtn = document.getElementById('applyCouponBtn');
        if (applyCouponBtn) {
            applyCouponBtn.addEventListener('click', function() {
                const couponInput = document.getElementById('couponInput');
                const couponCode = couponInput ? couponInput.value.trim() : '';
                
                if (!couponCode) {
                    alert('Please enter a coupon code');
                    return;
                }

                // Here you would typically make an AJAX call to apply the coupon
                console.log('Applying coupon:', couponCode);
                // For now, just show a message
                alert('Coupon "' + couponCode + '" applied!');
            });
        }
    }

    // ==================== CHECKOUT STEPS ====================
    let currentCheckoutStep = 1;
    const TOTAL_STEPS = 4;

    function updateCheckoutStep(stepNumber) {
        if (stepNumber < 1 || stepNumber > TOTAL_STEPS) return;

        // Update step circles
        for (let i = 1; i <= TOTAL_STEPS; i++) {
            const circle = document.getElementById(`stepCircle${i}`);
            const connector = document.getElementById(`connector${i}`);
            
            if (circle) {
                if (i < stepNumber) {
                    // Completed steps
                    circle.classList.add('completed');
                    circle.classList.remove('active');
                    const checkIcon = circle.querySelector('.step-check');
                    if (checkIcon) {
                        checkIcon.style.display = 'inline';
                        circle.querySelector('.step-number').style.display = 'none';
                    }
                } else if (i === stepNumber) {
                    // Current step
                    circle.classList.add('active');
                    circle.classList.remove('completed');
                    const checkIcon = circle.querySelector('.step-check');
                    if (checkIcon) {
                        checkIcon.style.display = 'none';
                    }
                    circle.querySelector('.step-number').style.display = 'inline';
                } else {
                    // Future steps
                    circle.classList.remove('active', 'completed');
                    const checkIcon = circle.querySelector('.step-check');
                    if (checkIcon) {
                        checkIcon.style.display = 'none';
                    }
                }
            }

            if (connector && i < stepNumber) {
                connector.classList.add('completed');
            } else if (connector) {
                connector.classList.remove('completed');
            }
        }
    }

    // ==================== PROCEED TO CHECKOUT ====================
    function setupCheckout() {
        const proceedBtn = document.getElementById('proceedToCheckout');
        if (proceedBtn) {
            proceedBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (currentCheckoutStep < TOTAL_STEPS) {
                    currentCheckoutStep++;
                    updateCheckoutStep(currentCheckoutStep);
                    updateCheckoutButtonText();
                } else {
                    // Place order
                    const form = document.getElementById('checkoutForm');
                    if (form) {
                        form.submit();
                    }
                }
            });
        }
    }

    function updateCheckoutButtonText() {
        const btnText = document.getElementById('checkoutBtnText');
        const btnIcon = document.querySelector('#proceedToCheckout i');
        
        const buttonTexts = {
            1: { text: 'Continue to Shipping', icon: 'bi-arrow-right-circle' },
            2: { text: 'Continue to Payment', icon: 'bi-arrow-right-circle' },
            3: { text: 'Continue to Confirm', icon: 'bi-arrow-right-circle' },
            4: { text: 'Place Order', icon: 'bi-bag-check' }
        };

        if (btnText && buttonTexts[currentCheckoutStep]) {
            btnText.textContent = buttonTexts[currentCheckoutStep].text;
        }
        
        if (btnIcon && buttonTexts[currentCheckoutStep]) {
            btnIcon.className = `bi ${buttonTexts[currentCheckoutStep].icon} me-2`;
        }
    }

    // ==================== ADD TO CART MODAL ====================
    function setupAddToCartModal() {
        const modal = document.getElementById('addToCartModal');
        if (!modal) return;

        const confirmBtn = document.getElementById('confirmAddToCartBtn');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function() {
                const productId = document.getElementById('modalProductId').value;
                const quantity = parseInt(document.getElementById('quantity').value) || 1;
                
                if (!productId) {
                    alert('Product not selected');
                    return;
                }

                // Create and submit form
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/cart/add/${productId}`;
                form.innerHTML = `<input type="hidden" name="quantity" value="${quantity}">`;
                document.body.appendChild(form);
                form.submit();
            });
        }

        // Quantity controls in modal
        const quantityInput = document.getElementById('quantity');
        const quantityPlus = document.getElementById('quantityPlus');
        const quantityMinus = document.getElementById('quantityMinus');

        if (quantityPlus) {
            quantityPlus.addEventListener('click', function() {
                if (quantityInput) {
                    quantityInput.value = Math.max(1, parseInt(quantityInput.value || 1) + 1);
                }
            });
        }

        if (quantityMinus) {
            quantityMinus.addEventListener('click', function() {
                if (quantityInput) {
                    quantityInput.value = Math.max(1, parseInt(quantityInput.value || 1) - 1);
                }
            });
        }
    }

    // ==================== ANIMATIONS ====================
    function setupAnimations() {
        // Fade in cart items
        const cartItems = document.querySelectorAll('.cart-item');
        cartItems.forEach((item, index) => {
            item.style.opacity = '0';
            item.style.animation = `fadeInUp 0.5s ease-out ${index * 0.1}s forwards`;
        });
    }

    // ==================== INITIALIZE ALL ====================
    setupQuantityControls();
    setupRemoveButtons();
    setupClearCart();
    setupCoupon();
    setupCheckout();
    setupAddToCartModal();
    setupAnimations();
    
    // Initialize checkout step
    updateCheckoutStep(currentCheckoutStep);
    updateCheckoutButtonText();
});
