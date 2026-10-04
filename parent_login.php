<?php
session_start();
include("db_connect.php");

/* ================= CSRF TOKEN ================= */
if(empty($_SESSION['token'])){
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

$error = "";

/* ================= PASSWORD VALIDATION ================= */
function validatePassword($password){
    return preg_match(
        "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/",
        $password
    );
}

/* ================= LOGIN ================= */
if(isset($_POST['parent_login'])){

    if(!hash_equals($_SESSION['token'], $_POST['token'])){
        die("Invalid CSRF token");
    }

    /* CAPTCHA CHECK */
    if(empty($_POST['captcha'])){
        $error = "Enter CAPTCHA!";
    } elseif(!isset($_SESSION['captcha'])){
        $error = "Captcha expired. Refresh!";
    } elseif(time() - $_SESSION['captcha_time'] > 120){
        $error = "Captcha expired!";
        unset($_SESSION['captcha']);
    } elseif(strtolower($_POST['captcha']) !== strtolower($_SESSION['captcha'])){
        $error = "Invalid CAPTCHA!";
    } else {

        unset($_SESSION['captcha']);

        $student_rno = trim($_POST['student_rno']);
        $parent_email = filter_var($_POST['parent_email'], FILTER_SANITIZE_EMAIL);
        $parent_password = trim($_POST['parent_password']);

        if(empty($student_rno) || empty($parent_email) || empty($parent_password)){
            $error = "All fields are required!";
        } else {

            $stmt = $conn->prepare("SELECT * FROM parent_login WHERE student_rno=? AND parent_email=?");
            $stmt->bind_param("ss", $student_rno, $parent_email);
            $stmt->execute();
            $result = $stmt->get_result();

            if($row = $result->fetch_assoc()){

                $db_password = $row['parent_password'];

                if(password_verify($parent_password, $db_password) || $parent_password === $db_password){

                    if($parent_password === $db_password){
                        $newHash = password_hash($parent_password, PASSWORD_DEFAULT);
                        $update = $conn->prepare("UPDATE parent_login SET parent_password=? WHERE student_rno=?");
                        $update->bind_param("ss", $newHash, $student_rno);
                        $update->execute();
                    }

                    if(!validatePassword($parent_password)){
                        $_SESSION['weak_pass'] = true;
                    } else {
                        unset($_SESSION['weak_pass']);
                    }

                    session_regenerate_id(true);

                    $_SESSION['parent'] = $row['student_rno'];
                    $_SESSION['login_time'] = time();

                    header("Location: parent_dashboard.php");
                    exit();

                } else {
                    $error = "Invalid credentials!";
                }

            } else {
                $error = "Invalid credentials!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Parent Login</title>

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

body{
height:100vh;
display:flex;
justify-content:center;
align-items:center;
background:
linear-gradient(rgba(0,0,0,0.6),rgba(0,0,0,0.6)),
url("1234.jpg");
background-size:cover;
background-position:center;
}

/* MAIN BOX */
.login-box{
background:rgba(255,255,255,0.95);
padding:35px;
width:380px;
border-radius:16px;
box-shadow:0 20px 50px rgba(0,0,0,0.4);
}

.logo{
text-align:center;
font-size:28px;
font-weight:bold;
color:#2563eb;
margin-bottom:8px;
}

h2{
text-align:center;
margin-bottom:20px;
color:#1e293b;
}

.error{
background:#fee2e2;
color:#dc2626;
padding:10px;
border-radius:8px;
margin-bottom:15px;
text-align:center;
font-size:14px;
}

.input-group{
margin-bottom:15px;
position:relative;
}

.input-group i{
position:absolute;
left:12px;
top:50%;
transform:translateY(-50%);
color:#64748b;
}

.input-group input{
width:100%;
padding:12px 12px 12px 38px;
border-radius:8px;
border:1px solid #cbd5e1;
}

.input-group.password-field input{
padding-right:42px;
}

.input-group .password-toggle{
left:auto;
right:12px;
cursor:pointer;
color:#64748b;
}

.input-group input:focus{
border-color:#2563eb;
outline:none;
}

.form-group{
margin-bottom:15px;
}

.form-group label{
display:block;
margin-bottom:6px;
font-weight:600;
color:#333;
font-size:14px;
}

.form-group input,
.form-group select,
.form-group textarea{
width:100%;
}

/* PASSWORD MSG */
#passMsg{
font-size:12px;
margin-top:-5px;
margin-bottom:5px;
}

.show-pass{
font-size:13px;
margin-bottom:10px;
}

/* CAPTCHA WRAPPER */
.captcha-wrapper{
margin-top:12px;
margin-bottom:15px;
}

.captcha-box{
display:flex;
align-items:center;
gap:10px;
margin-bottom:8px;
}

.captcha-box img{
height:45px;
border-radius:6px;
border:1px solid #ccc;
}

.captcha-box button{
padding:6px 10px;
border:none;
background:#e2e8f0;
border-radius:6px;
cursor:pointer;
}

.captcha-box button:hover{
background:#cbd5f5;
}

/* BUTTON */
button{
width:100%;
padding:11px;
background:linear-gradient(135deg,#2563eb,#1d4ed8);
color:white;
border:none;
border-radius:6px;
font-size:15px;
cursor:pointer;
}

button:hover{
transform:translateY(-2px);
box-shadow:0 5px 20px rgba(0,0,0,0.2);
}

.register-link{
text-align:center;
margin-top:15px;
font-size:14px;
}

.register-link a{
color:#2563eb;
text-decoration:none;
}

.back-btn{
position:absolute;
top:20px;
left:20px;
background:white;
padding:8px 15px;
border-radius:8px;
text-decoration:none;
}

/* MOBILE */
@media(max-width:500px){
.login-box{
width:90%;
padding:25px;
}
}
</style>
</head>

<body>

<a href="Ngo.php" class="back-btn">⬅ Back</a>

<div class="login-box">

<div class="logo">🎓 SPMS</div>

<h2>Parent Login</h2>

<?php if($error != ""){ ?>
<div class="error"><?php echo $error; ?></div>
<?php } ?>

<form method="POST" id="parentLoginForm">

<input type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>">

<div class="form-group">
<label for="student_rno">Student Roll Number</label>
<div class="input-group">
<i class="fa fa-id-card"></i>
<input type="text" id="student_rno" name="student_rno" placeholder="Student Roll Number" required>
</div>
</div>

<div class="form-group">
<label for="parent_email">Email Address</label>
<div class="input-group">
<i class="fa fa-envelope"></i>
<input type="email" id="parent_email" name="parent_email" placeholder="Parent Email" required>
</div>
</div>

<div class="form-group">
<label for="pass">Password</label>
<div class="input-group password-field">
<i class="fa fa-lock"></i>
<input type="password" name="parent_password" id="pass" placeholder="Password" required>
<i class="fa fa-eye password-toggle" id="passIcon" onclick="togglePassword('pass','passIcon')"></i>
</div>
</div>

<div id="passMsg" style="display:none;"></div>

<!-- CAPTCHA -->
<div class="captcha-wrapper">

    <div class="captcha-box">
        <img src="captcha.php" id="captchaImg">
        <button type="button" onclick="refreshCaptcha()">↻</button>
    </div>

    <div class="form-group">
    <label for="captcha">Enter CAPTCHA</label>
    <input type="text" id="captcha" name="captcha" placeholder="Enter CAPTCHA" required>
    </div>

</div>

<button name="parent_login">Login</button>

</form>

<div class="register-link">
<a href="forgot_password.php">Forgot Password?</a>
</div>

<div class="register-link">
Don't have account? <a href="parent_register.php">Register</a>
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

function showPassword(){
let x = document.getElementById("pass");
x.type = (x.type === "password") ? "text" : "password";
}

document.getElementById("pass").addEventListener("keyup", function(){
let pass = this.value;
let msg = document.getElementById("passMsg");

let strong = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/;

if(pass.length === 0){
msg.innerText = "";
return;
}

if(strong.test(pass)){
msg.style.color = "green";
msg.innerText = "Strong password ✔";
}else{
msg.style.color = "red";
msg.innerText = "Weak password";
}
});

function refreshCaptcha(){
document.getElementById("captchaImg").src = "captcha.php?" + Date.now();
}
</script>
<script src="ajax_app.js"></script>
<script>
SpmsAjax.loginForm("#parentLoginForm", "parent");
</script>

</body>
</html>
