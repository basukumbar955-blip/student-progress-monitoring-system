<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function load_phpmailer()
{
    if(file_exists(__DIR__ . "/vendor/autoload.php")){
        require_once __DIR__ . "/vendor/autoload.php";
        return;
    }

    if(file_exists(__DIR__ . "/PHPMailer/src/PHPMailer.php")){
        require_once __DIR__ . "/PHPMailer/src/Exception.php";
        require_once __DIR__ . "/PHPMailer/src/PHPMailer.php";
        require_once __DIR__ . "/PHPMailer/src/SMTP.php";
        return;
    }

    throw new \Exception("PHPMailer is not installed.");
}

function send_password_reset_otp($toEmail, $otp)
{
    require_once "mail_config.php";
    load_phpmailer();

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_APP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = SMTP_PORT;

    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    $mail->addAddress($toEmail);
    $mail->isHTML(true);
    $mail->Subject = "Your Password Reset OTP";
    $mail->Body = "
        <h3>Password Reset OTP</h3>
        <p>Your OTP is <strong>" . htmlspecialchars($otp) . "</strong>.</p>
        <p>This OTP expires in 5 minutes.</p>
        <p>If you did not request this, you can ignore this email.</p>
    ";
    $mail->AltBody = "Your password reset OTP is " . $otp . ". It expires in 5 minutes.";

    $mail->send();
}

function reset_csrf_token()
{
    if(empty($_SESSION['reset_token'])){
        $_SESSION['reset_token'] = bin2hex(random_bytes(32));
    }
}

function verify_reset_csrf()
{
    return isset($_POST['token'], $_SESSION['reset_token']) && hash_equals($_SESSION['reset_token'], $_POST['token']);
}

function db_prepare($conn, $sql)
{
    $stmt = $conn->prepare($sql);

    if(!$stmt){
        throw new \Exception("Database prepare failed: " . $conn->error);
    }

    return $stmt;
}

function password_reset_column_exists($conn, $column)
{
    $column = $conn->real_escape_string($column);
    $result = $conn->query("SHOW COLUMNS FROM password_resets LIKE '" . $column . "'");
    $exists = $result && $result->num_rows > 0;

    if($result){
        $result->free();
    }

    return $exists;
}

function ensure_password_resets_table($conn)
{
    $createSql = "
        CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            account_type VARCHAR(20) NOT NULL DEFAULT 'student',
            email VARCHAR(100) NOT NULL,
            otp_hash VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL,
            verified_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            INDEX idx_email (email),
            INDEX idx_expires_at (expires_at),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    if(!$conn->query($createSql)){
        throw new \Exception("Unable to create password_resets table: " . $conn->error);
    }

    if(!password_reset_column_exists($conn, "account_type")){
        if(!$conn->query("ALTER TABLE password_resets ADD account_type VARCHAR(20) NOT NULL DEFAULT 'student' AFTER id")){
            throw new \Exception("Unable to add account_type column: " . $conn->error);
        }
    }

    if(!password_reset_column_exists($conn, "otp_hash")){
        if(!$conn->query("ALTER TABLE password_resets ADD otp_hash VARCHAR(255) NOT NULL AFTER email")){
            throw new \Exception("Unable to add otp_hash column: " . $conn->error);
        }
    }

    if(!password_reset_column_exists($conn, "expires_at")){
        if(!$conn->query("ALTER TABLE password_resets ADD expires_at DATETIME NOT NULL AFTER otp_hash")){
            throw new \Exception("Unable to add expires_at column: " . $conn->error);
        }
    }

    if(!password_reset_column_exists($conn, "verified_at")){
        if(!$conn->query("ALTER TABLE password_resets ADD verified_at DATETIME DEFAULT NULL AFTER expires_at")){
            throw new \Exception("Unable to add verified_at column: " . $conn->error);
        }
    }

    if(!password_reset_column_exists($conn, "created_at")){
        if(!$conn->query("ALTER TABLE password_resets ADD created_at DATETIME NOT NULL AFTER verified_at")){
            throw new \Exception("Unable to add created_at column: " . $conn->error);
        }
    }

    if(!password_reset_column_exists($conn, "ip_address")){
        if(!$conn->query("ALTER TABLE password_resets ADD ip_address VARCHAR(45) DEFAULT NULL AFTER created_at")){
            throw new \Exception("Unable to add ip_address column: " . $conn->error);
        }
    }

    if(password_reset_column_exists($conn, "otp")){
        if(!$conn->query("ALTER TABLE password_resets MODIFY otp INT DEFAULT NULL")){
            throw new \Exception("Unable to update old otp column: " . $conn->error);
        }
    }

    if(password_reset_column_exists($conn, "expiry")){
        if(!$conn->query("ALTER TABLE password_resets MODIFY expiry DATETIME DEFAULT NULL")){
            throw new \Exception("Unable to update old expiry column: " . $conn->error);
        }
    }
}

function table_column_exists($conn, $table, $column)
{
    $table = $conn->real_escape_string($table);
    $column = $conn->real_escape_string($column);
    $result = $conn->query("SHOW COLUMNS FROM `" . $table . "` LIKE '" . $column . "'");
    $exists = $result && $result->num_rows > 0;

    if($result){
        $result->free();
    }

    return $exists;
}

function ensure_mentor_email_column($conn)
{
    if(!table_column_exists($conn, "mentor_login", "mentor_email")){
        if(!$conn->query("ALTER TABLE mentor_login ADD mentor_email VARCHAR(100) DEFAULT NULL AFTER mentor_user")){
            throw new \Exception("Unable to add mentor_email column: " . $conn->error);
        }
    }
}

function ensure_admin_email_column($conn)
{
    if(!table_column_exists($conn, "admin_login", "admin_email")){
        if(!$conn->query("ALTER TABLE admin_login ADD admin_email VARCHAR(100) DEFAULT NULL AFTER admin_user")){
            throw new \Exception("Unable to add admin_email column: " . $conn->error);
        }
    }
}

function clean_expired_password_resets($conn)
{
    $stmt = db_prepare($conn, "DELETE FROM password_resets WHERE expires_at < NOW() OR created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)");
    $stmt->execute();
    $stmt->close();
}

?>
