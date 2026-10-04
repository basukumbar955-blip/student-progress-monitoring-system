<?php
session_start();
include("db_connect.php");
require_once "password_reset_helpers.php";

reset_csrf_token();
ensure_password_resets_table($conn);
ensure_mentor_email_column($conn);
ensure_admin_email_column($conn);

$message = "";
$error = "";
$safeMessage = "If this email is registered, OTP has been sent.";

if(isset($_POST['send_otp'])){
    try {
        if(!verify_reset_csrf()){
            throw new Exception("Invalid request. Please refresh and try again.");
        }

        $email = filter_var(trim($_POST['s_email']), FILTER_VALIDATE_EMAIL);
        $accountType = $_POST['account_type'] ?? "student";
        $allowedTypes = array("student", "parent", "mentor", "admin");

        if(!$email || !in_array($accountType, $allowedTypes, true)){
            $message = $safeMessage;
        } else {
            $now = time();
            $_SESSION['forgot_last_request'] = $_SESSION['forgot_last_request'] ?? 0;

            if($accountType === "parent"){
                $stmt = db_prepare($conn, "SELECT parent_email AS email FROM parent_login WHERE parent_email=? LIMIT 1");
            } elseif($accountType === "mentor"){
                $stmt = db_prepare($conn, "SELECT COALESCE(mentor_email, mentor_user) AS email FROM mentor_login WHERE mentor_email=? OR mentor_user=? LIMIT 1");
            } elseif($accountType === "admin"){
                $stmt = db_prepare($conn, "SELECT COALESCE(admin_email, admin_user) AS email FROM admin_login WHERE admin_email=? OR admin_user=? LIMIT 1");
            } else {
                $stmt = db_prepare($conn, "SELECT s_email AS email FROM student_login WHERE s_email=? LIMIT 1");
            }

            if($accountType === "mentor" || $accountType === "admin"){
                $stmt->bind_param("ss", $email, $email);
            } else {
                $stmt->bind_param("s", $email);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $account = $result->fetch_assoc();
            $stmt->close();

            if($account && ($now - $_SESSION['forgot_last_request']) >= 60){
                clean_expired_password_resets($conn);

                $stmt = db_prepare($conn, "SELECT COUNT(*) AS total FROM password_resets WHERE account_type=? AND email=? AND created_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
                $stmt->bind_param("ss", $accountType, $email);
                $stmt->execute();
                $rateResult = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if((int)$rateResult['total'] < 3){
                    $otp = (string) random_int(100000, 999999);
                    $otpHash = password_hash($otp, PASSWORD_DEFAULT);
                    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? "";

                    $stmt = db_prepare($conn, "INSERT INTO password_resets (account_type, email, otp_hash, expires_at, created_at, ip_address) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE), NOW(), ?)");
                    $stmt->bind_param("ssss", $accountType, $email, $otpHash, $ipAddress);
                    $stmt->execute();
                    $stmt->close();

                    send_password_reset_otp($email, $otp);
                    $_SESSION['forgot_last_request'] = $now;
                    $_SESSION['reset_email'] = $email;
                    $_SESSION['reset_account_type'] = $accountType;

                    header("Location: verify_otp.php");
                    exit();
                }
            }

            $message = $safeMessage;
        }
    } catch (Exception $e) {
        error_log("Forgot password error: " . $e->getMessage());
        $message = $safeMessage;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Forgot Password</title>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',Arial;}
body{height:100vh;display:flex;justify-content:center;align-items:center;background:linear-gradient(rgba(0,0,0,0.6),rgba(0,0,0,0.6)),url("student.jpg");background-size:cover;}
.container{display:flex;width:900px;max-width:95%;height:480px;background:rgba(255,255,255,0.12);backdrop-filter:blur(14px);border-radius:15px;overflow:hidden;box-shadow:0 25px 60px rgba(0,0,0,0.5);}
.left-panel{flex:1;background:linear-gradient(rgba(0,0,0,0.6),rgba(0,0,0,0.7)),url("student.jpg");display:flex;flex-direction:column;justify-content:center;align-items:center;color:white;padding:40px;text-align:center;}
.right-panel{flex:1;background:white;padding:40px;display:flex;flex-direction:column;justify-content:center;}
h2{text-align:center;margin-bottom:20px;}
.error{background:#fee2e2;color:#dc2626;padding:10px;border-radius:6px;margin-bottom:12px;text-align:center;}
.message{background:#dcfce7;color:#166534;padding:10px;border-radius:6px;margin-bottom:12px;text-align:center;}
input{width:100%;padding:12px;margin-bottom:12px;border-radius:6px;border:1px solid #ccc;}
select{width:100%;padding:12px;margin-bottom:12px;border-radius:6px;border:1px solid #ccc;background:white;}
input:focus{border-color:#667eea;outline:none;}
select:focus{border-color:#667eea;outline:none;}
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
<p>Reset your student account password securely.</p>
</div>
<div class="right-panel">
<h2>Forgot Password</h2>
<?php if($error != ""){ ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php } ?>
<?php if($message != ""){ ?><div class="message"><?php echo htmlspecialchars($message); ?></div><?php } ?>
<form method="POST" id="forgotPasswordForm">
<input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['reset_token']); ?>">
<select name="account_type" required>
<option value="student">Student</option>
<option value="parent">Parent</option>
<option value="mentor">Mentor</option>
<option value="admin">Admin</option>
</select>
<input type="email" name="s_email" placeholder="Registered Email" required>
<div id="forgotEmailMsg" style="font-size:12px;margin-top:-8px;margin-bottom:8px;"></div>
<button type="submit" name="send_otp">Send OTP</button>
</form>
<div class="links">
<a href="verify_otp.php">Already have an OTP?</a> | <a href="login.php">Login</a>
</div>
</div>
</div>
<script src="ajax_app.js"></script>
<script>
SpmsAjax.forgotForm("#forgotPasswordForm");
</script>
</body>
</html>
