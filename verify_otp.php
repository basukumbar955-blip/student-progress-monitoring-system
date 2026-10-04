<?php
session_start();
include("db_connect.php");
require_once "password_reset_helpers.php";

reset_csrf_token();
ensure_password_resets_table($conn);
ensure_mentor_email_column($conn);
ensure_admin_email_column($conn);

$error = "";
$message = "";
$prefillEmail = $_SESSION['reset_email'] ?? "";
$prefillType = $_SESSION['reset_account_type'] ?? "student";

if(isset($_POST['verify_otp'])){
    try {
        if(!verify_reset_csrf()){
            throw new Exception("Invalid request. Please refresh and try again.");
        }

        $email = filter_var(trim($_POST['s_email']), FILTER_VALIDATE_EMAIL);
        $accountType = $_POST['account_type'] ?? "student";
        $allowedTypes = array("student", "parent", "mentor", "admin");
        $otp = trim($_POST['otp']);

        if(!$email || !in_array($accountType, $allowedTypes, true) || !preg_match('/^[0-9]{6}$/', $otp)){
            $error = "Enter a valid email and 6-digit OTP.";
        } else {
            clean_expired_password_resets($conn);

            $stmt = db_prepare($conn, "SELECT id, otp_hash FROM password_resets WHERE account_type=? AND email=? AND verified_at IS NULL AND expires_at >= NOW() ORDER BY id DESC LIMIT 1");
            $stmt->bind_param("ss", $accountType, $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $reset = $result->fetch_assoc();
            $stmt->close();

            if($reset && password_verify($otp, $reset['otp_hash'])){
                $stmt = db_prepare($conn, "UPDATE password_resets SET verified_at=NOW() WHERE id=?");
                $stmt->bind_param("i", $reset['id']);
                $stmt->execute();
                $stmt->close();

                $_SESSION['password_reset_verified'] = true;
                $_SESSION['password_reset_email'] = $email;
                $_SESSION['password_reset_account_type'] = $accountType;
                $_SESSION['password_reset_id'] = $reset['id'];

                header("Location: reset_password.php");
                exit();
            } else {
                $error = "Invalid or expired OTP.";
            }
        }
    } catch (Exception $e) {
        error_log("Verify OTP error: " . $e->getMessage());
        $error = "Unable to verify OTP right now. Please try again later.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Verify OTP</title>
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
<a href="forgot_password.php" class="back-btn">Back</a>
<div class="container">
<div class="left-panel">
<h1>Student Progress Monitoring System</h1>
<p>Enter the OTP sent to your registered email.</p>
</div>
<div class="right-panel">
<h2>Verify OTP</h2>
<?php if($error != ""){ ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php } ?>
<?php if($message != ""){ ?><div class="message"><?php echo htmlspecialchars($message); ?></div><?php } ?>
<form method="POST" id="verifyOtpForm">
<input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['reset_token']); ?>">
<select name="account_type" required>
<option value="student" <?php if($prefillType === "student"){ echo "selected"; } ?>>Student</option>
<option value="parent" <?php if($prefillType === "parent"){ echo "selected"; } ?>>Parent</option>
<option value="mentor" <?php if($prefillType === "mentor"){ echo "selected"; } ?>>Mentor</option>
<option value="admin" <?php if($prefillType === "admin"){ echo "selected"; } ?>>Admin</option>
</select>
<input type="email" name="s_email" placeholder="Registered Email" value="<?php echo htmlspecialchars($prefillEmail); ?>" required>
<input type="text" name="otp" placeholder="6-digit OTP" maxlength="6" pattern="[0-9]{6}" required>
<button type="submit" name="verify_otp">Verify OTP</button>
</form>
<div class="links">
<a href="forgot_password.php">Send OTP again</a> | <a href="login.php">Login</a>
</div>
</div>
</div>
<script src="ajax_app.js"></script>
<script>
SpmsAjax.otpForm("#verifyOtpForm");
</script>
</body>
</html>
