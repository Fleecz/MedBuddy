<?php
session_start();
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/config.php';
require_login();
$benutzer_id = (int)($_SESSION["benutzer_id"] ?? 0);
if ($benutzer_id <= 0) {
    die("benutzer_id fehlt in der Session.");
}
$return_qs = [];
if (isset($_GET["status"])) $return_qs["status"] = (string)get_str("status");
if (isset($_GET["sort"]))   $return_qs["sort"]   = (string)get_str("sort");
$return_url = "activities.php" . (count($return_qs) ? ("?" . http_build_query($return_qs)) : "");
$idRaw = get_str("id", "");
if ($idRaw === "" || !ctype_digit($idRaw)) {
    die("Ungültige Aktivität.");
}
$aktivitaet_id = (int)$idRaw;
$sqlLoad = "
SELECT
  a.aktivität_id,
  a.titel,
  a.beschreibung,
  a.category,
  a.datum,
  a.stimmungseintrag_id,
  s.stimmungswert,
  s.notiz AS stimmung_notiz
FROM `aktivität` a
LEFT JOIN `stimmungseintrag` s ON s.stimmungseintrag_id = a.stimmungseintrag_id
WHERE a.aktivität_id = ? AND a.benutzer_id = ?
LIMIT 1
";
$stmtLoad = mysqli_prepare($link, $sqlLoad);
if (!$stmtLoad) {
    die("SQL-Fehler: " . e(mysqli_error($link)));
}
mysqli_stmt_bind_param($stmtLoad, "ii", $aktivitaet_id, $benutzer_id);
mysqli_stmt_execute($stmtLoad);
$resLoad = mysqli_stmt_get_result($stmtLoad);
$aktivitaet = $resLoad ? mysqli_fetch_assoc($resLoad) : null;
mysqli_stmt_close($stmtLoad);
if (!$aktivitaet) {
    mysqli_close($link);
    die("Aktivität nicht gefunden.");
}
$allowed_categories = ["Bewegung", "Entspannung", "Soziales", "Selbstfürsorge"];
$title = (string)($aktivitaet["titel"] ?? "");
$desc  = (string)($aktivitaet["beschreibung"] ?? "");
$category = (string)($aktivitaet["category"] ?? "");
$date_db  = (string)($aktivitaet["datum"] ?? "");
$date = $date_db;
$dt_init = DateTime::createFromFormat("Y-m-d", $date_db);
if ($dt_init && $dt_init->format("Y-m-d") === $date_db) {
    $date = $dt_init->format("d.m.Y");
}
$mood_value = ($aktivitaet["stimmungswert"] === null) ? "" : (string)$aktivitaet["stimmungswert"];
$mood_note  = (string)($aktivitaet["stimmung_notiz"] ?? "");
$t_err = $desc_err = $category_err = $date_err = $mood_err = "";
$db_err = "";
function parse_date_input(string $input): ?DateTime {
    $input = trim($input);
    $dt = DateTime::createFromFormat("d.m.Y", $input);
    if ($dt && $dt->format("d.m.Y") === $input) {
        return $dt;
    }
    $dt = DateTime::createFromFormat("Y-m-d", $input);
    if ($dt && $dt->format("Y-m-d") === $input) {
        return $dt;
    }
    return null;
}
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $input_title = post_str("title");
    if ($input_title === "") {
        $t_err = "Bitte gib einen Aktivitätstitel ein.";
    } else {
        $title = $input_title;
    }
    $input_desc = post_str("desc");
    if ($input_desc === "") {
        $desc_err = "Bitte gib eine Beschreibung ein.";
    } else {
        $desc = $input_desc;
    }
    $input_date = post_str("date");
    if ($input_date === "") {
        $date_err = "Bitte gib ein Datum ein.";
    } else {
        $dt = parse_date_input($input_date);
        if ($dt === null) {
            $date_err = "Bitte Datum im Format TT.MM.JJJJ eingeben.";
        } else {
            $date = $dt->format("d.m.Y");
            $date_db = $dt->format("Y-m-d");
        }
    }
    $input_category = post_str("category");
    if ($input_category === "" || !in_array($input_category, $allowed_categories, true)) {
        $category_err = ("Bitte wähle eine gültige Kategorie.");
    } else {
        $category = $input_category;
    }
    $mood_value = post_str("mood_value");
    $mood_note  = post_str("mood_note");
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
        mysqli_begin_transaction($link);
        try {
            $existing_mood_id = (int)($aktivitaet["stimmungseintrag_id"] ?? 0);
            $new_mood_id = ($existing_mood_id > 0) ? $existing_mood_id : null;
            if ($mood_value !== "") {
                $mv = (int)$mood_value;
                $note = ($mood_note === "") ? null : $mood_note;
                if ($existing_mood_id > 0) {
                    $sqlMoodUpd = "UPDATE `stimmungseintrag` SET `datum` = ?, `stimmungswert` = ?, `notiz` = ? WHERE `stimmungseintrag_id` = ? AND `benutzer_id` = ?";
                    $stmtMoodUpd = mysqli_prepare($link, $sqlMoodUpd);
                    if (!$stmtMoodUpd) throw new Exception("Fehler beim Vorbereiten (Stimmung-Update): " . mysqli_error($link));
                    mysqli_stmt_bind_param($stmtMoodUpd, "sisii", $date_db, $mv, $note, $existing_mood_id, $benutzer_id);
                    if (!mysqli_stmt_execute($stmtMoodUpd)) {
                        throw new Exception("DB-Fehler beim Aktualisieren der Stimmung: " . mysqli_stmt_error($stmtMoodUpd));
                    }
                    mysqli_stmt_close($stmtMoodUpd);
                } else {
                    $sqlMoodIns = "INSERT INTO `stimmungseintrag` (`benutzer_id`, `datum`, `uhrzeit`, `stimmungswert`, `notiz`) VALUES (?, ?, CURTIME(), ?, ?)";
                    $stmtMoodIns = mysqli_prepare($link, $sqlMoodIns);
                    if (!$stmtMoodIns) throw new Exception("Fehler beim Vorbereiten (Stimmung-Insert): " . mysqli_error($link));
                    mysqli_stmt_bind_param($stmtMoodIns, "isis", $benutzer_id, $date_db, $mv, $note);
                    if (!mysqli_stmt_execute($stmtMoodIns)) {
                        throw new Exception("DB-Fehler beim Speichern der Stimmung: " . mysqli_stmt_error($stmtMoodIns));
                    }
                    $new_mood_id = (int)mysqli_insert_id($link);
                    mysqli_stmt_close($stmtMoodIns);
                }
            } else {
                if ($existing_mood_id > 0) {
                    $new_mood_id = null;
                    $sqlMoodDel = "DELETE FROM `stimmungseintrag` WHERE `stimmungseintrag_id` = ? AND `benutzer_id` = ?";
                    $stmtMoodDel = mysqli_prepare($link, $sqlMoodDel);
                    if (!$stmtMoodDel) throw new Exception("Fehler beim Vorbereiten (Stimmung-Delete): " . mysqli_error($link));
                    mysqli_stmt_bind_param($stmtMoodDel, "ii", $existing_mood_id, $benutzer_id);
                    if (!mysqli_stmt_execute($stmtMoodDel)) {
                        throw new Exception("DB-Fehler beim Löschen der Stimmung: " . mysqli_stmt_error($stmtMoodDel));
                    }
                    mysqli_stmt_close($stmtMoodDel);
                }
            }
            if ($new_mood_id === null) {
                $sqlUpd = "UPDATE `aktivität` SET `titel` = ?, `beschreibung` = ?, `datum` = ?, `category` = ?, `stimmungseintrag_id` = NULL WHERE `aktivität_id` = ? AND `benutzer_id` = ?";
                $stmtUpd = mysqli_prepare($link, $sqlUpd);
                if (!$stmtUpd) throw new Exception("Fehler beim Vorbereiten (Aktivität-Update): " . mysqli_error($link));
                mysqli_stmt_bind_param($stmtUpd, "ssssii", $title, $desc, $date_db, $category, $aktivitaet_id, $benutzer_id);
            } else {
                $sqlUpd = "UPDATE `aktivität` SET `titel` = ?, `beschreibung` = ?, `datum` = ?, `category` = ?, `stimmungseintrag_id` = ? WHERE `aktivität_id` = ? AND `benutzer_id` = ?";
                $stmtUpd = mysqli_prepare($link, $sqlUpd);
                if (!$stmtUpd) throw new Exception("Fehler beim Vorbereiten (Aktivität-Update): " . mysqli_error($link));
                mysqli_stmt_bind_param($stmtUpd, "ssssiii", $title, $desc, $date_db, $category, $new_mood_id, $aktivitaet_id, $benutzer_id);
            }
            if (!mysqli_stmt_execute($stmtUpd)) {
                throw new Exception("DB-Fehler beim Aktualisieren der Aktivität: " . mysqli_stmt_error($stmtUpd));
            }
            mysqli_stmt_close($stmtUpd);
            mysqli_commit($link);
            mysqli_close($link);
            redirect_to("activities.php", $return_qs);
        } catch (Throwable $e) {
            mysqli_rollback($link);
            $db_err = $e->getMessage();
        }
    }
}
mysqli_close($link);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Aktivität bearbeiten</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <div class="card">
        <h1>Aktivität bearbeiten</h1>
        <p>
            <a href="<?php echo e($return_url); ?>">← Zurück</a>
        </p>
        <?php if ($db_err !== ""): ?>
            <p class="msg err"><strong><?php echo e($db_err); ?></strong></p>
        <?php endif; ?>
        <form method="post" action="<?php echo e($_SERVER["REQUEST_URI"]); ?>">
            <label>Aktivitätenname</label><br>
            <input type="text" name="title" value="<?php echo e($title); ?>">
            <?php if ($t_err !== ""): ?>
                <br><span class="msg err"><?php echo e($t_err); ?></span>
            <?php endif; ?>
            <br>
            <label>Beschreibung</label><br>
            <input type="text" name="desc" value="<?php echo e($desc); ?>">
            <?php if ($desc_err !== ""): ?>
                <br><span class="msg err"><?php echo e($desc_err); ?></span>
            <?php endif; ?>
            <br>
            <label>Datum</label><br>
            <input type="text" name="date" value="<?php echo e($date); ?>" placeholder="TT.MM.JJJJ" pattern="\d{2}\.\d{2}\.\d{4}" inputmode="numeric">
            <?php if ($date_err !== ""): ?>
                <br><span class="msg err"><?php echo e($date_err); ?></span>
            <?php endif; ?>
            <br>
            <label>Kategorie</label><br>
            <select name="category">
                <option value="">bitte wählen</option>
                <?php foreach ($allowed_categories as $c): ?>
                    <option value="<?php echo e($c); ?>" <?php echo ($category === $c) ? "selected" : ""; ?>>
                        <?php echo e($c); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($category_err !== ""): ?>
                <br><span class="msg err"><?php echo e($category_err); ?></span>
            <?php endif; ?>
            <br>
            <hr>
            <h3>Optional: Stimmung</h3>
            <label>Stimmungswert (1–10)</label><br>
            <input type="number" name="mood_value" min="1" max="10" value="<?php echo e($mood_value); ?>">
            <?php if ($mood_err !== ""): ?>
                <br><span class="msg err"><?php echo e($mood_err); ?></span>
            <?php endif; ?>
            <br>
            <label>Notiz (optional)</label><br>
            <input type="text" name="mood_note" value="<?php echo e($mood_note); ?>">
            <br><br>
            <input type="submit" value="Speichern">
            <a href="<?php echo e($return_url); ?>">Abbrechen</a>
        </form>
    </div>
</div>
</body>
</html>
