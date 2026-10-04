<?php
session_start();
include("db_connect.php");
require_once "password_reset_helpers.php";

reset_csrf_token();
ensure_password_resets_table($conn);
ensure_mentor_email_column($conn);
ensure_admin_email_column($conn);

$error = "";

if(empty($_SESSION['password_reset_verified']) || empty($_SESSION['password_reset_email']) || empty($_SESSION['password_reset_account_type']) || empty($_SESSION['password_reset_id'])){
    header("Location: forgot_password.php");
    exit();
}

$email = $_SESSION['password_reset_email'];
$accountType = $_SESSION['password_reset_account_type'];
$resetId = (int) $_SESSION['password_reset_id'];

if(isset($_POST['reset_password'])){
    try {
        if(!verify_reset_csrf()){
            throw new Exception("Invalid request. Please refresh and try again.");
        }

        $newPassword = $_POST['u_password'] ?? "";
        $confirmPassword = $_POST['confirm_password'] ?? "";
        $strongPassword = preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/', $newPassword);

        if($newPassword === "" || $confirmPassword === ""){
            $error = "All fields are required.";
        } elseif($newPassword !== $confirmPassword){
            $error = "Passwords do not match.";
        } elseif(!$strongPassword){
            $error = "Use at least 8 characters with uppercase, lowercase, number and special character.";
        } else {
            $stmt = db_prepare($conn, "SELECT id FROM password_resets WHERE id=? AND account_type=? AND email=? AND verified_at IS NOT NULL AND expires_at >= NOW() LIMIT 1");
            $stmt->bind_param("iss", $resetId, $accountType, $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $reset = $result->fetch_assoc();
            $stmt->close();

            if(!$reset){
                $error = "Reset session expired. Please request a new OTP.";
            } else {
                $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

                if($accountType === "parent"){
                    $stmt = db_prepare($conn, "UPDATE parent_login SET parent_password=? WHERE parent_email=?");
                } elseif($accountType === "mentor"){
                    $stmt = db_prepare($conn, "UPDATE mentor_login SET mentor_pass=? WHERE mentor_email=? OR mentor_user=?");
                } elseif($accountType === "admin"){
                    $stmt = db_prepare($conn, "UPDATE admin_login SET admin_pass=? WHERE admin_email=? OR admin_user=?");
                } else {
                    $stmt = db_prepare($conn, "UPDATE student_login SET u_password=? WHERE s_email=?");
                }

                if($accountType === "mentor" || $accountType === "admin"){
                    $stmt->bind_param("sss", $passwordHash, $email, $email);
                } else {
                    $stmt->bind_param("ss", $passwordHash, $email);
                }
                $stmt->execute();
                $stmt->close();

                $stmt = db_prepare($conn, "DELETE FROM password_resets WHERE account_type=? AND email=?");
                $stmt->bind_param("ss", $accountType, $email);
                $stmt->execute();
                $stmt->close();

                unset($_SESSION['password_reset_verified'], $_SESSION['password_reset_email'], $_SESSION['password_reset_account_type'], $_SESSION['password_reset_id'], $_SESSION['reset_email'], $_SESSION['reset_account_type']);

                echo "<script>
                alert('Password reset successful. Please login with your new password.');
                window.location.href='login.php';
                </script>";
                exit();
            }
        }
    } catch (Exception $e) {
        error_log("Reset password error: " . $e->getMessage());
        $error = "Unable to reset password right now. Please try again later.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reset Password</title>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',Arial;}
body{height:100vh;display:flex;justify-content:center;align-items:center;background:linear-gradient(rgba(0,0,0,0.6),rgba(0,0,0,0.6)),url("student.jpg");background-size:cover;}
.container{display:flex;width:900px;max-width:95%;height:500px;background:rgba(255,255,255,0.12);backdrop-filter:blur(14px);border-radius:15px;overflow:hidden;box-shadow:0 25px 60px rgba(0,0,0,0.5);}
.left-panel{flex:1;background:linear-gradient(rgba(0,0,0,0.6),rgba(0,0,0,0.7)),url("student.jpg");display:flex;flex-direction:column;justify-content:center;align-items:center;color:white;padding:40px;text-align:center;}
.right-panel{flex:1;background:white;padding:40px;display:flex;flex-direction:column;justify-content:center;}
h2{text-align:center;margin-bottom:20px;}
.error{background:#fee2e2;color:#dc2626;padding:10px;border-radius:6px;margin-bottom:12px;text-align:center;}
input{width:100%;padding:12px;margin-bottom:12px;border-radius:6px;border:1px solid #ccc;}
input:focus{border-color:#667eea;outline:none;}
.password-box{position:relative;}
.password-box span{position:absolute;right:12px;top:50%;transform:translateY(-50%);cursor:pointer;}
#passMsg{font-size:12px;margin-top:-8px;margin-bottom:8px;}
button{width:100%;padding:12px;background:#667eea;color:white;border:none;border-radius:6px;cursor:pointer;margin-top:5px;}
button:hover{background:#5a67d8;}
.links{text-align:center;font-size:13px;margin-top:12px;}
.links a{color:#667eea;text-decoration:none;}
.links a:hover{text-decoration:underline;}
.back-btn{position:absolute;top:20px;left:20px;background:white;padding:8px 15px;border-radius:6px;text-decoration:none;}
@media(max-width:768px){.container{flex-direction:column;height:auto;}.left-panel{display:none;}}
</style>
</head>
<body>
<a href="login.php" class="back-btn">Back</a>
<div class="container">
<div class="left-panel">
<h1>Student Progress Monitoring System</h1>
<p>Create a new secure password for your account.</p>
</div>
<div class="right-panel">
<h2>Reset Password</h2>
<?php if($error != ""){ ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php } ?>
<form method="POST" id="resetPasswordForm">
<input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['reset_token']); ?>">
<div class="password-box">
<input type="password" name="u_password" id="password" placeholder="New Password" required>
<span onclick="togglePassword('password')">Show</span>
</div>
<div id="passMsg"></div>
<div class="password-box">
<input type="password" name="confirm_password" id="confirmPassword" placeholder="Confirm Password" required>
<span onclick="togglePassword('confirmPassword')">Show</span>
</div>
<button type="submit" name="reset_password">Reset Password</button>
</form>
<div class="links">
<a href="login.php">Login</a>
</div>
</div>
</div>
<script>
function togglePassword(id){
let p = document.getElementById(id);
p.type = (p.type === "password") ? "text" : "password";
}

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
    msg.innerText = "Strong password";
}else{
    msg.style.color = "red";
    msg.innerText = "Weak password (Use A-Z, a-z, 0-9, special char)";
}
});
</script>
<script src="ajax_app.js"></script>
<script>
SpmsAjax.resetPasswordForm("#resetPasswordForm");
</script>
</body>
</html>
