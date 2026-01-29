<?php
session_start();
require_once __DIR__ . '/lib/helpers.php';
require_login();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
</head>
<body>
<nav>
    <a href="activities.php">Aktivitäten</a>
    <a href="calendar.php">Kalender</a>
    <a href="vertrauenspersonen.php">Vertrauenspersonen</a>
    <a href="logout.php">Logout</a>
</nav>
<h1>Dashboard</h1>
<p>Willkommen bei MedBuddy.</p>
</body>
</html>
