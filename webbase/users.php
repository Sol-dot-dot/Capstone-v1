<?php
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/../backend/config.php';

$stmt = $pdo->query('SELECT id, email, created_at FROM users ORDER BY created_at DESC');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<h2 class="mb-4">Users</h2>
<a href="user_form.php" class="btn btn-success mb-3">Add User</a>
<table id="usersTable" class="table table-striped">
  <thead>
    <tr>
      <th>ID</th>
      <th>Email</th>
      <th>Created At</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><?= htmlspecialchars($u['id']) ?></td>
        <td><?= htmlspecialchars($u['email']) ?></td>
        <td><?= htmlspecialchars($u['created_at']) ?></td>
        <td>
          <a href="user_form.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
          <a href="delete_user.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete user?')">Delete</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(function(){ $('#usersTable').DataTable(); });
</script>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
