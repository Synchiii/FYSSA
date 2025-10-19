<?php
session_start();
if (isset($_SESSION["user"])) {
    header("Location: index.php");
    exit();
}

require_once "database.php"; // Make sure this connects to login_register

if (isset($_POST["submit"])) {
    $firstName = trim($_POST["firstName"]);
    $middleName = trim($_POST["middleName"]);
    $lastName = trim($_POST["lastName"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $passwordRepeat = $_POST["repeat_password"];
    $gender = $_POST["gender"] ?? '';
    $role = $_POST["role"] ?? '';

    $errors = [];

    // Validation
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($passwordRepeat) || empty($gender) || empty($role)) {
        $errors[] = "All required fields must be filled";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email is not valid";
    }
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    if ($password !== $passwordRepeat) {
        $errors[] = "Passwords do not match";
    }

    // Check if email exists
    $stmt = mysqli_stmt_init($conn);
    $sql = "SELECT user_id FROM users WHERE email = ?";
    if (mysqli_stmt_prepare($stmt, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "Email already exists!";
        }
    }

    // Insert user if no errors
    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Default status is 'pending' for admin approval
        $status = 'pending';

        $stmt = mysqli_stmt_init($conn);
        $sql = "INSERT INTO users (first_name, middle_name, last_name, gender, email, password, role, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        if (mysqli_stmt_prepare($stmt, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssssssss", $firstName, $middleName, $lastName, $gender, $email, $passwordHash, $role, $status);
            mysqli_stmt_execute($stmt);
            echo "<div class='alert alert-success'>You are registered successfully. Your account is pending admin approval.</div>";
            header("refresh:3;url=login.php"); // redirect after 3 seconds
            exit();
        } else {
            echo "<div class='alert alert-danger'>Database error: Could not insert user</div>";
        }
    } else {
        $errorMessages = '';
        foreach ($errors as $error) {
            $errorMessages .= "<div class='alert alert-danger small py-1 px-2 mb-2'>$error</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Registration Page</title>
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
        <div class="col-md-6 logo-section">
            <img src="pictures/logo.png" alt="Logo">
        </div>
        <div class="col-md-6 form-section">
            <div class="card shadow p-4" style="width: 400px;">
                <h3 class="text-center mb-4">Register</h3>
                <?php if (!empty($errorMessages)): ?>
                    <?= $errorMessages ?>
                <?php endif; ?>
                <form action="registration.php" method="post">
                    <div class="form-group mb-3">
                        <input type="text" class="form-control" name="firstName" placeholder="First Name" required>
                    </div>
                    <div class="form-group mb-3">
                        <input type="text" class="form-control" name="middleName" placeholder="Middle Name (Optional)">
                    </div>
                    <div class="form-group mb-3">
                        <input type="text" class="form-control" name="lastName" placeholder="Last Name" required>
                    </div>
                    <div class="form-group mb-3">
                        <label>Gender:</label><br>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="gender" id="male" value="m" required>
                            <label class="form-check-label" for="male">Male</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="gender" id="female" value="f">
                            <label class="form-check-label" for="female">Female</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="gender" id="other" value="o">
                            <label class="form-check-label" for="other">Other</label>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label>Role:</label>
                        <select name="role" class="form-select" required>
                            <option value="">Select Role</option>
                            <option value="student">Student</option>
                            <option value="instructor">Instructor</option>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <input type="email" class="form-control" name="email" placeholder="Email" required>
                    </div>
                    <div class="form-group mb-3">
                        <input type="password" class="form-control" name="password" placeholder="Password" required>
                    </div>
                    <div class="form-group mb-3">
                        <input type="password" class="form-control" name="repeat_password" placeholder="Repeat Password" required>
                    </div>
                    <div class="form-btn mb-3">
                        <input type="submit" value="Register" name="submit" class="btn btn-primary w-100">
                    </div>
                </form>
                <div class="text-center mt-3">
                    <p>Already Registered? <a href="login.php">Login Here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
