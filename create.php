<?php
require_once "config.php";
$title = "";
$t_err = "";
$desc = "";
$desc_err = "";
$category = "";
$category_err = "";
$date = "";
$date_err = "";
$allowed_categories = ["Bewegung", "Entspannung", "Soziales", "Selbstfürsorge"];
if ($_SERVER["REQUEST_METHOD"] === "POST") {
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
    if ($t_err === "" && $desc_err === "" && $date_err === "" && $category_err === "") {
        $sql = "INSERT INTO aktivität (benutzer_id, titel, beschreibung, datum, category) VALUES (?, ?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param(
                $stmt,
                "issss",
                $param_user_id,
                $param_titel,
                $param_beschreibung,
                $param_datum,
                $param_category
            );
            $param_user_id = $current_user_id;
            $param_titel = $title;
            $param_beschreibung = $desc;
            $param_datum = $date;
            $param_category = $category;
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                mysqli_close($link);
                header("location: dashboard.php");
                exit();
            } else {
                echo "Fehler. Versuch es später erneut.";
            }
            mysqli_stmt_close($stmt);
        } else {
            echo "Fehler beim Vorbereiten des Statements.";
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
    <h2>Aktivitätseintrag anlegen</h2>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <label>Aktivitätenname</label><br>
        <input type="text" name="title" class="<?php echo (!empty($t_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($title); ?>">
        <span><?php echo $t_err; ?></span><br><br>
        <label>Beschreibung</label><br>
        <input type="text" name="desc" class="<?php echo (!empty($desc_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($desc); ?>">
        <span><?php echo $desc_err; ?></span><br><br>
        <label>Datum</label><br>
        <input type="date" name="date" class="<?php echo (!empty($date_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($date); ?>">
        <span><?php echo $date_err; ?></span><br><br>
        <label>Kategorie</label><br>
        <select name="category" class="<?php echo (!empty($category_err)) ? 'is-invalid' : ''; ?>">
            <option value="">bitte wählen</option>
            <?php foreach ($allowed_categories as $c): ?>
                <option value="<?php echo htmlspecialchars($c); ?>" <?php echo ($category === $c) ? "selected" : ""; ?>>
                    <?php echo htmlspecialchars($c); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <span><?php echo $category_err; ?></span><br><br>
        <input type="submit" value="Speichern">
        <a href="dashboard.php">Abbrechen</a>
    </form>
</body>
</html>