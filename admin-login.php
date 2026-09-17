<?php
session_start();
require 'sqlicon.php';

if (isset($_POST['submit'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = mysqli_prepare($con, "SELECT id, name, password, role FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    mysqli_stmt_bind_result($stmt, $id, $name, $dbPassword, $role);
    mysqli_stmt_fetch($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0 && $password === $dbPassword && $role === 'support') {
        $_SESSION['user_id'] = $id;
        $_SESSION['name'] = $name;
        $_SESSION['role'] = $role;

        header("Location: admin-dashboard.php");
        exit;
    } else {
        echo '<script>alert("Invalid credentials or not an admin account.");window.location.href = "admin-login.php";</script>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./admin-login.css">
    <title>Admin Login</title>
</head>
<body>
    <section class="card">
        <div class="text">
            <h2 class="wlc">Welcome back!</h2>
            <p>Sign in to access the admin dashboard</p>
        </div>
        <form action="./admin-login.php" method="POST" class="form">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="submit">Login</button>
        </form>
    </section>
</body>
</html>