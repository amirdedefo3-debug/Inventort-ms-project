<?php
require_once '../includes/auth.php';
require_admin();
require_once '../db.php';
$page_title='🗂️ Categories';
$s=get_settings($conn);
$errors=[];$success='';

if(isset($_POST['add_cat'])){
    $name=trim($_POST['name']??'');$desc=trim($_POST['description']??'');
    if(!$name)$errors[]="Category name required.";
    else{
        $n=mysqli_real_escape_string($conn,$name);$d=mysqli_real_escape_string($conn,$desc);
        $chk=mysqli_query($conn,"SELECT id FROM categories WHERE name='$n'");
        if(mysqli_num_rows($chk)>0)$errors[]="Category already exists.";
        else{mysqli_query($conn,"INSERT INTO categories (name,description) VALUES ('$n','$d')");log_activity($conn,"Added Category",$name);$success="✅ Category '$name' added.";}
    }
}
if(isset($_POST['edit_cat'])){
    $id=intval($_POST['cid']??0);$name=trim($_POST['name']??'');$desc=trim($_POST['description']??'');
    if($id&&$name){$n=mysqli_real_escape_string($conn,$name);$d=mysqli_real_escape_string($conn,$desc);
    mysqli_query($conn,"UPDATE categories SET name='$n',description='$d' WHERE id=$id");log_activity($conn,"Updated Category",$name);$success="✅ Category updated.";}
}
if(isset($_GET['delete'])){
    $did=intval($_GET['delete']);
    mysqli_query($conn,"UPDATE products SET category_id=NULL WHERE category_id=$did");
    mysqli_query($conn,"DELETE FROM categories WHERE id=$did");
    log_activity($conn,"Deleted Category","ID: $did");$success="🗑️ Category deleted.";
}

$cats=mysqli_query($conn,"SELECT c.*,(SELECT COUNT(*) FROM products WHERE category_id=c.id) pc FROM categories c ORDER BY c.name");
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
  <div class="card">
    <div class="card-header"><h3>➕ Add Category</h3></div>
    <div class="card-body">
      <form method="POST"><input type="hidden" name="add_cat" value="1">
        <div class="form-group"><label>Category Name *</label><input type="text" name="name" required placeholder="e.g. Electronics"></div>
        <div class="form-group"><label>Description</label><textarea name="description" placeholder="Optional description"></textarea></div>
        <div class="form-actions"><button type="submit" class="btn btn-primary">➕ Add Category</button></div>
      </form>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><h3>All Categories (<?=mysqli_num_rows($cats)?>)</h3></div>
    <div class="table-responsive"><table>
      <thead><tr><th>#</th><th>Category</th><th>Description</th><th>Products</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if(mysqli_num_rows($cats)==0):?>
      <tr><td colspan="5"><div class="empty-state"><div class="ei">🗂️</div><p>No categories yet</p></div></td></tr>
      <?php else: $i=1; while($r=mysqli_fetch_assoc($cats)):?>
      <tr>
        <td style="color:#aaa"><?=$i++?></td>
        <td><strong><?=htmlspecialchars($r['name'])?></strong></td>
        <td style="font-size:12px;color:#888"><?=htmlspecialchars(substr($r['description']??'',0,50))?></td>
        <td><span class="badge b-info"><?=$r['pc']?> item(s)</span></td>
        <td>
          <div style="display:flex;gap:4px">
            <button onclick="editCat(<?=htmlspecialchars(json_encode($r),ENT_QUOTES)?>)" class="btn btn-xs btn-warning">✏️</button>
            <button onclick="delCat(<?=$r['id']?>,<?=htmlspecialchars(json_encode($r['name']))?>,<?=$r['pc']?>)" class="btn btn-xs btn-danger">🗑️</button>
          </div>
        </td>
      </tr>
      <?php endwhile; endif;?>
      </tbody>
    </table></div>
  </div>
</div>
</div></div>

<div class="modal-overlay" id="editModal">
  <div class="modal"><h3>✏️ Edit Category</h3>
    <form method="POST"><input type="hidden" name="edit_cat" value="1"><input type="hidden" name="cid" id="editId">
      <div class="form-group"><label>Name *</label><input type="text" name="name" id="editName" required></div>
      <div class="form-group"><label>Description</label><textarea name="description" id="editDesc"></textarea></div>
      <div class="modal-actions">
        <button type="button" onclick="document.getElementById('editModal').classList.remove('active')" class="btn btn-outline">Cancel</button>
        <button type="submit" class="btn btn-warning">💾 Save</button>
      </div>
    </form>
  </div>
</div>
<div class="modal-overlay" id="delModal">
  <div class="modal"><h3>🗑️ Delete Category</h3><p id="delMsg"></p>
    <div class="modal-actions">
      <button onclick="document.getElementById('delModal').classList.remove('active')" class="btn btn-outline">Cancel</button>
      <a href="#" id="delBtn" class="btn btn-danger">Delete</a>
    </div>
  </div>
</div>
<script>
function editCat(r){document.getElementById('editId').value=r.id;document.getElementById('editName').value=r.name;document.getElementById('editDesc').value=r.description||'';document.getElementById('editModal').classList.add('active');}
function delCat(id,name,cnt){document.getElementById('delMsg').textContent='Delete "'+name+'"?'+(cnt>0?' ⚠️ '+cnt+' product(s) will be uncategorized.':'');document.getElementById('delBtn').href='categories.php?delete='+id;document.getElementById('delModal').classList.add('active');}
['editModal','delModal'].forEach(id=>document.getElementById(id).addEventListener('click',e=>{if(e.target===e.currentTarget)e.currentTarget.classList.remove('active')}));
setTimeout(()=>{const f=document.getElementById('fm');if(f)f.remove();},4000);
</script>
</body></html>
