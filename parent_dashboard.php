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

/* FETCH BASIC DATA */
$stmt = $conn->prepare("SELECT * FROM student_progress WHERE rno=?");
$stmt->bind_param("s", $rno);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

$student_name = $data['student_name'] ?? "Student";
$attendance = $data['attendance'] ?? 0;
$marks = $data['marks'] ?? 0;

/* FETCH SUBJECT DATA */
$subjects = [];
$marks_data = [];

$stmt = $conn->prepare("SELECT subject_name, marks FROM student_subjects WHERE rno=?");
$stmt->bind_param("s",$rno);
$stmt->execute();
$res = $stmt->get_result();

while($row = $res->fetch_assoc()){
    $subjects[] = $row['subject_name'];
    $marks_data[] = $row['marks'];
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Parent Dashboard</title>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>

body{
margin:0;
font-family:'Segoe UI',sans-serif;
background:#f4f6fb;
display:flex;
}

/* SIDEBAR */
.sidebar{
width:180px;
background:#111827;
color:white;
padding:20px;
position:fixed;
height:100%;
top:0;
left:0;
}

.sidebar h2{
text-align:center;
margin-bottom:25px;
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
color:white;
}

/* MAIN CONTENT */
.main{
margin-left:230px;
padding:30px;
width:calc(100% - 230px);
max-width:1200px;
margin-right:auto;
}

/* HEADER */
.header{
display:flex;
justify-content:space-between;
align-items:center;
margin-bottom:25px;
}

.logout{
background:#ef4444;
padding:8px 14px;
color:white;
text-decoration:none;
border-radius:6px;
}

/* CARDS GRID */
.cards{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
gap:20px;
margin-bottom:25px;
}

/* CARD */
.card{
background:white;
padding:20px;
border-radius:12px;
box-shadow:0 4px 12px rgba(0,0,0,0.08);
display:flex;
flex-direction:column;
justify-content:center;
min-height:120px;
}

.card h3{
margin-bottom:10px;
color:#6b7280;
font-size:14px;
}

.card p{
font-size:22px;
font-weight:bold;
}

/* PROGRESS */
.progress{
background:#e5e7eb;
border-radius:20px;
overflow:hidden;
margin-top:10px;
}

.progress-bar{
height:18px;
color:white;
text-align:center;
font-size:12px;
line-height:18px;
}

.attendance{ background:#22c55e; }
.marks{ background:#3b82f6; }

/* GRID FOR CHARTS */
.grid{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(350px,1fr));
gap:20px;
align-items:stretch;
}

/* CHART BOX */
.chart-box{
background:white;
padding:20px;
border-radius:12px;
box-shadow:0 4px 12px rgba(0,0,0,0.08);
display:flex;
flex-direction:column;
justify-content:center;
}

/* BUTTON */
.btn{
margin:20px 0;
padding:10px 18px;
border:none;
background:#10b981;
color:white;
border-radius:6px;
cursor:pointer;
display:inline-block;
}

/* RESPONSIVE FIX */
@media(max-width:768px){

.sidebar{
width:200px;
}

.main{
margin-left:200px;
width:calc(100% - 200px);
padding:20px;
}

.header h2{
font-size:18px;
}

}

</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
<h2>Parent Panel</h2>
<a href="parent_dashboard.php">🏠 Dashboard</a>
<a href="parent_performance.php">📊 Performance</a>
<a href="parent_attendance.php">📅 Attendance</a>
<a href="student_info.php">👨‍🎓 Student Info</a>
<a href="Ngo.php">🚪 Logout</a>
</div>

<!-- MAIN -->
<div class="main">

<div class="header">
<h2>Welcome, <?php echo htmlspecialchars($student_name); ?> 👋</h2>
<a class="logout" href="Ngo.php">Logout</a>
</div>

<!-- CARDS -->
<div class="cards">

<div class="card">
<h3>🎓 Student Name</h3>
<p><?php echo htmlspecialchars($student_name); ?></p>
</div>

<div class="card">
<h3>🆔 Roll Number</h3>
<p><?php echo htmlspecialchars($rno); ?></p>
</div>

<div class="card">
<h3>📅 Attendance</h3>
<div class="progress">
<div class="progress-bar attendance" style="width:<?php echo $attendance; ?>%">
<?php echo $attendance; ?>%
</div>
</div>
</div>

<div class="card">
<h3>📊 Overall Marks</h3>
<div class="progress">
<div class="progress-bar marks" style="width:<?php echo $marks; ?>%">
<?php echo $marks; ?>%
</div>
</div>
</div>

</div>

<!-- DOWNLOAD -->
<button class="btn" onclick="window.print()">📥 Download Report</button>

<!-- CHARTS -->
<div class="grid">

<div class="chart-box">
<h3>Overall Performance</h3>
<canvas id="barChart"></canvas>
</div>

<div class="chart-box">
<h3>Subject-wise Marks</h3>
<canvas id="subjectChart"></canvas>
</div>

</div>

</div>

<script>

/* BAR CHART */
new Chart(document.getElementById('barChart'), {
type: 'bar',
data: {
labels: ['Attendance','Marks'],
datasets: [{
label: 'Performance',
data: [<?php echo $attendance; ?>, <?php echo $marks; ?>],
backgroundColor: ['#22c55e','#3b82f6']
}]
}
});

/* SUBJECT CHART */
new Chart(document.getElementById('subjectChart'), {
type: 'pie',
data: {
labels: <?php echo json_encode($subjects); ?>,
datasets: [{
data: <?php echo json_encode($marks_data); ?>,
backgroundColor: ['#f59e0b','#10b981','#3b82f6','#ef4444','#8b5cf6']
}]
}
});

</script>

</body>
</html>