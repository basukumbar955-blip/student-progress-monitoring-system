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

/* CALCULATE ATTENDANCE % */
$stmt = $conn->prepare("
SELECT 
COUNT(*) as total,
SUM(status='Present') as present
FROM attendance 
WHERE rno=?
");
$stmt->bind_param("s",$rno);
$stmt->execute();
$res = $stmt->get_result();
$data = $res->fetch_assoc();

$total = $data['total'] ?? 0;
$present = $data['present'] ?? 0;

$attendance_percent = ($total > 0) ? round(($present/$total)*100) : 0;

/* FETCH ATTENDANCE RECORDS */
$records = [];
$stmt = $conn->prepare("SELECT * FROM attendance WHERE rno=? ORDER BY date ASC");
$stmt->bind_param("s",$rno);
$stmt->execute();
$res = $stmt->get_result();

while($row = $res->fetch_assoc()){
    $records[] = $row;
}

/* SIMPLE CHART DATA */
$chartDates = [];
$chartValues = [];

foreach($records as $row){
    $chartDates[] = date("d M", strtotime($row['date']));
    $chartValues[] = ($row['status'] == "Present") ? 1 : 0;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Attendance</title>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body{
    margin:0;
    font-family:'Segoe UI';
    background:#f4f6fb;
    display:flex;
}

.sidebar{
    width:190px;
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

.main{
    margin-left:210px;
    padding:30px;
    width:calc(100% - 210px);
    max-width:1200px;
}

.card{
    background:white;
    padding:20px;
    border-radius:12px;
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
    margin-bottom:20px;
}

.progress{
    background:#e5e7eb;
    border-radius:20px;
    overflow:hidden;
    margin-top:10px;
}

.progress-bar{
    height:20px;
    color:white;
    text-align:center;
    line-height:20px;
    font-size:13px;
}

.good{
    background:#22c55e;
}

.medium{
    background:#f59e0b;
}

.low{
    background:#ef4444;
}

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

.present{
    color:green;
    font-weight:bold;
}

.absent{
    color:red;
    font-weight:bold;
}

.chart-box{
    height:300px;
}
</style>
</head>

<body>

<div class="sidebar">
    <h2>Parent Panel</h2>
    <a href="parent_dashboard.php">🏠 Dashboard</a>
    <a href="parent_performance.php">📊 Performance</a>
    <a href="attendance.php">📅 Attendance</a>
    <a href="student_info.php">👨‍🎓 Student Info</a>
    <a href="Ngo.php">🚪 Logout</a>
</div>

<div class="main">

<h2>📅 Attendance Overview</h2>

<div class="card">
    <h3>Attendance Percentage</h3>

    <?php
    $class = "good";

    if($attendance_percent < 75){
        $class = "low";
    }elseif($attendance_percent < 85){
        $class = "medium";
    }
    ?>

    <div class="progress">
        <div class="progress-bar <?php echo $class; ?>" style="width:<?php echo $attendance_percent; ?>%">
            <?php echo $attendance_percent; ?>%
        </div>
    </div>

    <p style="margin-top:10px;">
        Present: <?php echo $present; ?> / <?php echo $total; ?> days
    </p>
</div>

<div class="card">
    <h3>Simple Attendance Line Chart</h3>

    <div class="chart-box">
        <canvas id="attendanceChart"></canvas>
    </div>
</div>

<div class="card">
    <h3>Attendance History</h3>

    <table>
        <tr>
            <th>Date</th>
            <th>Status</th>
        </tr>

        <?php
        if(count($records) > 0){
            foreach($records as $row){

                $statusClass = ($row['status']=="Present") ? "present" : "absent";

                echo "<tr>
                    <td>".$row['date']."</td>
                    <td class='$statusClass'>".$row['status']."</td>
                </tr>";
            }
        }else{
            echo "<tr><td colspan='2'>No records found</td></tr>";
        }
        ?>
    </table>
</div>

</div>

<script>
const dates = <?php echo json_encode($chartDates); ?>;
const values = <?php echo json_encode($chartValues); ?>;

const ctx = document.getElementById('attendanceChart');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: dates,
        datasets: [{
            label: 'Attendance',
            data: values,
            borderWidth: 3,
            tension: 0.3,
            pointRadius: 5,
            fill: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,

        scales: {
            y: {
                min: 0,
                max: 1,
                ticks: {
                    stepSize: 1,
                    callback: function(value){
                        if(value == 1){
                            return "Present";
                        }
                        if(value == 0){
                            return "Absent";
                        }
                    }
                }
            },

            x: {
                title: {
                    display: true,
                    text: "Date"
                }
            }
        }
    }
});
</script>

</body>
</html>