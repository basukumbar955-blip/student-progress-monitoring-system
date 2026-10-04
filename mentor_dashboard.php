<?php
session_start();
include("db_connect.php");

/* SESSION SECURITY */
if(!isset($_SESSION['mentor'])){
    header("Location: mentor_login.php");
    exit();
}

session_regenerate_id(true);

if(empty($_SESSION['mentor_csrf'])){
    $_SESSION['mentor_csrf'] = bin2hex(random_bytes(32));
}

function validRollNumber($value){
    return preg_match('/^[0-9]+$/', $value);
}

function validPercent($value){
    return preg_match('/^[0-9]+$/', (string)$value) && (int)$value >= 0 && (int)$value <= 100;
}

function flashMessage($message){
    $_SESSION['mentor_msg'] = $message;
}

/* ADD ATTENDANCE */
if(isset($_POST['add_attendance'])){

    try {
        if(!isset($_POST['token']) || !hash_equals($_SESSION['mentor_csrf'], $_POST['token'])){
            throw new Exception("Invalid request. Please refresh and try again.");
        }

        $rno = trim($_POST['rno'] ?? "");
        $date = trim($_POST['date'] ?? "");
        $status = trim($_POST['status'] ?? "");

        if($rno === "" || $date === "" || $status === ""){
            throw new Exception("All fields are required!");
        }
        if(!validRollNumber($rno)){
            throw new Exception("Roll Number must contain numbers only.");
        }
        if(!in_array($status, ["Present", "Absent"], true)){
            throw new Exception("Invalid attendance status.");
        }

        $stmt = $conn->prepare("INSERT INTO attendance (rno, date, status) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $rno, $date, $status);
        $stmt->execute();

        flashMessage("Attendance Added Successfully");
    } catch (Exception $e) {
        error_log("Mentor attendance error: " . $e->getMessage());
        flashMessage($e->getMessage());
    }
}

/* ADD / UPDATE MARKS (UPDATED LOGIC) */
if(isset($_POST['add_marks'])){

    try {
        if(!isset($_POST['token']) || !hash_equals($_SESSION['mentor_csrf'], $_POST['token'])){
            throw new Exception("Invalid request. Please refresh and try again.");
        }

        $rno = trim($_POST['rno'] ?? "");
        $subject = trim($_POST['subject'] ?? "");
        $marks = trim($_POST['marks'] ?? "");

        if($rno === "" || $subject === "" || $marks === ""){
            throw new Exception("All fields are required!");
        }
        if(!validRollNumber($rno)){
            throw new Exception("Roll Number must contain numbers only.");
        }
        if(!validPercent($marks)){
            throw new Exception("Marks must be a number between 0 and 100.");
        }

        $marks = (int)$marks;

    /* CHECK EXISTING SUBJECT */
    $check = $conn->prepare("SELECT id FROM student_subjects WHERE rno=? AND subject_name=?");
    $check->bind_param("ss", $rno, $subject);
    $check->execute();
    $res = $check->get_result();

    if($res->num_rows > 0){
        /* UPDATE */
        $stmt = $conn->prepare("UPDATE student_subjects SET marks=? WHERE rno=? AND subject_name=?");
        $stmt->bind_param("iss", $marks, $rno, $subject);
    } else {
        /* INSERT */
        $stmt = $conn->prepare("INSERT INTO student_subjects (rno, subject_name, marks) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $rno, $subject, $marks);
    }

        $stmt->execute();

        flashMessage("Marks Saved Successfully");
    } catch (Exception $e) {
        error_log("Mentor marks error: " . $e->getMessage());
        flashMessage($e->getMessage());
    }
}

/* DASHBOARD COUNTS */
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM student_login");
$stmt->execute();
$student_count = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM attendance");
$stmt->execute();
$attendance_count = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM student_subjects");
$stmt->execute();
$marks_count = $stmt->get_result()->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html>
<head>
<title>Mentor Dashboard</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>

body{
margin:0;
font-family:'Segoe UI';
background:#f1f5f9;
display:flex;
}

/* SIDEBAR */
.sidebar{
width:230px;
background:#0f172a;
color:white;
height:100vh;
position:fixed;
}

.sidebar h2{
text-align:center;
padding:20px;
}

.sidebar a{
display:block;
padding:12px;
color:white;
text-decoration:none;
}

.sidebar a:hover{
background:#1e293b;
}

/* MAIN */
.main{
margin-left:230px;
padding:20px;
width:100%;
}

/* CARDS */
.cards{
display:grid;
grid-template-columns:repeat(3,1fr);
gap:15px;
margin-bottom:20px;
}

.card{
background:white;
padding:20px;
border-radius:10px;
box-shadow:0 4px 10px rgba(0,0,0,0.1);
text-align:center;
}

/* FORM */
.form-box{
background:white;
padding:20px;
margin-bottom:20px;
border-radius:10px;
box-shadow:0 4px 10px rgba(0,0,0,0.1);
}

input,select{
width:100%;
padding:10px;
margin:8px 0;
border-radius:5px;
border:1px solid #ccc;
}

input.invalid,select.invalid{
border-color:#dc2626;
}

.student-select{
position:relative;
margin:8px 0;
}

.student-select input[type="text"]{
margin:0;
}

.student-options{
display:none;
position:absolute;
top:calc(100% + 4px);
left:0;
right:0;
max-height:220px;
overflow-y:auto;
background:white;
border:1px solid #cbd5e1;
border-radius:5px;
box-shadow:0 10px 24px rgba(15,23,42,0.16);
z-index:20;
}

.student-select.open .student-options{
display:block;
}

.student-option{
width:100%;
display:block;
padding:10px;
border:0;
border-bottom:1px solid #e2e8f0;
background:white;
color:#0f172a;
text-align:left;
border-radius:0;
cursor:pointer;
}

.student-option:hover,
.student-option.active{
background:#eff6ff;
color:#1d4ed8;
}

.student-option:last-child{
border-bottom:0;
}

.student-empty{
display:none;
padding:10px;
color:#64748b;
font-size:14px;
}

.student-options.empty .student-empty{
display:block;
}

.table-search{
margin:10px 0 0;
}

.table-search input{
max-width:420px;
}

.no-students-row{
display:none;
}

.field-error{
color:#dc2626;
font-size:12px;
margin-top:-4px;
margin-bottom:6px;
min-height:14px;
}

button{
background:#2563eb;
color:white;
border:none;
padding:10px;
border-radius:5px;
cursor:pointer;
}

button:hover{
background:#1d4ed8;
}

/* TABLE */
table{
width:100%;
border-collapse:collapse;
background:white;
margin-top:20px;
}

th,td{
padding:10px;
border-bottom:1px solid #ddd;
}

th{
background:#2563eb;
color:white;
}

</style>

</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
<h2>Mentor Panel</h2>

<a href="#"><i class="fa fa-home"></i> Dashboard</a>
<a href="#attendance"><i class="fa fa-calendar"></i> Attendance</a>
<a href="#marks"><i class="fa fa-book"></i> Marks</a>
<a href="#students"><i class="fa fa-users"></i> Students</a>
<a href="Ngo.php"><i class="fa fa-sign-out"></i> Logout</a>

</div>

<!-- MAIN -->
<div class="main">

<h1>Mentor Dashboard</h1>

<!-- STATS -->
<div class="cards">

<div class="card">
<h3><?php echo $student_count; ?></h3>
<p>Total Students</p>
</div>

<div class="card">
<h3><?php echo $attendance_count; ?></h3>
<p>Attendance Records</p>
</div>

<div class="card">
<h3><?php echo $marks_count; ?></h3>
<p>Total Subject Entries</p>
</div>

</div>

<!-- ADD ATTENDANCE -->
<div class="form-box" id="attendance">
<h3>Add Attendance</h3>

<form method="POST" id="attendanceAjaxForm">
<input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['mentor_csrf'], ENT_QUOTES, 'UTF-8'); ?>">

<div class="student-select" data-searchable-student>
<input type="text" class="student-search-input" placeholder="Search student by Roll No or Name" autocomplete="off" required>
<input type="hidden" name="rno" required>
<div class="student-options">
<?php
$res = $conn->prepare("SELECT rno, s_name FROM student_login ORDER BY rno ASC");
$res->execute();
$students = $res->get_result();
while($s=$students->fetch_assoc()){
echo "<button type='button' class='student-option' data-rno='".htmlspecialchars($s['rno'], ENT_QUOTES, 'UTF-8')."' data-name='".htmlspecialchars($s['s_name'], ENT_QUOTES, 'UTF-8')."'>".htmlspecialchars($s['rno'])." - ".htmlspecialchars($s['s_name'])."</button>";
}
?>
<div class="student-empty">No students found</div>
</div>
</div>
<div class="field-error" data-error-for="rno"></div>

<input type="date" name="date" required>
<div class="field-error" data-error-for="date"></div>

<select name="status">
<option>Present</option>
<option>Absent</option>
</select>

<button name="add_attendance">Submit</button>

</form>
</div>

<!-- ADD MARKS -->
<div class="form-box" id="marks">
<h3>Add / Update Marks</h3>

<form method="POST" id="marksAjaxForm">
<input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['mentor_csrf'], ENT_QUOTES, 'UTF-8'); ?>">

<div class="student-select" data-searchable-student>
<input type="text" class="student-search-input" placeholder="Search student by Roll No or Name" autocomplete="off" required>
<input type="hidden" name="rno" required>
<div class="student-options">
<?php
$res = $conn->prepare("SELECT rno, s_name FROM student_login ORDER BY rno ASC");
$res->execute();
$students = $res->get_result();
while($s=$students->fetch_assoc()){
echo "<button type='button' class='student-option' data-rno='".htmlspecialchars($s['rno'], ENT_QUOTES, 'UTF-8')."' data-name='".htmlspecialchars($s['s_name'], ENT_QUOTES, 'UTF-8')."'>".htmlspecialchars($s['rno'])." - ".htmlspecialchars($s['s_name'])."</button>";
}
?>
<div class="student-empty">No students found</div>
</div>
</div>
<div class="field-error" data-error-for="rno"></div>

<select name="subject" required>
<option value="">Select Subject</option>
<option>Mathematics</option>
<option>Science</option>
<option>English</option>
<option>Computer</option>
</select>

<input type="number" name="marks" min="0" max="100" step="1" required>
<div class="field-error" data-error-for="marks"></div>

<button name="add_marks">Submit</button>

</form>
</div>

<!-- STUDENTS -->
<h3 id="students">Student List</h3>

<div class="table-search">
<input type="text" id="studentListSearch" placeholder="Search students by Roll No, Name, or Email" autocomplete="off">
</div>

<table id="studentListTable">
<tr>
<th>Roll No</th>
<th>Name</th>
<th>Email</th>
</tr>

<?php
$res = $conn->prepare("SELECT rno, s_name, s_email FROM student_login ORDER BY rno ASC");
$res->execute();
$students = $res->get_result();
while($row=$students->fetch_assoc()){
echo "<tr class='student-list-row'>
<td>".htmlspecialchars($row['rno'])."</td>
<td>".htmlspecialchars($row['s_name'])."</td>
<td>".htmlspecialchars($row['s_email'])."</td>
</tr>";
}
?>
<tr class="no-students-row">
<td colspan="3">No matching students found</td>
</tr>

</table>

<!-- ATTENDANCE -->
<h3>Attendance Records</h3>

<table>
<tr>
<th>Roll No</th>
<th>Date</th>
<th>Status</th>
</tr>
<tbody id="attendanceTableBody">

<?php
$res = $conn->prepare("SELECT rno, date, status FROM attendance ORDER BY date DESC");
$res->execute();
$attendance = $res->get_result();
while($row=$attendance->fetch_assoc()){
echo "<tr>
<td>".htmlspecialchars($row['rno'])."</td>
<td>".htmlspecialchars($row['date'])."</td>
<td>".htmlspecialchars($row['status'])."</td>
</tr>";
}
?>
</tbody>

</table>

<h3>Marks Records</h3>
<table>
<tr>
<th>Roll No</th>
<th>Subject</th>
<th>Marks</th>
</tr>
<tbody id="marksTableBody">
<?php
$res = $conn->prepare("SELECT rno, subject_name, marks FROM student_subjects ORDER BY id DESC");
$res->execute();
$marksRows = $res->get_result();
while($row=$marksRows->fetch_assoc()){
echo "<tr>
<td>".htmlspecialchars($row['rno'])."</td>
<td>".htmlspecialchars($row['subject_name'])."</td>
<td>".htmlspecialchars($row['marks'])."</td>
</tr>";
}
?>
</tbody>
</table>

</div>

<script>
function normalizeText(value){
return value.toLowerCase().trim();
}

document.querySelectorAll('[data-searchable-student]').forEach(widget => {
let searchInput = widget.querySelector('.student-search-input');
let hiddenInput = widget.querySelector('input[name="rno"]');
let optionsBox = widget.querySelector('.student-options');
let options = Array.from(widget.querySelectorAll('.student-option'));

function filterOptions(){
let query = normalizeText(searchInput.value);
let visibleCount = 0;

options.forEach(option => {
let searchable = normalizeText(option.dataset.rno + " " + option.dataset.name);
let visible = searchable.includes(query);
option.style.display = visible ? "" : "none";
option.classList.remove("active");
if(visible){
visibleCount++;
}
});

optionsBox.classList.toggle("empty", visibleCount === 0);
widget.classList.add("open");
}

searchInput.addEventListener("focus", filterOptions);
searchInput.addEventListener("input", function(){
hiddenInput.value = "";
filterOptions();
});

options.forEach(option => {
option.addEventListener("click", function(){
hiddenInput.value = option.dataset.rno;
searchInput.value = option.dataset.rno + " - " + option.dataset.name;
searchInput.classList.remove("invalid");
widget.classList.remove("open");
});
});
});

document.addEventListener("click", function(e){
document.querySelectorAll('[data-searchable-student]').forEach(widget => {
if(!widget.contains(e.target)){
widget.classList.remove("open");
}
});
});

let studentListSearch = document.getElementById("studentListSearch");
if(studentListSearch){
studentListSearch.addEventListener("input", function(){
let query = normalizeText(this.value);
let rows = document.querySelectorAll("#studentListTable .student-list-row");
let visibleCount = 0;

rows.forEach(row => {
let text = normalizeText(row.innerText);
let visible = text.includes(query);
row.style.display = visible ? "" : "none";
if(visible){
visibleCount++;
}
});

let emptyRow = document.querySelector("#studentListTable .no-students-row");
if(emptyRow){
emptyRow.style.display = visibleCount === 0 ? "" : "none";
}
});
}

function setMentorError(form, name, message){
let input = form.querySelector('[name="' + name + '"]');
let error = form.querySelector('[data-error-for="' + name + '"]');
if(!input || !error) return;
input.classList.toggle("invalid", message !== "");
let studentSearchInput = form.querySelector('.student-search-input');
if(name === "rno" && studentSearchInput){
studentSearchInput.classList.toggle("invalid", message !== "");
}
error.innerText = message;
}

document.querySelectorAll('form').forEach(form => {
form.addEventListener('submit', function(e){
let valid = true;
let rno = form.querySelector('[name="rno"]');
let marks = form.querySelector('[name="marks"]');
let date = form.querySelector('[name="date"]');

if(rno && rno.value.trim() === ""){
setMentorError(form, "rno", "Select a student from the list.");
valid = false;
} else if(rno){
setMentorError(form, "rno", "");
}

if(date && date.value === ""){
setMentorError(form, "date", "This field is required.");
valid = false;
}

if(marks){
let value = marks.value.trim();
if(value === "" || !/^[0-9]+$/.test(value) || Number(value) < 0 || Number(value) > 100){
setMentorError(form, "marks", "Marks must be a number between 0 and 100.");
valid = false;
} else {
setMentorError(form, "marks", "");
}
}

if(!valid){
e.preventDefault();
Swal.fire("Validation Error", "Please fix the highlighted fields.", "error");
}
});
});

<?php if(isset($_SESSION['mentor_msg'])){ ?>
Swal.fire("Notice", "<?php echo htmlspecialchars($_SESSION['mentor_msg'], ENT_QUOTES, 'UTF-8'); ?>", "<?php echo stripos($_SESSION['mentor_msg'], 'Successfully') !== false ? 'success' : 'error'; ?>");
<?php unset($_SESSION['mentor_msg']); } ?>
</script>
<script src="ajax_app.js"></script>
<script>
SpmsAjax.mentorForms("#attendanceAjaxForm", "#marksAjaxForm");
</script>

</body>
</html>
