<?php

namespace App\Service;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartService
{
    private const CART_SESSION_KEY = 'cart';
    
    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $em
    ) {}

    /**
     * Get session from request stack
     */
    private function getSession(): SessionInterface
    {
        return $this->requestStack->getSession();
    }

    /**
     * Add product to cart with stock validation
     */
    public function addItem(Product $product, int $quantity = 1): array
    {
        // Validate stock availability
        if ($product->getStock() === null || $product->getStock()->getQuantity() < $quantity) {
            return [
                'success' => false,
                'message' => 'Insufficient stock available',
                'available' => $product->getStock()?->getQuantity() ?? 0
            ];
        }

        $session = $this->getSession();
        $cart = $session->get(self::CART_SESSION_KEY, []);
        $productId = $product->getId();

        if (!isset($cart[$productId])) {
            $cart[$productId] = 0;
        }

        // Check total quantity doesn't exceed stock
        if (($cart[$productId] + $quantity) > $product->getStock()->getQuantity()) {
            return [
                'success' => false,
                'message' => 'Quantity exceeds available stock',
                'available' => $product->getStock()->getQuantity() - $cart[$productId]
            ];
        }

        $cart[$productId] += $quantity;
        $session->set(self::CART_SESSION_KEY, $cart);

        return [
            'success' => true,
            'message' => 'Product added to cart',
            'quantity' => $cart[$productId]
        ];
    }

    /**
     * Remove item from cart
     */
    public function removeItem(int $productId): bool
    {
        $session = $this->getSession();
        $cart = $session->get(self::CART_SESSION_KEY, []);
        
        if (isset($cart[$productId])) {
            unset($cart[$productId]);
            $session->set(self::CART_SESSION_KEY, $cart);
            return true;
        }

        return false;
    }

    /**
     * Update quantity of cart item
     */
    public function updateQuantity(Product $product, int $quantity): array
    {
        if ($quantity <= 0) {
            $this->removeItem($product->getId());
            return ['success' => true, 'message' => 'Item removed from cart'];
        }

        // Validate stock
        if ($product->getStock() === null || $product->getStock()->getQuantity() < $quantity) {
            return [
                'success' => false,
                'message' => 'Insufficient stock available',
                'available' => $product->getStock()?->getQuantity() ?? 0
            ];
        }

        $session = $this->getSession();
        $cart = $session->get(self::CART_SESSION_KEY, []);
        $productId = $product->getId();

        if (isset($cart[$productId])) {
            $cart[$productId] = $quantity;
            $session->set(self::CART_SESSION_KEY, $cart);
            return ['success' => true, 'quantity' => $quantity];
        }

        return ['success' => false, 'message' => 'Item not in cart'];
    }

    /**
     * Get raw cart data
     */
    public function getCart(): array
    {
        return $this->getSession()->get(self::CART_SESSION_KEY, []);
    }

    /**
     * Get cart items with product details
     */
    public function getCartItems(): array
    {
        $cart = $this->getCart();
        $items = [];

        foreach ($cart as $productId => $quantity) {
            $product = $this->em->getRepository(Product::class)->find($productId);
            
            if ($product && $product->isAvailable() && $product->getStock()?->getQuantity() > 0) {
                $items[] = [
                    'id' => $productId,
                    'product' => $product,
                    'quantity' => $quantity,
                    'price' => $product->getPrice(),
                    'subtotal' => $product->getPrice() * $quantity,
                    'stock_available' => $product->getStock()->getQuantity(),
                ];
            }
        }

        return $items;
    }

    /**
     * Calculate cart total
     */
    public function getCartTotal(): float
    {
        $items = $this->getCartItems();
        $total = 0;

        foreach ($items as $item) {
            $total += $item['subtotal'];
        }

        return $total;
    }

    /**
     * Calculate MRP (price before discount) - useful for showing savings
     */
    public function getMRP(): float
    {
        $items = $this->getCartItems();
        $mrp = 0;

        foreach ($items as $item) {
            $mrp += ($item['product']->getPrice() * $item['quantity']);
        }

        return $mrp;
    }

    /**
     * Get cart count
     */
    public function getCartCount(): int
    {
        $cart = $this->getCart();
        return array_sum($cart);
    }

    /**
     * Clear entire cart
     */
    public function clearCart(): void
    {
        $this->getSession()->remove(self::CART_SESSION_KEY);
    }

    /**
     * Check if cart is empty
     */
    public function isEmpty(): bool
    {
        return count($this->getCart()) === 0;
    }

    /**
     * Get shipping fee based on total (example logic)
     */
    public function calculateShippingFee(float $subtotal = null): float
    {
        $subtotal = $subtotal ?? $this->getCartTotal();

        // Free shipping for orders over ₱500
        if ($subtotal >= 500) {
            return 0;
        }

        // Standard shipping ₱100
        return 100;
    }

    /**
     * Apply discount percentage (for future coupon feature)
     */
    public function calculateDiscount(float $percentage = 0): float
    {
        $subtotal = $this->getCartTotal();
        return ($subtotal * $percentage) / 100;
    }

    /**
     * Get final total with shipping
     */
    public function getFinalTotal(float $discountPercentage = 0): float
    {
        $subtotal = $this->getCartTotal();
        $discount = $this->calculateDiscount($discountPercentage);
        $shipping = $this->calculateShippingFee($subtotal - $discount);

        return $subtotal - $discount + $shipping;
    }

    /**
     * Merge session cart with user's saved cart (for future persistence)
     */
    public function mergeCart(array $savedCart): void
    {
        $session = $this->getSession();
        $currentCart = $session->get(self::CART_SESSION_KEY, []);

        foreach ($savedCart as $productId => $quantity) {
            if (!isset($currentCart[$productId])) {
                $currentCart[$productId] = $quantity;
            }
        }

        $session->set(self::CART_SESSION_KEY, $currentCart);
    }

    /**
     * Validate entire cart (check all items still available)
     */
    public function validateCart(): array
    {
        $session = $this->getSession();
        $cart = $session->get(self::CART_SESSION_KEY, []);
        $issues = [];

        foreach ($cart as $productId => $quantity) {
            $product = $this->em->getRepository(Product::class)->find($productId);

            if (!$product || !$product->isAvailable()) {
                $issues[] = "Product #$productId is no longer available";
                unset($cart[$productId]);
            } elseif (!$product->getStock() || $product->getStock()->getQuantity() < $quantity) {
                $issues[] = $product->getName() . " has insufficient stock";
                unset($cart[$productId]);
            }
        }

        if (!empty($issues)) {
            $session->set(self::CART_SESSION_KEY, $cart);
        }

        return ['valid' => empty($issues), 'issues' => $issues];
    }
}
