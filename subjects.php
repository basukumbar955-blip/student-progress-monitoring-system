<?php
session_start();
include("db_connect.php");

/* SECURITY */
if(!isset($_SESSION['student'])){
    header("Location: login.php");
    exit();
}

session_regenerate_id(true);

$rno = $_SESSION['student'];

/* FETCH SUBJECTS */
$stmt = $conn->prepare("SELECT * FROM student_subjects WHERE rno=?");
$stmt->bind_param("s",$rno);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>Subjects</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

body{
margin:0;
font-family:'Segoe UI',sans-serif;
display:flex;
background:#eef1ff;
}

/* SIDEBAR SAME AS DASHBOARD */

.sidebar{
width:240px;
height:100vh;
background:linear-gradient(180deg,#5f3df5,#3a1fb0);
color:white;
padding-top:20px;
position:fixed;
}

.sidebar h2{
text-align:center;
margin-bottom:40px;
}

.sidebar a{
display:block;
padding:15px 25px;
color:white;
text-decoration:none;
}

.sidebar a:hover{
background:rgba(255,255,255,0.2);
}

.main{
margin-left:240px;
width:100%;
}

/* NAVBAR */

.navbar{
background:white;
padding:15px 30px;
display:flex;
justify-content:space-between;
box-shadow:0 3px 12px rgba(0,0,0,0.08);
}

.dashboard{
padding:30px;
}

/* TABLE */

table{
width:100%;
border-collapse:collapse;
background:white;
border-radius:12px;
overflow:hidden;
box-shadow:0 5px 15px rgba(0,0,0,0.08);
}

th, td{
padding:15px;
text-align:center;
}

th{
background:#5f3df5;
color:white;
}

tr:nth-child(even){
background:#f5f6ff;
}

.progress{
background:#ddd;
border-radius:20px;
overflow:hidden;
height:10px;
}

.progress-bar{
height:10px;
background:#5f3df5;
}

</style>

</head>

<body>

<!-- SIDEBAR -->

<div class="sidebar">

<h2>🎓 Student Panel</h2>

<a href="dashboard.php"><i class="fa fa-home"></i>Dashboard</a>
<a href="attendance.php"><i class="fa fa-calendar"></i>Attendance</a>
<a href="marks.php"><i class="fa fa-chart-bar"></i>Marks</a>
<a href="scholarship.php"><i class="fa fa-money-bill"></i>Scholarship</a>
<a href="#"><i class="fa fa-book"></i>Subjects</a>
<a href="Ngo.php"><i class="fa fa-sign-out-alt"></i>Logout</a>

</div>

<!-- MAIN -->

<div class="main">

<div class="navbar">
<h3>Subjects</h3>
<a href="Ngo.php">Logout</a>
</div>

<div class="dashboard">

<h2>Your Subjects</h2>
<br>

<table>

<tr>
<th>Subject</th>
<th>Faculty</th>
<th>Attendance</th>
<th>Marks</th>
</tr>

<?php
if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
?>

<tr>
<td><?php echo htmlspecialchars($row['subject_name']); ?></td>
<td><?php echo htmlspecialchars($row['faculty']); ?></td>

<td>
<?php echo $row['attendance']; ?>%
<div class="progress">
<div class="progress-bar" style="width:<?php echo $row['attendance']; ?>%"></div>
</div>
</td>

<td>
<?php echo $row['marks']; ?>%
<div class="progress">
<div class="progress-bar" style="width:<?php echo $row['marks']; ?>%"></div>
</div>
</td>

</tr>

<?php
    }
}else{
    echo "<tr><td colspan='4'>No subjects found</td></tr>";
}
?>

</table>

</div>

</div>

</body>
</html>