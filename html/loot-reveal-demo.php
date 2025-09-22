<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loot Reveal Demo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/vendors/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/kickback-kingdom.css">
    <link rel="stylesheet" href="assets/css/loot-opening.css">
    <style>
        body {
            position: relative;
            min-height: 100vh;
            margin: 0;
            color: #fff8ff;
            font-family: 'Nunito', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 32px;
            padding: 4.5rem 1rem 6.5rem;
            background: radial-gradient(circle at top, rgba(79, 34, 141, 0.45), rgba(10, 6, 24, 0.98) 68%) fixed,
                radial-gradient(circle at bottom, rgba(14, 94, 166, 0.18), transparent 55%) fixed,
                #05020b;
            overflow-x: hidden;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            background: radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.12), transparent 45%),
                radial-gradient(circle at 80% 30%, rgba(255, 214, 102, 0.18), transparent 55%),
                radial-gradient(circle at 40% 75%, rgba(168, 85, 247, 0.12), transparent 55%);
            opacity: 0.35;
            mix-blend-mode: screen;
            z-index: 0;
        }

        h1 {
            margin: 0;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-shadow: 0 18px 42px rgba(0, 0, 0, 0.55);
            font-size: clamp(2.2rem, 5vw, 3.2rem);
            z-index: 1;
        }

        .demo-controls {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .demo-controls button {
            background: linear-gradient(135deg, #6d28d9, #3b82f6);
            border: none;
            color: #fff;
            padding: 0.85rem 1.8rem;
            border-radius: 999px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            box-shadow: 0 14px 30px rgba(59, 130, 246, 0.35);
            transition: transform 220ms ease, box-shadow 220ms ease;
        }

        .demo-controls button:hover {
            transform: translateY(-3px);
            box-shadow: 0 22px 40px rgba(109, 40, 217, 0.4);
        }

        .demo-controls button:active {
            transform: translateY(0);
        }

        .demo-description {
            max-width: 760px;
            text-align: center;
            line-height: 1.7;
            opacity: 0.86;
            z-index: 1;
        }

        .demo-event-log {
            min-height: 1.5rem;
            font-size: 0.95rem;
            opacity: 0.7;
            z-index: 1;
        }

    </style>
</head>
<body class="loot-demo">
    <h1>Loot Reveal Prototype</h1>
    <p class="demo-description">
        Click the buttons below to simulate different chest outcomes. A full-screen overlay will appear&mdash;tap the
        chest to reveal its contents, then tap it again (or the backdrop) to close. Identical items combine into a
        single card with a counting animation, while unique items animate out and line up in a horizontal, scrollable
        spread.
    </p>
    <div class="demo-controls" data-chest-controls></div>
    <div class="demo-event-log" id="demo-event-log" aria-live="polite"></div>
    <div id="loot-root">
        <div class="modal fade modal-chest loot-reveal-modal" id="loot-reveal-modal" tabindex="-1" aria-hidden="true" aria-modal="true" aria-label="Loot rewards" data-loot-modal>
            <div class="modal-dialog modal-dialog-centered modal-xl loot-reveal__dialog">
                <div class="modal-content bg-transparent border-0">
                    <div class="modal-body p-0">
                        <div class="loot-reveal" data-loot-container></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="confetti-box" aria-hidden="true">
        <div class="js-container-confetti" style="width: 100vw; height: 100vh;"></div>
    </div>

    <script src="assets/vendors/jquery/jquery-3.7.0.min.js"></script>
    <script src="assets/vendors/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="assets/js/confetti.js"></script>
    <script src="assets/js/lootOpening.js"></script>
    <script>
        const chestData = <?php echo $activeAccountInfo->chestsJSON; ?>;
        const root = document.getElementById('loot-root');
        const log = document.getElementById('demo-event-log');
        const controls = document.querySelector('[data-chest-controls]');

        const writeLog = (message) => {
            if (!log) {
                return;
            }

            log.textContent = message;
        };

        writeLog('Awaiting chest interaction.');

        const getChestLabel = (chest, index) => {
            const rarityValue = chest?.rarity;
            const rarityMap = {
                0: 'Common',
                1: 'Uncommon',
                2: 'Rare',
                3: 'Epic',
                4: 'Legendary',
                5: 'Mythic'
            };

            if (typeof rarityValue === 'string' && rarityValue.trim().length > 0) {
                return `${rarityValue} Chest ${index + 1}`;
            }

            if (typeof rarityValue === 'number') {
                const rarityLabel = rarityMap[rarityValue] ?? 'Chest';
                return `${rarityLabel} Chest ${index + 1}`;
            }

            return `Chest ${index + 1}`;
        };

        const rarityFromItem = (item) => {
            const value = item?.rarity;
            const rarityMap = {
                0: 'common',
                1: 'uncommon',
                2: 'rare',
                3: 'epic',
                4: 'legendary',
                5: 'mythic'
            };

            if (typeof value === 'string') {
                return value.toLowerCase();
            }

            if (typeof value === 'number') {
                return rarityMap[value] ?? 'common';
            }

            if (value && typeof value === 'object' && 'value' in value) {
                const inner = value.value;
                if (typeof inner === 'string') {
                    return inner.toLowerCase();
                }

                if (typeof inner === 'number') {
                    return rarityMap[inner] ?? 'common';
                }
            }

            return 'common';
        };

        const pickImage = (item) => {
            const candidateImages = [
                item?.iconSmall?.url,
                item?.iconBig?.url,
                item?.iconBack?.url
            ];

            return candidateImages.find((src) => typeof src === 'string' && src.trim().length > 0) ?? null;
        };

        const stackToReward = (stack) => {
            const item = stack?.item ?? null;
            const itemLootId = stack?.itemLootId;
            const stackAmount = Number.isFinite(stack?.amount) ? Number(stack.amount) : 1;

            const itemId = item?.crand
                ?? item?.id
                ?? (typeof itemLootId === 'object' && itemLootId !== null ? itemLootId.crand : undefined)
                ?? itemLootId
                ?? stack?.item_id
                ?? stack?.itemId;

            return {
                itemId,
                name: typeof stack?.nickname === 'string' && stack.nickname.trim().length > 0
                    ? stack.nickname
                    : (item?.name ?? 'Unknown Loot'),
                amount: stackAmount > 0 ? Math.floor(stackAmount) : 1,
                rarity: rarityFromItem(item),
                image: pickImage(item)
            };
        };

        const fetchChestRewards = async (lootId) => {
            if (!lootId) {
                throw new Error('Unable to load chest rewards without an id.');
            }

            const response = await fetch(`/api/v1/engine/container/open.php?lootId=${encodeURIComponent(lootId)}`, {
                credentials: 'include'
            });

            if (!response.ok) {
                throw new Error(`Chest request failed with status ${response.status}.`);
            }

            const payload = await response.json();

            if (!payload?.success) {
                const message = typeof payload?.message === 'string' ? payload.message : 'Unknown error.';
                throw new Error(message);
            }

            if (!Array.isArray(payload?.data)) {
                throw new Error('Chest payload did not include item data.');
            }

            return payload.data.map(stackToReward).filter((reward) => reward?.itemId !== undefined);
        };

        const startClosedChestConfetti = () => {
            if (typeof StopConfetti === 'function') {
                StopConfetti();
            }

            if (typeof StartConfetti === 'function') {
                StartConfetti();
            }
        };

        const reveal = new LootReveal(root, {
            onChestOpen: (payload) => {
                const total = Array.isArray(payload) ? payload.length : 0;
                console.log(`Chest open callback fired with ${total} reward${total === 1 ? '' : 's'}.`);
            },
            onChestClose: (reason) => {
                console.log(`Chest close callback fired (${reason}).`);
                if (typeof StopConfetti === 'function') {
                    StopConfetti();
                }
            }
        });

        reveal.root.addEventListener('lootreveal:chestopen', (event) => {
            const rewards = event.detail?.rewards ?? [];
            const total = Array.isArray(rewards) ? rewards.length : 0;
            writeLog(`Chest opened with ${total} reward${total === 1 ? '' : 's'}.`);
        });

        reveal.root.addEventListener('lootreveal:close', (event) => {
            const reason = event.detail?.reason ?? 'manual';
            writeLog(`Loot overlay closed (${reason}).`);
            if (typeof StopConfetti === 'function') {
                StopConfetti();
            }
        });

        const attachChestButtons = () => {
            if (!controls) {
                return;
            }

            controls.innerHTML = '';

            if (!Array.isArray(chestData) || chestData.length === 0) {
                const placeholder = document.createElement('p');
                placeholder.className = 'demo-description';
                placeholder.textContent = 'No unopened chests are currently associated with this account.';
                controls.appendChild(placeholder);
                return;
            }

            chestData.forEach((chest, index) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = `Open ${getChestLabel(chest, index)}`;

                const lootId = chest?.Id ?? chest?.id ?? chest?.lootId ?? chest?.loot_id;

                button.addEventListener('click', async () => {
                    if (!lootId) {
                        writeLog('Selected chest is missing an identifier.');
                        return;
                    }

                    writeLog(`Loading ${getChestLabel(chest, index)}...`);

                    try {
                        const rewards = await fetchChestRewards(lootId);
                        const total = rewards.length;
                        writeLog(`Chest ready with ${total} reward${total === 1 ? '' : 's'}. Tap the chest to reveal.`);
                        reveal.open(rewards);
                        startClosedChestConfetti();
                    } catch (error) {
                        console.error('Failed to load chest rewards', error);
                        writeLog(`Failed to load chest: ${error?.message ?? error}`);
                    }
                });

                controls.appendChild(button);
            });
        };

        attachChestButtons();

        if (Array.isArray(chestData) && chestData.length > 0) {
            const initialChest = chestData[0];
            const lootId = initialChest?.Id ?? initialChest?.id ?? initialChest?.lootId ?? initialChest?.loot_id;

            if (lootId) {
                fetchChestRewards(lootId)
                    .then((rewards) => {
                        const total = rewards.length;
                        writeLog(`Chest ready with ${total} reward${total === 1 ? '' : 's'}. Tap the chest to reveal.`);
                        reveal.open(rewards);
                        startClosedChestConfetti();
                    })
                    .catch((error) => {
                        console.error('Failed to load initial chest rewards', error);
                        writeLog('Unable to load initial chest rewards. Select a chest to try again.');
                    });
            }
        }
    </script>
</body>
</html>
