<?php
require 'db.php'; // Database connection

// Fetch departments for dropdown
$stmt = $conn->query("SELECT Dept_ID, Description FROM Departments");
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get the first department if available
$defaultDept = !empty($departments) ? $departments[0]['Dept_ID'] : '';

// Fetch all Tax_Rate table columns and assign them to variables
$taxRateQuery = "SELECT * FROM Tax_Rate";
$taxRateStmt = $conn->prepare($taxRateQuery);
$taxRateStmt->execute();
$taxRates = $taxRateStmt->fetch(PDO::FETCH_ASSOC);

if ($taxRates) {
    foreach ($taxRates as $column => $value) {
        ${$column} = htmlspecialchars($value); // Dynamically create variables
    }
} else {
    echo "<div class='error'>No tax rate data found.</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>New Item Entry</title>
    <link rel="stylesheet" href="additem.css"> <!-- Include your CSS file -->
</head>
<body>

<h2>Enter New Item Details</h2>

<form action="save_entry.php" method="post" onsubmit="return validateForm();">
    <div class="main-container">
        <!-- Input Fields -->
        <div class="inline-group">
            <div class="form-column">
                <div class="form-group">
                    <label for="Dept_ID">Department</label>
                    <select name="Dept_ID" id="Dept_ID">
                    <?php foreach ($departments as $department): ?>
                    <option value="<?php echo htmlspecialchars($department['Dept_ID']); ?>" 
                    <?php echo ($department['Dept_ID'] == $defaultDept) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($department['Description']); ?>
                    </option>
                    <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="itemNumber">Item Number</label>
                    <input type="text" id="ItemNumber" name="ItemNumber" value="" maxlength="20" class="form-control">
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <input type="text" id="description" name="description" value="" maxlength="30">
                </div>
                <div class="form-group">
                    <label for="secondDescription">2nd Description</label>
                    <input type="text" id="secondDescription" name="secondDescription" value="" maxlength="40">
                </div>
            </div>
            <div class="form-column">
                <div class="form-group">
                    <label for="avgCost">Avg Cost</label>
                    <input type="text" id="avgCost" name="avgCost" value="" class="form-control"
                           placeholder="0.00">
                </div>
                <div class="form-group">
                    <label for="priceCharge">Price You Charge</label>
                    <input type="text" id="priceCharge" name="priceCharge" value="" class="form-control"
                           placeholder="0.00">
                </div>
                <div class="form-group">
                    <label for="inStock">In Stock</label>
                    <input type="number" id="inStock" name="inStock" value="" maxlength="10" class="form-control">
                    </div>
                    </div>
                    </div>
                    </div>

                    <!-- Tax Section -->
                    <div class="tax-section">
                        <div class="tax-row">
                            <div class="tax-checkbox-group">
                                <input type="checkbox" id="tax1" name="tax1">
                                <label for="tax1" title="Enable Tax 1"><?php echo $Tax1_Name ?? 'Tax 1'; ?></label>
                            </div>
                            <div class="tax-checkbox-group">
                                <input type="checkbox" id="tax2" name="tax2">
                                <label for="tax2" title="Enable Tax 2"><?php echo $Tax2_Name ?? 'Tax 2'; ?></label>
                            </div>
                            <div class="tax-checkbox-group">
                                <input type="checkbox" id="tax3" name="tax3">
                                <label for="tax3" title="Enable Tax 3"><?php echo $Tax3_Name ?? 'Tax 3'; ?></label>
                            </div>
                        </div>
                        <div class="tax-row">
                            <div class="tax-checkbox-group">
                                <input type="checkbox" id="tax4" name="tax4">
                                <label for="tax4" title="Enable Tax 4"><?php echo $Tax4_Name ?? 'Tax 4'; ?></label>
                            </div>
                            <div class="tax-checkbox-group">
                                <input type="checkbox" id="tax5" name="tax5">
                                <label for="tax5" title="Enable Tax 5"><?php echo $Tax5_Name ?? 'Tax 5'; ?></label>
                            </div>
                            <div class="tax-checkbox-group">
                                <input type="checkbox" id="tax6" name="tax6">
                                <label for="tax6" title="Enable Tax 6"><?php echo $Tax6_Name ?? 'Tax 6'; ?></label>
                            </div>
                        </div>
                    </div>
                
        <!-- Additional Checkboxes Above Save Button -->
        <div class="additional-checkboxes">
            <div class="checkbox-group">
                <input type="checkbox" id="countItem" name="countItem">
                <label for="countItem">Count This Item</label>
            </div>
            <div class="checkbox-group">
                <input type="checkbox" id="printReceipt" name="printReceipt">
                <label for="printReceipt">Print on Receipt</label>
            </div>
            <div class="checkbox-group">
                <input type="checkbox" id="foodstampable" name="foodstampable">
                <label for="foodstampable">Foodstampable</label>
            </div>
        </div>

        <!-- Save & Cancel Buttons -->
        <div class="form-submit">
        <input type="submit" value="Save Item" class="save-button">
        <button type="button" class="cancel-button" onclick="window.close();">Cancel</button>
        </div>
    </div>
</form>

<script src="scriptsadd.js"></script>

</body>
</html>
