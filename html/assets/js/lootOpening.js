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
            onChestClose: null
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

        const chest = document.createElement('button');
        chest.type = 'button';
        chest.className = 'loot-reveal__chest';
        chest.setAttribute('aria-label', 'Open loot chest');
        chest.setAttribute('aria-expanded', 'false');
        chest.innerHTML = `
            <div class="loot-reveal__chest-body"></div>
            <div class="loot-reveal__chest-lid"></div>
            <div class="loot-reveal__sparkle"></div>
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

        panel.appendChild(chest);
        panel.appendChild(items);
        panel.appendChild(status);

        this.root.appendChild(backdrop);
        this.root.appendChild(panel);

        this.backdropEl = backdrop;
        this.panelEl = panel;
        this.chestEl = chest;
        this.trackEl = items.querySelector('.loot-reveal__track');
        this.statusEl = status;
        this.cardMap = new Map();

        this.backdropEl.addEventListener('click', () => this.close('backdrop'));
        this.chestEl.addEventListener('click', () => this.#handleChestClick());
    }

    open(rewards) {
        if (!Array.isArray(rewards)) {
            this.normalizedRewards = [];
        } else {
            this.normalizedRewards = rewards.map((reward) => this.#normalizeReward(reward));
        }

        this.#prepareForReveal();

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
        this.normalizedRewards = [];

        if (typeof this.config.onChestClose === 'function') {
            this.config.onChestClose(reason);
        }

        this.root.dispatchEvent(new CustomEvent('lootreveal:close', {
            detail: { reason }
        }));
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

        if (typeof this.config.onChestOpen === 'function') {
            this.config.onChestOpen(this.normalizedRewards.slice());
        }

        this.root.dispatchEvent(new CustomEvent('lootreveal:chestopen', {
            detail: {
                rewards: this.normalizedRewards.slice()
            }
        }));

        await this.#delay(this.config.chestOpenDelay);
        this.root.classList.add('is-opening');
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
}

window.LootReveal = LootReveal;
