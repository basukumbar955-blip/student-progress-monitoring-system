<?php
session_start();
include("db_connect.php");

/* ✅ ADMIN PROTECTION */
if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

if(empty($_SESSION['admin_csrf'])){
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

$csrf = $_SESSION['admin_csrf'];

function e($value){
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function validRollNumber($value){
    return preg_match('/^[0-9]+$/', $value);
}

function validName($value){
    return preg_match('/^[A-Za-z ]{3,}$/', $value);
}

function validPassword($value){
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $value);
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

/* ================= ADD PARENT ================= */

if(isset($_POST['add_parent'])){

    try {
        if(!isset($_POST['token']) || !hash_equals($_SESSION['admin_csrf'], $_POST['token'])){
            throw new Exception("Invalid request. Please refresh and try again.");
        }

    $parent_name  = trim($_POST['parent_name']);
    $parent_email = filter_var($_POST['parent_email'], FILTER_SANITIZE_EMAIL);
    $parent_mobile = trim($_POST['parent_mobile'] ?? "");
    $parent_pass_plain = $_POST['parent_pass'];
    $student_rno  = trim($_POST['student_rno']);

    if(empty($parent_name) || empty($parent_email) || empty($parent_mobile) || empty($parent_pass_plain) || empty($student_rno)){
        throw new Exception("All fields are required!");
    } elseif(!validName($parent_name)){
        throw new Exception("Name should contain only alphabets and spaces with minimum 3 characters.");
    } elseif(!filter_var($parent_email, FILTER_VALIDATE_EMAIL)){
        throw new Exception("Enter a valid email address.");
    } elseif(!validMobile($parent_mobile)){
        throw new Exception("Enter a valid 10-digit mobile number starting with 6,7,8, or 9.");
    } elseif(!validPassword($parent_pass_plain)){
        throw new Exception("Password must be at least 8 characters and include uppercase, lowercase, number, and special character.");
    } elseif(!validRollNumber($student_rno)){
        throw new Exception("Roll Number must contain numbers only.");
    } else {

        ensureParentMobileColumn($conn);

        // HASH PASSWORD
        $parent_password = password_hash($parent_pass_plain, PASSWORD_DEFAULT);

        // CHECK DUPLICATE EMAIL
        $check = $conn->prepare("SELECT id FROM parent_login WHERE parent_email=? LIMIT 1");
        $check->bind_param("s", $parent_email);
        $check->execute();
        $check_result = $check->get_result();

        if($check_result->num_rows > 0){
            throw new Exception("Parent email already exists!");
        } else {

            $stmt = $conn->prepare("INSERT INTO parent_login (parent_name, parent_email, parent_mobile, parent_password, student_rno) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $parent_name, $parent_email, $parent_mobile, $parent_password, $student_rno);

            if($stmt->execute()){
                $_SESSION['msg'] = "Parent added successfully!";
            } else {
                throw new Exception("Parent add failed!");
            }
        }
    }
    } catch (Exception $e) {
        error_log("Admin add parent error: " . $e->getMessage());
        $_SESSION['msg'] = $e->getMessage();
    }

    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

/* ================= DELETE ================= */

if(isset($_POST['delete_parent'])){

    if(!isset($_POST['token']) || !hash_equals($_SESSION['admin_csrf'], $_POST['token'])){
        die("Invalid CSRF token");
    }

    $id = intval($_POST['id']);

    $stmt = $conn->prepare("DELETE FROM parent_login WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $_SESSION['msg'] = "Parent deleted successfully!";

    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

/* ================= SEARCH ================= */

$where = "";

ensureParentMobileColumn($conn);

if(isset($_GET['search'])){
    $search = trim($_GET['search']);
    $like = "%".$search."%";
    $stmt = $conn->prepare("SELECT * FROM parent_login WHERE id LIKE ? OR parent_name LIKE ? OR parent_email LIKE ? OR parent_mobile LIKE ? OR student_rno LIKE ? ORDER BY id DESC");
    $stmt->bind_param("sssss", $like, $like, $like, $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
}else{
    $stmt = $conn->prepare("SELECT * FROM parent_login ORDER BY id DESC");
    $stmt->execute();
    $result = $stmt->get_result();
}

/* ================= FETCH ================= */

/* ================= COUNT ================= */

$count_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM parent_login");
$count_stmt->execute();
$count_data = $count_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
<title>Manage Parents</title>
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
background:
linear-gradient(rgba(0,0,0,0.15), rgba(0,0,0,0.15)),
url("student_bg.jpg");
background-size:cover;
background-position:center;
background-attachment:fixed;

min-height:100vh;
display:flex;
justify-content:center;
align-items:center;
}

.back-btn{
position:absolute;
top:20px;
left:20px;
padding:8px 16px;
background:#64748b;
color:white;
text-decoration:none;
border-radius:6px;
}

.container{
width:1100px;
max-width:95%;
padding:30px;
border-radius:12px;
background:rgba(255,255,255,0.92);
backdrop-filter:blur(8px);
box-shadow:0 15px 40px rgba(0,0,0,0.3);
}

.count{
margin-bottom:15px;
font-weight:bold;
color:#2563eb;
}

.search-box{
margin-bottom:15px;
display:flex;
gap:10px;
}

.search-box input{
flex:1;
padding:10px;
border:1px solid #ccc;
border-radius:6px;
}

.search-box button{
padding:10px 20px;
background:#16a34a;
color:white;
border:none;
border-radius:6px;
}

.form-box{
display:flex;
gap:10px;
margin-bottom:20px;
flex-wrap:wrap;
}

.form-box input{
flex:1;
padding:10px;
border:1px solid #ccc;
border-radius:6px;
}

.form-box input.invalid{
border-color:#dc2626;
}

.field-error{
width:100%;
color:#dc2626;
font-size:12px;
margin-top:-6px;
}

.form-box button{
padding:10px 20px;
background:#2563eb;
color:white;
border:none;
border-radius:6px;
cursor:pointer;
}

.ajax-msg{
width:100%;
font-size:12px;
margin-top:-6px;
}

table{
width:100%;
border-collapse:collapse;
background:white;
}

th{
background:#2563eb;
color:white;
padding:12px;
}

td{
padding:12px;
text-align:center;
border-bottom:1px solid #ddd;
}

.delete{
background:#ef4444;
color:white;
padding:6px 12px;
border-radius:5px;
text-decoration:none;
border:none;
cursor:pointer;
}
</style>

</head>

<body>

<a href="admin_dashboard.php" class="back-btn">⬅ Back</a>

<div class="container">

<h2>Manage Parents</h2>

<div class="count">
Total Parents: <?php echo $count_data['total']; ?>
</div>

<!-- SEARCH -->
<form method="GET" class="search-box">
<input type="text" id="parentLiveSearch" name="search" placeholder="Search by ID, Name, Email, Mobile, RNO">
<button type="submit">Search</button>
</form>

<!-- ADD FORM -->
<form method="POST" class="form-box">
<input type="hidden" name="token" value="<?php echo e($csrf); ?>">
<input type="text" name="parent_name" placeholder="Parent Name" pattern="[A-Za-z ]{3,}" minlength="3" required>
<div class="field-error" data-error-for="parent_name"></div>
<input type="email" name="parent_email" placeholder="Email" required>
<div class="field-error" data-error-for="parent_email"></div>
<div class="ajax-msg" id="adminParentEmailMsg"></div>
<input type="text" name="parent_mobile" placeholder="Mobile Number" pattern="[6-9][0-9]{9}" maxlength="10" inputmode="numeric" required>
<div class="field-error" data-error-for="parent_mobile"></div>
<div class="ajax-msg" id="adminParentMobileMsg"></div>
<input type="password" name="parent_pass" placeholder="Password" required>
<div class="field-error" data-error-for="parent_pass"></div>
<input type="text" name="student_rno" placeholder="Student RNO" pattern="[0-9]+" inputmode="numeric" required>
<div class="field-error" data-error-for="student_rno"></div>
<button type="submit" name="add_parent">Add Parent</button>
</form>

<!-- TABLE -->
<table>

<tr>
<th>ID</th>
<th>Name</th>
<th>Email</th>
<th>Mobile</th>
<th>Student RNO</th>
<th>Password</th>
<th>Action</th>
</tr>
<tbody id="parentSearchResults">

<?php while($row=mysqli_fetch_assoc($result)){ ?>

<tr>

<td><?php echo e($row['id']); ?></td>
<td><?php echo e($row['parent_name']); ?></td>
<td><?php echo e($row['parent_email']); ?></td>
<td><?php echo e($row['parent_mobile'] ?? ''); ?></td>
<td><?php echo e($row['student_rno']); ?></td>
<td>Password Set</td>

<td>
<form method="POST" style="display:inline;" onsubmit="return confirm('Delete this parent?');">
<input type="hidden" name="token" value="<?php echo e($csrf); ?>">
<input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
<button type="submit" name="delete_parent" class="delete">Delete</button>
</form>
</td>

</tr>

<?php } ?>
</tbody>

</table>

</div>

<?php
if(isset($_SESSION['msg'])){
$popupMsg = $_SESSION['msg'];
unset($_SESSION['msg']);
}
?>

<script>
let parentEmail = document.querySelector('[name="parent_email"]');
let parentEmailMsg = document.getElementById("adminParentEmailMsg");

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

const adminParentValidators = {
parent_name: {regex: /^[A-Za-z ]{3,}$/, message: "Name should contain only alphabets and spaces with minimum 3 characters."},
parent_mobile: {regex: /^[6-9][0-9]{9}$/, message: "Enter a valid 10-digit mobile number starting with 6,7,8, or 9."},
parent_pass: {regex: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/, message: "Password must include uppercase, lowercase, number, special character and minimum 8 characters."},
student_rno: {regex: /^[0-9]+$/, message: "Roll Number must contain numbers only."}
};

function setAdminParentError(name, message){
let input = document.querySelector('.form-box [name="' + name + '"]');
let error = document.querySelector('[data-error-for="' + name + '"]');
if(!input || !error) return;
input.classList.toggle("invalid", message !== "");
error.innerText = message;
}

function validateAdminParentField(input){
let name = input.name;
let value = input.value.trim();
if(input.required && value === ""){
setAdminParentError(name, "This field is required.");
return false;
}
if(adminParentValidators[name] && value !== "" && !adminParentValidators[name].regex.test(value)){
setAdminParentError(name, adminParentValidators[name].message);
return false;
}
if(name === "parent_email" && value !== "" && !input.checkValidity()){
setAdminParentError(name, "Enter a valid email address.");
return false;
}
setAdminParentError(name, "");
return true;
}

document.querySelectorAll(".form-box input").forEach(input => {
input.addEventListener("input", () => validateAdminParentField(input));
input.addEventListener("blur", () => validateAdminParentField(input));
});

document.querySelector(".form-box").addEventListener("submit", function(e){
let valid = true;
this.querySelectorAll("input").forEach(input => {
if(!validateAdminParentField(input)) valid = false;
});
if(!valid){
e.preventDefault();
Swal.fire("Validation Error", "Please fix the highlighted fields.", "error");
}
});

<?php if(isset($popupMsg)){ ?>
Swal.fire("Notice", "<?php echo e($popupMsg); ?>", "<?php echo stripos($popupMsg, 'success') !== false ? 'success' : 'error'; ?>");
<?php } ?>
</script>
<script src="ajax_app.js"></script>
<script>
SpmsAjax.availability('[name="parent_mobile"]', "parent_phone", "#adminParentMobileMsg");
SpmsAjax.liveSearch("#parentLiveSearch", "parents", "#parentSearchResults");
</script>

</body>
</html>
