<?php
$host = 'localhost';
$db = 'products';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

$result = $conn->query("SELECT * FROM products");
$products = $result->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
            margin: 0;
            padding: 20px;
        }
        h1 {
            text-align: center;
            color: #704214;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #704214;
            color: white;
        }
        button {
            padding: 5px 10px;
            background-color: #704214;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s, transform 0.3s;
        }
        button:hover {
            background-color: #d88f3e;
            transform: scale(1.1);
        }

        .add {
            height: 40px;
            width: 120px;
            border-radius: 15px;
            background-color: #704214;
            position: absolute;
            top: 20px;
            right: 20px;
            transition: background-color 0.3s, transform 0.3s;
        }

        .add:hover {
            background-color: #d88f3e;
            transform: scale(1.1);
        }

        /* Responsive Styling */
        @media screen and (max-width: 768px) {
            table, th, td {
                font-size: 14px;
                padding: 8px;
            }
            .add {
                width: 100px;
                top: 10px;
                right: 10px;
            }
            button {
                padding: 5px 8px;
            }
            h1 {
                font-size: 24px;
            }
        }

        @media screen and (max-width: 480px) {
            table {
                font-size: 12px;
            }
            .add {
                width: 90px;
            }
            button {
                padding: 4px 6px;
            }
            h1 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <h1>Product List</h1>
    <table>
        <thead>
            <tr>
                <th>Product Name</th>
                <th>Weight/Quantity</th>
                <th>Price</th>
                <th>Add to Cart</th>
            </tr>
        </thead>
        <tbody id="product-list">
            <?php foreach ($products as $product): ?>
            <tr>
                <td><?= htmlspecialchars($product['name']) ?></td>
                <td><?= htmlspecialchars($product['weight']) ?></td>
                <td>₹<?= htmlspecialchars($product['price']) ?></td>
                <td>
                    <form action="cart.php" method="post">
                        <input type="hidden" name="name" value="<?= htmlspecialchars($product['name']) ?>">
                        <input type="hidden" name="weight" value="<?= htmlspecialchars($product['weight']) ?>">
                        <input type="hidden" name="price" value="<?= htmlspecialchars($product['price']) ?>">
                        <button type="submit">Add to Cart</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <a href="admin.php">
        <button class="add">Add Product</button>
    </a>
</body>
</html>
