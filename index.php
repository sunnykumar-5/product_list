<?php
$host = 'localhost';
$db = 'products';
$user = 'root';
$pass = '';



$conn = new mysqli($host, $user, $pass, $db);

// Handle search query
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

$query = $search ? 
    "SELECT * FROM products WHERE name LIKE '%$search%'" : 
    "SELECT * FROM products";

$result = $conn->query($query);
$products = $result->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart with Dynamic Price Update</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Product List</h1>
    
    <!-- Search Bar -->
    <div class="search-bar">
        <form method="get">
            <input type="text" name="search" placeholder="Search for products..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Search</button>
        </form>
    </div>

    <!-- Product Table -->
    <table>
        <thead>
            <tr>
                <th>Product Name</th>
                <th>Weight/Quantity</th>
                <th>Price</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td><?= htmlspecialchars($product['name']) ?></td>
                    <td>
                        <input 
                            type="text" 
                            value="<?= htmlspecialchars($product['weight']) ?>" 
                            data-product-id="<?= htmlspecialchars($product['id']) ?>" 
                            data-base-price="<?= htmlspecialchars($product['price']) ?>" 
                            data-base-weight="<?= htmlspecialchars($product['weight']) ?>" 
                            class="weight-input"
                        >
                    </td>
                    <td>
                        ₹<span class="price-display" id="price-display-<?= htmlspecialchars($product['id']) ?>">
                            <?= htmlspecialchars($product['price']) ?>
                        </span>
                    </td>
                    <td>
                        <form action="cart.php" method="post" id="add-to-cart-form-<?= $product['id'] ?>">
                            <input type="hidden" name="name" value="<?= htmlspecialchars($product['name']) ?>">
                            <input type="hidden" name="weight" value="<?= htmlspecialchars($product['weight']) ?>" class="hidden-weight">
                            <input type="hidden" name="price" value="<?= htmlspecialchars($product['price']) ?>" class="hidden-price">
                            <button type="button" onclick="addToCart(<?= $product['id'] ?>)">Add to Cart</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">No products found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Redirect button to Cart page -->
    <div id="cart-button" style="text-align: center;">
        <a href="cart.php">
            <button>Go to Cart</button>
        </a>
    </div>

    <a href="admin.php">
        <a href="admin.php"><button class="add">Admin Panel</button></a>
    </a>
     

    <script>
        document.querySelectorAll('.weight-input').forEach(input => {
            input.addEventListener('input', function () {
                const productId = this.dataset.productId;
                const basePrice = parseFloat(this.dataset.basePrice);
                const baseWeight = parseFloat(this.dataset.baseWeight);
                const newWeight = parseFloat(this.value);

                if (isNaN(newWeight) || newWeight <= 0) return;

                // Calculate new price (e.g., proportional to weight)
                const newPrice = (basePrice / baseWeight) * newWeight;

                // Update price display
                const priceDisplay = document.getElementById('price-display-' + productId);
                priceDisplay.textContent = newPrice.toFixed(2);

                // Update hidden fields in the cart form
                const form = document.getElementById('add-to-cart-form-' + productId);
                form.querySelector('.hidden-weight').value = newWeight;
                form.querySelector('.hidden-price').value = newPrice.toFixed(2);
            });
        });

        function addToCart(productId) {
            const form = document.getElementById('add-to-cart-form-' + productId);

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form)
            })
            .then(response => response.text())
            .then(data => {
                const addButton = form.querySelector('button');
                addButton.disabled = true;
                addButton.style.backgroundColor = 'rgb(216, 47, 87)';
                addButton.innerText = "Added to Cart";

                document.getElementById('cart-button').style.display = 'block';
            })
            .catch(error => console.error('Error adding to cart:', error));
        }
    </script>
</body>
</html>
