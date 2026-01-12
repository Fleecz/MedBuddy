<?php
require_once "config.php";
$username = $email = $password = $confirm_password = "";
$username_err = $email_err = $password_err = $confirm_password_err = "";
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty(trim($_POST["username"]))){
        $username_err = "Bitte geb deinen Nutzernamen nach dem Chema <Vorname, Nachname> ein";
    } else{
        $username = trim($_POST["username"]);
        if(!preg_match("/^[\p{L}][\p{L}\s'\-]{1,98}$/u", $username)){
            $username_err = "Der Nutername kann nur aus Buchstaben, Zahlen und _ bestehen.";
        }
    }
    if(empty(trim($_POST["email"]))){
        $email_err = "Geb bitte deine Email-Adresse ein.";
    } else{
        $email = trim($_POST["email"]);
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            $email_err = "Bitte gib eine gültige Email-Adresse an.";
        }
    }
    if(empty(trim($_POST["password"]))){
        $password_err = "Geb bitte ein Passwort ein.";
    } else{
        $password = trim($_POST["password"]);
    }
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Bitte geb ein Bestätigungspasswort ein.";
    } else{
        $confirm_password = trim($_POST["confirm_password"]);
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
                header("location: login.php");
                exit;
            } else{
                echo "Oops! Something went wrong. Please try again later.";
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
    <title>Sign Up</title>
</head>
<body>
    <h2>Sign Up</h2>
    <p>Please fill this form to create an account.</p>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <label>Name</label>
            <input type="text" name="username" <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($username); ?>">
            <span><?php echo $username_err; ?></span>
            <label>Email</label>
            <input type="email" name="email" <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($email); ?>">
            <span><?php echo $email_err; ?></span>
            <label>Password</label>
            <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($password); ?>">
            <span><?php echo $password_err; ?></span>
            <label>Confirm Password</label>
            <input type="password" name="confirm_password"<?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($confirm_password); ?>">
            <span><?php echo $confirm_password_err; ?></span>
            <input type="submit"value="Submit">
            <input type="reset"value="Reset">
        <p>Already have an account? <a href="login.php">Login here</a>.</p>
    </form>
</div>
</body>
</html>