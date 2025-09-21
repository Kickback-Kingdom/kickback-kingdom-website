<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loot Reveal Demo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/kickback-kingdom.css">
    <link rel="stylesheet" href="assets/css/loot-opening.css">
    <style>
        body {
            background: radial-gradient(circle at top, #1a102b, #05020b 70%);
            min-height: 100vh;
            margin: 0;
            color: #fff;
            font-family: 'Nunito', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 32px;
            padding: 4rem 1rem 6rem;
        }

        h1 {
            margin: 0;
            font-weight: 800;
            letter-spacing: 0.04em;
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
            line-height: 1.6;
            opacity: 0.85;
        }

        .demo-event-log {
            min-height: 1.5rem;
            font-size: 0.95rem;
            opacity: 0.7;
        }
    </style>
</head>
<body>
    <h1>Loot Reveal Prototype</h1>
    <p class="demo-description">
        Click the buttons below to simulate different chest outcomes. A full-screen overlay will appear&mdash;tap the
        chest to reveal its contents, then tap it again (or the backdrop) to close. Identical items combine into a
        single card with a counting animation, while unique items animate out and line up in a horizontal, scrollable
        spread.
    </p>
    <div class="demo-controls">
        <button type="button" data-demo="mixed">Open Mixed Chest</button>
        <button type="button" data-demo="duplicates">Open Duplicate Heavy Chest</button>
        <button type="button" data-demo="legendary">Open Legendary Chest</button>
    </div>
    <div class="demo-event-log" id="demo-event-log" aria-live="polite"></div>
    <div id="loot-root"></div>

    <script src="assets/js/lootOpening.js"></script>
    <script>
        const demoData = {
            mixed: [
                { itemId: 101, name: 'Sapphire Sigil', rarity: 'rare', image: 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=300&q=80' },
                { itemId: 205, name: 'Arcane Dust', rarity: 'common', amount: 2 },
                { itemId: 101, name: 'Sapphire Sigil', rarity: 'rare' },
                { itemId: 305, name: 'Runebound Gauntlet', rarity: 'epic', image: 'https://images.unsplash.com/photo-1528821154947-1aa3d1b74963?auto=format&fit=crop&w=300&q=80' },
                { itemId: 205, name: 'Arcane Dust', rarity: 'common' },
                { itemId: 412, name: 'Luminous Feather', rarity: 'rare', image: 'https://images.unsplash.com/photo-1452587925148-ce544e77e70d?auto=format&fit=crop&w=300&q=80' }
            ],
            duplicates: [
                { itemId: 501, name: 'Gold Coin', rarity: 'common', amount: 3 },
                { itemId: 501, name: 'Gold Coin', rarity: 'common', amount: 2 },
                { itemId: 501, name: 'Gold Coin', rarity: 'common' },
                { itemId: 640, name: 'Guild Voucher', rarity: 'rare' },
                { itemId: 501, name: 'Gold Coin', rarity: 'common', amount: 5 }
            ],
            legendary: [
                { itemId: 9001, name: 'Phoenix Crown', rarity: 'legendary', image: 'https://images.unsplash.com/photo-1545239351-1141bd82e8a6?auto=format&fit=crop&w=300&q=80' },
                { itemId: 813, name: 'Silver Bark Wand', rarity: 'epic', image: 'https://images.unsplash.com/photo-1519681393784-d120267933ba?auto=format&fit=crop&w=300&q=80' },
                { itemId: 205, name: 'Arcane Dust', rarity: 'common', amount: 4 },
                { itemId: 305, name: 'Runebound Gauntlet', rarity: 'epic' }
            ]
        };

        const root = document.getElementById('loot-root');
        const log = document.getElementById('demo-event-log');

        const writeLog = (message) => {
            if (!log) {
                return;
            }

            log.textContent = message;
        };

        writeLog('Awaiting chest interaction.');

        const reveal = new LootReveal(root, {
            onChestOpen: (payload) => {
                const total = Array.isArray(payload) ? payload.length : 0;
                console.log(`Chest open callback fired with ${total} reward${total === 1 ? '' : 's'}.`);
            },
            onChestClose: (reason) => {
                console.log(`Chest close callback fired (${reason}).`);
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
        });

        const buttons = document.querySelectorAll('.demo-controls button');
        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                const type = button.dataset.demo;
                reveal.open(demoData[type]);
            });
        });

        reveal.open(demoData.mixed);
    </script>
</body>
</html>
