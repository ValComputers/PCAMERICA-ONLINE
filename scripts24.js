// scripts.js

function openTab(evt, tabName) {
    var tabContent = document.getElementsByClassName("tab-content");
    for (var i = 0; i < tabContent.length; i++) {
        tabContent[i].style.display = "none";
    }

    var tabLinks = document.getElementsByClassName("tablinks");
    for (var i = 0; i < tabLinks.length; i++) {
        tabLinks[i].classList.remove("active");
    }

    // Show the selected tab and add the active class
    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.classList.add("active");

    // Automatically highlight Regular Fields when Online Attributes is selected
    if (tabName === 'OnlineAttributes') {
        document.getElementById("nestedDefaultOpen").click();
    }
}

function openNestedTab(evt, tabName) {
    var tabContentNested = document.getElementsByClassName("tab-content-nested");
    for (var i = 0; i < tabContentNested.length; i++) {
        tabContentNested[i].style.display = "none";
    }

    var tabLinksNested = document.querySelectorAll("#OnlineAttributes .tablinks");
    tabLinksNested.forEach(function(link) {
        link.classList.remove("active");
    });

    // Show the selected nested tab and add the active class
    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.classList.add("active");
}

// Set Options as active on page load
document.getElementById("defaultOpen").click();

var currentField = null;   // Track the currently selected field
var isShift = false;      // Track shift state for uppercase/lowercase

function setCurrentField(field) {
    currentField = field;
}

function openKeyboard() {
    if (currentField) {
        document.getElementById('keyboardPopup').style.display = 'block';
        document.getElementById('keyboardInput').value = '';  // Clear the keyboard input for new entry
        setInitialLowerCase();  // Ensure all keys start in lowercase
    } else {
        alert("Please select a field first.");
    }
}

function setInitialLowerCase() {
    const letterButtons = document.querySelectorAll('.letter-button');
    const symbolButtons = document.querySelectorAll('.symbol-button');
    const numberButtons = document.querySelectorAll('.number-button');
    
    letterButtons.forEach(button => {
        button.innerText = button.innerText.toLowerCase();
    });

    symbolButtons.forEach(button => {
        const key = button.getAttribute("data-key");
        button.innerText = key;  // Reset to unshifted symbols (lowercase state)
    });

    numberButtons.forEach(button => {
        const key = button.getAttribute("data-key");
        button.innerText = key;  // Reset to unshifted numbers
    });

    isShift = false;  // Set initial state to lowercase
}

function closeKeyboard() {
    document.getElementById('keyboardPopup').style.display = 'none';
}

function addKey(key) {
    const keyboardInput = document.getElementById('keyboardInput');

    if (isShift) {
        const shiftSymbols = {
            "1": "!", "2": "@", "3": "#", "4": "$", "5": "%",
            "6": "^", "7": "&", "8": "*", "9": "(", "0": ")",
            ";": ":", ",": "<", ".": ">", "/": "?"
        };
        key = shiftSymbols[key] || key.toUpperCase();
    } else {
        key = key.toLowerCase();
    }

    keyboardInput.value += key;
}

function toggleShift() {
    isShift = !isShift;
    const symbolButtons = document.querySelectorAll('.symbol-button');
    const letterButtons = document.querySelectorAll('.letter-button');
    const numberButtons = document.querySelectorAll('.number-button');

    symbolButtons.forEach(button => {
        let key = button.getAttribute("data-key");
        const shiftSymbols = {
            ";": ":", ",": "<", ".": ">", "/": "?"
        };
        button.innerText = isShift ? shiftSymbols[key] || key : key;
    });

    letterButtons.forEach(button => {
        let key = button.innerText;
        button.innerText = isShift ? key.toUpperCase() : key.toLowerCase();
    });

    numberButtons.forEach(button => {
        let key = button.getAttribute("data-key");
        const shiftSymbols = {
            "1": "!", "2": "@", "3": "#", "4": "$", "5": "%",
            "6": "^", "7": "&", "8": "*", "9": "(", "0": ")"
        };
        button.innerText = isShift ? shiftSymbols[key] || key : key;
    });
}

function backspace() {
    const keyboardInput = document.getElementById('keyboardInput');
    keyboardInput.value = keyboardInput.value.slice(0, -1);
}

function submitKeyboard() {
    if (currentField) {
        const newValue = document.getElementById('keyboardInput').value;
        currentField.value = newValue;
        closeKeyboard();
        if (currentField.dataset.originalValue !== newValue) {
            currentField.classList.add("highlight");
            currentField.classList.remove("normal");
        } else {
            currentField.classList.remove("highlight");
            currentField.classList.add("normal");
        }
    }
}

function openInventoryModal() {
    document.getElementById('inventoryModal').style.display = 'block';
}

function closeInventoryModal() {
    document.getElementById('inventoryModal').style.display = 'none';
}

// Logout functionality
const timeoutDuration = 600000;
let timeout;

function resetTimeout() {
    clearTimeout(timeout);
    timeout = setTimeout(logout, timeoutDuration);
}

function logout() {
    window.location.href = 'logout.php';
}

function downloadHelp() {
    const fileUrl = window.location.origin + "/cretests/CREHelp.chm";
    const link = document.createElement("a");
    link.href = fileUrl;
    link.download = "CREHelp.chm";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

window.onload = resetTimeout;
document.onmousemove = resetTimeout;
document.onkeydown = resetTimeout;
document.onscroll = resetTimeout;

document.querySelector('.search-btn').addEventListener('click', function () {
    const searchText = document.getElementById('searchText').value;

    fetch('inventorySearch.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `searchText=${encodeURIComponent(searchText)}`,
    })
        .then((response) => response.json())
        .then((data) => {
            const tableBody = document.querySelector('#inventoryTable tbody');
            tableBody.innerHTML = ''; // Clear existing rows

            if (data.error) {
                alert(data.error);
                return;
            }

            if (data.length === 0) {
                const row = document.createElement('tr');
                row.innerHTML = `<td colspan="6">No items found</td>`;
                tableBody.appendChild(row);
            } else {
                data.forEach((item) => {
                    const row = document.createElement('tr');
                    row.setAttribute('data-item-number', item.ItemNum); // Store ItemNum in a data attribute
                    row.innerHTML = `
                        <td>${item.ItemNum}</td>
                        <td>${item.ItemName}</td>
                        <td>${item.Price}</td>
                        <td>${item.In_Stock}</td>
                        <td>${item.ItemName_Extra}</td>
                        <td>${item.Vendor_Part_Num}</td>
                    `;
                    tableBody.appendChild(row);

                    // Add click event for selection
                    row.addEventListener('click', () => {
                        // Remove highlight from other rows
                        document.querySelectorAll('#inventoryTable tbody tr').forEach((tr) => tr.classList.remove('selected'));
                        // Highlight the clicked row
                        row.classList.add('selected');
                    });
                });
            }
        })
        .catch((error) => {
            console.error('Error:', error);
        });
});

// Handle "Select" button click
document.querySelector('.select-btn').addEventListener('click', function () {
    const selectedRow = document.querySelector('#inventoryTable tbody tr.selected');
    if (selectedRow) {
        const itemNumber = selectedRow.getAttribute('data-item-number');
        // Populate your form with the selected item number
        document.getElementById('itemNumberField').value = itemNumber; // Replace 'itemNumberField' with your form field ID
        closeInventoryModal(); // Close the modal
    } else {
        alert('Please select an item.');
    }
});

function openInventoryModal() {
    const tableBody = document.querySelector('#inventoryTable tbody');
    tableBody.innerHTML = ''; // Clear any existing rows
    const searchText = document.getElementById('searchText');
    if (searchText) searchText.value = '';
    document.getElementById('inventoryModal').style.display = 'block';
}

function closeInventoryModal() {
    let inventoryModal = document.getElementById('inventoryModal');
    if (inventoryModal) {
        inventoryModal.style.display = 'none';
    }
}


document.addEventListener('DOMContentLoaded', function () {
    let errorModal = document.getElementById('errorModal');
    if (errorModal) {
        errorModal.style.display = 'block';
    }
});

function closeModal() {
    let errorModal = document.getElementById('errorModal');
    if (errorModal) {
        errorModal.style.display = 'none';
    }
}

// Monitor user activity: mouse movement, key presses, etc.
    window.onload = resetTimeout; // Initialize on page load
    document.onmousemove = resetTimeout;
    document.onkeydown = resetTimeout;
    document.onscroll = resetTimeout;


function openPopupmulti(type) {
    const typeLabel = type === 'alternate' ? 'Alternate SKU' : 'Tag Along Item';
    Swal.fire({
        title: `Add ${typeLabel}`,
        input: 'text',
        inputPlaceholder: `Enter ${typeLabel}`,
        showCancelButton: true,
        confirmButtonText: 'Add',
        cancelButtonText: 'Cancel',
        inputValidator: (value) => {
            if (!value) {
                return 'Please enter a value!';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.getElementById('popup_form');
            document.getElementById('popup_form_type').value = type;
            document.getElementById('popup_input').value = result.value;
            form.submit();
        }
    });
}

// Get input fields
const priceChargeInput = document.getElementById('priceCharge');
const priceTaxInput = document.getElementById('priceTax');
const totalTaxRate = parseFloat(document.getElementById('totalTaxAmount').value) || 0;

// When priceCharge is updated, calculate price with tax
priceChargeInput.addEventListener('input', () => {
    const priceCharge = parseFloat(priceChargeInput.value) || 0;
    const priceWithTax = priceCharge * (1 + totalTaxRate);
    priceTaxInput.value = priceWithTax.toFixed(2); // Show total with tax
});

// When priceTax is updated, remove tax to get base price
priceTaxInput.addEventListener('input', () => {
    const priceWithTax = parseFloat(priceTaxInput.value) || 0;
    const priceCharge = priceWithTax / (1 + totalTaxRate);
    priceChargeInput.value = priceCharge.toFixed(5); // Show base price
});

// Instant PO Modal popup
function openPOModal() {
    let itemNumValue = $("#itemNum").val().trim(); // Get existing value from the page

    if (!itemNumValue || itemNumValue === "N/A") {
        console.error("Error: ItemNum is missing or not available yet!");
        alert("Item Number is not available. Please wait for the data to load.");
        return;
    }

    console.log("Opening PO Modal with ItemNum:", itemNumValue); // Debugging log

    $("#itemNumPO").val(itemNumValue); // Assign to hidden input in PO modal
    $("#pomodal").show(); // Show modal
}

// Close PO Modal and reset fields
function closePOModal() {
    document.getElementById("pomodal").style.display = "none";
    resetPOModalFields(true); // Reset including itemNum if needed
}

// Function to reset modal fields except the hidden itemNum field
function resetPOModalFields(clearItemNum) {
    let form = document.getElementById("pomodalForm");
    form.reset(); // Reset all fields

    let hiddenInput = document.getElementById("itemNum");
    if (!clearItemNum) {
        hiddenInput.value = hiddenInput.defaultValue; // Restore original itemNum
    } else {
        hiddenInput.value = ""; // Clear only if explicitly needed
    }
}

document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("instantpoButton").addEventListener("click", function() {
        submitPOModalForm();
    });
});

function submitPOModalForm() {
    let itemNumValue = $("#itemNum").val().trim();

    if (!itemNumValue || itemNumValue === "N/A") {
        alert("Error: Item Number is missing!");
        return;
    }

    let formData = {
        itemNum: itemNumValue,
        poAdjustmentDesc: $("#poAdjustmentDesc").val().trim(),
        poNumberReceived: $("#poNumberReceived").val().trim(),
        poCostPer: $("#poCostPer").val().trim()
    };

    console.log("Submitting Form Data:", formData); // Debugging

    fetch("instantpo.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        console.log("Server Response:", data); // Debugging

        if (data.success) {
            alert("Instant PO updated successfully! \nNew Stock: " + data.NewStock + "\nNew Avg Cost: $" + data.NewAvgCost);

            closePOModal(); // Close the modal

            // Reset the form fields and remove highlights
            resetPOModalFields();

            // Trigger AJAX refresh to fetch updated item data
            fetchItem("refresh", itemNumValue);

        } else {
            alert("Error updating Instant PO: " + data.error);
        }
    })
    .catch(error => console.error("AJAX request failed:", error));
}


// Reset all fields and remove highlights
function resetAllFields() {
    let forms = document.querySelectorAll("form");
    forms.forEach(form => {
        form.reset(); // Reset all form fields

        let inputs = form.querySelectorAll("input, textarea, select");
        inputs.forEach(input => {
            input.classList.remove("highlight"); // Remove highlights
            input.classList.add("normal"); // Ensure it has the default style
            input.dataset.originalValue = input.value; // Set new original value
        });
    });
}

// Ensure the function is called on save, add, instant PO, delete actions, and AJAX refresh
document.addEventListener("DOMContentLoaded", function () {
    let saveButton = document.getElementById("saveItem");
    let addButton = document.getElementById("addItem");
    let instantPOButton = document.getElementById("instantPO");
    let deleteButton = document.getElementById("deleteItem");

    [saveButton, addButton, instantPOButton, deleteButton].forEach(button => {
        if (button) {
            button.addEventListener("click", resetAllFields);
        }
    });

    // Listen for AJAX refresh
    document.addEventListener("ajaxComplete", resetAllFields);
});

function resetPOModalFields() {
    let form = document.getElementById("pomodalForm");
    form.reset(); // Reset all form fields

    let inputs = form.querySelectorAll("input");
    inputs.forEach(input => {
        input.classList.remove("highlight"); // Remove highlights
        input.classList.add("normal"); // Ensure it has the default style
        input.dataset.originalValue = input.value; // Set new original value
    });
}

// Ensure the function is called on save, add, instant PO, and delete actions
document.addEventListener("DOMContentLoaded", function () {
    let saveButton = document.getElementById("saveItem");
    let addButton = document.getElementById("addItem");
    let instantPOButton = document.getElementById("instantPO");
    let deleteButton = document.getElementById("deleteItem");

    [saveButton, addButton, instantPOButton, deleteButton].forEach(button => {
        if (button) {
            button.addEventListener("click", resetPOModalFields);
        }
    });
});

function resetPOModalFields() {
    let form = document.getElementById("pomodalForm");
    form.reset(); // Reset all form fields

    let inputs = form.querySelectorAll("input");
    inputs.forEach(input => {
        input.classList.remove("highlight"); // Remove highlights
        input.classList.add("normal"); // Ensure it has the default style
        input.dataset.originalValue = input.value; // Set new original value
    });
}

function closePOModal() {
    document.getElementById("pomodal").style.display = "none";
}

document.addEventListener("DOMContentLoaded", function () {
    const deleteButton = document.getElementById("deleteButton");

    if (deleteButton) {
        deleteButton.addEventListener("click", function () {
            const itemNum = document.getElementById("itemNum").value; // Ensure the correct ID is used

            if (!itemNum) {
                alert("Please enter a valid Item Number before deleting.");
                return;
            }

            if (confirm(`Are you sure you want to delete item number ${itemNum}?`)) {
                fetch("deleteitem.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({ itemNum: itemNum }), // Make sure this matches your PHP script
                })
                .then(response => response.json()) // Expect JSON response
                .then(data => {
                    if (data.success) {
                        alert(`Item Number ${itemNum} deleted successfully!`);
                        window.location.reload(); // Refresh the page after deletion
                    } else {
                        alert(`Error deleting item: ${data.error}`);
                    }
                })
                .catch(error => {
                    console.error("Error deleting item:", error);
                    alert("An error occurred while deleting the item.");
                });
            }
        });
    }
});

document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("saveButton").addEventListener("click", function() {
        saveFormData();
    });
});

function saveFormData() {
    let formData = {
        department: document.getElementById("department").value,
        itemNum: document.getElementById("itemNum").value,
        description: document.getElementById("description").value,
        secondDescription: document.getElementById("secondDescription").value,
        avgCost: document.getElementById("avgCost").value,
        priceCharge: document.getElementById("priceCharge").value,  // Updated
        inStock: document.getElementById("inStock").value
    };

    sendAjaxRequest(formData);
}

function sendAjaxRequest(formData) {
    fetch("update_item.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Item updated successfully: ${formData.itemNum}`);
        } else {
            alert("Error updating data: " + data.error);
        }
    })
    .catch(error => console.error("AJAX request failed:", error));
}

function openPopup() {
    var width = 600;
    var height = 500;
    var left = (screen.width - width) / 2;
    var top = (screen.height - height) / 2;

    window.open('addentry.php', 'AddNewItem', `width=${width},height=${height},top=${top},left=${left},resizable=no,scrollbars=no,toolbar=no,menubar=no,status=no,location=no,directories=no`);
}

document.addEventListener("DOMContentLoaded", function () {
    function storeOriginalValue(input) {
        if (input.type === "checkbox" || input.type === "radio") {
            input.dataset.originalValue = input.checked ? "1" : "0"; // Store as 1 (checked) or 0 (unchecked)
        } else {
            input.dataset.originalValue = input.value.trim();
        }
    }

    function handleInputChange(event) {
        let input = event.target;
        let originalValue = input.dataset.originalValue;

        if (input.type === "radio") {
            let radioGroup = document.querySelectorAll(`input[name="${input.name}"]`);

            radioGroup.forEach(radio => {
                let radioOriginalValue = radio.dataset.originalValue;
                let radioCurrentValue = radio.checked ? "1" : "0";
                let label = document.querySelector(`label[for="${radio.id}"]`);

                // Unhighlight the previously selected radio button
                if (!radio.checked) {
                    radio.classList.remove("highlight");
                    radio.classList.add("normal");
                    if (label) {
                        label.classList.remove("highlight-label");
                        label.classList.add("normal-label");
                    }
                }

                // Highlight only the new selected radio button if it's different from the original
                if (radio.checked && radioCurrentValue !== radioOriginalValue) {
                    radio.classList.add("highlight");
                    radio.classList.remove("normal");
                    if (label) {
                        label.classList.add("highlight-label");
                        label.classList.remove("normal-label");
                    }
                } else if (radio.checked && radioCurrentValue === radioOriginalValue) {
                    // If the selected radio is the original one, unhighlight it
                    radio.classList.remove("highlight");
                    radio.classList.add("normal");
                    if (label) {
                        label.classList.remove("highlight-label");
                        label.classList.add("normal-label");
                    }
                }
            });
        } else if (input.type === "checkbox") {
            let currentValue = input.checked ? "1" : "0";
            let label = document.querySelector(`label[for="${input.id}"]`);

            if (currentValue !== originalValue) {
                if (label) {
                    label.classList.add("highlight-label");
                    label.classList.remove("normal-label");
                }
                input.classList.add("highlight");
                input.classList.remove("normal");
            } else {
                if (label) {
                    label.classList.remove("highlight-label");
                    label.classList.add("normal-label");
                }
                input.classList.remove("highlight");
                input.classList.add("normal");
            }
        } else {
            let currentValue = input.value.trim();
            if (currentValue !== originalValue) {
                input.classList.add("highlight");
                input.classList.remove("normal");
            } else {
                input.classList.remove("highlight");
                input.classList.add("normal");
            }
        }
    }

    function applyHighlighting(input) {
        storeOriginalValue(input);
        input.addEventListener("input", handleInputChange);
        if (input.type === "checkbox" || input.type === "radio") {
            input.addEventListener("change", handleInputChange);
        }
    }

    // Apply highlighting to existing elements
    document.querySelectorAll("input:not([type=hidden]), textarea, select").forEach(applyHighlighting);

    // Watch for AJAX updates that modify input fields (INCLUDING CHECKBOXES & RADIOS)
    const observer = new MutationObserver((mutations) => {
        mutations.forEach(mutation => {
            mutation.addedNodes.forEach(node => {
                if (node.nodeType === 1) { // Only process element nodes
                    node.querySelectorAll("input:not([type=hidden]), textarea, select").forEach(input => {
                        storeOriginalValue(input); // Store new AJAX values as the original
                        applyHighlighting(input);
                    });
                }
            });

            // If AJAX updates any input field (text, select, checkbox, radio), reset the stored original value
            document.querySelectorAll("input:not([type=hidden]), textarea, select").forEach(input => {
                let newValue = input.type === "checkbox" || input.type === "radio" 
                    ? (input.checked ? "1" : "0") 
                    : input.value.trim();

                if (newValue !== input.dataset.originalValue) {
                    input.dataset.originalValue = newValue; // Update stored value to match AJAX data
                    input.classList.remove("highlight"); // Reset highlight when AJAX sets value
                    input.classList.add("normal");

                    let label = document.querySelector(`label[for="${input.id}"]`);
                    if (label) {
                        label.classList.remove("highlight-label");
                        label.classList.add("normal-label");
                    }
                }
            });
        });
    });

    // Observe changes to the document (for dynamically added or modified elements)
    observer.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ["value", "checked"] });
});
