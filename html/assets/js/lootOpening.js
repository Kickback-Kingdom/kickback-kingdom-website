class LootReveal {
    constructor(rootElement, options = {}) {
        if (!rootElement) {
            throw new Error('LootReveal requires a root element.');
        }

        this.root = rootElement;
        this.config = Object.assign({
            chestOpenDelay: 350,
            itemRevealDelay: 420,
            countAnimationDuration: 480,
            chestOpenDuration: 900,
            onChestOpen: null,
            onChestClose: null,
            assetBasePath: 'https://kickback-kingdom.com/assets/media/chests/',
            cardBackImage: 'https://kickback-kingdom.com/assets/media/cards/card-back.png'
        }, options);

        this.state = 'idle';
        this.normalizedRewards = [];

        this.#build();
    }

    #build() {
        this.root.classList.add('loot-reveal');
        this.root.setAttribute('aria-hidden', 'true');
        this.root.innerHTML = '';

        const backdrop = document.createElement('div');
        backdrop.className = 'loot-reveal__backdrop';

        const panel = document.createElement('div');
        panel.className = 'loot-reveal__panel';
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-modal', 'true');
        panel.setAttribute('aria-label', 'Loot rewards');

        const stage = document.createElement('div');
        stage.className = 'loot-reveal__stage';
        stage.innerHTML = `
            <img class="loot-reveal__glow loot-reveal__glow--back" data-glow-back alt="" aria-hidden="true" />
            <button type="button" class="loot-reveal__chest" data-chest aria-label="Open loot chest" aria-expanded="false">
                <img class="loot-reveal__chest-image loot-reveal__chest-image--closed" data-chest-closed alt="Closed treasure chest" />
                <img class="loot-reveal__chest-image loot-reveal__chest-image--open" data-chest-open alt="Open treasure chest" />
                <div class="loot-reveal__card-container" data-card-container aria-hidden="true">
                    <div class="loot-reveal__card-face loot-reveal__card-face--front" data-card-front>
                        <div class="loot-reveal__card-thumbnail" data-card-thumbnail></div>
                        <div class="loot-reveal__card-label" data-card-label></div>
                    </div>
                    <div class="loot-reveal__card-face loot-reveal__card-face--back" data-card-back></div>
                </div>
            </button>
            <img class="loot-reveal__glow loot-reveal__glow--front" data-glow-front alt="" aria-hidden="true" />
        `;

        const items = document.createElement('div');
        items.className = 'loot-reveal__items';
        items.innerHTML = `
            <div class="loot-reveal__track-wrapper">
                <div class="loot-reveal__track"></div>
            </div>
        `;

        const status = document.createElement('div');
        status.className = 'loot-reveal__status-text';
        status.textContent = 'Awaiting chest...';

        panel.appendChild(stage);
        panel.appendChild(items);
        panel.appendChild(status);

        this.root.appendChild(backdrop);
        this.root.appendChild(panel);

        this.backdropEl = backdrop;
        this.panelEl = panel;
        this.stageEl = stage;
        this.chestEl = stage.querySelector('[data-chest]');
        this.closedChestEl = stage.querySelector('[data-chest-closed]');
        this.openChestEl = stage.querySelector('[data-chest-open]');
        this.glowBackEl = stage.querySelector('[data-glow-back]');
        this.glowFrontEl = stage.querySelector('[data-glow-front]');
        this.cardContainerEl = stage.querySelector('[data-card-container]');
        this.cardFrontEl = stage.querySelector('[data-card-front]');
        this.cardBackEl = stage.querySelector('[data-card-back]');
        this.cardThumbnailEl = stage.querySelector('[data-card-thumbnail]');
        this.cardLabelEl = stage.querySelector('[data-card-label]');
        this.trackEl = items.querySelector('.loot-reveal__track');
        this.statusEl = status;
        this.cardMap = new Map();
        this.assetBasePath = this.#normalizeBasePath(this.config.assetBasePath);

        this.cardContainerEl?.addEventListener('animationend', (event) => {
            if (event.animationName === 'flip') {
                this.cardContainerEl.classList.remove('is-flipping');
            }
        });

        this.chestEl.addEventListener('animationend', (event) => {
            if (event.animationName === 'tada') {
                this.chestEl.classList.remove('is-activating');
            }
        });

        this.backdropEl.addEventListener('click', (event) => this.#handleBackdropInteraction(event));
        this.panelEl.addEventListener('click', (event) => this.#handlePanelInteraction(event));
        this.chestEl.addEventListener('click', () => this.#handleChestClick());
    }

    open(rewards) {
        if (!Array.isArray(rewards)) {
            this.normalizedRewards = [];
        } else {
            this.normalizedRewards = rewards.map((reward) => this.#normalizeReward(reward));
        }

        this.#prepareForReveal();
        this.#updateStageVisuals();
        this.stageEl.classList.add('is-ready');

        this.state = 'ready';
        this.statusEl.textContent = this.normalizedRewards.length > 0
            ? 'Tap the chest to reveal your loot'
            : 'Tap the chest to peek inside';

        this.root.classList.add('is-visible');
        this.root.setAttribute('aria-hidden', 'false');
        if (typeof this.chestEl.focus === 'function') {
            this.chestEl.focus({ preventScroll: true });
        }
    }

    close(reason = 'manual') {
        if (!this.root.classList.contains('is-visible')) {
            return;
        }

        if (this.state === 'opening') {
            return;
        }

        this.state = 'idle';
        this.root.classList.remove('is-visible', 'is-opening');
        this.root.setAttribute('aria-hidden', 'true');
        this.statusEl.textContent = 'Awaiting chest...';
        this.chestEl.setAttribute('aria-expanded', 'false');
        this.#clearTrack();
        this.stageEl.classList.remove('is-ready', 'is-open');
        this.cardContainerEl.classList.remove('is-visible', 'is-flipping');
        this.chestEl.classList.remove('is-activating');
        this.#resetFeatureCard();
        this.normalizedRewards = [];

        if (typeof this.config.onChestClose === 'function') {
            this.config.onChestClose(reason);
        }

        this.root.dispatchEvent(new CustomEvent('lootreveal:close', {
            detail: { reason }
        }));
    }

    #handleBackdropInteraction(event) {
        if (this.state === 'ready') {
            event.preventDefault();
            event.stopPropagation();
            void this.#startReveal();
            return;
        }

        if (this.state === 'finished') {
            this.close('backdrop');
        }
    }

    #handlePanelInteraction(event) {
        if (this.state !== 'ready') {
            return;
        }

        if (!this.panelEl.contains(event.target)) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        void this.#startReveal();
    }

    async #handleChestClick() {
        if (this.state === 'ready') {
            await this.#startReveal();
            return;
        }

        if (this.state === 'finished') {
            this.close('chest');
        }
    }

    async #startReveal() {
        if (this.state !== 'ready') {
            return;
        }

        this.state = 'opening';
        this.statusEl.textContent = 'Opening...';
        this.root.classList.remove('is-opening');
        this.#clearTrack();
        this.chestEl.disabled = true;
        this.chestEl.classList.remove('is-activating');
        this.stageEl.classList.remove('is-open');
        this.cardContainerEl.classList.remove('is-visible', 'is-flipping');

        if (typeof this.config.onChestOpen === 'function') {
            this.config.onChestOpen(this.normalizedRewards.slice());
        }

        this.root.dispatchEvent(new CustomEvent('lootreveal:chestopen', {
            detail: {
                rewards: this.normalizedRewards.slice()
            }
        }));

        await this.#delay(this.config.chestOpenDelay);
        this.chestEl.classList.add('is-activating');
        this.root.classList.add('is-opening');
        this.stageEl.classList.add('is-open');
        this.cardContainerEl.classList.add('is-visible', 'is-flipping');
        await this.#delay(this.config.chestOpenDuration);

        if (this.normalizedRewards.length === 0) {
            this.#renderEmpty();
        } else {
            for (const reward of this.normalizedRewards) {
                await this.#revealReward(reward);
                await this.#delay(this.config.itemRevealDelay);
            }
        }

        this.state = 'finished';
        this.statusEl.textContent = 'Tap the chest to close';
        this.chestEl.disabled = false;
        this.chestEl.setAttribute('aria-expanded', 'true');
    }

    #prepareForReveal() {
        this.root.classList.remove('is-opening');
        this.chestEl.disabled = false;
        this.chestEl.setAttribute('aria-expanded', 'false');
        this.#clearTrack();
        this.stageEl.classList.remove('is-ready', 'is-open');
        this.cardContainerEl.classList.remove('is-visible', 'is-flipping');
        this.chestEl.classList.remove('is-activating');
        this.#resetFeatureCard();
    }

    #clearTrack() {
        this.trackEl.innerHTML = '';
        this.cardMap.clear();
        const scroller = this.trackEl.parentElement;
        if (scroller) {
            if (typeof scroller.scrollTo === 'function') {
                scroller.scrollTo({ left: 0, behavior: 'auto' });
            } else {
                scroller.scrollLeft = 0;
            }
        }
    }

    #renderEmpty() {
        this.#clearTrack();
        const empty = document.createElement('div');
        empty.className = 'loot-reveal__empty';
        empty.textContent = 'Chest was empty... maybe next time!';
        this.trackEl.appendChild(empty);
    }

    #updateStageVisuals() {
        const rarityIndex = this.#determineChestRarity();
        const rarityLabels = ['Common', 'Uncommon', 'Rare', 'Epic', 'Legendary', 'Mythic'];
        const label = rarityLabels[rarityIndex] ?? 'Treasure';

        const closed = this.#buildAssetPath(`Loot_Box_0${rarityIndex}_01_Star.png`);
        const open = this.#buildAssetPath(`Loot_Box_0${rarityIndex}_02_Star.png`);
        const glowBack = this.#buildAssetPath(`${rarityIndex - 1}_c_s.png`);
        const glowFront = this.#buildAssetPath(`${rarityIndex - 1}_o_s.png`);

        if (this.closedChestEl) {
            this.closedChestEl.src = closed;
            this.closedChestEl.alt = `${label} loot chest, closed`;
        }

        if (this.openChestEl) {
            this.openChestEl.src = open;
            this.openChestEl.alt = `${label} loot chest, open`;
        }

        if (this.glowBackEl) {
            this.glowBackEl.src = glowBack;
        }

        if (this.glowFrontEl) {
            this.glowFrontEl.src = glowFront;
        }

        if (this.cardBackEl) {
            const backImage = this.config.cardBackImage;
            if (backImage) {
                this.cardBackEl.style.setProperty('--loot-card-back-image', `url("${backImage}")`);
            }
        }

        const featured = this.#selectFeaturedReward();
        this.#applyFeatureReward(featured);
    }

    #resetFeatureCard() {
        if (this.cardThumbnailEl) {
            this.cardThumbnailEl.style.removeProperty('--loot-card-thumb-image');
            this.cardThumbnailEl.textContent = '';
            this.cardThumbnailEl.classList.remove('has-image');
        }

        if (this.cardLabelEl) {
            this.cardLabelEl.textContent = '';
        }
    }

    async #revealReward(reward) {
        const normalized = this.#normalizeReward(reward);
        const existing = this.cardMap.get(normalized.itemId);

        if (existing) {
            const next = existing.count + normalized.amount;
            this.#animateCount(existing.countElement, existing.count, next);
            existing.count = next;
            existing.card.classList.remove('is-updating');
            void existing.card.offsetWidth;
            existing.card.classList.add('is-updating');
            return;
        }

        const card = this.#createCard(normalized);
        this.cardMap.set(normalized.itemId, {
            card,
            count: normalized.amount,
            countElement: card.querySelector('[data-count]')
        });
        this.trackEl.appendChild(card);

        requestAnimationFrame(() => {
            card.classList.add('is-visible');
        });
    }

    #createCard(reward) {
        const card = document.createElement('div');
        card.className = 'loot-reveal__item-card';

        const imageWrapper = document.createElement('div');
        imageWrapper.className = 'loot-reveal__item-card-image';

        if (reward.image) {
            const img = document.createElement('img');
            img.src = reward.image;
            img.alt = reward.name;
            imageWrapper.appendChild(img);
        } else {
            const placeholder = document.createElement('div');
            placeholder.textContent = reward.name.charAt(0).toUpperCase();
            placeholder.style.fontSize = '2.8rem';
            placeholder.style.fontWeight = '700';
            placeholder.style.color = 'rgba(255, 255, 255, 0.7)';
            imageWrapper.appendChild(placeholder);
        }

        if (reward.rarity) {
            const ring = document.createElement('div');
            ring.className = 'loot-reveal__rarity-ring';
            const rarityClass = {
                common: '',
                rare: 'loot-reveal__rarity-ring--rare',
                epic: 'loot-reveal__rarity-ring--epic',
                legendary: 'loot-reveal__rarity-ring--legendary'
            }[reward.rarity];

            if (rarityClass) {
                ring.classList.add(rarityClass);
            }

            imageWrapper.appendChild(ring);
        }

        const name = document.createElement('div');
        name.className = 'loot-reveal__item-name';
        name.textContent = reward.name;

        const count = document.createElement('div');
        count.className = 'loot-reveal__item-count';
        count.innerHTML = `
            <span data-count>${reward.amount}</span>
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path d="M4.5 6.75a2.25 2.25 0 0 1 2.25-2.25h10.5A2.25 2.25 0 0 1 19.5 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 17.25V6.75zm2.25-.75a.75.75 0 0 0-.75.75v10.5c0 .414.336.75.75.75h10.5a.75.75 0 0 0 .75-.75V6.75a.75.75 0 0 0-.75-.75H6.75zm5.25 2.25a.75.75 0 0 1 .75.75v2.25H15a.75.75 0 0 1 0 1.5h-2.25V15a.75.75 0 0 1-1.5 0v-2.25H9a.75.75 0 0 1 0-1.5h2.25V9a.75.75 0 0 1 .75-.75z"></path>
            </svg>
        `;

        card.appendChild(imageWrapper);
        card.appendChild(name);
        card.appendChild(count);

        card.addEventListener('animationend', (event) => {
            if (event.animationName === 'loot-card-pop') {
                card.classList.remove('is-updating');
            }
        });

        return card;
    }

    #normalizeReward(reward) {
        if (!reward || typeof reward !== 'object') {
            throw new Error('Each reward should be an object describing the loot item.');
        }

        const itemId = reward.itemId ?? reward.id;
        if (itemId === undefined) {
            throw new Error('Reward is missing an itemId (or id) property.');
        }

        return {
            itemId,
            name: reward.name ?? 'Unknown Loot',
            amount: Number(reward.amount ?? reward.quantity ?? 1),
            rarity: reward.rarity ?? 'common',
            image: reward.image ?? null
        };
    }

    #animateCount(node, from, to) {
        const duration = this.config.countAnimationDuration;
        const start = performance.now();

        const step = (now) => {
            const elapsed = Math.min((now - start) / duration, 1);
            const value = Math.floor(from + (to - from) * elapsed);
            node.textContent = value.toString();

            if (elapsed < 1) {
                requestAnimationFrame(step);
            } else {
                node.textContent = to.toString();
            }
        };

        requestAnimationFrame(step);
    }

    #delay(ms) {
        return new Promise((resolve) => setTimeout(resolve, ms));
    }

    #determineChestRarity() {
        if (!Array.isArray(this.normalizedRewards) || this.normalizedRewards.length === 0) {
            return 0;
        }

        let highest = 0;
        for (const reward of this.normalizedRewards) {
            const value = this.#rarityValue(reward.rarity);
            if (value > highest) {
                highest = value;
            }
        }

        return Math.min(highest, 5);
    }

    #rarityValue(rarity) {
        const map = {
            common: 0,
            bronze: 0,
            uncommon: 1,
            silver: 1,
            rare: 2,
            gold: 2,
            epic: 3,
            platinum: 3,
            legendary: 4,
            mythic: 5,
            exalted: 5
        };

        if (!rarity) {
            return 0;
        }

        const key = String(rarity).toLowerCase();
        return map[key] ?? 0;
    }

    #selectFeaturedReward() {
        if (!Array.isArray(this.normalizedRewards) || this.normalizedRewards.length === 0) {
            return null;
        }

        let selected = null;
        let highest = -1;

        for (const reward of this.normalizedRewards) {
            const rarityScore = this.#rarityValue(reward.rarity);
            const hasImage = reward.image ? 1 : 0;
            const amountScore = reward.amount > 1 ? 0.1 : 0;
            const score = rarityScore * 10 + hasImage * 2 + amountScore;

            if (!selected || score > highest) {
                selected = reward;
                highest = score;
            }
        }

        return selected;
    }

    #applyFeatureReward(reward) {
        if (!reward) {
            this.#resetFeatureCard();
            return;
        }

        if (this.cardLabelEl) {
            const amountText = reward.amount > 1 ? ` ×${reward.amount}` : '';
            this.cardLabelEl.textContent = `${reward.name}${amountText}`;
        }

        if (this.cardThumbnailEl) {
            if (reward.image) {
                this.cardThumbnailEl.style.setProperty('--loot-card-thumb-image', `url("${reward.image}")`);
                this.cardThumbnailEl.textContent = '';
                this.cardThumbnailEl.classList.add('has-image');
            } else {
                this.cardThumbnailEl.style.removeProperty('--loot-card-thumb-image');
                this.cardThumbnailEl.classList.remove('has-image');
                const initial = reward.name ? reward.name.charAt(0).toUpperCase() : '?';
                this.cardThumbnailEl.textContent = initial;
            }
        }
    }

    #buildAssetPath(fileName) {
        if (!fileName) {
            return '';
        }

        return `${this.assetBasePath}${fileName}`;
    }

    #normalizeBasePath(basePath) {
        if (!basePath) {
            return '';
        }

        let normalized = String(basePath).trim();
        if (!normalized.endsWith('/')) {
            normalized += '/';
        }

        return normalized;
    }
}

window.LootReveal = LootReveal;
