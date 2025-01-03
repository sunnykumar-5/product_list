<?php
session_start();
require_once('vendor/autoload.php'); // Include TCPDF library

// Initialize the cart if not already done
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Add the product to the cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'], $_POST['weight'], $_POST['price'])) {
    $product = [
        'name' => $_POST['name'],
        'weight' => $_POST['weight'],
        'price' => floatval($_POST['price']), // Ensure price is treated as a number
    ];

    // Check if the item already exists in the cart
    $found = false;
    foreach ($_SESSION['cart'] as &$cartItem) {
        if ($cartItem['name'] === $product['name']) {
            $cartItem['weight'] += $product['weight']; // Add weight
            $cartItem['price'] += $product['price']; // Add price
            $found = true;
            break;
        }
    }
    if (!$found) {
        $_SESSION['cart'][] = $product;
    }
}

// Remove a specific product from the cart
if (isset($_POST['remove_item'])) {
    $index = $_POST['remove_item'];
    unset($_SESSION['cart'][$index]);
    $_SESSION['cart'] = array_values($_SESSION['cart']); // Reindex array after removing item
}

// Display cart contents
$cartItems = $_SESSION['cart'];
$totalAmount = array_sum(array_column($cartItems, 'price'));

// Error messages for empty fields
$nameError = $addressError = $mobileError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_pdf'])) {
    $userName = $_POST['user_name'] ?? '';
    $userAddress = $_POST['user_address'] ?? '';
    $userMobile = $_POST['user_mobile'] ?? '';

    // Validate user information
    if (empty($userName)) {
        $nameError = "Name is required.";
    }
    if (empty($userAddress)) {
        $addressError = "Address is required.";
    }
    if (empty($userMobile)) {
        $mobileError = "Mobile Number is required.";
    }

    if (empty($nameError) && empty($addressError) && empty($mobileError)) {
        ob_start(); // Start output buffering to prevent errors
        $pdf = new TCPDF();
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 12);

        // PDF Header with "King Tiger"
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'King Tiger', 0, 1, 'C'); // Centered header
        $pdf->Ln(10); // Line break

        // User Details in PDF
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'Name: ' . htmlspecialchars($userName), 0, 1);
        $pdf->Cell(0, 10, 'Address: ' . htmlspecialchars($userAddress), 0, 1);
        $pdf->Cell(0, 10, 'Mobile: ' . htmlspecialchars($userMobile), 0, 1);
        $pdf->Ln(5);

        // Cart items table
        $html = '<table border="1" cellpadding="5">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Weight/Quantity</th>
                            <th>Price</th>
                        </tr>
                    </thead>
                    <tbody>';
        foreach ($cartItems as $item) {
            $html .= '<tr>
                        <td>' . htmlspecialchars($item['name']) . '</td>
                        <td>' . htmlspecialchars($item['weight']) . '</td>
                        <td>₹' . number_format($item['price'], 2) . '</td> <!-- Display price with ₹ -->
                      </tr>';
        }
        $html .= '<tr>
                    <td colspan="2"><strong>Total</strong></td>
                    <td><strong>₹' . number_format($totalAmount, 2) . '</strong></td> <!-- Display total with ₹ -->
                  </tr>';
        $html .= '</tbody></table>';

        $pdf->writeHTML($html);
        ob_end_clean(); // Clean output buffer before generating PDF
        $_SESSION['cart'] = []; // Clear the cart after PDF generation
        $pdf->Output('cart.pdf', 'D'); // Download PDF
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
            margin: 0;
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
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
        input {
            display: block;
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        input.error {
            border-color: red;
        }
        button {
            padding: 5px 10px;
            background-color: #704214;
            color: white;
            border: none;
            cursor: pointer;
            margin: 5px 0;
        }
        .error-message {
            color: red;
            font-size: 0.9em;
        }
        .whatsapp-btn {
            background-color: #25d366;
            color: white;
            border-radius: 5px;
            text-align: center;
            padding: 10px 15px;
            display: inline-block;
            cursor: pointer;
            margin-left: 10px;
            text-decoration: none;
        }
        .whatsapp-btn:hover {
            background-color: #128C7E;
        }

        /* Media Queries for Responsiveness */
        @media (max-width: 768px) {
            table th, table td {
                padding: 8px;
            }
            input, button {
                padding: 8px;
            }
            .whatsapp-btn {
                padding: 8px 12px;
            }
        }

        @media (max-width: 480px) {
            table th, table td {
                padding: 6px;
            }
            input, button {
                padding: 6px;
            }
            .whatsapp-btn {
                padding: 6px 10px;
            }
        }
    </style>
</head>
<body>
    <h1>Your Cart</h1>
    <table>
        <thead>
            <tr>
                <th>Product Name</th>
                <th>Weight/Quantity</th>
                <th>Price</th>
                <th>Remove</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($cartItems)): ?>
                <?php foreach ($cartItems as $index => $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td><?= htmlspecialchars($item['weight']) ?></td>
                    <td>₹<?= number_format($item['price'], 2) ?></td>
                    <td>
                        <form method="post">
                            <button type="submit" name="remove_item" value="<?= $index ?>">Remove</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="3"><strong>Total Amount</strong></td>
                    <td><strong>₹<?= number_format($totalAmount, 2) ?></strong></td>
                </tr>
            <?php else: ?>
            <tr>
                <td colspan="4">Your cart is empty.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <form method="post">
        <h3>Enter Your Details</h3>
        <div>
            <input type="text" name="user_name" placeholder="Name" class="<?= !empty($nameError) ? 'error' : '' ?>" value="<?= htmlspecialchars($_POST['user_name'] ?? '') ?>">
            <?php if (!empty($nameError)): ?>
                <span class="error-message"><?= $nameError ?></span>
            <?php endif; ?>
        </div>
        <div>
            <input type="text" name="user_address" placeholder="Address" class="<?= !empty($addressError) ? 'error' : '' ?>" value="<?= htmlspecialchars($_POST['user_address'] ?? '') ?>">
            <?php if (!empty($addressError)): ?>
                <span class="error-message"><?= $addressError ?></span>
            <?php endif; ?>
        </div>
        <div>
            <input type="text" name="user_mobile" placeholder="Mobile Number" class="<?= !empty($mobileError) ? 'error' : '' ?>" value="<?= htmlspecialchars($_POST['user_mobile'] ?? '') ?>">
            <?php if (!empty($mobileError)): ?>
                <span class="error-message"><?= $mobileError ?></span>
            <?php endif; ?>
        </div>
        <button type="submit" name="generate_pdf">Generate PDF</button>

        <!-- WhatsApp Button -->
        <a href="https://wa.me/8809429011?text=I%20have%20generated%20the%20PDF%20and%20I%20want%20to%20send%20it%20to%20you." target="_blank" class="whatsapp-btn">
            Send via WhatsApp generate Your product pdf
        </a>
    </form>

    <a href="index.php"><button>Back to Products</button></a>
</body>
</html>
