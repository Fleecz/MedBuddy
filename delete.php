<?php
session_start();
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/config.php';
require_login();
$userId = current_user_id();
$id = get_str('id');
if (!ctype_digit($id)) {
    exit('Ungültige ID.');
}
$sql = "DELETE FROM aktivität WHERE aktivität_id = ? AND benutzer_id = ?";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, 'ii', $id, $userId);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
redirect_to('activities.php');