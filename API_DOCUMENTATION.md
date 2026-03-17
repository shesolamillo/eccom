# ECCOM REST API Documentation

## Base URL
```
http://localhost:8000/api
```

## Authentication
Currently, the API can be accessed without authentication. Add Bearer token authentication if needed.

---

## Products Endpoints

### List Products
```
GET /api/products
```

**Query Parameters:**
- `page` (int, default: 1) - Page number
- `limit` (int, default: 20) - Items per page
- `category` (int) - Filter by category ID
- `type` (int) - Filter by type ID
- `search` (string) - Search by product name

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Product Name",
      "description": "Description",
      "price": 99.99,
      "image": "image.jpg",
      "category": {
        "id": 1,
        "name": "Category Name"
      },
      "type": {
        "id": 1,
        "name": "Type Name"
      },
      "sku": "SKU123",
      "createdAt": "2026-03-10 10:30:00",
      "updatedAt": "2026-03-10 10:30:00"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 100,
    "pages": 5
  }
}
```

### Get Product Details
```
GET /api/products/{id}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Product Name",
    "description": "Description",
    "price": 99.99,
    ...
  }
}
```

### Get Product Stock
```
GET /api/products/{id}/stock
```

**Response:**
```json
{
  "success": true,
  "data": {
    "productId": 1,
    "productName": "Product Name",
    "totalQuantity": 150,
    "stock": [
      {
        "id": 1,
        "size": "M",
        "quantity": 50,
        "reorderLevel": 20
      }
    ]
  }
}
```

---

## Orders Endpoints

### List Orders
```
GET /api/orders
```

**Query Parameters:**
- `page` (int, default: 1)
- `limit` (int, default: 20)
- `status` (string) - Filter by status (pending, confirmed, shipped, delivered)
- `userId` (int) - Filter by user ID

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "userId": 5,
      "userName": "John Doe",
      "status": "confirmed",
      "totalAmount": 299.97,
      "itemCount": 3,
      "items": [
        {
          "id": 1,
          "productId": 10,
          "productName": "Product Name",
          "quantity": 2,
          "price": 99.99,
          "subtotal": 199.98
        }
      ],
      "createdAt": "2026-03-10 10:30:00",
      "updatedAt": "2026-03-10 11:00:00"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 50,
    "pages": 3
  }
}
```

### Get Order Details
```
GET /api/orders/{id}
```

### Create Order
```
POST /api/orders
Content-Type: application/json
```

**Request Body:**
```json
{
  "userId": 5,
  "items": [
    {
      "productId": 10,
      "quantity": 2,
      "price": 99.99
    }
  ]
}
```

**Response:** (201 Created)
```json
{
  "success": true,
  "message": "Order created successfully",
  "data": { ... }
}
```

### Update Order
```
PUT /api/orders/{id}
Content-Type: application/json
```

**Request Body:**
```json
{
  "status": "shipped"
}
```

---

## Stock Endpoints

### List Stock
```
GET /api/stock
```

**Query Parameters:**
- `page` (int, default: 1)
- `limit` (int, default: 20)
- `productId` (int) - Filter by product ID

### Get Stock Details
```
GET /api/stock/{id}
```

### Update Stock
```
PUT /api/stock/{id}
Content-Type: application/json
```

**Request Body:**
```json
{
  "quantity": 100,
  "reorderLevel": 20
}
```

### Get Low Stock Items
```
GET /api/stock/low-stock
```

Returns all stock items where quantity <= reorderLevel

---

## Users Endpoints

### List Users
```
GET /api/users
```

**Query Parameters:**
- `page` (int, default: 1)
- `limit` (int, default: 20)
- `role` (string) - Filter by role

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "email": "user@example.com",
      "name": "John Doe",
      "username": "johndoe",
      "roles": ["ROLE_USER"],
      "isActive": true,
      "createdAt": "2026-03-10 10:30:00"
    }
  ],
  "pagination": { ... }
}
```

### Get User Details
```
GET /api/users/{id}
```

---

## Error Responses

### 404 Not Found
```json
{
  "success": false,
  "error": "Resource not found"
}
```

### 400 Bad Request
```json
{
  "success": false,
  "error": "Missing required fields: userId, items"
}
```

---

## Usage Examples

### JavaScript/Fetch API

```javascript
// Get all products
fetch('/api/products?page=1&limit=20')
  .then(res => res.json())
  .then(data => console.log(data));

// Get specific product
fetch('/api/products/1')
  .then(res => res.json())
  .then(data => console.log(data));

// Create order
fetch('/api/orders', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    userId: 5,
    items: [
      { productId: 10, quantity: 2, price: 99.99 }
    ]
  })
})
  .then(res => res.json())
  .then(data => console.log(data));

// Update order status
fetch('/api/orders/1', {
  method: 'PUT',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    status: 'shipped'
  })
})
  .then(res => res.json())
  .then(data => console.log(data));
```

### PHP/cURL

```php
<?php
// Get all products
$ch = curl_init('http://localhost:8000/api/products');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$data = json_decode($response, true);
print_r($data);

// Create order
$ch = curl_init('http://localhost:8000/api/orders');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'userId' => 5,
    'items' => [
        ['productId' => 10, 'quantity' => 2, 'price' => 99.99]
    ]
]));
$response = curl_exec($ch);
$data = json_decode($response, true);
print_r($data);
?>
```

---

## Testing the API

Use Postman, Insomnia, or cURL to test endpoints:

```bash
# Get products
curl http://localhost:8000/api/products

# Get specific product
curl http://localhost:8000/api/products/1

# Create order
curl -X POST http://localhost:8000/api/orders \
  -H "Content-Type: application/json" \
  -d '{
    "userId": 5,
    "items": [
      {"productId": 10, "quantity": 2, "price": 99.99}
    ]
  }'

# Update order
curl -X PUT http://localhost:8000/api/orders/1 \
  -H "Content-Type: application/json" \
  -d '{"status": "shipped"}'
```

---

## Next Steps

1. **Authentication**: Add JWT or API key authentication
2. **Rate Limiting**: Add rate limiting to prevent abuse
3. **Versioning**: Consider API versioning (/api/v1/...)
4. **CORS**: Configure CORS if accessing from different domain
5. **Documentation**: Generate Swagger/OpenAPI documentation
