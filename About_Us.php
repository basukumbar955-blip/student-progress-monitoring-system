<!DOCTYPE html>
<html>
<head>
<title>About Us - SPMS</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

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

/* HEADER */

header{
background:#2563eb;
color:white;
padding:15px 40px;
display:flex;
justify-content:space-between;
align-items:center;
}

header h1{
font-size:24px;
}

nav a{
color:white;
text-decoration:none;
margin-left:20px;
font-weight:500;
}

nav a:hover{
text-decoration:underline;
}

/* HERO SECTION */

.hero{
height:300px;
display:flex;
justify-content:center;
align-items:center;
text-align:center;
color:white;

background:
linear-gradient(rgba(0,0,0,0.6),rgba(0,0,0,0.6)),
url("school.jpg");

background-size:cover;
background-position:center;
}

.hero h2{
font-size:40px;
}

/* CONTENT */

.container{
width:80%;
margin:40px auto;
background:white;
padding:40px;
border-radius:10px;
box-shadow:0 10px 25px rgba(0,0,0,0.1);
}

.container h3{
color:#2563eb;
margin-bottom:10px;
}

.container p{
margin-bottom:20px;
line-height:1.6;
color:#334155;
}

/* FEATURES */

.features{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
gap:20px;
margin-top:30px;
}

.feature-box{
background:#f8fafc;
padding:20px;
border-radius:10px;
text-align:center;
transition:0.3s;
}

.feature-box:hover{
transform:translateY(-5px);
box-shadow:0 8px 20px rgba(0,0,0,0.1);
}

.feature-box i{
font-size:30px;
color:#2563eb;
margin-bottom:10px;
}

/* FOOTER */

footer{
text-align:center;
padding:15px;
background:#1e293b;
color:white;
margin-top:40px;
}

</style>

</head>

<body>

<header>
<h1>Dharwad</h1>

<nav>
<a href="Ngo.php">Home</a>
<a href="parent_login.php">Parent Login</a>
<a href="admin_login.php">Admin Login</a>
</nav>
</header>

<div class="hero">
<h2>About Student Progress Monitoring System</h2>
</div>

<div class="container">

<h3>About the Project</h3>
<p>
The Student Progress Monitoring System (SPMS) is a web-based application designed to track and manage student academic performance. It allows administrators to store and update student progress records while enabling parents to securely view their child's academic performance.
</p>

<h3>Purpose of the System</h3>
<p>
The main goal of this system is to improve communication between schools and parents. By providing real-time access to student progress, parents can stay informed about their child's academic development and support their learning more effectively.
</p>

<h3>Key Features</h3>

<div class="features">

<div class="feature-box">
<i class="fa fa-user-graduate"></i>
<h4>Student Records</h4>
<p>Store and manage student information easily.</p>
</div>

<div class="feature-box">
<i class="fa fa-chart-line"></i>
<h4>Progress Tracking</h4>
<p>Monitor academic performance and progress.</p>
</div>

<div class="feature-box">
<i class="fa fa-users"></i>
<h4>Parent Access</h4>
<p>Parents can log in and view student results.</p>
</div>

<div class="feature-box">
<i class="fa fa-shield-halved"></i>
<h4>Secure System</h4>
<p>Protected login for admin and parents.</p>
</div>

</div>

</div>

<footer>
© 2026 Student Progress Monitoring System | BCA Project
</footer>

</body>
</html>