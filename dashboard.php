<?php
session_start();
if (!isset($_SESSION["loggedin"])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<head>
    <nav>
        <a href="./activities.php">Aktivitätenübersicht</a>
    </nav>
</head>
    <body>
        <p>grober Tets</p>
    </body>
</html>