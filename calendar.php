<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}
require_once "config.php";
$benutzer_id = (int)($_SESSION["benutzer_id"] ?? 0);
if ($benutzer_id <= 0) {
    die("Fehler: benutzer_id fehlt in der Session.");
}
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, "UTF-8"); }
$ym = $_GET["ym"] ?? date("Y-m");
if (!preg_match('/^\d{4}-\d{2}$/', $ym)) $ym = date("Y-m");
$year  = (int)substr($ym, 0, 4);
$month = (int)substr($ym, 5, 2);
$firstOfMonth = DateTime::createFromFormat("Y-m-d", sprintf("%04d-%02d-01", $year, $month));
if (!$firstOfMonth) $firstOfMonth = new DateTime("first day of this month");
$daysInMonth = (int)$firstOfMonth->format("t");
$startWeekday = (int)$firstOfMonth->format("N");
$selected = $_GET["day"] ?? null;
if ($selected !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected)) $selected = null;
$monthStart = $firstOfMonth->format("Y-m-01");
$monthEndDt = clone $firstOfMonth;
$monthEndDt->modify("last day of this month");
$monthEnd = $monthEndDt->format("Y-m-d");
$sqlMed = "
SELECT
    p.plan_id,
    p.startdatum,
    p.enddatum,
    p.uhrzeit,
    p.p_dosierung,
    p.häufigkeit,
    m.name AS medikament_name,
    ef.name AS einnahmeform_name
FROM einnahmeplan p
JOIN medikament m ON m.medikament_id = p.medikament_id
LEFT JOIN einnahmeform ef ON ef.einnahmeform_id = m.einnahmeform_id
WHERE p.benutzer_id = ?
  AND (p.aktiv = 1 OR p.aktiv IS NULL)
  AND (p.startdatum IS NULL OR p.startdatum <= ?)
  AND (p.enddatum   IS NULL OR p.enddatum   >= ?)
ORDER BY p.uhrzeit ASC, m.name ASC
";
$stmtMed = mysqli_prepare($link, $sqlMed);
if (!$stmtMed) die("SQL-Fehler (Med): " . h(mysqli_error($link)));
mysqli_stmt_bind_param($stmtMed, "iss", $benutzer_id, $monthEnd, $monthStart);
mysqli_stmt_execute($stmtMed);
$resMed = mysqli_stmt_get_result($stmtMed);
$plans = [];
while ($row = mysqli_fetch_assoc($resMed)) {
    $plans[] = $row;
}
mysqli_stmt_close($stmtMed);
$sqlAct = "
SELECT aktivität_id, titel, category, datum
FROM `aktivität`
WHERE benutzer_id = ?
  AND datum BETWEEN ? AND ?
ORDER BY datum ASC, aktivität_id ASC
";
$stmtAct = mysqli_prepare($link, $sqlAct);
if (!$stmtAct) die("SQL-Fehler (Akt): " . h(mysqli_error($link)));
mysqli_stmt_bind_param($stmtAct, "iss", $benutzer_id, $monthStart, $monthEnd);
mysqli_stmt_execute($stmtAct);
$resAct = mysqli_stmt_get_result($stmtAct);
$actsByDay = [];
while ($row = mysqli_fetch_assoc($resAct)) {
    $d = $row["datum"];
    if (!isset($actsByDay[$d])) $actsByDay[$d] = [];
    $actsByDay[$d][] = $row;
}
mysqli_stmt_close($stmtAct);
function plans_for_day(array $plans, string $day): array {
    $out = [];
    foreach ($plans as $p) {
        $start = $p["startdatum"] ?? null;
        $end   = $p["enddatum"] ?? null;
        if ($start !== null && $start !== "" && $start > $day) continue;
        if ($end   !== null && $end   !== "" && $end   < $day) continue;
        $out[] = $p;
    }
    return $out;
}
$prev = (clone $firstOfMonth)->modify("-1 month")->format("Y-m");
$next = (clone $firstOfMonth)->modify("+1 month")->format("Y-m");
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Kalender</title>
    <!--temporäres Styleshheet bis Bootstrap-->
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 20px; }
        nav a { margin-right: 12px; }
        .topbar { display:flex; align-items:center; justify-content:space-between; gap: 12px; }
        .monthnav a { padding: 6px 10px; border: 1px solid #ddd; border-radius: 8px; text-decoration: none; }
        .wrap { display: grid; grid-template-columns: 1fr 340px; gap: 18px; align-items:start; }
        .cal { border: 1px solid #e5e5e5; border-radius: 12px; overflow:hidden; }
        .cal .weekdays { display:grid; grid-template-columns: repeat(7, 1fr); background:#fafafa; border-bottom: 1px solid #eee; }
        .cal .weekdays div { padding: 10px; font-weight: 600; font-size: 13px; }
        .grid { display:grid; grid-template-columns: repeat(7, 1fr); }
        .day { min-height: 86px; border-right: 1px solid #f0f0f0; border-bottom: 1px solid #f0f0f0; padding: 8px; }
        .day:nth-child(7n) { border-right: none; }
        .day .num { display:flex; align-items:center; justify-content:space-between; margin-bottom: 6px; }
        .day a { text-decoration:none; color: inherit; display:block; height:100%; }
        .pill { display:inline-block; font-size: 11px; padding: 2px 6px; border-radius: 999px; border: 1px solid #ddd; }
        .muted { color:#777; font-size: 12px; }
        .selected { outline: 2px solid #000; outline-offset: -2px; }
        .side { border: 1px solid #e5e5e5; border-radius: 12px; padding: 12px 12px; }
        .side h2 { margin: 0 0 8px 0; font-size: 18px; }
        .list { margin: 0; padding-left: 18px; }
        .list li { margin: 6px 0; }
        .small { font-size: 12px; color:#666; }
        .empty { color:#777; font-style: italic; }
    </style>
</head>
<body>
<nav>
    <a href="dashboard.php">Dashboard</a>
    <a href="activities.php">Aktivitäten</a>
    <a href="calendar.php">Kalender</a>
    <a href="logout.php">Logout</a>
</nav>
<div class="topbar">
    <h1 style="margin: 10px 0;"><?php echo h($firstOfMonth->format("F Y")); ?></h1>
    <div class="monthnav">
        <a href="calendar.php?ym=<?php echo h($prev); ?>">←</a>
        <a href="calendar.php?ym=<?php echo h(date("Y-m")); ?>">Heute</a>
        <a href="calendar.php?ym=<?php echo h($next); ?>">→</a>
    </div>
</div>
<div class="wrap">
    <div class="cal">
        <div class="weekdays">
            <div>Mo</div><div>Di</div><div>Mi</div><div>Do</div><div>Fr</div><div>Sa</div><div>So</div>
        </div>
        <div class="grid">
            <?php
            for ($i=1; $i<$startWeekday; $i++) {
                echo '<div class="day"><div class="muted"> </div></div>';
            }
            for ($d=1; $d<=$daysInMonth; $d++) {
                $dayStr = sprintf("%04d-%02d-%02d", $year, $month, $d);
                $dayPlans = plans_for_day($plans, $dayStr);
                $dayActs = $actsByDay[$dayStr] ?? [];
                $countMed = count($dayPlans);
                $countAct = count($dayActs);
                $isSelected = ($selected === $dayStr);
                $cls = "day" . ($isSelected ? " selected" : "");
                echo '<div class="'. $cls .'">';
                echo '<a href="calendar.php?ym='. h($ym) .'&day='. h($dayStr) .'">';
                echo '<div class="num">';
                echo '<strong>'. (int)$d .'</strong>';
                echo '<span class="pill">'. $countMed .'🩺 '. $countAct .'🏃</span>';
                echo '</div>';
                $preview = [];
                if ($countMed > 0) {
                    $p0 = $dayPlans[0];
                    $preview[] = h(($p0["medikament_name"] ?? "Medikament"));
                }
                $shownActs = 0;
                foreach ($dayActs as $a) {
                    if ($shownActs >= 2) break;
                    $preview[] = h($a["titel"] ?? "Aktivität");
                    $shownActs++;
                }
                if (count($preview) === 0) {
                    echo '<div class="muted">—</div>';
                } else {
                    echo '<div class="small">'. implode("<br>", array_slice($preview, 0, 3)) .'</div>';
                }
                echo '</a>';
                echo '</div>';
            }
            $cells = ($startWeekday - 1) + $daysInMonth;
            $rest = (7 - ($cells % 7)) % 7;
            for ($i=0; $i<$rest; $i++) {
                echo '<div class="day"><div class="muted"> </div></div>';
            }
            ?>
        </div>
    </div>
    <aside class="side">
        <?php if ($selected === null): ?>
            <h2>Tag auswählen</h2>
            <p class="empty">Klicke im Kalender auf einen Tag, um die Einträge zu sehen.</p>
        <?php else: ?>
            <h2><?php echo h($selected); ?></h2>
            <?php
            $selPlans = plans_for_day($plans, $selected);
            $selActs  = $actsByDay[$selected] ?? [];
            ?>
            <h3 style="margin-bottom:6px;">Einnahmen</h3>
            <?php if (count($selPlans) === 0): ?>
                <p class="empty">Keine Einnahmen für diesen Tag.</p>
            <?php else: ?>
                <ul class="list">
                    <?php foreach ($selPlans as $p): ?>
                        <?php
                        $time = $p["uhrzeit"] ? substr((string)$p["uhrzeit"], 0, 5) : "";
                        $m = trim((string)($p["medikament_name"] ?? ""));
                        $f = trim((string)($p["einnahmeform_name"] ?? ""));
                        $dose = trim((string)($p["p_dosierung"] ?? ""));
                        $line = ($time ? ($time . " – ") : "") . $m;
                        if ($f !== "") $line .= " (" . $f . ")";
                        if ($dose !== "") $line .= " – " . $dose;
                        ?>
                        <li><?php echo h($line); ?></li>
                    <?php endforeach; ?>
                </ul>
                <p class="small">Quelle: Join aus <code>einnahmeplan</code>, <code>medikament</code>, <code>einnahmeform</code>.</p>
            <?php endif; ?>
            <h3 style="margin:14px 0 6px;">Aktivitäten</h3>
            <?php if (count($selActs) === 0): ?>
                <p class="empty">Keine Aktivitäten für diesen Tag.</p>
            <?php else: ?>
                <ul class="list">
                    <?php foreach ($selActs as $a): ?>
                        <?php
                        $title = trim((string)($a["titel"] ?? ""));
                        $cat = trim((string)($a["category"] ?? ""));
                        $line = $title . ($cat !== "" ? " – " . $cat : "");
                        ?>
                        <li><?php echo h($line); ?></li>
                    <?php endforeach; ?>
                </ul>
                <p class="small">Hinweis: Details bleiben im Aktivitäten-Tab ausführlicher (hier bewusst kompakt).</p>
            <?php endif; ?>
        <?php endif; ?>
    </aside>
</div>
<?php mysqli_close($link); ?>
</body>
</html>