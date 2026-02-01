<?php
session_start();
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/config.php';
require_login();
$currentUserId = (int)($_SESSION["benutzer_id"] ?? 0);
if ($currentUserId <= 0) {
    die("benutzer_id fehlt in der Session.");
}
$activityTitle = "";
$titleError = "";
$activityDescription = "";
$descriptionError = "";
$activityCategory = "";
$categoryError = "";
$activityDate = "";
$dateError = "";
$moodScore = "";
$moodNote = "";
$moodError = "";
$allowed_categories = ["Bewegung", "Entspannung", "Soziales", "Selbstfürsorge"];
$errorMessage = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $input_title = post_str("title");
    if ($input_title === "") {
        $titleError = "Bitte gib einen Aktivitätstitel ein.";
    } else {
        $activityTitle = $input_title;
    }
    $input_desc = post_str("desc");
    if ($input_desc === "") {
        $descriptionError = "Bitte gib eine Beschreibung ein.";
    } else {
        $activityDescription = $input_desc;
    }
    $input_date = post_str("date");
    if ($input_date === "") {
        $dateError = "Bitte gib ein Datum ein.";
    } else {
        $activityDate = $input_date;
    }
    $input_category = post_str("category");
    if ($input_category === "" || !in_array($input_category, $allowed_categories, true)) {
        $categoryError = "Bitte wähle eine gültige Kategorie.";
    } else {
        $activityCategory = $input_category;
    }
    $moodScore = post_str("mood_value");
    $moodNote  = post_str("mood_note");
    $stimmungseintrag_id = null;
    if ($moodScore !== "") {
        if (!ctype_digit($moodScore)) {
            $moodError = "Stimmungswert muss eine Zahl (1–10) sein.";
        } else {
            $mv = (int)$moodScore;
            if ($mv < 1 || $mv > 10) {
                $moodError = "Stimmungswert muss zwischen 1 und 10 liegen.";
            }
        }
    }
    if ($titleError === "" && $descriptionError === "" && $dateError === "" && $categoryError === "" && $moodError === "") {
        if ($moodScore !== "") {
            $sqlMood = "INSERT INTO `stimmungseintrag` (`benutzer_id`, `datum`, `uhrzeit`, `stimmungswert`, `notiz`)
                        VALUES (?, ?, CURTIME(), ?, ?)";
            if ($stmtMood = mysqli_prepare($link, $sqlMood)) {
                $mv   = (int)$moodScore;
                $note = ($moodNote === "") ? null : $moodNote;
                mysqli_stmt_bind_param($stmtMood, "isis", $currentUserId, $activityDate, $mv, $note);

                if (mysqli_stmt_execute($stmtMood)) {
                    $stimmungseintrag_id = (int)mysqli_insert_id($link);
                } else {
                    $errorMessage = "Fehler beim Speichern der Stimmung: " . mysqli_stmt_error($stmtMood);
                }
                mysqli_stmt_close($stmtMood);
            } else {
                $errorMessage = "Fehler beim Vorbereiten (Stimmung): " . mysqli_error($link);
            }
        }
        if ($errorMessage === "") {
            if ($stimmungseintrag_id === null) {
                $sql = "INSERT INTO `aktivität` (`benutzer_id`, `titel`, `beschreibung`, `datum`, `category`, `stimmungseintrag_id`)
                        VALUES (?, ?, ?, ?, ?, NULL)";
                if ($stmt = mysqli_prepare($link, $sql)) {
                    mysqli_stmt_bind_param($stmt, "issss", $currentUserId, $activityTitle, $activityDescription, $activityDate, $activityCategory);
                    if (mysqli_stmt_execute($stmt)) {
                        mysqli_stmt_close($stmt);
                        mysqli_close($link);
                        redirect_to("activities.php");
                    } else {
                        $errorMessage = "Fehler beim Speichern der Aktivität: " . mysqli_stmt_error($stmt);
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $errorMessage = "Fehler beim Vorbereiten (Aktivität): " . mysqli_error($link);
                }
            } else {
                $sql = "INSERT INTO `aktivität` (`benutzer_id`, `titel`, `beschreibung`, `datum`, `category`, `stimmungseintrag_id`)
                        VALUES (?, ?, ?, ?, ?, ?)";
                if ($stmt = mysqli_prepare($link, $sql)) {
                    mysqli_stmt_bind_param($stmt, "issssi", $currentUserId, $activityTitle, $activityDescription, $activityDate, $activityCategory, $stimmungseintrag_id);
                    if (mysqli_stmt_execute($stmt)) {
                        mysqli_stmt_close($stmt);
                        mysqli_close($link);
                        redirect_to("activities.php");
                    } else {
                        $errorMessage = "Fehler beim Speichern der Aktivität: " . mysqli_stmt_error($stmt);
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $errorMessage = "Fehler beim Vorbereiten (Aktivität): " . mysqli_error($link);
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
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<h1>Aktivitätseintrag anlegen</h1>
<p><a href="activities.php">← Zurück</a></p>
<?php if ($errorMessage !== ""): ?>
    <p style="color:red;"><?php echo e($errorMessage); ?></p>
<?php endif; ?>
<form action="<?php echo e($_SERVER["PHP_SELF"]); ?>" method="post">
    <label>Aktivitätenname</label><br>
    <input type="text" name="title" value="<?php echo e($activityTitle); ?>">
    <br><span style="color:red;"><?php echo e($titleError); ?></span><br><br>
    <label>Beschreibung</label><br>
    <input type="text" name="desc" value="<?php echo e($activityDescription); ?>">
    <br><span style="color:red;"><?php echo e($descriptionError); ?></span>
    <br><br>
    <label>Datum</label><br>
    <input type="date" name="date" value="<?php echo e($activityDate); ?>">
    <br><span style="color:red;"><?php echo e($dateError); ?></span><br><br>
    <label>Kategorie</label><br>
    <select name="category">
        <option value="">bitte wählen</option>
        <?php foreach ($allowed_categories as $c): ?>
            <option value="<?php echo e($c); ?>" <?php echo ($activityCategory === $c) ? "selected" : ""; ?>>
                <?php echo e($c); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <br><span style="color:red;"><?php echo e($categoryError); ?></span><br><br>
    <hr>
    <h3>Optional: Stimmung</h3>
    <label>Stimmungswert (1–10)</label><br>
    <input type="number" name="mood_value" min="1" max="10" value="<?php echo e($moodScore); ?>">
    <br><span style="color:red;"><?php echo e($moodError); ?></span><br><br>
    <label>Notiz (optional)</label><br>
    <input type="text" name="mood_note" value="<?php echo e($moodNote); ?>"><br><br>
    <input type="submit" value="Speichern">
    <a href="activities.php">Abbrechen</a>
</form>
</body>
</html>
