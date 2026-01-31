<?php
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/config.php';
$username = $email = $password = $confirm_password = "";
$username_err = $email_err = $password_err = $confirm_password_err = "";
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty(post_str("username"))){
        $username_err = "Bitte geb deinen Nutzernamen nach dem Schema <Vorname.Nachname> ein";
    } else{
        $username = post_str("username");
        if(!preg_match("/^[\p{L}][\p{L}\s\'\-]{1,98}$/u", $username)){
            $username_err = "Der Nutername kann nur aus Buchstaben, Zahlen und _ bestehen.";
        }
    }
    if(empty(post_str("email"))){
        $email_err = "Geb bitte deine Email-Adresse ein.";
    } else{
        $email = post_str("email");
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            $email_err = "Bitte gib eine gültige Email-Adresse an.";
        }
    }
    if(empty(post_str("password"))){
        $password_err = "Geb bitte ein Passwort ein.";
    } else{
        $password = post_str("password");
    }
    if(empty(post_str("confirm_password"))){
        $confirm_password_err = "Bitte geb ein Bestätigungspasswort ein.";
    } else{
        $confirm_password = post_str("confirm_password");
        if(empty($password_err) && ($password != $confirm_password)){
            $confirm_password_err = "Passwörter sind nicht identisch.";
        }
    }
    if(empty($email_err)){
        $sql = "SELECT benutzer_id FROM benutzer WHERE email = ? LIMIT 1";
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            $param_email = $email;
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $email_err = "Unter dieser Email-Adresse ist bereits ein Account registriert";
                }
            } else{
                $email_err = "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    if(empty($username_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err)){
        $sql = "INSERT INTO benutzer (username, email, passwort_hash, rolle, konto_aktiv)
                VALUES (?, ?, ?, 'user', 1)";
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "sss", $param_username, $param_email, $param_passwordhash);
            $param_username = $username;
            $param_email = $email;
            $param_passwordhash = password_hash($password, PASSWORD_DEFAULT);
            if(mysqli_stmt_execute($stmt)){
                redirect_to("login.php");
            } else{
                echo "Oops! Da ist etwas schief gelaufen. Bitte später erneut versuchen.";
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
    <title>Registrierung</title>
</head>
<body>
    <h2>Registrierung</h2>
    <p>Bitte fülle dieses Formular aus, um ein Konto zu erstellen.</p>
    <form action="<?php echo e($_SERVER["PHP_SELF"]); ?>" method="post">
            <label>Name</label>
            <input type="text" name="username" <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo e($username); ?>">
            <span><?php echo $username_err; ?></span>
            <label>Email</label>
            <input type="email" name="email" <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo e($email); ?>">
            <span><?php echo $email_err; ?></span>
            <label>Passwort</label>
            <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo e($password); ?>">
            <span><?php echo $password_err; ?></span>
            <label>Passwort bestätigen</label>
            <input type="password" name="confirm_password"<?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo e($confirm_password); ?>">
            <span><?php echo $confirm_password_err; ?></span>
            <input type="submit"value="Bestätigen">
            <input type="reset"value="Zurücksetzen">
        <p>Hast du bereits einen Account? <a href="login.php">Login hier</a>.</p>
    </form>
</div>
</body>
</html>