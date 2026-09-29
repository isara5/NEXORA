<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$pdo = db();
$allowedCategories = ['WEB', 'APP', 'MARKETING', 'DESIGN', 'OTHER'];
function project_slug(string $text): string { $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text; $text = strtolower((string)preg_replace('/[^a-zA-Z0-9]+/', '-', $text)); return trim($text, '-') ?: bin2hex(random_bytes(5)); }
function upload_project_image(array $file, string $subdir): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) < 1 || ($file['size'] ?? 0) > UPLOAD_MAX_BYTES) throw new RuntimeException('Image upload failed or exceeds the 5 MB limit.');
    $tmp = (string)$file['tmp_name']; $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
    if (!is_uploaded_file($tmp)) throw new RuntimeException('The upload could not be verified.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    $imageInfo = @getimagesize($tmp);
    if (!isset($allowed[$ext]) || $allowed[$ext] !== $mime || $imageInfo === false || $imageInfo[0] < 1 || $imageInfo[1] < 1 || $imageInfo[0] * $imageInfo[1] > 40000000) throw new RuntimeException('Only valid JPG, PNG or WEBP images up to 40 megapixels are accepted.');
    $folder = UPLOAD_DIR . '/' . $subdir;
    if (!is_dir($folder) && !mkdir($folder, 0755, true) && !is_dir($folder)) throw new RuntimeException('Upload storage is unavailable.');
    $name = bin2hex(random_bytes(18)) . '.' . $ext;
    if (!move_uploaded_file($tmp, $folder . '/' . $name)) throw new RuntimeException('Could not store the uploaded image.');
    return '/uploads/' . $subdir . '/' . $name;
}
function remove_project_file(?string $path): void {
    if (!$path || !str_starts_with($path, '/uploads/')) return;
    $absolute = realpath(APP_ROOT . $path);
    $base = realpath(UPLOAD_DIR);
    if ($absolute && $base && str_starts_with($absolute, $base . DIRECTORY_SEPARATOR) && is_file($absolute)) @unlink($absolute);
}
$editId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$editing = null;
if ($editId) { $q = $pdo->prepare('SELECT * FROM projects WHERE id=?'); $q->execute([$editId]); $editing = $q->fetch(); if (!$editing) { admin_flash('Project not found.', 'error'); header('Location: /admin/projects.php'); exit; } }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf_token'] ?? null)) $error = 'Session expired. Refresh and retry.';
    else {
        $action = (string)($_POST['action'] ?? 'save');
        $createdFiles = [];
        $filesToRemove = [];
        try {
            if ($action === 'delete') {
                $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
                $q = $pdo->prepare('SELECT cover_image FROM projects WHERE id=?'); $q->execute([$id]); $old = $q->fetch();
                if ($old) {
                    $galleryQuery = $pdo->prepare('SELECT image_path FROM project_images WHERE project_id=?'); $galleryQuery->execute([$id]); $galleryPaths = $galleryQuery->fetchAll(PDO::FETCH_COLUMN);
                    $pdo->beginTransaction(); $pdo->prepare('DELETE FROM projects WHERE id=?')->execute([$id]); $pdo->commit();
                    remove_project_file($old['cover_image']); foreach ($galleryPaths as $galleryPath) remove_project_file($galleryPath);
                    admin_flash('Project deleted.');
                }
                header('Location: /admin/projects.php'); exit;
            }
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
            $title = request_string($_POST, 'title'); $category = strtoupper(request_string($_POST, 'category') ?: 'OTHER');
            $description = request_string($_POST, 'description'); $technologies = request_string($_POST, 'technologies');
            $url = request_string($_POST, 'project_url'); $status = request_string($_POST, 'status') === 'published' ? 'published' : 'draft';
            if ($title === '' || text_length($title) > 180 || !in_array($category, $allowedCategories, true) || $description === '' || text_length($description) > 5000 || text_length($technologies) > 500) throw new RuntimeException('Check title, category, description and technology fields.');
            if ($url !== '' && (!filter_var($url, FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($url, PHP_URL_SCHEME)), ['http','https'], true))) throw new RuntimeException('Project URL must be a valid HTTP or HTTPS address.');
            $galleryErrors = (array)($_FILES['gallery']['error'] ?? []);
            $newGalleryCount = count(array_filter($galleryErrors, static fn($code) => (int)$code !== UPLOAD_ERR_NO_FILE));
            if ($newGalleryCount > 12) throw new RuntimeException('A project can have at most 12 gallery images.');
            if ($id && $newGalleryCount) {
                $galleryCountQuery = $pdo->prepare('SELECT COUNT(*) FROM project_images WHERE project_id=?');
                $galleryCountQuery->execute([$id]);
                if ((int)$galleryCountQuery->fetchColumn() + $newGalleryCount > 12) throw new RuntimeException('Remove existing gallery images before adding more; projects are limited to 12 images.');
            }
            $slug = project_slug(request_string($_POST, 'slug') ?: $title);
            $findSlug = $pdo->prepare('SELECT id FROM projects WHERE slug=? AND id<>?'); $findSlug->execute([$slug, $id]); if ($findSlug->fetch()) $slug .= '-' . bin2hex(random_bytes(3));
            $pdo->beginTransaction();
            $cover = upload_project_image($_FILES['cover_image'] ?? [], 'projects');
            if ($cover) $createdFiles[] = $cover;
            if (!$id && !$cover) throw new RuntimeException('Please select a cover image.');
            if ($id) {
                $oldQ = $pdo->prepare('SELECT cover_image FROM projects WHERE id=?'); $oldQ->execute([$id]); $old = $oldQ->fetch(); if (!$old) throw new RuntimeException('Project not found.');
                $pdo->prepare('UPDATE projects SET title=?,slug=?,category=?,description=?,cover_image=?,project_url=?,technologies=?,featured=?,status=? WHERE id=?')->execute([$title,$slug,$category,$description,$cover ?: $old['cover_image'],$url ?: null,$technologies,(int)isset($_POST['featured']),$status,$id]);
                if ($cover) $filesToRemove[] = $old['cover_image'];
            } else {
                $pdo->prepare('INSERT INTO projects(title,slug,category,description,cover_image,project_url,technologies,featured,status) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$title,$slug,$category,$description,$cover,$url ?: null,$technologies,(int)isset($_POST['featured']),$status]);
                $id = (int)$pdo->lastInsertId();
            }
            foreach (($_FILES['gallery']['tmp_name'] ?? []) as $index => $tmp) {
                if (($_FILES['gallery']['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
                $file = ['tmp_name' => $tmp, 'name' => $_FILES['gallery']['name'][$index] ?? '', 'size' => $_FILES['gallery']['size'][$index] ?? 0, 'error' => $_FILES['gallery']['error'][$index] ?? UPLOAD_ERR_NO_FILE];
                $path = upload_project_image($file, 'gallery');
                if ($path) { $createdFiles[] = $path; $pdo->prepare('INSERT INTO project_images(project_id,image_path,alt_text) VALUES(?,?,?)')->execute([$id,$path,$title]); }
            }
            if ($editing) {
                foreach (array_slice((array)($_POST['remove_image_ids'] ?? []),0,50) as $imageId) {
                    $q = $pdo->prepare('SELECT image_path FROM project_images WHERE id=? AND project_id=?'); $q->execute([(int)$imageId,$id]); $image = $q->fetch();
                    if ($image) { $pdo->prepare('DELETE FROM project_images WHERE id=? AND project_id=?')->execute([(int)$imageId,$id]); $filesToRemove[] = $image['image_path']; }
                }
            }
            $pdo->commit();
            foreach ($filesToRemove as $path) remove_project_file($path);
            admin_flash('Project saved successfully.'); header('Location: /admin/projects.php'); exit;
        } catch (Throwable $exception) { if ($pdo->inTransaction()) $pdo->rollBack(); foreach ($createdFiles as $path) remove_project_file($path); error_log('NEXORA project management error: ' . $exception->getMessage()); $error = $exception instanceof RuntimeException ? $exception->getMessage() : 'Could not save this project. Check fields and image uploads.'; }
    }
}
$editing = $editing ?: ['id'=>0,'title'=>'','slug'=>'','category'=>'WEB','description'=>'','cover_image'=>'','project_url'=>'','technologies'=>'','featured'=>0,'status'=>'draft'];
$gallery = [];
if (!empty($editing['id'])) { $q = $pdo->prepare('SELECT id,image_path FROM project_images WHERE project_id=? ORDER BY sort_order,id'); $q->execute([$editing['id']]); $gallery = $q->fetchAll(); }
$pageTitle = $editing['id'] ? 'Edit project' : 'Projects'; require __DIR__ . '/includes/header.php';
?>
<?php if ($error): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?>
<section class="admin-panel"><div class="panel-heading"><div><h2><?= $editing['id'] ? 'Edit portfolio item' : 'Add a project' ?></h2><p>Publish only work NEXORA is authorized to show.</p></div></div><form class="admin-form" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$editing['id'] ?>"><div class="form-grid"><label>Project title<input name="title" required maxlength="180" value="<?= e($editing['title']) ?>"></label><label>Slug<input name="slug" maxlength="180" placeholder="Generated from title" value="<?= e($editing['slug']) ?>"></label><label>Category<select name="category"><?php foreach ($allowedCategories as $category): ?><option <?= $editing['category']===$category?'selected':'' ?>><?= e($category) ?></option><?php endforeach; ?></select></label><label>Project URL<input name="project_url" type="url" maxlength="500" placeholder="https://" value="<?= e($editing['project_url']) ?>"></label><label class="full">Description<textarea name="description" rows="4" maxlength="5000" required><?= e($editing['description']) ?></textarea></label><label>Technologies (comma separated)<input name="technologies" maxlength="500" value="<?= e($editing['technologies']) ?>"></label><label>Status<select name="status"><option value="draft" <?= $editing['status']==='draft'?'selected':'' ?>>Draft</option><option value="published" <?= $editing['status']==='published'?'selected':'' ?>>Published</option></select></label><label>Cover image (JPG, PNG, WEBP; max 5MB)<?= $editing['cover_image']?'<img class="cover-preview" src="'.e($editing['cover_image']).'" alt="Current project cover">':'' ?><input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp" <?= $editing['id']?'':'required' ?>></label><label>Gallery images (multiple)<input type="file" name="gallery[]" accept="image/jpeg,image/png,image/webp" multiple></label></div><?php if ($gallery): ?><div class="gallery-manage"><b>Current gallery</b><div><?php foreach ($gallery as $image): ?><label><img src="<?= e($image['image_path']) ?>" alt=""><span><input type="checkbox" name="remove_image_ids[]" value="<?= (int)$image['id'] ?>"> Remove</span></label><?php endforeach; ?></div></div><?php endif; ?><label class="check-label"><input type="checkbox" name="featured" value="1" <?= $editing['featured']?'checked':'' ?>> Feature this project</label><button class="admin-button" name="action" value="save">SAVE PROJECT <span>↗</span></button></form></section>
<section class="admin-panel"><div class="panel-heading"><div><h2>Portfolio projects</h2><p>Draft and published work shown in the site project manager.</p></div></div><?php $projects=$pdo->query('SELECT id,title,category,status,featured,created_at FROM projects ORDER BY created_at DESC')->fetchAll(); if (!$projects): ?><p class="empty-admin">No projects created. Published items will appear on the public site.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>PROJECT</th><th>CATEGORY</th><th>STATUS</th><th>FEATURED</th><th>CREATED</th><th>ACTIONS</th></tr></thead><tbody><?php foreach ($projects as $project): ?><tr><td><strong><?= e($project['title']) ?></strong></td><td><?= e($project['category']) ?></td><td><span class="status status-<?= e($project['status']) ?>"><?= e($project['status']) ?></span></td><td><?= $project['featured']?'Yes':'—' ?></td><td><?= e($project['created_at']) ?></td><td class="table-actions"><a href="/admin/projects.php?id=<?= (int)$project['id'] ?>">Edit</a><form method="post" onsubmit="return confirm('Delete this project permanently?')"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$project['id'] ?>"><button name="action" value="delete">Delete</button></form></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
