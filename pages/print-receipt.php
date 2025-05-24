<?php
// pages/print-receipt.php
// Receipt printing page for the Cafe Management System

// Include config file
require_once dirname(__DIR__) . '/config/config.php';

// Include database and required classes
require_once dirname(__DIR__) . '/classes/Database.php';
require_once dirname(__DIR__) . '/classes/Order.php';

// Include authentication
require_once dirname(__DIR__) . '/includes/auth.php';

// Require login to access this page
requireLogin();

// Initialize classes
$orderObj = new Order();

// Check if an order ID is provided
if (!isset($_GET['id'])) {
    // Redirect to orders page if no ID is provided
    header("Location: " . BASE_URL . "/pages/orders.php");
    exit;
}

$orderId = $_GET['id'];
$order = $orderObj->getOrderWithDetails($orderId);

if (!$order) {
    // Redirect to orders page if order not found
    header("Location: " . BASE_URL . "/pages/orders.php");
    exit;
}

// Get cafe settings for receipt
$db = Database::getInstance();
$cafeSettings = $db->selectOne("SELECT * FROM cafe_settings");

// Default settings if none found
if (!$cafeSettings) {
    $cafeSettings = [
        'cafe_name' => 'Cafe Management System',
        'cafe_address' => '',
        'cafe_phone' => '',
        'receipt_header' => 'Thank you for visiting our cafe!',
        'receipt_footer' => 'Please come again!',
        'tax_rate' => 0.10
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?php echo htmlspecialchars($order['order_id']); ?></title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            line-height: 1.4;
        }
        .receipt {
            max-width: 300px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .divider {
            border-top: 1px dashed #ccc;
            margin: 10px 0;
        }
        .item {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }
        .item-notes {
                font-size: 10px;
                font-style: italic;
                padding-left: 15px;
                color: #666;
            }
            .subtotal {
                display: flex;
                justify-content: space-between;
                margin: 5px 0;
                font-size: 11px;
            }
            .total {
                display: flex;
                justify-content: space-between;
                margin: 10px 0;
                font-weight: bold;
            }
            .footer {
                text-align: center;
                margin-top: 20px;
                font-size: 11px;
            }
            .order-info {
                font-size: 11px;
                margin-bottom: 10px;
            }
            .print-button {
                text-align: center;
                margin-top: 30px;
            }
            .print-button button {
                padding: 10px 20px;
                background-color: #854d0e;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
            }
            
            /* Hide print button when printing */
            @media print {
                .print-button {
                    display: none;
                }
                body {
                    padding: 0;
                }
            }
        </style>
    </head>
    <body>
        <div class="receipt">
            <div class="header">
                <h2 style="margin-bottom: 5px;"><?php echo htmlspecialchars($cafeSettings['cafe_name']); ?></h2>
                <?php if (!empty($cafeSettings['cafe_address'])): ?>
                <p style="margin: 3px 0;"><?php echo htmlspecialchars($cafeSettings['cafe_address']); ?></p>
                <?php endif; ?>
                <?php if (!empty($cafeSettings['cafe_phone'])): ?>
                <p style="margin: 3px 0;">Tel: <?php echo htmlspecialchars($cafeSettings['cafe_phone']); ?></p>
                <?php endif; ?>
                
                <p style="margin-top: 10px;"><?php echo htmlspecialchars($cafeSettings['receipt_header']); ?></p>
            </div>
            
            <div class="order-info">
                <div>Order: <?php echo htmlspecialchars($order['order_id']); ?></div>
                <div>Date: <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></div>
                <div>Table: <?php echo htmlspecialchars($order['table']['name']); ?></div>
                <div>Server: <?php echo htmlspecialchars($order['staff']['name']); ?></div>
            </div>
            
            <div class="divider"></div>
            
            <?php foreach ($order['items'] as $item): ?>
            <div class="item">
                <div><?php echo $item['quantity']; ?>x <?php echo htmlspecialchars($item['menu_item_name']); ?></div>
                <div><?php echo CURRENCY . ' ' . number_format($item['subtotal'], 2); ?></div>
            </div>
            <?php if (!empty($item['notes'])): ?>
            <div class="item-notes"><?php echo htmlspecialchars($item['notes']); ?></div>
            <?php endif; ?>
            <?php endforeach; ?>
            
            <div class="divider"></div>
            
            <div class="subtotal">
                <div>Subtotal:</div>
                <div><?php echo CURRENCY . ' ' . number_format($order['subtotal'], 2); ?></div>
            </div>
            
            <div class="subtotal">
                <div>Tax (<?php echo ($cafeSettings['tax_rate'] * 100) . '%'; ?>):</div>
                <div><?php echo CURRENCY . ' ' . number_format($order['tax'], 2); ?></div>
            </div>
            
            <?php if ($order['tip'] > 0): ?>
            <div class="subtotal">
                <div>Tip:</div>
                <div><?php echo CURRENCY . ' ' . number_format($order['tip'], 2); ?></div>
            </div>
            <?php endif; ?>
            
            <div class="total">
                <div>TOTAL:</div>
                <div><?php echo CURRENCY . ' ' . number_format($order['total'], 2); ?></div>
            </div>
            
            <div class="subtotal">
                <div>Payment Method:</div>
                <div>
                    <?php
                    $paymentMethod = $order['payment_method'];
                    echo $paymentMethod === 'credit_card' ? 'Credit Card' : 
                         ($paymentMethod === 'cash' ? 'Cash' : ucfirst($paymentMethod));
                    ?>
                </div>
            </div>
            
            <div class="divider"></div>
            
            <div class="footer">
                <p><?php echo htmlspecialchars($cafeSettings['receipt_footer']); ?></p>
                <p>*** Thank you for your business ***</p>
            </div>
        </div>
        
        <div class="print-button">
            <button onclick="window.print();">Print Receipt</button>
        </div>
        
        <script>
            // Auto-print when page loads (uncomment if desired)
            // window.onload = function() {
            //     window.print();
            // }
        </script>
    </body>
</html>