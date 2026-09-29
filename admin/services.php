<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$pdo = db();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Session expired. Refresh and try again.';
    } else {
        try {
            $posted = $_POST['service'] ?? null;
            if (!is_array($posted)) throw new RuntimeException('Service form data is invalid.');
            $pdo->beginTransaction();
            $save = $pdo->prepare('UPDATE services SET title=?,description=?,is_active=?,sort_order=? WHERE id=?');
            foreach ($posted as $id => $fields) {
                if (!ctype_digit((string)$id) || !is_array($fields)) continue;
                $title = request_string($fields, 'title');
                $description = request_string($fields, 'description');
                if ($title === '' || text_length($title) > 120 || text_length($description) > 500) {
                    throw new RuntimeException('Service titles and descriptions must be completed and within length limits.');
                }
                $sortOrder = filter_var($fields['sort_order'] ?? 0, FILTER_VALIDATE_INT);
                if ($sortOrder === false) $sortOrder = 0;
                $save->execute([$title, $description, isset($fields['active']) ? 1 : 0, max(0, min(99, $sortOrder)), (int)$id]);
            }
            $pdo->commit();
            admin_flash('Services updated.');
            header('Location: /admin/services.php');
            exit;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = $exception instanceof RuntimeException ? $exception->getMessage() : 'Unable to update services.';
        }
    }
}
$services = $pdo->query('SELECT * FROM services ORDER BY sort_order,id')->fetchAll();
$pageTitle = 'Services';
require __DIR__ . '/includes/header.php';
?>
<?php if ($error): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?>
<section class="admin-panel settings-panel">
  <div class="panel-heading"><div><h2>Service catalogue</h2><p>Keep descriptions current. Inactive services are hidden from the public catalogue.</p></div></div>
  <form method="post" class="admin-form">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <?php foreach ($services as $service): ?>
      <fieldset class="service-edit"><legend><?= e($service['service_key']) ?></legend>
        <div class="form-grid">
          <label>Title<input name="service[<?= (int)$service['id'] ?>][title]" value="<?= e($service['title']) ?>" maxlength="120" required></label>
          <label>Sort order<input type="number" name="service[<?= (int)$service['id'] ?>][sort_order]" value="<?= (int)$service['sort_order'] ?>" min="0" max="99"></label>
          <label class="full">Description<textarea name="service[<?= (int)$service['id'] ?>][description]" rows="2" maxlength="500"><?= e($service['description']) ?></textarea></label>
        </div>
        <label class="check-label"><input type="checkbox" name="service[<?= (int)$service['id'] ?>][active]" value="1" <?= $service['is_active'] ? 'checked' : '' ?>> Active on website</label>
      </fieldset>
    <?php endforeach; ?>
    <button class="admin-button">SAVE SERVICES <span>↗</span></button>
  </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
