<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

use Kickback\Services\Session;
use Kickback\Services\StripeService;

// Basic page shell using existing site components if available
?>
<!DOCTYPE html>
<html lang="en">
<?php require("php-components/base-page-head.php"); ?>
<body class="bg-body-secondary container p-0">
<?php require("php-components/base-page-components.php"); ?>
<main class="container pt-3 bg-body" style="margin-bottom:56px;">
  <div class="row">
    <div class="col-12 col-xl-9">
      <?php $activePageName = "Stripe Connect Demo"; require("php-components/base-page-breadcrumbs.php"); ?>

      <div class="card mb-3">
        <div class="card-body">
          <h5 class="card-title">Connected Account Onboarding</h5>
          <p class="text-muted mb-3">Click to create a connected account and begin onboarding. Status is fetched live from Stripe.</p>
          <div class="d-flex gap-2 mb-3">
            <input id="accountIdInput" class="form-control" placeholder="acct_... (demo only)"/>
            <button id="btnOnboard" class="btn btn-primary">Onboard to collect payments</button>
            <button id="btnStatus" class="btn btn-outline-secondary">Check Status</button>
          </div>
          <div id="onboardResult" class="small text-break"></div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <h5 class="card-title">Create Product (on Connected Account)</h5>
          <div class="row g-2 mb-2">
            <div class="col-md-4"><input id="prodName" class="form-control" placeholder="Name"/></div>
            <div class="col-md-4"><input id="prodDesc" class="form-control" placeholder="Description"/></div>
            <div class="col-md-2"><input id="prodPrice" type="number" class="form-control" placeholder="Price (cents)"/></div>
            <div class="col-md-2"><input id="prodCurrency" class="form-control" placeholder="USD" value="USD"/></div>
          </div>
          <button id="btnCreateProduct" class="btn btn-primary">Create Product</button>
          <div id="productResult" class="small text-break mt-2"></div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <h5 class="card-title">Storefront (List & Buy)</h5>
          <div class="d-flex gap-2 mb-2">
            <button id="btnListProducts" class="btn btn-outline-primary">List Products</button>
            <input id="appFee" class="form-control" style="max-width:200px" placeholder="App Fee (cents)" value="123"/>
          </div>
          <div id="products" class="row row-cols-1 row-cols-md-2 g-3"></div>
        </div>
      </div>
    </div>
    <?php require("php-components/base-page-discord.php"); ?>
  </div>
  <?php require("php-components/base-page-footer.php"); ?>
</main>
<?php require("php-components/base-page-javascript.php"); ?>

<script>
async function jsonPost(url, body) {
  const resp = await fetch(url, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body)});
  return resp.json();
}
async function jsonGet(url) {
  const resp = await fetch(url);
  return resp.json();
}

const accountIdEl = document.getElementById('accountIdInput');
const onboardResult = document.getElementById('onboardResult');
const productResult = document.getElementById('productResult');
const productsEl = document.getElementById('products');

document.getElementById('btnOnboard').onclick = async () => {
  onboardResult.textContent = 'Creating account and generating onboarding link...';
  const out = await jsonPost('<?php echo \Kickback\Common\Version::urlBetaPrefix(); ?>/api/v2/payments/connect/onboard.php', {});
  if (out.success) {
    accountIdEl.value = out.data.account_id;
    onboardResult.innerHTML = 'Account created: <code>'+out.data.account_id+'</code><br/>' +
      '<a href="'+out.data.onboarding_url+'" target="_blank">Start Onboarding</a>';
  } else {
    onboardResult.textContent = out.message;
  }
};

document.getElementById('btnStatus').onclick = async () => {
  const acct = accountIdEl.value.trim();
  if (!acct) { onboardResult.textContent = 'Enter account_id (acct_...) from onboarding step.'; return; }
  const out = await jsonGet('<?php echo \Kickback\Common\Version::urlBetaPrefix(); ?>/api/v2/payments/connect/status.php?account_id='+encodeURIComponent(acct));
  onboardResult.textContent = out.success ? JSON.stringify(out.data, null, 2) : out.message;
};

document.getElementById('btnCreateProduct').onclick = async () => {
  const acct = accountIdEl.value.trim();
  if (!acct) { productResult.textContent = 'Enter account_id (acct_...) first.'; return; }
  const name = document.getElementById('prodName').value.trim();
  const description = document.getElementById('prodDesc').value.trim();
  const price = parseInt(document.getElementById('prodPrice').value || '0', 10);
  const currency = (document.getElementById('prodCurrency').value || 'USD').trim();
  const out = await jsonPost('<?php echo \Kickback\Common\Version::urlBetaPrefix(); ?>/api/v2/payments/connect/create-product.php', {
    account_id: acct, name, description, price, currency
  });
  productResult.textContent = out.success ? 'Created: '+ out.data.id : out.message;
};

document.getElementById('btnListProducts').onclick = async () => {
  const acct = accountIdEl.value.trim();
  if (!acct) { productsEl.innerHTML = '<div class="text-muted">Enter account_id (acct_...)</div>'; return; }
  const out = await jsonGet('<?php echo \Kickback\Common\Version::urlBetaPrefix(); ?>/api/v2/payments/connect/list-products.php?account_id='+encodeURIComponent(acct));
  if (!out.success) { productsEl.innerHTML = '<div class="text-danger">'+out.message+'</div>'; return; }
  const fee = parseInt(document.getElementById('appFee').value || '0', 10);
  productsEl.innerHTML = '';
  (out.data.data || []).forEach(p => {
    const price = p.default_price;
    const amount = price ? price.unit_amount : 0;
    const cur = price ? price.currency.toUpperCase() : 'USD';
    const card = document.createElement('div');
    card.className = 'col';
    card.innerHTML = `
      <div class="card h-100">
        <div class="card-body">
          <h6 class="card-title">${p.name}</h6>
          <p class="card-text small">${p.description || ''}</p>
          <div class="d-flex justify-content-between align-items-center">
            <span class="fw-bold">${(amount/100).toFixed(2)} ${cur}</span>
            <button class="btn btn-sm btn-primary">Buy</button>
          </div>
        </div>
      </div>`;
    card.querySelector('button').onclick = async () => {
      const r = await jsonPost('<?php echo \Kickback\Common\Version::urlBetaPrefix(); ?>/api/v2/payments/connect/create-checkout.php', {
        account_id: acct,
        product_name: p.name,
        unit_amount: amount,
        currency: cur,
        quantity: 1,
        application_fee_amount: fee
      });
      if (r.success) {
        window.location.href = r.data.url;
      } else {
        alert(r.message);
      }
    };
    productsEl.appendChild(card);
  });
};
</script>
</body>
</html>

