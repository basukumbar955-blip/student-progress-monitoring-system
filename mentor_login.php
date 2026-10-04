<?php
session_start();
include("db_connect.php");

$error = "";

if(isset($_POST['mentor_login'])){

    $mentor_user = trim($_POST['mentor_user']);
    $mentor_pass = $_POST['mentor_pass'];

    $stmt = $conn->prepare("SELECT * FROM mentor_login WHERE mentor_user=? LIMIT 1");
    $stmt->bind_param("s", $mentor_user);
    $stmt->execute();
    $result = $stmt->get_result();

    if($row = $result->fetch_assoc()){
        if(password_verify($mentor_pass, $row['mentor_pass'])){
            $_SESSION['mentor'] = $mentor_user;

            header("Location: mentor_dashboard.php");
            exit();
        } else {
            $error = "Invalid Mentor Login";
        }
    } else {
        $error = "Invalid Mentor Login";
    }

}
?>

<!DOCTYPE html>
<html>
<head>
<title>Mentor Login</title>

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

background:
linear-gradient(rgba(0,0,0,0.65),rgba(0,0,0,0.65)),
url("mentor.jpg");

background-size:cover;
background-position:center;
background-attachment:fixed;
}

/* LOGIN BOX */

.login-box{
background:rgba(255,255,255,0.15);
backdrop-filter:blur(10px);
padding:40px;
width:350px;
border-radius:12px;
box-shadow:0 15px 40px rgba(0,0,0,0.4);
text-align:center;
color:white;
}

.login-box h2{
margin-bottom:25px;
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
background:linear-gradient(135deg,#2563eb,#1d4ed8);
color:white;
border:none;
border-radius:6px;
font-size:15px;
cursor:pointer;
transition:0.3s;
}

.login-box button:hover{
transform:scale(1.05);
}

/* BACK LINK */

.back{
margin-top:15px;
display:block;
text-decoration:none;
color:#e2e8f0;
}

.back:hover{
color:white;
}

</style>

</head>

<body>

<div class="login-box">

<h2>Mentor Login</h2>

<?php if($error != ""){ ?>
<div style="background:#fee2e2;color:#dc2626;padding:10px;border-radius:6px;margin-bottom:12px;">
<?php echo htmlspecialchars($error); ?>
</div>
<?php } ?>

<form method="POST" id="mentorLoginForm">

<div class="form-group">
<label for="mentor_user">Username</label>
<input type="text" id="mentor_user" name="mentor_user" placeholder="Mentor Username" required>
</div>

<div class="form-group">
<label for="mentor_pass">Password</label>
<div class="password-box">
<input type="password" id="mentor_pass" name="mentor_pass" placeholder="Password" required>
<i class="fa fa-eye password-toggle" id="mentorPassIcon" onclick="togglePassword('mentor_pass','mentorPassIcon')"></i>
</div>
</div>

<button name="mentor_login">Login</button>

</form>

<a href="forgot_password.php" class="back">Forgot Password?</a>

<a href="Ngo.php" class="back">← Back to Home</a>

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
SpmsAjax.loginForm("#mentorLoginForm", "mentor");
</script>

</body>
</html>
