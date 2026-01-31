<?php
session_start();
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/config.php';
require_login();
$currentUserId = ($_SESSION["benutzer_id"] ?? 0);
if ($currentUserId <= 0) {
    die("benutzer_id fehlt in der Session.");
    }
               

// Entfernen
if (isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];

    $stmt = mysqli_prepare($link, "
        DELETE FROM vertrauensperson
        WHERE vertrauensperson_id = ? AND benutzer_id = ?
    ");
    mysqli_stmt_bind_param($stmt,"ii",$id, $currentUserId);
    mysqli_stmt_execute($stmt);
    echo "<p class=\"msg ok\">Vertrauensperson gelöscht</p>";
    }

// Hinzufügen
if(isset($_POST["Hinzufügen"])){
    $name = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $beziehung = $_POST["benutzer_beziehung"];

    if ($name && $email) {
        $stmt = mysqli_prepare($link, "
            INSERT INTO vertrauensperson
            (benutzer_id, username, email, benutzer_beziehung)
            VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "isss", 
            $currentUserId,
            $name,
            $email,
            $beziehung);
        mysqli_stmt_execute($stmt);
        }
        header("Location: vertrauenspersonen.php");
        exit;   
    }
// Ändern
if (isset($_POST['update_id'])) {
    $stmt = mysqli_prepare($link, "
        UPDATE vertrauensperson
        SET username = ?, email = ?, benutzer_beziehung = ?
        WHERE vertrauensperson_id = ? AND benutzer_id = ?
    ");
    mysqli_stmt_bind_param($stmt, "sssii", 
        $_POST["username"] ??'',
        $_POST["email"] ??'',
        $_POST["benutzer_beziehung"] ??'',
        (int)$_POST['update_id'] ??'',
        $currentUserId
    );
    mysqli_stmt_execute($stmt);
    header("Location: vertrauenspersonen.php");
    exit;
}

// Liste
$stmt = mysqli_prepare($link, "
    SELECT vertrauensperson_id, username, email, benutzer_beziehung
    FROM vertrauensperson
    WHERE benutzer_id = ?
");
mysqli_stmt_bind_param($stmt, "i", $currentUserId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$personen = mysqli_fetch_all($result, MYSQLI_ASSOC);

//Bearbeiten
$editPerson = null;
if (isset($_GET['edit_id'])) {
    $editId = $_GET["edit_id"];

    $stmt = mysqli_prepare($link, "
        SELECT * FROM vertrauensperson
        WHERE vertrauensperson_id = ? AND benutzer_id = ?
    ");
    mysqli_stmt_bind_param($stmt, "ii", $editId, $currentUserId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $editPerson = mysqli_fetch_assoc($result);
}
?>
<html>
    <head>
        <meta charset="UTF-8">

        <title>Vertrauenspersonen_Verwaltung</title>

        <link rel="stylesheet" href="styles.css">
 
    </head>
    <body>
        <div class="container">
        <div class="card">
        <h1>Vertrauenspersonen: Verwaltung</h1>

        <a href="index.php"><button>Zurück zur Startseite</button></a>

        <h2>Hallo, <?php echo htmlspecialchars($_SESSION["username"] ?? "Gast"); ?></h2>

        <h2>In diesem Menü verwalten Sie ihre Vertrauensperonen.</h2>
        <br>
        <br>
                <h3>Meine Vertrauenspersonen</h3>
        <ul>
        <?php foreach ($personen as $p): ?>
            <li>
                <?= htmlspecialchars($p['username']) ?> –
                <?= htmlspecialchars($p['benutzer_beziehung']) ?>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="delete_id" value="<?= $p['vertrauensperson_id'] ?>">
                    <button type="submit">Entfernen</button>
                </form>
                <form method="get" style="display:inline;">
                    <input type="hidden" name="edit_id" value="<?= $p['vertrauensperson_id'] ?>">
                    <button type="submit">Bearbeiten</button>
                </form>
            </li>
        <?php endforeach; ?>
        </ul>
        <h3>Vertrauensperson hinzufügen</h3>
        <form  method="post">
        <label for="username">Nachname, Vorname</label><br>
        <input type="text" id="username" name="username" size="30" maxlength="50"><br>
        <label for="email">E-Mail:</label><br>
        <input type="email" id="email" name="email" size="30" maxlength="50"><br>
        <label for="Beziehung">Beziehung:</label>
        <select name="benutzer_beziehung" id="benutzer_beziehung">
            <option value="Freund">Freund</option>
            <option value="Familie">Familie</option>
            <option value="Arzt">Arzt</option>
        </select>   
            <br>
        <input type="submit" name="Hinzufügen"/>
        </form>
        <br>
        <br>
         <?php if ($editPerson !== null): ?>
        <h3>Vertrauensperson bearbeiten</h3>

        <form method="post">
            <input type="hidden" name="update_id" value="<?= ($editPerson['vertrauensperson_id'] ?? '') ?>">

            <label>Nachname, Vorname:</label><br>
            <input type="text" name="username"
                value="<?= htmlspecialchars($editPerson['username'] ?? '') ?>"><br>

            <label>E-Mail:</label><br>
            <input type="email" name="email"
                value="<?= htmlspecialchars($editPerson['email'] ?? '') ?>"><br>

            <label>Beziehung:</label><br>
            <select name="benutzer_beziehung">
                <option value="Freund" <?= ($editPerson['benutzer_beziehung'] ?? '')=='Freund'?'selected':'' ?>>Freund</option>
                <option value="Familie" <?= ($editPerson['benutzer_beziehung'] ?? '')=='Familie'?'selected':'' ?>>Familie</option>
                <option value="Arzt" <?= ($editPerson['benutzer_beziehung'] ?? '')=='Arzt'?'selected':'' ?>>Arzt</option>
            </select><br><br>

            <button type="submit">Änderungen speichern</button>
        </form>
        <?php endif; ?>
        </div>
        </div>
</body>
