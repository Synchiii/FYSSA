<?php
require_once "database.php";
session_start();

// Redirect if admin is already logged in
if (isset($_SESSION['admin'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$error = '';

if (isset($_POST["login"])) {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    // Prepare statement to fetch admin by email
    $stmt = mysqli_stmt_init($conn);
    $sql = "SELECT * FROM admin WHERE email = ?";
    if (mysqli_stmt_prepare($stmt, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $admin = mysqli_fetch_assoc($result);

        if ($admin) {
            // Verify password (hashed in DB)
            if (password_verify($password, $admin["password"])) {
                $_SESSION["admin"] = $admin["user_id"]; // store admin ID
                header("Location: admin_dashboard.php");
                exit();
            } else {
                $error = "Password does not match";
            }
        } else {
            $error = "Email does not exist";
        }
    } else {
        $error = "Database error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ADMIN LOGIN</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    height: 100vh;
    background-color: #f8f9fa;
}
.logo-section {
    background-color: #000000ff;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
}
.login-section {
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
</head>
<body>
<div class="container-fluid h-100">
    <div class="row h-100">
        <div class="col-md-6 logo-section">
            <h1>ADMIN LOGIN</h1>
        </div>
        <div class="col-md-6 login-section">
            <div class="card shadow p-4" style="width: 350px;">
                <h3 class="text-center mb-4">Login</h3>
                <?php if(!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form action="admin_login.php" method="post">
    <div class="form-group mb-3">
        <input type="email" name="email" class="form-control" placeholder="Email" required>
    </div>
    <div class="form-group mb-3">
        <input type="password" name="password" class="form-control" placeholder="Password" required>
    </div>
    <div class="form-group mb-3">
        <input type="submit" name="login" class="btn btn-primary w-100" value="Login">
    </div>
</form>

            </div>
        </div>
    </div>
</div>
</body>
</html>
