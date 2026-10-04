<?php
session_start();
include("db_connect.php");

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

function validCourse($value){
return in_array($value, ['BCA', 'BBA', 'B.Com', 'BA', 'B.Sc', 'Other'], true);
}

function ensureStudentMobileColumn($conn){
$column = "mobile";
$check = $conn->prepare("SHOW COLUMNS FROM student_login LIKE ?");
if(!$check){
return false;
}
$check->bind_param("s", $column);
$check->execute();
$result = $check->get_result();
$exists = ($result && $result->num_rows > 0);
$check->close();

if($exists){
return true;
}

if($conn->query("ALTER TABLE student_login ADD COLUMN mobile VARCHAR(10) NULL")){
return true;
}

return stripos($conn->error, "Duplicate column") !== false;
}

function ensureStudentCourseColumn($conn){
$column = "course";
$check = $conn->prepare("SHOW COLUMNS FROM student_login LIKE ?");
if(!$check){
return false;
}
$check->bind_param("s", $column);
$check->execute();
$result = $check->get_result();
$exists = ($result && $result->num_rows > 0);
$check->close();

if($exists){
return true;
}

if($conn->query("ALTER TABLE student_login ADD COLUMN course VARCHAR(100) NOT NULL DEFAULT 'Other'")){
$conn->query("ALTER TABLE student_login MODIFY COLUMN course VARCHAR(100) NOT NULL");
return true;
}

return stripos($conn->error, "Duplicate column") !== false;
}

$studentHasMobile = ensureStudentMobileColumn($conn);
$studentHasCourse = ensureStudentCourseColumn($conn);

/* ADD STUDENT */

if(isset($_POST['add_student'])){

try {
if(!isset($_POST['token']) || !hash_equals($_SESSION['admin_csrf'], $_POST['token'])){
throw new Exception("Invalid request. Please refresh and try again.");
}

$rno = trim($_POST['rno']);
$s_name = trim($_POST['s_name']);
$course = trim($_POST['course'] ?? "");
$s_email = filter_var($_POST['s_email'], FILTER_SANITIZE_EMAIL);
$mobile = trim($_POST['mobile'] ?? "");
$password_plain = $_POST['u_password'];

if($rno === "" || $s_name === "" || $course === "" || $s_email === "" || $password_plain === "" || ($studentHasMobile && $mobile === "")){
throw new Exception("All fields are required!");
}
if(!validRollNumber($rno)){
throw new Exception("Roll Number must contain numbers only.");
}
if(!validName($s_name)){
throw new Exception("Name should contain only alphabets and spaces with minimum 3 characters.");
}
if(!validCourse($course)){
throw new Exception("Select a valid course.");
}
if(!filter_var($s_email, FILTER_VALIDATE_EMAIL)){
throw new Exception("Enter a valid email address.");
}
if($studentHasMobile && !validMobile($mobile)){
throw new Exception("Enter a valid 10-digit mobile number starting with 6,7,8, or 9.");
}
if(!validPassword($password_plain)){
throw new Exception("Password must be at least 8 characters and include uppercase, lowercase, number, and special character.");
}

$studentHasMobile = ensureStudentMobileColumn($conn);
$studentHasCourse = ensureStudentCourseColumn($conn);

/* PASSWORD ENCRYPTION */
$u_password = password_hash($password_plain, PASSWORD_DEFAULT);

/* CHECK DUPLICATE ROLL NUMBER OR EMAIL */

$check = $conn->prepare("SELECT u_id FROM student_login WHERE rno=? OR s_email=? LIMIT 1");
$check->bind_param("ss", $rno, $s_email);
$check->execute();
$check_result = $check->get_result();

if($check_result->num_rows > 0){

throw new Exception("Roll Number or Email already exists!");

}else{

if($studentHasMobile){
$stmt = $conn->prepare("INSERT INTO student_login (rno,s_name,course,s_email,mobile,u_password) VALUES (?,?,?,?,?,?)");
$stmt->bind_param("ssssss", $rno, $s_name, $course, $s_email, $mobile, $u_password);
}else{
$stmt = $conn->prepare("INSERT INTO student_login (rno,s_name,course,s_email,u_password) VALUES (?,?,?,?,?)");
$stmt->bind_param("sssss", $rno, $s_name, $course, $s_email, $u_password);
}

if($stmt->execute()){
$_SESSION['msg'] = "Student added successfully!";
}else{
throw new Exception("Student add failed!");
}

}
} catch (Exception $e) {
error_log("Admin add student error: " . $e->getMessage());
$_SESSION['msg'] = $e->getMessage();
}

header("Location: admin_manage_students.php");
exit();
}

/* DELETE STUDENT */

if(isset($_POST['delete_student'])){

if(!isset($_POST['token']) || !hash_equals($_SESSION['admin_csrf'], $_POST['token'])){
die("Invalid CSRF token");
}

$u_id = intval($_POST['u_id']);

$stmt = $conn->prepare("DELETE FROM student_login WHERE u_id=?");
$stmt->bind_param("i", $u_id);
$stmt->execute();

$_SESSION['msg'] = "Student deleted successfully!";

header("Location: admin_manage_students.php");
exit();
}

/* SEARCH */

$where = "";

if(isset($_GET['search'])){

$search = trim($_GET['search']);
$like = "%".$search."%";

if($studentHasMobile){
$stmt = $conn->prepare("SELECT * FROM student_login WHERE rno LIKE ? OR s_name LIKE ? OR course LIKE ? OR s_email LIKE ? OR mobile LIKE ? ORDER BY u_id DESC");
$stmt->bind_param("sssss", $like, $like, $like, $like, $like);
}else{
$stmt = $conn->prepare("SELECT * FROM student_login WHERE rno LIKE ? OR s_name LIKE ? OR course LIKE ? OR s_email LIKE ? ORDER BY u_id DESC");
$stmt->bind_param("ssss", $like, $like, $like, $like);
}
$stmt->execute();
$result = $stmt->get_result();
}else{
$stmt = $conn->prepare("SELECT * FROM student_login ORDER BY u_id DESC");
$stmt->execute();
$result = $stmt->get_result();
}

/* STUDENT COUNT */

$count_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM student_login");
$count_stmt->execute();
$count_data = $count_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
<title>Manage Students</title>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Segoe UI',sans-serif;
transition:0.3s;
}

/* BODY BACKGROUND */

body{
background:
linear-gradient(rgba(0,0,0,0.15), rgba(0,0,0,0.15)),
url("student_bg.jpg");
background-size:cover;
background-position:center;
background-repeat:no-repeat;
background-attachment:fixed;

min-height:100vh;
display:flex;
justify-content:center;
align-items:center;
}

/* BACK BUTTON */

.back-btn{
position:absolute;
top:20px;
left:20px;
padding:8px 16px;
background:#64748b;
color:white;
text-decoration:none;
border-radius:6px;
font-size:14px;
box-shadow:0 3px 10px rgba(0,0,0,0.3);
}

.back-btn:hover{
background:#475569;
transform:scale(1.05);
}

/* CONTAINER */

.container{
width:1000px;
max-width:95%;
padding:30px;
border-radius:12px;

/* GLASS EFFECT */
background:rgba(255,255,255,0.92);
backdrop-filter:blur(8px);

box-shadow:0 15px 40px rgba(0,0,0,0.3);
}

.header{
margin-bottom:15px;
}

.header h2{
color:#1e293b;
}

/* STUDENT COUNT */

.count{
margin-bottom:15px;
font-weight:bold;
color:#2563eb;
}

/* SEARCH */

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
cursor:pointer;
}

.search-box button:hover{
background:#15803d;
}

/* FORM */

.form-box{
display:flex;
flex-wrap:wrap;
gap:10px;
margin-bottom:20px;
}

.form-box input,
.form-box select{
flex:1;
padding:10px;
border:1px solid #ccc;
border-radius:6px;
}

.form-box input.invalid,
.form-box select.invalid{
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

.form-box button:hover{
background:#1d4ed8;
transform:translateY(-2px);
}

.ajax-msg{
width:100%;
font-size:12px;
margin-top:-6px;
}

/* TABLE */

table{
width:100%;
border-collapse:collapse;
border-radius:10px;
overflow:hidden;
box-shadow:0 5px 15px rgba(0,0,0,0.1);
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

tr:nth-child(even){
background:#f8fafc;
}

tr:hover{
background:#e2e8f0;
transform:scale(1.01);
}

/* DELETE BUTTON */

.delete{
background:#ef4444;
color:white;
padding:6px 12px;
border-radius:5px;
text-decoration:none;
border:none;
cursor:pointer;
}

.delete:hover{
background:#dc2626;
}

</style>

</head>

<body>

<a href="admin_dashboard.php" class="back-btn">⬅ Back</a>

<div class="container">

<div class="header">
<h2>Manage Students</h2>
</div>

<div class="count">
Total Students: <?php echo $count_data['total']; ?>
</div>

<form method="GET" class="search-box">

<input type="text" id="studentLiveSearch" name="search" placeholder="Search by Roll No, Name, Course, Email<?php echo $studentHasMobile ? ', Mobile' : ''; ?>">

<button type="submit">Search</button>

</form>

<form method="POST" class="form-box">

<input type="hidden" name="token" value="<?php echo e($csrf); ?>">
<input type="text" name="rno" placeholder="Roll Number" pattern="[0-9]+" inputmode="numeric" required>
<div class="field-error" data-error-for="rno"></div>
<div class="ajax-msg" id="adminRnoMsg"></div>
<input type="text" name="s_name" placeholder="Student Name" pattern="[A-Za-z ]{3,}" minlength="3" required>
<div class="field-error" data-error-for="s_name"></div>
<select name="course" required>
<option value="">Select Course</option>
<option value="BCA">BCA</option>
<option value="BBA">BBA</option>
<option value="B.Com">B.Com</option>
<option value="BA">BA</option>
<option value="B.Sc">B.Sc</option>
<option value="Other">Other</option>
</select>
<div class="field-error" data-error-for="course"></div>
<input type="email" name="s_email" placeholder="Email" required>
<div class="field-error" data-error-for="s_email"></div>
<div class="ajax-msg" id="adminEmailMsg"></div>
<?php if($studentHasMobile){ ?>
<input type="text" name="mobile" placeholder="Mobile Number" pattern="[6-9][0-9]{9}" maxlength="10" inputmode="numeric" required>
<div class="field-error" data-error-for="mobile"></div>
<div class="ajax-msg" id="adminMobileMsg"></div>
<?php } ?>
<input type="password" name="u_password" placeholder="Password" required>
<div class="field-error" data-error-for="u_password"></div>

<button name="add_student">Add Student</button>

</form>

<table>

<tr>
<th>ID</th>
<th>Roll No</th>
<th>Name</th>
<th>Course</th>
<th>Email</th>
<?php if($studentHasMobile){ ?>
<th>Mobile</th>
<?php } ?>
<th>Action</th>
</tr>
<tbody id="studentSearchResults">

<?php while($row=mysqli_fetch_assoc($result)){ ?>

<tr>

<td><?php echo e($row['u_id']); ?></td>
<td><?php echo e($row['rno']); ?></td>
<td><?php echo e($row['s_name']); ?></td>
<td><?php echo e($row['course'] ?? ''); ?></td>
<td><?php echo e($row['s_email']); ?></td>
<?php if($studentHasMobile){ ?>
<td><?php echo e($row['mobile'] ?? ''); ?></td>
<?php } ?>

<td>
<form method="POST" style="display:inline;" onsubmit="return confirm('Delete this student?');">
<input type="hidden" name="token" value="<?php echo e($csrf); ?>">
<input type="hidden" name="u_id" value="<?php echo (int)$row['u_id']; ?>">
<button type="submit" name="delete_student" class="delete">Delete</button>
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

ajaxCheck("rno", "student_rno", "adminRnoMsg");
ajaxCheck("s_email", "student_email", "adminEmailMsg");

const adminStudentValidators = {
rno: {regex: /^[0-9]+$/, message: "Roll Number must contain numbers only."},
s_name: {regex: /^[A-Za-z ]{3,}$/, message: "Name should contain only alphabets and spaces with minimum 3 characters."},
course: {allowed: ["BCA", "BBA", "B.Com", "BA", "B.Sc", "Other"], message: "Select a valid course."},
u_password: {regex: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/, message: "Password must include uppercase, lowercase, number, special character and minimum 8 characters."}
,
mobile: {regex: /^[6-9][0-9]{9}$/, message: "Enter a valid 10-digit mobile number starting with 6,7,8, or 9."}
};

function setAdminStudentError(name, message){
let input = document.querySelector('.form-box [name="' + name + '"]');
let error = document.querySelector('[data-error-for="' + name + '"]');
if(!input || !error) return;
input.classList.toggle("invalid", message !== "");
error.innerText = message;
}

function validateAdminStudentField(input){
let name = input.name;
let value = input.value.trim();
if(input.required && value === ""){
setAdminStudentError(name, "This field is required.");
return false;
}
if(adminStudentValidators[name] && adminStudentValidators[name].regex && value !== "" && !adminStudentValidators[name].regex.test(value)){
setAdminStudentError(name, adminStudentValidators[name].message);
return false;
}
if(adminStudentValidators[name] && adminStudentValidators[name].allowed && value !== "" && !adminStudentValidators[name].allowed.includes(value)){
setAdminStudentError(name, adminStudentValidators[name].message);
return false;
}
if(name === "s_email" && value !== "" && !input.checkValidity()){
setAdminStudentError(name, "Enter a valid email address.");
return false;
}
setAdminStudentError(name, "");
return true;
}

document.querySelectorAll(".form-box input, .form-box select").forEach(input => {
input.addEventListener("input", () => validateAdminStudentField(input));
input.addEventListener("blur", () => validateAdminStudentField(input));
});

document.querySelector(".form-box").addEventListener("submit", function(e){
let valid = true;
this.querySelectorAll("input, select").forEach(input => {
if(!validateAdminStudentField(input)) valid = false;
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
SpmsAjax.availability('[name="mobile"]', "student_phone", "#adminMobileMsg");
SpmsAjax.liveSearch("#studentLiveSearch", "students", "#studentSearchResults");
</script>

</body>
</html>
