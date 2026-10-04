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

/* FETCH ATTENDANCE RECORDS */
$stmt = $conn->prepare("SELECT date, status FROM attendance WHERE rno=? ORDER BY date DESC");
$stmt->bind_param("s", $rno);
$stmt->execute();
$result = $stmt->get_result();

$total = 0;
$present = 0;
?>

<!DOCTYPE html>
<html>
<head>
<title>Attendance Page</title>

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

/* STATUS */

.present{
color:green;
font-weight:bold;
}

.absent{
color:red;
font-weight:bold;
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

</style>
</head>

<body>

<div class="navbar">
<h2>📅 Attendance</h2>
<a href="dashboard.php" class="back">⬅ Back</a>
</div>

<div class="container">

<div class="summary">

<?php
while($row = $result->fetch_assoc()){
    $total++;
    if($row['status'] == "Present"){
        $present++;
    }
    $records[] = $row;
}

$percentage = ($total > 0) ? round(($present/$total)*100) : 0;

echo "<h3>Total Classes: $total</h3>";
echo "<h3>Present: $present</h3>";
echo "<h3>Attendance: $percentage%</h3>";
?>

</div>

<table>

<tr>
<th>Date</th>
<th>Status</th>
</tr>

<?php
if(!empty($records)){
    foreach($records as $row){
        $statusClass = ($row['status'] == "Present") ? "present" : "absent";

        echo "<tr>
                <td>".htmlspecialchars($row['date'])."</td>
                <td class='$statusClass'>".htmlspecialchars($row['status'])."</td>
              </tr>";
    }
}else{
    echo "<tr><td colspan='2'>No Attendance Records Found</td></tr>";
}
?>

</table>

</div>

</body>
</html>