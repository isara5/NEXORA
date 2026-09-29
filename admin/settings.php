<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

$pdo = db();
$error = '';
$allowed = [
    'company_name' => 120, 'tagline' => 160, 'whatsapp' => 40, 'email' => 190,
    'phone' => 40, 'address' => 300, 'hero_heading' => 180, 'hero_description' => 1000,
    'stat_projects' => 80, 'stat_clients' => 80, 'stat_solutions' => 80, 'stat_experience' => 80
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf_token'] ?? null)) {
        $error = 'Session expired. Refresh and try again.';
    } else {
        try {
            $values = [];
            foreach ($allowed as $key => $maxLength) {
                $value = request_string($_POST, $key);
                if (text_length($value) > $maxLength) throw new RuntimeException('One or more values exceed their length limit.');
                if ($key === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Enter a valid company email address.');
                $values[$key] = $value;
            }

            $socialJson = request_string($_POST, 'social_links') ?: '{}';
            $socialLinks = json_decode($socialJson);
            if (!is_object($socialLinks) || strlen($socialJson) > 3000) {
                throw new RuntimeException('Social links must be a JSON object with named keys.');
            }
            foreach ($socialLinks as $url) {
                if (!is_string($url) || !filter_var($url, FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($url, PHP_URL_SCHEME)), ['https', 'http'], true)) {
                    throw new RuntimeException('Social links must contain valid HTTP(S) URLs.');
                }
            }

            $pdo->beginTransaction();
            $save = $pdo->prepare('INSERT INTO site_settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
            foreach ($values as $key => $value) $save->execute([$key, $value]);
            $save->execute(['social_links', json_encode($socialLinks, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)]);
            $pdo->commit();
            admin_flash('Site settings saved.');
            header('Location: /admin/settings.php');
            exit;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = $exception instanceof RuntimeException ? $exception->getMessage() : 'Unable to save settings.';
        }
    }
}

$settings = setting_map($pdo);
$pageTitle = 'Site settings';
require __DIR__ . '/includes/header.php';
?>
<?php if ($error): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?>
<section class="admin-panel settings-panel">
  <div class="panel-heading"><div><h2>Company information</h2><p>Public-facing brand details and homepage copy.</p></div></div>
  <form method="post" class="admin-form">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <div class="form-grid">
      <label>Company name<input name="company_name" maxlength="120" value="<?= e($settings['company_name']) ?>" required></label>
      <label>Tagline<input name="tagline" maxlength="160" value="<?= e($settings['tagline']) ?>"></label>
      <label>WhatsApp number<input name="whatsapp" maxlength="40" value="<?= e($settings['whatsapp']) ?>"></label>
      <label>Company phone<input name="phone" maxlength="40" value="<?= e($settings['phone']) ?>"></label>
      <label>Contact email<input name="email" type="email" maxlength="190" value="<?= e($settings['email']) ?>"></label>
      <label>Address<input name="address" maxlength="300" value="<?= e($settings['address']) ?>"></label>
      <label>Projects statistic<input name="stat_projects" maxlength="80" value="<?= e($settings['stat_projects']) ?>"></label>
      <label>Clients statistic<input name="stat_clients" maxlength="80" value="<?= e($settings['stat_clients']) ?>"></label>
      <label>Solutions statistic<input name="stat_solutions" maxlength="80" value="<?= e($settings['stat_solutions']) ?>"></label>
      <label>Years of experience statistic<input name="stat_experience" maxlength="80" value="<?= e($settings['stat_experience']) ?>"></label>
      <label class="full">Hero heading <small>Use | to break the heading onto a second line.</small><input name="hero_heading" maxlength="180" value="<?= e($settings['hero_heading']) ?>"></label>
      <label class="full">Hero description<textarea name="hero_description" rows="4" maxlength="1000"><?= e($settings['hero_description']) ?></textarea></label>
      <label class="full">Social media links <small>JSON object with named keys and HTTP(S) URLs.</small><textarea name="social_links" rows="3"><?= e($settings['social_links'] ?? '{}') ?></textarea></label>
    </div>
    <button class="admin-button">SAVE SETTINGS <span>↗</span></button>
  </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
