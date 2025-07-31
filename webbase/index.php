<?php
require_once __DIR__ . '/../backend/config.php';
$stmt = $pdo->query('SELECT le.id, u.email, le.login_time FROM login_events le JOIN users u ON le.user_id = u.id ORDER BY le.login_time DESC');
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login Events Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-4">
    <h1 class="mb-4">Recent Login Events</h1>
    <table class="table table-striped">
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
  </div>
</body>
</html>
