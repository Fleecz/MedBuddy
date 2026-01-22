<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}

require_once "config.php";

$current_user_id = (int)($_SESSION["benutzer_id"] ?? 0);
if ($current_user_id <= 0) {
    die("Fehler: benutzer_id fehlt in der Session.");
}

$title = "";
$t_err = "";
$desc = "";
$desc_err = "";
$category = "";
$category_err = "";
$date = "";
$date_err = "";

$mood_value = "";
$mood_note = "";
$mood_err = "";

$allowed_categories = ["Bewegung", "Entspannung", "Soziales", "Selbstfürsorge"];
$db_err = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // --- validate inputs ---
    $input_title = trim($_POST["title"] ?? "");
    if ($input_title === "") {
        $t_err = "Bitte gib einen Aktivitätstitel ein.";
    } else {
        $title = $input_title;
    }

    $input_desc = trim($_POST["desc"] ?? "");
    if ($input_desc === "") {
        $desc_err = "Bitte gib eine Beschreibung ein.";
    } else {
        $desc = $input_desc;
    }

    $input_date = trim($_POST["date"] ?? "");
    if ($input_date === "") {
        $date_err = "Bitte gib ein Datum ein.";
    } else {
        $date = $input_date;
    }

    $input_category = trim($_POST["category"] ?? "");
    if ($input_category === "" || !in_array($input_category, $allowed_categories, true)) {
        $category_err = "Bitte wähle eine gültige Kategorie.";
    } else {
        $category = $input_category;
    }

    $mood_value = trim($_POST["mood_value"] ?? "");
    $mood_note  = trim($_POST["mood_note"] ?? "");

    $stimmungseintrag_id = null;

    if ($mood_value !== "") {
        if (!ctype_digit($mood_value)) {
            $mood_err = "Stimmungswert muss eine Zahl (1–10) sein.";
        } else {
            $mv = (int)$mood_value;
            if ($mv < 1 || $mv > 10) {
                $mood_err = "Stimmungswert muss zwischen 1 und 10 liegen.";
            }
        }
    }

    if ($t_err === "" && $desc_err === "" && $date_err === "" && $category_err === "" && $mood_err === "") {

        // --- optionally insert mood entry ---
        if ($mood_value !== "") {
            $sqlMood = "INSERT INTO `stimmungseintrag` (`benutzer_id`, `datum`, `uhrzeit`, `stimmungswert`, `notiz`)
                        VALUES (?, ?, CURTIME(), ?, ?)";

            if ($stmtMood = mysqli_prepare($link, $sqlMood)) {
                $mv   = (int)$mood_value;
                $note = ($mood_note === "") ? null : $mood_note;

                mysqli_stmt_bind_param($stmtMood, "isis", $current_user_id, $date, $mv, $note);

                if (mysqli_stmt_execute($stmtMood)) {
                    $stimmungseintrag_id = (int)mysqli_insert_id($link);
                } else {
                    $db_err = "DB-Fehler beim Speichern der Stimmung: " . mysqli_stmt_error($stmtMood);
                }

                mysqli_stmt_close($stmtMood);
            } else {
                $db_err = "Fehler beim Vorbereiten (Stimmung): " . mysqli_error($link);
            }
        }

        // --- insert activity; IMPORTANT: keep NULL as NULL (not 0) ---
        if ($db_err === "") {
            if ($stimmungseintrag_id === null) {
                $sql = "INSERT INTO `aktivität` (`benutzer_id`, `titel`, `beschreibung`, `datum`, `category`, `stimmungseintrag_id`)
                        VALUES (?, ?, ?, ?, ?, NULL)";
                if ($stmt = mysqli_prepare($link, $sql)) {
                    mysqli_stmt_bind_param($stmt, "issss", $current_user_id, $title, $desc, $date, $category);

                    if (mysqli_stmt_execute($stmt)) {
                        mysqli_stmt_close($stmt);
                        mysqli_close($link);
                        header("Location: activities.php");
                        exit;
                    } else {
                        $db_err = "DB-Fehler beim Speichern der Aktivität: " . mysqli_stmt_error($stmt);
                    }

                    mysqli_stmt_close($stmt);
                } else {
                    $db_err = "Fehler beim Vorbereiten (Aktivität): " . mysqli_error($link);
                }
            } else {
                $sql = "INSERT INTO `aktivität` (`benutzer_id`, `titel`, `beschreibung`, `datum`, `category`, `stimmungseintrag_id`)
                        VALUES (?, ?, ?, ?, ?, ?)";
                if ($stmt = mysqli_prepare($link, $sql)) {
                    mysqli_stmt_bind_param($stmt, "issssi", $current_user_id, $title, $desc, $date, $category, $stimmungseintrag_id);

                    if (mysqli_stmt_execute($stmt)) {
                        mysqli_stmt_close($stmt);
                        mysqli_close($link);
                        header("Location: activities.php");
                        exit;
                    } else {
                        $db_err = "DB-Fehler beim Speichern der Aktivität: " . mysqli_stmt_error($stmt);
                    }

                    mysqli_stmt_close($stmt);
                } else {
                    $db_err = "Fehler beim Vorbereiten (Aktivität): " . mysqli_error($link);
                }
            }
        }
    }
}

mysqli_close($link);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Aktivitätseintrag anlegen</title>
</head>
<body>
<h1>Aktivitätseintrag anlegen</h1>
<p><a href="activities.php">← Zurück</a></p>

<?php if ($db_err !== ""): ?>
    <p style="color:red;"><?php echo htmlspecialchars($db_err, ENT_QUOTES, "UTF-8"); ?></p>
<?php endif; ?>

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"], ENT_QUOTES, "UTF-8"); ?>" method="post">
    <label>Aktivitätenname</label><br>
    <input type="text" name="title" value="<?php echo htmlspecialchars($title, ENT_QUOTES, "UTF-8"); ?>">
    <br><span style="color:red;"><?php echo htmlspecialchars($t_err, ENT_QUOTES, "UTF-8"); ?></span>

    <br><br>

    <label>Beschreibung</label><br>
    <input type="text" name="desc" value="<?php echo htmlspecialchars($desc, ENT_QUOTES, "UTF-8"); ?>">
    <br><span style="color:red;"><?php echo htmlspecialchars($desc_err, ENT_QUOTES, "UTF-8"); ?></span>

    <br><br>

    <label>Datum</label><br>
    <input type="date" name="date" value="<?php echo htmlspecialchars($date, ENT_QUOTES, "UTF-8"); ?>">
    <br><span style="color:red;"><?php echo htmlspecialchars($date_err, ENT_QUOTES, "UTF-8"); ?></span>

    <br><br>

    <label>Kategorie</label><br>
    <select name="category">
        <option value="">bitte wählen</option>
        <?php foreach ($allowed_categories as $c): ?>
            <option value="<?php echo htmlspecialchars($c, ENT_QUOTES, "UTF-8"); ?>" <?php echo ($category === $c) ? "selected" : ""; ?>>
                <?php echo htmlspecialchars($c, ENT_QUOTES, "UTF-8"); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <br><span style="color:red;"><?php echo htmlspecialchars($category_err, ENT_QUOTES, "UTF-8"); ?></span>

    <br><br>

    <hr>

    <h3>Optional: Stimmung</h3>
    <label>Stimmungswert (1–10)</label><br>
    <input type="number" name="mood_value" min="1" max="10" value="<?php echo htmlspecialchars($mood_value, ENT_QUOTES, "UTF-8"); ?>">
    <br><span style="color:red;"><?php echo htmlspecialchars($mood_err, ENT_QUOTES, "UTF-8"); ?></span>

    <br><br>

    <label>Notiz (optional)</label><br>
    <input type="text" name="mood_note" value="<?php echo htmlspecialchars($mood_note, ENT_QUOTES, "UTF-8"); ?>">

    <br><br>

    <input type="submit" value="Speichern">
    <a href="activities.php">Abbrechen</a>
</form>
</body>
</html>