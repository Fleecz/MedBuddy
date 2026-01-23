<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}
require_once "config.php";
$benutzer_id = (int)($_SESSION["benutzer_id"] ?? 0);
if ($benutzer_id <= 0) {
    die("benutzer_id fehlt in der Session.");
}
function column_exists(mysqli $link, string $table, string $column): bool {
    $table_esc = mysqli_real_escape_string($link, $table);
    $col_esc   = mysqli_real_escape_string($link, $column);
    $sql = "SHOW COLUMNS FROM `$table_esc` LIKE '$col_esc'";
    $res = mysqli_query($link, $sql);
    if (!$res) return false;
    $ok = (mysqli_num_rows($res) > 0);
    mysqli_free_result($res);
    return $ok;
}
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_id"]) && ctype_digit($_POST["delete_id"])) {
    $delId = (int)$_POST["delete_id"];
    $sqlDel = "DELETE FROM `aktivität` WHERE `aktivität_id` = ? AND `benutzer_id` = ?";
    if ($stmtDel = mysqli_prepare($link, $sqlDel)) {
        mysqli_stmt_bind_param($stmtDel, "ii", $delId, $benutzer_id);
        mysqli_stmt_execute($stmtDel);
        mysqli_stmt_close($stmtDel);
    }
    $qs = [];
    if (isset($_GET["status"])) $qs["status"] = $_GET["status"];
    if (isset($_GET["sort"]))   $qs["sort"]   = $_GET["sort"];
    $target = "activities.php" . (count($qs) ? ("?" . http_build_query($qs)) : "");
    header("Location: $target");
    exit;
}
$status = $mapStatus[strtolower((string)$status)] ?? "active";
$sort = $_GET["sort"] ?? "date_desc";
if (!in_array($sort, ["date_desc", "date_asc"], true)) $sort = "date_desc";
$dateExpr = "STR_TO_DATE(a.datum, '%Y-%m-%d')";
$orderBy = ($sort === "date_asc")
    ? "$dateExpr ASC, a.aktivität_id ASC"
    : "$dateExpr DESC, a.aktivität_id DESC";
$whereStatus = ($status === "archiv") ? "$dateExpr < CURDATE()" : "$dateExpr >= CURDATE()";
$has_time_col = column_exists($link, "aktivität", "uhrzeit");
$timeSelect = $has_time_col ? ", a.uhrzeit" : "";
$timeHeader = $has_time_col ? "<th>Uhrzeit</th>" : "";
$userCol = column_exists($link, "benutzer", "username") ? "username" : "name";
$sql = "
SELECT
    a.aktivität_id,
    a.titel,
    a.beschreibung,
    a.category,
    a.datum
    $timeSelect,
    a.stimmungseintrag_id,
    s.stimmungswert,
    s.notiz AS stimmung_notiz
FROM `aktivität` a
LEFT JOIN `stimmungseintrag` s ON s.stimmungseintrag_id = a.stimmungseintrag_id
WHERE a.benutzer_id = ?
  AND $whereStatus
ORDER BY $orderBy
";
$stmt = mysqli_prepare($link, $sql);
if (!$stmt) {
    die("SQL-Fehler: " . htmlspecialchars(mysqli_error($link)));
}
mysqli_stmt_bind_param($stmt, "i", $benutzer_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Aktivitäten</title>
</head>
<body>
<h1>Aktivitäten</h1>
    <a href="dashboard.php">← Dashboard</a> |
    <a href="calendar.php">Kalender</a> |
    <a href="create.php">+ Neue Aktivität</a> |
    <a href="logout.php">Logout</a>
<hr>

<form method="get" action="activities.php">
    <label>Status:</label>
    <select name="status">
        <option value="active" <?php echo ($status === "active") ? "selected" : ""; ?>>Aktiv</option>
        <option value="archiv" <?php echo ($status === "archiv") ? "selected" : ""; ?>>Archiv</option>
    </select>

    <label>Sortierung:</label>
    <select name="sort">
        <option value="date_desc" <?php echo ($sort === "date_desc") ? "selected" : ""; ?>>Datum ↓</option>
        <option value="date_asc"  <?php echo ($sort === "date_asc") ? "selected" : ""; ?>>Datum ↑</option>
    </select>
    <button type="submit">Anwenden</button>
</form>
<hr>
<?php if ($result && mysqli_num_rows($result) > 0): ?>
    <table border="1" cellpadding="6" cellspacing="0">
        <thead>
        <tr>
            <th>#</th>
            <th>Titel</th>
            <th>Beschreibung</th>
            <th>Kategorie</th>
            <th>Datum</th>
            <?php echo $timeHeader; ?>
            <th>Stimmung</th>
            <th>Aktionen</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <?php
            $id = (int)$row["aktivität_id"];
            $titel = htmlspecialchars($row["titel"] ?? "", ENT_QUOTES, "UTF-8");
            $beschreibung = htmlspecialchars($row["beschreibung"] ?? "", ENT_QUOTES, "UTF-8");
            $category = htmlspecialchars($row["category"] ?? "", ENT_QUOTES, "UTF-8");
            $datum = htmlspecialchars($row["datum"] ?? "", ENT_QUOTES, "UTF-8");
            $uhrzeitCell = "";
            if ($has_time_col) {
                $uhrzeitCell = "<td>" . htmlspecialchars($row["uhrzeit"] ?? "", ENT_QUOTES, "UTF-8") . "</td>";
            }
            $stimmungHtml = "—";
            if (!empty($row["stimmungseintrag_id"])) {
                $sw = $row["stimmungswert"];
                $sn = $row["stimmung_notiz"];
                $stimmungHtml = "Wert: " . htmlspecialchars((string)$sw, ENT_QUOTES, "UTF-8");
                if ($sn !== null && trim((string)$sn) !== "") {
                    $stimmungHtml .= "<br><small>Notiz: " . htmlspecialchars((string)$sn, ENT_QUOTES, "UTF-8") . "</small>";
                }
            }
            $qs = http_build_query(["status" => $status, "sort" => $sort]);
            ?>
            <tr>
                <td><?php echo $id; ?></td>
                <td><?php echo $titel; ?></td>
                <td><?php echo $beschreibung; ?></td>
                <td><?php echo $category; ?></td>
                <td><?php echo $datum; ?></td>
                <?php echo $uhrzeitCell; ?>
                <td><?php echo $stimmungHtml; ?></td>
                <td>
                    <a href="update.php?id=<?php echo $id; ?>&<?php echo $qs; ?>">[Bearbeiten]</a>
                    <form method="post" action="activities.php?<?php echo $qs; ?>" style="display:inline;">
                        <input type="hidden" name="delete_id" value="<?php echo $id; ?>">
                        <button type="submit" onclick="return confirm('Wirklich löschen?');">[Löschen]</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p><em>Keine Aktivitäten gefunden.</em></p>
<?php endif; ?>
<?php
mysqli_stmt_close($stmt);
mysqli_close($link);
?>
</body>
</html>