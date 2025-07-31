<?php
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/../backend/config.php';

$id = $_GET['id'] ?? null;
$email = '';
if ($id) {
    $stmt = $pdo->prepare('SELECT email FROM users WHERE id=?');
    $stmt->execute([$id]);
    $email = $stmt->fetchColumn();
    if (!$email) {
        echo '<div class="alert alert-danger">User not found</div>';
        require_once __DIR__ . '/partials/footer.php';
        exit;
    }
}
?>
<h2 class="mb-4"><?= $id ? 'Edit User' : 'Add User' ?></h2>
<form action="save_user.php" method="post">
  <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
  <div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label">Password <?= $id ? '(leave blank to keep unchanged)' : '' ?></label>
    <input type="password" name="password" class="form-control" <?= $id ? '' : 'required' ?>>
  </div>
  <button class="btn btn-primary" type="submit">Save</button>
  <a href="users.php" class="btn btn-secondary">Cancel</a>
</form>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
