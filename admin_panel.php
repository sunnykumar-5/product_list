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

// Delete order if marked as delivered
if (isset($_POST['delivered_order_id'])) {
    $orderId = intval($_POST['delivered_order_id']);
    $conn->query("DELETE FROM orders WHERE id = $orderId");
    $conn->query("DELETE FROM order_items WHERE order_id = $orderId");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch orders
$orders = $conn->query("SELECT * FROM orders ORDER BY created_at DESC");

// Fetch order details if an order_id is provided
$orderDetails = [];
$userInfo = [];
$totalAmount = 0;

if (isset($_GET['order_id'])) {
    $orderId = intval($_GET['order_id']);

    // Fetch order items
    $orderDetailsQuery = $conn->query("SELECT * FROM order_items WHERE order_id = $orderId");
    while ($row = $orderDetailsQuery->fetch_assoc()) {
        $orderDetails[] = $row;
        $totalAmount += $row['price'];
    }

    // Fetch user details
    $userQuery = $conn->query("SELECT user_name, user_mobile FROM orders WHERE id = $orderId");
    if ($userQuery->num_rows > 0) {
        $userInfo = $userQuery->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Orders</title>
    <link rel="stylesheet" href="add.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
</head>
<body>
    <h1>Admin Panel - Orders</h1>
    <div class="container">
        <!-- Orders List -->
        <div class="orders">
            <h2>Orders List</h2>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Name</th>
                        <th>Address</th>
                        <th>Mobile</th>
                        <th>Total Amount</th>
                        <th>Details</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($orders->num_rows > 0): ?>
                        <?php while ($order = $orders->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($order['id']) ?></td>
                            <td><?= htmlspecialchars($order['user_name']) ?></td>
                            <td><?= htmlspecialchars($order['user_address']) ?></td>
                            <td><?= htmlspecialchars($order['user_mobile']) ?></td>
                            <td>₹<?= number_format($order['total_amount'], 2) ?></td>
                            <td><a href="?order_id=<?= htmlspecialchars($order['id']) ?>">View Details</a></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="delivered_order_id" value="<?= htmlspecialchars($order['id']) ?>">
                                    <button type="submit">Delivered</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="7">No orders found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Order Details -->
        <div class="order-details">
            <h2>Order Details</h2>
            <?php if (!empty($orderDetails)): ?>
                <table id="order-details-table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Weight/Quantity</th>
                            <th>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orderDetails as $detail): ?>
                        <tr>
                            <td><?= htmlspecialchars($detail['product_name']) ?></td>
                            <td><?= htmlspecialchars($detail['weight']) ?></td>
                            <td>₹<?= number_format($detail['price'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p><strong>Total Amount: ₹<?= number_format($totalAmount, 2) ?></strong></p>
                <button class="pdf-button" onclick="generatePDF()">Generate PDF</button>
            <?php else: ?>
                <p>Select an order to view its details.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        async function generatePDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            // Add title
            doc.setFontSize(16);
            doc.text('King Tiger Packaging Shop', 105, 10, { align: 'center' });

            // User info
            const userName = "<?= htmlspecialchars($userInfo['user_name'] ?? 'N/A') ?>";
            const userMobile = "<?= htmlspecialchars($userInfo['user_mobile'] ?? 'N/A') ?>";

            doc.setFontSize(12);
            doc.text('Customer Name: ' + userName, 10, 30);
            doc.text('Customer Mobile: ' + userMobile, 10, 40);

            // Order details
            let y = 50;
            doc.setFontSize(10);
            document.querySelectorAll("#order-details-table tr").forEach((row, index) => {
                let x = 10;
                row.querySelectorAll("th, td").forEach(cell => {
                    doc.text(cell.innerText, x, y);
                    x += 60;
                });
                y += 10;
                if (index === 0) doc.line(10, y - 5, 200, y - 5);
            });

            doc.text('Total: ₹<?= number_format($totalAmount, 2) ?>', 10, y + 10);

            doc.save('order-details.pdf');
        }
    </script>
</body>
</html>

<?php $conn->close(); ?>
