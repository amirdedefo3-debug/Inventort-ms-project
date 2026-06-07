<?php
require_once '../includes/auth.php';
require_admin();
require_once '../db.php';
$page_title='⚙️ Settings';
$s=get_settings($conn);
$success='';

if($_SERVER['REQUEST_METHOD']=='POST'){
    $sn  = mysqli_real_escape_string($conn,trim($_POST['shop_name']??'ShopStock'));
    $sa  = mysqli_real_escape_string($conn,trim($_POST['shop_address']??''));
    $sp  = mysqli_real_escape_string($conn,trim($_POST['shop_phone']??''));
    $se  = mysqli_real_escape_string($conn,trim($_POST['shop_email']??''));
    $tax = floatval($_POST['tax_rate']??0);
    $cur = mysqli_real_escape_string($conn,trim($_POST['currency']??'USD'));
    $sym = mysqli_real_escape_string($conn,trim($_POST['currency_symbol']??'$'));
    $thm = in_array($_POST['theme']??'light',['light','dark'])?$_POST['theme']:'light';

    mysqli_query($conn,"UPDATE settings SET shop_name='$sn',shop_address='$sa',shop_phone='$sp',shop_email='$se',tax_rate=$tax,currency='$cur',currency_symbol='$sym',theme='$thm' WHERE id=1");
    log_activity($conn,"Updated Settings","Shop: $sn");
    $success="✅ Settings saved successfully.";
    $s=get_settings($conn);
}
?>
<!DOCTYPE html><html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?=$page_title?> | <?=$s['shop_name']?></title><link rel="stylesheet" href="../css/dashboard.css"></head>
<body>
<?php include '../includes/admin_sidebar.php'; ?>
<div class="main-area">
<?php include '../includes/topbar.php'; ?>
<div class="content">
<?php if($success):?><div class="alert alert-success"><?=$success?></div><?php endif;?>
<div style="max-width:700px">
<div class="card">
  <div class="card-header"><h3>⚙️ System Settings</h3></div>
  <div class="card-body">
    <form method="POST">
      <h4 style="color:#555;font-size:13px;text-transform:uppercase;letter-spacing:1px;margin-bottom:16px;border-bottom:1px solid #eee;padding-bottom:8px">🏪 Shop Information</h4>
      <div class="form-row">
        <div class="form-group"><label>Shop Name</label><input type="text" name="shop_name" value="<?=htmlspecialchars($s['shop_name']??'ShopStock')?>"></div>
        <div class="form-group"><label>Phone</label><input type="text" name="shop_phone" value="<?=htmlspecialchars($s['shop_phone']??'')?>"></div>
      </div>
      <div class="form-group"><label>Email</label><input type="email" name="shop_email" value="<?=htmlspecialchars($s['shop_email']??'')?>"></div>
      <div class="form-group"><label>Address</label><textarea name="shop_address"><?=htmlspecialchars($s['shop_address']??'')?></textarea></div>
      <h4 style="color:#555;font-size:13px;text-transform:uppercase;letter-spacing:1px;margin:20px 0 16px;border-bottom:1px solid #eee;padding-bottom:8px">💰 Currency & Tax</h4>
      <div class="form-row-3">
        <div class="form-group"><label>Currency Code</label><input type="text" name="currency" value="<?=htmlspecialchars($s['currency']??'USD')?>" maxlength="10"></div>
        <div class="form-group"><label>Currency Symbol</label><input type="text" name="currency_symbol" value="<?=htmlspecialchars($s['currency_symbol']??'$')?>" maxlength="5"></div>
        <div class="form-group"><label>Tax Rate (%)</label><input type="number" name="tax_rate" value="<?=htmlspecialchars($s['tax_rate']??0)?>" min="0" max="100" step="0.01"></div>
      </div>
      <h4 style="color:#555;font-size:13px;text-transform:uppercase;letter-spacing:1px;margin:20px 0 16px;border-bottom:1px solid #eee;padding-bottom:8px">🎨 Appearance</h4>
      <div class="form-group"><label>Theme</label>
        <div style="display:flex;gap:12px;margin-top:6px">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:10px 20px;border:2px solid #e8e8e8;border-radius:8px;font-weight:600;font-size:13px;<?=$s['theme']=='light'?'border-color:#e94560;background:#fff5f6;':''?>">
            <input type="radio" name="theme" value="light" <?=$s['theme']=='light'?'checked':''?> onchange="document.body.classList.remove('dark')"> ☀️ Light Mode
          </label>
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:10px 20px;border:2px solid #e8e8e8;border-radius:8px;font-weight:600;font-size:13px;<?=$s['theme']=='dark'?'border-color:#e94560;background:#fff5f6;':''?>">
            <input type="radio" name="theme" value="dark" <?=$s['theme']=='dark'?'checked':''?> onchange="document.body.classList.add('dark')"> 🌙 Dark Mode
          </label>
        </div>
      </div>
      <div class="form-actions" style="margin-top:24px"><button type="submit" class="btn btn-primary">💾 Save Settings</button></div>
    </form>
  </div>
</div>
</div>
</div></div>
<?php if($s['theme']=='dark'):?><script>document.body.classList.add('dark');</script><?php endif;?>
</body></html>
