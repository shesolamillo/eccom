// Cart Manager para sa Symfony Database Cart
class CartManager {
    constructor() {
        this.cart = [];
        this.modal = null;
        this.currentProduct = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.updateCartUI();
    }

    setupEventListeners() {
        // Add to cart modal
        const modal = document.getElementById('addToCartModal');
        if (modal) {
            this.modal = new bootstrap.Modal(modal);
            
            // Quantity controls
            const minusBtn = document.getElementById('quantityMinus');
            const plusBtn = document.getElementById('quantityPlus');
            const qtyInput = document.getElementById('quantity');


            if (minusBtn) {
                minusBtn.onclick = () => {
                    let val = parseInt(qtyInput.value);
                    if (val > 1) {
                        qtyInput.value = val - 1;
                        this.updateTotalPrice();
                    }
                };
            }

            if (plusBtn) {
                plusBtn.onclick = () => {
                    let val = parseInt(qtyInput.value);
                    let max = this.currentProduct ? this.currentProduct.stock : 999;
                    if (val < max) {
                        qtyInput.value = val + 1;
                        this.updateTotalPrice();
                    }
                };
            }

            if (qtyInput) {
                qtyInput.onchange = () => this.updateTotalPrice();
            }
            
            // Confirm button
            document.getElementById('confirmAddToCartBtn')?.addEventListener('click', () => this.confirmAddToCart());
        }

        // Cart preview
        document.getElementById('cartPreviewBtn')?.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleCartPreview();
        });

        document.getElementById('clearCartPreviewBtn')?.addEventListener('click', () => this.clearCart());

        // Close cart preview when clicking outside
        document.addEventListener('click', (e) => {
            const preview = document.getElementById('cartPreview');
            const btn = document.getElementById('cartPreviewBtn');
            if (preview && !preview.contains(e.target) && !btn.contains(e.target)) {
                this.closeCartPreview();
            }
        });

        // All "Add to Cart" buttons
        document.querySelectorAll('[data-add-to-cart]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const productId = btn.dataset.addToCart;
                const productName = btn.dataset.productName || 'Product';
                const productPrice = parseFloat(btn.dataset.productPrice || 0);
                const productImage = btn.dataset.productImage || '';
                const stock = parseInt(btn.dataset.stock || 0);

                this.openAddToCartModal(productId, productName, productPrice, productImage, stock);
            });
        });
    }

    // openAddToCartModal(productId, productName, productPrice, productImage, stock) {
    //     this.currentProduct = {
    //         id: productId,
    //         name: productName,
    //         price: productPrice,
    //         image: productImage,
    //         stock: stock
    //     };

    //     // Populate modal
    //     document.getElementById('modalProductId').value = productId;
    //     document.getElementById('modalProductImage').src = productImage;
    //     document.getElementById('modalProductName').textContent = productName;
    //     document.getElementById('modalProductPrice').textContent = `₱${productPrice.toFixed(2)}`;
    //     document.getElementById('quantity').value = 1;
    //     document.getElementById('stockStatus').textContent = `${stock} items available`;

    //     this.resetOptionsForm();
    //     this.populateSizeOptions();
    //     this.populateColorOptions();
    //     this.updateTotalPrice();
    //     this.modal.show();
    // }


    openAddToCartModal(productId, productName, productPrice, productImage, stock) {
    this.currentProduct = { id: productId, name: productName, price: productPrice, image: productImage, stock: stock };

    // NULL SAFE setters
    const set = (id, prop, val) => { const el = document.getElementById(id); if (el) el[prop] = val; };
    
    set('modalProductId',    'value',       productId);
    set('modalProductImage', 'src',         productImage);
    set('modalProductName',  'textContent', productName);
    set('modalProductPrice', 'textContent', `₱${productPrice.toFixed(2)}`);
    set('quantity',          'value',       1);
    set('stockStatus',       'textContent', `${stock} items available`);

    this.resetOptionsForm();
    this.populateSizeOptions();
    this.populateColorOptions();
    this.updateTotalPrice();
    this.modal.show();
}


    populateSizeOptions() {
        const sizeOptions = document.getElementById('sizeOptions');
        if (!sizeOptions) return;
        
        sizeOptions.innerHTML = '';
        const sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        sizes.forEach(size => {
            const label = document.createElement('label');
            label.className = 'size-option';
            label.innerHTML = `
                <input type="radio" name="size" value="${size}" style="display: none;">
                ${size}
            `;
            label.addEventListener('click', function() {
                document.querySelectorAll('.size-option').forEach(el => el.classList.remove('active'));
                this.classList.add('active');
            });
            sizeOptions.appendChild(label);
        });
    }

    populateColorOptions() {
        const colorOptions = document.getElementById('colorOptions');
        if (!colorOptions) return;
        
        colorOptions.innerHTML = '';
        const colors = [
            { name: 'Black', hex: '#000000' },
            { name: 'White', hex: '#FFFFFF' },
            { name: 'Red', hex: '#DC3545' },
            { name: 'Blue', hex: '#0D6EFD' },
            { name: 'Green', hex: '#198754' },
            { name: 'Yellow', hex: '#FFC107' }
        ];

        colors.forEach((color, index) => {
            const wrapper = document.createElement('div');
            wrapper.style.textAlign = 'center';
            
            const input = document.createElement('input');
            input.type = 'radio';
            input.name = 'color';
            input.value = color.name;
            input.style.display = 'none';
            input.id = `color-${index}`;

            const label = document.createElement('label');
            label.className = 'color-option';
            label.htmlFor = `color-${index}`;
            label.style.backgroundColor = color.hex;
            label.style.border = color.hex === '#FFFFFF' ? '2px solid #ddd' : '2px solid transparent';
            
            label.addEventListener('click', function() {
                document.querySelectorAll('.color-option').forEach(el => el.classList.remove('active'));
                this.classList.add('active');
            });

            wrapper.appendChild(input);
            wrapper.appendChild(label);
            
            const nameLabel = document.createElement('div');
            nameLabel.className = 'color-label';
            nameLabel.textContent = color.name;
            wrapper.appendChild(nameLabel);

            colorOptions.appendChild(wrapper);
        });
    }

    resetOptionsForm() {
        document.querySelectorAll('[name="size"], [name="color"]').forEach(el => {
            el.checked = false;
        });
        document.querySelectorAll('.size-option, .color-option').forEach(el => {
            el.classList.remove('active');
        });
    }

    increaseQuantity() {
        const input = document.getElementById('quantity');
        if (!input || !this.currentProduct) return;
        
        const currentValue = parseInt(input.value);
        if (currentValue < this.currentProduct.stock) {
            input.value = currentValue + 1;
            this.updateTotalPrice();
        }
    }

    decreaseQuantity() {
        const input = document.getElementById('quantity');
        if (!input) return;
        
        const currentValue = parseInt(input.value);
        if (currentValue > 1) {
            input.value = currentValue - 1;
            this.updateTotalPrice();
        }
    }

    updateTotalPrice() {
        const quantityInput = document.getElementById('quantity');
        const totalPriceElement = document.getElementById('totalPrice');
        
        if (!quantityInput || !totalPriceElement || !this.currentProduct) {
            return;
        }
        
        const quantity = parseInt(quantityInput.value);
        const total = this.currentProduct.price * quantity;
        totalPriceElement.textContent = `₱${total.toFixed(2)}`;
    }

    confirmAddToCart() {
        const size = document.querySelector('[name="size"]:checked')?.value;
        const color = document.querySelector('[name="color"]:checked')?.value;
        const quantity = parseInt(document.getElementById('quantity')?.value || 1);

        if (!size) {
            this.showToast('Please select a size', 'warning');
            return;
        }

        if (!color) {
            this.showToast('Please select a color', 'warning');
            return;
        }

        // Send to Symfony backend
        this.addToCartBackend(this.currentProduct.id, quantity);
        this.modal.hide();
        this.showToast(`${this.currentProduct.name} added to cart!`, 'success');
    }

    addToCartBackend(productId, quantity) {
        fetch(`/cart/add/${productId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `quantity=${quantity}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateCartUI();
                this.animateAddToCart();
            } else {
                this.showToast(data.message || 'Error adding to cart', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showToast('Error adding to cart', 'error');
        });
    }

    animateAddToCart() {
        const cartBtn = document.getElementById('cartPreviewBtn');
        if (!cartBtn) return;

        cartBtn.style.animation = 'none';
        setTimeout(() => {
            cartBtn.style.animation = 'cartBounce 0.6s ease-out';
        }, 10);
    }

    updateCartUI() {
        fetch('/cart/mini')
            .then(response => response.json())
            .then(data => {
                const badge = document.getElementById('cartBadge');
                if (badge) {
                    if (data.count > 0) {
                        badge.textContent = data.count;
                        badge.style.display = 'block';
                    } else {
                        badge.style.display = 'none';
                    }
                }
                this.updateCartPreview();
            })
            .catch(error => console.error('Error:', error));
    }

    updateCartPreview() {
        fetch('/cart/api/details')
            .then(response => response.json())
            .then(data => {
                const itemsList = document.getElementById('cartItemsList');
                const summary = document.getElementById('cartSummary');
                const itemCount = document.getElementById('previewItemCount');

                if (!itemsList) return;

                if (data.items.length === 0) {
                    itemsList.innerHTML = `
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-bag-slash display-3 d-block mb-2"></i>
                            Your cart is empty
                        </div>
                    `;
                    if (summary) summary.style.display = 'none';
                    return;
                }

                itemsList.innerHTML = data.items.map(item => `
                    <div class="cart-item-preview">
                        <div class="cart-item-info">
                            <div class="cart-item-name">${item.name}</div>
                            <div class="cart-item-details">Qty: ${item.quantity}</div>
                        </div>
                        <div class="text-end">
                            <div class="cart-item-price">₱${item.subtotal.toFixed(2)}</div>
                        </div>
                    </div>
                `).join('');

                if (itemCount) itemCount.textContent = data.count;
                document.getElementById('previewSubtotal').textContent = `₱${data.subtotal.toFixed(2)}`;
                document.getElementById('previewTotal').textContent = `₱${data.total.toFixed(2)}`;
                
                if (summary) summary.style.display = 'block';
            })
            .catch(error => console.error('Error:', error));
    }

    toggleCartPreview() {
        const preview = document.getElementById('cartPreview');
        if (preview) {
            preview.style.display = preview.style.display === 'none' ? 'block' : 'none';
        }
    }

    closeCartPreview() {
        const preview = document.getElementById('cartPreview');
        if (preview) {
            preview.style.display = 'none';
        }
    }

    clearCart() {
        if (confirm('Clear entire cart?')) {
            fetch('/cart/clear', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.showToast('Cart cleared', 'info');
                    setTimeout(() => { window.location.reload(); }, 500);
                }
            })
            .catch(error => console.error('Error:', error));
        }
    }

    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; min-width: 300px; z-index: 10000; animation: slideInRight 0.3s ease-out;';
        toast.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
}

// Initialize cart manager
let cartManager;
document.addEventListener('DOMContentLoaded', () => {
    cartManager = new CartManager();
});

// CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes cartBounce {
        0% { transform: scale(1, 1); }
        50% { transform: scale(1.15, 1.15); }
        100% { transform: scale(1, 1); }
    }
    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
`;
document.head.appendChild(style);