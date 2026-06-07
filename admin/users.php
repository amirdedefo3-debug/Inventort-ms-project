<?php
require_once '../includes/auth.php';
require_admin();
require_once '../db.php';
$page_title='👥 User Management';
$s=get_settings($conn);
$errors=[];$success='';

// Toggle status
if(isset($_GET['toggle'])){
    $tid=intval($_GET['toggle']);
    if($tid!=$_SESSION['user_id']){
        $cur=mysqli_fetch_assoc(mysqli_query($conn,"SELECT status FROM users WHERE id=$tid"));
        $ns=$cur['status']=='active'?'inactive':'active';
        mysqli_query($conn,"UPDATE users SET status='$ns' WHERE id=$tid");
        log_activity($conn,"User Status Changed","User ID $tid → $ns");
        $success="✅ User status updated to $ns.";
    }
}
// Delete
if(isset($_GET['delete'])){
    $did=intval($_GET['delete']);
    if($did==$_SESSION['user_id']){$errors[]="Cannot delete yourself.";}
    else{mysqli_query($conn,"DELETE FROM users WHERE id=$did");log_activity($conn,"Deleted User","ID: $did");$success="🗑️ User deleted.";}
}
// Reset password
if(isset($_POST['reset_password'])){
    $rid=intval($_POST['user_id']??0);$np=$_POST['new_password']??'';
    if($rid&&strlen($np)>=6){
        $hash=password_hash($np,PASSWORD_DEFAULT);
        mysqli_query($conn,"UPDATE users SET password='$hash' WHERE id=$rid");
        log_activity($conn,"Password Reset","User ID: $rid");
        $success="✅ Password reset successfully.";
    } else {$errors[]="Password must be at least 6 characters.";}
}
// Add user
if(isset($_POST['add_user'])){
    $fn=trim($_POST['full_name']??'');$em=trim($_POST['email']??'');
    $un=trim($_POST['username']??'');$pw=$_POST['password']??'';
    $ph=trim($_POST['phone']??'');$role=$_POST['role']??'cashier';
    if(!$fn||!$em||!$pw)$errors[]="Name, email and password required.";
    elseif(!filter_var($em,FILTER_VALIDATE_EMAIL))$errors[]="Invalid email.";
    elseif(strlen($pw)<6)$errors[]="Password min 6 chars.";
    else{
        $fn_e=mysqli_real_escape_string($conn,$fn);$em_e=mysqli_real_escape_string($conn,$em);
        $un_e=mysqli_real_escape_string($conn,$un);$ph_e=mysqli_real_escape_string($conn,$ph);
        $hash=password_hash($pw,PASSWORD_DEFAULT);
        $check=mysqli_query($conn,"SELECT id FROM users WHERE email='$em_e'");
        if(mysqli_num_rows($check)>0)$errors[]="Email already exists.";
        else{
            mysqli_query($conn,"INSERT INTO users (full_name,email,phone,username,password,role) VALUES ('$fn_e','$em_e','$ph_e','$un_e','$hash','$role')");
            log_activity($conn,"Added User","$fn ($role)");
            $success="✅ User '$fn' added as $role.";
        }
    }
}

$search=isset($_GET['q'])?mysqli_real_escape_string($conn,$_GET['q']):'';
$role_f=isset($_GET['role'])?$_GET['role']:'';
$where="WHERE 1=1";
if($search)$where.=" AND (full_name LIKE '%$search%' OR email LIKE '%$search%' OR username LIKE '%$search%')";
if($role_f&&in_array($role_f,['admin','manager','cashier']))$where.=" AND role='$role_f'";
$users=mysqli_query($conn,"SELECT * FROM users $where ORDER BY role,full_name");
?>
<!DOCTYPE html><html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?=$page_title?> | <?=$s['shop_name']?></title><link rel="stylesheet" href="../css/dashboard.css"></head>
<body>
<?php include '../includes/admin_sidebar.php'; ?>
<div class="main-area">
<?php include '../includes/topbar.php'; ?>
<div class="content">
<?php if($success):?><div class="alert alert-success" id="fm"><?=$success?><button class="close-btn" onclick="this.parentElement.remove()">✕</button></div><?php endif;?>
<?php if(!empty($errors)):?><div class="alert alert-danger"><?=implode('<br>',$errors)?></div><?php endif;?>

<div class="grid-2" style="align-items:start">
<div>
<div class="card" style="margin-bottom:20px">
  <div class="card-header"><h3>➕ Add New User</h3></div>
  <div class="card-body">
    <form method="POST">
      <input type="hidden" name="add_user" value="1">
      <div class="form-row">
        <div class="form-group"><label>Full Name *</label><input type="text" name="full_name" required placeholder="Full name"></div>
        <div class="form-group"><label>Email *</label><input type="email" name="email" required placeholder="email@example.com"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Username</label><input type="text" name="username" placeholder="username (optional)"></div>
        <div class="form-group"><label>Phone</label><input type="text" name="phone" placeholder="Phone number"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Password *</label><input type="password" name="password" required placeholder="Min 6 chars"></div>
        <div class="form-group"><label>Role *</label>
          <select name="role">
            <option value="cashier">🛒 Cashier</option>
            <option value="manager">📦 Manager</option>
            <option value="admin">🔑 Admin</option>
          </select>
        </div>
      </div>
      <div class="form-actions"><button type="submit" class="btn btn-primary">➕ Add User</button></div>
    </form>
  </div>
</div>
</div>

<div>
<div class="card">
  <div class="card-header">
    <h3>All Users (<?=mysqli_num_rows($users)?>)</h3>
    <form method="GET" class="search-bar">
      <input type="text" name="q" placeholder="🔍 Search..." value="<?=htmlspecialchars($_GET['q']??'')?>">
      <select name="role">
        <option value="">All Roles</option>
        <option value="admin" <?=$role_f=='admin'?'selected':''?>>🔑 Admin</option>
        <option value="manager" <?=$role_f=='manager'?'selected':''?>>📦 Manager</option>
        <option value="cashier" <?=$role_f=='cashier'?'selected':''?>>🛒 Cashier</option>
      </select>
      <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
    </form>
  </div>
  <div class="table-responsive"><table>
    <thead><tr><th>User</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
    <tbody>
    <?php if(!$users||mysqli_num_rows($users)==0):?>
    <tr><td colspan="5"><div class="empty-state"><div class="ei">👥</div><p>No users found</p></div></td></tr>
    <?php else: while($u=mysqli_fetch_assoc($users)):
      $roles=['admin'=>['🔑 Admin','b-danger'],'manager'=>['📦 Manager','b-info'],'cashier'=>['🛒 Cashier','b-success']];
      $rl=$roles[$u['role']]??[$u['role'],'b-gray'];
    ?>
    <tr <?=$u['id']==$_SESSION['user_id']?'style="background:#f0fff4"':''?>>
      <td>
        <div style="display:flex;align-items:center;gap:10px">
          <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#e94560,#c0392b);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0"><?=strtoupper(substr($u['full_name'],0,1))?></div>
          <div>
            <strong><?=htmlspecialchars($u['full_name'])?></strong><?=$u['id']==$_SESSION['user_id']?' <span style="font-size:11px;color:#27ae60">(You)</span>':''?>
            <div style="font-size:11px;color:#aaa"><?=htmlspecialchars($u['email'])?></div>
          </div>
        </div>
      </td>
      <td><span class="badge <?=$rl[1]?>"><?=$rl[0]?></span></td>
      <td>
        <span class="badge <?=$u['status']=='active'?'b-success':'b-danger'?>">
          <?=$u['status']=='active'?'✅ Active':'❌ Inactive'?>
        </span>
      </td>
      <td style="font-size:12px;color:#aaa"><?=date('d M Y',strtotime($u['created_at']))?></td>
      <td>
        <?php if($u['id']!=$_SESSION['user_id']):?>
        <div style="display:flex;gap:4px;flex-wrap:wrap">
          <a href="?toggle=<?=$u['id']?>" class="btn btn-xs <?=$u['status']=='active'?'btn-warning':'btn-success'?>" title="Toggle status" onclick="return confirm('Toggle status?')"><?=$u['status']=='active'?'🚫':'✅'?></a>
          <button onclick="showReset(<?=$u['id']?>,<?=htmlspecialchars(json_encode($u['full_name']))?>')" class="btn btn-xs btn-secondary" title="Reset password">🔑</button>
          <button onclick="delUser(<?=$u['id']?>,<?=htmlspecialchars(json_encode($u['full_name']))?>')" class="btn btn-xs btn-danger" title="Delete">🗑️</button>
        </div>
        <?php else:?><span style="font-size:11px;color:#aaa">Current user</span><?php endif;?>
      </td>
    </tr>
    <?php endwhile; endif;?>
    </tbody>
  </table></div>
</div>
</div>
</div>
</div></div>

<!-- Reset Password Modal -->
<div class="modal-overlay" id="resetModal">
  <div class="modal">
    <h3>🔑 Reset Password</h3>
    <p id="resetMsg" style="color:#666;font-size:13px;margin-bottom:16px;"></p>
    <form method="POST">
      <input type="hidden" name="reset_password" value="1">
      <input type="hidden" name="user_id" id="resetUserId">
      <div class="form-group"><label>New Password *</label><input type="password" name="new_password" placeholder="Min 6 characters" required></div>
      <div class="modal-actions">
        <button type="button" onclick="document.getElementById('resetModal').classList.remove('active')" class="btn btn-outline">Cancel</button>
        <button type="submit" class="btn btn-primary">Reset Password</button>
      </div>
    </form>
  </div>
</div>
<!-- Delete Modal -->
<div class="modal-overlay" id="delModal">
  <div class="modal">
    <h3>🗑️ Delete User</h3>
    <p id="delMsg"></p>
    <div class="modal-actions">
      <button onclick="document.getElementById('delModal').classList.remove('active')" class="btn btn-outline">Cancel</button>
      <a href="#" id="delBtn" class="btn btn-danger">Delete</a>
    </div>
  </div>
</div>
<script>
function showReset(id,name){
  document.getElementById('resetUserId').value=id;
  document.getElementById('resetMsg').textContent='Reset password for: '+name;
  document.getElementById('resetModal').classList.add('active');
}
function delUser(id,name){
  document.getElementById('delMsg').textContent='Delete user "'+name+'"? This cannot be undone.';
  document.getElementById('delBtn').href='users.php?delete='+id;
  document.getElementById('delModal').classList.add('active');
}
['resetModal','delModal'].forEach(id=>{
  document.getElementById(id).addEventListener('click',e=>{if(e.target===e.currentTarget)e.currentTarget.classList.remove('active')});
});
setTimeout(()=>{const f=document.getElementById('fm');if(f)f.remove();},4000);
</script>
</body></html>
