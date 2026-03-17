<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\UserProfile;
use App\Entity\ProductType;
use App\Entity\ClothesCategory;
use App\Entity\Product;
use App\Entity\Stock;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Receipt;
use App\Entity\ActivityLog;
use App\Entity\Settings;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $this->loadUsers($manager);
        $this->loadProductTypes($manager);
        $this->loadClothesCategories($manager);
        $this->loadProducts($manager);
        $this->loadStocks($manager);
        $this->loadOrders($manager);
        $this->loadSettings($manager);
        $this->loadActivityLogs($manager);

        $manager->flush();
    }

    private function loadUsers(ObjectManager $manager): void
    {
        // Admin User
        $admin = new User();
        $admin->setEmail('admin@gmail.com');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setFirstName('seila');
        $admin->setLastName('Admin');
        $admin->setPhoneNumber('+1 (555) 123-4567');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setIsActive(true);

        $adminProfile = new UserProfile();
        $adminProfile->setUser($admin);
        $adminProfile->setAddress('123 Admin Street, City');
        $adminProfile->setCity('Metro City');
        $adminProfile->setState('State');
        $adminProfile->setZipCode('12345');
        $adminProfile->setGender('male');

        $manager->persist($admin);
        $manager->persist($adminProfile);

        // Staff Users
        $staffNames = [
            ['Sarah', 'Johnson', 'sarah@laundrypro.com'],
            ['staff', 'staff', 'staff@gmail.com'],
            ['Lisa', 'Brown', 'lisa@laundrypro.com'],
        ];

        foreach ($staffNames as $index => $staff) {
            $staffUser = new User();
            $staffUser->setEmail($staff[2]);
            $staffUser->setPassword($this->passwordHasher->hashPassword($staffUser, 'staff123'));
            $staffUser->setFirstName($staff[0]);
            $staffUser->setLastName($staff[1]);
            $staffUser->setPhoneNumber('+1 (555) 100-000' . ($index + 1));
            $staffUser->setRoles(['ROLE_STAFF']);
            $staffUser->setIsActive(true);

            $staffProfile = new UserProfile();
            $staffProfile->setUser($staffUser);
            $staffProfile->setGender($index == 0 ? 'female' : 'male');

            $manager->persist($staffUser);
            $manager->persist($staffProfile);

            $this->addReference('staff_' . $index, $staffUser);
        }

        // Regular Users
        $userNames = [
            ['Robert', 'Fox', 'robert@example.com'],
            ['Emma', 'Davis', 'emma@example.com'],
            ['James', 'Miller', 'james@example.com'],
            ['Sophia', 'Taylor', 'sophia@example.com'],
            ['David', 'Anderson', 'david@example.com'],
            ['Olivia', 'Thomas', 'olivia@example.com'],
            ['William', 'Jackson', 'william@example.com'],
            ['Ava', 'White', 'ava@example.com'],
        ];

        foreach ($userNames as $index => $user) {
            $regularUser = new User();
            $regularUser->setEmail($user[2]);
            $regularUser->setPassword($this->passwordHasher->hashPassword($regularUser, 'user123'));
            $regularUser->setFirstName($user[0]);
            $regularUser->setLastName($user[1]);
            $regularUser->setPhoneNumber('+1 (555) 200-000' . ($index + 1));
            $regularUser->setRoles(['ROLE_USER']);
            $regularUser->setIsActive(true);

            $userProfile = new UserProfile();
            $userProfile->setUser($regularUser);
            $userProfile->setAddress(($index + 100) . ' Main Street, Apt ' . ($index + 1));
            $userProfile->setCity('City');
            $userProfile->setState('State');
            $userProfile->setZipCode('1000' . $index);
            $userProfile->setGender($index % 2 == 0 ? 'male' : 'female');

            $manager->persist($regularUser);
            $manager->persist($userProfile);

            $this->addReference('user_' . $index, $regularUser);
        }

        // Inactive user
        $inactiveUser = new User();
        $inactiveUser->setEmail('inactive@example.com');
        $inactiveUser->setPassword($this->passwordHasher->hashPassword($inactiveUser, 'user123'));
        $inactiveUser->setFirstName('Inactive');
        $inactiveUser->setLastName('User');
        $inactiveUser->setIsActive(false);

        $manager->persist($inactiveUser);
    }

    private function loadProductTypes(ObjectManager $manager): void
    {
        $productTypes = [
            ['Dry Cleaning', 'Professional dry cleaning for delicate fabrics', 150.00, 24],
            ['Wash & Fold', 'Standard wash, dry, and fold service', 80.00, 12],
            ['Ironing', 'Professional ironing and pressing', 50.00, 6],
            ['Stain Removal', 'Specialized stain removal treatment', 100.00, 48],
            ['Laundry', 'Basic laundry service', 70.00, 18],
            ['Express Service', 'Fast turnaround service', 120.00, 6],
            ['Bulk Laundry', 'Large volume laundry service', 200.00, 72],
            ['Special Care', 'Special care for delicate items', 180.00, 36],
        ];

        foreach ($productTypes as $index => $type) {
            $productType = new ProductType();
            $productType->setName($type[0]);
            $productType->setDescription($type[1]);
            $productType->setBasePrice($type[2]);
            $productType->setEstimatedHours($type[3]);
            $productType->setIsActive(true);

            $manager->persist($productType);
            $this->addReference('product_type_' . $index, $productType);
        }
    }

    private function loadClothesCategories(ObjectManager $manager): void
    {
        $categories = [
            'Shirts',
            'Pants',
            'Jackets',
            'Dresses',
            'Skirts',
            'Suits',
            'Underwear',
            'Socks',
            'Bedding',
            'Curtains',
            'Pet Clothing',
            'Baby Clothes'
        ];

        foreach ($categories as $index => $category) {
            $clothesCategory = new ClothesCategory();
            $clothesCategory->setName($category);
            $clothesCategory->setDescription('Clothing category for ' . strtolower($category));
            $clothesCategory->setIsActive(true);

            $manager->persist($clothesCategory);
            $this->addReference('category_' . $index, $clothesCategory);
        }
    }

    private function loadProducts(ObjectManager $manager): void
    {
        $products = [
            // Format: [Name, Description, Price, TypeIndex, CategoryIndex]
            ['Premium Shirt Dry Clean', 'Professional dry cleaning for premium shirts', 120.00, 0, 0],
            ['Casual Shirt Wash & Fold', 'Wash and fold service for casual shirts', 60.00, 1, 0],
            ['Formal Shirt Ironing', 'Professional ironing for formal shirts', 40.00, 2, 0],
            ['Jeans Wash & Fold', 'Denim wash and fold service', 70.00, 1, 1],
            ['Dress Pants Dry Clean', 'Dry cleaning for dress pants', 130.00, 0, 1],
            ['Winter Jacket Cleaning', 'Special cleaning for winter jackets', 250.00, 0, 2],
            ['Leather Jacket Care', 'Specialized care for leather jackets', 300.00, 7, 2],
            ['Evening Dress Dry Clean', 'Delicate dry cleaning for evening dresses', 180.00, 0, 3],
            ['Wedding Gown Preservation', 'Special preservation for wedding gowns', 500.00, 7, 3],
            ['Skirt Wash & Iron', 'Wash and iron service for skirts', 55.00, 1, 4],
            ['Business Suit Dry Clean', 'Complete suit dry cleaning service', 220.00, 0, 5],
            ['Underwear Wash & Fold', 'Sanitary wash and fold for underwear', 40.00, 1, 6],
            ['Socks Wash & Fold', 'Wash and fold service for socks', 30.00, 1, 7],
            ['Bed Sheet Cleaning', 'Large bedding cleaning service', 150.00, 6, 8],
            ['Pillow Cleaning', 'Special cleaning for pillows', 80.00, 3, 8],
            ['Curtain Dry Cleaning', 'Dry cleaning for curtains', 200.00, 0, 9],
            ['Pet Bed Cleaning', 'Cleaning service for pet beds', 90.00, 5, 10],
            ['Baby Clothes Special Care', 'Gentle cleaning for baby clothes', 65.00, 7, 11],
            ['Express Shirt Service', 'Fast turnaround shirt service', 100.00, 5, 0],
            ['Bulk Laundry Service', 'Large volume laundry service', 180.00, 6, 0],
        ];

        $staff = $this->getReference('staff_0', User::class);

        foreach ($products as $index => $product) {
            $productEntity = new Product();
            $productEntity->setName($product[0]);
            $productEntity->setDescription($product[1]);
            $productEntity->setPrice($product[2]);
            $productEntity->setProductType($this->getReference('product_type_' . $product[3], ProductType::class));
            $productEntity->setClothesCategory($this->getReference('category_' . $product[4], ClothesCategory::class));
            $productEntity->setCreatedBy($staff);
            $productEntity->setIsAvailable(true);

            $manager->persist($productEntity);
            $this->addReference('product_' . $index, $productEntity);
        }
    }

    private function loadStocks(ObjectManager $manager): void
    {
        // Create stock for each product
        for ($i = 0; $i < 20; $i++) {
            $stock = new Stock();
            $stock->setProduct($this->getReference('product_' . $i, Product::class));
            
            // Vary stock levels
            if ($i < 5) {
                // Low stock items
                $stock->setQuantity(rand(3, 8));
                $stock->setMinimumThreshold(10);
            } elseif ($i < 10) {
                // Medium stock items
                $stock->setQuantity(rand(20, 40));
                $stock->setMinimumThreshold(15);
            } else {
                // High stock items
                $stock->setQuantity(rand(50, 100));
                $stock->setMinimumThreshold(20);
            }
            
            $stock->setIsLowStock($stock->getQuantity() <= $stock->getMinimumThreshold());

            $manager->persist($stock);
            $this->addReference('stock_' . $i, $stock);
        }
    }

    private function loadOrders(ObjectManager $manager): void
    {
        $statuses = ['pending', 'processing', 'completed', 'cancelled'];
        $deliveryTypes = ['pickup', 'delivery'];
        $paymentMethods = ['cash', 'online'];
        
        $staff = $this->getReference('staff_0', User::class);

        // Create 50 sample orders
        for ($i = 0; $i < 50; $i++) {
            $order = new Order();
            
            // Assign random user (skip first 3 for variety)
            $userIndex = rand(0, 7);
            $order->setCustomer($this->getReference('user_' . $userIndex, User::class));
            
            // Random status with weights
            $statusWeights = ['pending' => 2, 'processing' => 3, 'completed' => 4, 'cancelled' => 1];
            $status = $this->getWeightedRandom($statusWeights);
            $order->setStatus($status);
            
            $order->setDeliveryType($deliveryTypes[rand(0, 1)]);
            $order->setPaymentMethod($paymentMethods[rand(0, 1)]);
            $order->setIsPaid($status == 'completed' ? (rand(0, 1) == 1) : false);
            
            if ($order->getDeliveryType() == 'delivery') {
                $order->setDeliveryAddress($userIndex . ' Delivery Street, City');
                $order->setDeliveryFee(50.00);
            }
            
            // Set random dates within last 30 days
            $createdAt = new \DateTimeImmutable('-' . rand(0, 30) . ' days');
            $createdAt = $createdAt->modify('-' . rand(0, 23) . ' hours');
            $order->setCreatedAt($createdAt);
            
            if ($status == 'completed') {
                $completedAt = $createdAt->modify('+' . rand(1, 72) . ' hours');
                $order->setCompletedAt($completedAt);
                
                if ($order->isIsPaid()) {
                    $order->setPaidAt($completedAt->modify('-1 hour'));
                }
            }
            
            if ($status != 'pending') {
                $order->setProcessedBy($staff);
            }
            
            // Add 1-4 random items to order
            $itemCount = rand(1, 4);
            $totalAmount = 0;
            
            for ($j = 0; $j < $itemCount; $j++) {
                $productIndex = rand(0, 19);
                $product = $this->getReference('product_' . $productIndex, Product::class);
                
                $orderItem = new OrderItem();
                $orderItem->setProduct($product);
                $orderItem->setQuantity(rand(1, 5));
                $orderItem->setUnitPrice($product->getPrice());
                $orderItem->setTotalPrice($orderItem->getUnitPrice() * $orderItem->getQuantity());
                
                $order->addOrderItem($orderItem);
                $totalAmount += $orderItem->getTotalPrice();
            }
            
            $order->setTotalAmount($totalAmount + ($order->getDeliveryFee() ?? 0));
            
            $manager->persist($order);
            $this->addReference('order_' . $i, $order);
            
            // Create receipt for completed orders
            if ($status == 'completed') {
                $receipt = new Receipt();
                $receipt->setOrderRef($order);
                $receipt->setSubtotal($totalAmount);
                $receipt->setTotalAmount($order->getTotalAmount());
                $receipt->setPaymentMethod($order->getPaymentMethod());
                
                if (rand(0, 1) == 1) {
                    $receipt->markAsPrinted($staff);
                }
                
                $manager->persist($receipt);
            }
        }
    }

    private function loadSettings(ObjectManager $manager): void
    {
        $settings = [
            // General Settings
            ['site_name', 'LaundryPro Management System', 'string', 'Website name'],
            ['site_url', 'http://localhost:8000', 'string', 'Website URL'],
            ['site_description', 'Professional laundry management system', 'string', 'Website description'],
            ['timezone', 'Asia/Manila', 'string', 'Default timezone'],
            ['date_format', 'Y-m-d', 'string', 'Date format'],
            ['currency', 'PHP', 'string', 'Default currency'],
            ['currency_symbol', '₱', 'string', 'Currency symbol'],
            ['maintenance_mode', 'false', 'boolean', 'Maintenance mode status'],
            
            // Business Settings
            ['business_name', 'LaundryPro Inc.', 'string', 'Business name'],
            ['business_email', 'info@laundrypro.com', 'string', 'Business email'],
            ['business_phone', '+1 (555) 123-4567', 'string', 'Business phone'],
            ['business_address', '123 Main Street, City, Country', 'string', 'Business address'],
            ['business_hours', "Monday - Friday: 8:00 AM - 8:00 PM\nSaturday: 9:00 AM - 6:00 PM\nSunday: 10:00 AM - 4:00 PM", 'string', 'Business hours'],
            ['tax_rate', '0.12', 'float', 'Tax rate'],
            ['tax_enabled', 'false', 'boolean', 'Enable tax calculation'],
            
            // Order Settings
            ['order_auto_accept', 'false', 'boolean', 'Auto-accept orders'],
            ['order_timeout', '30', 'integer', 'Order timeout in minutes'],
            ['delivery_fee', '50.00', 'float', 'Default delivery fee'],
            ['free_delivery_threshold', '500.00', 'float', 'Free delivery threshold'],
            ['order_statuses', 'pending,processing,completed,cancelled', 'string', 'Available order statuses'],
            ['default_order_status', 'pending', 'string', 'Default order status'],
            ['auto_generate_receipt', 'true', 'boolean', 'Auto-generate receipt on completion'],
            
            // Notification Settings
            ['notify_new_order', 'true', 'boolean', 'Notify on new order'],
            ['notify_order_status', 'true', 'boolean', 'Notify on order status change'],
            ['notify_low_stock', 'true', 'boolean', 'Notify on low stock'],
            ['notify_admin_email', 'admin@laundrypro.com', 'string', 'Admin notification email'],
            ['sms_enabled', 'false', 'boolean', 'Enable SMS notifications'],
            ['sms_provider', 'twilio', 'string', 'SMS provider'],
            ['sms_api_key', '', 'string', 'SMS API key'],
            ['sms_sender_id', 'LAUNDRYPRO', 'string', 'SMS sender ID'],
            
            // Payment Settings
            ['payment_cash_enabled', 'true', 'boolean', 'Enable cash payments'],
            ['payment_online_enabled', 'true', 'boolean', 'Enable online payments'],
            ['payment_gateway', 'stripe', 'string', 'Payment gateway'],
            ['payment_test_mode', 'true', 'boolean', 'Payment test mode'],
            ['payment_api_key', '', 'string', 'Payment API key'],
            ['payment_secret_key', '', 'string', 'Payment secret key'],
            
            // Email Settings
            ['mailer_transport', 'smtp', 'string', 'Mailer transport'],
            ['mailer_host', 'smtp.gmail.com', 'string', 'Mailer host'],
            ['mailer_port', '587', 'string', 'Mailer port'],
            ['mailer_encryption', 'tls', 'string', 'Mailer encryption'],
            ['mailer_username', 'noreply@laundrypro.com', 'string', 'Mailer username'],
            ['mailer_password', '', 'string', 'Mailer password'],
            ['mailer_from_email', 'noreply@laundrypro.com', 'string', 'From email'],
            ['mailer_from_name', 'LaundryPro System', 'string', 'From name'],
            
            // Security Settings
            ['login_attempts', '5', 'integer', 'Maximum login attempts'],
            ['login_timeout', '15', 'integer', 'Login timeout in minutes'],
            ['password_min_length', '8', 'integer', 'Minimum password length'],
            ['password_require_special', 'true', 'boolean', 'Require special characters in password'],
            ['session_timeout', '3600', 'integer', 'Session timeout in seconds'],
            ['remember_me_duration', '604800', 'integer', 'Remember me duration in seconds'],
            ['upload_max_size', '5', 'integer', 'Maximum upload size in MB'],
            ['upload_allowed_types', 'jpg,jpeg,png,gif,pdf', 'string', 'Allowed file types'],
        ];

        foreach ($settings as $setting) {
            $settingsEntity = new Settings();
            $settingsEntity->setSettingKey($setting[0]);
            $settingsEntity->setSettingValue($setting[1]);
            $settingsEntity->setDataType($setting[2]);
            $settingsEntity->setDescription($setting[3]);
            $settingsEntity->setIsPublic($setting[0] === 'site_name' || $setting[0] === 'business_name');

            $manager->persist($settingsEntity);
        }
    }

    private function loadActivityLogs(ObjectManager $manager): void
    {
        $actions = ['create', 'update', 'delete', 'login', 'logout', 'order_status'];
        $entities = ['user', 'order', 'product', 'stock', 'receipt'];
        $users = [
            $this->getReference('staff_0', User::class),
            $this->getReference('staff_1', User::class),
            $this->getReference('user_0', User::class),
            $this->getReference('user_1', User::class),
            null // System actions
        ];

        // Create 100 activity logs
        for ($i = 0; $i < 100; $i++) {
            $log = new ActivityLog();
            
            $userIndex = rand(0, 4);
            if ($userIndex < 4) {
                $log->setUser($users[$userIndex]);
            }
            
            $action = $actions[rand(0, 5)];
            $log->setAction($action);
            
            $entity = $entities[rand(0, 4)];
            $log->setEntity($entity);
            
            // Set entity ID for some logs
            if (rand(0, 1) == 1 && $entity == 'order') {
                $log->setEntityId(rand(1, 50));
            }
            
            // Create descriptive messages
            $descriptions = [
                'create' => ['Created new %s', 'Added %s to system'],
                'update' => ['Updated %s information', 'Modified %s details'],
                'delete' => ['Deleted %s from system', 'Removed %s'],
                'login' => ['User logged into system', 'Successful login'],
                'logout' => ['User logged out', 'Session ended'],
                'order_status' => ['Changed order status', 'Updated order workflow'],
            ];
            
            $descOptions = $descriptions[$action];
            $description = $descOptions[rand(0, count($descOptions) - 1)];
            
            if (strpos($description, '%s') !== false) {
                $description = sprintf($description, $entity);
            }
            
            $log->setDescription($description);
            $log->setIpAddress($this->generateIpAddress());
            $log->setUserAgent($this->generateUserAgent());
            
            // Set random date within last 30 days
            $createdAt = new \DateTimeImmutable('-' . rand(0, 30) . ' days');
            $createdAt = $createdAt->modify('-' . rand(0, 23) . ' hours');
            $createdAt = $createdAt->modify('-' . rand(0, 59) . ' minutes');
            $log->setCreatedAt($createdAt);

            $manager->persist($log);
        }
    }

    private function getWeightedRandom(array $weightedValues): string
    {
        $total = array_sum($weightedValues);
        $rand = rand(1, $total);
        
        foreach ($weightedValues as $key => $weight) {
            $rand -= $weight;
            if ($rand <= 0) {
                return $key;
            }
        }
        
        return array_key_first($weightedValues);
    }

    private function generateIpAddress(): string
    {
        return rand(192, 223) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 254);
    }

    private function generateUserAgent(): string
    {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (iPad; CPU OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
        ];
        
        return $userAgents[rand(0, count($userAgents) - 1)];
    }
}