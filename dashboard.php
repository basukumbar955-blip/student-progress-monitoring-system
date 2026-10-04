<?php
session_start();
include("db_connect.php");

if(!isset($_SESSION['student'])){
    header("Location: student_login.php");
    exit();
}

$rno = $_SESSION['student'];

$stmt = $conn->prepare("
    SELECT 
        sl.s_name,
        sl.course,
        sp.attendance,
        sp.marks
    FROM student_login sl
    LEFT JOIN student_progress sp ON sl.rno = sp.rno
    WHERE sl.rno = ?
    LIMIT 1
");

if(!$stmt){
    die("SQL Error: " . $conn->error);
}

$stmt->bind_param("s", $rno);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

$name = $data['s_name'] ?? 'Student';
$course = !empty($data['course']) ? $data['course'] : 'N/A';
$attendance = $data['attendance'] ?? 0;
$marks = $data['marks'] ?? 0;
$scholarship = "Not Applied";
?>

<!DOCTYPE html>
<html>
<head>

<title>Student Dashboard</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Segoe UI',sans-serif;
}

body{
display:flex;
background:#eef1ff;
}

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
transition:.3s;
font-size:15px;
}

.sidebar a:hover{
background:rgba(255,255,255,0.2);
padding-left:35px;
}

.sidebar i{
margin-right:10px;
}

.main{
margin-left:240px;
width:100%;
}

.navbar{
background:white;
padding:15px 30px;
display:flex;
justify-content:space-between;
align-items:center;
box-shadow:0 3px 12px rgba(0,0,0,0.08);
}

.logout{
background:#5f3df5;
color:white;
padding:8px 20px;
border-radius:25px;
text-decoration:none;
}

.dashboard{
padding:30px;
}

.profile{
background:white;
padding:20px;
border-radius:12px;
box-shadow:0 4px 12px rgba(0,0,0,0.08);
display:flex;
align-items:center;
gap:20px;
margin-bottom:30px;
}

.profile img{
width:70px;
border-radius:50%;
}

.cards{
display:flex;
flex-wrap:wrap;
gap:20px;
}

.card{
flex:1;
min-width:200px;
background:white;
padding:25px;
border-radius:12px;
box-shadow:0 5px 15px rgba(0,0,0,0.08);
text-align:center;
transition:0.3s;
}

.card:hover{
transform:translateY(-5px);
}

.card i{
font-size:28px;
color:#5f3df5;
margin-bottom:10px;
}

.progress-box{
margin-top:30px;
background:white;
padding:25px;
border-radius:12px;
box-shadow:0 5px 15px rgba(0,0,0,0.08);
}

.progress{
background:#ddd;
border-radius:20px;
overflow:hidden;
margin-bottom:20px;
}

.progress-bar{
height:12px;
background:linear-gradient(90deg,#5f3df5,#7a5cff);
}
</style>

</head>

<body>

<div class="sidebar">
<h2>🎓 Student Panel</h2>

<a href="#"><i class="fa fa-home"></i>Dashboard</a>
<a href="attendance.php"><i class="fa fa-calendar"></i>Attendance</a>
<a href="marks.php"><i class="fa fa-chart-bar"></i>Marks</a>
<a href="scholarship.php"><i class="fa fa-money-bill"></i>Scholarship</a>
<a href="subjects.php"><i class="fa fa-book"></i>Subjects</a>
<a href="Ngo.php"><i class="fa fa-sign-out-alt"></i>Logout</a>
</div>

<div class="main">

<div class="navbar">
<h3>Student Dashboard</h3>
<a href="Ngo.php" class="logout">Logout</a>
</div>

<div class="dashboard">

<h2 id="welcome"></h2>
<p>Track your academic progress and information.</p>

<br>

<div class="profile">

<img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png">

<div>
<b>Name:</b> <?php echo htmlspecialchars($name); ?> <br>
<b>Course:</b> <?php echo htmlspecialchars($course); ?> <br>
<b>Roll No:</b> <?php echo htmlspecialchars($rno); ?>
</div>

</div>

<div class="cards">

<div class="card">
<i class="fa fa-calendar-check"></i>
<h3>Attendance</h3>
<p><?php echo htmlspecialchars($attendance); ?>%</p>
</div>

<div class="card">
<i class="fa fa-chart-line"></i>
<h3>Marks</h3>
<p><?php echo htmlspecialchars($marks); ?>%</p>
</div>

<div class="card">
<i class="fa fa-coins"></i>
<h3>Scholarship</h3>
<p><?php echo htmlspecialchars($scholarship); ?></p>
</div>

<div class="card">
<i class="fa fa-comments"></i>
<h3>Messages</h3>
<p>Contact Mentor</p>
</div>

</div>

<div class="progress-box">

<h3>Academic Progress</h3>
<br>

<p>Attendance</p>
<div class="progress">
<div class="progress-bar" style="width:<?php echo htmlspecialchars($attendance); ?>%"></div>
</div>

<p>Semester Performance</p>
<div class="progress">
<div class="progress-bar" style="width:<?php echo htmlspecialchars($marks); ?>%"></div>
</div>

</div>

</div>

</div>

<script>
let hour = new Date().getHours();
let greeting;

if(hour < 12){
    greeting = "Good Morning";
}
else if(hour < 18){
    greeting = "Good Afternoon";
}
else{
    greeting = "Good Evening";
}

document.getElementById("welcome").innerText =
greeting + ", <?php echo htmlspecialchars($name); ?>";
</script>

</body>
</html>
