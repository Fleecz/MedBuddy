<?php
session_start();
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/config.php';
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    redirect_to('index.php');
}
$username = '';
$password = '';
$username_err = '';
$password_err = '';
$login_err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = post_str('username');
    $password = post_str('password');
    if ($username === '') {
        $username_err = 'Geb deinen Nutzernamen oder deine Email-Adresse ein.';
    }
    if ($password === '') {
        $password_err = 'Geb bitte dein Passwort ein.';
    }
    if ($username_err === '' && $password_err === '') {
        $sql = "SELECT benutzer_id, username, email, passwort_hash
                FROM benutzer
                WHERE email = ? OR username = ?
                LIMIT 1";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, 'ss', $username, $username);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) === 1) {
                    mysqli_stmt_bind_result($stmt, $id, $db_username, $db_email, $hash);
                    if (mysqli_stmt_fetch($stmt) && password_verify($password, $hash)) {
                        $_SESSION['loggedin'] = true;
                        $_SESSION['benutzer_id'] = $id;
                        $_SESSION['username'] = $db_username;
                        $_SESSION['email'] = $db_email;

                        redirect_to('index.php');
                    } else {
                        $login_err = 'Ungültige Zugangsdaten.';
                    }
                } else {
                    $login_err = 'Ungültige Zugangsdaten.';
                }
            } else {
                $login_err = 'Login fehlgeschlagen. Bitte später erneut versuchen.';
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <div class="card">
        <h2>Login</h2>
        <?php if ($login_err !== ''): ?>
            <p class="msg err"><?php echo e($login_err); ?></p>
        <?php endif; ?>
        <form method="post">
            <label>Username oder Email</label><br>
            <input type="text" name="username" value="<?php echo e($username); ?>"><br>
            <?php if ($username_err !== ''): ?>
                <span class="msg err"><?php echo e($username_err); ?></span><br>
            <?php endif; ?>
            <br>
            <label>Passwort</label><br>
            <input type="password" name="password"><br>
            <?php if ($password_err !== ''): ?>
                <span class="msg err"><?php echo e($password_err); ?></span><br>
            <?php endif; ?>
            <br>
            <input type="submit" value="Login"><br><br>
            Noch kein Profil? <a href="register.php">Hier registrieren</a>
        </form>
    </div>
</div>
</body>
</html>
