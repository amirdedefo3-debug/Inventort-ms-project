<?php
require_once '../includes/auth.php';
require_once '../db.php';
if(!is_cashier()&&!is_admin()){header("Location: ../login.php?error=access_denied");exit;}
$page_title='🛒 Point of Sale';
$s=get_settings($conn);

// Handle sale submission
if($_SERVER['REQUEST_METHOD']=='POST'&&isset($_POST['checkout'])){
    $items    = json_decode($_POST['cart_data']??'[]',true);
    $discount = floatval($_POST['discount']??0);
    $payment  = in_array($_POST['payment'],['cash','card','mobile'])?$_POST['payment']:'cash';
    $paid     = floatval($_POST['amount_paid']??0);

    if(!empty($items)){
        $subtotal=0;
        foreach($items as $it) $subtotal+=floatval($it['price'])*intval($it['qty']);
        $tax_rate = floatval($s['tax_rate']);
        $tax = round(($subtotal-$discount)*($tax_rate/100),2);
        $total = round($subtotal - $discount + $tax, 2);
        $change = max(0, $paid - $total);
        $inv = 'INV-'.date('Ymd').'-'.str_pad(rand(1,9999),4,'0',STR_PAD_LEFT);
        $uid = $_SESSION['user_id'];

        mysqli_query($conn,"INSERT INTO sales (invoice_number,user_id,subtotal,discount,tax,total,payment_method,amount_paid,change_amount) VALUES ('$inv',$uid,$subtotal,$discount,$tax,$total,'$payment',$paid,$change)");
        $sale_id = mysqli_insert_id($conn);

        foreach($items as $it){
            $pid  = intval($it['id']);
            $qty  = intval($it['qty']);
            $price= floatval($it['price']);
            $name = mysqli_real_escape_string($conn,$it['name']);
            $total_price = $qty*$price;
            mysqli_query($conn,"INSERT INTO sale_items (sale_id,product_id,product_name,quantity,unit_price,total_price) VALUES ($sale_id,$pid,'$name',$qty,$price,$total_price)");
            mysqli_query($conn,"UPDATE products SET quantity=quantity-$qty WHERE id=$pid AND quantity>=$qty");
        }
        log_activity($conn,"New Sale","Invoice: $inv, Total: ".$s['currency_symbol']."$total");
        header("Location: receipt.php?id=$sale_id"); exit;
    }
}

$products = mysqli_query($conn,"SELECT p.*,c.name cat FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.quantity>0 ORDER BY p.name");
$categories = mysqli_query($conn,"SELECT * FROM categories ORDER BY name");
?>
<!DOCTYPE html><html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?=$page_title?> | <?=$s['shop_name']?></title>
<link rel="stylesheet" href="../css/dashboard.css">
<style>
.pos-layout{display:grid;grid-template-columns:1fr 360px;gap:20px;height:calc(100vh - 120px);}
.pos-products{overflow-y:auto;}
.product-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;padding:4px;}
.prod-card{background:#fff;border-radius:10px;padding:14px;cursor:pointer;border:2px solid transparent;transition:all .2s;box-shadow:0 2px 6px rgba(0,0,0,.06);text-align:center;}
.prod-card:hover{border-color:#e94560;transform:translateY(-2px);}
.prod-card.out{opacity:.5;cursor:not-allowed;}
.prod-name{font-weight:700;font-size:13px;margin-bottom:4px;color:#1a1a2e;}
.prod-price{color:#e94560;font-weight:700;font-size:15px;}
.prod-stock{font-size:11px;color:#aaa;margin-top:3px;}
.prod-cat{font-size:10px;background:#e8f0fe;color:#3498db;padding:2px 6px;border-radius:10px;display:inline-block;margin-bottom:6px;}
.cart-panel{background:#fff;border-radius:12px;display:flex;flex-direction:column;box-shadow:0 2px 12px rgba(0,0,0,.08);overflow:hidden;}
.cart-header{padding:16px 18px;border-bottom:1px solid #f0f0f0;font-weight:700;font-size:15px;color:#1a1a2e;display:flex;justify-content:space-between;align-items:center;}
.cart-items{flex:1;overflow-y:auto;padding:10px;}
.cart-item{display:flex;align-items:center;gap:10px;padding:10px;border-radius:8px;border-bottom:1px solid #f5f5f5;}
.cart-item-name{font-weight:600;font-size:13px;flex:1;}
.cart-qty{display:flex;align-items:center;gap:6px;}
.qty-btn{width:26px;height:26px;border-radius:6px;border:none;background:#f0f0f0;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;font-weight:700;}
.qty-btn:hover{background:#e94560;color:#fff;}
.qty-input{width:40px;text-align:center;border:1px solid #eee;border-radius:5px;padding:3px;font-size:13px;}
.cart-item-total{font-weight:700;color:#e94560;min-width:60px;text-align:right;}
.rm-btn{background:none;border:none;cursor:pointer;color:#e74c3c;font-size:16px;}
.cart-summary{padding:14px 18px;border-top:1px solid #f0f0f0;background:#fafafa;}
.summary-row{display:flex;justify-content:space-between;font-size:13px;padding:4px 0;color:#666;}
.summary-total{display:flex;justify-content:space-between;font-size:17px;font-weight:700;color:#1a1a2e;padding:10px 0 6px;border-top:1px solid #eee;margin-top:4px;}
.payment-btns{display:grid;grid-template-columns:1fr 1fr 1fr;gap:6px;margin:8px 0;}
.pay-btn{padding:8px;border-radius:8px;border:2px solid #e8e8e8;background:#fff;cursor:pointer;font-size:12px;font-weight:600;color:#555;text-align:center;transition:all .2s;}
.pay-btn.active,.pay-btn:hover{border-color:#e94560;color:#e94560;background:#fff5f6;}
.checkout-btn{width:100%;padding:13px;background:linear-gradient(135deg,#27ae60,#219a52);color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:700;cursor:pointer;margin-top:6px;}
.checkout-btn:hover{opacity:.9;}
.checkout-btn:disabled{background:#ccc;cursor:not-allowed;}
.pos-search{padding:12px;border-bottom:1px solid #f0f0f0;}
.pos-search input{width:100%;padding:9px 13px;border:2px solid #e8e8e8;border-radius:8px;font-size:13px;}
.pos-search input:focus{outline:none;border-color:#e94560;}
.cat-filter{display:flex;gap:6px;overflow-x:auto;padding:10px 14px;border-bottom:1px solid #f0f0f0;}
.cat-btn{flex-shrink:0;padding:5px 12px;border-radius:20px;border:2px solid #e8e8e8;background:#fff;cursor:pointer;font-size:12px;font-weight:600;color:#555;transition:all .2s;white-space:nowrap;}
.cat-btn.active,.cat-btn:hover{background:#e94560;color:#fff;border-color:#e94560;}
.empty-cart{text-align:center;padding:40px 20px;color:#aaa;}
.empty-cart .ei{font-size:40px;margin-bottom:10px;}
</style>
</head>
<body>
<?php include '../includes/cashier_sidebar.php'; ?>
<div class="main-area">
<?php include '../includes/topbar.php'; ?>
<div class="content" style="padding-top:16px;">
<form method="POST" id="saleForm">
<input type="hidden" name="checkout" value="1">
<input type="hidden" name="cart_data" id="cart_data">
<input type="hidden" name="payment" id="payment_method_val" value="cash">

<div class="pos-layout">
  <!-- LEFT: Products -->
  <div class="pos-products card">
    <div class="pos-search">
      <input type="text" id="prodSearch" placeholder="🔍 Search product or scan barcode..." oninput="filterProducts()">
    </div>
    <div class="cat-filter">
      <button type="button" class="cat-btn active" onclick="filterCat('',this)">All</button>
      <?php while($c=mysqli_fetch_assoc($categories)):?>
      <button type="button" class="cat-btn" onclick="filterCat('<?=addslashes($c['name'])?>',this)"><?=htmlspecialchars($c['name'])?></button>
      <?php endwhile;?>
    </div>
    <div class="product-grid" id="productGrid">
      <?php while($p=mysqli_fetch_assoc($products)): ?>
      <div class="prod-card <?=$p['quantity']==0?'out':''?>"
           data-id="<?=$p['id']?>"
           data-name="<?=htmlspecialchars($p['name'],ENT_QUOTES)?>"
           data-price="<?=$p['selling_price']?>"
           data-stock="<?=$p['quantity']?>"
           data-cat="<?=htmlspecialchars($p['cat']??'',ENT_QUOTES)?>"
           onclick="addToCart(this)">
        <?php if($p['cat']):?><div class="prod-cat"><?=htmlspecialchars($p['cat'])?></div><?php endif;?>
        <div class="prod-name"><?=htmlspecialchars($p['name'])?></div>
        <div class="prod-price"><?=$s['currency_symbol']?><?=number_format($p['selling_price'],2)?></div>
        <div class="prod-stock">Stock: <?=$p['quantity']?></div>
      </div>
      <?php endwhile;?>
    </div>
  </div>

  <!-- RIGHT: Cart -->
  <div class="cart-panel">
    <div class="cart-header">
      🛒 Cart <span id="cartCount" style="background:#e94560;color:#fff;border-radius:20px;padding:2px 10px;font-size:13px;">0</span>
      <button type="button" onclick="clearCart()" class="btn btn-xs btn-outline">Clear</button>
    </div>
    <div class="cart-items" id="cartItems">
      <div class="empty-cart"><div class="ei">🛒</div><p>Cart is empty</p><small>Click products to add</small></div>
    </div>
    <div class="cart-summary">
      <div class="summary-row"><span>Subtotal</span><span id="subtotal"><?=$s['currency_symbol']?>0.00</span></div>
      <div class="summary-row">
        <span>Discount</span>
        <span><input type="number" name="discount" id="discountInput" value="0" min="0" step="0.01" style="width:70px;border:1px solid #ddd;border-radius:5px;padding:2px 6px;font-size:13px;" onchange="recalc()"></span>
      </div>
      <?php if($s['tax_rate']>0):?>
      <div class="summary-row"><span>Tax (<?=$s['tax_rate']?>%)</span><span id="taxAmt"><?=$s['currency_symbol']?>0.00</span></div>
      <?php endif;?>
      <div class="summary-total"><span>TOTAL</span><span id="totalAmt"><?=$s['currency_symbol']?>0.00</span></div>
      <div style="font-size:12px;color:#666;margin-bottom:6px;font-weight:600;">PAYMENT METHOD</div>
      <div class="payment-btns">
        <button type="button" class="pay-btn active" id="pm_cash" onclick="setPayment('cash')">💵 Cash</button>
        <button type="button" class="pay-btn" id="pm_card" onclick="setPayment('card')">💳 Card</button>
        <button type="button" class="pay-btn" id="pm_mobile" onclick="setPayment('mobile')">📱 Mobile</button>
      </div>
      <div id="cashSection">
        <div class="summary-row">
          <span>Amount Paid</span>
          <span><input type="number" name="amount_paid" id="amountPaid" value="0" min="0" step="0.01" style="width:80px;border:1px solid #ddd;border-radius:5px;padding:2px 6px;font-size:13px;" oninput="calcChange()"></span>
        </div>
        <div class="summary-row"><span>Change</span><span id="changeAmt" style="color:#27ae60;font-weight:700;"><?=$s['currency_symbol']?>0.00</span></div>
      </div>
      <button type="button" class="checkout-btn" id="checkoutBtn" onclick="checkout()" disabled>
        ✅ Checkout
      </button>
    </div>
  </div>
</div>
</form>
</div>
</div>

<script>
const CURRENCY = '<?=$s['currency_symbol']?>';
const TAX_RATE = <?=$s['tax_rate']?>;
let cart = {};

function addToCart(el){
  if(el.classList.contains('out')) return;
  const id    = el.dataset.id;
  const name  = el.dataset.name;
  const price = parseFloat(el.dataset.price);
  const stock = parseInt(el.dataset.stock);
  if(cart[id]){
    if(cart[id].qty >= stock){alert('Max stock reached!');return;}
    cart[id].qty++;
  } else {
    cart[id] = {id,name,price,stock,qty:1};
  }
  renderCart();
}

function renderCart(){
  const div = document.getElementById('cartItems');
  const keys = Object.keys(cart);
  document.getElementById('cartCount').textContent = keys.reduce((a,k)=>a+cart[k].qty,0);
  if(keys.length===0){
    div.innerHTML='<div class="empty-cart"><div class="ei">🛒</div><p>Cart is empty</p><small>Click products to add</small></div>';
    document.getElementById('checkoutBtn').disabled=true;
    recalc(); return;
  }
  div.innerHTML = keys.map(k=>{
    const it=cart[k];
    return `<div class="cart-item">
      <div class="cart-item-name">${it.name}</div>
      <div class="cart-qty">
        <button type="button" class="qty-btn" onclick="changeQty('${k}',-1)">−</button>
        <input class="qty-input" type="number" value="${it.qty}" min="1" max="${it.stock}" onchange="setQty('${k}',this.value)">
        <button type="button" class="qty-btn" onclick="changeQty('${k}',1)">+</button>
      </div>
      <div class="cart-item-total">${CURRENCY}${(it.price*it.qty).toFixed(2)}</div>
      <button type="button" class="rm-btn" onclick="removeItem('${k}')">🗑️</button>
    </div>`;
  }).join('');
  document.getElementById('checkoutBtn').disabled=false;
  recalc();
}

function changeQty(id,d){
  if(!cart[id]) return;
  cart[id].qty = Math.min(cart[id].stock, Math.max(1, cart[id].qty+d));
  renderCart();
}
function setQty(id,v){
  if(!cart[id]) return;
  cart[id].qty = Math.min(cart[id].stock, Math.max(1, parseInt(v)||1));
  renderCart();
}
function removeItem(id){ delete cart[id]; renderCart(); }
function clearCart(){ cart={}; renderCart(); }

function recalc(){
  const subtotal = Object.values(cart).reduce((a,it)=>a+(it.price*it.qty),0);
  const discount = parseFloat(document.getElementById('discountInput').value)||0;
  const taxable  = Math.max(0, subtotal - discount);
  const tax      = taxable * (TAX_RATE/100);
  const total    = taxable + tax;
  document.getElementById('subtotal').textContent = CURRENCY+subtotal.toFixed(2);
  const taxEl = document.getElementById('taxAmt');
  if(taxEl) taxEl.textContent = CURRENCY+tax.toFixed(2);
  document.getElementById('totalAmt').textContent = CURRENCY+total.toFixed(2);
  calcChange();
}

function calcChange(){
  const total = parseFloat(document.getElementById('totalAmt').textContent.replace(CURRENCY,''))||0;
  const paid  = parseFloat(document.getElementById('amountPaid').value)||0;
  document.getElementById('changeAmt').textContent = CURRENCY+Math.max(0,paid-total).toFixed(2);
}

let currentPayment='cash';
function setPayment(m){
  currentPayment=m;
  document.getElementById('payment_method_val').value=m;
  ['cash','card','mobile'].forEach(p=>document.getElementById('pm_'+p).classList.remove('active'));
  document.getElementById('pm_'+m).classList.add('active');
  document.getElementById('cashSection').style.display = m==='cash'?'block':'none';
}

function checkout(){
  if(Object.keys(cart).length===0){alert('Cart is empty!');return;}
  document.getElementById('cart_data').value = JSON.stringify(Object.values(cart));
  document.getElementById('saleForm').submit();
}

function filterProducts(){
  const q = document.getElementById('prodSearch').value.toLowerCase();
  document.querySelectorAll('.prod-card').forEach(c=>{
    const name = c.dataset.name.toLowerCase();
    c.style.display = name.includes(q)?'':'none';
  });
}

function filterCat(cat,btn){
  document.querySelectorAll('.cat-btn').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.prod-card').forEach(c=>{
    c.style.display = (!cat||c.dataset.cat===cat)?'':'none';
  });
}
</script>
</body></html>
