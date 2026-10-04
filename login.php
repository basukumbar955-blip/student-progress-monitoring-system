<?php
session_start();
include("db_connect.php");

/* SESSION SECURITY */
session_regenerate_id(true);

/* CSRF TOKEN */
if(empty($_SESSION['token'])){
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

/* RATE LIMIT */
$_SESSION['attempts'] = $_SESSION['attempts'] ?? 0;

if($_SESSION['attempts'] > 5){
    die("Too many login attempts. Try again later.");
}

$error = "";

/* LOGIN */
if(isset($_POST['login'])){

    if(!hash_equals($_SESSION['token'], $_POST['token'])){
        die("Invalid CSRF token");
    }

    /* CAPTCHA */
    if(empty($_POST['captcha'])){
        $error = "Enter CAPTCHA!";
    } elseif(!isset($_SESSION['captcha'])){
        $error = "Captcha expired. Refresh!";
    } elseif(time() - $_SESSION['captcha_time'] > 120){
        $error = "Captcha expired!";
        unset($_SESSION['captcha']);
    } elseif(strtolower($_POST['captcha']) !== strtolower($_SESSION['captcha'])){
        $_SESSION['attempts']++;
        $error = "Invalid CAPTCHA!";
    } else {

        unset($_SESSION['captcha']);

        $rno = trim($_POST['rno']);
        $s_email = filter_var($_POST['s_email'], FILTER_SANITIZE_EMAIL);
        $u_password = $_POST['u_password'];

        if(empty($rno) || empty($s_email) || empty($u_password)){
            $error = "All fields required!";
        } else {

            $stmt = $conn->prepare("SELECT * FROM student_login WHERE rno=? AND s_email=?");
            $stmt->bind_param("ss", $rno, $s_email);
            $stmt->execute();
            $result = $stmt->get_result();

            if($row = $result->fetch_assoc()){

                if(password_verify($u_password, $row['u_password'])){

                    session_regenerate_id(true);

                    $_SESSION['student'] = $row['rno'];
                    $_SESSION['login_time'] = time();
                    $_SESSION['attempts'] = 0;

                    header("Location: dashboard.php");
                    exit();

                } else {
                    $_SESSION['attempts']++;
                    $error = "Invalid credentials!";
                }

            } else {
                $_SESSION['attempts']++;
                $error = "Invalid credentials!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Login</title>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',Arial;}

body{
min-height:100vh;
display:flex;
justify-content:center;
align-items:center;
padding:30px 0;
background:linear-gradient(rgba(0,0,0,0.6),rgba(0,0,0,0.6)),url("student.jpg");
background-size:cover;
}

/* CONTAINER */
.container{
display:flex;
width:900px;
max-width:95%;
min-height:560px;
background:rgba(255,255,255,0.12);
backdrop-filter:blur(14px);
border-radius:15px;
overflow:visible;
box-shadow:0 25px 60px rgba(0,0,0,0.5);
}

/* LEFT PANEL */
.left-panel{
flex:1;
background:linear-gradient(rgba(0,0,0,0.6),rgba(0,0,0,0.7)),url("student.jpg");
display:flex;
flex-direction:column;
justify-content:center;
align-items:center;
color:white;
padding:40px;
text-align:center;
}

/* RIGHT PANEL */
.right-panel{
flex:1;
background:white;
padding:40px;
display:flex;
flex-direction:column;
justify-content:center;
}

h2{text-align:center;margin-bottom:20px;}

.error{
background:#fee2e2;
color:#dc2626;
padding:10px;
border-radius:6px;
margin-bottom:12px;
text-align:center;
}

/* INPUT */
input{
width:100%;
padding:12px;
margin-bottom:12px;
border-radius:6px;
border:1px solid #ccc;
}

input:focus{
border-color:#667eea;
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

/* PASSWORD */
.password-box{
position:relative;
}

.password-box span{
position:absolute;
right:12px;
top:50%;
transform:translateY(-50%);
cursor:pointer;
}

/* PASSWORD MESSAGE */
#passMsg{
font-size:12px;
margin-top:-8px;
margin-bottom:8px;
}

/* CAPTCHA */
.captcha-wrapper{
margin-top:10px;
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
padding:12px;
background:#667eea;
color:white;
border:none;
border-radius:6px;
cursor:pointer;
margin-top:5px;
}

button:hover{
background:#5a67d8;
}

/* LINKS */
.links{font-size:13px;margin-top:5px;}
.links a{color:#667eea;text-decoration:none;}
.links a:hover{text-decoration:underline;}

.register-link{
text-align:center;
margin-top:12px;
font-size:14px;
color:#333;
}

.register-link a{
color:#667eea;
font-weight:600;
text-decoration:none;
}

.register-link a:hover{
text-decoration:underline;
}

.back-btn{
position:absolute;
top:20px;
left:20px;
background:white;
padding:8px 15px;
border-radius:6px;
text-decoration:none;
}

/* MOBILE */
@media(max-width:768px){
.container{flex-direction:column;height:auto;}
.left-panel{display:none;}
}
</style>
</head>

<body>

<a href="Ngo.php" class="back-btn">⬅ Back</a>

<div class="container">

<div class="left-panel">
<h1>Student Progress Monitoring System</h1>
<p>Track performance, attendance and progress easily.</p>
</div>

<div class="right-panel">

<h2>Student Login</h2>

<?php if($error != ""){ ?>
<div class="error"><?php echo $error; ?></div>
<?php } ?>

<form method="POST" id="studentLoginForm">

<input type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>">

<div class="form-group">
<label for="rno">Roll Number</label>
<input type="text" id="rno" name="rno" placeholder="Roll Number" required>
</div>

<div class="form-group">
<label for="s_email">Email Address</label>
<input type="email" id="s_email" name="s_email" placeholder="Email" required>
</div>

<div class="form-group">
<label for="password">Password</label>
<div class="password-box">
<input type="password" name="u_password" id="password" placeholder="Password" required>
<span onclick="togglePassword()">👁</span>
</div>
</div>

<div id="passMsg"></div>

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

<button name="login">Login</button>

<div class="links">
<a href="forgot_password.php">Forgot Password?</a>
</div>

<div class="register-link">
Don't have an account? <a href="register.php">Register</a>
</div>

</form>

</div>
</div>

<script>

/* SHOW PASSWORD */
function togglePassword(){
let p = document.getElementById("password");
p.type = (p.type === "password") ? "text" : "password";
}

/* LIVE PASSWORD CHECK (SAME AS REGISTER) */
document.getElementById("password").addEventListener("keyup", function(){

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

/* CAPTCHA REFRESH */
function refreshCaptcha(){
document.getElementById("captchaImg").src = "captcha.php?" + Date.now();
}

</script>
<script src="ajax_app.js"></script>
<script>
SpmsAjax.loginForm("#studentLoginForm", "student");
</script>

</body>
</html>
