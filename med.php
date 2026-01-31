<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/helpers.php';

require_login();
$userId = current_user_id();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$search  = trim((string)($_GET['q'] ?? ''));
$saved   = (($_GET['saved'] ?? '') === '1');
$message = '';

$freqRaw = trim((string)($_GET['freq'] ?? '1'));
$freq = (ctype_digit($freqRaw) ? (int)$freqRaw : 1);
if ($freq < 1) $freq = 1;
if ($freq > 6) $freq = 6;

$meds = [];
if ($search !== '') {
    $stmt = mysqli_prepare(
        $link,
        "SELECT medikament_id, mname
         FROM medikament
         WHERE mname LIKE CONCAT('%', ?, '%')
         ORDER BY mname
         LIMIT 50"
    );
    mysqli_stmt_bind_param($stmt, "s", $search);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($res && ($row = mysqli_fetch_assoc($res))) $meds[] = $row;
    if ($res) mysqli_free_result($res);
    mysqli_stmt_close($stmt);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['render_times'])) {
    $postFreqRaw = trim((string)($_POST['häufigkeit'] ?? '1'));
    $newFreq = (ctype_digit($postFreqRaw) ? (int)$postFreqRaw : 1);
    if ($newFreq < 1) $newFreq = 1;
    if ($newFreq > 6) $newFreq = 6;

    $self = $_SERVER['PHP_SELF']; // z.B. /MedBuddy/med_neu.php
    header("Location: {$self}?q=" . urlencode($search) . "&freq=" . $newFreq);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_plan'])) {
    $medikamentIdRaw = trim((string)($_POST['medikament_id'] ?? ''));
    $p_dosierung     = trim((string)($_POST['p_dosierung'] ?? ''));
    $haeufigkeitRaw  = trim((string)($_POST['häufigkeit'] ?? ''));
    $startdatum      = trim((string)($_POST['startdatum'] ?? ''));
    $enddatum        = trim((string)($_POST['enddatum'] ?? ''));

    $uhrzeiten = $_POST['uhrzeit'] ?? [];
    if (!is_array($uhrzeiten)) $uhrzeiten = [];

    if ($medikamentIdRaw === '' || !ctype_digit($medikamentIdRaw)) {
        $message = "Bitte ein Medikament auswählen.";
    } elseif ($p_dosierung === '' || $haeufigkeitRaw === '' || $startdatum === '') {
        $message = "Bitte Dosierung, Häufigkeit und Startdatum ausfüllen.";
    } elseif (!ctype_digit($haeufigkeitRaw) || (int)$haeufigkeitRaw < 1) {
        $message = "Häufigkeit muss eine positive ganze Zahl sein.";
    } elseif (!is_valid_date_ymd($startdatum)) {
        $message = "Startdatum ist ungültig (YYYY-MM-DD).";
    } elseif ($enddatum !== '' && !is_valid_date_ymd($enddatum)) {
        $message = "Enddatum ist ungültig (YYYY-MM-DD).";
    } else {
        $medikamentId = (int)$medikamentIdRaw;
        $haeufigkeit  = (int)$haeufigkeitRaw;
        if ($haeufigkeit > 6) $haeufigkeit = 6;

        $cleanTimes = [];
        foreach ($uhrzeiten as $t) {
            $t = trim((string)$t);
            if ($t === '') continue;
            if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $t)) {
                $message = "Uhrzeit muss im Format HH:MM sein.";
                break;
            }
            $cleanTimes[] = $t;
        }

        if ($message === '') {
            if (count($cleanTimes) !== $haeufigkeit) {
                $message = "Bitte genau {$haeufigkeit} Uhrzeiten angeben.";
            } else {
                $endToBind = ($enddatum === '') ? null : $enddatum;

                mysqli_begin_transaction($link);

                try {
                    $stmtPlan = mysqli_prepare(
                        $link,
                        "INSERT INTO einnahmeplan
                         (benutzer_id, medikament_id, p_dosierung, `häufigkeit`, startdatum, enddatum, aktiv)
                         VALUES (?, ?, ?, ?, ?, ?, 1)"
                    );
                    mysqli_stmt_bind_param(
                        $stmtPlan,
                        "iisiss",
                        $userId,
                        $medikamentId,
                        $p_dosierung,
                        $haeufigkeit,
                        $startdatum,
                        $endToBind
                    );
                    mysqli_stmt_execute($stmtPlan);
                    mysqli_stmt_close($stmtPlan);

                    $planId = mysqli_insert_id($link);
                    if ($planId <= 0) {
                        throw new Exception("DB-Fehler: plan_id konnte nicht ermittelt werden.");
                    }

                    $stmtTime = mysqli_prepare(
                        $link,
                        "INSERT INTO einnahmeplan_uhrzeit (plan_id, uhrzeit) VALUES (?, ?)"
                    );
                    foreach ($cleanTimes as $time) {
                        mysqli_stmt_bind_param($stmtTime, "is", $planId, $time);
                        mysqli_stmt_execute($stmtTime);
                    }
                    mysqli_stmt_close($stmtTime);

                    mysqli_commit($link);

                    $self = $_SERVER['PHP_SELF'];
                    header("Location: {$self}?saved=1");
                    exit;

                } catch (Throwable $e) {
                    mysqli_rollback($link);
                    $message = "Speichern fehlgeschlagen: " . $e->getMessage();
                }
            }
        }
    }
}
?>
<h1>Einnahmeplan erstellen</h1>

<a href="eplan.php"><button>Medikamentenübersicht</button></a>
<a href="index.php"><button>Zurück zur Startseite</button></a>

<?php if ($saved): ?>
<p>Plan gespeichert.</p>
<?php endif; ?>

<?php if ($message !== ''): ?>
<p><?php echo e($message); ?></p>
<?php endif; ?>

<form method="get" action="">
    <label>Medikament suchen:
        <input name="q" value="<?php echo e($search); ?>">
    </label>
    <button type="submit">Suchen</button>
</form>

<hr>

<form method="post" action="">
    <h2>1) Medikament auswählen</h2>

    <?php if ($search === ''): ?>
        <p>Suchbegriff eingeben und suchen.</p>
    <?php elseif (!$meds): ?>
        <p>Keine Treffer.</p>
    <?php else: ?>
        <?php foreach ($meds as $m): ?>
            <label>
                <input type="radio" name="medikament_id" value="<?php echo (int)$m['medikament_id']; ?>">
                <?php echo e((string)$m['mname']); ?>
            </label><br>
        <?php endforeach; ?>
    <?php endif; ?>

    <h2>2) Plan-Daten</h2>

    <label>Dosierung:
        <input name="p_dosierung" placeholder="z.B. 1 Tablette">
    </label><br><br>

    <label>Häufigkeit (Zahl):
        <input name="häufigkeit" value="<?php echo e((string)$freq); ?>" placeholder="z.B. 2">
    </label>
    <button type="submit" name="render_times" value="1">Uhrzeiten anzeigen</button>
    <br><br>

    <h3>Uhrzeiten</h3>
    <?php for ($i = 0; $i < $freq; $i++): ?>
        <label>Uhrzeit <?php echo $i + 1; ?>:
            <input name="uhrzeit[]" placeholder="HH:MM">
        </label><br><br>
    <?php endfor; ?>

    <label>Startdatum:
        <input name="startdatum" placeholder="YYYY-MM-DD">
    </label><br><br>

    <label>Enddatum (optional):
        <input name="enddatum" placeholder="YYYY-MM-DD">
    </label><br><br>

    <button type="submit" name="save_plan" value="1">Plan speichern</button>

</form>
