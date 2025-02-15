<?php
session_start();

// Database connection
$host = 'localhost';
$db = 'products';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Initialize the cart if not already done
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Order success message
$orderSuccess = false;
$orderId = null;

// Add product to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'], $_POST['weight'], $_POST['price'])) {
    $product = [
        'name' => htmlspecialchars($_POST['name']),
        'weight' => floatval($_POST['weight']),
        'price' => floatval($_POST['price']),
    ];
    
    $found = false;
    foreach ($_SESSION['cart'] as &$cartItem) {
        if ($cartItem['name'] === $product['name']) {
            $cartItem['weight'] += $product['weight'];
            $cartItem['price'] += $product['price'];
            $found = true;
            break;
        }
    }
    if (!$found) {
        $_SESSION['cart'][] = $product;
    }
}

// Remove a product from the cart
if (isset($_POST['remove_item'])) {
    $index = $_POST['remove_item'];
    unset($_SESSION['cart'][$index]);
    $_SESSION['cart'] = array_values($_SESSION['cart']);
}

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['userName'], $_POST['userAddress'], $_POST['userMobile'])) {
    $userName = $conn->real_escape_string($_POST['userName']);
    $userAddress = $conn->real_escape_string($_POST['userAddress']);
    $userMobile = $conn->real_escape_string($_POST['userMobile']);
    $totalAmount = array_sum(array_column($_SESSION['cart'], 'price'));

    $sql = "INSERT INTO orders (user_name, user_address, user_mobile, total_amount) VALUES ('$userName', '$userAddress', '$userMobile', '$totalAmount')";
    if ($conn->query($sql) === TRUE) {
        $orderId = $conn->insert_id;
        
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_name, weight, price) VALUES (?, ?, ?, ?)");
        foreach ($_SESSION['cart'] as $item) {
            $stmt->bind_param("isdd", $orderId, $item['name'], $item['weight'], $item['price']);
            $stmt->execute();
        }
        $stmt->close();
        
        $orderSuccess = true;
        $_SESSION['cart'] = [];
    }
}

// View order details
if (isset($_GET['order_id'])) {
    $orderId = $_GET['order_id'];
    $orderQuery = $conn->query("SELECT * FROM orders WHERE id = $orderId");
    $orderDetails = $orderQuery->fetch_assoc();
    
    $orderItemsQuery = $conn->query("SELECT * FROM order_items WHERE order_id = $orderId");
    $orderItems = [];
    while ($item = $orderItemsQuery->fetch_assoc()) {
        $orderItems[] = $item;
    }
}

$cartItems = $_SESSION['cart'];
$totalAmount = array_sum(array_column($cartItems, 'price'));

function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart and Order Page</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; background-color: #e8f5e9; color: green; padding: 20px; }
        h1, h3 { color: green; text-align: center; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid green; padding: 10px; text-align: left; }
        th { background-color: #c8e6c9; }
        button { background-color: green; color: white; border: none; padding: 10px; cursor: pointer; width: 100%; }
        button:hover { background-color: darkgreen; }
        .cart-container { margin-bottom: 20px; overflow-x: auto; }
        .success-message { color: green; font-weight: bold; margin-top: 20px; text-align: center; }
        form { display: flex; flex-direction: column; gap: 10px; align-items: center; }
        input { width: 80%; padding: 10px; border: 1px solid green; border-radius: 5px; }
        .button-container { display: flex; justify-content: space-around; margin-top: 20px; }
        .button-container a { text-decoration: none; }

        /* Responsive Design */
        @media (max-width: 768px) {
            body { padding: 10px; }
            table, th, td { font-size: 14px; }
            button { font-size: 14px; }
            .cart-container { overflow-x: auto; }
            form { width: 100%; }
            input { width: 100%; }
        }

        @media (max-width: 600px) {
            table, th, td { font-size: 12px; }
            button { font-size: 12px; }
            input { width: 100%; padding: 8px; }
        }
    </style>
</head>
<body>
    <h1>Your Cart</h1>
    <div class="cart-container">
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
                <?php foreach ($cartItems as $index => $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td><?= htmlspecialchars($item['weight']) ?></td>
                    <td><?= formatCurrency($item['price']) ?></td>
                    <td>
                        <form method="post" style="display: inline;">
                            <button type="submit" name="remove_item" value="<?= $index ?>">Remove</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="3"><strong>Total Amount</strong></td>
                    <td><strong><?= formatCurrency($totalAmount) ?></strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <form id="userDetailsForm" method="post">
        <h3>Enter Your Details</h3>
        <input type="text" name="userName" placeholder="Name" required>
        <input type="text" name="userAddress" placeholder="Address" required>
        <input type="number" name="userMobile" placeholder="Mobile Number" required minlength="10" maxlength="10">
        <button type="submit">Place Order</button>
    </form>

    <div class="button-container">
        <a href="index.php"><button>Back to Products</button></a>
        <a href="https://wa.me/qr/YVULH425NYCNI1" target="_blank"><button>Send via WhatsApp</button></a>
    </div>

    <?php if ($orderSuccess): ?>
        <div class="success-message">Your order has been successfully placed! 🎉</div>
        <div class="button-container">
            <a href="?order_id=<?= $orderId ?>"><button>View Order Details</button></a>
        </div>
    <?php endif; ?>

    <?php if (isset($orderDetails)): ?>
        <h3>Order Details</h3>
        <table>
            <tr>
                <th>Order ID</th>
                <td><?= $orderDetails['id'] ?></td>
            </tr>
            <tr>
                <th>User Name</th>
                <td><?= $orderDetails['user_name'] ?></td>
            </tr>
            <tr>
                <th>Address</th>
                <td><?= $orderDetails['user_address'] ?></td>
            </tr>
            <tr>
                <th>Mobile</th>
                <td><?= $orderDetails['user_mobile'] ?></td>
            </tr>
            <tr>
                <th>Total Amount</th>
                <td><?= formatCurrency($orderDetails['total_amount']) ?></td>
            </tr>
        </table>

        <h3>Products Ordered</h3>
        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Weight</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orderItems as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                    <td><?= htmlspecialchars($item['weight']) ?></td>
                    <td><?= formatCurrency($item['price']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <button onclick="generatePDF()">Download Order PDF</button>
    <?php endif; ?>

    <script>
        function generatePDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            doc.setFontSize(16);
            doc.text("Order Details", 20, 20);

            doc.setFontSize(12);
            doc.text("Order ID: <?= $orderDetails['id'] ?>", 20, 30);
            doc.text("User Name: <?= $orderDetails['user_name'] ?>", 20, 40);
            doc.text("Address: <?= $orderDetails['user_address'] ?>", 20, 50);
            doc.text("Mobile: <?= $orderDetails['user_mobile'] ?>", 20, 60);

            let yPosition = 80;
            doc.text("Products Ordered:", 20, yPosition);

            const header = ["Product Name", "Weight", "Price"];
            let startX = 20;
            let startY = yPosition + 10;
            const colWidths = [80, 40, 40];

            doc.setFontSize(10);
            doc.setFont("helvetica", "bold");
            for (let i = 0; i < header.length; i++) {
                doc.text(header[i], startX + colWidths[i] * i, startY);
            }

            doc.setFont("helvetica", "normal");
            let rowHeight = 10;
            yPosition = startY + rowHeight;

            <?php foreach ($orderItems as $item): ?>
                doc.text("<?= $item['product_name'] ?>", startX, yPosition);
                doc.text("<?= $item['weight'] ?>", startX + colWidths[0], yPosition);
                doc.text("<?= formatCurrency($item['price']) ?>", startX + colWidths[0] + colWidths[1], yPosition);
                yPosition += rowHeight;
            <?php endforeach; ?>

            const totalAmount = 'Total Amount: <?= formatCurrency($orderDetails['total_amount']) ?>';
            const pageWidth = doc.internal.pageSize.width;
            const margin = 20;
            const totalWidth = doc.getTextWidth(totalAmount);
            const xPosition = (pageWidth - totalWidth) / 2;

            doc.setFontSize(12);
            doc.setFont("helvetica", "bold");
            doc.text(totalAmount, xPosition, yPosition + 20);

            doc.save('order_<?= $orderDetails['id'] ?>.pdf');
        }
    </script>
</body>
</html>
