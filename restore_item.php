<?php
require 'db.php'; // Include your database connection

if (isset($_GET['itemNumber'])) {
    $itemNumber = $_GET['itemNumber'];

    // Update IsDeleted to 0
    $restoreQuery = "UPDATE Inventory SET IsDeleted = 0 WHERE ItemNum = ?";
    $restoreStmt = $conn->prepare($restoreQuery);

    if ($restoreStmt->execute([$itemNumber])) {
        echo "<script>
            alert('Item \"{$itemNumber}\" has been restored successfully.');
            window.close();
        </script>";
    } else {
        echo "<script>
            alert('Error: Could not restore the item.');
            window.history.back();
        </script>";
    }
} else {
    echo "<script>
        alert('Invalid request.');
        window.history.back();
    </script>";
}
?>
