<?php
session_start();
require_once "database.php";

$message = '';

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $message = "Please fill in both email and password.";
    } else {
        $stmt = mysqli_stmt_init($conn);
        $sql = "SELECT * FROM users WHERE email = ?";
        if (mysqli_stmt_prepare($stmt, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($row = mysqli_fetch_assoc($result)) {
                $status = strtoupper($row['status']);

                if ($status === 'PENDING') {
                    $message = "Your account is pending admin approval.";
                } elseif ($status === 'REJECT' || $status === 'DECLINED') {
                    $message = "Admin declined your registration request.";
                } elseif ($status === 'APPROVED') {
                    if (password_verify($password, $row['password'])) {
                        $_SESSION['user'] = $row['user_id'];
                        $_SESSION['role'] = $row['role'];

                        if (strtolower($row['role']) === 'student') {
                            header("Location: student_dashboard.php");
                        } elseif (strtolower($row['role']) === 'instructor') {
                            header("Location: instructor_dashboard.php");
                        } else {
                            $message = "Unknown role. Contact admin.";
                        }
                        exit();
                    } else {
                        $message = "Incorrect password.";
                    }
                } else {
                    $message = "Invalid account status. Contact admin.";
                }
            } else {
                $message = "Email not registered.";
            }
        } else {
            $message = "Database error. Try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login Page</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { height: 100vh; background-color: #f8f9fa; overflow: hidden; }
.logo-section { background-color: #394E25; color: white; display: flex; align-items: center; justify-content: center; flex-direction: column; }
.logo-section img { width: 450px; height: auto; margin-bottom: 20px; }
.form-section { display: flex; align-items: center; justify-content: center; }
.alert-danger.small { font-size: 13px; padding: 6px 10px; margin-bottom: 6px; }
</style>
</head>
<body>
<div class="container-fluid h-100">
    <div class="row h-100">
        <!-- LEFT LOGO PANEL -->
        <div class="col-md-6 logo-section">
            <img src="pictures/logo.png" alt="Logo">
        </div>

        <!-- RIGHT LOGIN PANEL -->
        <div class="col-md-6 form-section">
            <div class="card shadow p-4" style="width: 400px;">
                <h3 class="text-center mb-4"></h3>

                <?php if (!empty($message)) : ?>
                    <div class="alert alert-danger small text-center"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <form method="post" action="login.php">
                    <div class="form-group mb-3">
                        <input type="email" class="form-control" name="email" placeholder="Email" required>
                    </div>
                    <div class="form-group mb-3">
                        <input type="password" class="form-control" name="password" placeholder="Password" required>
                    </div>
                    <div class="form-btn mb-3">
                        <input type="submit" name="login" value="Log in" class="btn btn-primary w-100 ">
                    </div>
                </form>

                <div class="text-center mt-3">
                    <p>Not registered? <a href="registration.php">Register here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
