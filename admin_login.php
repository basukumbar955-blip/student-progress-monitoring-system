<?php
session_start();
include("db_connect.php");
require_once "password_reset_helpers.php";

ensure_admin_email_column($conn);
ensure_mentor_email_column($conn);

/* ADMIN LOGIN */
if(isset($_POST['admin_login'])){
    $admin_user = trim($_POST['admin_user']);
    $admin_pass = $_POST['admin_pass'];

    $stmt = $conn->prepare("SELECT * FROM admin_login WHERE admin_user=? LIMIT 1");
    $stmt->bind_param("s", $admin_user);
    $stmt->execute();
    $result = $stmt->get_result();

    if($row = $result->fetch_assoc()){
        if(password_verify($admin_pass, $row['admin_pass']) || $admin_pass === $row['admin_pass']){
            if($admin_pass === $row['admin_pass']){
                $newHash = password_hash($admin_pass, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE admin_login SET admin_pass=? WHERE admin_user=?");
                $update->bind_param("ss", $newHash, $admin_user);
                $update->execute();
                $update->close();
            }

            $_SESSION['admin'] = $admin_user;
            header("Location: admin_dashboard.php");
            exit();
        } else {
            echo "<script>alert('Invalid Admin Username or Password');</script>";
        }
    } else {
        echo "<script>alert('Invalid Admin Username or Password');</script>";
    }

    $stmt->close();
}

/* MENTOR LOGIN */
if(isset($_POST['mentor_login'])){

    $mentor_user = trim($_POST['mentor_user']);
    $mentor_pass = $_POST['mentor_pass'];

    $stmt = $conn->prepare("SELECT * FROM mentor_login WHERE mentor_user=? LIMIT 1");
    $stmt->bind_param("s", $mentor_user);
    $stmt->execute();
    $result = $stmt->get_result();

    if($row = $result->fetch_assoc()){
        /* VERIFY HASHED PASSWORD */

        if(password_verify($mentor_pass,$row['mentor_pass'])){

            $_SESSION['mentor'] = $mentor_user;

            header("Location: mentor_dashboard.php");
            exit();

        }else{

            echo "
            <script>
            alert('Invalid Mentor Username or Password');
            </script>
            ";

        }

    }else{

        echo "
        <script>
        alert('Invalid Mentor Username or Password');
        </script>
        ";

    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin & Mentor Login</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI',sans-serif;
}

/* BACKGROUND */
body{
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    overflow:hidden;

    background:
    linear-gradient(120deg, rgba(37,99,235,0.8), rgba(139,92,246,0.8), rgba(236,72,153,0.7)),
    url("admin1.jpg") no-repeat center center fixed;

    background-size:cover;
    animation: bgAnimate 18s ease-in-out infinite alternate;
}

/* ANIMATION */
@keyframes bgAnimate {
    0%{ background-position:center; transform:scale(1); }
    100%{ background-position:top; transform:scale(1.08); }
}

/* LIGHT EFFECT */
.lights{
    position:absolute;
    width:100%;
    height:100%;
    background:
        radial-gradient(circle at 20% 30%, rgba(255,255,255,0.15), transparent 40%),
        radial-gradient(circle at 80% 70%, rgba(255,255,255,0.1), transparent 40%),
        radial-gradient(circle at 50% 50%, rgba(255,255,255,0.08), transparent 50%);
    animation: floatLights 10s infinite alternate ease-in-out;
    pointer-events:none;
}

@keyframes floatLights{
    0%{ transform: translateY(0px); }
    100%{ transform: translateY(-20px); }
}

/* BACK BUTTON */
.back-btn{
    position:absolute;
    top:20px;
    left:20px;
    background:#ef4444;
    color:white;
    padding:10px 18px;
    border:none;
    border-radius:6px;
    cursor:pointer;
    font-size:14px;
    transition:0.3s;
}
.back-btn:hover{
    background:#dc2626;
    transform:scale(1.05);
}

/* CONTAINER */
.container{
    display:flex;
    gap:40px;
    z-index:1;
}

/* LOGIN BOX */
.login-box{
    background: rgba(255,255,255,0.12);
    backdrop-filter: blur(15px);
    border: 1px solid rgba(255,255,255,0.2);
    padding:40px;
    width:320px;
    border-radius:16px;
    box-shadow: 0 0 40px rgba(0,0,0,0.6);
    text-align:center;
    color:white;
    transition:0.3s;
}

.login-box:hover{
    transform:translateY(-8px) scale(1.02);
}

.login-box h2{
    margin-bottom:20px;
}

/* INPUT */
.login-box input{
    width:100%;
    padding:12px;
    margin:10px 0;
    border-radius:6px;
    border:1px solid rgba(255,255,255,0.4);
    background:rgba(255,255,255,0.2);
    color:white;
    outline:none;
}
.login-box input::placeholder{
    color:#e2e8f0;
}

.form-group{
    margin-bottom:15px;
    text-align:left;
}

.form-group label{
    display:block;
    margin-bottom:6px;
    font-weight:600;
    color:white;
    font-size:14px;
}

.form-group input,
.form-group select,
.form-group textarea{
    width:100%;
}

.password-box{
    position:relative;
}

.password-box input{
    padding-right:42px;
}

.password-toggle{
    position:absolute;
    right:12px;
    top:50%;
    transform:translateY(-50%);
    cursor:pointer;
    color:#e2e8f0;
}

/* BUTTON */
.login-box button{
    width:100%;
    padding:12px;
    margin-top:10px;
    background: linear-gradient(45deg, #2563eb, #7c3aed);
    color:white;
    border:none;
    border-radius:6px;
    cursor:pointer;
    font-size:15px;
    transition:0.3s;
}
.login-box button:hover{
    background: linear-gradient(45deg, #1d4ed8, #6d28d9);
    transform:scale(1.05);
}
.forgot-link{
    display:block;
    margin-top:12px;
    color:#e2e8f0;
    text-decoration:none;
    font-size:14px;
}
.forgot-link:hover{
    color:white;
    text-decoration:underline;
}
</style>
</head>

<body>

<!-- LIGHT EFFECT -->
<div class="lights"></div>

<!-- BACK BUTTON -->
<a href="Ngo.php">
<button class="back-btn">← Back</button>
</a>

<div class="container">

<!-- ADMIN LOGIN -->
<div class="login-box">
<h2>Admin Login</h2>
<form method="POST" id="adminLoginForm">
<div class="form-group">
<label for="admin_user">Username</label>
<input type="text" id="admin_user" name="admin_user" placeholder="Admin Username" required>
</div>
<div class="form-group">
<label for="admin_pass">Password</label>
<div class="password-box">
<input type="password" id="admin_pass" name="admin_pass" placeholder="Password" required>
<i class="fa fa-eye password-toggle" id="adminPassIcon" onclick="togglePassword('admin_pass','adminPassIcon')"></i>
</div>
</div>
<button type="submit" name="admin_login">Login</button>
</form>
<a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
</div>

<!-- MENTOR LOGIN -->
<div class="login-box">
<h2>Mentor Login</h2>
<form method="POST" id="adminPageMentorLoginForm">
<div class="form-group">
<label for="admin_page_mentor_user">Username</label>
<input type="text" id="admin_page_mentor_user" name="mentor_user" placeholder="Mentor Username" required>
</div>
<div class="form-group">
<label for="admin_page_mentor_pass">Password</label>
<div class="password-box">
<input type="password" id="admin_page_mentor_pass" name="mentor_pass" placeholder="Password" required>
<i class="fa fa-eye password-toggle" id="adminPageMentorPassIcon" onclick="togglePassword('admin_page_mentor_pass','adminPageMentorPassIcon')"></i>
</div>
</div>
<button type="submit" name="mentor_login">Login</button>
</form>
<a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
</div>

</div>

<script>
function togglePassword(fieldId, iconId){
    let field = document.getElementById(fieldId);
    let icon = document.getElementById(iconId);
    field.type = (field.type === "password") ? "text" : "password";
    icon.classList.toggle("fa-eye");
    icon.classList.toggle("fa-eye-slash");
}
</script>
<script src="ajax_app.js"></script>
<script>
SpmsAjax.loginForm("#adminLoginForm", "admin");
SpmsAjax.loginForm("#adminPageMentorLoginForm", "mentor");
</script>

</body>
</html>
