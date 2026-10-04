<?php
session_start();
include("db_connect.php");

/* STUDENT LOGIN */

if(isset($_POST['login'])){

$rno = trim($_POST['rno']);
$s_name = trim($_POST['s_name']);
$s_email = filter_var($_POST['s_email'], FILTER_SANITIZE_EMAIL);
$u_password = $_POST['u_password'];

$stmt = $conn->prepare("SELECT * FROM student_login WHERE rno=? AND s_name=? AND s_email=? LIMIT 1");
$stmt->bind_param("sss", $rno, $s_name, $s_email);
$stmt->execute();
$result = $stmt->get_result();

if(($row = $result->fetch_assoc()) && password_verify($u_password, $row['u_password'])){

$_SESSION['user'] = $rno;

header("Location: Ngo.php");
exit();

}else{

echo "<script>alert('Invalid Student Login Details');</script>";

}

}


/* ADMIN LOGIN */

if(isset($_POST['admin_login'])){

$admin_user = trim($_POST['admin_user']);
$admin_pass = $_POST['admin_pass'];

$stmt = $conn->prepare("SELECT * FROM admin_login WHERE admin_user=? LIMIT 1");
$stmt->bind_param("s", $admin_user);
$stmt->execute();
$result = $stmt->get_result();

if(($row = $result->fetch_assoc()) && (password_verify($admin_pass, $row['admin_pass']) || $admin_pass === $row['admin_pass'])){

$_SESSION['admin'] = $admin_user;

header("Location: admin_dashboard.php");
exit();

}else{

echo "<script>alert('Invalid Admin Login');</script>";

}

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Progress Monitoring System</title>

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Segoe UI',sans-serif;
}

body{
background:#f1f5f9;
}

/* NAVBAR */

.navbar{
display:flex;
justify-content:space-between;
align-items:center;
padding:20px 70px;
background:rgba(255,255,255,0.9);
backdrop-filter:blur(10px);
box-shadow:0 4px 15px rgba(0,0,0,0.08);
position:sticky;
top:0;
}

.theme-toggle{
background:#0f172a;
color:white;
border:none;
padding:8px 14px;
border-radius:20px;
cursor:pointer;
font-weight:600;
transition:0.3s;
}

.theme-toggle:hover{
background:#2563eb;
}

.logo{
font-size:24px;
font-weight:bold;
color:#2563eb;
}

.menu a{
text-decoration:none;
color:#334155;
margin:0 15px;
font-weight:500;
transition:0.3s;
}

.menu a:hover{
color:#2563eb;
}

.login-btn{
background:#2563eb;
color:white;
padding:8px 18px;
border-radius:20px;
}

/* HERO */

.h{
display:flex;
justify-content:space-between;
align-items:center;
padding:100px 80px;
background:linear-gradient(120deg,#2563eb,#6366f1);
color:white;
}

.h-text{
max-width:550px;
}

.h-text h1{
font-size:50px;
line-height:1.2;
}

.h-text span{
color:#fde68a;
}

.h-text p{
margin-top:18px;
font-size:17px;
line-height:1.6;
color:#e2e8f0;
}

/* BUTTONS */

.buttons{
margin-top:30px;
}

.buttons button{
padding:13px 24px;
border:none;
border-radius:30px;
font-size:16px;
cursor:pointer;
margin-right:12px;
transition:0.3s;
}

.parent-btn{
background:linear-gradient(135deg,#22c55e,#16a34a);
color:white;
padding:13px 24px;
border:none;
border-radius:30px;
font-size:16px;
cursor:pointer;
margin-right:12px;
transition:0.3s;
box-shadow:0 8px 18px rgba(0,0,0,0.2);
}

.parent-btn:hover{
transform:scale(1.08);
box-shadow:0 10px 25px rgba(0,0,0,0.3);
}

.login{
background:#f59e0b;
color:white;
}

.login:hover{
transform:scale(1.05);
}

.admin{
background:#ef4444;
color:white;
}

.admin:hover{
transform:scale(1.05);
}

/* MODULE SECTION */

.modules-section{
text-align:center;
padding:80px 60px;
}

.modules-section h2{
font-size:32px;
color:#1e293b;
margin-bottom:50px;
}

/* CARDS */

.cards{
display:flex;
justify-content:center;
flex-wrap:wrap;
gap:30px;
}

.card{
background:white;
width:250px;
padding:30px;
border-radius:16px;
box-shadow:0 10px 25px rgba(0,0,0,0.08);
transition:0.3s;
}

.card:hover{
transform:translateY(-10px);
box-shadow:0 15px 35px rgba(0,0,0,0.15);
}

.card-icon{
font-size:40px;
margin-bottom:15px;
}

.card h3{
color:#2563eb;
margin-bottom:10px;
}

.card p{
color:#64748b;
font-size:15px;
}

/* FOOTER */

footer{
margin-top:70px;
background:#1e293b;
color:white;
text-align:center;
padding:20px;
font-size:14px;
}

body.dark-mode{
background:#0f172a;
color:#e2e8f0;
}

body.dark-mode .navbar{
background:rgba(15,23,42,0.95);
box-shadow:0 4px 15px rgba(0,0,0,0.35);
}

body.dark-mode .logo,
body.dark-mode .menu a:hover,
body.dark-mode .card h3{
color:#60a5fa;
}

body.dark-mode .menu a{
color:#e2e8f0;
}

body.dark-mode .theme-toggle{
background:#f8fafc;
color:#0f172a;
}

body.dark-mode .h{
background:linear-gradient(120deg,#0f172a,#1e3a8a);
}

body.dark-mode .modules-section{
background:#0f172a;
}

body.dark-mode .modules-section h2{
color:#f8fafc;
}

body.dark-mode .card{
background:#1e293b;
box-shadow:0 10px 25px rgba(0,0,0,0.35);
}

body.dark-mode .card p{
color:#cbd5e1;
}

body.dark-mode footer{
background:#020617;
color:#e2e8f0;
}

</style>
</head>

<body>

<div class="navbar">

<div class="logo">DHARWAD</div>

<div class="menu">

<a href="#home">Home</a>
<a href="About_Us.php">About Us</a>
<a href="project_overview.php">Project Overview</a>
<a href="impact.php">Impact</a>
<a href="volunteer.php">Volunteer</a>

</div>

<button type="button" class="theme-toggle" id="themeToggle" onclick="toggleDarkMode()">Dark Mode</button>

</div>
<section class="h">

<div id="home" class="h-text">

<h1>Student Progress <span>Monitoring System</span></h1>

<p>
A modern digital platform designed for Samarthanam Trust to
track student attendance, academic performance, scholarships
and mentor communication efficiently.
</p>

<div class="buttons">

<a href="login.php">
<button class="login">🎓 Student Login</button>
</a>

<a href="parent_login.php">
<button class="parent-btn">👨‍👩‍👧 Parent Login</button>
</a>

<a href="admin_login.php">
<button class="admin">🛠 Admin Login</button>
</a>


</div>

</div>

</section>

<section class="modules-section" id="modules">

<h2>System Modules</h2>

<div class="cards">

<div class="card">
<div class="card-icon">🎓</div>
<h3>Student</h3>
<p>View attendance, academic progress and scholarship status.</p>
</div>

<div class="card">
<div class="card-icon">⚙️</div>
<h3>Admin</h3>
<p>Manage student records, attendance and reports.</p>
</div>

<div class="card">
<div class="card-icon">👨‍🏫</div>
<h3>Mentor</h3>
<p>Monitor student progress and communicate with students.</p>
</div>

<div class="card">
<div class="card-icon">📊</div>
<h3>Reports</h3>
<p>Generate student performance reports and analytics.</p>
</div>

</div>

</section>


<footer>
<p>© 2026 Student Progress Monitoring System | BCA Final Year Project</p>
</footer>

<script>
function applyTheme(isDark){
document.body.classList.toggle("dark-mode", isDark);
document.getElementById("themeToggle").innerText = isDark ? "Light Mode" : "Dark Mode";
}

function toggleDarkMode(){
let isDark = !document.body.classList.contains("dark-mode");
localStorage.setItem("spmsDarkMode", isDark ? "enabled" : "disabled");
applyTheme(isDark);
}

document.addEventListener("DOMContentLoaded", function(){
applyTheme(localStorage.getItem("spmsDarkMode") === "enabled");
});
</script>

</body>
</html>
