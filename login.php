<?php
session_start();
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("Location: dashboard.php");
    exit;
}
require_once "config.php";
$username = $password = "";
$username_err = $password_err = $login_err = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty(trim($_POST["username"]))) {
        $username_err = "Geb deinen Nutzernamen oder deine Email-Adresse ein.";
    } else {
        $username = trim($_POST["username"]);
    }
    if (empty(trim($_POST["password"]))) {
        $password_err = "Geb bitte dein Passwort ein.";
    } else {
        $password = trim($_POST["password"]);
    }
    if (empty($username_err) && empty($password_err)) {
        $sql = "SELECT benutzer_id, username, email, passwort_hash
                FROM benutzer
                WHERE email = ? OR username = ?
                LIMIT 1";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ss", $param_login1, $param_login2);
            $param_login1 = $username;
            $param_login2 = $username;
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) === 1) {
                    mysqli_stmt_bind_result(
                        $stmt,
                        $benutzer_id,
                        $db_username,
                        $db_email,
                        $hashed_password
                    );
                    if (mysqli_stmt_fetch($stmt)) {
                        if (password_verify($password, $hashed_password)) {
                            $_SESSION["loggedin"]   = true;
                            $_SESSION["benutzer_id"] = $benutzer_id;
                            $_SESSION["username"]    = $db_username;
                            $_SESSION["email"]       = $db_email;
                            header("Location: dashboard.php");
                            exit;
                        } else {
                            $login_err = "Ungültiger Benutzername / Email oder Passwort.";
                        }
                    }
                } else {
                    $login_err = "Ungültiger Benutzername / Email oder Passwort.";
                }
            } else {
                $login_err = "Login fehlgeschlagen. Bitte später erneut versuchen.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    mysqli_close($link);
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
</head>
<body>
<h2>Login</h2>
<?php if (!empty($login_err)): ?>
    <p style="color:red;"><?php echo $login_err; ?></p>
<?php endif; ?>
<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
    <label>Username oder Email</label><br>
    <input type="text" name="username"
           value="<?php echo htmlspecialchars($username); ?>"><br>
    <span style="color:red;"><?php echo $username_err; ?></span><br><br>
    <label>Passwort</label><br>
    <input type="password" name="password"><br>
    <span style="color:red;"><?php echo $password_err; ?></span><br><br>
    <input type="submit" value="Login">
    <p>Noch keinen Account? <a href="register.php">Registrieren</a></p>
</form>
</body>
</html>