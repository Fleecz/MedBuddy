<?php
session_start();
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/config.php';
require_login();
$userId = (int)($_SESSION["benutzer_id"] ?? 0);
if ($userId <= 0) {
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
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $deleteIdRaw = post_str("delete_id");
    if ($deleteIdRaw !== "" && ctype_digit($deleteIdRaw)) {
        $activityId = (int)$deleteIdRaw;
        $sqlDel = "DELETE FROM `aktivität` WHERE `aktivität_id` = ? AND `benutzer_id` = ?";
        if ($stmtDel = mysqli_prepare($link, $sqlDel)) {
            mysqli_stmt_bind_param($stmtDel, "ii", $activityId, $userId);
            mysqli_stmt_execute($stmtDel);
            mysqli_stmt_close($stmtDel);
        }
        $qs = [];
        if (isset($_GET["status"])) $qs["status"] = get_str("status");
        if (isset($_GET["sort"]))   $qs["sort"]   = get_str("sort");
        redirect_to("index.php", $qs);
    }
}
$status = get_str("status", "active");
$mapStatus = [
  "active"   => "active",
  "aktiv"    => "active",
  "archiv"   => "archiv",
  "archive"  => "archiv",
  "archived" => "archiv",
];
$status = $mapStatus[strtolower((string)$status)] ?? "active";
$sort = get_str("sort", "date_desc");
if (!in_array($sort, ["date_desc", "date_asc"], true)) $sort = "date_desc";
$activityDate = "STR_TO_DATE(a.datum, '%Y-%m-%d')";
$orderBy = ($sort === "date_asc")
    ? "$activityDate ASC, a.aktivität_id ASC"
    : "$activityDate DESC, a.aktivität_id DESC";
$whereStatus = ($status === "archiv") ? "$activityDate < CURDATE()" : "$activityDate >= CURDATE()";
$has_time_col = column_exists($link, "aktivität", "uhrzeit");
$timeSelect = $has_time_col ? ", a.uhrzeit" : "";
$timeHeader = $has_time_col ? "<th>Uhrzeit</th>" : "";
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
$activityStmt = mysqli_prepare($link, $sql);
if (!$activityStmt) {
    die("SQL-Fehler: " . e(mysqli_error($link)));
}
mysqli_stmt_bind_param($activityStmt, "i", $userId);
mysqli_stmt_execute($activityStmt);
$activityResult = mysqli_stmt_get_result($activityStmt);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Aktivitäten</title>
</head>
<body>
<h1>Aktivitätseinträge</h1>
    <a href="index.php"><button>Dashboard</button></a> |
    <a href="create.php"><button>Neuen Eintrag erstellen</button></a> |
    <a href="calendar.php"><button>Kalender</button></a> |
    <a href="logout.php"><button>Logout</button></a>
<form method="get" style="margin: 0 0 20px 0;">
    <label>Status:
        <select name="status">
            <option value="active" <?php echo ($status === "active") ? "selected" : ""; ?>>Aktiv</option>
            <option value="archiv" <?php echo ($status === "archiv") ? "selected" : ""; ?>>Archiv</option>
        </select>
    </label>
    <label style="margin-left: 10px;">Sortierung:
        <select name="sort">
            <option value="date_desc" <?php echo ($sort === "date_desc") ? "selected" : ""; ?>>Datum (neu → alt)</option>
            <option value="date_asc" <?php echo ($sort === "date_asc") ? "selected" : ""; ?>>Datum (alt → neu)</option>
        </select>
    </label>
    <button type="submit" style="margin-left: 10px;">Anwenden</button>
</form>
<table border="1" cellpadding="8" cellspacing="0">
    <thead>
    <tr>
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
    <?php if ($activityResult && mysqli_num_rows($activityResult) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($activityResult)): ?>
            <?php
            $id = (int)$row["aktivität_id"];
            $titel = e($row["titel"] ?? "");
            $beschreibung = e($row["beschreibung"] ?? "");
            $category = e($row["category"] ?? "");
            $datum = e($row["datum"] ?? "");
            $uhrzeitCell = "";
            if ($has_time_col) {
                $uhrzeitCell = "<td>" . e($row["uhrzeit"] ?? "") . "</td>";
            }
            $stimmungHtml = "-";
            $sw = $row["stimmungswert"];
            if ($sw !== null && (string)$sw !== "") {
                $sn = $row["stimmung_notiz"];
                $stimmungHtml = "Wert: " . e((string)$sw);
                if ($sn !== null && trim((string)$sn) !== "") {
                    $stimmungHtml .= "<br><small>Notiz: " . e((string)$sn) . "</small>";
                }
            }
            ?>
            <tr>
                <td><?php echo $titel; ?></td>
                <td><?php echo $beschreibung; ?></td>
                <td><?php echo $category; ?></td>
                <td><?php echo $datum; ?></td>
                <?php echo $uhrzeitCell; ?>
                <td><?php echo $stimmungHtml; ?></td>
                <td>
                    <a href="update.php?id=<?php echo $id; ?>">Bearbeiten</a>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Wirklich löschen?');">
                        <input type="hidden" name="delete_id" value="<?php echo $id; ?>">
                        <button type="submit">Löschen</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="<?php echo $has_time_col ? "7" : "6"; ?>">Keine Aktivitäten gefunden.</td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>
</body>
<?php
mysqli_stmt_close($activityStmt);
mysqli_close($link);
?>
</html>