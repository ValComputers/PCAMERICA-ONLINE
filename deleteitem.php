<?php
// Include database connection from db.php
require 'db.php';

header("Content-Type: application/json");

// Decode the JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Check if data is valid and contains itemNum
if (!isset($data['itemNum']) || empty($data['itemNum'])) {
    echo json_encode(["success" => false, "error" => "Invalid or missing itemNum."]);
    http_response_code(400);
    exit;
}

$itemNum = $data['itemNum'];

try {
    // Prepare and execute the update query (soft delete)
    $updateQuery = "UPDATE Inventory SET IsDeleted = 1 WHERE ItemNum = ?";
    $stmt = $conn->prepare($updateQuery);
    
    if ($stmt->execute([$itemNum])) {
        echo json_encode(["success" => true, "message" => "ItemNum $itemNum marked as deleted."]);
    } else {
        echo json_encode(["success" => false, "error" => "Error: Could not update IsDeleted field."]);
        http_response_code(500);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
    http_response_code(500);
}
?>
