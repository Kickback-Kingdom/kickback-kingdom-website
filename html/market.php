<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use \Kickback\Backend\Controllers\StoreController;
use \Kickback\Backend\Controllers\ProductController;
use \Kickback\Backend\Controllers\CartController;
use \Kickback\Backend\Config\StoreCategory;
use \Kickback\Backend\Views\vRecordId;
use \Kickback\Backend\Views\vMedia;
use \Kickback\Services\Session;
use \Kickback\Backend\Config\StoreTag;

$products = [];
$store = null;
$cart = null;




$account = Session::getCurrentAccount();
$locator = $_GET["store-locator"] ?? "kickback-market";

$storeResp = StoreController::getStoreByLocator($locator);
if (!$storeResp || !$storeResp->success || empty($storeResp->data)) {
    throw new Exception("Failed to retrieve store with locator: $locator - {$storeResp->message}");
}

$store = $storeResp->data[0];

// Retrieve products for the store
$productsResp = $store->products;




if (Session::isLoggedIn()) {
    
    // Retrieve cart for the account and store
    $cartResp = StoreController::getCartForAccount($account, $store);

    if (!$cartResp || !$cartResp->success) {
        throw new Exception("Failed to retrieve cart for account: {$cartResp->message}");
    }

    $cart = $cartResp->data;
}



?>

<!DOCTYPE html>
<html lang="en">


<?php require("php-components/base-page-head.php"); ?>

<body class="bg-body-secondary container p-0">
    
    <?php 
    
    require("php-components/base-page-components.php"); 
    
    require("php-components/ad-carousel.php"); 
    
    ?>
    
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@600&display=swap');

/* --- BASE STYLES --- */
.emberwood-store {
  position: relative;
  background-color: #000;
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  border: 2px solid #33ffee44;
  border-radius: 12px;
  box-shadow: 0 0 30px #00ffc855 inset, 0 0 10px #00ffc822;
  font-family: 'Orbitron', sans-serif;
  color: #cceeff;
  padding-top: 40px;
  overflow: hidden;
  transition: background-image 0.5s ease;
}

.emberwood-store::after {
  content: "";
  pointer-events: none;
  position: absolute;
  top: 0; left: 0;
  width: 100%; height: 100%;
  z-index: 1;
  background: repeating-linear-gradient(to bottom, rgba(255,255,255,0.03) 1px, transparent 4px);
  opacity: 0.08;
  mix-blend-mode: overlay;
  animation: scanScroll 8s linear infinite, scanPulse 5s ease-in-out infinite;
}

/* Theme backgrounds */

<?= StoreCategory::getThemeCss(); ?>

/* --- ANIMATIONS --- */
@keyframes scanScroll { from { background-position: 0 0; } to { background-position: 0 100%; } }
@keyframes scanPulse { 0%, 100% { opacity: 0.08; } 50% { opacity: 0.14; } }
@keyframes flicker {
  0%, 19%, 21%, 23%, 25%, 54%, 56%, 100% { opacity: 1; }
  20%, 22%, 55% { opacity: 0.7; }
}

/* --- GRID --- */
.store-header { text-align: center; margin-bottom: 2rem; }
.store-logo { max-width: 100%; }

.store-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 1rem;
  padding: 0 1rem 2rem;
  justify-items: center;
}

@media (max-width: 1400px) { .store-grid { grid-template-columns: repeat(4, 1fr); } }
@media (max-width: 1200px) { .store-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 900px)  { .store-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 576px)  { .store-grid { grid-template-columns: 1fr; } }

/* --- CARD & FLIP --- */
.card-flip {
  position: relative;
  width: 100%;
  max-width: 220px;
  aspect-ratio: 2 / 3;
  margin: 0 auto;
  perspective: 1000px;
}

.flip-inner {
  width: 100%;
  height: 100%;
  position: relative;
  transform-style: preserve-3d;
  transition: transform 0.8s ease;
}

.card-flip.flipped .flip-inner { transform: rotateY(180deg); }

.item-front, .item-back {
  position: absolute;
  top: 0; left: 0;
  width: 100%;
  height: 100%;
  backface-visibility: hidden;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 1rem;
  border: 2px solid #3af7ff33;
  border-radius: 10px;
  box-shadow: 0 0 10px #0ff2;
  background: #111d22;
}

.item-front {
  transform: rotateY(0deg);
  z-index: 2;
}
.item-back {
  transform: rotateY(180deg);
  color: #9fcedd;
  overflow: hidden;
}

/* --- INTERACTIONS --- */
.card-flip:hover .item-front {
  box-shadow: 0 0 15px #00ffee, 0 0 10px #ffee00 inset;
}

/* --- IMAGE --- */
.item-frame {
  height: 180px;
  overflow: hidden;
  display: flex;
  justify-content: center;
  align-items: center;
}

.tilt-perspective {
  perspective: 600px;
  width: 100%;
  height: 100%;
}

.image-tilt-wrapper {
  transition: transform 0.3s ease;
  will-change: transform;
}

.item-image {
  max-width: 100%;
  height: auto;
  object-fit: contain;
  transform-style: preserve-3d;
  transition: transform 0.3s ease, filter 0.3s ease;
  will-change: transform, filter;
}

/* --- CONTENT --- */
.item-card, .item-info {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.item-title {
  font-size: 1rem;
  color: #ffeeaa;
  text-shadow: 0 0 4px #ffaa00aa;
  animation: flicker 3s infinite;
  margin: 0.75rem 0 0.5rem;
}

.item-desc {
  flex-grow: 1;
  overflow-y: auto;
  font-size: 0.8rem;
  line-height: 1.4;
  margin-bottom: 0.75rem;
  padding-right: 4px;
  -webkit-overflow-scrolling: touch;
}

/* --- STOCK & TAG --- */
.item-stock {
  position: absolute;
  top: 8px;
  right: 8px;
  background: rgba(0, 0, 0, 0.8);
  color: #33f7ff;
  padding: 2px 6px;
  font-size: 0.75rem;
  border-radius: 4px;
  z-index: 10;
}

.item-ribbon {
  position: absolute;
  top: 0;
  left: 0;
  background: #ff3366;
  color: #fff;
  font-size: 0.65rem;
  font-weight: bold;
  padding: 4px 8px;
  border-bottom-right-radius: 6px;
  box-shadow: 0 0 6px #ff336688;
  text-transform: uppercase;
  z-index: 10;
}
<?= StoreTag::getTagCss(); ?>

/* --- FOOTER --- */
.item-footer {
  margin-top: auto;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.5rem;
  padding-top: 0.5rem;
}

/* --- PRICE --- */
.price-text {
  font-weight: bold;
  font-size: 0.9rem;
  color: #33f7ff;
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
}

.currency-icon {
  width: 16px;
  height: 16px;
  object-fit: contain;
}

/* --- BUTTONS --- */
.buy-btn, .flip-btn-icon {
  width: 38px;
  height: 38px;
  background: #111;
  border-radius: 50%;
  font-size: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.25s ease;
  box-shadow: inset 0 0 4px #000;
}

.buy-btn {
  color: #ffee00;
  border: 2px solid #ffee00aa;
  box-shadow: 0 0 6px #ffee00aa, inset 0 0 4px #000;
}
.buy-btn:hover {
  background: #ffee00;
  color: #000;
  transform: scale(1.05) translateY(-1px);
  box-shadow: 0 0 12px #ffee00cc, inset 0 0 6px #000;
}
.buy-btn:active {
  transform: scale(0.95);
  box-shadow: 0 0 4px #ffee00aa, inset 0 0 8px #000;
}

.flip-btn-icon {
  color: #77c8ff;
  border: 2px solid #77c8ff88;
  box-shadow: 0 0 6px #77c8ff66, inset 0 0 4px #000;
  margin-bottom: 0.5rem;
}
.flip-btn-icon:hover {
  background: #77c8ff;
  color: #000;
  transform: scale(1.05) translateY(-1px);
  box-shadow: 0 0 10px #77c8ffcc, inset 0 0 5px #000;
}
.flip-btn-icon:active {
  transform: scale(0.95);
  box-shadow: 0 0 4px #77c8ff88, inset 0 0 8px #000;
}

/* --- CATEGORY FILTER --- */
.category-pills {
  display: flex;
  justify-content: center;
  gap: 0.75rem;
  flex-wrap: wrap;
  margin-bottom: 1.5rem;
}
.pill-btn {
  padding: 6px 16px;
  border-radius: 999px;
  background-color: #112233;
  color: #cceeff;
  border: 2px solid #33ffeeaa;
  font-weight: bold;
  font-size: 0.85rem;
  cursor: pointer;
  transition: all 0.3s ease;
}

.pill-btn:hover:not(.active) {
  background-color: #1a3344;
  border-color: #55ffff;
  color: #99eeff;
  box-shadow: 0 0 6px #33ffee88;
}

.pill-btn.active {
  background-color: #33ffee;
  color: #000;
}


    </style>



<!-- HTML -->
<main class="container pt-3 bg-body" style="margin-bottom: 56px;">
  <div class="row">
    <div class="col-12">
      <section class="emberwood-store theme-default">
        <header class="store-header">
          <img src="/assets/images/emberwoodtradingco.png" class="store-logo" alt="Emberwood Trading Company">
          <div class="category-pills">
            <?= StoreCategory::renderCategoryPills() ?>
          </div>

          <div id="category-flavor-text" style="margin-top: 0.5rem; font-size: 0.95rem; color: #99eeff; text-shadow: 0 0 3px #33ffee66;">
            <?= htmlspecialchars(StoreCategory::getCategory('all')['description']) ?>
          </div>

        </header>
        <div class="store-grid">
          <?php foreach ($products as $product): ?>
            <?php
              $priceAmount = $product->price->smallCurrencyUnit ?? 0;
              $isAda = !isset($product->currency_item) || $product->currency_item->crand < 0;
              $formattedAmount = $isAda ? number_format((float)$priceAmount, 2) : (int)$priceAmount;
              $currencyDisplay = $isAda
                ? "<span class='price-text'>{$formattedAmount} ₳</span>"
                : "<span class='price-text'>{$formattedAmount} <img src='" . $product->currency_item->iconSmall->getFullPath() . "' class='currency-icon' alt='Currency'></span>";
              $imageUrl = $product->ref_large_image_path->getFullPath() ?? "/assets/media/default.png";
              $altText = htmlspecialchars($product->name);
              $descText = htmlspecialchars($product->description);
              $stockCount = "?";//$product->stock_quantity ?? null;
              $stockLabel = is_null($stockCount) ? "" : "<div class='item-stock'>In Stock: $stockCount</div>";
              $tagSlug = StoreTag::getRandomTagSlug();//strtolower($product->label ?? '');
              $ribbonHtml = StoreTag::renderRibbon($tagSlug);

              $categoryList = StoreCategory::getRandomCategorySlugList(2);//array_map('trim', explode(',', strtolower($product->category ?? 'general')));
              $categoryAttr = htmlspecialchars(implode(' ', $categoryList));


              
            ?>
            <div class="card-flip" data-category="<?= $categoryAttr ?>">
              <div class="flip-inner">
                <!-- FRONT -->
                <div class="item-card item-front">
                <?= $ribbonHtml ?>

                <?= $stockLabel ?>
                <div class="item-frame" onclick="flipCard(this)">
                  <div  class="tilt-perspective">
                    <img src="<?= $imageUrl ?>" alt="<?= $altText ?>" class="item-image">
                  </div>
                </div>

                  <div class="item-info">
                    <h3 class="item-title"><?= $altText ?></h3>
                    <div class="item-footer">
                      <button class="flip-btn-icon" onclick="flipCard(this)" title="View Details">
                        <i class="fas fa-eye"></i>
                      </button>
                      <div style="display: flex; align-items: center; gap: 0.5rem; margin-left: auto;">
                        <?= $currencyDisplay ?>
                        <button class="buy-btn"
                                data-product-ctime="<?= htmlspecialchars($product->ctime) ?>"
                                data-product-crand="<?= htmlspecialchars($product->crand) ?>"
                                title="Add to Cart">
                          <i class="fas fa-cart-plus"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- BACK -->
                <div class="item-back">
                  <p class="item-desc"><?= $descText ?></p>
                  <button class="flip-btn-icon" onclick="flipCard(this)" title="Back">
                    <i class="fas fa-undo"></i>
                  </button>
                </div>

              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div id="no-results-message" style="text-align: center; padding: 2rem; font-size: 1rem; color: #ffeeaa; display: none;">
          Nothing in stock here... yet. Check back soon, traveler.
        </div>

      </section>
    </div>
  </div>
  <?php require("php-components/base-page-footer.php"); ?>
</main>






    <script>
        (function(){
            const productsGrid = document.getElementById('products-grid');
            const storeCtime = productsGrid.dataset.storeCtime;
            const storeCrand = productsGrid.dataset.storeCrand;
            const cartCtime = productsGrid.dataset.cartCtime;
            const cartCrand = productsGrid.dataset.cartCrand;

            async function refreshProducts()
            {
                console.log("refresh products");
            }

            document.addEventListener('submit', async(event) =>
            {
                if(!event.target.matches('.add-product-to-cart')) return;

                event.preventDefault();

                try
                {
                    const formData = new FormData(event.target);

                    formData.append("storeCtime", storeCtime);
                    formData.append("storeCrand", storeCrand);
                    formData.append("cartCtime", cartCtime);
                    formData.append("cartCrand", cartCrand);

                    const response = await fetch('/php-components/store/add-to-cart.php',
                        {
                            method: 'POST',
                            body: formData
                        }
                    )

                    if(response.ok)
                    {
                        const respJson = await response.json();
                        const message = respJson.message;

                        if(respJson.success === true)
                        {
                            const modalBody = document.getElementById("successModalMessage");

                            modalBody.textContent = "Successfully Product Added To Cart";

                            const modal = new bootstrap.Modal(document.getElementById("successModal"));
                            modal.show();

                            await refreshProducts();
                        }
                        else
                        {
                            const modalBody = document.getElementById("errorModalMessage");

                            modalBody.textContent = "Error Adding Product to Cart : " + message;

                            const modal = new bootstrap.Modal(document.getElementById("errorModal"));
                            modal.show();

                            console.error("Failed to product to cart", message);
                        }  
                    }
                    else
                    {
                        const modalBody = document.getElementById("errorModalMessage");

                        modalBody.textContent = "An Error Occurred Attempting to Add Product To Cart";

                        const modal = new bootstrap.Modal(document.getElementById("errorModal"));
                        modal.show();

                        console.error("Failed to product to cart : ", await response.text());
                    }

                    
                }
                catch(e)
                {
                    console.error("Exception caught while adding product to cart", e)
                }
            })
        })();
        
    </script>

    <!-- ERROR MODAL -->
        <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header text-bg-danger">
                        <h1 class="modal-title fs-5" id="errorModalLabel">Modal title</h1>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p id="errorModalMessage"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn bg-ranked-1" data-bs-dismiss="modal">Okay</button>
                    </div>
                </div>
            </div>
        </div> 

        <!-- SUCCESS MODAL -->
        <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="successModalLabel">Modal title</h1>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p id="successModalMessage"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn bg-ranked-1" data-bs-dismiss="modal">Okay</button>
                    </div>
                </div>
            </div>
        </div>

    
    <?php require("php-components/base-page-javascript.php"); ?>
    <script>

function flipCard(button) {
  const card = button.closest('.card-flip');
  card.classList.toggle('flipped');
}

document.querySelectorAll('.item-frame').forEach(frame => {
  const tiltTarget = frame.querySelector('.item-image');
  if (!tiltTarget) return;

  const maxRotate = 45;
  const maxBrightness = 0.5;
  const minBrightness = 1.2;

  frame.addEventListener('mousemove', (e) => {
    const rect = frame.getBoundingClientRect();
    const offsetX = e.clientX - rect.left;
    const offsetY = e.clientY - rect.top;
    const centerX = rect.width / 2;
    const centerY = rect.height / 2;

    const normX = (offsetX - centerX) / centerX;
    const normY = (offsetY - centerY) / centerY;

    const rotateX = normY * -maxRotate;
    const rotateY = normX * maxRotate;

    const brightness = maxBrightness - ((rotateX + maxRotate) / (2 * maxRotate)) * (maxBrightness - minBrightness);

    tiltTarget.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
    tiltTarget.style.filter = `brightness(${brightness.toFixed(2)})`;
    tiltTarget.style.transition = 'transform 0.1s ease-out, filter 0.1s ease-out';
  });

  frame.addEventListener('mouseleave', () => {
    tiltTarget.style.transform = 'rotateX(0deg) rotateY(0deg)';
    tiltTarget.style.filter = 'brightness(1)';
    tiltTarget.style.transition = 'transform 0.3s ease, filter 0.3s ease';
  });
});


const flavorText = document.getElementById('category-flavor-text');

document.querySelectorAll('.pill-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const category = btn.dataset.category;
    const theme = btn.dataset.theme;

    // Toggle active pill
    document.querySelectorAll('.pill-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    // Change store theme
    const store = document.querySelector('.emberwood-store');
    store.className = 'emberwood-store';
    if (theme) store.classList.add('theme-' + theme);

    // Update flavor text (from description stored in button title)
    const description = btn.getAttribute('title');
    if (flavorText) flavorText.textContent = description || '';

    // Filter product cards
    let anyVisible = false;
    document.querySelectorAll('.card-flip').forEach(card => {
      const itemCats = (card.dataset.category || 'general').split(/\s+/);
      const show = category === 'all' || itemCats.includes(category);
      card.style.display = show ? 'block' : 'none';
      if (show) anyVisible = true;
    });

    const noResults = document.getElementById('no-results-message');
    if (noResults) {
      noResults.style.display = anyVisible ? 'none' : 'block';
    }
  });
});

    </script>

</body>

</html>
