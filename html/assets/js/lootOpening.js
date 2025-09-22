class LootReveal {
    constructor(rootElement, options = {}) {
        if (!rootElement) {
            throw new Error('LootReveal requires a root element.');
        }

        this.root = rootElement;
        this.config = Object.assign({
            chestOpenDelay: 320,
            itemRevealDelay: 360,
            countAnimationDuration: 420,
            chestOpenDuration: 900,
            onChestOpen: null,
            onChestClose: null,
            assetBasePath: 'https://kickback-kingdom.com/assets/media/chests/',
            cardBackImage: 'https://kickback-kingdom.com/assets/media/cards/card-back.png'
        }, options);

        this.state = 'idle';
        this.normalizedRewards = [];
        this.cardEntries = new Map();
        this.pendingCloseReason = null;

        this.#build();
    }

    #build() {
        let modal = this.root.querySelector('[data-loot-modal]');

        if (!modal) {
            this.root.innerHTML = `
                <div class="modal fade modal-chest loot-reveal-modal" tabindex="-1" aria-hidden="true" aria-modal="true" aria-label="Loot rewards" data-loot-modal>
                    <div class="modal-dialog modal-dialog-centered modal-xl loot-reveal__dialog">
                        <div class="modal-content bg-transparent border-0">
                            <div class="modal-body p-0">
                                <div class="loot-reveal" data-loot-container></div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            modal = this.root.querySelector('[data-loot-modal]');
        }

        if (!modal) {
            throw new Error('LootReveal requires a modal container element.');
        }

        this.modalEl = modal;
        this.modalEl.classList.add('modal-chest', 'loot-reveal-modal');
        this.modalDialogEl = this.modalEl.querySelector('.modal-dialog');
        this.modalEl.setAttribute('role', 'dialog');
        this.modalEl.setAttribute('aria-modal', 'true');

        if (!this.modalDialogEl) {
            throw new Error('LootReveal modal is missing a .modal-dialog element.');
        }

        let lootContainer = this.modalEl.querySelector('[data-loot-container]');

        if (!lootContainer) {
            lootContainer = document.createElement('div');
            lootContainer.className = 'loot-reveal';
            lootContainer.setAttribute('data-loot-container', '');
            const modalBody = this.modalEl.querySelector('.modal-body');
            (modalBody ?? this.modalDialogEl).appendChild(lootContainer);
        }

        this.lootContainerEl = lootContainer;
        this.lootContainerEl.setAttribute('aria-hidden', 'true');
        this.lootContainerEl.innerHTML = '';

        const panel = document.createElement('div');
        panel.className = 'loot-reveal__panel';
        panel.setAttribute('role', 'document');

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

        const flightLayer = document.createElement('div');
        flightLayer.className = 'loot-reveal__flight-layer';

        const status = document.createElement('div');
        status.className = 'loot-reveal__status-text';
        status.textContent = 'Awaiting chest...';

        panel.appendChild(stage);
        panel.appendChild(items);
        panel.appendChild(status);
        panel.appendChild(flightLayer);

        this.lootContainerEl.appendChild(panel);

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
        this.flightLayerEl = flightLayer;
        this.statusEl = status;
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

        this.modalEl.addEventListener('click', (event) => this.#handleBackdropInteraction(event));
        this.panelEl.addEventListener('click', (event) => this.#handlePanelInteraction(event));
        this.chestEl.addEventListener('click', () => this.#handleChestClick());

        this.modalEl.addEventListener('hide.bs.modal', () => {
            if (!this.pendingCloseReason) {
                this.pendingCloseReason = 'dismissed';
            }
        });

        this.modalEl.addEventListener('hidden.bs.modal', () => {
            this.lootContainerEl.setAttribute('aria-hidden', 'true');
            const reason = this.pendingCloseReason ?? 'manual';
            this.pendingCloseReason = null;
            this.#finalizeClose(reason);
        });

        this.modalEl.addEventListener('shown.bs.modal', () => {
            this.lootContainerEl.setAttribute('aria-hidden', 'false');
            this.lootContainerEl.classList.add('is-visible');
            if (typeof this.chestEl.focus === 'function') {
                this.chestEl.focus({ preventScroll: true });
            }
        });

        if (typeof bootstrap === 'undefined' || !bootstrap?.Modal) {
            throw new Error('Bootstrap Modal is required for LootReveal.');
        }

        this.modalInstance = bootstrap.Modal.getOrCreateInstance(this.modalEl, {
            backdrop: 'static',
            keyboard: false,
            focus: true
        });
    }

    open(rewards) {
        this.normalizedRewards = this.#normalizeRewards(rewards);

        this.#prepareForReveal();
        this.#updateStageVisuals();
        this.stageEl.classList.add('is-ready');

        this.state = 'ready';
        this.statusEl.textContent = this.normalizedRewards.length > 0
            ? 'Tap the chest to reveal your loot'
            : 'Tap the chest to peek inside';

        this.pendingCloseReason = null;
        this.modalInstance.show();
    }

    close(reason = 'manual') {
        if (this.state === 'opening' || this.state === 'closing') {
            return;
        }

        if (!this.modalInstance) {
            this.#finalizeClose(reason);
            return;
        }

        if (!this.modalEl.classList.contains('show')) {
            this.#finalizeClose(reason);
            return;
        }

        if (this.state === 'finished') {
            this.statusEl.textContent = 'Closing chest...';
        }

        this.state = 'closing';
        this.modalEl.classList.remove('chest-open-animate');
        this.pendingCloseReason = reason;
        this.modalInstance.hide();
    }

    #handleBackdropInteraction(event) {
        if (event.target !== this.modalEl) {
            return;
        }

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
        this.#clearTrack();
        this.chestEl.disabled = true;
        this.chestEl.classList.remove('is-activating');
        this.stageEl.classList.remove('is-open');
        this.cardContainerEl.classList.remove('is-visible', 'is-flipping');
        this.#triggerChestOpenAnimation();

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
        this.chestEl.disabled = false;
        this.chestEl.setAttribute('aria-expanded', 'false');
        this.#clearTrack();
        this.stageEl.classList.remove('is-ready', 'is-open');
        this.cardContainerEl.classList.remove('is-visible', 'is-flipping');
        this.chestEl.classList.remove('is-activating');
        this.modalEl.classList.remove('chest-open-animate');
        this.#resetFeatureCard();
    }

    #clearTrack() {
        this.trackEl.innerHTML = '';
        this.flightLayerEl.innerHTML = '';
        this.cardEntries.clear();
        const scroller = this.trackEl.parentElement;
        if (scroller) {
            if (typeof scroller.scrollTo === 'function') {
                scroller.scrollTo({ left: 0, behavior: 'auto' });
            } else {
                scroller.scrollLeft = 0;
            }
        }
    }

    #finalizeClose(reason) {
        this.state = 'idle';
        this.statusEl.textContent = 'Awaiting chest...';
        this.chestEl.disabled = false;
        this.chestEl.setAttribute('aria-expanded', 'false');
        this.#clearTrack();
        this.stageEl.classList.remove('is-ready', 'is-open');
        this.cardContainerEl.classList.remove('is-visible', 'is-flipping');
        this.chestEl.classList.remove('is-activating');
        this.modalEl.classList.remove('chest-open-animate');
        this.#resetFeatureCard();
        this.normalizedRewards = [];
        this.lootContainerEl.classList.remove('is-visible');
        this.lootContainerEl.setAttribute('aria-hidden', 'true');

        if (typeof this.config.onChestClose === 'function') {
            this.config.onChestClose(reason);
        }

        this.root.dispatchEvent(new CustomEvent('lootreveal:close', {
            detail: { reason }
        }));
    }

    #renderEmpty() {
        this.#clearTrack();
        const empty = document.createElement('div');
        empty.className = 'loot-reveal__empty';
        empty.textContent = 'Chest was empty... maybe next time!';
        this.trackEl.appendChild(empty);
    }

    async #revealReward(reward) {
        const normalized = this.#normalizeReward(reward);
        const key = this.#rewardKey(normalized);
        const existing = this.cardEntries.get(key);

        if (existing) {
            this.#animateDuplicateReward(existing, normalized);
            return;
        }

        const card = this.#createCardElement(normalized, { orientation: 'back' });
        card.classList.add('is-pending');
        this.trackEl.appendChild(card);

        const entry = {
            key,
            card,
            count: normalized.amount,
            countElement: card.querySelector('[data-count]'),
            reward: Object.assign({}, normalized),
            orientation: 'back'
        };

        this.#setCardOrientation(entry, 'back');
        this.cardEntries.set(key, entry);
        this.#bindCardInteraction(entry);
        this.#animateCardEntrance(entry, normalized);
    }

    #animateCardEntrance(entry, reward) {
        const { card } = entry;
        if (!(card instanceof HTMLElement)) {
            return;
        }

        const targetRect = card.getBoundingClientRect();

        this.#playFlightAnimation(reward, targetRect, {
            orientation: entry.orientation,
            onArrive: () => {
                card.classList.remove('is-pending');
                card.classList.add('is-visible');
            },
            onFinish: () => {
                this.#pulseCard(card, { delayFrame: true });
            }
        });
    }

    #animateDuplicateReward(entry, reward) {
        const { card, count, countElement } = entry;
        if (!card || !countElement) {
            return;
        }

        const start = count;
        const next = count + reward.amount;
        entry.count = next;
        entry.reward.amount = next;

        const targetRect = card.getBoundingClientRect();

        this.#playFlightAnimation(reward, targetRect, {
            orientation: entry.orientation,
            onArrive: () => {
                this.#animateCount(countElement, start, next);
            },
            onFinish: () => {
                this.#pulseCard(card);
            }
        });
    }

    #pulseCard(card, { delayFrame = false } = {}) {
        if (!(card instanceof HTMLElement)) {
            return;
        }

        const run = () => {
            card.classList.remove('is-updating');
            void card.offsetWidth;
            card.classList.add('is-updating');
        };

        if (delayFrame) {
            requestAnimationFrame(run);
        } else {
            run();
        }
    }

    #playFlightAnimation(reward, targetRect, { orientation = 'front', onArrive, onFinish } = {}) {
        const chestRect = this.chestEl?.getBoundingClientRect();
        if (!this.flightLayerEl || !chestRect || !targetRect?.width || !targetRect?.height) {
            if (typeof onArrive === 'function') {
                onArrive();
            }
            if (typeof onFinish === 'function') {
                onFinish();
            }
            return;
        }

        const chestPoint = {
            x: chestRect.left + chestRect.width / 2,
            y: chestRect.top + chestRect.height * 0.6
        };

        const targetPoint = {
            x: targetRect.left + targetRect.width / 2,
            y: targetRect.top + targetRect.height / 2
        };

        const ghost = this.#createCardElement(reward, { ghost: true, orientation });
        ghost.classList.add('loot-reveal__item-card--ghost');

        ghost.style.width = `${targetRect.width}px`;
        ghost.style.height = `${targetRect.height}px`;
        this.flightLayerEl.appendChild(ghost);

        const ghostRect = ghost.getBoundingClientRect();
        const offsetX = ghostRect.width / 2;
        const offsetY = ghostRect.height / 2;

        const startTransform = `translate(${chestPoint.x - offsetX}px, ${chestPoint.y - offsetY}px) scale(0.55)`;
        const endTransform = `translate(${targetPoint.x - offsetX}px, ${targetPoint.y - offsetY}px) scale(1)`;

        const flightDuration = 720;
        const arrivalOffset = 0.8;
        const arrivalDelay = flightDuration * arrivalOffset;

        const animation = ghost.animate([
            { transform: startTransform, opacity: 0 },
            { transform: startTransform, opacity: 1, offset: 0.18 },
            { transform: endTransform, opacity: 0.95, offset: 0.8 },
            { transform: endTransform, opacity: 1 }
        ], {
            duration: flightDuration,
            easing: 'cubic-bezier(0.22, 1, 0.36, 1)'
        });

        let cleaned = false;
        let arrived = false;

        const triggerArrival = () => {
            if (arrived) {
                return;
            }
            arrived = true;
            if (typeof onArrive === 'function') {
                onArrive();
            }
        };

        const arrivalTimer = setTimeout(triggerArrival, arrivalDelay);

        const cleanup = () => {
            if (cleaned) {
                return;
            }
            cleaned = true;
            clearTimeout(arrivalTimer);
            triggerArrival();
            ghost.remove();
            if (typeof onFinish === 'function') {
                onFinish();
            }
        };

        animation.addEventListener?.('finish', cleanup, { once: true });
        animation.addEventListener?.('cancel', cleanup, { once: true });
        animation.onfinish = cleanup;
        animation.oncancel = cleanup;
        animation.finished?.then(() => cleanup()).catch(() => cleanup());
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

    #triggerChestOpenAnimation() {
        if (!this.modalEl) {
            return;
        }

        this.modalEl.classList.remove('chest-open-animate');
        void this.modalEl.offsetWidth;
        this.modalEl.classList.add('chest-open-animate');
    }

    #animateCount(node, from, to) {
        const duration = this.config.countAnimationDuration;
        const start = performance.now();

        const step = (now) => {
            const elapsed = Math.min((now - start) / duration, 1);
            const value = Math.round(from + (to - from) * elapsed);
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

    #normalizeRewards(rewards) {
        if (!Array.isArray(rewards)) {
            return [];
        }

        const normalized = [];
        for (const reward of rewards) {
            try {
                normalized.push(this.#normalizeReward(reward));
            } catch (error) {
                console.error('LootReveal skipped invalid reward', reward, error);
            }
        }

        return normalized;
    }

    #normalizeReward(reward) {
        if (!reward || typeof reward !== 'object') {
            throw new Error('Each reward should be an object describing the loot item.');
        }

        const itemId = reward.itemId ?? reward.id;
        if (itemId === undefined) {
            throw new Error('Reward is missing an itemId (or id) property.');
        }

        const amountValue = Number(reward.amount ?? reward.quantity ?? 1);
        const amount = Number.isFinite(amountValue) && amountValue > 0 ? Math.floor(amountValue) : 1;

        return {
            itemId,
            name: reward.name ?? 'Unknown Loot',
            amount,
            rarity: reward.rarity ?? 'common',
            image: reward.image ?? null
        };
    }

    #rewardKey(reward) {
        const rarity = reward.rarity ? String(reward.rarity).toLowerCase() : 'common';
        return `${reward.itemId}::${rarity}`;
    }

    #createCardElement(reward, { ghost = false, orientation = 'front' } = {}) {
        const tagName = ghost ? 'div' : 'button';
        const card = document.createElement(tagName);
        card.className = 'loot-reveal__item-card';
        if (!ghost) {
            card.type = 'button';
        }

        if (ghost) {
            card.classList.add('loot-reveal__item-card--ghost');
        }

        const normalizedOrientation = orientation === 'back' ? 'back' : 'front';
        card.dataset.orientation = normalizedOrientation;

        if (!ghost) {
            card.tabIndex = 0;
        }

        if (this.config.cardBackImage) {
            card.style.setProperty('--loot-card-back-image', `url("${this.config.cardBackImage}")`);
        }

        const rarityClass = reward?.rarity
            ? {
                rare: 'loot-reveal__item-card--rare',
                epic: 'loot-reveal__item-card--epic',
                legendary: 'loot-reveal__item-card--legendary',
                mythic: 'loot-reveal__item-card--mythic'
            }[String(reward.rarity).toLowerCase()] ?? null
            : null;

        if (rarityClass) {
            card.classList.add(rarityClass);
        }

        const inner = document.createElement('div');
        inner.className = 'loot-reveal__item-card-inner';

        const backFace = document.createElement('div');
        backFace.className = 'loot-reveal__item-card-face loot-reveal__item-card-face--back';

        const frontFace = document.createElement('div');
        frontFace.className = 'loot-reveal__item-card-face loot-reveal__item-card-face--front';

        const imageWrapper = document.createElement('div');
        imageWrapper.className = 'loot-reveal__item-card-image';

        if (reward.image) {
            const img = document.createElement('img');
            img.src = reward.image;
            img.alt = reward.name;
            imageWrapper.appendChild(img);
        } else {
            imageWrapper.classList.add('loot-reveal__item-card-image--placeholder');
        }

        frontFace.appendChild(imageWrapper);

        inner.appendChild(backFace);
        inner.appendChild(frontFace);

        const count = document.createElement('div');
        count.className = 'loot-reveal__item-count';
        count.innerHTML = `<span data-count>${reward.amount}</span>`;

        card.appendChild(inner);
        card.appendChild(count);

        if (!ghost) {
            card.addEventListener('animationend', (event) => {
                if (event.animationName === 'loot-card-pop') {
                    card.classList.remove('is-updating');
                }
            });
        }

        return card;
    }

    #bindCardInteraction(entry) {
        const { card, reward } = entry;
        if (!(card instanceof HTMLElement)) {
            return;
        }

        card.classList.add('loot-reveal__item-card--interactive');
        if (reward?.name) {
            card.setAttribute('aria-label', `Reveal ${reward.name}`);
        }

        const handleTransitionEnd = (event) => {
            if (event.propertyName !== 'transform') {
                return;
            }
            card.classList.remove('is-flipping');
        };

        card.addEventListener('transitionend', handleTransitionEnd);
        card.addEventListener('click', () => this.#handleCardInteraction(entry));
    }

    #handleCardInteraction(entry) {
        if (!entry || entry.orientation === 'front') {
            return;
        }

        this.#setCardOrientation(entry, 'front');
        const { card } = entry;
        if (card instanceof HTMLElement) {
            card.classList.add('is-flipping');
        }
    }

    #setCardOrientation(entry, orientation) {
        if (!entry?.card) {
            return;
        }

        const normalized = orientation === 'front' ? 'front' : 'back';
        const previous = entry.orientation;
        entry.orientation = normalized;
        entry.card.dataset.orientation = normalized;
        entry.card.setAttribute('aria-pressed', normalized === 'front' ? 'true' : 'false');

        if (entry.reward?.name) {
            if (normalized === 'front') {
                entry.card.setAttribute('aria-label', `${entry.reward.name} revealed`);
            } else {
                entry.card.setAttribute('aria-label', `Reveal ${entry.reward.name}`);
            }
        }

        if (previous === normalized) {
            return;
        }

        if (normalized === 'front') {
            entry.card.classList.add('is-revealed');
        } else {
            entry.card.classList.remove('is-revealed');
        }
    }
}

window.LootReveal = LootReveal;
