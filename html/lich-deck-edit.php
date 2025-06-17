<?php
declare(strict_types=1);

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");


use Kickback\Backend\Controllers\LootController;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vLichCard;
use Kickback\Backend\Views\vRecordId;
use Kickback\Services\Session;

$canEditDeck = false;
if (!isset($thisLichDeckData))
{

    if (isset($_GET["locator"]))
    {
        $lootId = (int)$_GET["locator"];
        
        // Retrieve the Lich Card by locator
        $response = LootController::getLootById(new vRecordId('', $lootId));

        if ($response->success) {
            $thisLichDeckData = $response->data;

            if (Session::isLoggedIn() && $thisLichDeckData->ownerId->crand == Session::getCurrentAccount()->crand)
            {
                $canEditDeck = true;
            }
        }
    }
}

if (!isset($thisLichDeckData))
{
    if (Session::isLoggedIn())
    {
        Session::Redirect("/u/".Session::getCurrentAccount()->username);
    }
    else{
        
        Session::Redirect("/");
    }
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

    <!-- Save Deck Modal -->
    <div class="modal fade" id="saveDeckModal" tabindex="-1" aria-labelledby="saveDeckModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="saveDeckModalLabel"><i class="fa-solid fa-floppy-disk me-2"></i>Save Deck</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-4">
                        <!-- Deck Box Image -->
                        <div class="col-md-4 text-center">
                            <img src="<?= $thisLichDeckData->item->iconBig->getFullPath(); ?>" alt="Deck Box" class="img-fluid" style="max-height: 220px;">
                        </div>
                    
                        <!-- Form -->
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="deckName" class="form-label">Deck Name</label>
                                <input type="text" class="form-control" id="deckName" placeholder="Enter a name for your deck" value="<?= $thisLichDeckData->nickname; ?>">
                            </div>
                            <div class="mb-3">
                                <label for="deckDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="deckDescription" rows="4" placeholder="Describe your deck's theme, strategy, etc."><?= $thisLichDeckData->description; ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <span class="text-muted small">Your deck will be saved to your collection.</span>
                    <button type="button" class="btn btn-success" onclick="ConfirmSaveDeck()">Save Deck</button>
                </div>
            </div>
        </div>
    </div>


    <!--MAIN CONTENT-->
    <main class="container pt-3 bg-body" style="margin-bottom: 56px;">
        <div class="row">
            <div class="col-12 col-xl-9">
                
                
                <?php 
                
                
                $activePageName = "L.I.C.H. Deck Editor";
                require("php-components/base-page-breadcrumbs.php"); 
                
                ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <h4 class="mb-3">Deck Summary - <?= $thisLichDeckData->GetName(); ?>
                            <?php if ($canEditDeck) { ?>
                                <button class="btn bg-ranked-1 me-2 float-end" data-bs-toggle="modal" data-bs-target="#saveDeckModal">
                                <i class="fa-solid fa-pen-to-square"></i> Edit Deck Name
                                </button>
                            <?php } ?>
                        </h4>
                        <div class="card bg-dark text-white shadow-sm p-3">
                            <div class="row">
                                <div class="col-md-3">
                                    <strong>Overall Stats:</strong>
                                    <ul class="list-unstyled mb-0" id="deckOverallStats">
                                        <!-- Populated by JS -->
                                    </ul>
                                </div>
                                <div class="col-md-3">
                                    <strong>Types:</strong>
                                    <ul class="list-unstyled mb-0" id="deckStatTypes">
                                        <!-- Populated by JS -->
                                    </ul>
                                </div>
                                <div class="col-md-3">
                                    <strong>Source Costs:</strong>
                                    <ul class="list-unstyled mb-0" id="deckStatCosts">
                                        <!-- Populated by JS -->
                                    </ul>
                                </div>
                                <div class="col-md-3">
                                    <strong>Source Types:</strong>
                                    <ul class="list-unstyled mb-0" id="deckStatSourceTypes">
                                        <!-- Populated by JS -->
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="row">
                    <!-- Your Deck -->
                    <div class="col-12 mb-4">
                        <div class="card border-0 shadow-sm bg-white">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fa-solid fa-layer-group me-2"></i>Your Deck
                                </h5>
                                <div>
                                    <span class="badge bg-light text-dark me-2" id="deckCardCount">0 Cards</span>
                                    <button 
                                        class="btn btn-sm btn-light"
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#deckCollapse"
                                        aria-expanded="true"
                                        aria-controls="deckCollapse"
                                    >
                                        <i class="fa-solid fa-chevron-down"></i>
                                    </button>
                                </div>
                            </div>
                            <div id="deckCollapse" class="collapse show">
                                <div class="card-body p-3" id="cardDeckContainer"  style="transition: height 1s ease;">
                                    <div id="deckEditorSelectedCards" class="inventory-grid-sm deck-editor-grid">
                                        <!-- Cards added to deck go here -->
                                    </div>
                                    <div id="deckLoadingSpinner" class="text-center py-5">
                                        <div class="spinner-border text-secondary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mt-3 text-muted">Loading your deck...</p>
                                    </div>
                                    <div id="deckEditorSelectedCardsEmptyMessage" class="text-center text-muted my-4 d-none">
                                        <!-- JS will populate this -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($canEditDeck) { ?>
                    <!-- Card Binder Picker-->
                    <div class="col-12 mb-4">
                        <div class="card border-0 shadow-sm bg-white">
                            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fa-solid fa-book me-2"></i>Open a Card Binder
                                </h5>
                                <div>
                                    <span class="badge bg-light text-dark me-2" id="bindersCount">0 Binders</span>
                                    <button 
                                        class="btn btn-sm btn-light"
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#bindersCollapse"
                                        aria-expanded="true"
                                        aria-controls="bindersCollapse"
                                    >
                                        <i class="fa-solid fa-chevron-down"></i>
                                    </button>
                                </div>
                            </div>
                            <div id="bindersCollapse" class="collapse show">
                                <div class="card-body p-3" id="cardBindersContainer" style="max-height: 500px; overflow-y: auto;">
                                    <div id="deckEditorAvailableBinders" class="inventory-grid-sm deck-editor-grid"></div>
                                    <div id="bindersLoadingSpinner" class="text-center py-5">
                                        <div class="spinner-border text-secondary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mt-3 text-muted">Loading your card binders...</p>
                                    </div>
                                    <div id="deckEditorAvailableBindersEmptyMessage" class="text-center text-muted my-4 d-none">
                                        <!-- JS will populate this -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Binder -->
                    <div class="col-12 mb-4">
                        <div class="card border-0 shadow-sm bg-white">
                            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fa-solid fa-book me-2"></i>Card Binder
                                </h5>
                                <div>
                                    <span class="badge bg-light text-dark me-2" id="binderCardCount">0 Cards</span>
                                    <button 
                                        class="btn btn-sm btn-light d-none"
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#binderCollapse"
                                        aria-expanded="true"
                                        aria-controls="binderCollapse"
                                    >
                                        <i class="fa-solid fa-chevron-down"></i>
                                    </button>
                                    <button id="binder-close-button" class="btn btn-danger pull-right" onclick="CloseCardBinder();">Close Binder</button>
                                </div>
                            </div>
                            <div id="binderCollapse" class="collapse show">
                                <div class="card-body p-3" id="cardBinderContainer" style="max-height: 500px; overflow-y: auto;">
                                    <div id="deckEditorAvailableCards" class="inventory-grid-sm deck-editor-grid"></div>
                                    <div id="binderLoadingSpinner" class="text-center py-5">
                                        <div class="spinner-border text-secondary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mt-3 text-muted">Loading your card binder...</p>
                                    </div>
                                    <div id="deckEditorAvailableCardsEmptyMessage" class="text-center text-muted my-4 d-none">
                                        <!-- JS will populate this -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>




            </div>
            
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>

    
    <?php require("php-components/base-page-javascript.php"); ?>
    <script>
        

        let allCards = [];
        let deckCards = [];
        let allBinders = [];
        var currentOpenedBinder = null;
        var currentOpenedDeck = <?= $thisLichDeckData->itemLootId->crand; ?>;


        function LoadDeck() {
            
            clearMessageInContainer("deckEditorSelectedCardsEmptyMessage")
            document.getElementById('deckLoadingSpinner').style.display = 'block';
            document.getElementById('deckEditorSelectedCards').innerHTML = '';

            LoadContainerLoot(currentOpenedDeck, function(success, cards) {
                if (success) {
                    deckCards = cards;
                    
                    deckCards.forEach(stack => {
                        AddItemIfNotExists(stack.item);
                    });
                    RenderCurrentDeck();
                }
                else
                {
                    StopLoadingSpinner('deckLoadingSpinner');
                    showMessageInContainer(
                        'deckEditorSelectedCardsEmptyMessage',
                        'fa-triangle-exclamation',
                        'Failed to load your deck.',
                        'The magic seems to have faltered... Try again soon. <br/>'+data.message
                    );
                }
            });
        }
        LoadDeck();

        <?php if ($canEditDeck) { ?>
        LoadMyBinders();
        //LoadCardBinder(135);
        function LoadMyBinders() {
            
            CloseCardBinder();
            document.getElementById('bindersLoadingSpinner').style.display = 'block';
            document.getElementById('deckEditorAvailableBinders').innerHTML = '';
            
            const data = {
                sessionToken: "<?= $_SESSION['sessionToken']; ?>",
            };

            const params = new URLSearchParams(data);

            fetch(`/api/v1/lich/get-my-binders.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: params,
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success && Array.isArray(data.data)) {
                        allBinders = data.data;

                        
                        allBinders.forEach(stack => {
                            AddItemIfNotExists(stack.item);
                        });
                        RenderMyBinders();

                        if (allBinders.length == 1)
                        {
                            OpenCardBinder(allBinders[0].item.crand, allBinders[0].itemLootId.crand);
                        }
                    } else {
                        StopLoadingSpinner('bindersLoadingSpinner');
                        showMessageInContainer(
                            'deckEditorAvailableBindersEmptyMessage',
                            'fa-triangle-exclamation',
                            'Failed to load your binder.',
                            'The magic seems to have faltered... Try again soon. <br/>'+data.message
                        );
                    }
                })
                .catch(err => {
                    console.error(err);
                    StopLoadingSpinner('bindersLoadingSpinner');
                    showMessageInContainer(
                        'deckEditorAvailableBindersEmptyMessage',
                        'fa-triangle-exclamation',
                        'An error occurred.',
                        'Dark forces prevented the binder from opening. <br/>'+err
                    );
                });
        }

        function LoadCardBinder(containerLootId) {
            clearMessageInContainer("deckEditorAvailableCardsEmptyMessage")
            document.getElementById('binderLoadingSpinner').style.display = 'block';
            document.getElementById('deckEditorAvailableCards').innerHTML = '';
            
            LoadContainerLoot(containerLootId, function(success, cards) {
                if (success) {
                    allCards = cards;
                    
                    allCards.forEach(stack => {
                        AddItemIfNotExists(stack.item);
                    });
                    RenderOpenedBinder();
                    currentOpenedBinder = containerLootId;

                    
                    var binderEl = document.getElementById(`binder-loot-icon-${currentOpenedBinder}`);
                    binderEl.classList.add("fa-book-open-cover");
                    binderEl.classList.remove("fa-book-blank");

                    
                    binderEl = document.getElementById(`binder-loot-icon-button-${currentOpenedBinder}`);
                    binderEl.classList.add("bg-ranked-1");
                    binderEl.classList.remove("btn-primary");

                    var binder = allBinders.find(b => b.itemLootId.crand === currentOpenedBinder);

                    document.getElementById("binderCardCount").innerText = `${binder.amount} Cards`;
                    document.getElementById("binder-close-button").classList.remove("d-none");
                }
                else
                {
                    StopLoadingSpinner('binderLoadingSpinner');
                    showMessageInContainer(
                        'deckEditorAvailableCardsEmptyMessage',
                        'fa-triangle-exclamation',
                        'Failed to load your binder.',
                        'The magic seems to have faltered... Try again soon. <br/>'+data.message
                    );
                }
            });
        }

        function TransferCard(cardLootId, toContainerLootId, callback = null) {
            if (currentOpenedBinder == null)
            {
                ShowPopError("Please open a card binder before removing cards from your deck!","No card binder opened");
                return;
            }

            TransferLootIntoContainer(cardLootId, toContainerLootId, function(success, message) {
                if (!success) {
                    
                    ShowPopError(data.message,"Failed to transfer card");
                }

                if (callback != null)
                    callback(success);
            });
        }

        function ConfirmSaveDeck() {
            const name = document.getElementById("deckName").value.trim();
            const description = document.getElementById("deckDescription").value.trim();

            if (!name) {
                ShowPopError("Please enter a name for your deck.","Missing Information");
                return;
            }

            GiveLootNickname(currentOpenedDeck, name, description, function(success, message) {
                if (!success) {
                    
                    ShowPopError(message,"Failed to edit deck");
                }
                else
                {
                    $("#saveDeckModal").modal("hide");
                }
            });
        }

        function playCardAnimation(el, animationClass, callback = null) {
            if (!el) return;

            // Remove previous animation classes
            el.classList.remove('animate__animated', animationClass);

            // Force reflow to reset animation
            void el.offsetWidth;

            // Add the animation class
            el.classList.add('animate__animated', animationClass);

            // Clean up after animation ends and call the callback
            el.addEventListener('animationend', function handleAnimEnd() {
                el.classList.remove('animate__animated', animationClass);
                el.removeEventListener('animationend', handleAnimEnd);

                if (typeof callback === 'function') {
                    callback();
                }
            });
        }

        function TransferCardFromBinderToDeck(cardItemId) {
            const binderStack = allCards.find(s => s.item.crand === cardItemId);
            if (!binderStack || binderStack.amount <= 0) return;
            var cardLootId = binderStack.lootStack[0].crand;

            TransferCard(cardLootId, currentOpenedDeck, function(success) {
                console.log(success);
                if (success)
                    AddCardToDeck(cardItemId, cardLootId);
            });
        }

        function TransferCardFromDeckToBinder(cardItemId) {
            const deckStack = deckCards.find(s => s.item.crand === cardItemId);
            if (!deckStack || deckStack.amount <= 0) return;
            var cardLootId = deckStack.lootStack[0].crand;

            TransferCard(cardLootId, currentOpenedBinder, function(success) {
                console.log(success);
                if (success)
                    AddCardToBinder(cardItemId, cardLootId);
            });
        }

        function AddCardToDeck(itemId, lootId) {
            const binderStack = allCards.find(s => s.item.crand === itemId);
            if (!binderStack || binderStack.amount <= 0) return;
            const cardToAdd = binderStack.lootStack.find(l => l.crand === lootId);
            
            binderStack.lootStack = arrayRemoveItem(binderStack.lootStack, cardToAdd);
            binderStack.amount--;

            let deckStack = deckCards.find(s => s.item.crand === itemId);
            if (!deckStack) {
                deckStack = { item: binderStack.item, amount: 1, lootStack: [cardToAdd], nextLootId: {ctime: '', crand: lootId}, itemLootId: {ctime: '', crand: lootId} };
                deckCards.push(deckStack);

                const container = document.getElementById('deckEditorSelectedCards');
                container.insertAdjacentHTML('beforeend', CardHTML(deckStack, true));

                const newCard = document.getElementById(`deck-card-${itemId}`);
                playCardAnimation(newCard, "animate__fadeInUp");
                initializeTooltipsInElement(newCard);


            } else {
                deckStack.amount++;
                deckStack.lootStack.push(cardToAdd);
                const countEl = document.getElementById(`deck-count-${itemId}`);
                if (countEl) countEl.innerText = `x${deckStack.amount}`;

                
                const cardEl = document.getElementById(`deck-card-${itemId}`);
                if (cardEl) playCardAnimation(cardEl, "animate__headShake");
            }
            // Update binder
            if (binderStack.amount === 0) {
                const cardEl = document.getElementById(`binder-card-${itemId}`);
                if (cardEl) {
                    playCardAnimation(cardEl, 'animate__fadeOutUpBig', () => {
                        cardEl.remove();
                        allCards = allCards.filter(s => s.item.crand !== itemId);
                        UpdateDeckStats();
                    });
                }
            } else {
                document.getElementById(`binder-count-${itemId}`).innerText = `x${binderStack.amount}`;
                UpdateDeckStats();
            }

            const binder = allBinders.find(b => b.itemLootId.crand === currentOpenedBinder);
            binder.amount--;
            document.getElementById(`binder-count-${currentOpenedBinder}`).innerHTML = `<i class="fa-solid fa-box"></i> ${binder.amount}`;
            document.getElementById("binderCardCount").innerText = `${binder.amount} Cards`;
        }

        function AddCardToBinder(itemId, lootId) {
            const deckStack = deckCards.find(s => s.item.crand === itemId);
            if (!deckStack || deckStack.amount <= 0) return;

            const cardToAdd = deckStack.lootStack.find(l => l.crand === lootId);

            deckStack.lootStack = arrayRemoveItem(deckStack.lootStack, cardToAdd);
            deckStack.amount--;

            let binderStack = allCards.find(s => s.item.crand === itemId);

            if (!binderStack) {
                binderStack = { item: deckStack.item, amount: 1, lootStack: [cardToAdd], nextLootId: {ctime: '', crand: lootId}, itemLootId: {ctime: '', crand: lootId} };
                allCards.push(binderStack);

                const container = document.getElementById('deckEditorAvailableCards');
                container.insertAdjacentHTML('beforeend', CardHTML(binderStack, false));

                const newCard = document.getElementById(`binder-card-${itemId}`);
                playCardAnimation(newCard, "animate__fadeInDown");
                initializeTooltipsInElement(newCard);
            } else {

                binderStack.amount++;
                binderStack.lootStack.push(cardToAdd);
                const countEl = document.getElementById(`binder-count-${itemId}`);
                if (countEl) countEl.innerText = `x${binderStack.amount}`;

                const cardEl = document.getElementById(`binder-card-${itemId}`);
                if (cardEl) playCardAnimation(cardEl, "animate__headShake");
            }
            
            
            if (deckStack.amount === 0) {
                const cardEl = document.getElementById(`deck-card-${itemId}`);
                if (cardEl) {
                    playCardAnimation(cardEl, 'animate__fadeOutDownBig', () => {
                        cardEl.remove();
                        deckCards = deckCards.filter(s => s.item.crand !== itemId);
                        UpdateDeckStats();
                    });
                }
            } else {
                document.getElementById(`deck-count-${itemId}`).innerText = `x${deckStack.amount}`;
                UpdateDeckStats();
            }

            const binder = allBinders.find(b => b.itemLootId.crand === currentOpenedBinder);
            binder.amount++;
            document.getElementById(`binder-count-${currentOpenedBinder}`).innerHTML = `<i class="fa-solid fa-box"></i> ${binder.amount}`;
            document.getElementById("binderCardCount").innerText = `${binder.amount} Cards`;

        }
        
        function OpenCardBinder(binderItem, binderLoot) {
            if (currentOpenedBinder != null) {
                CloseCardBinder();
            }
            LoadCardBinder(binderLoot);

            
        }

        function CloseCardBinder() {
            var closingBinder = currentOpenedBinder;
            
            document.getElementById('deckEditorAvailableCards').innerHTML = '';
            document.getElementById("binder-close-button").classList.add("d-none");
            addCards = [];
            currentOpenedBinder = null;
            StopLoadingSpinner('binderLoadingSpinner');
            checkIfContainerIsEmpty(
                    'deckEditorAvailableCards',
                    'deckEditorAvailableCardsEmptyMessage',
                    [
                        "A bare table... Maybe I should open a card binder."    
                    ],
                    'fa-book-open-cover',
                    'Select a card binder to open.'
                );

            if (closingBinder != null)
            {
                
                var binderEl = document.getElementById(`binder-loot-icon-${closingBinder}`);
                binderEl.classList.remove("fa-book-open-cover");
                binderEl.classList.add("fa-book-blank");

                binderEl = document.getElementById(`binder-loot-icon-button-${closingBinder}`);
                binderEl.classList.remove("bg-ranked-1");
                binderEl.classList.add("btn-primary");

                
                document.getElementById("binderCardCount").innerText = `0 Cards`;
            }
        }

        function BinderHTML(vItemStack, opened) {
            var loot  = vItemStack.itemLootId;
            var item = vItemStack.item;
            var count = vItemStack.amount;
            return `
                <div class="inventory-item" id="binder-${loot.crand}" onclick="ShowInventoryItemModal(${item.crand}, ${loot.crand})"  data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="${item.name}">
                    <img src="${item.iconBig.url}" alt="${item.name}">
                    <div class="item-count" id="binder-count-${loot.crand}"><i class="fa-solid fa-box"></i> ${count}</div>
                    <button id="binder-loot-icon-button-${loot.crand}"
                        class="btn btn-sm ${opened ? 'bg-ranked-1' : 'btn-primary'} action-button" 
                        onclick="event.stopPropagation(); ${opened ? 'CloseCardBinder' : 'OpenCardBinder'}(${item.crand}, ${loot.crand})" 
                        title="${opened ? 'Close Binder' : 'Open Binder'}"
                    >
                        <i id="binder-loot-icon-${loot.crand}" class="fa-solid ${!opened ? 'fa-book-blank' : 'fa-book-open-cover'}"></i>
                    </button>
                </div>
            `;
        }
        
        function RenderMyBinders() {
            StopLoadingSpinner('bindersLoadingSpinner');
            const bindersEl = document.getElementById('deckEditorAvailableBinders');
            bindersEl.innerHTML = '';

            allBinders.forEach(stack => {
                bindersEl.innerHTML += BinderHTML(stack, false);
            });

            
            document.getElementById("bindersCount").innerText = `${allBinders.length} Binders`;
        }

        function RenderOpenedBinder() {
            StopLoadingSpinner('binderLoadingSpinner');
            const availableEl = document.getElementById('deckEditorAvailableCards');

            availableEl.innerHTML = '';


            allCards.forEach(stack => {
                if (stack.amount > 0) {
                    const cardHTML = CardHTML(stack, false);
                    const wrapper = document.createElement('div');
                    wrapper.innerHTML = cardHTML;
                    const cardEl = wrapper.firstElementChild;
                    availableEl.appendChild(cardEl);
                }
            });



            ScrollReveal().reveal('.inventory-item', {
                container: '#cardBinderContainer',
                interval: 50,
                easing: 'ease-in',
                duration: 500,
            });

            checkIfContainerIsEmpty(
                'deckEditorAvailableCards',
                'deckEditorAvailableCardsEmptyMessage',
                [
                    "Nothing in here but some cobwebs and dust. Seems this binder hasn't seen use in many moons.",
                    "The binder lies bare—its cards played or forgotten.",
                    "Not but air and old parchment within.",
                    "You flip the pages... and find naught.",
                    "No cards, no colors—just the echo of emptiness.",
                    "A fine binder, but nothing rests within it... yet."
                ],
                'fa-spider-web',
                'Your binder is empty.'
            );
        }
        <?php } ?>

        function CardHTML(vItemStack, inDeck) {
            var item = vItemStack.item;
            var loot = vItemStack.itemLootId;
            var count = vItemStack.amount;
            const side = inDeck ? 'deck' : 'binder';
            return `
                <div class="inventory-item" onclick="ShowInventoryItemModal(${item.crand}, ${loot.crand})"  id="${side}-card-${item.crand}" data-bs-toggle="tooltip" data-bs-placement="bottom"  data-bs-title="${item.name}">
                    <img src="${item.iconBig.url}" alt="${item.name}">
                    <div class="item-count" id="${side}-count-${item.crand}">x${count}</div>
                    
                    <?php if ($canEditDeck) { ?>
                    <button 
                        class="btn btn-sm ${inDeck ? 'btn-danger' : 'btn-success'} action-button" 
                        onclick="event.stopPropagation(); ${inDeck ? 'TransferCardFromDeckToBinder' : 'TransferCardFromBinderToDeck'}(${item.crand})" 
                        title="${inDeck ? 'Remove from Deck' : 'Add to Deck'}"
                    >
                        <i class="fa-solid ${inDeck ? 'fa-minus' : 'fa-plus'}"></i>
                    </button>
                    
                    <?php } ?>
                </div>
            `;
        }

        function clearMessageInContainer(messageId) {
            
            const message = document.getElementById(messageId);
            if (!message) return;

            message.classList.add('d-none');
            message.innerHTML = ''; // Optional cleanup
        }

        function showMessageInContainer(messageId, iconClass, label, flavor = null) {
            const message = document.getElementById(messageId);
            if (!message) return;

            message.innerHTML = `
                <i class="fa-solid ${iconClass} fa-2x mb-3"></i>
                <p>${label}</p>
                ${flavor ? `<p><em>"${flavor}"</em></p>` : ""}
            `;
            message.classList.remove('d-none');
        }

        function checkIfContainerIsEmpty(containerId, messageId, flavorTexts, iconClass = 'fa-scroll', label = 'This container is empty.') {
            const container = document.getElementById(containerId);
            const message = document.getElementById(messageId);

            if (!container || !message) return;

            const items = container.querySelectorAll('.inventory-item');

            if (items.length === 0) {
                const flavor = flavorTexts[Math.floor(Math.random() * flavorTexts.length)];
                showMessageInContainer(messageId, iconClass, label, flavor);
            } else {
                clearMessageInContainer(messageId);
            }
        }

        function StopLoadingSpinner(spinnderId) {

            const loadingSpinner = document.getElementById(spinnderId);
            loadingSpinner.style.display = 'none';
        }

        function RenderCurrentDeck() {

            StopLoadingSpinner('deckLoadingSpinner');
            const selectedEl = document.getElementById('deckEditorSelectedCards');
            selectedEl.innerHTML = '';

            deckCards.forEach(stack => {
                if (stack.amount > 0) {
                    selectedEl.innerHTML += CardHTML(stack, true);
                }
            });

            UpdateDeckStats();
        }
        
        function UpdateDeckStats() {
            const total = deckCards.reduce((sum, s) => sum + s.amount, 0);
            //document.getElementById('deckStatTotal').textContent = total;

            const typeCounts = {};
            const sourceTypeCounts = {
                Arcanic: 0,
                Abyssal: 0,
                Thermic: 0,
                Verdant: 0,
                Luminate: 0
            };
            
            const sourceTypeCardCounts = {
                Arcanic: 0,
                Abyssal: 0,
                Thermic: 0,
                Verdant: 0,
                Luminate: 0
            };

            const overallStats = {
                "Total Cards": total,
                "Average Cost": 0
            }

            const costCounts = {};

            let totalSourceCost = 0;
            let cardCostEntries = 0;

            deckCards.forEach(stack => {
                const item = stack.item;
                const count = stack.amount;
                const lichCard = item.auxData?.lichCard;
                if (!lichCard) return;


                // Count subtypes instead of base type
                if (Array.isArray(lichCard.subTypes)) {
                    lichCard.subTypes.forEach(sub => {
                        typeCounts[sub] = (typeCounts[sub] || 0) + count;
                    });
                }

                // Count individual source types
                sourceTypeCardCounts.Arcanic += (lichCard.arcanic > 0) * count;
                sourceTypeCardCounts.Abyssal += (lichCard.abyssal > 0) * count;
                sourceTypeCardCounts.Thermic += (lichCard.thermic > 0) * count;
                sourceTypeCardCounts.Verdant += (lichCard.verdant > 0) * count;
                sourceTypeCardCounts.Luminate += (lichCard.luminate > 0) * count;

                
                sourceTypeCounts.Arcanic += (lichCard.arcanic || 0) * count;
                sourceTypeCounts.Abyssal += (lichCard.abyssal || 0) * count;
                sourceTypeCounts.Thermic += (lichCard.thermic || 0) * count;
                sourceTypeCounts.Verdant += (lichCard.verdant || 0) * count;
                sourceTypeCounts.Luminate += (lichCard.luminate || 0) * count;

                // Aggregate total source cost
                const cardCost = 
                    (lichCard.arcanic || 0) + 
                    (lichCard.abyssal || 0) + 
                    (lichCard.thermic || 0) + 
                    (lichCard.verdant || 0) + 
                    (lichCard.luminate || 0);

                totalSourceCost += cardCost * count;
                cardCostEntries += count;

                costCounts[cardCost] = (costCounts[cardCost] || 0) + count;
            });


            const avgCost = cardCostEntries > 0 ? (totalSourceCost / cardCostEntries).toFixed(2) : "0";
            overallStats["Average Cost"] = avgCost;
            // Render stat sections
            renderStatList('deckOverallStats', overallStats);
            renderStatList('deckStatTypes', typeCounts);
            renderStatList('deckStatSourceTypes', sourceTypeCardCounts);
            renderStatList('deckStatCosts', sourceTypeCounts);

            document.getElementById("deckCardCount").innerText = `${total} Cards`;
            // Add average cost to top line
            //const statTotal = document.getElementById('deckStatTotal');
            //statTotal.textContent += ` (Avg Cost: ${avgCost})`;

            // Empty message check
            checkIfContainerIsEmpty(
                'deckEditorSelectedCards',
                'deckEditorSelectedCardsEmptyMessage',
                [
                    "No cards here yet. Your deck dreams of greatness.",
                    "The deck box creaks... empty.",
                    "A blank slate—your strategy begins here.",
                    "It is not yet a deck. Merely a hope."
                ],
                'fa-spider-web',
                'Your deck is empty.'
            );

        }

        function renderStatList(containerId, dataObj) {
            const el = document.getElementById(containerId);
            el.innerHTML = '';
            Object.entries(dataObj).sort().forEach(([key, value]) => {
                const li = document.createElement('li');
                li.textContent = `${key}: ${value}`;
                el.appendChild(li);
            });
        }

    </script>
    <style>
.inventory-item {
    position: relative;
}

.action-button {
    position: absolute;
    top: 4px;
    right: 4px;
    padding: 2px 6px;
    font-size: 0.75rem;
    z-index: 5;
    opacity: 0.85;
}

.action-button:hover {
    opacity: 1;
}
.deck-editor-grid .inventory-item {
    transition: transform 0.2s ease-in-out;
    border-radius: 0.5rem;
    overflow: hidden;
    padding:0px;
}
.deck-editor-grid .inventory-item:hover {
    transform: scale(1.05);
    box-shadow: 0 0 10px rgba(0,0,0,0.15);
    z-index: 2;
}
.card-header i {
    opacity: 0.8;
}
.deck-editor-grid .inventory-item:not(.animate__animated):hover {
    transform: scale(1.05);
    box-shadow: 0 0 10px rgba(0,0,0,0.15);
    z-index: 2;
}

        </style>
</body>

</html>