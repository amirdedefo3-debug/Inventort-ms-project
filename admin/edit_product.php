<?php
require_once '../includes/auth.php';
require_admin();
require_once '../db.php';
$page_title='✏️ Edit Product';
$s=get_settings($conn);
$id=intval($_GET['id']??0);
if(!$id){header("Location: products.php");exit;}
$prod=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM products WHERE id=$id"));
if(!$prod){header("Location: products.php");exit;}
$errors=[];

if($_SERVER['REQUEST_METHOD']=='POST'){
    $name=$_POST['name']??'';$bc=$_POST['barcode']??'';$cat=intval($_POST['category_id']??0);
    $sup=intval($_POST['supplier_id']??0);$pp=floatval($_POST['purchase_price']??0);
    $sp=floatval($_POST['selling_price']??0);$qty=intval($_POST['quantity']??0);
    $rl=intval($_POST['reorder_level']??5);$desc=$_POST['description']??'';
    if(!trim($name))$errors[]="Product name required.";
    if(empty($errors)){
        $n=mysqli_real_escape_string($conn,$name);$b=mysqli_real_escape_string($conn,$bc);$d=mysqli_real_escape_string($conn,$desc);
        $cv=$cat>0?$cat:'NULL';$sv=$sup>0?$sup:'NULL';
        mysqli_query($conn,"UPDATE products SET name='$n',barcode='$b',category_id=$cv,supplier_id=$sv,purchase_price=$pp,selling_price=$sp,quantity=$qty,reorder_level=$rl,description='$d' WHERE id=$id");
        log_activity($conn,"Updated Product",$name);
        header("Location: products.php?msg=updated"); exit;
    }
    $prod=array_merge($prod,['name'=>$name,'barcode'=>$bc,'category_id'=>$cat,'supplier_id'=>$sup,'purchase_price'=>$pp,'selling_price'=>$sp,'quantity'=>$qty,'reorder_level'=>$rl,'description'=>$desc]);
}
$categories=mysqli_query($conn,"SELECT * FROM categories ORDER BY name");
$suppliers=mysqli_query($conn,"SELECT * FROM suppliers ORDER BY name");
?>
<!DOCTYPE html><html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?=$page_title?> | <?=$s['shop_name']?></title><link rel="stylesheet" href="../css/dashboard.css"></head>
<body>
<?php include '../includes/admin_sidebar.php'; ?>
<div class="main-area">
<?php include '../includes/topbar.php'; ?>
<div class="content">
<?php if(!empty($errors)):?><div class="alert alert-danger"><?=implode('<br>',$errors)?></div><?php endif;?>
<div style="max-width:700px">
<div class="card">
  <div class="card-header"><h3>✏️ Edit: <?=htmlspecialchars($prod['name'])?></h3><a href="products.php" class="btn btn-sm btn-outline">← Back</a></div>
  <div class="card-body">
    <form method="POST">
      <div class="form-row">
        <div class="form-group"><label>Product Name *</label><input type="text" name="name" required value="<?=htmlspecialchars($prod['name'])?>"></div>
        <div class="form-group"><label>Barcode</label><input type="text" name="barcode" value="<?=htmlspecialchars($prod['barcode']??'')?>"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Category</label>
          <select name="category_id"><option value="0">— Select —</option>
          <?php while($c=mysqli_fetch_assoc($categories)):?><option value="<?=$c['id']?>" <?=$prod['category_id']==$c['id']?'selected':''?>><?=htmlspecialchars($c['name'])?></option><?php endwhile;?>
          </select>
        </div>
        <div class="form-group"><label>Supplier</label>
          <select name="supplier_id"><option value="0">— Select —</option>
          <?php while($sp=mysqli_fetch_assoc($suppliers)):?><option value="<?=$sp['id']?>" <?=$prod['supplier_id']==$sp['id']?'selected':''?>><?=htmlspecialchars($sp['name'])?></option><?php endwhile;?>
          </select>
        </div>
      </div>
      <div class="form-row-3">
        <div class="form-group"><label>Purchase Price</label><input type="number" name="purchase_price" min="0" step="0.01" value="<?=htmlspecialchars($prod['purchase_price'])?>"></div>
        <div class="form-group"><label>Selling Price *</label><input type="number" name="selling_price" min="0" step="0.01" required value="<?=htmlspecialchars($prod['selling_price'])?>"></div>
        <div class="form-group"><label>Quantity</label><input type="number" name="quantity" min="0" value="<?=htmlspecialchars($prod['quantity'])?>"></div>
      </div>
      <div class="form-group"><label>Reorder Level</label><input type="number" name="reorder_level" min="0" value="<?=htmlspecialchars($prod['reorder_level'])?>"></div>
      <div class="form-group"><label>Description</label><textarea name="description"><?=htmlspecialchars($prod['description']??'')?></textarea></div>
      <div class="form-actions">
        <button type="submit" class="btn btn-warning">💾 Save Changes</button>
        <a href="products.php" class="btn btn-outline">Cancel</a>
        <a href="delete_product.php?id=<?=$id?>" class="btn btn-danger" style="margin-left:auto" onclick="return confirm('Delete this product?')">🗑️ Delete</a>
      </div>
    </form>
  </div>
</div>
</div>
</div></div>
</body></html>
