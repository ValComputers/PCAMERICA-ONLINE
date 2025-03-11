$(document).ready(function() {
    function fetchItem(action, searchItem = null) {
        $.ajax({
            url: "fetch_data.php",
            type: "POST",
            data: { action: action, searchItem: searchItem },
            dataType: "json",
            success: function(response) {
                if (response.error) {
                    alert(response.error);
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    }
                } else {
                    $("#itemNum").val(response.ItemNum || "N/A");
                    $("#itemNumPO").val(response.ItemNum || "N/A");
                    $("#description").val(response.ItemName || "N/A");
                    $("#descriptionSpan").text(response.ItemName || "N/A");
                    $("#secondDescription").val(response.ItemName_Extra || "N/A");
                    $("#avgCost").val(response.Cost ? parseFloat(response.Cost).toFixed(5) : "");
                    $("#priceCharge").val(response.Price ? parseFloat(response.Price).toFixed(5) : "");
                    $("#poNumberReceived").val(""); // Clear previous PO input
                    $("#poCostPer").val(""); // Clear previous PO input
                    $("#totalTaxAmount").val(response.TaxTotalAmt || "N/A");
                    $("#priceTax").val(response.PriceWithTax || "N/A");
                    $("#inStock").val(response.In_Stock != null ? parseFloat(response.In_Stock).toFixed(0) : "N/A");
                    $("#department").val(response.Dept_ID || "N/A");
                    $("#categoryID").val(response.Category_ID || "N/A");
                    $("#vendor").val(response.Vendor_Number || "N/A");
                    $("#commissionOptions").val(response.Comm_Type !== undefined && response.Comm_Type !== null ? response.Comm_Type.toString() : "0");
                    $("#commAmount").val(response.Comm_Amt || "N/A");

                    $("#taxesApplied").val(response.TaxesApplied || "No Taxes");
                    $("#alternateSKUs").html(response.Alt_SKUs || "");
                    $("#tagAlongItems").html(response.Tag_Alongs || "");
                    
                    $("#location").val(response.Location || "");
                    $("#unitSize").val(response.Unit_Size || "");
                    $("#unitType").val(response.Unit_Type || "");

                    if (response.Tax_1 == 1) {
                        $('#tax1').prop('checked', true);
                    } else {
                        $('#tax1').prop('checked', false);
                    }
                    if (response.Tax_2 == 1) {
                        $('#tax2').prop('checked', true);
                    } else {
                        $('#tax2').prop('checked', false);
                    }
                    if (response.Tax_3 == 1) {
                        $('#tax3').prop('checked', true);
                    } else {
                        $('#tax3').prop('checked', false);
                    }
                    if (response.Tax_4 == 1) {
                        $('#tax4').prop('checked', true);
                    } else {
                        $('#tax4').prop('checked', false);
                    }
                    if (response.Tax_5 == 1) {
                        $('#tax5').prop('checked', true);
                    } else {
                        $('#tax5').prop('checked', false);
                    }
                    if (response.Tax_6 == 1) {
                        $('#tax6').prop('checked', true);
                    } else {
                        $('#tax6').prop('checked', false);
                    }
                    if (response.BarTaxInclusive == 1) {
                        $('#barTax').prop('checked', true);
                    } else {
                        $('#barTax').prop('checked', false);
                    }
                    $("#fixedTax").val(response.Fixed_Tax ? parseFloat(response.Fixed_Tax).toFixed(2) : "");
                    $("#bonusPoints").val(response.Num_Bonus_Points || "0");
                    $("#barcodes").val(response.Inv_Num_Barcode_Labels || "0");
                    $("#commissionOptions").val(response.Comm_Type);
                    $("#commission").val(response.Comm_Amt || "0");

                    if (response.IsModifier == 1) {
                        $('#modifierItem').prop('checked', true);
                    } else {
                        $('#modifierItem').prop('checked', false);
                    }
                    if (response.Inactive == 1) {
                        $('#disableItem').prop('checked', true);
                    } else {
                        $('#disableItem').prop('checked', false);
                    }
                    if (response.FoodStampable == 1) {
                        $('#foodstampable').prop('checked', true);
                    } else {
                        $('#foodstampable').prop('checked', false);
                    }
                    if (response.Inactive == 1) {
                        $('#disableItem').prop('checked', true);
                    } else {
                        $('#disableItem').prop('checked', false);
                    }
                    if (response.Exclude_Acct_Limit == 1) {
                        $('#excludeAccount').prop('checked', true);
                    } else {
                        $('#excludeAccount').prop('checked', false);
                    }
                    if (response.Prompt_Quantity == 1) {
                        $('#promptQuantity').prop('checked', true);
                    } else {
                        $('#promptQuantity').prop('checked', false);
                    }
                    if (response.AutoWeigh == 1) {
                        $('#autoWeigh').prop('checked', true);
                    } else {
                        $('#autoWeigh').prop('checked', false);
                    }
                    if (response.Check_ID == 1) {
                        $('#checkID').prop('checked', true);
                    } else {
                        $('#checkID').prop('checked', false);
                    }
                    if (response.Prompt_Price == 1) {
                        $('#promptPrice').prop('checked', true);
                    } else {
                        $('#promptPrice').prop('checked', false);
                    }
                    if (response.Use_Serial_Numbers == 1) {
                        $('#useSerialBatch').prop('checked', true);
                    } else {
                        $('#useSerialBatch').prop('checked', false);
                    }
                    if (response.Check_ID2 == 1) {
                        $('#checkID2').prop('checked', true);
                    } else {
                        $('#checkID2').prop('checked', false);
                    }
                    if (response.Allow_BuyBack == 1) {
                        $('#allowBuyback').prop('checked', true);
                    } else {
                        $('#allowBuyback').prop('checked', false);
                    }
                    if (response.Special_Permission == 1) {
                        $('#specialPermission').prop('checked', true);
                    } else {
                        $('#specialPermission').prop('checked', false);
                    }
                    if (response.Count_This_Item == 1) {
                        $('#countThisItem').prop('checked', true);
                    } else {
                        $('#countThisItem').prop('checked', false);
                    }
                    if (response.Print_On_Receipt == 1) {
                        $('#printOnReceipt').prop('checked', true);
                    } else {
                        $('#printOnReceipt').prop('checked', false);
                    }
                    if (response.PromptCompletionDate == 1) {
                        $('#promptCompletionDate').prop('checked', true);
                    } else {
                        $('#promptCompletionDate').prop('checked', false);
                    }
                    if (response.PromptInvoiceNotes == 1) {
                        $('#promptInvoiceNotes').prop('checked', true);
                    } else {
                        $('#promptInvoiceNotes').prop('checked', false);
                    }
                    if (response.Prompt_Description == 1) {
                        $('#promptDescription').prop('checked', true);
                    } else {
                        $('#promptDescription').prop('checked', false);
                    }
                    if (response.As_Is == 1) {
                        $('#sellAsIs').prop('checked', true);
                    } else {
                        $('#sellAsIs').prop('checked', false);
                    }
                    if (response.RequireCustomer == 1) {
                        $('#requireCustomer').prop('checked', true);
                    } else {
                        $('#requireCustomer').prop('checked', false);
                    }
                    $("#promptDescriptionOver").val(
    response.Prompt_DescriptionOverDollarAmt 
        ? `$${parseFloat(response.Prompt_DescriptionOverDollarAmt).toFixed(2)}` 
        : "N/A"
);
                    $("#limitQtyOnInvoice").val(response.InvoiceLimitQty || "0");
                    if (response.Exclude_From_Loyalty == 1) {
                        $('#excludeFromLoyaltyPlan').prop('checked', true);
                    } else {
                        $('#excludeFromLoyaltyPlan').prop('checked', false);
                    }
                    if (response.Print_Ticket == 1) {
                        $('#printTicket').prop('checked', true);
                    } else {
                        $('#printTicket').prop('checked', false);
                    }
                    if (response.ScaleSingleDeduct == 1) {
                        $('#scaleSingleDeduct').prop('checked', true);
                    } else {
                        $('#scaleSingleDeduct').prop('checked', false);
                    }
                    if (response.AllowReturns == 1) {
                        $('#allowReturns').prop('checked', true);
                    } else {
                        $('#allowReturns').prop('checked', false);
                    }
                    if (response.Liability == 1) {
                        $('#liabilityItem').prop('checked', true);
                    } else {
                        $('#liabilityItem').prop('checked', false);
                    }
                    if (response.AllowOnDepositInvoices == 1) {
                        $('#allowOnDepositInvoices').prop('checked', true);
                    } else {
                        $('#allowOnDepositInvoices').prop('checked', false);
                    }
                    if (response.AllowOnFleetCard == 1) {
                        $('#allowOnFleetCard').prop('checked', true);
                    } else {
                        $('#allowOnFleetCard').prop('checked', false);
                    }
                    if (response.DisplayTaxInPrice == 1) {
                        $('#displayTaxInPrice').prop('checked', true);
                    } else {
                        $('#displayTaxInPrice').prop('checked', false);
                    }
                    if (response.DisableInventoryUpload == 1) {
                        $('#disableInventoryUpload').prop('checked', true);
                    } else {
                        $('#disableInventoryUpload').prop('checked', false);
                    }
                    if (response.Print_Ticket == 1) {
                        $('#printTicket').prop('checked', true);
                    } else {
                        $('#printTicket').prop('checked', false);
                    }

                    if (response.ScaleItemType == 0) {
                        $('#soldByPiece').prop('checked', true);
                    } else {
                        $('#soldByPiece').prop('checked', false);
                    }
                    if (response.ScaleItemType == 1) {
                        $('#weighedOnScale').prop('checked', true);
                    } else {
                        $('#weighedOnScale').prop('checked', false);
                    }
                    if (response.ScaleItemType == 2) {
                        $('#weighedWithTare').prop('checked', true);
                    } else {
                        $('#weighedWithTare').prop('checked', false);
                    }
                    if (response.ScaleItemType == 3) {
                        $('#barcoded').prop('checked', true);
                    } else {
                        $('#barcoded').prop('checked', false);
                    }
                    if (response.ScaleItemType == 4) {
                        $('#barcodedAndSoldByPiece').prop('checked', true);
                    } else {
                        $('#barcodedAndSoldByPiece').prop('checked', false);
                    }
                    if (response.NeverPrintInKitchen == 1) {
                        $('#neverPrintInKitchen').prop('checked', true);
                    } else {
                        $('#neverPrintInKitchen').prop('checked', false);
                    }
                    $("#daysValid").val(response.Num_Days_Valid || "N/A");
                    $("#discountType").val(response.DiscountType);
                    $("#generalLedgerNumber").val(response.GLNumber || "");

                    // Assign TagStatus properly to the <select> dropdown
                    if (response.TagStatus) {
                    $("#tag").val(response.TagStatus);
                    } else {
                    $("#tag").val("None"); // Default selection
                    }
                    
                    $("#ProfitFormatted").html("Profit %: "+response.ProfitFormatted);
                    $("#RetailDiscountFormatted").html("Retail Discount: "+response.RetailDiscountFormatted);
                    $("#GrossMarginFormatted").html("Gross Margin: "+response.GrossMarginFormatted);
                   
                    $("#webPrice").val(response.webPrice ? `$${parseFloat(response.webPrice).toFixed(2)}` 
        : "$0.00"
                    );
                    $("#keywords").val(response.keywords || "");
                    $("#brand").val(response.brand || "");
                    $("#theme").val(response.theme || "");
                    $("#subCategory").val(response.subCategory || "");
                    $("#leadTime").val(response.leadTime || "");
                    $("#weight").val(response.weight || "0");
                    $("#releaseDate").val(response.releaseDate || "");
                    $("#priority").val(response.priority || "0");
                    $("#rating").val(response.rating || "0");
                    $("#extendedDescription").val(response.extendedDescription || "");
                    if (response.productOnPromotion == 1) {
                        $('#productOnPromotion').prop('checked', true);
                    } else {
                        $('#productOnPromotion').prop('checked', false);
                    }
                    if (response.productSpecialOffer == 1) {
                        $('#productSpecialOffer').prop('checked', true);
                    } else {
                        $('#productSpecialOffer').prop('checked', false);
                    }
                    if (response.newProduct == 1) {
                        $('#newProduct').prop('checked', true);
                    } else {
                        $('#newProduct').prop('checked', false);
                    }
                    if (response.discountable == 1) {
                        $('#discountable').prop('checked', true);
                    } else {
                        $('#discountable').prop('checked', false);
                    }
                    if (response.AvailableOnline == 1) {
                        $('#availableOnline').prop('checked', true);
                    } else {
                        $('#availableOnline').prop('checked', false);
                    }
                    if (response.notForWebSale == 1) {
                        $('#notForWebSale').prop('checked', true);
                    } else {
                        $('#notForWebSale').prop('checked', false);
                    }
                    $("#customNumber1").val(response.customNumber1 || "0");
                    $("#customNumber2").val(response.customNumber2 || "0");
                    $("#customNumber3").val(response.customNumber3 || "0");
                    $("#customNumber4").val(response.customNumber4 || "0");
                    $("#customNumber5").val(response.customNumber5 || "0");
                    $("#subDescription1").val(response.subDescription1 || "");
                    $("#subDescription2").val(response.subDescription2 || "");
                    $("#subDescription3").val(response.subDescription3 || "");
                    $("#customText1").val(response.customText1 || "");
                    $("#customText2").val(response.customText2 || "");
                    $("#customText3").val(response.customText3 || "");
                    $("#customText4").val(response.customText4 || "");
                    $("#customText5").val(response.customText5 || "");
                    $("#customExtendedText1").val(response.customExtendedText1 || "");
                    $("#customExtendedText2").val(response.customExtendedText2 || "");
                    console.log("Item data refreshed successfully.");
                }
            },
            error: function(xhr, status, error) {
                alert("AJAX Error: " + error);
            }
        });
    }

    // Ensure fetchItem is globally available
    window.fetchItem = fetchItem;
    
    fetchItem("first");

    $("#nextItem").click(function() { fetchItem("next"); });
    $("#prevItem").click(function() { fetchItem("prev"); });
    $("#lastItem").click(function() { fetchItem("last"); });

    $("#searchItemForm").submit(function(event) {
        event.preventDefault();
        let searchItem = $("#searchItem").val();
        fetchItem("search", searchItem);
    });

    // Ensure refresh happens only after confirmation popup is clicked
    $(document).on('click', '#confirmationPopupOK', function() {
        if (typeof fetchItem === "function") {
            fetchItem("refresh", $("#itemNum").val());
        } else {
            console.error("fetchItem is not defined after confirmation popup.");
        }
    });
});

document.addEventListener("DOMContentLoaded", function () {
    document.getElementById("itemNumInput").addEventListener("change", function () {
        let itemNum = this.value;
        loadAltSkuTagAlong(itemNum);
    });
});
