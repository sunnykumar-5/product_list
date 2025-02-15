<?php
session_start();

// Database connection
$host = 'localhost';
$db = 'products';
$user = 'root';
$pass = '';


$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create tables if they don't exist
$conn->query("
    CREATE TABLE IF NOT EXISTS admin (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL
    )
");

$conn->query("
    CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        weight VARCHAR(50) NOT NULL,
        price DECIMAL(10, 2) NOT NULL
    )
");

// Insert default admin if not exists
$conn->query("
    INSERT IGNORE INTO admin (username, password) 
    VALUES ('admin', MD5('password123'))
");

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $password = md5($conn->real_escape_string($_POST['password']));

    $query = "SELECT * FROM admin WHERE username = '$username' AND password = '$password'";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $error = "Invalid username or password.";
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Handle product addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product']) && isset($_SESSION['admin_logged_in'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $weight = $conn->real_escape_string($_POST['weight']);
    $price = (float)$_POST['price'];

    // Check if editing or adding a new product
    if (!empty($_POST['product_id'])) {
        $id = (int)$_POST['product_id'];
        $query = "UPDATE products SET name = '$name', weight = '$weight', price = $price WHERE id = $id";
    } else {
        $query = "INSERT INTO products (name, weight, price) VALUES ('$name', '$weight', $price)";
    }

    if ($conn->query($query) === TRUE) {
        $success = "Product " . (!empty($_POST['product_id']) ? "updated" : "added") . " successfully.";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Fetch products
$products = [];
if (isset($_SESSION['admin_logged_in'])) {
    $result = $conn->query("SELECT * FROM products");
    if ($result->num_rows > 0) {
        $products = $result->fetch_all(MYSQLI_ASSOC);
    }
}

// For editing product
$product_to_edit = null;
if (isset($_GET['edit']) && isset($_SESSION['admin_logged_in'])) {
    $id = (int)$_GET['edit'];
    $result = $conn->query("SELECT * FROM products WHERE id = $id");
    if ($result->num_rows > 0) {
        $product_to_edit = $result->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
        }
        input, button {
            display: block;
            width: 100%;
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            background-color: #704214;
            color: white;
            border: none;
            cursor: pointer;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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
        .error {
            color: red;
            margin-bottom: 10px;
        }
        .success {
            color: green;
            margin-bottom: 10px;
        }

        /* Responsive Styles */
        @media screen and (max-width: 768px) {
            .container {
                max-width: 100%;
                padding: 10px;
            }
            table {
                font-size: 12px;
                overflow-x: auto;
            }
            th, td {
                padding: 8px;
            }
            input, button {
                padding: 8px;
            }
        }

        @media screen and (max-width: 480px) {
            body {
                padding: 10px;
            }
            .container {
                padding: 15px;
            }
            table {
                font-size: 10px;
            }
            th, td {
                padding: 5px;
            }
            input, button {
                padding: 6px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!isset($_SESSION['admin_logged_in'])): ?>
            <h2>Admin Login</h2>
            <?php if (isset($error)): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="login">Login</button>
            </form>
        <?php else: ?>
            <h1>Admin Panel</h1>
             <!-- Orders Button -->
             <a href="admin_panel.php"><button>Orders</button></a>
            <a href="?logout=true" style="float: right;">Logout</a>
            <h2><?= $product_to_edit ? "Edit Product" : "Add Product" ?></h2>
            <?php if (isset($success)): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php elseif (isset($error)): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="product_id" value="<?= $product_to_edit['id'] ?? '' ?>">
                <input type="text" name="name" placeholder="Product Name" value="<?= $product_to_edit['name'] ?? '' ?>" required>
                <input type="text" name="weight" placeholder="Weight/Quantity" value="<?= $product_to_edit['weight'] ?? '' ?>" required>
                <input type="number" step="0.01" name="price" placeholder="Price" value="<?= $product_to_edit['price'] ?? '' ?>" required>
                <button type="submit" name="add_product"><?= $product_to_edit ? "Update Product" : "Add Product" ?></button>
            </form>

            <h2>Product List</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Weight</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?= $product['id'] ?></td>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td><?= htmlspecialchars($product['weight']) ?></td>
                                <td>₹<?= number_format($product['price'], 2) ?></td>
                                <td>
                                    <a href="?edit=<?= $product['id'] ?>">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">No products found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

           
        <?php endif; ?>
    </div>
</body>
</html>
