<?php
session_start();
include("db_connect.php");

/* ================= CSRF TOKEN ================= */
if(empty($_SESSION['token'])){
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

$error = "";
$success = "";

/* ================= PASSWORD VALIDATION ================= */
function validatePassword($password){
    return preg_match(
        "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/",
        $password
    );
}

function e($value){
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function validRollNumber($value){
    return preg_match('/^[0-9]+$/', $value);
}

function validName($value){
    return preg_match('/^[A-Za-z ]{3,}$/', $value);
}

function validMobile($value){
    return preg_match('/^[6-9][0-9]{9}$/', $value);
}

function ensureParentMobileColumn($conn){
    $check = $conn->prepare("SHOW COLUMNS FROM parent_login LIKE 'parent_mobile'");
    $check->execute();

    if($check->get_result()->num_rows === 0){
        $conn->query("ALTER TABLE parent_login ADD COLUMN parent_mobile VARCHAR(10) NULL AFTER parent_email");
    }

    $check->close();
}

/* ================= REGISTER ================= */
if(isset($_POST['register'])){

    try {
        /* CSRF CHECK */
        if(!isset($_POST['token']) || !hash_equals($_SESSION['token'], $_POST['token'])){
            throw new Exception("Invalid request. Please refresh and try again.");
        }

    /* CAPTCHA CHECK */
        if(empty($_POST['captcha'])){
            throw new Exception("Enter CAPTCHA!");
        } elseif(!isset($_SESSION['captcha'])){
            throw new Exception("Captcha expired. Refresh!");
        } elseif(time() - $_SESSION['captcha_time'] > 120){
        unset($_SESSION['captcha']);
            throw new Exception("Captcha expired!");
        } elseif(strtolower($_POST['captcha']) !== strtolower($_SESSION['captcha'])){
            throw new Exception("Invalid CAPTCHA!");
        }

        unset($_SESSION['captcha']);

        $student_rno = trim($_POST['student_rno'] ?? "");
        $parent_name = trim($_POST['parent_name'] ?? "");
        $parent_email = filter_var(trim($_POST['parent_email'] ?? ""), FILTER_SANITIZE_EMAIL);
        $parent_mobile = trim($_POST['parent_mobile'] ?? "");
        $parent_password = $_POST['parent_password'] ?? "";
        $confirm_password = $_POST['confirm_password'] ?? "";

        if(empty($student_rno) || empty($parent_name) || empty($parent_email) || empty($parent_mobile) || empty($parent_password) || empty($confirm_password)){
            throw new Exception("All fields are required!");
        } elseif(!validRollNumber($student_rno)){
            throw new Exception("Roll Number must contain numbers only.");
        } elseif(!validName($parent_name)){
            throw new Exception("Name should contain only alphabets and spaces with minimum 3 characters.");
        } elseif(!filter_var($parent_email, FILTER_VALIDATE_EMAIL)){
            throw new Exception("Enter a valid email address.");
        } elseif(!validMobile($parent_mobile)){
            throw new Exception("Enter a valid 10-digit mobile number starting with 6,7,8, or 9.");
        } elseif($parent_password !== $confirm_password){
            throw new Exception("Passwords do not match!");
        } elseif(!validatePassword($parent_password)){
            throw new Exception("Password must be at least 8 characters and include uppercase, lowercase, number, and special character.");
        } else {

                $hashed_password = password_hash($parent_password, PASSWORD_DEFAULT);

                /* CHECK EXIST */
                $check = $conn->prepare("SELECT * FROM parent_login WHERE parent_email=?");
                $check->bind_param("s", $parent_email);
                $check->execute();
                $res = $check->get_result();

                if($res->num_rows > 0){
                    throw new Exception("Email already registered!");
                } else {

                    ensureParentMobileColumn($conn);

                    /* INSERT */
                    $stmt = $conn->prepare("INSERT INTO parent_login (student_rno, parent_name, parent_email, parent_mobile, parent_password) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssss", $student_rno, $parent_name, $parent_email, $parent_mobile, $hashed_password);

                    if($stmt->execute()){
                        $success = "Registration successful! You can login now.";
                        $_SESSION['token'] = bin2hex(random_bytes(32));
                    } else {
                        throw new Exception("Something went wrong!");
                    }
                }
        }
    } catch (Exception $e) {
        error_log("Parent registration error: " . $e->getMessage());
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Parent Registration</title>

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
padding:16px 0;
background:
linear-gradient(rgba(0,0,0,0.6),rgba(0,0,0,0.6)),
url("1234.jpg");
background-size:cover;
background-position:center;
overflow:hidden;
}

.main-container{
display:flex;
width:980px;
max-width:95%;
height:calc(100vh - 32px);
max-height:680px;
background:rgba(255,255,255,0.12);
backdrop-filter:blur(14px);
border-radius:16px;
overflow:hidden;
box-shadow:0 25px 60px rgba(0,0,0,0.5);
}

.info-section{
flex:0 0 38%;
background:linear-gradient(rgba(0,0,0,0.55),rgba(0,0,0,0.7)),url("1234.jpg");
background-size:cover;
background-position:center;
color:white;
padding:32px;
display:flex;
flex-direction:column;
justify-content:center;
}

.info-section h1{
font-size:24px;
margin-bottom:10px;
}

.info-section p{
font-size:14px;
opacity:0.9;
line-height:1.5;
}

/* BOX */
.form-box{
background:rgba(255,255,255,0.97);
padding:26px 36px;
flex:1;
border-radius:16px;
border-radius:0 16px 16px 0;
overflow:hidden;
}

.logo{
text-align:center;
font-size:24px;
font-weight:bold;
color:#2563eb;
margin-bottom:6px;
}

h2{
text-align:center;
margin-bottom:14px;
font-size:23px;
color:#1f2937;
}

/* ALERTS */
.error{
background:#fee2e2;
color:#dc2626;
padding:10px;
border-radius:8px;
margin-bottom:8px;
text-align:center;
font-size:13px;
}

.success{
background:#dcfce7;
color:#15803d;
padding:10px;
border-radius:8px;
margin-bottom:8px;
text-align:center;
font-size:13px;
}

.form-grid{
display:grid;
grid-template-columns:repeat(2, minmax(0, 1fr));
column-gap:14px;
row-gap:2px;
}

.field-block{
min-width:0;
}

.full-row{
grid-column:1 / -1;
}

/* INPUT */
.input-group{
margin-bottom:0;
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
padding:9px 11px 9px 36px;
border-radius:8px;
border:1px solid #cbd5e1;
font-size:13px;
background:#fff;
transition:border-color 0.2s, box-shadow 0.2s;
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
box-shadow:0 0 0 3px rgba(37,99,235,0.14);
}

.form-group{
margin-bottom:7px;
}

.form-group label{
display:block;
margin-bottom:4px;
font-weight:600;
color:#333;
font-size:13px;
}

.form-group input,
.form-group select,
.form-group textarea{
width:100%;
}

.input-group input.invalid{
border-color:#dc2626;
}

.field-error{
color:#dc2626;
font-size:11px;
margin-top:-5px;
margin-bottom:3px;
min-height:11px;
}

/* PASSWORD MSG */
#passMsg{
font-size:11px;
margin-top:-5px;
margin-bottom:4px;
}

.strength-wrap{
height:5px;
background:#e5e7eb;
border-radius:999px;
overflow:hidden;
margin-top:-4px;
margin-bottom:5px;
}

#strengthBar{
height:100%;
width:0;
background:#dc2626;
transition:0.3s;
}

.ajax-msg{
font-size:11px;
margin-top:-5px;
margin-bottom:3px;
min-height:11px;
}

.show-password{
display:flex;
align-items:center;
gap:6px;
font-size:13px;
color:#4b5563;
margin:2px 0 8px;
}

.show-password input{
width:auto;
}

/* CAPTCHA */
.captcha-wrapper{
margin-top:4px;
margin-bottom:10px;
}

.captcha-box{
display:flex;
align-items:center;
gap:10px;
margin-bottom:6px;
background:#f8fafc;
border:1px solid #e5e7eb;
border-radius:8px;
padding:7px;
}

.captcha-box img{
height:38px;
border-radius:6px;
border:1px solid #ccc;
}

.captcha-box button{
width:auto;
padding:7px 11px;
border:none;
background:#e2e8f0;
border-radius:6px;
cursor:pointer;
margin-top:0;
color:#334155;
}

/* BUTTON */
button{
width:100%;
padding:10px;
background:linear-gradient(135deg,#2563eb,#1d4ed8);
color:white;
border:none;
border-radius:6px;
cursor:pointer;
font-size:14px;
font-weight:600;
}

button:hover{
transform:translateY(-2px);
}

/* LINKS */
.login-link{
text-align:center;
margin-top:10px;
font-size:13px;
color:#4b5563;
}

.login-link a{
color:#2563eb;
text-decoration:none;
font-weight:600;
}

/* BACK */
.back-btn{
position:absolute;
top:20px;
left:20px;
background:white;
padding:8px 15px;
border-radius:8px;
text-decoration:none;
}

@media(max-width:768px){
body{
height:auto;
min-height:100vh;
overflow:auto;
}
.main-container{
flex-direction:column;
height:auto;
min-height:auto;
}
.info-section{
display:none;
}
.form-box{
border-radius:16px;
overflow:visible;
}
.form-grid{
grid-template-columns:1fr;
}
}
</style>

</head>

<body>

<a href="Ngo.php" class="back-btn">⬅ Back</a>

<div class="main-container">

<div class="info-section">
<h1>Student Progress Monitoring System</h1>
<p>Connect with student progress, attendance, and academic updates from one parent account.</p>
</div>

<div class="form-box">

<div class="logo">🎓 SPMS</div>

<h2>Parent Registration</h2>

<?php if($error != ""){ ?>
<div class="error"><?php echo e($error); ?></div>
<?php } ?>

<?php if($success != ""){ ?>
<div class="success"><?php echo e($success); ?></div>
<?php } ?>

<form method="POST" id="parentRegisterForm" novalidate>

<input type="hidden" name="token" value="<?php echo e($_SESSION['token']); ?>">

<div class="form-grid">

<div class="field-block">
<div class="form-group">
<label for="student_rno">Student Roll Number</label>
<div class="input-group">
<i class="fa fa-id-card"></i>
<input type="text" id="student_rno" name="student_rno" placeholder="Student Roll Number" pattern="[0-9]+" inputmode="numeric" required>
</div>
</div>
<div class="field-error" data-error-for="student_rno"></div>
<div class="ajax-msg" id="studentRnoMsg"></div>
</div>

<div class="field-block">
<div class="form-group">
<label for="parent_name">Parent Name</label>
<div class="input-group">
<i class="fa fa-user"></i>
<input type="text" id="parent_name" name="parent_name" placeholder="Parent Name" pattern="[A-Za-z ]{3,}" minlength="3" required>
</div>
</div>
<div class="field-error" data-error-for="parent_name"></div>
</div>

<div class="field-block">
<div class="form-group">
<label for="parent_email">Email Address</label>
<div class="input-group">
<i class="fa fa-envelope"></i>
<input type="email" id="parent_email" name="parent_email" placeholder="Parent Email" required>
</div>
</div>
<div class="field-error" data-error-for="parent_email"></div>
<div class="ajax-msg" id="parentEmailMsg"></div>
</div>

<div class="field-block">
<div class="form-group">
<label for="parent_mobile">Mobile Number</label>
<div class="input-group">
<i class="fa fa-phone"></i>
<input type="text" id="parent_mobile" name="parent_mobile" placeholder="Mobile Number" pattern="[6-9][0-9]{9}" maxlength="10" inputmode="numeric" required>
</div>
</div>
<div class="field-error" data-error-for="parent_mobile"></div>
<div class="ajax-msg" id="parentMobileMsg"></div>
</div>

<div class="field-block">
<div class="form-group">
<label for="pass">Password</label>
<div class="input-group password-field">
<i class="fa fa-lock"></i>
<input type="password" name="parent_password" id="pass" placeholder="Password" required>
<i class="fa fa-eye password-toggle" id="passIcon" onclick="togglePassword('pass','passIcon')"></i>
</div>
</div>

<div class="strength-wrap"><div id="strengthBar"></div></div>
<div id="passMsg"></div>
<div class="field-error" data-error-for="parent_password"></div>
</div>

<div class="field-block">
<div class="form-group">
<label for="confirmPass">Confirm Password</label>
<div class="input-group password-field">
<i class="fa fa-lock"></i>
<input type="password" name="confirm_password" id="confirmPass" placeholder="Confirm Password" required>
<i class="fa fa-eye password-toggle" id="confirmPassIcon" onclick="togglePassword('confirmPass','confirmPassIcon')"></i>
</div>
</div>
<div class="field-error" data-error-for="confirm_password"></div>
</div>

<!-- CAPTCHA -->
<div class="full-row">
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
</div>

<div class="full-row">
<button name="register">Register</button>
</div>

</div>

</form>

<div class="login-link">
Already have account? <a href="parent_login.php">Login</a>
</div>

</div>

</div>

<script>

/* SHOW PASSWORD */
function togglePassword(fieldId, iconId){
let field = document.getElementById(fieldId);
let icon = document.getElementById(iconId);
field.type = (field.type === "password") ? "text" : "password";
icon.classList.toggle("fa-eye");
icon.classList.toggle("fa-eye-slash");
}

/* PASSWORD STRENGTH */
document.getElementById("pass").addEventListener("keyup", function(){
let pass = this.value;
let msg = document.getElementById("passMsg");

let strong = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/;

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

let parentEmail = document.querySelector('[name="parent_email"]');
let parentEmailMsg = document.getElementById("parentEmailMsg");

parentEmail.addEventListener("blur", function(){
let value = this.value.trim();
parentEmailMsg.innerText = "";

if(value === ""){
return;
}

let data = new FormData();
data.append("type", "parent_email");
data.append("value", value);

fetch("ajax_check.php", {
method: "POST",
body: data
})
.then(response => response.json())
.then(result => {
parentEmailMsg.style.color = result.exists ? "red" : "green";
parentEmailMsg.innerText = result.message;
})
.catch(() => {
parentEmailMsg.style.color = "red";
parentEmailMsg.innerText = "Unable to check right now";
});
});

</script>

<script>
const parentValidators = {
student_rno: {regex: /^[0-9]+$/, message: "Roll Number must contain numbers only."},
parent_name: {regex: /^[A-Za-z ]{3,}$/, message: "Name should contain only alphabets and spaces with minimum 3 characters."},
parent_mobile: {regex: /^[6-9][0-9]{9}$/, message: "Enter a valid 10-digit mobile number starting with 6,7,8, or 9."},
parent_password: {regex: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/, message: "Password must include uppercase, lowercase, number, special character and minimum 8 characters."}
};

function setParentFieldError(name, message){
let input = document.querySelector('[name="' + name + '"]');
let error = document.querySelector('[data-error-for="' + name + '"]');
if(!input || !error) return;
input.classList.toggle("invalid", message !== "");
error.innerText = message;
}

function validateParentField(input){
let name = input.name;
let value = input.value.trim();
if(input.required && value === ""){
setParentFieldError(name, "This field is required.");
return false;
}
if(parentValidators[name] && value !== "" && !parentValidators[name].regex.test(value)){
setParentFieldError(name, parentValidators[name].message);
return false;
}
if(name === "parent_email" && value !== "" && !input.checkValidity()){
setParentFieldError(name, "Enter a valid email address.");
return false;
}
if(name === "confirm_password" && value !== document.getElementById("pass").value){
setParentFieldError(name, "Passwords do not match!");
return false;
}
setParentFieldError(name, "");
return true;
}

document.querySelectorAll("#parentRegisterForm input").forEach(input => {
input.addEventListener("input", () => validateParentField(input));
input.addEventListener("blur", () => validateParentField(input));
});

document.getElementById("parentRegisterForm").addEventListener("submit", function(e){
let valid = true;
this.querySelectorAll("input").forEach(input => {
if(!validateParentField(input)) valid = false;
});
if(!valid){
e.preventDefault();
Swal.fire("Validation Error", "Please fix the highlighted fields.", "error");
}
});

document.getElementById("pass").addEventListener("input", function(){
let pass = this.value;
let bar = document.getElementById("strengthBar");
let score = 0;
if(pass.length >= 8) score++;
if(/[A-Z]/.test(pass)) score++;
if(/[a-z]/.test(pass)) score++;
if(/\d/.test(pass)) score++;
if(/[^A-Za-z0-9]/.test(pass)) score++;
bar.style.width = (score * 20) + "%";
bar.style.background = score < 3 ? "#dc2626" : (score < 5 ? "#f59e0b" : "#16a34a");
});

<?php if($success != ""){ ?>
Swal.fire("Success", "<?php echo e($success); ?>", "success");
<?php } elseif($error != ""){ ?>
Swal.fire("Error", "<?php echo e($error); ?>", "error");
<?php } ?>
</script>
<script src="ajax_app.js"></script>
<script>
SpmsAjax.availability('[name="student_rno"]', "student_rno", "#studentRnoMsg");
SpmsAjax.availability('[name="parent_mobile"]', "parent_phone", "#parentMobileMsg");
</script>

</body>
</html>
