function validateForm() {
            let description = document.getElementById("description").value.trim();
            
            if (description === "") {
                alert("Item Name / Description is required!");
                document.getElementById("description").focus(); // Focus on the input
                return false; // Prevent form submission
            }

            return true; // Allow form submission
        }