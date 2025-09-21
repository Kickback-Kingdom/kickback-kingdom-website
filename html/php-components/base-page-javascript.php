<?php
use Kickback\Backend\Models\PlayStyle;
use Kickback\Common\Version;
?>
<!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="<?= Version::urlBetaPrefix(); ?>/assets/vendors/jquery/jquery-3.7.0.min.js"></script>
    <!--<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>-->
    <script src="<?= Version::urlBetaPrefix(); ?>/assets/vendors/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dompurify@3.0.6/dist/purify.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prettify/r298/run_prettify.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.js"></script>
    <script src="<?= Version::urlBetaPrefix(); ?>/assets/vendors/qrcode/qrcode.min.js"></script>
    <script src="<?= Version::urlBetaPrefix(); ?>/assets/js/qrcode.js"></script>

    <!--<script src="assets/owl-carousel/owl.carousel.js"></script>-->
    <script>
        function arrayRemoveItem(array, itemToRemove) {
            let index = array.indexOf(itemToRemove);

            let newArr = index !== -1 ? 
                [...array.slice(0, index), ...array.slice(index + 1)] : fruits;
            
            return newArr;
        }


        var play_styles = <?= PlayStyle::getPlayStyleJSON(); ?>;
        $(document).ready(function () {

            if (shouldShowVersionPopup && true == <?= ($activeAccountInfo->delayUpdateAfterChests?"false":"true"); ?>)
            {
                ShowVersionPopUp();
            }

            $('.parallax-mouse-capture').on('mousemove', function(e) {
                var xPos = e.pageX - $(this).offset().left;
                var yPos = e.pageY - $(this).offset().top;
                $(this).prev().find('.parallax').css({transform: `translate(${xPos * -0.05}px, ${yPos * -0.05}px)`});
            });

            // For each iframe (video) in the carousel, save its src to a data attribute
            $('#topCarouselAd iframe').each(function() {
                $(this).attr('data-src', $(this).attr('src'));
            });

            // Carousel slide event
            $('#topCarouselAd').on('slide.bs.carousel', function (event) {
                var nextactiveslide = $(event.relatedTarget);

                // Check if the next active slide contains a video
                if (nextactiveslide.find('iframe').length > 0) {
                    // If it does, reset the src to start the video
                    var video = nextactiveslide.find('iframe');
                    video.attr('src', video.attr('data-src'));
                }

                // For each iframe (video) in the previous slide, stop the video by clearing the src
                $(event.from).find('iframe').each(function() {
                    $(this).attr('src', '');
                });
            });

            // Carousel slid event (when the sliding animation finishes)
            $('#topCarouselAd').on('slid.bs.carousel', function (event) {
                // For each iframe (video) in the now inactive slides, reset the src to the original value
                $('#topCarouselAd .carousel-item:not(.active)').find('iframe').each(function() {
                    $(this).attr('src', $(this).attr('data-src'));
                });
            });

            if (sessionStorage.getItem('showActionModal') === 'true') {
                // Show the modal
                if ($('#actionModal').length) {

                    $('#actionModal').modal("show");
                    DisableShowActionModal();
                }
            }

            
            if (isBetaEnabled())
            {
                $("#btnEnableBeta").hide();
            }
            else{

                $("#btnDisableBeta").hide();
            }

            $('#modalChest').modal({
                backdrop: 'static',
                keyboard: false
            })
            
            <?php
                if ($showPopUpSuccess)
                {
                    echo "ShowPopSuccess(".json_encode($PopUpMessage).",".json_encode($PopUpTitle).");";
                }
                
                if ($showPopUpError)
                {

                    echo "ShowPopError(".json_encode($PopUpMessage).",".json_encode($PopUpTitle).");";
                }
            ?>

            const dateElements = document.querySelectorAll('.date');

            dateElements.forEach(function (element) {
                const utcDateTime = element.getAttribute('data-datetime-utc');
                
                if (utcDateTime) {
                    // Create a Date object in the browser's local timezone
                    const localDate = new Date(utcDateTime);

                    // Format the date to a more readable local time
                    const formattedDate = localDate.toLocaleDateString(undefined, {
                        weekday: 'short',
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    }) + ' ' + localDate.toLocaleTimeString();

                    // Update the element's inner text with the formatted local date
                    element.innerText = formattedDate;
                }
            });
        });

        const myCarouselElement = document.querySelector('#topCarouselAd');
        if (myCarouselElement)
        {

            const carousel = new bootstrap.Carousel(myCarouselElement, {
                interval: 2000,
                touch: false
            });
        }

        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [];
        Array.prototype.forEach.call(tooltipTriggerList, function(tooltipTriggerEl) {
            tooltipList.push(new bootstrap.Tooltip(tooltipTriggerEl));
        });

        const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
        const popoverList = [];
        Array.prototype.forEach.call(popoverTriggerList, function(popoverTriggerEl) {
            popoverList.push(new bootstrap.Popover(popoverTriggerEl));
        });

        (function() {
            const USERNAME_SELECTOR = '.username';
            const BOUND_FLAG = 'playerCardPopoverBound';
            // Delay popover dismissal so users have time to move from the trigger
            // toward the floating card without it collapsing mid-flight.
            const SHOW_DELAY = 150;
            const HIDE_DELAY = 500;
            const accountCacheByUsername = new Map();
            const accountCacheById = new Map();
            const pendingRequests = new Map();
            const popoverInstances = new WeakMap();
            const showTimers = new WeakMap();
            const hideTimers = new WeakMap();
            const supportsHover = window.matchMedia ? window.matchMedia('(hover: hover)').matches : false;
            const hoverStates = new WeakMap();
            const DEBUG_PREFIX = '[PlayerCardPopover]';
            const DEBUG_ENABLED = true;

            function debugLog(element, message, details = undefined) {
                if (!DEBUG_ENABLED || typeof console === 'undefined' || typeof console.debug !== 'function') {
                    return;
                }

                const descriptor = describeElement(element);
                if (details !== undefined) {
                    console.debug(DEBUG_PREFIX, message, descriptor, details);
                } else {
                    console.debug(DEBUG_PREFIX, message, descriptor);
                }
            }

            function describeElement(element) {
                if (!(element instanceof HTMLElement)) {
                    return '(unknown trigger)';
                }

                const tag = element.tagName ? element.tagName.toLowerCase() : 'unknown';
                const id = element.id ? `#${element.id}` : '';
                const className = element.className ? `.${String(element.className).trim().replace(/\s+/g, '.')}` : '';
                const username = getElementUsername(element) || '(no username)';
                const accountId = getElementAccountId(element) || '(no account id)';

                return `${tag}${id}${className} username="${username}" accountId="${accountId}"`;
            }

            function isElementHovered(element) {
                if (!element) {
                    return false;
                }

                if (element.matches(':hover')) {
                    return true;
                }

                const hoveredElements = document.querySelectorAll(':hover');
                for (let i = hoveredElements.length - 1; i >= 0; i--) {
                    const hovered = hoveredElements[i];
                    if (hovered === element || element.contains(hovered)) {
                        return true;
                    }
                }

                return false;
            }

            function getHoverState(element) {
                let state = hoverStates.get(element);
                if (!state) {
                    state = { triggerHovered: false, popoverHovered: false };
                    hoverStates.set(element, state);
                }

                return state;
            }

            function normalize(value) {
                if (value === undefined || value === null) {
                    return '';
                }

                return String(value).trim();
            }

            function normalizeUsername(value) {
                return normalize(value).toLowerCase();
            }

            function decodeEntities(value) {
                if (!value || value.indexOf('&') === -1) {
                    return value;
                }

                const textarea = decodeEntities.textarea || (decodeEntities.textarea = document.createElement('textarea'));
                textarea.innerHTML = value;
                return textarea.value;
            }

            function cacheAccount(account) {
                if (!account || typeof account !== 'object') {
                    return null;
                }

                const username = normalize(account.username ?? '');
                const accountId = normalize(account.crand ?? account.accountId ?? account.account_id ?? '');

                if (username) {
                    accountCacheByUsername.set(normalizeUsername(username), account);
                }

                if (accountId) {
                    accountCacheById.set(accountId, account);
                }

                return account;
            }

            function getCachedAccount(username, accountId) {
                const normalizedId = normalize(accountId);
                if (normalizedId && accountCacheById.has(normalizedId)) {
                    return accountCacheById.get(normalizedId);
                }

                const normalizedUsername = normalizeUsername(username);
                if (normalizedUsername && accountCacheByUsername.has(normalizedUsername)) {
                    return accountCacheByUsername.get(normalizedUsername);
                }

                return null;
            }

            function getElementUsername(element) {
                const datasetUsername = normalize(element.dataset.username ?? '');
                if (datasetUsername) {
                    return normalize(decodeEntities(datasetUsername));
                }

                return normalize(element.textContent ?? '');
            }

            function getElementAccountId(element) {
                const datasetAccountId = normalize(element.dataset.accountId ?? '');
                if (datasetAccountId) {
                    return normalize(decodeEntities(datasetAccountId));
                }

                return null;
            }

            function getRequestKey(username, accountId) {
                const normalizedId = normalize(accountId);
                if (normalizedId) {
                    return `id:${normalizedId}`;
                }

                const normalizedUsername = normalizeUsername(username);
                if (normalizedUsername) {
                    return `user:${normalizedUsername}`;
                }

                return null;
            }

            function fetchAccountData(searchTerm) {
                const params = new URLSearchParams();
                params.append('searchTerm', searchTerm);
                params.append('page', '1');
                params.append('itemsPerPage', '1');

                return fetch('/api/v1/account/search.php?json', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: params
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.success && data.data && Array.isArray(data.data.accountItems) && data.data.accountItems.length > 0) {
                            return cacheAccount(data.data.accountItems[0]);
                        }

                        return null;
                    })
                    .catch(error => {
                        console.error('Failed to load account information for player card popover.', error);
                        return null;
                    });
            }

            function requestAccount(element) {
                const username = getElementUsername(element);
                const accountId = getElementAccountId(element);

                const cached = getCachedAccount(username, accountId);
                if (cached) {
                    return Promise.resolve(cached);
                }

                const searchTerm = username || accountId;
                if (!searchTerm) {
                    return Promise.resolve(null);
                }

                const requestKey = getRequestKey(username, accountId);
                if (requestKey && pendingRequests.has(requestKey)) {
                    return pendingRequests.get(requestKey);
                }

                const requestPromise = fetchAccountData(searchTerm).finally(() => {
                    if (requestKey) {
                        pendingRequests.delete(requestKey);
                    }
                });

                if (requestKey) {
                    pendingRequests.set(requestKey, requestPromise);
                }

                return requestPromise;
            }

            function clearTimer(map, element) {
                if (!map.has(element)) {
                    return;
                }

                const timerId = map.get(element);
                clearTimeout(timerId);
                map.delete(element);
                debugLog(element, 'Cleared timer', { mapName: map === showTimers ? 'showTimers' : 'hideTimers' });
            }

            function scheduleShow(element) {
                clearTimer(hideTimers, element);

                if (showTimers.has(element)) {
                    debugLog(element, 'Show timer already scheduled');
                    return;
                }

                debugLog(element, 'Scheduling show timer');
                const timerId = setTimeout(() => {
                    showTimers.delete(element);
                    debugLog(element, 'Show timer fired');
                    ensurePopover(element).then(popover => {
                        if (popover) {
                            debugLog(element, 'Showing popover');
                            popover.show();
                        }
                    });
                }, SHOW_DELAY);

                showTimers.set(element, timerId);
            }

            function scheduleHide(element) {
                clearTimer(showTimers, element);

                const popover = popoverInstances.get(element);
                if (popover && typeof popover.getTipElement === 'function') {
                    const tipElement = popover.getTipElement();
                    if (tipElement && (isElementHovered(tipElement) || tipElement.matches(':focus-within'))) {
                        const state = getHoverState(element);
                        state.popoverHovered = true;
                        clearTimer(hideTimers, element);
                        debugLog(element, 'Skipping hide timer because tip is hovered or focused');
                        return;
                    }
                }

                debugLog(element, 'Scheduling hide timer');
                const timerId = setTimeout(() => {
                    hideTimers.delete(element);
                    const state = getHoverState(element);
                    debugLog(element, 'Hide timer fired', {
                        triggerHovered: state.triggerHovered,
                        popoverHovered: state.popoverHovered
                    });
                    if (state.triggerHovered || state.popoverHovered) {
                        debugLog(element, 'Hide timer aborted because hover state is still active', {
                            triggerHovered: state.triggerHovered,
                            popoverHovered: state.popoverHovered
                        });
                        return;
                    }
                    const popover = popoverInstances.get(element);
                    if (popover) {
                        const tipElement = typeof popover.getTipElement === 'function' ? popover.getTipElement() : null;
                        if (tipElement && (isElementHovered(tipElement) || tipElement.matches(':focus-within'))) {
                            state.popoverHovered = true;
                            clearTimer(hideTimers, element);
                            debugLog(element, 'Hide timer aborted because tip regained hover before hiding');
                            return;
                        }

                        debugLog(element, 'Hiding popover');
                        popover.hide();
                    }
                }, HIDE_DELAY);

                hideTimers.set(element, timerId);
            }

            function ensurePopover(element) {
                if (typeof window.generatePlayerCardHTML !== 'function') {
                    return Promise.resolve(null);
                }

                return requestAccount(element).then(account => {
                    if (!account) {
                        return null;
                    }

                    const popover = getOrCreatePopover(element, account);
                    return popover;
                });
            }

            function getOrCreatePopover(element, account) {
                let popover = popoverInstances.get(element);
                const username = normalize(account.username ?? '');
                const accountId = normalize(account.crand ?? account.accountId ?? account.account_id ?? '');
                const usernameAttr = username.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                const accountIdAttr = accountId.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');

                if (username) {
                    element.dataset.username = usernameAttr;
                }

                if (accountId) {
                    element.dataset.accountId = accountIdAttr;
                }

                const content = window.generatePlayerCardHTML(account);

                if (!content) {
                    return null;
                }

                if (!popover) {
                    popover = new bootstrap.Popover(element, {
                        trigger: 'manual',
                        html: true,
                        sanitize: false,
                        customClass: 'player-card-popover',
                        content: content
                    });

                    popoverInstances.set(element, popover);

                    element.addEventListener('shown.bs.popover', () => handlePopoverShown(element));
                    element.addEventListener('hide.bs.popover', event => {
                        const state = getHoverState(element);
                        const tipElement = typeof popover.getTipElement === 'function' ? popover.getTipElement() : null;
                        const shouldKeepOpen = state.triggerHovered || state.popoverHovered ||
                            (tipElement && (tipElement.matches(':hover') || tipElement.matches(':focus-within')));

                        if (shouldKeepOpen) {
                            debugLog(element, 'Preventing hide.bs.popover because hover state is active', {
                                triggerHovered: state.triggerHovered,
                                popoverHovered: state.popoverHovered,
                                tipHovered: tipElement ? tipElement.matches(':hover') : false,
                                tipFocused: tipElement ? tipElement.matches(':focus-within') : false
                            });
                            event.preventDefault();
                            return;
                        }

                        debugLog(element, 'Allowing popover to hide');
                        clearTimer(showTimers, element);
                        clearTimer(hideTimers, element);
                        state.triggerHovered = false;
                        state.popoverHovered = false;
                    });
                } else if (typeof popover.setContent === 'function') {
                    popover.setContent({ '.popover-body': content });
                    if (typeof popover.update === 'function') {
                        popover.update();
                    }
                } else {
                    popover._config = popover._config || {};
                    popover._config.content = content;
                    const tip = popover.getTipElement ? popover.getTipElement() : null;
                    if (tip) {
                        const body = tip.querySelector('.popover-body');
                        if (body) {
                            body.innerHTML = content;
                        }
                    }
                }

                return popover;
            }

            function handlePopoverShown(element) {
                const popover = popoverInstances.get(element);
                if (!popover || typeof popover.getTipElement !== 'function') {
                    debugLog(element, 'Popover shown but instance missing or tip unavailable');
                    return;
                }

                const tipElement = popover.getTipElement();
                if (!tipElement) {
                    debugLog(element, 'Popover shown but tip element missing');
                    return;
                }

                const state = getHoverState(element);
                state.popoverHovered = true;
                clearTimer(hideTimers, element);
                debugLog(element, 'Popover shown and hover state updated');

                if (!tipElement.dataset.playerCardPopoverBound) {
                    const handleEnter = () => {
                        state.popoverHovered = true;
                        clearTimer(hideTimers, element);
                        debugLog(element, 'Tip enter detected');
                    };
                    const handleLeave = () => {
                        if (isElementHovered(tipElement)) {
                            debugLog(element, 'Tip leave ignored because hover still detected');
                            return;
                        }

                        state.popoverHovered = false;
                        debugLog(element, 'Tip leave detected, scheduling hide');
                        scheduleHide(element);
                    };
                    const handleOver = event => {
                        if (tipElement.contains(event.target)) {
                            debugLog(element, 'Tip mouseover detected for child element');
                            handleEnter();
                        }
                    };
                    const handleOut = event => {
                        const nextTarget = event.relatedTarget;
                        if (nextTarget && tipElement.contains(nextTarget)) {
                            debugLog(element, 'Tip mouseout ignored because moving inside tip');
                            return;
                        }
                        debugLog(element, 'Tip mouseout detected');
                        handleLeave();
                    };

                    tipElement.addEventListener('mouseenter', handleEnter);
                    tipElement.addEventListener('mouseleave', handleLeave);
                    tipElement.addEventListener('mouseover', handleOver);
                    tipElement.addEventListener('mouseout', handleOut);
                    tipElement.addEventListener('pointerenter', handleEnter);
                    tipElement.addEventListener('pointerleave', handleLeave);
                    tipElement.addEventListener('focusin', handleEnter);
                    tipElement.addEventListener('focusout', handleOut);
                    tipElement.dataset.playerCardPopoverBound = 'true';
                }

                if (isElementHovered(tipElement)) {
                    state.popoverHovered = true;
                    clearTimer(hideTimers, element);
                    debugLog(element, 'Tip already hovered on show, keeping hide timer cleared');
                }

                if (window.bootstrap && bootstrap.Tooltip) {
                    const tooltipElements = tipElement.querySelectorAll('[data-bs-toggle="tooltip"]');
                    tooltipElements.forEach(el => {
                        const instance = bootstrap.Tooltip.getInstance(el);
                        if (instance && typeof instance.update === 'function') {
                            instance.update();
                        } else if (typeof bootstrap.Tooltip.getOrCreateInstance === 'function') {
                            bootstrap.Tooltip.getOrCreateInstance(el);
                        } else {
                            new bootstrap.Tooltip(el);
                        }
                    });
                }

                if (window.bootstrap && bootstrap.Popover) {
                    const popoverElements = tipElement.querySelectorAll('[data-bs-toggle="popover"]');
                    popoverElements.forEach(el => {
                        const instance = bootstrap.Popover.getInstance(el);
                        if (instance && typeof instance.update === 'function') {
                            instance.update();
                        } else if (typeof bootstrap.Popover.getOrCreateInstance === 'function') {
                            bootstrap.Popover.getOrCreateInstance(el);
                        } else {
                            new bootstrap.Popover(el);
                        }
                    });
                }
            }

            function bindElement(element) {
                if (!(element instanceof HTMLElement)) {
                    return;
                }

                if (element.dataset[BOUND_FLAG]) {
                    return;
                }

                element.dataset[BOUND_FLAG] = 'true';
                getHoverState(element);

                const showHandler = () => {
                    const state = getHoverState(element);
                    state.triggerHovered = true;
                    debugLog(element, 'Trigger enter detected', { triggerHovered: state.triggerHovered });
                    scheduleShow(element);
                };
                const hideHandler = () => {
                    const state = getHoverState(element);
                    state.triggerHovered = false;
                    const popover = popoverInstances.get(element);
                    if (popover && typeof popover.getTipElement === 'function') {
                        const tipElement = popover.getTipElement();
                        if (tipElement && (isElementHovered(tipElement) || tipElement.matches(':focus-within'))) {
                            state.popoverHovered = true;
                            debugLog(element, 'Trigger leave ignored because tip is hovered or focused');
                            return;
                        }
                    }
                    debugLog(element, 'Trigger leave detected', {
                        triggerHovered: state.triggerHovered,
                        popoverHovered: state.popoverHovered
                    });
                    scheduleHide(element);
                };

                if (supportsHover) {
                    element.addEventListener('mouseenter', showHandler);
                    element.addEventListener('mouseleave', hideHandler);
                }

                element.addEventListener('focus', showHandler);
                element.addEventListener('blur', hideHandler);
            }

            function bindAll(root) {
                const elements = (root instanceof Element ? root : document).querySelectorAll(USERNAME_SELECTOR);
                elements.forEach(bindElement);
            }

            function observeUsernameElements() {
                if (!('MutationObserver' in window) || !document.body) {
                    return;
                }

                const observer = new MutationObserver(mutations => {
                    mutations.forEach(mutation => {
                        mutation.addedNodes.forEach(node => {
                            if (!(node instanceof Element)) {
                                return;
                            }

                            if (node.matches(USERNAME_SELECTOR)) {
                                bindElement(node);
                            }

                            bindAll(node);
                        });
                    });
                });

                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            }

            function initialize() {
                if (!document.body) {
                    return;
                }

                bindAll(document);
                observeUsernameElements();
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initialize);
            } else {
                initialize();
            }
        })();


        
        function LoadContainerLoot(containerLootId, callback = null) {

            const data = {
                lootId: containerLootId
            };

            const params = new URLSearchParams(data);

            fetch(`/api/v1/lich/get-container-cards.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: params,
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success && Array.isArray(data.data)) {
                        if (callback != null)
                            callback(true, data.data);
                    } else {
                        if (callback != null)
                            callback(false, data.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    if (callback != null)
                        callback(false, err);
                });
        }
        <?php 

        if (Kickback\Services\Session::isLoggedIn())
        {
        ?>

        var chests = <?php echo  $activeAccountInfo->chestsJSON; ?>;
        var notificationsJSON = <?php echo $activeAccountInfo->notificationsJSON; ?>;
    
        var chestElement = document.getElementById("imgChest");
        var imgShineBackground = document.getElementById("imgShineBackground");
        var imgShineForeground = document.getElementById("imgShineForeground");
        //var imgItem = document.getElementById("imgItem");
        var imgItemContainer = document.getElementById("imgItemWrapper");


        function submitTreasureHuntFoundObject(ctime, crand, url) {
                TreasureHuntFoundObject(ctime, crand, function(success, message) {
                    if (!success) {
                        ShowPopError(message,"Failed to collect treasure!");
                    }
                    else
                    {
                        window.location.href = url;
                    }
                });
            }
            
        function TreasureHuntFoundObject(ctime, crand, callback = null)
        {

            const data = {
                sessionToken: "<?= $_SESSION['sessionToken']; ?>",
                item_ctime: ctime,
                item_crand: parseInt(crand),
            };

            const params = new URLSearchParams(data);
            
            fetch(`<?= Version::urlBetaPrefix(); ?>/api/v1/event/treasure-hunt-found-object.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: params,
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        
                        if (callback != null)
                        {
                            callback(true,data.message);
                        }
                    } else {
                        if (callback != null)
                        {
                            callback(false,data.message);
                        }
                    }
                })
                .catch(err => {
                    if (callback != null)
                    {
                        callback(false, err);
                    }
                });
        }

        <?php if (Kickback\Services\Session::isSteward()) { ?>
            
            function TreasureHuntDeleteObject(ctime, crand, callback = null) {

                const data = {
                    sessionToken: "<?= $_SESSION['sessionToken']; ?>",
                    item_ctime: ctime,
                    item_crand: parseInt(crand),
                };

                const params = new URLSearchParams(data);

                
                fetch(`<?= Version::urlBetaPrefix(); ?>/api/v1/event/treasure-hunt-delete-object.php`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: params,
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            
                            if (callback != null)
                            {
                                callback(true,data.message);
                            }
                        } else {
                            if (callback != null)
                            {
                                callback(false,data.message);
                            }
                        }
                    })
                    .catch(err => {
                        if (callback != null)
                        {
                            callback(false, err);
                        }
                    });
            }

            function TreasureHuntHideObject(huntLocator, itemId, mediaId, oneTimeOnly, pageUrl, xPercent, yPercent, callback = null) {
                
                const data = {
                    sessionToken: "<?= $_SESSION['sessionToken']; ?>",
                    hunt_locator: huntLocator,
                    item_crand: parseInt(itemId),
                    media_id: parseInt(mediaId),
                    one_time_only: !!oneTimeOnly,
                    page_url: pageUrl,
                    x_percentage: parseFloat(xPercent),
                    y_percentage: parseFloat(yPercent)
                };

                const params = new URLSearchParams(data);

                fetch(`<?= Version::urlBetaPrefix(); ?>/api/v1/event/treasure-hunt-hide-object.php`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: params,
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            
                            if (callback != null)
                            {
                                callback(true,data.message);
                            }
                        } else {
                            if (callback != null)
                            {
                                callback(false,data.message);
                            }
                        }
                    })
                    .catch(err => {
                        if (callback != null)
                        {
                            callback(false, err);
                        }
                    });
            }
        <?php } ?>
        function GiveLootNickname(lootId, nickname, description, callback = null)
        {
            if (nickname == null || nickname.trim() == "")
            {
                if (callback != null)
                    callback(false, "Please provide a nickname.");
            }
            
            const data = {
                sessionToken: "<?= $_SESSION['sessionToken']; ?>",
                lootId: lootId,
                nickname: nickname,
                description: description
            };

            const params = new URLSearchParams(data);
            
            fetch(`<?= Version::urlBetaPrefix(); ?>/api/v1/loot/give-nickname.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: params,
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        
                        if (callback != null)
                        {
                            callback(true);
                        }
                    } else {
                        if (callback != null)
                        {
                            callback(false,data.message);
                        }
                    }
                })
                .catch(err => {
                    if (callback != null)
                    {
                        callback(false, err);
                    }
                });
        }
        
        function TransferLootIntoContainer(lootId, toContainerLootId, callback = null)
        {
            const data = {
                sessionToken: "<?= $_SESSION['sessionToken']; ?>",
                itemLootId: lootId,
                toContainerLootId: toContainerLootId
            };

            const params = new URLSearchParams(data);

            fetch(`<?= Version::urlBetaPrefix(); ?>/api/v1/container/transfer.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: params,
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        
                        if (callback != null)
                        {
                            callback(true);
                        }
                    } else {
                        if (callback != null)
                        {
                            callback(false,data.message);
                        }
                    }
                })
                .catch(err => {
                    if (callback != null)
                    {
                        callback(false, err);
                    }
                });
        }

        function OpenChest() {
            //var chestRarityArray = [9,9,9,9,9,9];
            chestElement.src = "/assets/media/chests/Loot_Box_0" + (parseInt(chests[0]["rarity"]) + 1) + "_02_Star.png";
            //$('#imgItem').addClass('chest-item-animate');
            
            imgItemContainer.classList.add('animate-flip');

            $('#modalChest').addClass('chest-open-animate');
            imgShineForeground.style.visibility = "visible";
            imgShineBackground.style.visibility = "hidden";
            imgShineForeground.src = "/assets/media/chests/" + chests[0]["rarity"] + "_o_s.png";
            imgItemContainer.style.visibility = "visible";

            
            // Set front and back images
            //document.getElementById('imgItemFront').src = "/assets/media/" + chests[0]["ItemImg"];
            //document.getElementById('imgItemBack').src = "https://kickback-kingdom.com/assets/images/lich/decks/back.jpg";

            // Trigger flip
            document.getElementById('imgItemWrapper').classList.add('flipped');
        }

        function ShowChest() {
            StartConfetti();
            if (chests[0]["Id"] % 8 == 0) {
                chests[0]["rarity"] = 0;
            }
            //https://kickback-kingdom.com/assets/media/chests/Loot_Box_02_01_Star.png
            //var chestRarityArray = [10,10,10,10,10,10];
            chestElement.src = "/assets/media/chests/Loot_Box_0" + (parseInt(chests[0]["rarity"]) + 1) + "_01_Star.png";
            //$('#imgItem').removeClass('chest-item-animate');
            imgItemContainer.classList.remove('animate-flip');
            $('#modalChest').removeClass('chest-open-animate');
            imgShineBackground.style.visibility = "visible";
            imgShineForeground.style.visibility = "hidden";
            imgShineBackground.src = "/assets/media/chests/" + chests[0]["rarity"] + "_c_s.png";
            imgItemContainer.style.visibility = "hidden";
            //imgItem.src = "/assets/media/" + chests[0]["ItemImg"];

            document.getElementById('imgItemFront').src = "/assets/media/" + chests[0]["ItemImg"];
            document.getElementById('imgItemBack').src = "/assets/media/" + chests[0]["ItemImgBack"];

            $("#modalChest").modal("show");
        }

        function CloseChest() {

            $("#modalChest").modal("hide");

            const data = {
                chestId: chests[0]["Id"],
                accountId: <?php echo Kickback\Services\Session::getCurrentAccount()->crand; ?>,
                sessionToken: "<?php echo $_SESSION["sessionToken"]; ?>"
            };
            chests.shift();
            const params = new URLSearchParams();

            for (const [key,value] of Object.entries(data)) {
                params.append(key, value);
            }
            
            fetch('<?= Version::formatUrl("/api/v1/chest/close.php?json"); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: params
            }).then(response=>response.text()).then(data=>console.log(data));
        
        }

        function ToggleChest() {
            if ($('#modalChest').hasClass('show')) {
                if (imgItemContainer.style.visibility == "hidden") {

                    OpenChest();
                } else {

                    NextChest();
                }
            } else {
                ShowChest();
            }
        }

        function OpenAllChests() {
            if (chests.length > 0) {
                ToggleChest();
            }
        }

        function NextChest() {
            CloseChest();
            if (chests.length > 0) {
                setTimeout(()=>{
                    ShowChest();
                }
                , (500));

            } else {
                StopConfetti();
                if (shouldShowVersionPopup && true == <?= ($activeAccountInfo->delayUpdateAfterChests?"true":"false"); ?>)
                {
                    ShowVersionPopUp();
                }
                showNextPopups();
            }
        }
        <?php

        } else {
        
        ?>

            function submitTreasureHuntFoundObject(ctime, crand, url) {
                
                window.location.href = "/login.php?redirect="+url;
            }
        <?php } ?>



        function SetShowActionModal()
        {
            sessionStorage.setItem('showActionModal', 'true');
        }

        function DisableShowActionModal()
        {
            sessionStorage.setItem('showActionModal', 'false');
        }
        
        const Confettiful = function (el) {
            this.el = el;
            this.containerEl = null;

            this.confettiFrequency = 3;
            this.confettiColors = ['#fce18a', '#ff726d', '#b48def', '#f4306d'];
            this.confettiAnimations = ['slow', 'medium', 'fast'];

            this._setupElements();
            this._renderConfetti();
        };

        Confettiful.prototype._setupElements = function () {
        const containerEl = document.createElement('div');
        const elPosition = this.el.style.position;

        if (elPosition !== 'relative' || elPosition !== 'absolute') {
            this.el.style.position = 'relative';
        }

        containerEl.classList.add('confetti-container');
        containerEl.style="pointer-events:none;z-index:10000;";
        this.el.appendChild(containerEl);

        this.containerEl = containerEl;
        };

        Confettiful.prototype._renderConfetti = function () {
        this.confettiInterval = setInterval(() => {
            const confettiEl = document.createElement('div');
            const confettiSize = Math.floor(Math.random() * 3) + 7 + 'px';
            const confettiBackground = this.confettiColors[Math.floor(Math.random() * this.confettiColors.length)];
            const confettiLeft = Math.floor(Math.random() * this.el.offsetWidth) + 'px';
            const confettiAnimation = this.confettiAnimations[Math.floor(Math.random() * this.confettiAnimations.length)];

            confettiEl.classList.add('confetti', 'confetti--animation-' + confettiAnimation);
            confettiEl.style.left = confettiLeft;
            confettiEl.style.width = confettiSize;
            confettiEl.style.height = confettiSize;
            confettiEl.style.backgroundColor = confettiBackground;

            confettiEl.removeTimeout = setTimeout(function () {
            confettiEl.parentNode.removeChild(confettiEl);
            }, 3000);

            this.containerEl.appendChild(confettiEl);
        }, 25);
        };



        function StartConfetti()
        {

            window.confettiful = new Confettiful(document.querySelector('.js-container-confetti'));
        }

        function StopConfetti()
        {
            delete window.confettiful;
            $("#div1").remove();
            $("div").remove(".confetti-container");
        }


        
        function enableBeta() {
            window.location.href = "/beta/";
        }

        function disableBeta() {
            window.location.href = "/";
        }

        function toggleBeta() {

            // If "betaEnabled" is the string "true", set it to "false". Otherwise, set it to "true".
            if (isBetaEnabled()) {
                disableBeta();
            } else {
                enableBeta();
            }
        }

        function isBetaEnabled() {
            return <?= Version::isBeta() ? "true" : "false"?>;
        }

        var itemInformation = <?= (isset($itemInformationJSON)?$itemInformationJSON:"[]"); ?>;
        var itemStackInformation = <?= (isset($itemStackInformationJSON)?$itemStackInformationJSON:"[]"); ?>;
        function ShowInventoryItemModal(itemId, lootId) 
        {
            CloseInventoryItemContainerModal();

            var item = GetItemInformationById(itemId);
            console.log(item);
            var primaryImage = $("#inventoryItemImage");
            var secondaryImage = $("#inventoryItemImageSecondary");
            primaryImage.css("display", "block");
            secondaryImage.css("display", "none");
            primaryImage.attr("src", item.iconBig.url);

            $("#inventoryItemDescription").text(item.description);
            $("#inventoryItemTitle").text(item.name);
            $("#inventoryItemArtist").text(item.iconBig.author.username);
            $("#inventoryItemArtist").attr("href", "<?php echo Version::urlBetaPrefix(); ?>/u/"+item.iconBig.author.username);
            $("#inventoryItemDate").text(item.date_created);
            $("#inventoryItemModal").modal("show");
            $("#inventoryItemImageContainer").attr("onclick","FlipInventoryItem("+itemId+");");
            $("#inventoryItemCopyContainer").addClass("d-none");
            $("#inventoryItemFooter").addClass("d-none");

            SetupItemInventoryModal(item, lootId);

            primaryImage.addClass("animate__jackInTheBox");

            primaryImage.on('animationend', function() {
                primaryImage.removeClass("animate__jackInTheBox");

                // Reset event handler
                primaryImage.off('animationend');
            });

            setTimeout(function() {
                $('[data-bs-toggle="tooltip"]').tooltip('hide');
            }, 10);
        }

        function CloseInventoryItemModal()
        {
            
            $("#inventoryItemModal").modal("hide");
        }

        function CloseInventoryItemContainerModal()
        {
            
            $("#inventoryItemContainerModal").modal("hide");
        }



        function SetupItemInventoryModal(item, lootId)
        {
            if (item.crand == "14" && myNextWritOfPassageURL != '')
            {
                $("#inventoryItemCopyInput").val(myNextWritOfPassageURL);
                $("#inventoryItemCopyContainer").removeClass("d-none");
                $("#inventoryItemFooter").removeClass('d-none');
            }

            
            if (item.useable) {
                $("#inventoryItemUseButton").removeClass('d-none');
                $("#inventoryItemFooter").removeClass('d-none');
                $("#inventoryItemUseButton").attr("onclick", "UseInventoryItem("+item.crand+");");
            } else {
                $("#inventoryItemUseButton").addClass('d-none');
                $("#inventoryItemUseButton").attr("onclick", "");
            }

            
            // Show container open section if item is a container
            if (item.isContainer) {
                $("#inventoryItemContainerSection").show();
                $("#inventoryItemOpenContainerButton").attr("onclick", "OpenContainer(" + lootId + ");");

                if (item.itemCategory === 2) {
                    //$('#containerIcon').attr('class', 'fa-regular fa-cards-blank');
                    $('#containerExplanation').html(`
                        This can hold <strong>L.I.C.H. trading cards</strong>. 
                        You can view the cards inside and use them to <strong>build a deck</strong> for battle.
                    `);
                    
                    $('#inventoryItemEditDeckButton').removeClass('d-none');
                    $("#inventoryItemEditDeckButton").attr("href", `/lich/deck/edit/${lootId}`);

                } else {
                    //$('#containerIcon').attr('class', 'fa-duotone fa-regular fa-box-open');
                    $('#containerExplanation').html(`
                        This is a <strong>container</strong>. Click below to view its contents.
                    `);
                    
                    $('#inventoryItemEditDeckButton').addClass('d-none');
                    $("#inventoryItemEditDeckButton").attr("href", "#");
                }

            } else {
                $("#inventoryItemContainerSection").hide();
                $("#inventoryItemOpenContainerButton").attr("onclick", "");
            }
        }

        function HandleItemInventoryFlip(item, secondaryImage)
        {
                if (item.crand == "14")
                {
                    var imgData = GenerateQRCodeImageData(myNextWritOfPassageURL, function(imageData) {
                        secondaryImage.attr("src", imageData);
                    });

                }

        }

        function PopulateContainer(data) {
            $('#inventoryItemContainerTitle').text(data.containerName || "Container Contents");

            // The array of items is in data.data
            if (data.success && Array.isArray(data.data)) {

                if (data.data.length === 0) {
                    const emptyFlavorTexts = [
                        "Nothing in here but some cobwebs and dust. Seems this chest hasn't seen use in many moons.",
                        "The container lies bare—its treasures taken or never placed.",
                        "Not but air and old wood within.",
                        "The lid creaks open... and reveals naught.",
                        "You peer inside. A hollow container, waiting to be filled.",
                        "No tools, no trinkets—just the echo of emptiness.",
                        "A fine box, but nothing rests within it... yet."
                    ];
                    const randomFlavor = emptyFlavorTexts[Math.floor(Math.random() * emptyFlavorTexts.length)];


                    $('#inventoryItemContainerContents')
                        .html(`
                            <div class="text-center text-muted my-4">
                                <i class="fa-solid fa-spider-web fa-2x mb-3"></i>
                                <p>This container is empty.</p>
                                <p><em>"${randomFlavor}"</em></p>
                            </div>
                        `)
                        .removeClass('d-none').removeClass("inventory-grid");
                    $('#inventoryItemContainerLoading').addClass('d-none');
                    return;
                }

                data.data.forEach(stack => {
                    AddItemIfNotExists(stack.item);
                });

                const containerHTML = data.data.map(stack => `
                    <div class="inventory-item" onclick="ShowInventoryItemModal(${stack.item.crand}, ${stack.nextLootId.crand});" data-bs-toggle="tooltip" data-bs-placement="bottom" title="${stack.item.name}">
                        <img src="${stack.item.iconBig.url}" alt="${stack.item.name}">
                        <div class="item-count">x${stack.amount}</div>
                    </div>
                `).join('');

                $('#inventoryItemContainerContents').html(containerHTML).removeClass('d-none').addClass('inventory-grid');
                $('#inventoryItemContainerLoading').addClass('d-none');

                initializeTooltipsInElement(document.getElementById('inventoryItemContainerContents'));
            } else {
                $('#inventoryItemContainerLoading').html(`<p>Failed to load container contents.</p>`);
            }
        }


        function EditDeck(lootId) {
            // You can customize this
            alert("Deck Editor coming soon!");
            // or navigate: window.location.href = `/lich/deck-builder?id=${selectedDeckId}`;
        }

        
        function OpenContainer(lootId) {
            CloseInventoryItemModal();
            // Reset modal
            $('#inventoryItemContainerTitle').text("Loading...");
            $('#inventoryItemContainerContents').html('').addClass('d-none');
            $('#inventoryItemContainerLoading').removeClass('d-none');

            // Show modal
            $('#inventoryItemContainerModal').modal('show');

            // Call the API to fetch container contents
            fetch(`/api/v1/container/open.php?lootId=${lootId}`)
                .then(response => response.json())
                .then(data => {
                    
                    PopulateContainer(data);
                })
                .catch(err => {
                    console.error(err);
                    $('#inventoryItemContainerLoading').html(`<p>Error loading container.</p>`);
                });
        }


        function CopyContainerToClipboard()
        {
            var input = document.getElementById('inventoryItemCopyInput');
    
            // Select the content of the input
            input.select();
            input.setSelectionRange(0, 99999); // For mobile devices
            
            // Copy the selected text to clipboard
            navigator.clipboard.writeText(input.value)
                .then(() => {
                    console.log('Text copied to clipboard');
                })
                .catch(err => {
                    console.error('Failed to copy text: ', err);
                });
        }

        function UseInventoryItem(itemId) {
            FlipInventoryItem(itemId);
        }

        function FlipInventoryItem(itemId) {
            var item = GetItemInformationById(itemId);
            var primaryImage = $("#inventoryItemImage");
            var secondaryImage = $("#inventoryItemImageSecondary");

            // Determine which image is currently visible
            var flipFrom = primaryImage.is(':visible') ? primaryImage : secondaryImage;
            var flipTo = primaryImage.is(':visible') ? secondaryImage : primaryImage;

            // Set the secondary image if it's about to be shown
            if (!primaryImage.is(':visible')) {
                flipTo.attr("src", item.iconBig.url); 
            } else {
                flipTo.attr("src", item.iconBack.url); 

                HandleItemInventoryFlip(item, secondaryImage);
            }

            // Start the flip out animation
            flipFrom.addClass("animate__flipOutY");
            flipFrom.on('animationend', function() {
                flipFrom.removeClass("animate__flipOutY").css("display", "none");
                flipTo.css("display", "block").addClass("animate__flipInY2");

                // Reset event handler to prevent memory leak and multiple triggers
                flipFrom.off('animationend');
            });

            flipTo.on('animationend', function() {
                flipTo.removeClass("animate__flipInY2");

                // Reset event handler
                flipTo.off('animationend');
            });
        }

        function AddItemIfNotExists(downloadedItem) {
            const exists = itemInformation.some(item => item.crand === downloadedItem.crand);
            if (!exists) {
                itemInformation.push(downloadedItem);
            }
        }


        function GetItemInformationById(id)
        {
            for (let index = 0; index < itemInformation.length; index++) {
                var item = itemInformation[index];
                if (item.crand == id)
                {
                    return item;
                }
            }
            return null;
        }

        function LoadQuestHostReviewModal(id)
        {

        }
        
        function LoadNotificationViewPrestige(id) {
            var notification = notificationsJSON[id];
            console.log(notification);

            var titleAnimation = 'backInLeft';
             
            var positiveTitles = [
                "The Kingdom Honors Your Deeds…",
                "Your Name Echoes in the Halls…",
                "Legends Speak of Your Valor…",
                "The Bards Sing of Your Glory…",
                "Your Prestige Grows Across the Land…",
                "Your Feats Will Be Remembered…",
                "Your Name is Etched in History…",
                "The Realm Rejoices at Your Triumph…",
                "Songs of Your Honor Fill the Taverns…",
                "You Have Brought Great Renown…",
                "A New Chapter of Glory is Written…",
                "Your Reputation Shines Brighter…",
                "A Tale of Valor is Told Once More…",
                "The Banners Fly in Your Honor…",
                "Knights and Nobles Speak of You…",
                "The People Whisper of Your Bravery…",
                "Legends Are Forged by Actions Like These…",
                "The Heralds Announce Your Greatness…",
                "A Hero's Name is Spoken Again…",
                "Your Strength and Wisdom Prevail…",
                "You Have Secured Your Place in History…",
                "The Kingdom Stands Behind You…",
                "Echoes of Your Triumph Resound…",
                "The Council Recognizes Your Worth…",
                "Your Leadership is Praised Throughout the Land…"
            ];
            
            var negativeTitles = [
                "The Kingdom Does Not Forget…",
                "Your Name Fades from Memory…",
                "A Mark Stains Your Legacy…",
                "Whispers of Dishonor Spread…",
                "Your Reputation is Tarnished…",
                "The People Speak in Shadows…",
                "Your Deeds Are Questioned…",
                "A Dark Cloud Hangs Over Your Name…",
                "Once Respected, Now Shunned…",
                "A Warning is Issued in Your Name…",
                "The Kingdom Watches with Displeasure…",
                "Your Legacy Begins to Crumble…",
                "Murmurs of Betrayal Circulate…",
                "Your Presence No Longer Commands Respect…",
                "The Walls Whisper of Your Fall…",
                "The Realm Mourns Its Lost Trust…",
                "A Blow to Your Honor is Dealt…",
                "The Bards Tell a Cautionary Tale…",
                "You Have Been Cast in a Different Light…",
                "The Court No Longer Speaks of You…",
                "Once Celebrated, Now Forgotten…",
                "The Council Watches with Suspicion…",
                "A Great House Crumbles in Shame…",
                "The People No Longer Cheer Your Name…",
                "The Histories Record Your Missteps…"
            ];

            var prestigeId = notification.prestigeReview.crand;
            
            // Function to close modal and mark as viewed
            function closeModal() {
                $("#notificationViewPrestigeModal").modal("hide");
                MarkPrestigeAsViewed(prestigeId, id);
            }

            function MarkPrestigeAsViewed(prestigeId, index) {
                const data = {
                    prestigeId: prestigeId,
                    accountId: <?php echo Kickback\Services\Session::isLoggedIn() ? Kickback\Services\Session::getCurrentAccount()->crand : 'null'; ?>,
                    sessionToken: "<?php echo $_SESSION["sessionToken"] ?? ''; ?>"
                };


                console.log(data);

                const params = new URLSearchParams();

                for (const [key, value] of Object.entries(data)) {
                    params.append(key, value);
                }

                fetch('<?= Version::formatUrl("/api/v1/prestige/mark_viewed.php?json"); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: params
                }).then(response => response.text())
                    .then(data => {
                        console.log("Server Response:", data);
                        
                        // Remove the notification div
                        $(".toast").eq(index).fadeOut(300, function () {
                            $(this).remove();
                        });

                        // Remove the notification from the JSON array
                        notificationsJSON.splice(index, 1);
                    });
            }

            
            // Reset animations before showing modal
            $("#animated-prestige-title").removeClass("animate__"+titleAnimation).css("opacity", "0");
            $("#animated-prestige-body").removeClass("animate__fadeInUp").css("opacity", "0");
                    
            
            // Show modal
            $("#notificationViewPrestigeModal").modal('show');
            
            
            // Disable closing by blocking clicks
            $("#notificationViewPrestigeModal, #animated-prestige-body").off("click");
                    

            // Enable closing anywhere inside modal after 3 seconds
            setTimeout(() => {
                $("#notificationViewPrestigeModal, #animated-prestige-body").on("click", closeModal);
            }, 3500);
            // Choose a random title based on commendation status
            var title = notification.prestigeReview.commend ? 
                positiveTitles[Math.floor(Math.random() * positiveTitles.length)] : 
                negativeTitles[Math.floor(Math.random() * negativeTitles.length)];
            
            $("#animated-prestige-title").text(title);
            // Apply animation using Animate.css
            setTimeout(() => {
                $("#animated-prestige-title").css("opacity", "1").addClass("animate__animated animate__"+titleAnimation);
            }, 100);
            
            // Delay body animation slightly after title animation
            setTimeout(() => {
                $("#animated-prestige-body").css("opacity", "1").addClass("animate__animated animate__fadeInUp");
            }, 2500);
            
            // Populate review content immediately
            $("#notification-view-prestige-avatar").attr("src", notification.prestigeReview.fromAccount.avatar.url);
            $("#notification-view-prestige-username").text(notification.prestigeReview.fromAccount.username);
            $("#notification-view-prestige-date").text(notification.date.formattedBasic);
            $("#notification-view-prestige-message").text(notification.prestigeReview.message);
            
            // Change header background color and title based on commendation
            if (notification.prestigeReview.commend) {
                $("#notification-view-prestige-commend").html('<span style="background: #28a745; color: white; padding: 5px 10px; border-radius: 4px;">Commended</span>');
            } else {
                $("#notification-view-prestige-commend").html('<span style="background: #dc3545; color: white; padding: 5px 10px; border-radius: 4px;">Denounced</span>');
            }
        }
        function LoadQuestReviewModal(id)
        {
            var notification = notificationsJSON[id];

            $("#quest-review-quest-image").attr("src", notification.quest.icon.url);
            $("#quest-review-quest-title-link").attr("href", "/q/"+notification.quest.locator);
            $("#quest-review-quest-title").text(notification.quest.title);
            $("#quest-review-quest-host-1").attr("href", "/u/"+notification.quest.host1.username);
            $("#quest-review-quest-host-1").text(notification.quest.host1.username);
            if (notification.quest.host2 == null)
            {
                
                $("#quest-review-quest-host-2-span").attr("class","d-none");
            }
            else
            {

                $("#quest-review-quest-host-2").attr("href", "/u/"+notification.quest.host2.username);
                $("#quest-review-quest-host-2").text(notification.quest.host2.username);
                $("#quest-review-quest-host-2-span").attr("class","d-inline");
            }
            
            let dateObject = new Date(notification.date.valueString + 'Z');


            let options = { year: 'numeric', month: 'short', day: 'numeric' };
            let formattedDate = dateObject.toLocaleDateString(undefined, options);

            $("#quest-review-quest-date").text(formattedDate);


            $("#quest-review-play-style").attr("class","quest-tag quest-tag-"+play_styles[notification.quest.playStyle].name.toLowerCase());
            $("#quest-review-play-style").text(play_styles[notification.quest.playStyle].name);
            $("#quest-review-quest-summary").text(notification.quest.summary);
            $("#quest-review-quest-id").attr("value",notification.quest.crand);
            OpenQuestReviewModal();
        }

        function OpenQuestReviewModal()
        {
            $("#questReviewModal").modal('show');
        }

        $(document).ready(function() {
    $('.star-rating i').on('mouseover', function(){
        var onStar = parseInt($(this).data('rating'), 10);
        $(this).parent().children('i').each(function(e){
            if (e < onStar) {
                $(this).addClass('hover');
            }
            else {
                $(this).removeClass('hover');
            }
        });
    }).on('mouseout', function(){
        $(this).parent().children('i').each(function(e){
            $(this).removeClass('hover');
        });
    });
    
    $('.star-rating i').on('click', function(){
        var onStar = parseInt($(this).data('rating'), 10);
        $(this).parent().children('i').each(function(e){
            if (e < onStar) {
                $(this).removeClass('fa-regular fa-star').addClass('fa-solid fa-star selected');
            }
            else {
                $(this).removeClass('fa-solid fa-star selected').addClass('fa-regular fa-star');
            }
        });
        $(this).siblings('input.rating-value').val(onStar);
    });
});


function ShowPopSuccess(message, title)
{
    $("#successModalLabel").text(title);
    $("#successModalMessage").text(message);
    $("#successModal").modal("show");
    console.log(message);
}
function ShowPopError(message, title)
{
    $("#errorModalLabel").text(title);
    $("#errorModalMessage").text(message);
    $("#errorModal").modal("show");
    console.log(message);
    
}

let loadingBarProgressInterval;

function ShowLoadingBar() {
    $("#loadingModal").modal("show");
    
    let progressBar = document.getElementById('loadingModalProgressBar');
    let progressContainer = document.getElementById('loadingModalProgress');
    let progress = 0; // initial width
    let increment = 2; // smaller initial increment value for smoother start
    let capProgress = 90;
    let reductionRate = 0.98; // slightly smaller reduction for a smoother slow down
    loadingBarProgressInterval = setInterval(() => {
        // Increase the progress
        progress += increment;

        // Reduce the increment to make it slower
        increment *= reductionRate;

        // Cap the progress at 90% to not make it full until the task is done
        if (progress > capProgress) progress = capProgress;

        // Update the progress bar width and set aria-valuenow
        progressBar.style.width = `${progress}%`;
        progressContainer.setAttribute('aria-valuenow', progress);
    }, 50); // reduced interval for smoother updates
}


function HideLoadingBar() {
    let progressBar = document.getElementById('loadingModalProgressBar');
    let progressContainer = document.getElementById('loadingModalProgress');
    
    // Set progress to 100%
    progressBar.style.width = '100%';
    progressContainer.setAttribute('aria-valuenow', 100);

    // Clear the interval
    clearInterval(loadingBarProgressInterval);

    // Wait for 1 second, then hide the modal
    setTimeout(() => {
        $("#loadingModal").modal("hide");
    }, 1000);
}

function removePrefix(str, prefix) {
    
    if (str.startsWith(prefix)) {
        return str.slice(prefix.length);
    }
    
    return str;
}

function initializeTooltipsInElement(element) {
    var tooltipTriggerList = [].slice.call(element.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

window.onload = function() {
    const timeInputs = document.querySelectorAll('input[data-utc-time]');

    timeInputs.forEach(timeInput => {
        const utcTime = timeInput.getAttribute('data-utc-time');

        // Create a date object with the UTC time
        const utcDate = new Date(`1970-01-01T${utcTime}Z`); // The 'Z' at the end specifies UTC
        
        // Get hours and minutes in local timezone
        const localHours = String(utcDate.getHours()).padStart(2, '0');
        const localMinutes = String(utcDate.getMinutes()).padStart(2, '0');

        // Set the input's value to the local time
        timeInput.value = `${localHours}:${localMinutes}`;
    });
};
<?php if (Kickback\Services\Session::isAdmin()) { ?>
    function UseDelegateAccess(accountId)
    {
        window.location.href = "<?php echo Version::urlBetaPrefix(); ?>/?delegateAccess="+accountId;
    }

<?php } ?>
    </script>

<?php 

require_once('base-page-version-popup.php');
require("base-page-loading-overlay-javascript.php"); 
require("base-page-javascript-account-search.php"); 


?>
