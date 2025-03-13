<?php
session_start();
require 'dbAuth.php'; // Include the database connection

// **Set PHP timezone to match MSSQL server**
date_default_timezone_set('America/New_York');  // Change if needed

// **Login System Integration**
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // Validate user input
        if (empty($username) || empty($password)) {
            $error_message = "Username and password required.";
        } else {
            // Check credentials in the database
            $conn->exec("USE UserAuth;");
            $query = "SELECT * FROM users WHERE username = :username";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['logged_in'] = true;
                $_SESSION['username'] = $user['username'];
                
            } else {
                $error_message = "Invalid username or password.";
            }
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login</title>
    </head>
    <body>
        <h2>Admin Login</h2>
        <?php if (isset($error_message)) { echo "<p style='color: red;'>$error_message</p>"; } ?>
        <form method="POST" action="">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            <button type="submit" name="login">Login</button>
        </form>
    </body>
    </html>
    <?php
    exit();
}

// **Handle form submission to update block level**
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ip_to_manage'])) {
    $ip_to_manage = $_POST['ip_to_manage'] ?? '';
    $new_block_level = $_POST['new_block_level'] ?? '';

    if (!in_array($new_block_level, ['0', '1', '2', '3'], true)) {
        die("Invalid block level. Select 0 (Unblocked), 1 (24-hour block), 2 (72-hour block), or 3 (Permanent block). ");
    }

    $query = "SELECT * FROM login_attempts WHERE ip_address = :ip_address";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':ip_address', $ip_to_manage, PDO::PARAM_STR);
    $stmt->execute();
    $ip_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($ip_data) {
        $query = "UPDATE login_attempts SET block_level = :block_level, attempts = 0, last_attempt = CURRENT_TIMESTAMP WHERE ip_address = :ip_address";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':block_level', $new_block_level, PDO::PARAM_INT);
        $stmt->bindParam(':ip_address', $ip_to_manage, PDO::PARAM_STR);
        $stmt->execute();
        echo "<p>Block level for IP <strong>$ip_to_manage</strong> updated to <strong>$new_block_level</strong>.</p>";
    } else {
        echo "<p>Error: IP address not found.</p>";
    }
}

// **Fetch all blocked IPs**
$query = "SELECT ip_address, attempts, block_level, last_attempt FROM login_attempts WHERE block_level > 0 ORDER BY block_level DESC, last_attempt DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$blocked_ips = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin: Manage Blocked IPs</title>
</head>
<body>
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
    <form method="POST" action="">
        <h2>Manage Blocked IPs</h2>
        <label for="ip_to_manage">IP Address to Manage</label>
        <input type="text" id="ip_to_manage" name="ip_to_manage" required>
        <label for="new_block_level">New Block Level</label>
        <select id="new_block_level" name="new_block_level" required>
            <option value="0">0 - Unblocked</option>
            <option value="1">1 - 24-hour block</option>
            <option value="2">2 - 72-hour block</option>
            <option value="3">3 - Permanent block</option>
        </select>
        <button type="submit">Update Block Level</button>
    </form>
    <table>
        <tr>
            <th>IP Address</th>
            <th>Attempts</th>
            <th>Block Level</th>
            <th>Last Attempt</th>
        </tr>
        <?php foreach ($blocked_ips as $ip) { ?>
            <tr>
                <td><?php echo htmlspecialchars($ip['ip_address']); ?></td>
                <td><?php echo htmlspecialchars($ip['attempts']); ?></td>
                <td><?php echo htmlspecialchars($ip['block_level']); ?></td>
                <td><?php echo htmlspecialchars($ip['last_attempt']); ?></td>
            </tr>
        <?php } ?>
    </table>
    <a href="logoutip.php"><button>Logout</button></a>
</body>
</html>
