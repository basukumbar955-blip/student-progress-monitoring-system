<?php
session_start();
include("db_connect.php");

/* SESSION PROTECTION */
if(!isset($_SESSION['parent'])){
    header("Location: parent_login.php");
    exit();
}

session_regenerate_id(true);

$rno = $_SESSION['parent'];

/* FETCH STUDENT BASIC INFO */
$stmt = $conn->prepare("SELECT * FROM student_login WHERE rno=?");
$stmt->bind_param("s",$rno);
$stmt->execute();
$res = $stmt->get_result();
$student = $res->fetch_assoc();

$name = $student['s_name'] ?? "Student";
$email = $student['s_email'] ?? "N/A";

/* FETCH SUBJECT DATA */
$subjects = [];
$stmt = $conn->prepare("SELECT * FROM student_subjects WHERE rno=?");
$stmt->bind_param("s",$rno);
$stmt->execute();
$res = $stmt->get_result();

while($row = $res->fetch_assoc()){
    $subjects[] = $row;
}

/* FETCH ATTENDANCE */
$attendance_records = [];
$stmt = $conn->prepare("SELECT * FROM attendance WHERE rno=? ORDER BY date DESC");
$stmt->bind_param("s",$rno);
$stmt->execute();
$res = $stmt->get_result();

while($row = $res->fetch_assoc()){
    $attendance_records[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Student Info</title>

<style>

body{
margin:0;
font-family:'Segoe UI',sans-serif;
background:#f4f6fb;
display:flex;
}

/* SIDEBAR */
.sidebar{
width:200px;
background:#111827;
color:white;
padding:20px;
position:fixed;
height:100%;
}

.sidebar a{
display:block;
padding:12px;
margin:8px 0;
color:#cbd5e1;
text-decoration:none;
border-radius:6px;
}

.sidebar a:hover{
background:#1f2937;
}

/* MAIN */
.main{
margin-left:210px;
padding:30px;
width:calc(100% - 210px);
max-width:1200px;
}

/* CARD */
.card{
background:white;
padding:20px;
border-radius:12px;
box-shadow:0 4px 12px rgba(0,0,0,0.08);
margin-bottom:20px;
}

/* TABLE */
table{
width:100%;
border-collapse:collapse;
}

th, td{
padding:10px;
border-bottom:1px solid #ddd;
text-align:center;
}

th{
background:#3b82f6;
color:white;
}

/* BADGE */
.present{
color:green;
font-weight:bold;
}

.absent{
color:red;
font-weight:bold;
}

</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
<h2>Parent Panel</h2>
<a href="parent_dashboard.php">🏠 Dashboard</a>
<a href="Parent_performance.php">📊 Performance</a>
<a href="parent_attendance.php">📅 Attendance</a>
<a href="student_info.php">👨‍🎓 Student Info</a>
<a href="Ngo.php">🚪 Logout</a>
</div>

<!-- MAIN -->
<div class="main">

<h2>👨‍🎓 Student Information</h2>

<!-- BASIC INFO -->
<div class="card">
<h3>Basic Details</h3>
<p><b>Name:</b> <?php echo htmlspecialchars($name); ?></p>
<p><b>Roll No:</b> <?php echo htmlspecialchars($rno); ?></p>
<p><b>Email:</b> <?php echo htmlspecialchars($email); ?></p>
</div>

<!-- SUBJECTS -->
<div class="card">
<h3>📚 Subjects & Marks</h3>

<table>
<tr>
<th>Subject</th>
<th>Marks</th>
</tr>

<?php
if(count($subjects) > 0){
    foreach($subjects as $sub){
        echo "<tr>
        <td>".htmlspecialchars($sub['subject_name'])."</td>
        <td>".$sub['marks']."%</td>
        </tr>";
    }
}else{
    echo "<tr><td colspan='2'>No data available</td></tr>";
}
?>

</table>
</div>

<!-- ATTENDANCE -->
<div class="card">
<h3>📅 Attendance History</h3>

<table>
<tr>
<th>Date</th>
<th>Status</th>
</tr>

<?php
if(count($attendance_records) > 0){
    foreach($attendance_records as $att){
        $class = ($att['status']=="Present") ? "present" : "absent";

        echo "<tr>
        <td>".$att['date']."</td>
        <td class='$class'>".$att['status']."</td>
        </tr>";
    }
}else{
    echo "<tr><td colspan='2'>No attendance records</td></tr>";
}
?>

</table>

</div>

</div>

</body>
</html>