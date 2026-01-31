<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/helpers.php';

require_login();
$userId = current_user_id();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* Welches Medikament wurde angeklickt? */
$selectedMedId = 0;
if (isset($_GET['med']) && ctype_digit((string)$_GET['med'])) {
    $selectedMedId = (int)$_GET['med'];
}

$sqlPlans = "
    SELECT
        ep.plan_id,
        ep.medikament_id,
        m.mname,
        ep.p_dosierung,
        ep.`häufigkeit`,
        GROUP_CONCAT(TIME_FORMAT(eu.uhrzeit, '%H:%i') ORDER BY eu.uhrzeit SEPARATOR ', ') AS uhrzeiten,
        ep.startdatum,
        ep.enddatum,
        ep.aktiv
    FROM einnahmeplan ep
    JOIN medikament m ON m.medikament_id = ep.medikament_id
    LEFT JOIN einnahmeplan_uhrzeit eu ON eu.plan_id = ep.plan_id
    WHERE ep.benutzer_id = ?
    GROUP BY
        ep.plan_id, ep.medikament_id, m.mname, ep.p_dosierung, ep.`häufigkeit`, ep.startdatum, ep.enddatum, ep.aktiv
    ORDER BY ep.plan_id DESC
";

$stmt = mysqli_prepare($link, $sqlPlans);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$plans = [];
while ($res && ($row = mysqli_fetch_assoc($res))) $plans[] = $row;
if ($res) mysqli_free_result($res);
mysqli_stmt_close($stmt);

$sideEffects = [];
$selectedMedName = '';
if ($selectedMedId > 0) {
    $stmtName = mysqli_prepare($link, "SELECT mname FROM medikament WHERE medikament_id = ?");
    mysqli_stmt_bind_param($stmtName, "i", $selectedMedId);
    mysqli_stmt_execute($stmtName);
    $rName = mysqli_stmt_get_result($stmtName);
    if ($rName && ($tmp = mysqli_fetch_assoc($rName))) $selectedMedName = (string)$tmp['mname'];
    if ($rName) mysqli_free_result($rName);
    mysqli_stmt_close($stmtName);

    $sqlNW = "
        SELECT DISTINCT
            n.bezeichnung,
            n.beschreibung,
            n.`häufigkeit` AS nw_haeufigkeit,
            n.schweregrad
        FROM medikament_wirkstoff mw
        JOIN nebenwirkung_wirkstoff nw ON nw.wirkstoff_id = mw.wirkstoff_id
        JOIN nebenwirkung n ON n.nebenwirkung_id = nw.nebenwirkung_id
        WHERE mw.medikament_id = ?
        ORDER BY n.schweregrad, n.bezeichnung
    ";
    $stmtNW = mysqli_prepare($link, $sqlNW);
    mysqli_stmt_bind_param($stmtNW, "i", $selectedMedId);
    mysqli_stmt_execute($stmtNW);
    $resNW = mysqli_stmt_get_result($stmtNW);

    while ($resNW && ($row = mysqli_fetch_assoc($resNW))) $sideEffects[] = $row;
    if ($resNW) mysqli_free_result($resNW);
    mysqli_stmt_close($stmtNW);
}

$interactions = [];
$sqlWW = "
    SELECT
        ww.wechselwirkung_id,
        ww.bezeichnung,
        ww.beschreibung,
        ww.empfehlung,
        ww.schweregrad,
        GROUP_CONCAT(DISTINCT m.mname ORDER BY m.mname SEPARATOR ', ') AS betroffene_medikamente
    FROM wechselwirkung ww
    JOIN wechselwirkung_wirkstoff wwk ON wwk.wechselwirkung_id = ww.wechselwirkung_id
    JOIN medikament_wirkstoff mw ON mw.wirkstoff_id = wwk.wirkstoff_id
    JOIN einnahmeplan ep ON ep.medikament_id = mw.medikament_id AND ep.benutzer_id = ?
    JOIN medikament m ON m.medikament_id = ep.medikament_id
    GROUP BY ww.wechselwirkung_id, ww.bezeichnung, ww.beschreibung, ww.empfehlung, ww.schweregrad
    HAVING COUNT(DISTINCT ep.medikament_id) >= 2
    ORDER BY ww.schweregrad DESC, ww.bezeichnung
";
$stmtWW = mysqli_prepare($link, $sqlWW);
mysqli_stmt_bind_param($stmtWW, "i", $userId);
mysqli_stmt_execute($stmtWW);
$resWW = mysqli_stmt_get_result($stmtWW);

while ($resWW && ($row = mysqli_fetch_assoc($resWW))) $interactions[] = $row;
if ($resWW) mysqli_free_result($resWW);
mysqli_stmt_close($stmtWW);
?>

<h1>Mein Einnahmeplan</h1>

<p>
    <a href="med.php"><button>Neues Medikament zum Plan hinzufügen</button></a>
    <a href="index.php"><button>Zurück zur Startseite</button></a>
</p>

<?php if (!$plans): ?>
    <p>Keine Einträge im Einnahmeplan.</p>
<?php else: ?>
    <table border="1" cellpadding="6" cellspacing="0">
        <tr>
            <th>Medikament</th>
            <th>Dosierung</th>
            <th>Häufigkeit</th>
            <th>Uhrzeiten</th>
            <th>Start</th>
            <th>Ende</th>
            <th>Aktiv</th>
        </tr>

        <?php foreach ($plans as $p): ?>
            <tr>
                <td>
                    <a href="eplan.php?med=<?php echo (int)$p['medikament_id']; ?>">
                        <?php echo e((string)$p['mname']); ?>
                    </a>
                </td>
                <td><?php echo e((string)$p['p_dosierung']); ?></td>
                <td><?php echo e((string)$p['häufigkeit']); ?></td>
                <td><?php echo e((string)($p['uhrzeiten'] ?? '')); ?></td>
                <td><?php echo e((string)$p['startdatum']); ?></td>
                <td><?php echo e((string)($p['enddatum'] ?? '')); ?></td>
                <td><?php echo ((int)$p['aktiv'] === 1) ? 'Ja' : 'Nein'; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<hr>

<h2>Nebenwirkungen</h2>

<?php if ($selectedMedId <= 0): ?>
    <p>Klicke oben in der Tabelle auf ein Medikament, um Nebenwirkungen zu sehen.</p>
<?php else: ?>
    <p><strong><?php echo e($selectedMedName); ?></strong></p>

    <?php if (!$sideEffects): ?>
        <p>Keine Nebenwirkungen gefunden (oder nicht verknüpft).</p>
    <?php else: ?>
        <ul>
            <?php foreach ($sideEffects as $n): ?>
                <li>
                    <strong><?php echo e((string)$n['bezeichnung']); ?></strong>
                    <?php
                        $hz = (string)($n['nw_haeufigkeit'] ?? '');
                        $sg = (string)($n['schweregrad'] ?? '');
                        $bs = (string)($n['beschreibung'] ?? '');
                    ?>
                    <?php if ($hz !== '') echo ' – ' . e($hz); ?>
                    <?php if ($sg !== '') echo ' – ' . e($sg); ?>
                    <?php if ($bs !== '') echo ': ' . e($bs); ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
<?php endif; ?>

<hr>

<h2>Wechselwirkungen in deinem Plan</h2>

<?php if (!$interactions): ?>
    <p>Keine Wechselwirkungen gefunden (oder nicht verknüpft).</p>
<?php else: ?>
    <ul>
        <?php foreach ($interactions as $w): ?>
            <li>
                <strong><?php echo e((string)$w['bezeichnung']); ?></strong>
                <?php
                    $bm = (string)($w['betroffene_medikamente'] ?? '');
                    $sg = (string)($w['schweregrad'] ?? '');
                    $bs = (string)($w['beschreibung'] ?? '');
                    $em = (string)($w['empfehlung'] ?? '');
                ?>
                <?php if ($bm !== '') echo ' (betrifft: ' . e($bm) . ')'; ?>
                <?php if ($sg !== '') echo ' – ' . e($sg); ?>
                <?php if ($bs !== '') echo ': ' . e($bs); ?>
                <?php if ($em !== '') echo ' Empfehlung: ' . e($em); ?>
            </li>
        <?php endforeach; ?>
    </ul>
    
<?php endif; ?>

