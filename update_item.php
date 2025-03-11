<?php
require 'db.php'; // Include your MSSQL database connection

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['itemNum'])) {
    echo json_encode(["success" => false, "error" => "Item Number is missing."]);
    exit;
}

$itemNum = $data['itemNum'];
$department = $data['department'];
$description = $data['description'];
$secondDescription = $data['secondDescription'];
$avgCost = $data['avgCost'];
$priceCharge = $data['priceCharge'];  // Updated
$inStock = $data['inStock'];

try {
    $stmt = $conn->prepare("UPDATE Inventory SET 
        Dept_ID = ?, 
        ItemName = ?, 
        ItemName_Extra = ?, 
        Cost = ?, 
        Price = ?, 
        In_Stock = ?
        WHERE ItemNum = ?");
    
    $stmt->execute([$department, $description, $secondDescription, $avgCost, $priceCharge, $inStock, $itemNum]);

    echo json_encode(["success" => true]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>

