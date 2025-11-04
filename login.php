<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $name, $hashed_password);
        $stmt->fetch();

        // ✅ Use MD5 instead of password_verify
        if (md5($password) === $hashed_password) {
            $_SESSION['user_id'] = $id;
            $_SESSION['name'] = $name;
            header("Location: dashboard.php");
            exit;
        } else {
            echo "<script>alert('Invalid password'); window.location.href='login.html';</script>";
        }
    } else {
        echo "<script>alert('No account found with that email'); window.location.href='login.html';</script>";
    }

    $stmt->close();
}
$conn->close();
?>
