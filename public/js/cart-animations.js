// Cart and Add-to-Cart Animations System

class CartManager {
    constructor() {
        this.cart = this.loadCart();
        this.modal = null;
        this.currentProduct = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.updateCartUI();
        this.loadCart();
    }

    setupEventListeners() {
        // Add to cart modal
        const modal = document.getElementById('addToCartModal');
        if (modal) {
            this.modal = new bootstrap.Modal(modal);
            
            // Quantity controls
            document.getElementById('quantityMinus')?.addEventListener('click', () => this.decreaseQuantity());
            document.getElementById('quantityPlus')?.addEventListener('click', () => this.increaseQuantity());
            document.getElementById('quantity')?.addEventListener('change', () => this.updateTotalPrice());
            
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

    openAddToCartModal(productId, productName, productPrice, productImage, stock) {
        this.currentProduct = {
            id: productId,
            name: productName,
            price: productPrice,
            image: productImage,
            stock: stock
        };

        // Populate modal
        document.getElementById('modalProductId').value = productId;
        document.getElementById('modalProductImage').src = productImage;
        document.getElementById('modalProductName').textContent = productName;
        document.getElementById('modalProductPrice').textContent = `₱${productPrice.toFixed(2)}`;
        document.getElementById('quantity').value = 1;
        document.getElementById('stockStatus').textContent = `${stock} items available`;

        // Reset form
        this.resetOptionsForm();

        // Show sizes and colors (mock data - replace with actual options)
        this.populateSizeOptions();
        this.populateColorOptions();

        this.updateTotalPrice();
        this.modal.show();
    }

    populateSizeOptions() {
        const sizeOptions = document.getElementById('sizeOptions');
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
        const currentValue = parseInt(input.value);
        if (currentValue < this.currentProduct.stock) {
            input.value = currentValue + 1;
            this.updateTotalPrice();
        }
    }

    decreaseQuantity() {
        const input = document.getElementById('quantity');
        const currentValue = parseInt(input.value);
        if (currentValue > 1) {
            input.value = currentValue - 1;
            this.updateTotalPrice();
        }
    }

    updateTotalPrice() {
        const quantity = parseInt(document.getElementById('quantity').value);
        const total = this.currentProduct.price * quantity;
        document.getElementById('totalPrice').textContent = `₱${total.toFixed(2)}`;
    }

    confirmAddToCart() {
        const size = document.querySelector('[name="size"]:checked')?.value;
        const color = document.querySelector('[name="color"]:checked')?.value;
        const quantity = parseInt(document.getElementById('quantity').value);

        if (!size) {
            this.showToast('Please select a size', 'warning');
            return;
        }

        if (!color) {
            this.showToast('Please select a color', 'warning');
            return;
        }

        const item = {
            id: this.currentProduct.id,
            name: this.currentProduct.name,
            price: this.currentProduct.price,
            quantity: quantity,
            size: size,
            color: color,
            image: this.currentProduct.image,
            timestamp: Date.now()
        };

        // Add to cart with animation
        this.addToCart(item);
        this.modal.hide();
        this.showToast(`${this.currentProduct.name} added to cart!`, 'success');
    }

    addToCart(item) {
        // Check if item with same size and color already exists
        const existingIndex = this.cart.findIndex(cartItem => 
            cartItem.id === item.id && 
            cartItem.size === item.size && 
            cartItem.color === item.color
        );

        if (existingIndex > -1) {
            this.cart[existingIndex].quantity += item.quantity;
        } else {
            this.cart.push(item);
        }

        this.saveCart();
        this.updateCartUI();
        this.animateAddToCart();
    }

    animateAddToCart() {
        const modal = document.getElementById('addToCartModal');
        const cartBtn = document.getElementById('cartPreviewBtn');
        
        if (!modal || !cartBtn) return;

        // Create flying element
        const flyingItem = document.createElement('div');
        flyingItem.className = 'flying-cart-item';
        flyingItem.innerHTML = `<i class="bi bi-bag" style="font-size: 2rem; color: #667eea;"></i>`;
        document.body.appendChild(flyingItem);

        const modalImage = document.getElementById('modalProductImage');
        const sourceRect = modalImage.getBoundingClientRect();
        const targetRect = cartBtn.getBoundingClientRect();

        flyingItem.style.position = 'fixed';
        flyingItem.style.left = sourceRect.left + sourceRect.width / 2 + 'px';
        flyingItem.style.top = sourceRect.top + sourceRect.height / 2 + 'px';
        flyingItem.style.width = '40px';
        flyingItem.style.height = '40px';
        flyingItem.style.display = 'flex';
        flyingItem.style.alignItems = 'center';
        flyingItem.style.justifyContent = 'center';
        flyingItem.style.borderRadius = '50%';
        flyingItem.style.background = 'rgba(102, 126, 234, 0.2)';
        flyingItem.style.zIndex = '9999';
        flyingItem.style.pointerEvents = 'none';

        // Animate
        setTimeout(() => {
            flyingItem.style.transition = 'all 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
            flyingItem.style.left = targetRect.left + targetRect.width / 2 + 'px';
            flyingItem.style.top = targetRect.top + targetRect.height / 2 + 'px';
            flyingItem.style.width = '20px';
            flyingItem.style.height = '20px';
            flyingItem.style.opacity = '0.3';
        }, 10);

        setTimeout(() => {
            flyingItem.remove();
            this.bounceCartIcon();
        }, 600);
    }

    bounceCartIcon() {
        const cartBtn = document.getElementById('cartPreviewBtn');
        if (!cartBtn) return;

        cartBtn.style.animation = 'none';
        setTimeout(() => {
            cartBtn.style.animation = 'cartBounce 0.6s ease-out';
        }, 10);
    }

    updateCartUI() {
        const count = this.getCartItemCount();
        const badge = document.getElementById('cartBadge');
        
        if (badge) {
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
        }

        this.updateCartPreview();
    }

    getCartItemCount() {
        return this.cart.reduce((total, item) => total + item.quantity, 0);
    }

    updateCartPreview() {
        const itemsList = document.getElementById('cartItemsList');
        const summary = document.getElementById('cartSummary');
        const itemCount = document.getElementById('previewItemCount');

        if (!itemsList) return;

        if (this.cart.length === 0) {
            itemsList.innerHTML = `
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-bag-slash display-3 d-block mb-2"></i>
                    Your cart is empty
                </div>
            `;
            if (summary) summary.style.display = 'none';
            return;
        }

        itemsList.innerHTML = this.cart.map((item, index) => `
            <div class="cart-item-preview" data-cart-index="${index}">
                <img src="${item.image}" alt="${item.name}" class="cart-item-image">
                <div class="cart-item-info">
                    <div class="cart-item-name">${item.name}</div>
                    <div class="cart-item-details">
                        Size: <strong>${item.size}</strong> | 
                        Color: <strong>${item.color}</strong> |
                        Qty: <strong>${item.quantity}</strong>
                    </div>
                </div>
                <div class="text-end">
                    <div class="cart-item-price">₱${(item.price * item.quantity).toFixed(2)}</div>
                    <i class="bi bi-trash cart-item-remove mt-2" onclick="cartManager.removeFromPreview(${index})"></i>
                </div>
            </div>
        `).join('');

        const subtotal = this.calculateSubtotal();
        itemCount.textContent = this.getCartItemCount();
        document.getElementById('previewSubtotal').textContent = `₱${subtotal.toFixed(2)}`;
        document.getElementById('previewTotal').textContent = `₱${subtotal.toFixed(2)}`;
        
        if (summary) summary.style.display = 'block';
    }

    calculateSubtotal() {
        return this.cart.reduce((total, item) => total + (item.price * item.quantity), 0);
    }

    removeFromPreview(index) {
        if (confirm('Remove this item?')) {
            this.cart.splice(index, 1);
            this.saveCart();
            this.updateCartUI();
            this.showToast('Item removed from cart', 'info');
        }
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
            this.cart = [];
            this.saveCart();
            this.updateCartUI();
            this.showToast('Cart cleared', 'info');
        }
    }

    saveCart() {
        localStorage.setItem('eccom_cart', JSON.stringify(this.cart));
    }

    loadCart() {
        const saved = localStorage.getItem('eccom_cart');
        return saved ? JSON.parse(saved) : [];
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

// Initialize cart manager when DOM is ready
let cartManager;
document.addEventListener('DOMContentLoaded', () => {
    cartManager = new CartManager();
});

// CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes cartBounce {
        0% {
            transform: scale(1, 1);
        }
        50% {
            transform: scale(1.15, 1.15);
        }
        100% {
            transform: scale(1, 1);
        }
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

    .flying-cart-item {
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }
`;
document.head.appendChild(style);
