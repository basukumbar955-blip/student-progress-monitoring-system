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

/* FETCH STUDENT DATA */
$stmt = $conn->prepare("
SELECT * FROM student_progress
WHERE rno=?
");

$stmt->bind_param("s", $rno);

$stmt->execute();

$result = $stmt->get_result();

$data = $result->fetch_assoc();

$student_name = $data['student_name'] ?? "Student";
$attendance   = $data['attendance'] ?? 0;
$overall_marks = $data['marks'] ?? 0;

/* SUBJECT DATA */

$subjects = [];
$marks_data = [];

$total = 0;
$count = 0;

$stmt = $conn->prepare("
SELECT subject_name, marks
FROM student_subjects
WHERE rno=?
");

$stmt->bind_param("s",$rno);

$stmt->execute();

$res = $stmt->get_result();

while($row = $res->fetch_assoc()){

    $subjects[]   = $row['subject_name'];

    $marks_data[] = $row['marks'];

    $total += $row['marks'];

    $count++;
}

/* CALCULATE AVERAGE */

$average = $count > 0
? round($total / $count,2)
: 0;

/* GRADE */

if($average >= 90){

    $grade = "A+";

}elseif($average >= 75){

    $grade = "A";

}elseif($average >= 60){

    $grade = "B";

}elseif($average >= 50){

    $grade = "C";

}else{

    $grade = "D";
}

/* REMARK */

if($average >= 75){

    $remark = "Excellent Performance 🎉";

}elseif($average >= 60){

    $remark = "Good, Keep Improving 👍";

}else{

    $remark = "Needs Improvement ⚠️";
}

?>

<!DOCTYPE html>
<html>

<head>

<title>Performance Page</title>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Segoe UI';
}

body{

background:#f4f6fb;
}

/* BACK BUTTON */

.back-btn{

position:fixed;

top:20px;
left:20px;

background:#2563eb;

color:white;

padding:10px 18px;

text-decoration:none;

border-radius:8px;

font-size:14px;

font-weight:bold;

box-shadow:0 4px 10px rgba(0,0,0,0.2);

transition:0.3s;
}

.back-btn:hover{

background:#1d4ed8;

transform:scale(1.05);
}

/* CONTAINER */

.container{

padding:30px;

max-width:1100px;

margin:auto;

margin-top:70px;
}

h2{

margin-bottom:20px;

color:#111827;
}

/* CARDS */

.cards{

display:grid;

grid-template-columns:
repeat(auto-fit,minmax(220px,1fr));

gap:20px;
}

.card{

background:white;

padding:20px;

border-radius:12px;

box-shadow:0 4px 10px rgba(0,0,0,0.08);

transition:0.3s;
}

.card:hover{

transform:translateY(-5px);
}

.card h3{

font-size:14px;

color:#6b7280;

margin-bottom:10px;
}

.card p{

font-size:24px;

font-weight:bold;

color:#111827;
}

/* TABLE */

table{

width:100%;

margin-top:25px;

border-collapse:collapse;

background:white;

border-radius:10px;

overflow:hidden;

box-shadow:0 4px 10px rgba(0,0,0,0.08);
}

th,td{

padding:14px;

text-align:center;

border-bottom:1px solid #ddd;
}

th{

background:#111827;

color:white;
}

tr:hover{

background:#f9fafb;
}

/* CHART */

.chart-box{

margin-top:30px;

background:white;

padding:20px;

border-radius:12px;

box-shadow:0 4px 10px rgba(0,0,0,0.08);
}

.chart-box h3{

margin-bottom:15px;
}

/* MOBILE */

@media(max-width:768px){

.container{
padding:15px;
}

.back-btn{
padding:8px 14px;
font-size:13px;
}

}

</style>

</head>

<body>

<!-- BACK BUTTON -->

<a href="parent_dashboard.php" class="back-btn">
⬅ Back
</a>

<div class="container">

<h2>
📊 Performance Report -
<?php echo htmlspecialchars($student_name); ?>
</h2>

<!-- CARDS -->

<div class="cards">

<div class="card">
<h3>Overall Marks</h3>
<p><?php echo $overall_marks; ?>%</p>
</div>

<div class="card">
<h3>Average Marks</h3>
<p><?php echo $average; ?>%</p>
</div>

<div class="card">
<h3>Grade</h3>
<p><?php echo $grade; ?></p>
</div>

<div class="card">
<h3>Attendance</h3>
<p><?php echo $attendance; ?>%</p>
</div>

</div>

<!-- REMARK -->

<div class="card" style="margin-top:20px;">

<h3>Teacher Remark</h3>

<p><?php echo $remark; ?></p>

</div>

<!-- SUBJECT TABLE -->

<table>

<tr>
<th>Subject</th>
<th>Marks</th>
<th>Status</th>
</tr>

<?php

for($i=0; $i<count($subjects); $i++){

$status =
($marks_data[$i] >= 50)
? "Pass ✅"
: "Fail ❌";

echo "

<tr>

<td>{$subjects[$i]}</td>

<td>{$marks_data[$i]}</td>

<td>$status</td>

</tr>

";
}
?>

</table>

<!-- CHART -->

<div class="chart-box">

<h3>Subject Performance</h3>

<canvas id="lineChart"></canvas>

</div>

</div>

<script>

new Chart(
document.getElementById('lineChart'),

{

type: 'line',

data: {

labels:
<?php echo json_encode($subjects); ?>,

datasets: [{

label: 'Marks',

data:
<?php echo json_encode($marks_data); ?>,

borderColor: '#3b82f6',

backgroundColor: 'rgba(59,130,246,0.2)',

fill: true,

tension: 0.3

}]
},

options: {

responsive:true,

plugins:{
legend:{
display:true
}
},

scales:{

y:{

beginAtZero:true,

max:100

}
}

}

});

</script>

</body>
</html>