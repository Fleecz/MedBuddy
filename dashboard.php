<?php
session_start();
if (!isset($_SESSION["loggedin"])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
</head>
<body>
<nav>
    <a href="./activities.php">Aktivitäten</a>
    <a href="./calendar.php">Kalender</a>
    <a href="./logout.php">Logout</a>
</nav>
<h1>Dashboard</h1>
<p>Willkommen bei MedBuddy.</p>
</body>
</html>