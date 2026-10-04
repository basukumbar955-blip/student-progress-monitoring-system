<?php
session_start();
include("db_connect.php");
require_once "password_reset_helpers.php";

ensure_mentor_email_column($conn);

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

function validPassword($value){
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $value);
}

/* ================= ADD MENTOR ================= */

if(isset($_POST['add_mentor'])){

    try {
        if(!isset($_POST['token']) || !hash_equals($_SESSION['admin_csrf'], $_POST['token'])){
            throw new Exception("Invalid request. Please refresh and try again.");
        }

    $mentor_user = trim($_POST['mentor_user']);
    $mentor_email = filter_var($_POST['mentor_email'], FILTER_SANITIZE_EMAIL);
    $mentor_pass_plain = $_POST['mentor_pass'];

    if(empty($mentor_user) || empty($mentor_email) || empty($mentor_pass_plain)){
        throw new Exception("All fields are required!");
    } elseif(!filter_var($mentor_email, FILTER_VALIDATE_EMAIL)){
        throw new Exception("Enter a valid email address.");
    } elseif(!validPassword($mentor_pass_plain)){
        throw new Exception("Password must be at least 8 characters and include uppercase, lowercase, number, and special character.");
    } else {

        // HASH PASSWORD
        $mentor_pass = password_hash($mentor_pass_plain, PASSWORD_DEFAULT);

        // CHECK DUPLICATE USERNAME
        $check = $conn->prepare("SELECT id FROM mentor_login WHERE mentor_user=? OR mentor_email=? LIMIT 1");
        $check->bind_param("ss", $mentor_user, $mentor_email);
        $check->execute();
        $check_result = $check->get_result();

        if($check_result->num_rows > 0){

            $_SESSION['msg'] = "Mentor username or email already exists!";

        } else {

            $stmt = $conn->prepare("INSERT INTO mentor_login (mentor_user, mentor_email, mentor_pass) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $mentor_user, $mentor_email, $mentor_pass);

            if($stmt->execute()){
                $_SESSION['msg'] = "Mentor added successfully!";
            } else {
                throw new Exception("Mentor add failed!");
            }
        }
    }
    } catch (Exception $e) {
        error_log("Admin add mentor error: " . $e->getMessage());
        $_SESSION['msg'] = $e->getMessage();
    }

    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

/* ================= DELETE ================= */

if(isset($_POST['delete_mentor'])){

    if(!isset($_POST['token']) || !hash_equals($_SESSION['admin_csrf'], $_POST['token'])){
        die("Invalid CSRF token");
    }

    $id = intval($_POST['id']);

    $stmt = $conn->prepare("DELETE FROM mentor_login WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $_SESSION['msg'] = "Mentor deleted successfully!";

    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

/* ================= SEARCH ================= */

$where = "";

if(isset($_GET['search'])){
    $search = trim($_GET['search']);
    $like = "%".$search."%";
    $stmt = $conn->prepare("SELECT * FROM mentor_login WHERE id LIKE ? OR mentor_user LIKE ? OR mentor_email LIKE ? ORDER BY id DESC");
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
}else{
    $stmt = $conn->prepare("SELECT * FROM mentor_login ORDER BY id DESC");
    $stmt->execute();
    $result = $stmt->get_result();
}

/* ================= FETCH ================= */

/* ================= COUNT ================= */

$count_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM mentor_login");
$count_stmt->execute();
$count_data = $count_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
<title>Manage Mentors</title>
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
width:1000px;
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

<h2>Manage Mentors</h2>

<div class="count">
Total Mentors: <?php echo $count_data['total']; ?>
</div>

<!-- SEARCH -->
<form method="GET" class="search-box">
<input type="text" id="mentorLiveSearch" name="search" placeholder="Search by ID, Username, or Email">
<button type="submit">Search</button>
</form>

<!-- ADD FORM -->
<form method="POST" class="form-box">
<input type="hidden" name="token" value="<?php echo e($csrf); ?>">
<input type="text" name="mentor_user" placeholder="Username" required>
<div class="ajax-msg" id="mentorUserMsg"></div>
<input type="email" name="mentor_email" placeholder="Email" required>
<div class="field-error" data-error-for="mentor_email"></div>
<div class="ajax-msg" id="mentorEmailMsg"></div>
<input type="password" name="mentor_pass" placeholder="Password" required>
<div class="field-error" data-error-for="mentor_pass"></div>
<button type="submit" name="add_mentor">Add Mentor</button>
</form>

<!-- TABLE -->
<table>

<tr>
<th>ID</th>
<th>Username</th>
<th>Email</th>
<th>Password</th>
<th>Action</th>
</tr>
<tbody id="mentorSearchResults">

<?php while($row=mysqli_fetch_assoc($result)){ ?>

<tr>

<td><?php echo e($row['id']); ?></td>
<td><?php echo e($row['mentor_user']); ?></td>
<td><?php echo e($row['mentor_email']); ?></td>
<td>Password Set</td>

<td>
<form method="POST" style="display:inline;" onsubmit="return confirm('Delete this mentor?');">
<input type="hidden" name="token" value="<?php echo e($csrf); ?>">
<input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
<button type="submit" name="delete_mentor" class="delete">Delete</button>
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
function ajaxCheck(inputName, type, messageId){
let input = document.querySelector('[name="' + inputName + '"]');
let msg = document.getElementById(messageId);

input.addEventListener("blur", function(){
let value = this.value.trim();
msg.innerText = "";

if(value === ""){
return;
}

let data = new FormData();
data.append("type", type);
data.append("value", value);

fetch("ajax_check.php", {
method: "POST",
body: data
})
.then(response => response.json())
.then(result => {
msg.style.color = result.exists ? "red" : "green";
msg.innerText = result.message;
})
.catch(() => {
msg.style.color = "red";
msg.innerText = "Unable to check right now";
});
});
}

ajaxCheck("mentor_user", "mentor_user", "mentorUserMsg");
ajaxCheck("mentor_email", "mentor_email", "mentorEmailMsg");

function setMentorAdminError(name, message){
let input = document.querySelector('.form-box [name="' + name + '"]');
let error = document.querySelector('[data-error-for="' + name + '"]');
if(!input || !error) return;
input.classList.toggle("invalid", message !== "");
error.innerText = message;
}

function validateMentorAdminField(input){
let name = input.name;
let value = input.value.trim();
if(input.required && value === ""){
setMentorAdminError(name, "This field is required.");
return false;
}
if(name === "mentor_email" && value !== "" && !input.checkValidity()){
setMentorAdminError(name, "Enter a valid email address.");
return false;
}
if(name === "mentor_pass" && value !== "" && !/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/.test(value)){
setMentorAdminError(name, "Password must include uppercase, lowercase, number, special character and minimum 8 characters.");
return false;
}
setMentorAdminError(name, "");
return true;
}

document.querySelectorAll(".form-box input").forEach(input => {
input.addEventListener("input", () => validateMentorAdminField(input));
input.addEventListener("blur", () => validateMentorAdminField(input));
});

document.querySelector(".form-box").addEventListener("submit", function(e){
let valid = true;
this.querySelectorAll("input").forEach(input => {
if(!validateMentorAdminField(input)) valid = false;
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
SpmsAjax.liveSearch("#mentorLiveSearch", "mentors", "#mentorSearchResults");
</script>

</body>
</html>
