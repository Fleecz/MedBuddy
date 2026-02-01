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
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<nav>
    <a href="eplan.php"><button>Medikamente</button></a>
    <a href="activities.php"><button>Aktivitäten</button></a>
    <a href="calendar.php"><button>Kalender</button></a>
    <a href="vertrauenspersonen.php"><button>Vertrauenspersonen</button></a>
    <a href="logout.php"><button>Logout</button></a>
    
</nav>
<h1>Dashboard</h1>
<p>Willkommen bei MedBuddy.</p>
</body>
</html>
