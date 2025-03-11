<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? 'refresh';
$searchItem = $_POST['searchItem'] ?? null;
$result = null;

try {
    // Ensure currentItemNum is set in the session
    if (!isset($_SESSION['currentItemNum']) || $action === 'reset') {
        $query = "SELECT TOP 1 ItemNum FROM Inventory WHERE IsDeleted = 0 ORDER BY ItemNum ASC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $_SESSION['currentItemNum'] = $row['ItemNum'] ?? null;
    }

    $currentItemNum = $_SESSION['currentItemNum'];

    if (empty($currentItemNum) && $action !== 'reset') {
        echo json_encode(['error' => 'No item found in session. Resetting to default.']);
        exit;
    }

    // Get first and last item numbers
    $firstQuery = "SELECT TOP 1 ItemNum FROM Inventory WHERE IsDeleted = 0 ORDER BY ItemNum ASC";
    $lastQuery = "SELECT TOP 1 ItemNum FROM Inventory WHERE IsDeleted = 0 ORDER BY ItemNum DESC";

    $firstStmt = $conn->query($firstQuery);
    $firstItem = $firstStmt->fetch(PDO::FETCH_ASSOC)['ItemNum'];

    $lastStmt = $conn->query($lastQuery);
    $lastItem = $lastStmt->fetch(PDO::FETCH_ASSOC)['ItemNum'];

    // Determine the appropriate SQL query based on the action
    if ($action === 'search' && $searchItem) {
        $query = "SELECT TOP 1 * FROM Inventory WHERE ItemNum = :itemNum AND IsDeleted = 0";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':itemNum', $searchItem, PDO::PARAM_STR);
    } elseif ($action === 'next') {
        if ($currentItemNum == $lastItem) {
            $query = "SELECT TOP 1 * FROM Inventory WHERE IsDeleted = 0 ORDER BY ItemNum ASC";
        } else {
            $query = "SELECT TOP 1 * FROM Inventory WHERE ItemNum > :currentItemNum AND IsDeleted = 0 ORDER BY ItemNum ASC";
        }
        $stmt = $conn->prepare($query);
        if ($currentItemNum != $lastItem) {
            $stmt->bindParam(':currentItemNum', $currentItemNum, PDO::PARAM_STR);
        }
    } elseif ($action === 'prev') {
        if ($currentItemNum == $firstItem) {
            $query = "SELECT TOP 1 * FROM Inventory WHERE IsDeleted = 0 ORDER BY ItemNum DESC";
        } else {
            $query = "SELECT TOP 1 * FROM Inventory WHERE ItemNum < :currentItemNum AND IsDeleted = 0 ORDER BY ItemNum DESC";
        }
        $stmt = $conn->prepare($query);
        if ($currentItemNum != $firstItem) {
            $stmt->bindParam(':currentItemNum', $currentItemNum, PDO::PARAM_STR);
        }
    } elseif ($action === 'last') {
        $query = "SELECT TOP 1 * FROM Inventory WHERE IsDeleted = 0 ORDER BY ItemNum DESC";
        $stmt = $conn->prepare($query);
    } else {
        $query = "SELECT TOP 1 * FROM Inventory WHERE ItemNum = :currentItemNum AND IsDeleted = 0";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':currentItemNum', $currentItemNum, PDO::PARAM_STR);
    }

    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        echo json_encode(['error' => 'Item not found']);
        exit;
    }

    $_SESSION['currentItemNum'] = $result['ItemNum'];

    // Fetch tax rates
    $taxQuery = "SELECT Tax1_Rate, Tax2_Rate, Tax3_Rate, Tax4_Rate, Tax5_Rate, Tax6_Rate FROM Tax_Rate";
    $taxStmt = $conn->query($taxQuery);
    $taxRates = $taxStmt->fetch(PDO::FETCH_ASSOC);

    // Calculate total tax amount
    $price = floatval($result['Price'] ?? 0);
    $totalTaxAmount = 0;
    $TaxTotalAmt = 0;
    $appliedTaxes = [];

    foreach ($taxRates as $key => $value) {
        if (strpos($key, 'Rate') !== false && is_numeric($value)) {
            $taxIndex = substr($key, 3, 1);
            $taxApplicable = isset($result["Tax_$taxIndex"]) ? (bool)$result["Tax_$taxIndex"] : false;
            if ($taxApplicable) {
                $TaxTotalAmt += floatval($value);
                $totalTaxAmount += round($price * $value, 2);
                $appliedTaxes[] = "Tax $taxIndex";
            }
        }
    }

    $result['TaxTotalAmt'] = number_format($TaxTotalAmt, 2, '.', '');
    $result['PriceWithTax'] = number_format($price + $totalTaxAmount, 2, '.', '');
    $result['TaxesApplied'] = !empty($appliedTaxes) ? implode(", ", $appliedTaxes) : "No Taxes";

    // Fetch alternate SKUs
    $skuStmt = $conn->prepare("SELECT AltSKU FROM Inventory_SKUS WHERE ItemNum = :itemNum");
    $skuStmt->bindParam(':itemNum', $_SESSION['currentItemNum'], PDO::PARAM_STR);
    $skuStmt->execute();
    $result['Alt_SKUs'] = implode(", ", $skuStmt->fetchAll(PDO::FETCH_COLUMN));

    // Fetch Tag Along Items
    $tagAlongStmt = $conn->prepare("SELECT TagAlong_ItemNum FROM Inventory_TagAlongs WHERE ItemNum = :itemNum");
    $tagAlongStmt->bindParam(':itemNum', $_SESSION['currentItemNum'], PDO::PARAM_STR);
    $tagAlongStmt->execute();
    $result['Tag_Alongs'] = implode(", ", $tagAlongStmt->fetchAll(PDO::FETCH_COLUMN));

    // Fetch commission data
    $commQuery = "SELECT Comm_Type, Comm_Amt FROM Inventory_Commissions WHERE ItemNum = :itemNum";
    $commStmt = $conn->prepare($commQuery);
    $commStmt->bindParam(':itemNum', $_SESSION['currentItemNum'], PDO::PARAM_STR);
    $commStmt->execute();
    $commResult = $commStmt->fetch(PDO::FETCH_ASSOC);

    if ($commResult) {
        $Comm_Type = (int)$commResult['Comm_Type'];
        $Comm_Amt = (float)$commResult['Comm_Amt'];
    } else {
        $Comm_Type = 0;
        $Comm_Amt = 0.0;
    }

    // Format Comm_Amt based on Comm_Type
    if ($Comm_Type === 0 || $Comm_Type === 1) {
        $formattedCommAmt = number_format($Comm_Amt * 100, 1) . '%';
    } elseif ($Comm_Type === 2) {
        $formattedCommAmt = '$' . number_format($Comm_Amt, 2);
    } else {
        $formattedCommAmt = 'Invalid Type';
    }

    $result['Comm_Type'] = $Comm_Type;
    $result['Comm_Amt'] = $formattedCommAmt;

    
    $avgCost = isset($result['Cost']) ? number_format($result['Cost'], 5) : '';
    $avgCost2 = isset($result['Cost']) ? floatval($result['Cost']) : 0;
    $priceCharge2 = isset($result['Price']) ? floatval($result['Price']) : 0;
    $Retail_Price = isset($result['Retail_Price']) ? floatval($result['Retail_Price']) : 0;

    if ($avgCost2 > 0 && $priceCharge2 == 0) {
        // Case: Price is $0, full loss
        $Profit = -100; // Loss is 100% of the cost
        $GrossMargin = 0; // Gross margin is 0%
    } elseif ($avgCost2 == 0 && $priceCharge2 > 0) {
        // Case: Cost is $0
        $Profit = 0; // Profit is 0%
        $GrossMargin = 100; // Gross margin is 100%
    } elseif ($avgCost2 > 0 && $priceCharge2 > 0) {
        // Handle loss scenario or normal profit calculation
        $Profit = (($priceCharge2 - $avgCost2) / $avgCost2) * 100; // Profit as percentage
        $GrossMargin = (($priceCharge2 - $avgCost2) / $priceCharge2) * 100; // Gross Margin as percentage
    } else {
        // Default case for both Cost and Price being zero or invalid values
        $Profit = 0;
        $GrossMargin = 0;
    }

    // Retail Discount Calculation
    if (!empty($Retail_Price) && $Retail_Price > 0) {
    $RetailDiscount = ((($Retail_Price - $priceCharge2) / $Retail_Price) * 100); // Calculate Retail Discount as percentage
    } else {
    $RetailDiscount = 0; // Default value if Retail_Price is missing or invalid
    }

    $result['ProfitFormatted'] = number_format($Profit, 3) . '%';
    $result['RetailDiscountFormatted'] = number_format($RetailDiscount, 3) . '%';
    $result['GrossMarginFormatted'] = number_format($GrossMargin, 3) . '%';

    // Fetch additional related data from Inventory_AdditionalInfo
if (!empty($result['ItemNum'])) {
    $additionalInfoQuery = "SELECT * FROM Inventory_AdditionalInfo WHERE ItemNum = :itemNum";
    $additionalStmt = $conn->prepare($additionalInfoQuery);
    $additionalStmt->bindParam(':itemNum', $result['ItemNum'], PDO::PARAM_STR);
    $additionalStmt->execute();
    $additionalInfo = $additionalStmt->fetch(PDO::FETCH_ASSOC);

    if ($additionalInfo) {
        $result['webPrice'] = isset($additionalInfo['WebPrice']) ? number_format($additionalInfo['WebPrice'], 2) : '';
        $result['keywords'] = htmlspecialchars($additionalInfo['Keywords'] ?? '');
        $result['brand'] = htmlspecialchars($additionalInfo['Brand'] ?? '');
        $result['theme'] = htmlspecialchars($additionalInfo['Theme'] ?? '');
        $result['subCategory'] = htmlspecialchars($additionalInfo['SubCategory'] ?? '');
        $result['leadTime'] = htmlspecialchars($additionalInfo['LeadTime'] ?? '');
        $result['weight'] = htmlspecialchars($additionalInfo['Weight'] ?? '');
        $result['releaseDate'] = !empty($additionalInfo['ReleaseDate']) ? htmlspecialchars(date('Y-m-d', strtotime($additionalInfo['ReleaseDate']))) : '';
        $result['priority'] = htmlspecialchars($additionalInfo['Priority'] ?? '');
        $result['rating'] = htmlspecialchars($additionalInfo['Rating'] ?? '');
        $result['extendedDescription'] = htmlspecialchars($additionalInfo['ExtendedDescription'] ?? '');

        // Product-related flags
        $result['productOnPromotion'] = $additionalInfo['ProductOnPromotionPreOrder'] ?? '';
        $result['productSpecialOffer'] = $additionalInfo['ProductOnSpecialOffer'] ?? '';
        $result['newProduct'] = $additionalInfo['NewProduct'] ?? '';
        $result['discountable'] = $additionalInfo['Discountable'] ?? '';
        $result['availableOnline'] = $additionalInfo['AvailableOnline'] ?? '';
        $result['notForWebSale'] = $additionalInfo['NoWebSales'] ?? '';

        // Custom numbers
        for ($i = 1; $i <= 5; $i++) {
            $result["customNumber$i"] = htmlspecialchars($additionalInfo["CustomNumber$i"] ?? '');
        }

        // Sub descriptions
        for ($i = 1; $i <= 3; $i++) {
            $result["subDescription$i"] = htmlspecialchars($additionalInfo["SubDescription$i"] ?? '');
        }

        // Custom texts
        for ($i = 1; $i <= 5; $i++) {
            $result["customText$i"] = htmlspecialchars($additionalInfo["CustomText$i"] ?? '');
        }

        // Custom extended texts
        for ($i = 1; $i <= 2; $i++) {
            $result["customExtendedText$i"] = htmlspecialchars($additionalInfo["CustomExtendedText$i"] ?? '');
        }
    }
}
    echo json_encode($result);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
