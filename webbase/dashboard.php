<?php
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/../backend/config.php';

// Fetch login events
$stmt = $pdo->query('SELECT le.id, u.email, le.login_time FROM login_events le JOIN users u ON le.user_id = u.id ORDER BY le.login_time DESC');
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for chart (logins per day, last 7 days)
$chartLabels = [];
$chartData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = $date;
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM login_events WHERE DATE(login_time)=?');
    $countStmt->execute([$date]);
    $chartData[] = (int)$countStmt->fetchColumn();
}
?>
<h2 class="mb-4">Login Events</h2>
<canvas id="loginChart" height="100"></canvas>
<script>
const ctx = document.getElementById('loginChart');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
            label: 'Logins per day',
            data: <?= json_encode($chartData) ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.6)'
        }]
    },
    options: {scales:{y:{beginAtZero:true}}}
});
</script>

<table id="eventsTable" class="table table-striped mt-4">
  <thead>
    <tr>
      <th>ID</th>
      <th>User Email</th>
      <th>Login Time</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($events as $event): ?>
      <tr>
        <td><?= htmlspecialchars($event['id']) ?></td>
        <td><?= htmlspecialchars($event['email']) ?></td>
        <td><?= htmlspecialchars($event['login_time']) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function(){
    $('#eventsTable').DataTable();
});
</script>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
