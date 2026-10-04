<?php
session_start();
include("db_connect.php");

if(!isset($_SESSION['admin'])){
    header("Location: admin_login.php");
    exit();
}

if(!isset($conn) || $conn->connect_error){
    die("Database connection failed");
}

function getTotal($conn, $table){
    $sql = "SELECT COUNT(*) AS total FROM $table";
    $stmt = $conn->prepare($sql);

    if(!$stmt){
        die("Query Error: " . $conn->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    return $data['total'] ?? 0;
}

$total_students = getTotal($conn, "student_login");
$total_mentors  = getTotal($conn, "mentor_login");
$total_parents  = getTotal($conn, "parent_login");
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard</title>

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI', sans-serif;
}

body{
    display:flex;
    background:#f1f5f9;
}

.sidebar{
    width:230px;
    height:100vh;
    background:#1e293b;
    color:white;
    padding-top:20px;
    position:fixed;
}

.sidebar h2{
    text-align:center;
    margin-bottom:30px;
    color:#38bdf8;
}

.sidebar a{
    display:block;
    padding:15px 25px;
    text-decoration:none;
    color:#cbd5e1;
}

.sidebar a:hover{
    background:#334155;
    color:white;
}

.main{
    margin-left:230px;
    width:100%;
}

.header{
    background:white;
    padding:20px 30px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    box-shadow:0 3px 10px rgba(0,0,0,0.05);
}

.header h1{
    font-size:24px;
    color:#1e293b;
}

.logout{
    background:#ef4444;
    color:white;
    padding:8px 15px;
    border-radius:6px;
    text-decoration:none;
}

.logout:hover{
    background:#dc2626;
}

.dashboard{
    padding:30px;
}

.cards{
    display:flex;
    justify-content:center;
    gap:30px;
    flex-wrap:wrap;
}

.card{
    background:white;
    width:250px;
    padding:25px;
    border-radius:12px;
    box-shadow:0 8px 20px rgba(0,0,0,0.08);
}

.card h3{
    font-size:16px;
    color:#64748b;
}

.card h1{
    margin-top:10px;
    font-size:28px;
    color:#2563eb;
}
</style>
</head>

<body>

<div class="sidebar">
    <h2>Admin Panel</h2>

    <a href="admin_dashboard.php">🏠 Dashboard</a>
    <a href="admin_manage_students.php">🎓 Manage Students</a>
    <a href="admin_manage_mentor.php">👨‍🏫 Manage Mentors</a>
    <a href="admin_manage_parents.php">👨‍👩‍👧 Manage Parents</a>
    <a href="admin_reports.php">📊 Reports</a>
    <a href="admin_settings.php">⚙ Settings</a>
</div>

<div class="main">

    <div class="header">
        <h1>Student Progress Monitoring System</h1>
        <a href="Ngo.php" class="logout">Logout</a>
    </div>

    <div class="dashboard">

        <div class="cards">

            <div class="card">
                <h3>Total Students</h3>
                <h1><?php echo htmlspecialchars($total_students); ?></h1>
            </div>

            <div class="card">
                <h3>Mentors</h3>
                <h1><?php echo htmlspecialchars($total_mentors); ?></h1>
            </div>

            <div class="card">
                <h3>Parents</h3>
                <h1><?php echo htmlspecialchars($total_parents); ?></h1>
            </div>

        </div>

    </div>

</div>

</body>
</html>