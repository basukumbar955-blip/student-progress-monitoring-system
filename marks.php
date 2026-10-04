<?php
session_start();
include("db_connect.php");

/* SESSION SECURITY */
if(!isset($_SESSION['student'])){
    header("Location: student_login.php");
    exit();
}

session_regenerate_id(true);

$rno = $_SESSION['student'];

/* FETCH SUBJECTS */
$stmt = $conn->prepare("SELECT * FROM student_subjects WHERE rno=?");
$stmt->bind_param("s",$rno);
$stmt->execute();
$result = $stmt->get_result();

$totalMarks = 0;
$totalSubjects = 0;
$records = [];
?>

<!DOCTYPE html>
<html>
<head>
<title>Marks Page</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

body{
font-family:'Segoe UI',sans-serif;
background:#eef1ff;
margin:0;
}

/* NAVBAR */

.navbar{
background:white;
padding:15px 30px;
display:flex;
justify-content:space-between;
box-shadow:0 3px 12px rgba(0,0,0,0.08);
}

.back{
text-decoration:none;
background:#5f3df5;
color:white;
padding:8px 20px;
border-radius:20px;
}

/* CONTAINER */

.container{
padding:30px;
}

/* TABLE */

table{
width:100%;
border-collapse:collapse;
background:white;
border-radius:10px;
overflow:hidden;
box-shadow:0 5px 15px rgba(0,0,0,0.08);
}

th,td{
padding:15px;
text-align:center;
}

th{
background:#5f3df5;
color:white;
}

tr:nth-child(even){
background:#f4f6ff;
}

/* CARD */

.summary{
margin-bottom:20px;
background:white;
padding:20px;
border-radius:10px;
box-shadow:0 5px 15px rgba(0,0,0,0.08);
text-align:center;
}

/* PERFORMANCE COLORS */

.good{
color:green;
font-weight:bold;
}

.avg{
color:orange;
font-weight:bold;
}

.poor{
color:red;
font-weight:bold;
}

</style>
</head>

<body>

<div class="navbar">
<h2>📊 Marks</h2>
<a href="dashboard.php" class="back">⬅ Back</a>
</div>

<div class="container">

<div class="summary">

<?php
while($row = $result->fetch_assoc()){
    $totalSubjects++;
    $totalMarks += $row['marks'];
    $records[] = $row;
}

$average = ($totalSubjects > 0) ? round($totalMarks / $totalSubjects) : 0;

/* PERFORMANCE LABEL */
if($average >= 75){
    $performance = "<span class='good'>Excellent</span>";
}
elseif($average >= 50){
    $performance = "<span class='avg'>Average</span>";
}
else{
    $performance = "<span class='poor'>Needs Improvement</span>";
}

echo "<h3>Total Subjects: $totalSubjects</h3>";
echo "<h3>Total Marks: $totalMarks</h3>";
echo "<h3>Average: $average%</h3>";
echo "<h3>Performance: $performance</h3>";
?>

</div>

<table>

<tr>
<th>Subject</th>
<th>Marks (%)</th>
</tr>

<?php
if(!empty($records)){
    foreach($records as $row){
        echo "<tr>
                <td>".htmlspecialchars($row['subject_name'])."</td>
                <td>".htmlspecialchars($row['marks'])."%</td>
              </tr>";
    }
}else{
    echo "<tr><td colspan='2'>No Marks Found</td></tr>";
}
?>

</table>

</div>

</body>
</html>