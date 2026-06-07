<?php
require_once 'db.php';

$errors  = [];
$success = '';

// ADD category
if (isset($_POST['action']) && $_POST['action'] == 'add') {
    $name = trim($_POST['cat_name'] ?? '');
    if (empty($name)) {
        $errors[] = "Category name is required.";
    } elseif (strlen($name) > 100) {
        $errors[] = "Category name must be under 100 characters.";
    } else {
        $name_esc = mysqli_real_escape_string($conn, $name);
        $check = mysqli_query($conn, "SELECT id FROM categories WHERE name = '$name_esc'");
        if (mysqli_num_rows($check) > 0) {
            $errors[] = "Category '$name' already exists.";
        } else {
            mysqli_query($conn, "INSERT INTO categories (name) VALUES ('$name_esc')");
            $success = "✅ Category '$name' added successfully.";
        }
    }
}

// DELETE category
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    // Set products category to NULL first
    mysqli_query($conn, "UPDATE products SET category_id = NULL WHERE category_id = $del_id");
    mysqli_query($conn, "DELETE FROM categories WHERE id = $del_id");
    $success = "🗑️ Category deleted. Products in this category are now uncategorized.";
}

// Fetch all categories with product count
$categories = mysqli_query($conn, "
    SELECT c.*, COUNT(p.id) as product_count
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id
    ORDER BY c.name
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories | ShopStock</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main">
    <div class="topbar">
        <h1>🗂️ <span>Categories</span></h1>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success" id="flash-msg">
        <?= $success ?>
        <button onclick="document.getElementById('flash-msg').remove()" style="margin-left:auto; background:none; border:none; cursor:pointer; font-size:16px; color:inherit;">✕</button>
    </div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns:340px 1fr; gap:24px; align-items:start;">

        <!-- Add Category Form -->
        <div class="card">
            <div class="card-header">
                <h3>➕ Add New Category</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($errors[0]) ?>
                </div>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label>Category Name *</label>
                        <input type="text" name="cat_name" placeholder="e.g. Electronics, Clothing..."
                               value="<?= htmlspecialchars($_POST['cat_name'] ?? '') ?>"
                               maxlength="100" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">➕ Add Category</button>
                </form>
            </div>
        </div>

        <!-- Categories List -->
        <div class="card">
            <div class="card-header">
                <h3>All Categories (<?= mysqli_num_rows($categories) ?>)</h3>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Category Name</th>
                            <th>Products</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($categories) == 0): ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <div class="empty-icon">🗂️</div>
                                    <p>No categories yet. Add one!</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: $i = 1; while ($cat = mysqli_fetch_assoc($categories)): ?>
                        <tr>
                            <td style="color:#aaa;"><?= $i++ ?></td>
                            <td><strong><?= htmlspecialchars($cat['name']) ?></strong></td>
                            <td>
                                <span class="badge badge-info"><?= $cat['product_count'] ?> product<?= $cat['product_count'] != 1 ? 's' : '' ?></span>
                            </td>
                            <td style="color:#aaa; font-size:13px;"><?= date('d M Y', strtotime($cat['created_at'])) ?></td>
                            <td>
                                <button onclick="confirmDeleteCat(<?= $cat['id'] ?>, '<?= htmlspecialchars(addslashes($cat['name'])) ?>', <?= $cat['product_count'] ?>)"
                                        class="btn btn-sm btn-danger">🗑️ Delete</button>
                            </td>
                        </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirm Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal">
        <h3>🗑️ Delete Category</h3>
        <p id="deleteMsg"></p>
        <div class="modal-actions">
            <button onclick="document.getElementById('deleteModal').classList.remove('active')" class="btn btn-outline">Cancel</button>
            <a href="#" id="deleteBtn" class="btn btn-danger">Delete</a>
        </div>
    </div>
</div>

<script>
function confirmDeleteCat(id, name, count) {
    let msg = 'Are you sure you want to delete "' + name + '"?';
    if (count > 0) {
        msg += ' ⚠️ This category has ' + count + ' product(s) — they will become uncategorized.';
    }
    document.getElementById('deleteMsg').textContent = msg;
    document.getElementById('deleteBtn').href = 'categories.php?delete=' + id;
    document.getElementById('deleteModal').classList.add('active');
}

document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('active');
});

setTimeout(() => {
    const msg = document.getElementById('flash-msg');
    if (msg) msg.remove();
}, 4000);
</script>

</body>
</html>
