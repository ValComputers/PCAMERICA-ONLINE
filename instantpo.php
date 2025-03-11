<?php
// Include database connection
require 'db.php';  

if (!isset($conn)) {
    die(json_encode(["success" => false, "error" => "Database connection error: PDO not initialized."]));
}

// Set response type to JSON
header("Content-Type: application/json");

// Static variables
$Cashier_ID = '100101';
$Store_ID = '1001';
$TransType = 'R';
$datetime = date('Y-m-d H:i:s.000'); // Get current datetime in required format
$dirty = '1';

// Get JSON input from AJAX request
$jsonData = json_decode(file_get_contents("php://input"), true);

// Extract variables from JSON
$poAdjustmentDesc = isset($jsonData['poAdjustmentDesc']) ? trim($jsonData['poAdjustmentDesc']) : '';
$poNumberReceived = isset($jsonData['poNumberReceived']) ? (int) trim($jsonData['poNumberReceived']) : 0;
$poCostPer = isset($jsonData['poCostPer']) ? (float) trim($jsonData['poCostPer']) : 0;
$itemNum = isset($jsonData['itemNum']) ? trim($jsonData['itemNum']) : '';

// Ensure all fields are provided
if (empty($poAdjustmentDesc) || empty($poNumberReceived) || empty($poCostPer) || empty($itemNum)) {
    echo json_encode(["success" => false, "error" => "All fields are required!"]);
    exit;
}

try {
    // Start transaction
    $conn->beginTransaction();

    // Fetch current In_Stock and Cost from the database
    $sql = "SELECT In_Stock, Cost FROM Inventory WHERE ItemNum = :itemNum";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':itemNum', $itemNum, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        echo json_encode(["success" => false, "error" => "Item not found in inventory!"]);
        exit;
    }

    $instock = (int) $result['In_Stock'];
    $current_avg_cost = (float) $result['Cost'];

    // Compute new average cost, preventing division by zero
    if (($instock + $poNumberReceived) > 0) {
        $avg_cost = (($instock * $current_avg_cost) + ($poNumberReceived * $poCostPer)) / ($instock + $poNumberReceived);
    } else {
        $avg_cost = $poCostPer;
    }

    $NewStock = $instock + $poNumberReceived;

    // Insert into Inventory_IN table (tracking purchase orders)
    $sql_insert = "INSERT INTO Inventory_IN (Cashier_ID, CostPer, Description, ItemNum, Quantity, Store_ID, TransType, Dirty, DateTime) 
                   VALUES (:Cashier_ID, :poCostPer, :poAdjustmentDesc, :itemNum, :poNumberReceived, :Store_ID, :TransType, :dirty, 
                   DATEADD(MILLISECOND, -DATEPART(MILLISECOND, GETDATE()), GETDATE()))";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bindParam(':Cashier_ID', $Cashier_ID, PDO::PARAM_STR);
    $stmt_insert->bindParam(':poCostPer', $poCostPer, PDO::PARAM_STR);
    $stmt_insert->bindParam(':poAdjustmentDesc', $poAdjustmentDesc, PDO::PARAM_STR);
    $stmt_insert->bindParam(':itemNum', $itemNum, PDO::PARAM_STR);
    $stmt_insert->bindParam(':poNumberReceived', $poNumberReceived, PDO::PARAM_INT);
    $stmt_insert->bindParam(':Store_ID', $Store_ID, PDO::PARAM_STR);
    $stmt_insert->bindParam(':TransType', $TransType, PDO::PARAM_STR);
    $stmt_insert->bindParam(':dirty', $dirty, PDO::PARAM_STR);
    $stmt_insert->execute();

    // Update Inventory table (update stock and cost)
    $sql_update = "UPDATE Inventory SET Cost = :avg_cost, In_Stock = :NewStock WHERE ItemNum = :itemNum";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bindParam(':avg_cost', $avg_cost, PDO::PARAM_STR);
    $stmt_update->bindParam(':NewStock', $NewStock, PDO::PARAM_INT);
    $stmt_update->bindParam(':itemNum', $itemNum, PDO::PARAM_STR);
    $stmt_update->execute();

    // Commit transaction
    $conn->commit();

    // Return JSON response
    echo json_encode([
        "success" => true, 
        "message" => "Instant PO for Item: {$itemNum} Processed",
        "NewStock" => $NewStock,
        "NewAvgCost" => number_format($avg_cost, 2)
    ]);
} catch (PDOException $e) {
    // Rollback in case of error
    $conn->rollBack();
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>
