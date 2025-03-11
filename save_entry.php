<?php
require 'db.php'; // Include database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // **Retrieve Store_ID from the Setup table**
    $storeIDQuery = "SELECT Store_ID FROM Setup";
    $storeIDStmt = $conn->prepare($storeIDQuery);
    $storeIDStmt->execute();
    $storeIDResult = $storeIDStmt->fetch(PDO::FETCH_ASSOC);

    if ($storeIDResult) {
        $storeID = $storeIDResult['Store_ID'];
    } else {
        http_response_code(500);
        echo "Error: Could not retrieve Store_ID.";
        exit;
    }

    // Retrieve form inputs
    $deptID = $_POST['Dept_ID'] ?? '';
    $itemNumber = $_POST['ItemNumber'] ?? '';
    $description = $_POST['description'] ?? '';
    $secondDescription = $_POST['secondDescription'] ?? '';
    $avgCost = $_POST['avgCost'] ?? '0';
    $priceCharge = $_POST['priceCharge'] ?? '0';
    $inStock = isset($_POST['inStock']) && $_POST['inStock'] !== '' ? $_POST['inStock'] : '0';
    $dateCreated = date('Y-m-d');

    // **Ensure cost and price are valid decimal values rounded to two decimal places**
    if (!is_numeric($avgCost)) {
        $avgCost = 0.00;
    }
    if (!is_numeric($priceCharge)) {
        $priceCharge = 0.00;
    }

    $avgCost = round(floatval($avgCost), 2);
    $priceCharge = round(floatval($priceCharge), 2);

    // Tax checkboxes
    $tax1 = isset($_POST['tax1']) ? 1 : 0;
    $tax2 = isset($_POST['tax2']) ? 1 : 0;
    $tax3 = isset($_POST['tax3']) ? 1 : 0;
    $tax4 = isset($_POST['tax4']) ? 1 : 0;
    $tax5 = isset($_POST['tax5']) ? 1 : 0;
    $tax6 = isset($_POST['tax6']) ? 1 : 0;

    // New checkboxes
    $countItem = isset($_POST['countItem']) ? 1 : 0;
    $printReceipt = isset($_POST['printReceipt']) ? 1 : 0;
    $foodstampable = isset($_POST['foodstampable']) ? 1 : 0;

    // Extra Fields
    $reorderlevel = '0';
    $reorderquantity = '0';
    $iskit = '0';
    $ismodifier = '0';
    $Dirty = '1';
    $rowId = null;

    // Individual extra fields with default values
    $DOB = 0;
    $Check_ID2 = 0;
    $InStock_Committed = 0;
    $ScaleSingleDeduct = 0;
    $PricePerMeasure = 0;
    $Prompt_Price = 0;
    $Old_InStock = 0;
    $Fixed_Tax = 0;
    $RequireCustomer = 0;
    $Exclude_Acct_Limit = 0;
    $Use_Serial_Numbers = 0;
    $ItemType = 0;
    $Transfer_Cost_Markup = 0;
    $IsDeleted = 0;
    $IsRental = 0;
    $Special_Permission = 0;
    $AlcoholContent = 0;
    $Last_Sold = null;
    $ItemCategory = 0;
    $UnitMeasure = 0;
    $ReOrder_Cost = 0;
    $DiscountType = 0;
    $BarTaxInclusive = 0;
    $Retail_Price = 0;
    $DoughnutTax = 0;
    $Liability = 0;
    $SuggestedDeposit = 0;
    $Tear = 0;
    $QuantityRequired = 0;
    $ShipCompliantProductType = null;
    $Check_ID = 0;
    $AutoWeigh = 0;
    $Transfer_Markup_Enabled = 0;
    $AllowReturns = 1;
    $Inactive = 0;
    $IsMatrixItem = 0;
    $ModifierType = 0;
    $DisplayTaxInPrice = 0;
    $Allow_BuyBack = 0;
    $Print_Voucher = 0;
    $DisableInventoryUpload = 0;
    $PromptCompletionDate = 0;
    $Kit_Override = 0;
    $As_Is = 0;
    $Import_Markup = 0;
    $PromptInvoiceNotes = 0;
    $Num_Bonus_Points = 0;
    $Unit_Size = 0;
    $AllowOnFleetCard = 0;
    $Print_Ticket = 0;
    $Prompt_Quantity = 0;
    $Inv_Num_Barcode_Labels = 0;
    $Use_Bulk_Pricing = 0;
    $Num_Days_Valid = 0;
    $Prompt_Description = 0;
    $ScaleItemType = 0;

    // **Check if ItemNum already exists in Inventory**
$checkItemQuery = "SELECT IsDeleted FROM Inventory WHERE ItemNum = ?";
$checkItemStmt = $conn->prepare($checkItemQuery);
$checkItemStmt->execute([$itemNumber]);
$itemExists = $checkItemStmt->fetch(PDO::FETCH_ASSOC);

if ($itemExists) {
    if ($itemExists['IsDeleted'] == 1) {
        echo "<script>
            if (confirm('The item \"{$itemNumber}\" exists but is marked as deleted. Do you want to restore it?')) {
                window.location.href = 'restore_item.php?itemNumber={$itemNumber}';
            } else {
                alert('Item was not restored.');
                window.history.back();
            }
        </script>";
        exit;
    } else {
        echo "<script>
            alert('Error: Item Number \"{$itemNumber}\" already exists in the database. Please use a different Item Number or Use Inventory Maintenance to Update the Item that exists.');
            window.history.back();
        </script>";
        exit;
    }
}


    // Insert query with Store_ID and all variables
$sql = "INSERT INTO Inventory (
    Store_ID, Dept_ID, ItemNum, ItemName, ItemName_Extra, Cost, Price, In_Stock, Date_Created,
    Tax_1, Tax_2, Tax_3, Tax_4, Tax_5, Tax_6, Count_This_Item, Print_On_Receipt, FoodStampable,
    Reorder_Level, Reorder_Quantity, IsKit, IsModifier, Dirty, DOB, Check_ID2, InStock_Committed, ScaleSingleDeduct,
    PricePerMeasure, Prompt_Price, Old_InStock, Fixed_Tax, RequireCustomer, Exclude_Acct_Limit, Use_Serial_Numbers,
    ItemType, Transfer_Cost_Markup, IsDeleted, IsRental, Special_Permission, AlcoholContent, Last_Sold, ItemCategory,
    UnitMeasure, ReOrder_Cost, DiscountType, BarTaxInclusive, Retail_Price, DoughnutTax, Liability, SuggestedDeposit,
    Tear, QuantityRequired, ShipCompliantProductType, Check_ID, AutoWeigh, Transfer_Markup_Enabled,
    AllowReturns, Inactive, IsMatrixItem, ModifierType, DisplayTaxInPrice, Allow_BuyBack, Print_Voucher,
    DisableInventoryUpload, PromptCompletionDate, Kit_Override, As_Is, Import_Markup, PromptInvoiceNotes,
    Num_Bonus_Points, Unit_Size, AllowOnFleetCard, Print_Ticket, Prompt_Quantity, Inv_Num_Barcode_Labels, Use_Bulk_Pricing, Num_Days_Valid, Prompt_Description, ScaleItemType
) VALUES (
    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
)";

$stmt = $conn->prepare($sql);
$stmt->execute([
    $storeID, $deptID, $itemNumber, $description, $secondDescription, $avgCost, $priceCharge, $inStock, $dateCreated,
    $tax1, $tax2, $tax3, $tax4, $tax5, $tax6, $countItem, $printReceipt, $foodstampable,
    $reorderlevel, $reorderquantity, $iskit, $ismodifier, $Dirty, $DOB, $Check_ID2, $InStock_Committed, $ScaleSingleDeduct,
    $PricePerMeasure, $Prompt_Price, $Old_InStock, $Fixed_Tax, $RequireCustomer, $Exclude_Acct_Limit, $Use_Serial_Numbers,
    $ItemType, $Transfer_Cost_Markup, $IsDeleted, $IsRental, $Special_Permission, $AlcoholContent, $Last_Sold, $ItemCategory,
    $UnitMeasure, $ReOrder_Cost, $DiscountType, $BarTaxInclusive, $Retail_Price, $DoughnutTax, $Liability, $SuggestedDeposit,
    $Tear, $QuantityRequired, $ShipCompliantProductType, $Check_ID, $AutoWeigh, $Transfer_Markup_Enabled,
    $AllowReturns, $Inactive, $IsMatrixItem, $ModifierType, $DisplayTaxInPrice, $Allow_BuyBack, $Print_Voucher,
    $DisableInventoryUpload, $PromptCompletionDate, $Kit_Override, $As_Is, $Import_Markup, $PromptInvoiceNotes,
    $Num_Bonus_Points, $Unit_Size, $AllowOnFleetCard, $Print_Ticket, $Prompt_Quantity, $Inv_Num_Barcode_Labels, $Use_Bulk_Pricing, $Num_Days_Valid, $Prompt_Description, $ScaleItemType
]);

if ($itemNumber) {
    // Check if the ItemNum exists in Setup_TS_Buttons
    $checkQuery = "SELECT Option1 FROM Setup_TS_Buttons WHERE Ident = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->execute([$itemNumber]);
    $deptRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($deptRow) {
        // If ItemNum exists, retrieve Dept_ID (Option1)
        $deptID = $deptRow['Option1'];

        // Get the highest current Index for the matching Option1
        $indexQuery = "SELECT ISNULL(MAX([Index]), 0) + 1 AS NewIndex FROM Setup_TS_Buttons WHERE Option1 = ?";
        $stmt = $conn->prepare($indexQuery);
        $stmt->execute([$deptID]);
        $indexRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $newIndex = $indexRow['NewIndex'];

        // Get the BackColor of the last inserted item with the same Option1
        $backColorQuery = "SELECT BackColor FROM Setup_TS_Buttons WHERE Option1 = ? ORDER BY [Index] DESC OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY";
        $stmt = $conn->prepare($backColorQuery);
        $stmt->execute([$deptID]);
        $backColorRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $backColor = $backColorRow ? $backColorRow['BackColor'] : '12632256'; // Default if none found
    } else {
        // If no matching ItemNum, set defaults
        $newIndex = 0;
        $backColor = 12632256;
    }

    // Static values
    $btnType = 0;
    $stationid = '';
    $function = 0;
    $foreColor = null; // NULL value
    $visible = 1; // 1 = True
    $scheduleIndex = 0;
    $option4 = 0;
    $hideCaption = 0;
    $picture = '';
    $option2 = '';
    $option3 = '';

    // INSERT Query with Function Escaped
    $insertQuery = "INSERT INTO Setup_TS_Buttons 
        ([Index], Caption, BackColor, BtnType, Store_ID, [Function], ForeColor, Visible, Ident, ScheduleIndex, Option4, HideCaption, Station_ID, Picture, Option1, Option2, Option3) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($insertQuery);
    $stmt->execute([$newIndex, $description, $backColor, $btnType, $storeID, $function, $foreColor, $visible, $itemNumber, $scheduleIndex, $option4, $hideCaption, $stationid, $picture, $deptID, $option2, $option3]);

    // Check if the ItemNum exists in Inventory_BumpBarSettings before inserting
    $checkBumpBarQuery = "SELECT COUNT(*) FROM Inventory_BumpBarSettings WHERE ItemNum = ?";
    $stmt = $conn->prepare($checkBumpBarQuery);
    $stmt->execute([$itemNumber]);
    $bumpBarExists = $stmt->fetchColumn();

    // Check if the ItemNum exists in Inventory to avoid foreign key constraint error
    $checkInventoryQuery = "SELECT COUNT(*) FROM Inventory WHERE ItemNum = ?";
    $stmt = $conn->prepare($checkInventoryQuery);
    $stmt->execute([$itemNumber]);
    $inventoryExists = $stmt->fetchColumn();

    if (!$bumpBarExists && $inventoryExists) {
            // Static values for Inventory_BumpBarSettings
            $foreColor2 = 2;
            $backColor2 = 0;

            // INSERT into Inventory_BumpBarSettings only if the ItemNum exists in Inventory
            $insertQuery2 = "INSERT INTO Inventory_BumpBarSettings 
                ([Forecolor], [ItemNum], [Backcolor], [Store_ID]) 
                VALUES (?, ?, ?, ?)";

            $stmt = $conn->prepare($insertQuery2);
            $stmt->execute([$foreColor2, $itemNumber, $backColor2, $storeID]);

}
}

echo "<div style='text-align: center; margin-top: 20px;'>
    <p style='font-size: 18px; font-weight: bold; color: #333;'>
        New Item \"{$itemNumber}\" Saved Successfully
    </p>
    <div style='margin-top: 15px;'>
        <a href='addentry.php' style='
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            text-decoration: none;
            color: white;
            background-color: #007BFF;
            border-radius: 5px;
            transition: background 0.3s;
            font-size: 16px;
        ' onmouseover='this.style.backgroundColor=\"#0056b3\"' onmouseout='this.style.backgroundColor=\"#007BFF\"'>
            Add Another Item
        </a>
        <a href='#' style='
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            text-decoration: none;
            color: white;
            background-color: #dc3545;
            border-radius: 5px;
            transition: background 0.3s;
            font-size: 16px;
        ' onmouseover='this.style.backgroundColor=\"#b02a37\"' onmouseout='this.style.backgroundColor=\"#dc3545\"' onclick='window.close(); return false;'>
            Close Window
        </a>
    </div>
</div>";

}
 
?>
