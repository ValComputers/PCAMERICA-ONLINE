<?php
require 'DB.php'; // Include the database connection

$searchText = $_POST['searchText'] ?? null;

try {
    // Use the existing $conn object from DB.php
    if ($searchText === null || trim($searchText) === '') {
        // Fetch all items, replace NULL with blank
        $query = "
            SELECT 
                ISNULL(ItemNum, '') AS ItemNum, 
                ISNULL(ItemName, '') AS ItemName, 
                ISNULL(ItemName_Extra, '') AS ItemName_Extra, 
                ISNULL(Price, 0) AS Price, 
                ISNULL(In_Stock, 0) AS In_Stock, 
                ISNULL(Vendor_Part_Num, '') AS Vendor_Part_Num 
            FROM Inventory";
        $stmt = $conn->query($query);
    } else {
        // Fetch filtered items, replace NULL with blank
        $query = "
            SELECT 
                ISNULL(ItemNum, '') AS ItemNum, 
                ISNULL(ItemName, '') AS ItemName, 
                ISNULL(ItemName_Extra, '') AS ItemName_Extra, 
                ISNULL(Price, 0) AS Price, 
                ISNULL(In_Stock, 0) AS In_Stock, 
                ISNULL(Vendor_Part_Num, '') AS Vendor_Part_Num 
            FROM Inventory 
            WHERE ItemNum LIKE ? OR ItemName LIKE ? OR ItemName_Extra LIKE ? OR Vendor_Part_Num LIKE ?";
        $stmt = $conn->prepare($query);

        // Bind parameters
        $searchParam = '%' . $searchText . '%';
        $stmt->execute([$searchParam, $searchParam, $searchParam, $searchParam]);
    }

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch results
    echo json_encode($items); // Return results as JSON
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]); // Handle and return errors
}
