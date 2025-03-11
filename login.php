<?php
session_start();
require 'dbAuth.php'; // Include the database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        // Fetch the user from the MSSQL database
        $query = "SELECT * FROM users WHERE username = :username";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify the password
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;

            header('Location: index7.php');
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Please enter a username and password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        form {
            background: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        h2 {
            margin-bottom: 20px;
            color: #333;
        }
        label {
            display: block;
            font-size: 18px;
            margin-bottom: 10px;
            color: #555;
        }
        input[type="text"], input[type="password"] {
            width: 80%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }
        button {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: grab;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #0056b3;
        }
        p {
            color: #d9534f;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <form method="POST" action="">
        <h2>Login</h2>
        <?php if (!empty($error)) echo "<p>$error</p>"; ?>
        <label for="username">Username</label>
        <input type="text" title="Enter User Name" id="username" name="username" required>
        <label for="password">Password</label>
        <input type="password" title="Enter User Password" id="password" name="password" required>
        <button type="submit" title="Click here to Login">Login</button>
    </form>
</body>
</html>
