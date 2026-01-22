<?php
require_once "config.php";
$id = $_GET["id"] ?? "";
if (!ctype_digit($id)) {
    mysqli_close($link);
    die("Ungültige ID.");
}
$aktivitaet_id = (int)$id;

$sql = "DELETE FROM `aktivität` WHERE `aktivität_id` = ?";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $aktivitaet_id);
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($link);
        header("Location: activities.php");
        exit;
    }
    mysqli_stmt_close($stmt);
}
mysqli_close($link);
echo "Löschen fehlgeschlagen.";