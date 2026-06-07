<?php
require_once 'includes/auth.php';
require_admin(); // Admin only
require_once 'db.php';

$success = '';
$errors  = [];

// Delete user
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    if ($del_id == $_SESSION['user_id']) {
        $errors[] = "You cannot delete your own account.";
    } else {
        mysqli_query($conn, "DELETE FROM users WHERE id = $del_id");
        $success = "🗑️ User deleted successfully.";
    }
}

// Change role
if (isset($_GET['toggle_role'])) {
    $tog_id = intval($_GET['toggle_role']);
    if ($tog_id == $_SESSION['user_id']) {
        $errors[] = "You cannot change your own role.";
    } else {
        $cur = mysqli_fetch_assoc(mysqli_query($conn, "SELECT role FROM users WHERE id = $tog_id"));
        $new_role = ($cur['role'] == 'admin') ? 'staff' : 'admin';
        mysqli_query($conn, "UPDATE users SET role = '$new_role' WHERE id = $tog_id");
        $success = "✅ User role updated to $new_role.";
    }
}

$users = mysqli_query($conn, "SELECT * FROM users ORDER BY role ASC, name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | ShopStock</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main">
    <div class="topbar">
        <h1>👥 <span>Manage Users</span></h1>
        <div class="topbar-right">
            <a href="register.php" class="btn btn-primary" target="_blank">➕ Add New User</a>
        </div>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success" id="flash-msg">
        <?= $success ?>
        <button onclick="document.getElementById('flash-msg').remove()" style="margin-left:auto; background:none; border:none; cursor:pointer; font-size:16px; color:inherit;">✕</button>
    </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errors[0]) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h3>All Users (<?= mysqli_num_rows($users) ?>)</h3>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; while ($user = mysqli_fetch_assoc($users)): ?>
                    <tr <?= $user['id'] == $_SESSION['user_id'] ? 'style="background:#f0fff4;"' : '' ?>>
                        <td style="color:#aaa;"><?= $i++ ?></td>
                        <td>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#e94560,#c0392b);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0;">
                                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                </div>
                                <span><strong><?= htmlspecialchars($user['name']) ?></strong>
                                <?= $user['id'] == $_SESSION['user_id'] ? ' <span style="font-size:11px;color:#27ae60;">(You)</span>' : '' ?>
                                </span>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                            <?php if ($user['role'] == 'admin'): ?>
                                <span class="badge" style="background:#fdecea;color:#e94560;">🔑 Admin</span>
                            <?php else: ?>
                                <span class="badge badge-info">👤 Staff</span>
                            <?php endif; ?>
                        </td>
                        <td style="color:#aaa; font-size:13px;"><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                        <td>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                            <div style="display:flex; gap:6px;">
                                <a href="manage_users.php?toggle_role=<?= $user['id'] ?>"
                                   class="btn btn-sm btn-secondary"
                                   title="Toggle role"
                                   onclick="return confirm('Change role of <?= htmlspecialchars(addslashes($user['name'])) ?>?')">
                                   🔄 Role
                                </a>
                                <button onclick="confirmDelUser(<?= $user['id'] ?>, '<?= htmlspecialchars(addslashes($user['name'])) ?>')"
                                        class="btn btn-sm btn-danger">🗑️</button>
                            </div>
                            <?php else: ?>
                            <span style="color:#ccc; font-size:12px;">Current user</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal-overlay" id="delUserModal">
    <div class="modal">
        <h3>🗑️ Delete User</h3>
        <p id="delUserMsg"></p>
        <div class="modal-actions">
            <button onclick="document.getElementById('delUserModal').classList.remove('active')" class="btn btn-outline">Cancel</button>
            <a href="#" id="delUserBtn" class="btn btn-danger">Delete</a>
        </div>
    </div>
</div>

<script>
function confirmDelUser(id, name) {
    document.getElementById('delUserMsg').textContent = 'Are you sure you want to delete user "' + name + '"? This cannot be undone.';
    document.getElementById('delUserBtn').href = 'manage_users.php?delete=' + id;
    document.getElementById('delUserModal').classList.add('active');
}
document.getElementById('delUserModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('active');
});
setTimeout(() => {
    const msg = document.getElementById('flash-msg');
    if (msg) msg.remove();
}, 4000);
</script>

</body>
</html>
