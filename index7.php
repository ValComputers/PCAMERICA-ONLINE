<?php
session_start();

// Set timeout duration (600 seconds = 10 minutes)
$timeoutDuration = 600;

// Check if the user is logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if (isset($_SESSION['last_activity'])) {
        // Calculate elapsed time since last activity
        $elapsedTime = time() - $_SESSION['last_activity'];

        if ($elapsedTime > $timeoutDuration) {
            // Timeout, destroy session, and redirect to logout
            session_unset();
            session_destroy();
            echo "<script>window.location.href = 'logout.php?timeout=true';</script>";
            exit;
        }
    }

    // Update last activity timestamp
    $_SESSION['last_activity'] = time();
} else {
    // If not logged in, redirect to login page
    header('Location: login.php');
    exit;
}

// Include database configuration
require 'db.php';

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

// Check if there's a selected department ID from POST, GET, or a predefined variable
$selectedDeptId = isset($_POST['selectedDeptId']) ? $_POST['selectedDeptId'] : null;

// Query to get all department names and IDs
$departmentsQuery = "SELECT Dept_ID, Description FROM Departments";
$stmt = $conn->query($departmentsQuery);
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Query to get all department names and IDs for Inventory Modual Popup
        $IMPdepartmentsQuery = "SELECT Dept_ID, Description FROM Departments";
        $IMPdepartments = [];

        $stmt = $conn->query($IMPdepartmentsQuery);
        $IMPdepartments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Query to get all Vendor names and Company Names for Inventory Modual Popup
        $IMPvendorsQuery = "SELECT Vendor_Number, Company FROM Vendors";
        $IMPvendors = [];

        $stmt = $conn->query($IMPvendorsQuery);
        $IMPvendors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Query to get all Category names and IDs for Inventory Modual Popup
        $IMPcategoriesQuery = "SELECT ID, Cat_ID FROM Categories_Reference";
        $IMPcategories = [];

        $stmt = $conn->query($IMPcategoriesQuery);
        $IMPcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php

// Define the query parameters
$ud_id = 'RONLORD';
$store_id = '1001';
$description_filter = 'NITROSELLENABLED';

try {
    // Prepare the SQL query to fetch only the Type column
    $query = "SELECT Type FROM User_Defined 
              WHERE UD_ID = :ud_id 
              AND Store_ID = :store_id 
              AND Description = :description";
    $stmt = $conn->prepare($query);

    // Bind parameters to the query
    $stmt->bindParam(':ud_id', $ud_id);
    $stmt->bindParam(':store_id', $store_id);
    $stmt->bindParam(':description', $description_filter);

    // Execute the query
    $stmt->execute();

    // Fetch the result
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Set the value of $showOnline dynamically
    if ($result && isset($result['Type'])) {
        $showOnline = $result['Type'];
    } else {
        $showOnline = 0; // Default value if no results found
    }

    // Optionally, print the value to debug
    // echo "showOnline: " . $showOnline;
} catch (PDOException $e) {
    echo "Query failed: " . $e->getMessage();
}
?>

<?php

/**
 * Recursive function to selectively assign JSON data to variables
 * 
 * @param array $data The JSON data to traverse
 * @param string $prefix For nested keys, use this as a prefix
 * @param array $keysToAssign Keys for which variables should be set
 * @param array &$variables Reference to the variable array
 */
function assignSelectedJsonToVariables($data, $prefix = '', $keysToAssign = [], &$variables = []) {
    foreach ($data as $key => $value) {
        $variableName = $prefix . $key;

        if (is_array($value)) {
            // If the value is an array, recursively call the function
            assignSelectedJsonToVariables($value, $variableName . '_', $keysToAssign, $variables);
        } else {
            // Only assign if the key is in the list of keys to assign
            if (in_array($variableName, $keysToAssign)) {
                $variables[$variableName] = $value;
            }
        }
    }
}

try {
    // Query to fetch JSON from the `Type` column
    $sql = "SELECT Type FROM User_Defined WHERE UD_ID = 'STORCONFIG'";
    $stmt = $conn->query($sql);

    // Fetch the result
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $jsonString = $row['Type']; // Assuming 'Type' column contains JSON string

        // Decode the JSON string
        $data = json_decode($jsonString, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            // Specify the keys you want to set as variables
            $keysToAssign = [
                'tax_CustomTax',
                'InventorySetting_IsTagEnabled',
                'InventorySetting_TagItems_0_Item',
                'InventorySetting_TagItems_1_Item'
            ];

            // Dynamic variable storage
            $variables = [];
            assignSelectedJsonToVariables($data, '', $keysToAssign, $variables);

            // Extract variables for easy access
            extract($variables);

        } else {
            echo "JSON Decode Error: " . json_last_error_msg();
        }
    } else {
        echo "No results found.";
    }
} catch (PDOException $e) {
    echo "Query failed: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Maintenance</title>
    <link rel="stylesheet" href="styles30.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="scripts2a.js"></script>
</head>
<body>
<div class="container">
    <h2>Inventory Maintenance</h2>

    <!-- Top Section -->
    <div class="item-section">
        <div class="item-label">
            Item: <span class="item-value" id="descriptionSpan">N/A</span>
        </div>
        <div class="buttons-section">
            <button class="btn-print" title="Not Currently Active">Print Labels</button>
            <button class="btn-keyboard" title="Show Onscreen Keyboard" onclick="openKeyboard()">Keyboard</button>
        </div>
    </div>

    <div class="main-container">
    <!-- Left Section -->
    <div class="inline-group">
        <div class="form-column">
            <div class="form-group">
                <label for="department" title="Item Department">Department</label>
                <select name="department" id="department">
                <?php foreach ($departments as $department): ?>
                <option value="<?php echo htmlspecialchars($department['Dept_ID']); ?>"
                <?php echo ($department['Dept_ID'] == $selectedDeptId) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($department['Description']); ?>
                </option>
                <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="itemNum" title="Item Number / SKU">Item Number</label>
                <input type="text" id="itemNum" name="itemNum" maxlength="20" readonly>
            </div>
            <div class="form-group">
                <label for="description" title="Item Description">Description</label>
                <input type="text" id="description" name="description" maxlength="30" onclick="setCurrentField(this)">
            </div>
            <div class="form-group">
                <label for="secondDescription" title="Item 2nd Description">2nd Description</label>
                <input type="text" id="secondDescription" name="secondDescription" value="" maxlength="40" onclick="setCurrentField(this)">
            </div>
        </div>
        <div class="form-column">
            <div class="form-group">
                <label for="avgCost" title="Item Average Cost">Avg Cost</label>
                <input type="number" id="avgCost" name="avgCost" value="" maxlength="13" readonly>
            </div>

            <div class="form-group">
                <label for="priceCharge" title="Price of Item">Price You Charge</label>
                <input type="number" id="priceCharge" name="priceCharge" value="" onclick="setCurrentField(this)">
                <input type="hidden" id="totalTaxAmount" value="">
            </div>

            <div class="form-group">
                <label for="priceTax" title="Item Price with Tax">Price With Tax</label>
                <input type="number" id="priceTax" name="priceTax" value="" onclick="setCurrentField(this)">
            </div>
            <div class="form-group">
                <label for="inStock" title="Currently In Stock">In Stock</label>
                <input type="number" id="inStock" name="inStock" value="" maxlength="10" readonly>
            </div>
        </div>
    </div>
    
    <!-- Right Section (Tax Section) -->
    <div class="tax-section">
    <div class="tax-checkbox-group">
        <input type="checkbox" id="tax1" name="tax1">
        <label for="tax1" title="Enable Tax 1"><?php echo $Tax1_Name ?? 'Tax 1'; ?></label>
    </div>
    <div class="tax-checkbox-group">
        <input type="checkbox" id="tax4" name="tax4">
        <label for="tax4" title="Enable Tax 4"><?php echo $Tax4_Name ?? 'Tax 4'; ?></label>
    </div>
    <div class="tax-checkbox-group">
        <input type="checkbox" id="tax2" name="tax2">
        <label for="tax2" title="Enable Tax 2"><?php echo $Tax2_Name ?? 'Tax 2'; ?></label>
    </div>
    <div class="tax-checkbox-group">
        <input type="checkbox" id="tax5" name="tax5">
        <label for="tax5" title="Enable Tax 5"><?php echo $Tax5_Name ?? 'Tax 5'; ?></label>
    </div>
    <div class="tax-checkbox-group">
        <input type="checkbox" id="tax3" name="tax3">
        <label for="tax3" title="Enable Tax 3"><?php echo $Tax3_Name ?? 'Tax 3'; ?></label>
    </div>
    <div class="tax-checkbox-group">
        <input type="checkbox" id="tax6" name="tax6">
        <label for="tax6" title="Enable Tax 6"><?php echo $Tax6_Name ?? 'Tax 6'; ?></label>
    </div>
    <div class="tax-checkbox-group">
        <input type="checkbox" id="barTax" name="barTax">
        <label for="barTax" title="Enable Bar Tax">Bar Tax</label>
    </div>
    <?php if ($tax_CustomTax == 8): ?>
    <div class="fixed-tax-group">
        <label for="fixedTax" title="Set a Fixed Tax Amount">Fixed Tax</label>
        <input type="number" id="fixedTax" name="fixedTax" step="0.01" value="" onclick="setCurrentField(this)">
    </div>
    <?php endif; ?>
</div>
    </div>
    <!-- Middle Section -->
    <div class="middle-section">
        <!-- Tab links -->
        <div class="tab">
            <button class="tablinks" title="Item Options" onclick="openTab(event, 'Options')" id="defaultOpen">Options</button>
            <button class="tablinks" title="Item Additional Info" onclick="openTab(event, 'AdditionalInfo')">Additional Info</button>
            <?php if ($showOnline == 1): ?><button class="tablinks" title="Item Online Attributes" onclick="openTab(event, 'OnlineAttributes')">Online Attributes</button><?php endif; ?>
        </div>

        <!-- Tab content -->
        <div id="Options" class="tab-content active">
            <div class="inline-group">
                <!-- Left Column -->
                <div class="left-column">
                    <div class="input-row">
                        <label for="bonusPoints" title="Bonus Points for Item">Bonus Points</label>
                        <input type="number" id="bonusPoints" name="bonusPoints" value="" maxlength="10" onclick="setCurrentField(this)">
                    </div>
                    <div class="input-row">
                        <label for="barcodes" title="Number of Barcodes"># Barcodes</label>
                        <input type="number" id="barcodes" name="barcodes" value="" maxlength="10" onclick="setCurrentField(this)">
                    </div>
                    <div class="input-row">
                    <label for="commissionOptions" title="Commission">Commission</label>
                    <!-- Dropdown box for selecting commission options -->
                    <select id="commissionOptions" name="commissionOptions" style="width: 125px;">
                    <option value="0">% of Gross Profit</option>
                    <option value="1">% of Gross Sales</option>
                    <option value="2">Amount</option>
                    </select>
                    <div style="width: 5px; display: inline-block;"></div>
                    <!-- Input box for commission value placed below the dropdown -->
                    <input type="text" id="commission" name="commission" value="" style="width: 60px;" onclick="setCurrentField(this)">
                    </div>
                    <div class="input-row">
                        <label for="location" title="Item Location">Location</label>
                        <input type="text" id="location" name="location" value="" maxlength="20" onclick="setCurrentField(this)">
                    </div>
                    <div class="input-row">
                        <label for="unitSize" title="Item Unit Size">Unit Size</label>
                        <input type="text" id="unitSize" name="unitSize" value="" maxlength="10" onclick="setCurrentField(this)">
                    </div>
                    <div class="input-row">
                        <label for="unitType" title="Item Unit Type">Unit Type</label>
                        <input type="text" id="unitType" name="unitType" value="" maxlength="10" onclick="setCurrentField(this)">
                    </div>
                </div>

                <!-- Right Column -->
                <div class="right-column">
                    <div class="tag-group-container">
                        <!-- Alternate SKUs Section -->
    <div class="tag-group">
        <label for="alternateSKUs" title="Alternate Item SKUs">Alternate SKUs</label>
        <form method="post">
            <div class="line-container" id="alternateSKUs"></div>
            <input type="hidden" id="selected_alternate" name="selected_alternate">
            <div class="tag-buttons">
        <button type="button" style="margin-left: 15px;" onclick="openPopupmulti('alternate')">Add</button>
        <button class="delete-btn" name="delete_alternate">Delete</button>
            </div>
        </form>
    </div>

                        <!-- Tag Along Items Section -->
    <div class="tag-group">
        <label for="tagAlongItems" title="Tag Along Items">Tag Along Items</label>
        <form method="post">
            <div class="line-container" id="tagAlongItems"></div>
            <input type="hidden" id="selected_tagAlong" name="selected_tagAlong">
            <div class="tag-buttons">
        <button type="button" style="margin-left: 15px;" onclick="openPopupmulti('tagAlong')">Add</button>
        <button class="delete-btn" name="delete_tagAlong">Delete</button>
            </div>
        </form>
    </div>
                    </div>

                    <!-- Hidden Popup Form -->
                    <form id="popup_form" method="post" style="display: none;">
                <input type="hidden" id="popup_input" name="popup_input">
                <input type="hidden" id="popup_form_type" name="popup_form_type">
                <input type="hidden" name="popup_add" value="1">
                    </form>
                    <!-- Checkboxes Section -->
                    <div class="checkboxes-right">
                        <div class="checkbox-group">
                            <input type="checkbox" id="modifierItem" name="modifierItem">
                            <label for="modifierItem">Modifier Item</label>
                        </div> 
                        <div class="checkbox-group">
                            <input type="checkbox" id="disableItem" name="disableItem">
                            <label for="disableItem">Disable This Item</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" id="foodstampable" name="foodstampable">
                            <label for="foodstampable">Foodstampable</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" id="excludeAccount" name="excludeAccount">
                            <label for="excludeAccount">Exclude from Account Limit</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" id="promptQuantity" name="promptQuantity">
                            <label for="promptQuantity">Prompt Quantity</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" id="autoWeigh" name="autoWeigh">
                            <label for="autoWeigh">Auto-Weigh</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" id="checkID" name="checkID">
                            <label for="checkID">Check ID Before Selling</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" id="promptPrice" name="promptPrice">
                            <label for="promptPrice">Prompt Price</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" id="useSerialBatch" name="useSerialBatch">
                            <label for="useSerialBatch">Use Serial/Batch #</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" id="checkID2" name="checkID2">
                            <label for="checkID2">Check ID #2 Before Selling</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" id="allowBuyback" name="allowBuyback">
                            <label for="allowBuyback">Allow Buyback</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" id="specialPermission" name="specialPermission">
                            <label for="specialPermission">Special Permission</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" id="countThisItem" name="countThisItem">
                            <label for="countThisItem">Count This Item</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" id="printOnReceipt" name="printOnReceipt">
                            <label for="printOnReceipt">Print on Receipt</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab content for Additional Info -->
        <div id="AdditionalInfo" class="tab-content">
            <div class="inline-group">
                <div class="form-column3">
                    <div class="form-group3">
                        <input type="checkbox" id="promptCompletionDate" name="promptCompletionDate">
                        <label for="promptCompletionDate">Prompt Completion Date</label>
                    </div>
                    <div class="form-group3">
                        <input type="checkbox" id="promptInvoiceNotes" name="promptInvoiceNotes">
                        <label for="promptInvoiceNotes">Prompt Invoice Notes</label>
                    </div>
                    <div class="form-group3">
                        <input type="checkbox" id="promptDescription" name="promptDescription">
                        <label for="promptDescription">Prompt Description</label>
                    </div>
                    <div class="form-group3">
                        <input type="checkbox" id="sellAsIs" name="sellAsIs"> 
                        <label for="sellAsIs">Sell 'As Is'</label>
                    </div>
                    <div class="form-group3">
                        <input type="checkbox" id="requireCustomer" name="requireCustomer">
                        <label for="requireCustomer">Require Customer</label>
                    </div>
                    <div class="form-group">
                        <label for="promptDescriptionOver">Prompt for Description Over</label>
                        <input type="text" id="promptDescriptionOver" name="promptDescriptionOver" value="" onclick="setCurrentField(this)">
                    </div>
                    <div class="form-group">
                        <label for="limitQtyOnInvoice">Limit Qty on Invoice</label>
                        <input type="number" id="limitQtyOnInvoice" name="limitQtyOnInvoice" value="" maxlength="10" onclick="setCurrentField(this)">
                    </div>
                </div>

                <!-- Right Column -->
                <div class="form-column3">
                    <div class="form-group3">
                        <input type="checkbox" id="excludeFromLoyaltyPlan" name="excludeFromLoyaltyPlan">
                        <label for="excludeFromLoyaltyPlan">Exclude from Loyalty Plan</label>
                    </div>
                    <div class="form-group3">
                        <input type="checkbox" id="printTicket" name="printTicket">
                        <label for="printTicket">Print Ticket</label>
                    </div>
                    <div class="form-group3">
                        <input type="checkbox" id="scaleSingleDeduct" name="scaleSingleDeduct">
                        <label for="scaleSingleDeduct">Scale Single Deduct</label>
                    </div>
                    <div class="form-group3">
                        <input type="checkbox" id="allowReturns" name="allowReturns">
                        <label for="allowReturns">Allow Returns</label>
                    </div>
                    <div class="form-group3">
                        <input type="checkbox" id="liabilityItem" name="liabilityItem">
                        <label for="liabilityItem">Liability Item</label>
                    </div>
                    <div class="form-group3">
                        <input type="checkbox" id="allowOnDepositInvoices" name="allowOnDepositInvoices">
                        <label for="allowOnDepositInvoices">Allow on Deposit Invoices</label>
                    </div>
                    <div class="form-group3">
                        <input type="checkbox" id="allowOnFleetCard" name="allowOnFleetCard">
                        <label for="allowOnFleetCard">Allow on Fleet Card</label>
                    </div>
                    <div class="form-group3">
                        <input type="checkbox" id="displayTaxInPrice" name="displayTaxInPrice">
                        <label for="displayTaxInPrice">Display Tax in Price</label>
                    </div>
                    <div class="form-group3">
                        <input type="checkbox" id="disableInventoryUpload" name="disableInventoryUpload">
                        <label for="disableInventoryUpload">Disable Inventory Upload</label>
                    </div>
                </div>

                <!-- Scale Item Type Section -->
                <div class="form-column2">
                    <fieldset>
    <legend>Scale Item Type</legend>

    <div class="form-group3">
        <input type="radio" id="soldByPiece" name="scaleItemType" value="piece">
        <label for="soldByPiece">Sold by Piece</label>
    </div>

    <div class="form-group3">
        <input type="radio" id="weighedOnScale" name="scaleItemType" value="weighedOnScale">
        <label for="weighedOnScale">Weighed on Scale</label>
    </div>

    <div class="form-group3">
        <input type="radio" id="weighedWithTare" name="scaleItemType" value="weighedWithTare">
        <label for="weighedWithTare">Weighed with Tare</label>
    </div>

    <div class="form-group3">
        <input type="radio" id="barcoded" name="scaleItemType" value="barcoded">
        <label for="barcoded">Barcoded</label>
    </div>

    <div class="form-group3">
        <input type="radio" id="barcodedAndSoldByPiece" name="scaleItemType" value="barcodedAndSoldByPiece">
        <label for="barcodedAndSoldByPiece">Barcoded and Sold by Piece</label>
    </div>
</fieldset>
                    <div class="form-group3">
                        <input type="checkbox" id="neverPrintInKitchen" name="neverPrintInKitchen">
                        <label for="neverPrintInKitchen">Never Print in Kitchen</label>
                    </div>
                    <div class="form-group">
                        <label for="daysValid">Days Valid</label>
                        <input type="number" id="daysValid" name="daysValid" value="" maxlength="10" onclick="setCurrentField(this)">
                    </div>
                </div>

                <!-- Discount Section -->
                <div class="discount-column">
                    <label for="discountType">Discount Type (Retail Only)</label>
                    <select id="discountType" name="discountType">
                        <option value="0">Percent</option>
                        <option value="1">Dollar Amount</option>
                    </select>
                    <div class="form-group">
                        <label for="generalLedgerNumber">General Ledger Number</label>
                        <input type="text" id="generalLedgerNumber" name="generalLedgerNumber" value="<?php echo $generalLedgerNumber ?? ''; ?>" maxlength="20" onclick="setCurrentField(this)">
                    </div>
                    <div class="form-group">
                        <label for="tag">Tag</label>
                        <select id="tag" name="tag">
                        <option value="None" <?php echo isset($tagdesc) ? ($tagdesc == 'None' ? 'selected' : '') : 'selected'; ?>>None</option>

                        

                        <option value="<?php echo $InventorySetting_TagItems_0_Item; ?>" 
                        <?php echo isset($tagdesc) && $InventorySetting_TagItems_0_Item == $tagdesc ? 'selected' : ''; ?>>
                        <?php echo $InventorySetting_TagItems_0_Item; ?>
                        </option>

                        <option value="<?php echo $InventorySetting_TagItems_1_Item; ?>" 
                        <?php echo isset($tagdesc) && $InventorySetting_TagItems_1_Item == $tagdesc ? 'selected' : ''; ?>>
                        <?php echo $InventorySetting_TagItems_1_Item; ?>
                        </option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Online Attributes Tab -->
        <div id="OnlineAttributes" class="tab-content">
            <!-- Nested Tab links -->
            <div class="tab">
                <button class="tablinks" title="Regular Fields" onclick="openNestedTab(event, 'RegularFields')" id="nestedDefaultOpen">Regular Fields</button>
                <button class="tablinks" title="Custom Fields" onclick="openNestedTab(event, 'CustomFields')">Custom Fields</button>
            </div>

            <!-- Nested Tab content for Regular Fields -->
            <div id="RegularFields" class="tab-content-nested">
                <div class="inline-group">
                    <div class="form-column">
                        <div class="form-group inline-regular">
                            <label for="webPrice">Web Price</label>
                            <input type="text" id="webPrice" name="webPrice" value="" onclick="setCurrentField(this)">
                        </div>
                        <div class="form-group inline-regular">
                            <label for="keywords">Keywords</label>
                            <input type="text" id="keywords" name="keywords" value="" maxlength="300" onclick="setCurrentField(this)">
                        </div>
                        <div class="form-group inline-regular">
                            <label for="brand">Brand</label>
                            <input type="text" id="brand" name="brand" value="" maxlength="50" onclick="setCurrentField(this)">
                        </div>
                        <div class="form-group inline-regular">
                            <label for="theme">Theme</label>
                            <input type="text" id="theme" name="theme" value="" maxlength="50" onclick="setCurrentField(this)">
                        </div>
                        <div class="form-group inline-regular">
                            <label for="subCategory">Sub Category</label>
                            <input type="text" id="subCategory" name="subCategory" value="" maxlength="50" onclick="setCurrentField(this)">
                        </div>
                        <div class="form-group inline-regular">
                            <label for="leadTime">Lead Time</label>
                            <input type="text" id="leadTime" name="leadTime" value="" maxlength="100" onclick="setCurrentField(this)">
                        </div>
                        <div class="form-group inline-regular" style="display: inline-flex; align-items: center;">
                            <label for="weight">Weight</label>
                            <input type="text" id="weight" name="weight" value="" onclick="setCurrentField(this)">
                            <span style="margin-left: 5px;">lbs</span>
                        </div>
                    </div>

                    <div class="form-column">
                        <div class="form-group inline-regular">
                        <label for="releaseDate">Release Date</label>
                        <input type="date" id="releaseDate" name="releaseDate" value="" onclick="setCurrentField(this)" style="width: 170px; height: 25px;">
                        </div>
                        <div class="form-group inline-regular">
                            <label for="priority">Priority</label>
                            <input type="number" id="priority" name="priority" value="" onclick="setCurrentField(this)">
                        </div>
                        <div class="form-group inline-regular">
                            <label for="rating">Rating</label>
                            <select id="rating" name="rating" onclick="setCurrentField(this)" style="width: 176px;">
                            <?php for ($i = 0; $i <= 5; $i++): ?>
                            <option value="<?php echo $i; ?>">
                            <?php echo $i; ?>
                            </option>
                            <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group extended-group">
                            <label for="extendedDescription" class="extended-label">Extended Description</label>
                            <textarea id="extendedDescription" name="extendedDescription" onclick="setCurrentField(this)"><?php echo $extendedDescription ?? ''; ?></textarea>
                        </div>
                    </div>

                    <!-- Right-aligned checkbox column -->
                    <div class="form-column checkbox-column2">
                        <div class="checkbox-group2">
                            <input type="checkbox" id="productOnPromotion" name="productOnPromotion">
                            <label for="productOnPromotion">Product on Promotion Or Pre-Order</label>
                        </div>
                        <div class="checkbox-group2">
                            <input type="checkbox" id="productSpecialOffer" name="productSpecialOffer">
                            <label for="productSpecialOffer">Product On Special Offer</label>
                        </div>
                        <div class="checkbox-group2">
                            <input type="checkbox" id="newProduct" name="newProduct">
                            <label for="newProduct">New Product</label>
                        </div>
                        <div class="checkbox-group2">
                            <input type="checkbox" id="discountable" name="discountable">
                            <label for="discountable">Discountable</label>
                        </div>
                        <div class="checkbox-group2">
                            <input type="checkbox" id="availableOnline" name="availableOnline">
                            <label for="availableOnline">Available Online</label>
                        </div>
                        <div class="checkbox-group2">
                            <input type="checkbox" id="notForWebSale" name="notForWebSale">
                            <label for="notForWebSale">Not for Web Sale</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Custom Fields Section -->
            <div id="CustomFields" class="tab-content-nested" style="display: none;">
                <div class="custom-inline-group">
                    <div class="custom-fields-column">
                        <div class="custom-fields-group">
                <label for="customNumber1">Custom Number1</label>
                <input type="number" id="customNumber1" name="customNumber1" value="" onclick="setCurrentField(this)">
            </div>
            <div class="custom-fields-group">
                <label for="customNumber2">Custom Number2</label>
                <input type="number" id="customNumber2" name="customNumber2" value="" onclick="setCurrentField(this)">
            </div>
            <div class="custom-fields-group">
                <label for="customNumber3">Custom Number3</label>
                <input type="number" id="customNumber3" name="customNumber3" value="" onclick="setCurrentField(this)">
            </div>
            <div class="custom-fields-group">
                <label for="customNumber4">Custom Number4</label>
                <input type="number" id="customNumber4" name="customNumber4" value="" onclick="setCurrentField(this)">
            </div>
            <div class="custom-fields-group">
                <label for="customNumber5">Custom Number5</label>
                <input type="number" id="customNumber5" name="customNumber5" value="" onclick="setCurrentField(this)">
            </div>
                        <div class="custom-fields-group">
                            <label for="subDescription1">Sub Description1</label>
                            <input type="text" id="subDescription1" name="subDescription1" value="" maxlength="70" onclick="setCurrentField(this)">
                        </div>
                        <div class="custom-fields-group">
                            <label for="subDescription2">Sub Description2</label>
                            <input type="text" id="subDescription2" name="subDescription2" value="" maxlength="70" onclick="setCurrentField(this)">
                        </div>
                    </div>

                    <div class="custom-fields-column">
                        <div class="custom-fields-group">
                            <label for="subDescription3">Sub Description3</label>
                            <input type="text" id="subDescription3" name="subDescription3" value="" maxlength="70" onclick="setCurrentField(this)">
                        </div>
                        <div class="custom-fields-group">
                            <label for="customText1">Custom Text1</label>
                            <input type="text" id="customText1" name="customText1" value="" maxlength="250" onclick="setCurrentField(this)">
                        </div>
                        <div class="custom-fields-group">
                            <label for="customText2">Custom Text2</label>
                            <input type="text" id="customText2" name="customText2" value="" maxlength="250" onclick="setCurrentField(this)">
                        </div>
                        <div class="custom-fields-group">
                            <label for="customText3">Custom Text3</label>
                            <input type="text" id="customText3" name="customText3" value="" maxlength="250" onclick="setCurrentField(this)">
                        </div>
                        <div class="custom-fields-group">
                            <label for="customText4">Custom Text4</label>
                            <input type="text" id="customText4" name="customText4" value="" maxlength="250" onclick="setCurrentField(this)">
                        </div>
                        <div class="custom-fields-group">
                            <label for="customText5">Custom Text5</label>
                            <input type="text" id="customText5" name="customText5" value="" maxlength="250" onclick="setCurrentField(this)">
                        </div>
                    </div>

                    <div class="custom-fields-column">
                        <div class="custom-fields-group">
                            <label for="customExtendedText1" class="custom-fields-extended-label">Custom<br>Extended<br>Text1</label>
                            <textarea id="customExtendedText1" name="customExtendedText1" onclick="setCurrentField(this)"></textarea>
                        </div>
                        <div class="custom-fields-group">
                            <label for="customExtendedText2" class="custom-fields-extended-label">Custom<br>Extended<br>Text2</label>
                            <textarea id="customExtendedText2" name="customExtendedText2" onclick="setCurrentField(this)"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Section (Always Visible) -->
    <div class="bottom-section">
        <div class="info-boxes">
            <div class="info-box" id="ProfitFormatted" title="Item Profit %"></div>
            <div class="info-box" id="RetailDiscountFormatted" title="Item Retail Discount"></div>
            <div class="info-box" id="GrossMarginFormatted" title="Item Gross Margin"></div>
        </div>

        <div class="bottom-buttons">
    <div class="left-buttons">
        <form id="searchItemForm">
            <div class="search-box">
                <label for="searchItem" title="Search Database for Item">Search by Item Number</label>
                <input type="text" id="searchItem" name="searchItem" placeholder="Enter Item Number" autocomplete="off" maxlength="20" onclick="setCurrentField(this)" onkeypress="if(event.key === 'Enter') this.form.submit();">
            </div>
        </form>
        <div class="nav-buttons">
            <button id="prevItem" title="Show Previous Item">Previous</button>
            <button type="button" title="Open Database Lookup" onclick="openInventoryModal()">Lookup</button>
            <button id="nextItem" title="Show Next Item">Next</button>
        </div>
    </div>
            <div class="right-buttons">
                <div class="button-row">
                    <button class="btn-green" title="Add New Item" type="button" id="addItemButton" onclick="openPopup()">Add Item</button>

                    <button class="btn-green" title="Save Current Item" type="button" id="saveButton">Save</button>

                    <button class="btn-blue" title="Not Currently Active">Transfer</button>
                    <button class="btn-purple" title="Instant Purchase Order" onclick="openPOModal()">Instant PO</button>
                </div>
                <div class="button-row">
                    <button class="btn-green" title="Create Duplicate Item">Duplicate</button>
                    <button class="btn-red" title="Delete Current Item" type="button" id="deleteButton">Delete</button>
                    <a href="logout.php"><button class="btn-red" title="Logout of Inventory Maintenance">Logout</button></a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="pomodal" class="pomodal">
    <div class="pomodal-content">
        <h2>Instant PO Details</h2>
        <form id="pomodalForm">

            <!-- Hidden Input for ItemNum -->
            <input type="hidden" id="itemNum" name="itemNum">

            <!-- Adjustment Description -->
            <label for="poAdjustmentDesc">Adjustment Description:</label>
            <input type="text" id="poAdjustmentDesc" class="styled-input" name="poAdjustmentDesc" required>
            <br>

            <!-- Number Received -->
            <label for="poNumberReceived">Number Received:</label>
            <input type="number" id="poNumberReceived" class="styled-input" name="poNumberReceived" required>
            <br>

            <!-- Cost Per -->
            <label for="poCostPer">Cost Per:</label>
            <input type="number" id="poCostPer" class="styled-input" name="poCostPer" step="0.01" required>
            <br>

            <!-- Buttons -->
            <button type="button" class="btn-orange" id="instantpoButton">Submit</button>
            <button type="button" class="btn-red" onclick="closePOModal()">Cancel</button>

        </form>
    </div>
</div>

<!-- Onscreen Keyboard Popup -->
<div id="keyboardPopup">
    <label id="keyboardInputLabel">Enter new value</label>
    <div id="keyboardInputContainer">
        <input type="text" id="keyboardInput" readonly>
        <button class="back-button" onclick="backspace()">Back</button>
        <button class="close-button" onclick="closeKeyboard()">Close</button>
    </div>

    <div class="keyboard-row">
        <button class="key-button number-button" data-key="1" onclick="addKey('1')">1</button>
        <button class="key-button number-button" data-key="2" onclick="addKey('2')">2</button>
        <button class="key-button number-button" data-key="3" onclick="addKey('3')">3</button>
        <button class="key-button number-button" data-key="4" onclick="addKey('4')">4</button>
        <button class="key-button number-button" data-key="5" onclick="addKey('5')">5</button>
        <button class="key-button number-button" data-key="6" onclick="addKey('6')">6</button>
        <button class="key-button number-button" data-key="7" onclick="addKey('7')">7</button>
        <button class="key-button number-button" data-key="8" onclick="addKey('8')">8</button>
        <button class="key-button number-button" data-key="9" onclick="addKey('9')">9</button>
        <button class="key-button number-button" data-key="0" onclick="addKey('0')">0</button>
        <button class="key-button number-button" data-key="-" onclick="addKey('-')">-</button>
    </div>
    <div class="keyboard-row">
        <button class="key-button letter-button" onclick="addKey('Q')">Q</button>
        <button class="key-button letter-button" onclick="addKey('W')">W</button>
        <button class="key-button letter-button" onclick="addKey('E')">E</button>
        <button class="key-button letter-button" onclick="addKey('R')">R</button>
        <button class="key-button letter-button" onclick="addKey('T')">T</button>
        <button class="key-button letter-button" onclick="addKey('Y')">Y</button>
        <button class="key-button letter-button" onclick="addKey('U')">U</button>
        <button class="key-button letter-button" onclick="addKey('I')">I</button>
        <button class="key-button letter-button" onclick="addKey('O')">O</button>
        <button class="key-button letter-button" onclick="addKey('P')">P</button>
    </div>
    <div class="keyboard-row">
        <button class="key-button letter-button" onclick="addKey('A')">A</button>
        <button class="key-button letter-button" onclick="addKey('S')">S</button>
        <button class="key-button letter-button" onclick="addKey('D')">D</button>
        <button class="key-button letter-button" onclick="addKey('F')">F</button>
        <button class="key-button letter-button" onclick="addKey('G')">G</button>
        <button class="key-button letter-button" onclick="addKey('H')">H</button>
        <button class="key-button letter-button" onclick="addKey('J')">J</button>
        <button class="key-button letter-button" onclick="addKey('K')">K</button>
        <button class="key-button letter-button" onclick="addKey('L')">L</button>
        <button class="key-button symbol-button" data-key=";" onclick="addKey(';')">;</button>
    </div>
    <div class="keyboard-row">
        <button class="key-button letter-button" onclick="addKey('Z')">Z</button>
        <button class="key-button letter-button" onclick="addKey('X')">X</button>
        <button class="key-button letter-button" onclick="addKey('C')">C</button>
        <button class="key-button letter-button" onclick="addKey('V')">V</button>
        <button class="key-button letter-button" onclick="addKey('B')">B</button>
        <button class="key-button letter-button" onclick="addKey('N')">N</button>
        <button class="key-button letter-button" onclick="addKey('M')">M</button>
        <button class="key-button symbol-button" data-key="," onclick="addKey(',')">,</button>
        <button class="key-button symbol-button" data-key="." onclick="addKey('.')">.</button>
        <button class="key-button symbol-button" data-key="/" onclick="addKey('/')">/</button>
    </div>
    <div class="keyboard-row">
        <button class="key-button shift-button" onclick="toggleShift()" style="width: 160px; height: 60px;">Shift</button>
        <button class="key-button space-button" onclick="addKey(' ')">Space</button>
        <button class="key-button enter-button" onclick="submitKeyboard()" style="width: 160px; height: 60px;">Enter</button>
    </div>
</div>

<!-- Inventory Modal Popup -->
<div id="inventoryModal">
    <div id="inventoryModalHeader">Search Inventory</div>
    <div id="inventoryModalContent">
        <div id="inventoryTableContainer">
            <table id="inventoryTable">
                <thead>
                    <tr>
                        <th>Item Number</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Description 2</th>
                        <th>Vendor Part Num</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Rows will be populated dynamically -->
                </tbody>
            </table>
        </div>
        <div id="inventorySidebar">
            <button>Keyboard</button>
            <button>Modifiers</button>
            <button>Modifier Groups</button>
            <button>Choice Items</button>
            <button>Kits</button>
            <button>Rentals</button>
            <button>Style Items</button>
            <button>Serial Number Items</button>
            <button>Add New Item</button>
        </div>
    </div>
    <div class="bottom-controls">
        <div class="dropdown-group">
            <div class="dropdown">
                <label for="IMPcategory" title="Item Category">Category</label>
                <select name="IMPcategory" id="IMPcategory">
                <option value="">No Category Selected</option>
                <?php foreach ($IMPcategories as $IMPcategory): ?>
                <option value="<?php echo htmlspecialchars($IMPcategory['Cat_ID']); ?>">
                <?php echo htmlspecialchars($IMPcategory['Cat_ID']); ?>
                </option>
                <?php endforeach; ?>
                </select>
            </div>
            <div class="dropdown">
                <label for="IMPdepartment" title="Item Department">Department</label>
                <select name="IMPdepartment" id="IMPdepartment">
                <option value="">No Department Selected</option>
                <?php foreach ($IMPdepartments as $IMPdepartment): ?>
                <option value="<?php echo htmlspecialchars($IMPdepartment['Dept_ID']); ?>">
                <?php echo htmlspecialchars($IMPdepartment['Description']); ?>
                </option>
                <?php endforeach; ?>
                </select>
            </div>
            <div class="dropdown">
                <label for="IMPvendor" title="Item Vendor">Vendor</label>
                <select name="IMPvendor" id="IMPvendor">
                <option value="">No Vendor Selected</option>
                <?php foreach ($IMPvendors as $IMPvendor): ?>
                <option value="<?php echo htmlspecialchars($IMPvendor['Vendor_Number']); ?>">
                <?php echo htmlspecialchars($IMPvendor['Company']); ?>
                </option>
                <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="radio-group">
            <label><input type="radio" name="searchField" value="ItemNum"> Item Number</label>
            <label><input type="radio" name="searchField" value="ItemName"> Description</label>
            <label><input type="radio" name="searchField" value="VendorPartNum"> Vendor Part Num</label>
            <label><input type="radio" name="searchField" value="Style"> Style</label>
            <label><input type="radio" name="searchField" value="MainFields"> Search Main Fields</label>
            <label><input type="radio" name="searchField" value="AltSKU"> Alt SKU</label>
        </div>
        <div class="action-section">
            <button class="select-btn">Select</button>
            <button class="cancel-btn" onclick="closeInventoryModal()">Cancel</button>
            <div class="search-section">
                <label for="searchText">Enter Search Text:</label>
                <input type="text" id="searchText">
                <button class="search-btn">Search</button>
            </div>
             <button class="help-btn" onclick="downloadHelp()">Help</button>
        </div>
    </div>
</div>

<script src="scripts24.js"></script>

</body>
</html>
