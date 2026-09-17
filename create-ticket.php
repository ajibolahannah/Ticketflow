<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require 'sqlicon.php';
require 'upload-helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (isset($_POST['submit'])) {
    $user_id = $_SESSION['user_id'];
    $subject = $_POST['subject'];
    $priority = $_POST['priority'];
    $description = $_POST['description'];

    list($imageName, $uploadError) = handleImageUpload($_FILES['attachment'] ?? null);

    if ($subject == "" || $description == "") {
        echo '<script>alert("Please fill all required fields.");window.location.href = "create-ticket.php";</script>';
    } else if ($uploadError) {
        echo '<script>alert("' . $uploadError . '");window.location.href = "create-ticket.php";</script>';
    } else {
        $ticket_code = 'TCK-' . strtoupper(substr(uniqid(), -6));

        $stmt = mysqli_prepare($con, "INSERT INTO tickets (user_id, ticket_code, subject, description, image, priority, status) VALUES (?, ?, ?, ?, ?, ?, 'open')");
        mysqli_stmt_bind_param($stmt, "isssss", $user_id, $ticket_code, $subject, $description, $imageName, $priority);
        mysqli_stmt_execute($stmt);

        header("Location: myticket.php?created=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Ticket</title>
    <link rel="stylesheet" href="./create-ticket.css">
</head>
<body>
    <section class="wrapper">
        <button class="navbar-toggle">
            <span class="bar"></span><span class="bar"></span><span class="bar"></span>
        </button>
        <nav class="sidenav">
            <div class="image"><img src="./logo.png" alt="logo" class="logo"></div>
            <ul>
                <li><a href="./user_dash.php">Dashboard</a></li>
                <li><a href="./myticket.php">My Tickets</a></li>
                <li><a href="./create-ticket.php" class="active">Create Ticket</a></li>
                <li><a href="./logout.php">Log Out</a></li>
            </ul>
        </nav>
        <main class="create">
            <header class="create-header">
                <a href="./user_dash.php">← Dashboard</a>
                <h2>Create New Ticket</h2>
                <p>Fill the form below to submit your support request</p>
            </header>
            <section class="form">
                <form method="POST" action="create-ticket.php" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" placeholder="Enter ticket subject" required>
                    </div>
                    <div class="form-group">
                        <label for="priority">Priority</label>
                        <select id="priority" name="priority">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" placeholder="Describe your issue in detail" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="attachment">Attachment (optional)</label>
                        <input type="file" id="attachment" name="attachment" accept="image/*">
                    </div>
                    <button type="submit" name="submit">Submit Ticket</button>
                </form>
            </section>
        </main>
    </section>
<script>
    let togglebutton = document.querySelector(".navbar-toggle");
    let navmenu = document.querySelector(".sidenav");
    togglebutton.addEventListener("click", () =>{
        togglebutton.classList.toggle("active");
        navmenu.classList.toggle("active");
    })
    let navlinks = document.querySelectorAll(".sidenav ul li a");
    navlinks.forEach(link => {
        link.addEventListener("click", () => {
            togglebutton.classList.remove("active");
            navmenu.classList.remove("active");
        });
    });
</script>
</html>