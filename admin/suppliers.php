<?php
require_once '../includes/auth.php';
require_admin();
require_once '../db.php';
$page_title='🚚 Suppliers';
$s=get_settings($conn);
$errors=[];$success='';

// Add
if(isset($_POST['add_supplier'])){
    $name=trim($_POST['name']??'');$cp=trim($_POST['contact_person']??'');
    $ph=trim($_POST['phone']??'');$em=trim($_POST['email']??'');$addr=trim($_POST['address']??'');
    if(!$name)$errors[]="Supplier name required.";
    else{
        $n=mysqli_real_escape_string($conn,$name);$c=mysqli_real_escape_string($conn,$cp);
        $p=mysqli_real_escape_string($conn,$ph);$e=mysqli_real_escape_string($conn,$em);$a=mysqli_real_escape_string($conn,$addr);
        $chk=mysqli_query($conn,"SELECT id FROM suppliers WHERE name='$n'");
        if(mysqli_num_rows($chk)>0)$errors[]="Supplier already exists.";
        else{mysqli_query($conn,"INSERT INTO suppliers (name,contact_person,phone,email,address) VALUES ('$n','$c','$p','$e','$a')");
        log_activity($conn,"Added Supplier",$name);$success="✅ Supplier '$name' added.";}
    }
}
// Edit
if(isset($_POST['edit_supplier'])){
    $id=intval($_POST['sid']??0);
    $name=trim($_POST['name']??'');$cp=trim($_POST['contact_person']??'');
    $ph=trim($_POST['phone']??'');$em=trim($_POST['email']??'');$addr=trim($_POST['address']??'');
    if($id&&$name){
        $n=mysqli_real_escape_string($conn,$name);$c=mysqli_real_escape_string($conn,$cp);
        $p=mysqli_real_escape_string($conn,$ph);$e=mysqli_real_escape_string($conn,$em);$a=mysqli_real_escape_string($conn,$addr);
        mysqli_query($conn,"UPDATE suppliers SET name='$n',contact_person='$c',phone='$p',email='$e',address='$a' WHERE id=$id");
        log_activity($conn,"Updated Supplier",$name);$success="✅ Supplier updated.";
    }
}
// Delete
if(isset($_GET['delete'])){
    $did=intval($_GET['delete']);
    mysqli_query($conn,"UPDATE products SET supplier_id=NULL WHERE supplier_id=$did");
    mysqli_query($conn,"DELETE FROM suppliers WHERE id=$did");
    log_activity($conn,"Deleted Supplier","ID: $did");$success="🗑️ Supplier deleted.";
}

$search=isset($_GET['q'])?mysqli_real_escape_string($conn,$_GET['q']):'';
$where=$search?"WHERE name LIKE '%$search%' OR contact_person LIKE '%$search%' OR phone LIKE '%$search%'":'';
$suppliers=mysqli_query($conn,"SELECT s.*,(SELECT COUNT(*) FROM products WHERE supplier_id=s.id) pc FROM suppliers s $where ORDER BY s.name");
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
    <div class="card-header"><h3>➕ Add Supplier</h3></div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="add_supplier" value="1">
        <div class="form-group"><label>Supplier Name *</label><input type="text" name="name" required placeholder="Company name"></div>
        <div class="form-row">
          <div class="form-group"><label>Contact Person</label><input type="text" name="contact_person" placeholder="Contact name"></div>
          <div class="form-group"><label>Phone</label><input type="text" name="phone" placeholder="Phone number"></div>
        </div>
        <div class="form-group"><label>Email</label><input type="email" name="email" placeholder="email@supplier.com"></div>
        <div class="form-group"><label>Address</label><textarea name="address" rows="2" placeholder="Supplier address"></textarea></div>
        <div class="form-actions"><button type="submit" class="btn btn-primary">➕ Add Supplier</button></div>
      </form>
    </div>
  </div>
  <div class="card">
    <div class="card-header">
      <h3>All Suppliers (<?=mysqli_num_rows($suppliers)?>)</h3>
      <form method="GET" class="search-bar">
        <input type="text" name="q" placeholder="🔍 Search..." value="<?=htmlspecialchars($_GET['q']??'')?>">
        <button type="submit" class="btn btn-secondary btn-sm">Search</button>
        <?php if($search):?><a href="suppliers.php" class="btn btn-outline btn-sm">Clear</a><?php endif;?>
      </form>
    </div>
    <div class="table-responsive"><table>
      <thead><tr><th>Supplier</th><th>Contact</th><th>Phone</th><th>Products</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if(!$suppliers||mysqli_num_rows($suppliers)==0):?>
      <tr><td colspan="5"><div class="empty-state"><div class="ei">🚚</div><p>No suppliers yet</p></div></td></tr>
      <?php else: while($r=mysqli_fetch_assoc($suppliers)):?>
      <tr>
        <td><strong><?=htmlspecialchars($r['name'])?></strong><?php if($r['email']):?><br><small style="color:#aaa"><?=htmlspecialchars($r['email'])?></small><?php endif;?></td>
        <td><?=htmlspecialchars($r['contact_person']??'—')?></td>
        <td><?=htmlspecialchars($r['phone']??'—')?></td>
        <td><span class="badge b-info"><?=$r['pc']?> item(s)</span></td>
        <td>
          <div style="display:flex;gap:4px">
            <button onclick="editSup(<?=htmlspecialchars(json_encode($r),ENT_QUOTES)?>')" class="btn btn-xs btn-warning">✏️</button>
            <button onclick="delSup(<?=$r['id']?>,<?=htmlspecialchars(json_encode($r['name']),'UTF-8')?>)" class="btn btn-xs btn-danger">🗑️</button>
          </div>
        </td>
      </tr>
      <?php endwhile; endif;?>
      </tbody>
    </table></div>
  </div>
</div>
</div></div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
  <div class="modal">
    <h3>✏️ Edit Supplier</h3>
    <form method="POST">
      <input type="hidden" name="edit_supplier" value="1">
      <input type="hidden" name="sid" id="editId">
      <div class="form-group"><label>Supplier Name *</label><input type="text" name="name" id="editName" required></div>
      <div class="form-row">
        <div class="form-group"><label>Contact Person</label><input type="text" name="contact_person" id="editCP"></div>
        <div class="form-group"><label>Phone</label><input type="text" name="phone" id="editPhone"></div>
      </div>
      <div class="form-group"><label>Email</label><input type="email" name="email" id="editEmail"></div>
      <div class="form-group"><label>Address</label><textarea name="address" id="editAddr" rows="2"></textarea></div>
      <div class="modal-actions">
        <button type="button" onclick="document.getElementById('editModal').classList.remove('active')" class="btn btn-outline">Cancel</button>
        <button type="submit" class="btn btn-warning">💾 Save</button>
      </div>
    </form>
  </div>
</div>
<!-- Delete Modal -->
<div class="modal-overlay" id="delModal">
  <div class="modal"><h3>🗑️ Delete Supplier</h3><p id="delMsg"></p>
    <div class="modal-actions">
      <button onclick="document.getElementById('delModal').classList.remove('active')" class="btn btn-outline">Cancel</button>
      <a href="#" id="delBtn" class="btn btn-danger">Delete</a>
    </div>
  </div>
</div>
<script>
function editSup(r){
  document.getElementById('editId').value=r.id;
  document.getElementById('editName').value=r.name||'';
  document.getElementById('editCP').value=r.contact_person||'';
  document.getElementById('editPhone').value=r.phone||'';
  document.getElementById('editEmail').value=r.email||'';
  document.getElementById('editAddr').value=r.address||'';
  document.getElementById('editModal').classList.add('active');
}
function delSup(id,name){
  document.getElementById('delMsg').textContent='Delete supplier "'+name+'"? Products using this supplier will become unassigned.';
  document.getElementById('delBtn').href='suppliers.php?delete='+id;
  document.getElementById('delModal').classList.add('active');
}
['editModal','delModal'].forEach(id=>document.getElementById(id).addEventListener('click',e=>{if(e.target===e.currentTarget)e.currentTarget.classList.remove('active')}));
setTimeout(()=>{const f=document.getElementById('fm');if(f)f.remove();},4000);
</script>
</body></html>
