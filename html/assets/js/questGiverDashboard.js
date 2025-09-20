(function ($) {
    'use strict';

    function ensureStarHelper() {
        if (typeof window.renderStarRatingJs !== 'function') {
            window.renderStarRatingJs = function (rating) {
                const rounded = Math.round((rating || 0) * 2) / 2;
                let stars = '<span class="star-rating" style="pointer-events: none; display: inline-block;">';
                for (let i = 1; i <= 5; i += 1) {
                    let cls;
                    if (rounded >= i) {
                        cls = 'fa-solid fa-star selected';
                    } else if (rounded >= i - 0.5) {
                        cls = 'fa-solid fa-star-half-stroke selected';
                    } else {
                        cls = 'fa-regular fa-star';
                    }
                    stars += `<i class="${cls}"></i>`;
                }
                return `${stars}</span>`;
            };
        }
    }

    function getStarRenderer() {
        ensureStarHelper();
        return window.renderStarRatingJs;
    }

    function hideElement($el) {
        if ($el && $el.length) {
            $el.addClass('d-none');
        }
    }

    function showElement($el) {
        if ($el && $el.length) {
            $el.removeClass('d-none');
        }
    }

    const summarySelectors = {
        hosted: $('[data-summary-key="hosted"]'),
        uniqueParticipants: $('[data-summary-key="uniqueParticipants"]'),
        recentHost: $('[data-summary-key="recentHost"]'),
        recentQuest: $('[data-summary-key="recentQuest"]')
    };

    const sectionConfig = {
        suggestions: {
            spinner: '#suggestionsSpinner',
            error: '#suggestionsError',
            empty: '#suggestionsEmpty',
            content: '#suggestionsContent'
        },
        questLines: {
            spinner: '#questLinesSpinner',
            error: '#questLinesError',
            empty: '#questLinesEmpty',
            content: '#questLinesContent'
        },
        top: {
            spinner: '#topSpinner',
            error: '#topError',
            content: '#topContent'
        }
    };

    function renderSectionState(sectionKey, state, message) {
        const config = sectionConfig[sectionKey];
        if (!config) {
            return;
        }
        const $spinner = config.spinner ? $(config.spinner) : null;
        const $error = config.error ? $(config.error) : null;
        const $empty = config.empty ? $(config.empty) : null;
        const $content = config.content ? $(config.content) : null;

        if ($spinner && $spinner.length) {
            if (state === 'loading') {
                showElement($spinner);
            } else {
                hideElement($spinner);
            }
        }

        if ($error && $error.length) {
            if (state === 'error') {
                $error.text(message || 'Unable to load data.').removeClass('d-none');
            } else {
                $error.addClass('d-none').text('');
            }
        }

        if ($empty && $empty.length) {
            if (state === 'empty') {
                showElement($empty);
            } else {
                hideElement($empty);
            }
        }

        if ($content && $content.length) {
            if (state === 'ready') {
                showElement($content);
            } else {
                hideElement($content);
            }
        }
    }

    function resetSummary(card) {
        if (!card || !card.length) {
            return;
        }
        hideElement(card.find('.summary-error'));
        hideElement(card.find('.summary-rating'));
        hideElement(card.find('.summary-value'));
    }

    function showSummaryError(key, message) {
        const card = summarySelectors[key];
        if (!card || !card.length) {
            return;
        }
        hideElement(card.find('.summary-spinner'));
        resetSummary(card);
        const errorEl = card.find('.summary-error');
        if (errorEl.length) {
            errorEl.text(message).removeClass('d-none');
        }
    }

    function setSummaryNumber(key, value) {
        const card = summarySelectors[key];
        if (!card || !card.length) {
            return;
        }
        hideElement(card.find('.summary-spinner'));
        hideElement(card.find('.summary-error'));
        const valueEl = card.find('.summary-value');
        if (valueEl.length) {
            const numeric = Number.isFinite(value) ? value : null;
            valueEl.text(numeric !== null ? numeric.toLocaleString() : '—').removeClass('d-none');
        }
    }

    function setSummaryMessage(key, message) {
        const card = summarySelectors[key];
        if (!card || !card.length) {
            return;
        }
        hideElement(card.find('.summary-spinner'));
        hideElement(card.find('.summary-error'));
        const messageEl = card.find('.summary-value');
        if (messageEl.length) {
            messageEl.text(message).removeClass('d-none');
        }
        hideElement(card.find('.summary-rating'));
    }

    function setSummaryRating(key, rating) {
        const card = summarySelectors[key];
        if (!card || !card.length) {
            return;
        }
        hideElement(card.find('.summary-spinner'));
        hideElement(card.find('.summary-error'));
        const ratingEl = card.find('.summary-rating');
        const valueEl = card.find('.summary-value');
        if (typeof rating === 'number' && !Number.isNaN(rating) && rating > 0) {
            if (ratingEl.length) {
                const renderStarRating = getStarRenderer();
                const ratingHtml = `${renderStarRating(rating)}<span class="ms-1">${rating.toFixed(2)}/5</span>`;
                ratingEl.html(ratingHtml).removeClass('d-none');
            }
            hideElement(valueEl);
        } else if (valueEl.length) {
            valueEl.text('Not enough data yet.').removeClass('d-none');
            hideElement(ratingEl);
        }
    }

    function formatDateFromPayload(dateObj) {
        if (!dateObj || typeof dateObj !== 'object') {
            return { text: '—', tooltip: '', order: '' };
        }
        if (typeof dateObj.timestamp === 'number') {
            const local = new Date(dateObj.timestamp * 1000);
            const text = `${local.toLocaleDateString(undefined, {
                weekday: 'short',
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            })} ${local.toLocaleTimeString()}`;
            const tooltip = dateObj.formattedDetailed ? `${dateObj.formattedDetailed} UTC` : '';
            return { text, tooltip, order: dateObj.timestamp };
        }
        if (dateObj.formattedBasic) {
            const tooltip = dateObj.formattedDetailed ? `${dateObj.formattedDetailed} UTC` : '';
            return { text: dateObj.formattedBasic, tooltip, order: dateObj.formattedBasic };
        }
        return { text: '—', tooltip: '', order: '' };
    }

    function renderOverview(overview, errorMessage) {
        if (!overview || typeof overview !== 'object') {
            const message = errorMessage || 'Unable to load overview.';
            Object.keys(summarySelectors).forEach((key) => showSummaryError(key, message));
            return;
        }
        const totals = overview.totals || {};
        const participants = overview.participants || {};
        const ratings = overview.ratings || {};

        setSummaryNumber('hosted', Number(totals.hosted));
        setSummaryNumber('uniqueParticipants', Number(participants.unique));
        if (ratings && Object.prototype.hasOwnProperty.call(ratings, 'recentHost')) {
            setSummaryRating('recentHost', Number(ratings.recentHost));
        } else {
            setSummaryMessage('recentHost', 'Not enough data yet.');
        }
        if (ratings && Object.prototype.hasOwnProperty.call(ratings, 'recentQuest')) {
            setSummaryRating('recentQuest', Number(ratings.recentQuest));
        } else {
            setSummaryMessage('recentQuest', 'Not enough data yet.');
        }
    }

    function renderUpcomingList(upcoming, errorMessage) {
        const $spinner = $('#upcomingSpinner');
        const $error = $('#upcomingError');
        const $empty = $('#upcomingEmpty');
        const $list = $('#upcomingList');

        hideElement($spinner);
        hideElement($error);
        hideElement($empty);
        if ($list.length) {
            $list.empty().addClass('d-none');
        }

        if (errorMessage) {
            if ($error.length) {
                $error.text(errorMessage).removeClass('d-none');
            }
            return;
        }

        if (!Array.isArray(upcoming) || upcoming.length === 0) {
            showElement($empty);
            return;
        }

        upcoming.forEach((quest) => {
            const card = $('<div class="card mb-4 feed-card"></div>');
            const row = $('<div class="row g-0 align-items-center"></div>');

            const imageCol = $('<div class="col-12 col-md-4 col-lg-3 text-center p-3"></div>');
            if (quest && quest.icon) {
                imageCol.append($('<img>', {
                    src: quest.icon,
                    class: 'img-fluid rounded',
                    alt: quest.title || 'Quest icon'
                }));
            } else {
                imageCol.append('<div class="text-muted">No image</div>');
            }

            const bodyCol = $('<div class="col p-3 d-flex flex-column gap-2"></div>');
            const title = quest && quest.title ? quest.title : 'Untitled Quest';
            const url = quest && quest.url ? quest.url : '#';
            const titleLink = $('<a></a>', {
                href: url,
                target: '_blank',
                rel: 'noopener'
            }).append($('<h5 class="card-title mb-1"></h5>').text(title));
            bodyCol.append(titleLink);

            if (quest && quest.description) {
                bodyCol.append($('<p class="mb-2"></p>').text(quest.description));
            }

            if (quest && quest.dateTime) {
                const formatted = formatDateFromPayload(quest.dateTime);
                const meta = $('<p class="text-muted small mb-2"></p>');
                const $dateSpan = $('<span class="date"></span>').text(formatted.text);
                if (formatted.tooltip) {
                    $dateSpan.attr('data-bs-toggle', 'tooltip')
                        .attr('data-bs-placement', 'bottom')
                        .attr('data-bs-title', formatted.tooltip);
                }
                meta.append($dateSpan);
                bodyCol.append(meta);
            }

            if (quest && quest.reviewStatus) {
                const reviewStatus = quest.reviewStatus;
                const badgeRow = $('<div class="d-flex flex-wrap gap-2 small text-muted"></div>');
                const parts = [];
                if (reviewStatus.published) { parts.push(`${reviewStatus.published} published`); }
                if (reviewStatus.beingReviewed) { parts.push(`${reviewStatus.beingReviewed} in review`); }
                if (reviewStatus.draft) { parts.push(`${reviewStatus.draft} draft`); }
                if (parts.length) {
                    badgeRow.text(parts.join(' • '));
                    bodyCol.append(badgeRow);
                }
            }

            const actions = $('<div class="d-flex justify-content-end mt-auto"></div>');
            if (quest && quest.questId) {
                const cloneBtn = $('<button type="button" class="btn btn-sm btn-outline-secondary clone-quest-btn"></button>')
                    .attr('data-quest-id', quest.questId)
                    .attr('data-quest-title', title)
                    .html('<i class="fa-regular fa-clone me-1"></i>Clone Quest');
                actions.append(cloneBtn);
            }
            bodyCol.append(actions);

            row.append(imageCol).append(bodyCol);
            card.append(row);
            $list.append(card);
        });

        $list.removeClass('d-none');
        initializeTooltips($list);
    }

    const suggestionDefinitions = [
        { key: 'dormantQuest', headline: 'Revive this beloved quest with a fresh twist' },
        { key: 'fanFavoriteQuest', headline: 'Reward loyal players with a long-awaited sequel' },
        { key: 'recommendedQuest', headline: 'Launch a new quest inspired by your top performer' },
        { key: 'hiddenGemQuest', headline: 'Promote this hidden gem to boost attendance' },
        { key: 'underperformingQuest', headline: 'Refine this quest to improve its performance' }
    ];

    function resolveQuestUrl(quest) {
        if (!quest || typeof quest !== 'object') {
            return '#';
        }
        if (quest.viewUrl) {
            return quest.viewUrl;
        }
        if (quest.publicUrl) {
            return quest.publicUrl;
        }
        if (quest.url) {
            return quest.url;
        }
        if (quest.locator) {
            return `/q/${quest.locator}`;
        }
        if (quest.questLocator) {
            return `/q/${quest.questLocator}`;
        }
        return '#';
    }

    function resolveQuestRunInfo(quest) {
        if (!quest || typeof quest !== 'object') {
            return { text: 'date TBD', tooltip: '', order: '' };
        }
        let payload = null;
        if (quest.lastRun && typeof quest.lastRun === 'object') {
            payload = quest.lastRun;
        } else if (quest.endDate && typeof quest.endDate === 'object') {
            payload = quest.endDate;
        } else if (quest.dateTime && typeof quest.dateTime === 'object') {
            payload = quest.dateTime;
        }
        if (payload) {
            const formatted = formatDateFromPayload(payload);
            if (!formatted.text || formatted.text === '—') {
                formatted.text = 'date TBD';
            }
            return formatted;
        }
        if (typeof quest.endDateFormatted === 'string' && quest.endDateFormatted) {
            return { text: quest.endDateFormatted, tooltip: '', order: '' };
        }
        if (typeof quest.lastRunFormatted === 'string' && quest.lastRunFormatted) {
            return { text: quest.lastRunFormatted, tooltip: '', order: '' };
        }
        return { text: 'date TBD', tooltip: '', order: '' };
    }

    function buildSuggestionCard(definition, quest, renderStarRating) {
        const card = $('<div class="card mb-3 suggestion-card"></div>');
        const $body = $('<div class="card-body"></div>');
        const $header = $('<div class="d-flex align-items-center mb-2"></div>');
        if (quest.icon) {
            $header.append($('<img>', {
                src: quest.icon,
                class: 'rounded me-3',
                css: { width: '60px', height: '60px', objectFit: 'cover' },
                alt: quest.title || 'Quest icon'
            }));
        }
        const $headerText = $('<div></div>');
        const heading = quest.headline || definition.headline;
        if (heading) {
            $headerText.append($('<h5 class="card-title mb-1"></h5>').text(heading));
        }

        const questTitle = quest.title || 'Untitled Quest';
        const questUrl = resolveQuestUrl(quest);
        const runInfo = resolveQuestRunInfo(quest);
        const $runLine = $('<p class="card-text mb-1"></p>');
        const $runLink = $('<a></a>', {
            href: questUrl,
            target: '_blank',
            rel: 'noopener',
            text: questTitle
        });
        const $runSpan = $('<span class="date"></span>').text(runInfo.text || 'date TBD');
        if (runInfo.tooltip) {
            $runSpan.attr('data-bs-toggle', 'tooltip')
                .attr('data-bs-placement', 'bottom')
                .attr('data-bs-title', runInfo.tooltip);
        }
        $runLine.append($runLink).append(' last ran ').append($runSpan);
        $headerText.append($runLine);

        const ratingPieces = [];
        const questRating = Number(quest.avgQuestRating);
        if (Number.isFinite(questRating) && questRating > 0) {
            ratingPieces.push(`Quest Rating: ${renderStarRating(questRating)}<span class="ms-1">${questRating.toFixed(2)}</span>`);
        } else {
            ratingPieces.push('Quest Rating: —');
        }
        const hostRating = Number(quest.avgHostRating);
        if (Number.isFinite(hostRating) && hostRating > 0) {
            ratingPieces.push(`Host Rating: ${renderStarRating(hostRating)}<span class="ms-1">${hostRating.toFixed(2)}</span>`);
        } else {
            ratingPieces.push('Host Rating: —');
        }
        $headerText.append($('<p class="card-text mb-0"></p>').html(ratingPieces.join(' &middot; ')));
        $header.append($headerText);
        $body.append($header);

        if (quest.message) {
            $body.append($('<p class="card-text mb-2"></p>').text(quest.message));
        }

        const questId = quest.id || quest.questId;
        if (questId) {
            const $actions = $('<div class="mt-2 d-flex flex-wrap gap-2"></div>');
            const banner = quest.banner || quest.questBanner || '';
            $actions.append($('<button type="button" class="btn btn-sm btn-outline-primary view-reviews-btn"></button>')
                .attr('data-quest-id', questId)
                .attr('data-quest-title', questTitle)
                .attr('data-quest-banner', banner)
                .html('<i class="fa-regular fa-comments me-1"></i>Reviews'));
            $actions.append($('<button type="button" class="btn btn-sm btn-outline-secondary clone-quest-btn"></button>')
                .attr('data-quest-id', questId)
                .attr('data-quest-title', questTitle)
                .html('<i class="fa-regular fa-clone me-1"></i>Clone Quest'));
            $body.append($actions);
        }

        card.append($body);
        return card;
    }

    function renderSuggestions(suggestions, errorMessage) {
        const $cardsContainer = $('#suggestionCards');
        const $coHostCard = $('#coHostSuggestionCard');
        const $coHostTableBody = $('#coHostSuggestionTable tbody');
        if ($cardsContainer.length) {
            $cardsContainer.empty();
        }
        if ($coHostTableBody.length) {
            $coHostTableBody.empty();
        }
        hideElement($coHostCard);

        if (errorMessage) {
            renderSectionState('suggestions', 'error', errorMessage);
            return;
        }

        if (!suggestions || typeof suggestions !== 'object') {
            renderSectionState('suggestions', 'empty');
            return;
        }

        const renderStarRating = getStarRenderer();
        let cardCount = 0;
        suggestionDefinitions.forEach((definition) => {
            const quest = suggestions[definition.key];
            if (quest) {
                $cardsContainer.append(buildSuggestionCard(definition, quest, renderStarRating));
                cardCount += 1;
            }
        });

        const coHosts = Array.isArray(suggestions.coHostCandidates) ? suggestions.coHostCandidates : [];
        if ($coHostCard.length && coHosts.length > 0) {
            coHosts.forEach((candidate) => {
                const $row = $('<tr></tr>');
                const $playerCell = $('<td></td>');
                const $playerWrap = $('<div class="d-flex align-items-center"></div>');
                if (candidate.avatar) {
                    $playerWrap.append($('<img>', {
                        src: candidate.avatar,
                        class: 'rounded me-2',
                        css: { width: '40px', height: '40px', objectFit: 'cover' },
                        alt: candidate.username || 'Co-host'
                    }));
                }
                const $playerLink = $('<a></a>', {
                    href: candidate.url || '#',
                    target: '_blank',
                    rel: 'noopener',
                    class: 'username',
                    text: candidate.username || 'Player'
                });
                $playerWrap.append($playerLink);
                $playerCell.append($playerWrap);
                $row.append($playerCell);

                const loyalty = Number(candidate.loyalty);
                $row.append($('<td class="align-middle"></td>').text(Number.isFinite(loyalty) ? loyalty.toLocaleString() : '—'));

                const reliability = Number(candidate.reliability);
                const reliabilityText = Number.isFinite(reliability) ? `${Math.round(reliability * 100)}%` : '—';
                $row.append($('<td class="align-middle"></td>').text(reliabilityText));

                const hosted = Number(candidate.questsHosted);
                $row.append($('<td class="align-middle"></td>').text(Number.isFinite(hosted) ? hosted.toLocaleString() : '—'));

                const network = Number(candidate.network);
                $row.append($('<td class="align-middle"></td>').text(Number.isFinite(network) ? network.toLocaleString() : '—'));

                const $lastQuestCell = $('<td class="align-middle"></td>');
                if (typeof candidate.daysSinceLastQuest === 'number') {
                    const days = candidate.daysSinceLastQuest;
                    $lastQuestCell.text(`${days} day${days === 1 ? '' : 's'} ago`);
                } else if (candidate.lastQuest && typeof candidate.lastQuest === 'object') {
                    const lastInfo = formatDateFromPayload(candidate.lastQuest);
                    const $lastSpan = $('<span class="date"></span>').text(lastInfo.text || '—');
                    if (lastInfo.tooltip) {
                        $lastSpan.attr('data-bs-toggle', 'tooltip')
                            .attr('data-bs-placement', 'bottom')
                            .attr('data-bs-title', lastInfo.tooltip);
                    }
                    $lastQuestCell.append($lastSpan);
                } else {
                    $lastQuestCell.text('—');
                }
                $row.append($lastQuestCell);

                $coHostTableBody.append($row);
            });
            showElement($coHostCard);
        }

        if (cardCount === 0 && coHosts.length === 0) {
            renderSectionState('suggestions', 'empty');
        } else {
            renderSectionState('suggestions', 'ready');
        }

        initializeTooltips($cardsContainer);
        initializeTooltips($coHostCard);
    }

    function renderQuestLines(questLines, errorMessage) {
        const $tbody = $('#questLinesTableBody');
        const $alert = $('#questLinesSchedulingAlert');
        if ($tbody.length) {
            $tbody.empty();
        }
        if ($alert.length) {
            $alert.addClass('d-none').text('');
        }

        if (errorMessage) {
            renderSectionState('questLines', 'error', errorMessage);
            return;
        }

        if (!questLines || typeof questLines !== 'object') {
            renderSectionState('questLines', 'empty');
            return;
        }

        const statusCounts = questLines.statusCounts || {};
        const setCount = (key, value) => {
            const $el = $(`[data-quest-line-count="${key}"]`);
            if (!$el.length) {
                return;
            }
            const numeric = Number(value);
            if (Number.isFinite(numeric)) {
                $el.text(numeric.toLocaleString());
            } else {
                $el.text('—');
            }
        };
        ['total', 'withUpcoming', 'withoutQuests', 'published', 'needingScheduling', 'inReview', 'draft'].forEach((key) => {
            setCount(key, statusCounts[key]);
        });

        if (questLines.error) {
            renderSectionState('questLines', 'error', questLines.error);
            return;
        }

        const lines = Array.isArray(questLines.lines) ? questLines.lines : [];
        if (!lines.length) {
            renderSectionState('questLines', 'empty');
            return;
        }

        const renderStarRating = getStarRenderer();
        lines.forEach((line) => {
            const reviewStatus = line.reviewStatus || {};
            const counts = line.counts || {};
            const questCount = Number(counts.quests) || 0;
            const futureCount = Number(counts.future) || 0;
            const pastCount = Number(counts.past) || 0;
            const isPublished = !!reviewStatus.published;
            const hasUpcoming = futureCount > 0;
            const needsScheduling = isPublished && questCount > 0 && futureCount === 0;
            const noQuestsYet = questCount === 0;
            const statusLabel = reviewStatus.published ? 'Published' : (reviewStatus.beingReviewed ? 'In Review' : 'Draft');
            const statusClass = reviewStatus.published ? 'bg-success' : (reviewStatus.beingReviewed ? 'bg-warning text-dark' : 'bg-secondary');

            const $row = $('<tr></tr>');

            const $titleCell = $('<td></td>');
            const $titleWrap = $('<div class="d-flex align-items-center"></div>');
            if (line.icon) {
                $titleWrap.append($('<img>', {
                    src: line.icon,
                    class: 'rounded me-2',
                    css: { width: '48px', height: '48px', objectFit: 'cover' },
                    alt: line.title || 'Quest line icon'
                }));
            }
            const $titleInfo = $('<div></div>');
            $titleInfo.append($('<div class="fw-semibold"></div>').text(line.title || 'Quest Line'));
            const $badgeRow = $('<div class="small"></div>');
            $badgeRow.append(`<span class="badge ${statusClass} me-1">${statusLabel}</span>`);
            if (hasUpcoming) {
                $badgeRow.append('<span class="badge bg-info text-dark me-1">Upcoming quests</span>');
            }
            if (needsScheduling) {
                $badgeRow.append('<span class="badge bg-warning text-dark me-1">Needs scheduling</span>');
            }
            if (noQuestsYet) {
                $badgeRow.append('<span class="badge bg-secondary me-1">No quests yet</span>');
            }
            $titleInfo.append($badgeRow);
            $titleWrap.append($titleInfo);
            $titleCell.append($titleWrap);
            $row.append($titleCell);

            const $questCounts = $('<td></td>');
            $questCounts.append($('<div class="fw-semibold"></div>').text(questCount.toLocaleString()));
            $questCounts.append($('<div class="small text-muted"></div>').text(`${futureCount.toLocaleString()} upcoming · ${pastCount.toLocaleString()} past`));
            const publishedCount = Number(counts.published) || 0;
            const inReviewCount = Number(counts.inReview) || 0;
            const draftCount = Number(counts.draft) || 0;
            $questCounts.append($('<div class="small text-muted"></div>').text(`Published: ${publishedCount.toLocaleString()} · Review: ${inReviewCount.toLocaleString()} · Draft: ${draftCount.toLocaleString()}`));
            $row.append($questCounts);

            const $scheduleCell = $('<td></td>');
            const $scheduleWrapper = $('<div class="small"></div>');
            const $nextLine = $('<div><strong>Next:</strong> </div>');
            if (futureCount > 0) {
                if (line.nextRun) {
                    const nextInfo = formatDateFromPayload(line.nextRun);
                    const $nextSpan = $('<span class="date"></span>').text(nextInfo.text);
                    if (nextInfo.tooltip) {
                        $nextSpan.attr('data-bs-toggle', 'tooltip')
                            .attr('data-bs-placement', 'bottom')
                            .attr('data-bs-title', nextInfo.tooltip);
                    }
                    $nextLine.append($nextSpan);
                } else {
                    $nextLine.append('<span class="text-muted">Date TBD</span>');
                }
            } else {
                $nextLine.append('<span class="text-muted">No quest scheduled</span>');
            }
            $scheduleWrapper.append($nextLine);

            const $lastLine = $('<div><strong>Last:</strong> </div>');
            if (line.lastRun) {
                const lastInfo = formatDateFromPayload(line.lastRun);
                const $lastSpan = $('<span class="date"></span>').text(lastInfo.text);
                if (lastInfo.tooltip) {
                    $lastSpan.attr('data-bs-toggle', 'tooltip')
                        .attr('data-bs-placement', 'bottom')
                        .attr('data-bs-title', lastInfo.tooltip);
                }
                $lastLine.append($lastSpan);
            } else if (pastCount > 0) {
                $lastLine.append('<span class="text-muted">Date TBD</span>');
            } else {
                $lastLine.append('<span class="text-muted">Never</span>');
            }
            $scheduleWrapper.append($lastLine);
            $scheduleCell.append($scheduleWrapper);
            $row.append($scheduleCell);

            const questRating = typeof line.avgQuestRating === 'number' ? line.avgQuestRating : null;
            const hostRating = typeof line.avgHostRating === 'number' ? line.avgHostRating : null;
            const $ratingsCell = $('<td></td>');
            if ((questRating && questRating > 0) || (hostRating && hostRating > 0)) {
                const $questRatingRow = $('<div class="d-flex align-items-center"></div>');
                $questRatingRow.append('<strong>Quest:</strong>');
                if (questRating && questRating > 0) {
                    $questRatingRow.append(`<span class="ms-2">${renderStarRating(questRating)}</span>`);
                } else {
                    $questRatingRow.append('<span class="ms-2 text-muted">—</span>');
                }
                const $hostRatingRow = $('<div class="d-flex align-items-center"></div>');
                $hostRatingRow.append('<strong>Host:</strong>');
                if (hostRating && hostRating > 0) {
                    $hostRatingRow.append(`<span class="ms-2">${renderStarRating(hostRating)}</span>`);
                } else {
                    $hostRatingRow.append('<span class="ms-2 text-muted">—</span>');
                }
                $ratingsCell.append($questRatingRow).append($hostRatingRow);
            } else {
                $ratingsCell.append('<span class="text-muted">No reviews yet</span>');
            }
            $row.append($ratingsCell);

            const participantsTotal = Number(line.participantsTotal ?? (line.metrics && line.metrics.participantsTotal));
            const registeredTotal = Number(line.registeredTotal ?? (line.metrics && line.metrics.registeredTotal));
            const attendanceRate = typeof line.attendanceRate === 'number' ? line.attendanceRate : null;
            const participantsText = Number.isFinite(participantsTotal) ? participantsTotal.toLocaleString() : '—';
            const registrationsText = Number.isFinite(registeredTotal) ? registeredTotal.toLocaleString() : '—';
            const attendanceText = attendanceRate !== null && !Number.isNaN(attendanceRate)
                ? `${Math.round(attendanceRate * 100)}%`
                : '—';
            const $engagementCell = $('<td></td>');
            $engagementCell.append($('<div class="fw-semibold"></div>').text(participantsText));
            $engagementCell.append($('<div class="small text-muted"></div>').text(`Registrations: ${registrationsText}`));
            $engagementCell.append($('<div class="small text-muted"></div>').text(`Attendance: ${attendanceText}`));
            $row.append($engagementCell);

            const viewUrl = line.viewUrl || line.publicUrl || line.url || '';
            const $actionCell = $('<td class="text-end"></td>');
            if (isPublished && viewUrl) {
                $actionCell.append($('<a class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">View</a>').attr('href', viewUrl));
            } else {
                $actionCell.append('<button type="button" class="btn btn-sm btn-outline-secondary" disabled>View</button>');
            }
            $row.append($actionCell);

            $tbody.append($row);
        });

        if ($alert.length) {
            const count = Number(statusCounts.needingScheduling);
            if (Number.isFinite(count) && count > 0) {
                const needsVerb = count === 1 ? 'needs' : 'need';
                const lineLabel = count === 1 ? 'quest line' : 'quest lines';
                $alert.html(`<i class="fa-solid fa-bell me-2"></i>${count} published ${lineLabel} ${needsVerb} a scheduled follow-up. Plan the next quest to keep players engaged.`);
                $alert.removeClass('d-none');
            }
        }

        initializeTooltips($tbody);
        renderSectionState('questLines', 'ready');
    }

    function renderTopSections(topData, errorMessage) {
        const $questsWrapper = $('#topQuestsTableWrapper');
        const $questsEmpty = $('#topQuestsEmpty');
        const $questsBody = $('#topQuestsBody');
        const $participantsControls = $('#topParticipantsControls');
        const $participantsWrapper = $('#topParticipantsTableWrapper');
        const $participantsEmpty = $('#topParticipantsEmpty');
        const $participantsBody = $('#topParticipantsBody');
        const $coHostsWrapper = $('#topCoHostsTableWrapper');
        const $coHostsEmpty = $('#topCoHostsEmpty');
        const $coHostsBody = $('#topCoHostsBody');

        if ($questsBody.length) { $questsBody.empty(); }
        if ($participantsBody.length) { $participantsBody.empty(); }
        if ($coHostsBody.length) { $coHostsBody.empty(); }

        hideElement($questsWrapper);
        hideElement($participantsWrapper);
        hideElement($participantsControls);
        hideElement($coHostsWrapper);
        hideElement($questsEmpty);
        hideElement($participantsEmpty);
        hideElement($coHostsEmpty);

        if (errorMessage) {
            renderSectionState('top', 'error', errorMessage);
            return;
        }

        const renderStarRating = getStarRenderer();
        const quests = Array.isArray(topData && topData.quests) ? topData.quests : [];
        if (quests.length > 0 && $questsBody.length) {
            quests.forEach((quest) => {
                const $row = $('<tr></tr>');
                const $questCell = $('<td></td>');
                const $questWrap = $('<div class="d-flex align-items-center"></div>');
                if (quest.icon) {
                    $questWrap.append($('<img>', {
                        src: quest.icon,
                        class: 'rounded me-2',
                        css: { width: '40px', height: '40px', objectFit: 'cover' },
                        alt: quest.title || 'Quest icon'
                    }));
                }
                const questTitle = quest.title || 'Quest';
                const questUrl = resolveQuestUrl(quest);
                $questWrap.append($('<a></a>', {
                    href: questUrl,
                    target: '_blank',
                    rel: 'noopener',
                    text: questTitle
                }));
                $questCell.append($questWrap);
                $row.append($questCell);

                const participants = Number(quest.participants);
                $row.append($('<td class="align-middle"></td>').text(Number.isFinite(participants) ? participants.toLocaleString() : '—'));

                const questRating = Number(quest.avgQuestRating);
                const $questRatingCell = $('<td class="align-middle"></td>');
                if (Number.isFinite(questRating) && questRating > 0) {
                    $questRatingCell.html(`${renderStarRating(questRating)}<span class="ms-1">${questRating.toFixed(2)}</span>`);
                } else {
                    $questRatingCell.text('—');
                }
                $row.append($questRatingCell);

                const hostRating = Number(quest.avgHostRating);
                const $hostRatingCell = $('<td class="align-middle"></td>');
                if (Number.isFinite(hostRating) && hostRating > 0) {
                    $hostRatingCell.html(`${renderStarRating(hostRating)}<span class="ms-1">${hostRating.toFixed(2)}</span>`);
                } else {
                    $hostRatingCell.text('—');
                }
                $row.append($hostRatingCell);

                const questId = quest.id || quest.questId || '';
                const banner = quest.banner || quest.questBanner || '';
                $row.append($('<td class="align-middle"></td>').append($('<button type="button" class="btn btn-sm btn-outline-primary view-reviews-btn"></button>')
                    .attr('data-quest-id', questId)
                    .attr('data-quest-title', questTitle)
                    .attr('data-quest-banner', banner)
                    .html('<i class="fa-regular fa-comments me-1"></i>Reviews')));

                $row.append($('<td class="align-middle"></td>').append($('<button type="button" class="btn btn-sm btn-outline-secondary clone-quest-btn"></button>')
                    .attr('data-quest-id', questId)
                    .attr('data-quest-title', questTitle)
                    .html('<i class="fa-regular fa-clone me-1"></i>Clone')));

                $questsBody.append($row);
            });
            showElement($questsWrapper);
        } else {
            showElement($questsEmpty);
        }

        const participants = Array.isArray(topData && topData.participants) ? topData.participants : [];
        if (participants.length > 0 && $participantsBody.length) {
            participants.forEach((participant) => {
                const loyalty = Number(participant.loyalty) || 0;
                const reliability = Number(participant.reliability) || 0;
                const hosted = Number(participant.questsHosted) || 0;
                const network = Number(participant.network) || 0;
                const $row = $('<tr></tr>')
                    .attr('data-loyalty', loyalty)
                    .attr('data-reliability', reliability)
                    .attr('data-questshosted', hosted)
                    .attr('data-network', network);

                const $playerCell = $('<td></td>');
                const $playerWrap = $('<div class="d-flex align-items-center"></div>');
                if (participant.avatar) {
                    $playerWrap.append($('<img>', {
                        src: participant.avatar,
                        class: 'rounded me-2',
                        css: { width: '40px', height: '40px', objectFit: 'cover' },
                        alt: participant.username || 'Participant avatar'
                    }));
                }
                $playerWrap.append($('<a></a>', {
                    href: participant.url || '#',
                    target: '_blank',
                    rel: 'noopener',
                    class: 'username',
                    text: participant.username || 'Participant'
                }));
                $playerCell.append($playerWrap);
                $row.append($playerCell);

                $row.append($('<td class="align-middle"></td>').text(loyalty.toLocaleString()));
                $row.append($('<td class="align-middle"></td>').text(`${Math.round(reliability * 100)}%`));
                $row.append($('<td class="align-middle"></td>').text(hosted.toLocaleString()));
                $row.append($('<td class="align-middle"></td>').text(network.toLocaleString()));

                const avgQuestRating = Number(participant.avgQuestRating);
                const $avgQuestCell = $('<td class="align-middle"></td>');
                if (Number.isFinite(avgQuestRating) && avgQuestRating > 0) {
                    $avgQuestCell.html(`${renderStarRating(avgQuestRating)}<span class="ms-1">${avgQuestRating.toFixed(2)}</span>`);
                } else {
                    $avgQuestCell.text('—');
                }
                $row.append($avgQuestCell);

                const avgHostRating = Number(participant.avgHostRating);
                const $avgHostCell = $('<td class="align-middle"></td>');
                if (Number.isFinite(avgHostRating) && avgHostRating > 0) {
                    $avgHostCell.html(`${renderStarRating(avgHostRating)}<span class="ms-1">${avgHostRating.toFixed(2)}</span>`);
                } else {
                    $avgHostCell.text('—');
                }
                $row.append($avgHostCell);

                $participantsBody.append($row);
            });
            showElement($participantsWrapper);
            showElement($participantsControls);
        } else {
            showElement($participantsEmpty);
        }

        const coHosts = Array.isArray(topData && topData.coHosts) ? topData.coHosts : [];
        if (coHosts.length > 0 && $coHostsBody.length) {
            coHosts.forEach((host) => {
                const $row = $('<tr></tr>');
                const $hostCell = $('<td></td>');
                const $hostWrap = $('<div class="d-flex align-items-center"></div>');
                if (host.avatar) {
                    $hostWrap.append($('<img>', {
                        src: host.avatar,
                        class: 'rounded me-2',
                        css: { width: '40px', height: '40px', objectFit: 'cover' },
                        alt: host.username || 'Co-host avatar'
                    }));
                }
                $hostWrap.append($('<a></a>', {
                    href: host.url || '#',
                    target: '_blank',
                    rel: 'noopener',
                    class: 'username',
                    text: host.username || 'Co-host'
                }));
                $hostCell.append($hostWrap);
                $row.append($hostCell);

                const score = Number(host.score);
                $row.append($('<td class="align-middle fw-semibold"></td>').text(Number.isFinite(score) ? score.toFixed(1) : '—'));

                const questCount = Number(host.questCount);
                $row.append($('<td class="align-middle"></td>').text(Number.isFinite(questCount) ? questCount.toLocaleString() : '—'));

                const avgParticipants = Number(host.avgParticipants);
                $row.append($('<td class="align-middle"></td>').text(Number.isFinite(avgParticipants) ? avgParticipants.toFixed(1) : '—'));

                const uniqueParticipants = Number(host.uniqueParticipants);
                $row.append($('<td class="align-middle"></td>').text(Number.isFinite(uniqueParticipants) ? uniqueParticipants.toLocaleString() : '—'));

                const avgHostRating = Number(host.avgHostRating);
                const $avgHostRatingCell = $('<td class="align-middle"></td>');
                if (Number.isFinite(avgHostRating) && avgHostRating > 0) {
                    $avgHostRatingCell.html(`${renderStarRating(avgHostRating)}<span class="ms-1">${avgHostRating.toFixed(2)}</span>`);
                } else {
                    $avgHostRatingCell.text('—');
                }
                $row.append($avgHostRatingCell);

                const avgQuestRating = Number(host.avgQuestRating);
                const $avgQuestRatingCell = $('<td class="align-middle"></td>');
                if (Number.isFinite(avgQuestRating) && avgQuestRating > 0) {
                    $avgQuestRatingCell.html(`${renderStarRating(avgQuestRating)}<span class="ms-1">${avgQuestRating.toFixed(2)}</span>`);
                } else {
                    $avgQuestRatingCell.text('—');
                }
                $row.append($avgQuestRatingCell);

                $coHostsBody.append($row);
            });
            showElement($coHostsWrapper);
        } else {
            showElement($coHostsEmpty);
        }

        renderSectionState('top', 'ready');
        initializeTooltips($('#topContent'));
        document.dispatchEvent(new CustomEvent('questDashboard:participantsRendered'));
    }

    function initializeTooltips($container) {
        if (!window.bootstrap || typeof window.bootstrap.Tooltip !== 'function') {
            return;
        }
        $container.find('[data-bs-toggle="tooltip"]').each(function () {
            const instance = window.bootstrap.Tooltip.getInstance(this);
            if (instance) {
                instance.dispose();
            }
            new window.bootstrap.Tooltip(this);
        });
    }

    function renderQuestReviewsTable(rows, errorMessage) {
        const $spinner = $('#questReviewsSpinner');
        const $error = $('#questReviewsError');
        const $empty = $('#questReviewsEmpty');
        const $wrapper = $('#questReviewsTableWrapper');
        const $table = $('#datatable-reviews');

        hideElement($spinner);
        hideElement($error);
        hideElement($empty);
        if ($wrapper.length) {
            $wrapper.addClass('d-none');
        }

        if ($.fn.DataTable && $.fn.DataTable.isDataTable($table)) {
            $table.DataTable().destroy();
        }

        const $tbody = $table.find('tbody');
        if ($tbody.length) {
            $tbody.empty();
        }

        if (errorMessage) {
            if ($error.length) {
                $error.text(errorMessage).removeClass('d-none');
            }
            return;
        }

        if (!Array.isArray(rows) || rows.length === 0) {
            showElement($empty);
            return;
        }

        const renderStarRating = getStarRenderer();
        rows.forEach((summary) => {
            const $row = $('<tr></tr>');

            const $questCell = $('<td></td>');
            const questWrapper = $('<div class="d-flex align-items-center"></div>');
            if (summary.questIcon) {
                questWrapper.append($('<img>', {
                    src: summary.questIcon,
                    class: 'rounded me-2',
                    css: { width: '40px', height: '40px' },
                    alt: summary.questTitle || 'Quest'
                }));
            }
            const questLink = $('<a></a>', {
                href: summary.questLocator ? `/q/${summary.questLocator}` : '#',
                target: '_blank',
                text: summary.questTitle || 'Quest'
            });
            questWrapper.append(questLink);
            $questCell.append(questWrapper);
            $row.append($questCell);

            const $dateCell = $('<td></td>');
            const formattedDate = formatDateFromPayload(summary.questEndDate);
            if (formattedDate.order !== '') {
                $dateCell.attr('data-order', formattedDate.order);
            }
            const $dateSpan = $('<span class="date"></span>').text(formattedDate.text);
            if (formattedDate.tooltip) {
                $dateSpan.attr('data-bs-toggle', 'tooltip')
                    .attr('data-bs-placement', 'bottom')
                    .attr('data-bs-title', formattedDate.tooltip);
            }
            $dateCell.append($dateSpan);
            $row.append($dateCell);

            const hostRating = Number(summary.avgHostRating);
            const $hostCell = $('<td class="align-middle"></td>').attr('data-order', Number.isFinite(hostRating) ? hostRating : 0);
            if (Number.isFinite(hostRating) && hostRating > 0) {
                $hostCell.html(`${renderStarRating(hostRating)}<span class="ms-1">${hostRating.toFixed(2)}</span>`);
            } else {
                $hostCell.text('—');
            }
            $row.append($hostCell);

            const questRating = Number(summary.avgQuestRating);
            const $questRatingCell = $('<td class="align-middle"></td>').attr('data-order', Number.isFinite(questRating) ? questRating : 0);
            if (Number.isFinite(questRating) && questRating > 0) {
                $questRatingCell.html(`${renderStarRating(questRating)}<span class="ms-1">${questRating.toFixed(2)}</span>`);
            } else {
                $questRatingCell.text('—');
            }
            $row.append($questRatingCell);

            const reviewsBtn = $('<button type="button" class="btn btn-sm view-reviews-btn"></button>')
                .addClass(summary.hasComments ? 'btn-primary' : 'btn-outline-secondary')
                .attr('data-quest-id', summary.questId || '')
                .attr('data-quest-title', summary.questTitle || '')
                .attr('data-quest-banner', summary.questBanner || '')
                .html(`<i class="${summary.hasComments ? 'fa-solid' : 'fa-regular'} fa-comments me-1"></i>View`);
            $row.append($('<td class="align-middle"></td>').append(reviewsBtn));

            const cloneBtn = $('<button type="button" class="btn btn-sm btn-outline-secondary clone-quest-btn"></button>')
                .attr('data-quest-id', summary.questId || '')
                .attr('data-quest-title', summary.questTitle || '')
                .html('<i class="fa-regular fa-clone me-1"></i>Clone');
            $row.append($('<td class="align-middle"></td>').append(cloneBtn));

            $tbody.append($row);
        });

        initializeTooltips($tbody);

        if ($wrapper.length) {
            $wrapper.removeClass('d-none');
        }

        if ($.fn.DataTable) {
            $table.DataTable({
                pageLength: 10,
                lengthChange: true,
                columnDefs: [{ targets: [4, 5], orderable: false }],
                order: [[1, 'desc']]
            });
        }
    }

    const charts = {
        review: null,
        ratingOverTime: null,
        participant: null
    };

    function destroyChart(instanceKey) {
        if (charts[instanceKey]) {
            charts[instanceKey].destroy();
            charts[instanceKey] = null;
        }
    }

    function renderReviewChart(chartData, errorMessage) {
        const $spinner = $('#reviewChartSpinner');
        const $error = $('#reviewChartError');
        const $empty = $('#reviewChartEmpty');
        const $canvas = $('#reviewChart');

        hideElement($spinner);
        hideElement($error);
        hideElement($empty);
        hideElement($canvas);

        if (errorMessage) {
            $error.text(errorMessage).removeClass('d-none');
            return;
        }

        const labels = chartData && Array.isArray(chartData.questTitles) ? chartData.questTitles : [];
        const hostRatings = chartData && Array.isArray(chartData.avgHostRatings) ? chartData.avgHostRatings : [];
        const questRatings = chartData && Array.isArray(chartData.avgQuestRatings) ? chartData.avgQuestRatings : [];

        if (!labels.length || typeof Chart === 'undefined') {
            if (!labels.length) {
                $empty.removeClass('d-none');
            } else {
                $error.text('Chart library unavailable.').removeClass('d-none');
            }
            return;
        }

        destroyChart('review');
        const ctx = $canvas[0].getContext('2d');
        charts.review = new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Avg Host Rating',
                        data: hostRatings,
                        backgroundColor: 'rgba(75, 192, 192, 0.6)'
                    },
                    {
                        label: 'Avg Quest Rating',
                        data: questRatings,
                        backgroundColor: 'rgba(153, 102, 255, 0.6)'
                    }
                ]
            },
            options: {
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            max: 5
                        }
                    }]
                }
            }
        });
        showElement($canvas);
    }

    function renderRatingOverTimeChart(chartData, errorMessage) {
        const $spinner = $('#ratingOverTimeChartSpinner');
        const $error = $('#ratingOverTimeChartError');
        const $empty = $('#ratingOverTimeChartEmpty');
        const $canvas = $('#ratingOverTimeChart');

        hideElement($spinner);
        hideElement($error);
        hideElement($empty);
        hideElement($canvas);

        if (errorMessage) {
            $error.text(errorMessage).removeClass('d-none');
            return;
        }

        const labels = chartData && Array.isArray(chartData.ratingDates) ? chartData.ratingDates : [];
        const averages = chartData && Array.isArray(chartData.avgRatingsOverTime) ? chartData.avgRatingsOverTime : [];

        if (!labels.length || typeof Chart === 'undefined') {
            if (!labels.length) {
                $empty.removeClass('d-none');
            } else {
                $error.text('Chart library unavailable.').removeClass('d-none');
            }
            return;
        }

        destroyChart('ratingOverTime');
        const ctx = $canvas[0].getContext('2d');
        charts.ratingOverTime = new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Avg Quest Rating',
                    data: averages,
                    fill: false,
                    borderColor: 'rgba(255, 206, 86, 1)',
                    tension: 0.1
                }]
            },
            options: {
                scales: {
                    xAxes: [{
                        type: 'time',
                        time: {
                            parser: 'YYYY-MM-DD',
                            unit: 'day',
                            displayFormats: {
                                day: 'MMM D'
                            }
                        }
                    }],
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            max: 5
                        }
                    }]
                }
            }
        });
        showElement($canvas);
    }

    function renderParticipantChart(chartData, errorMessage) {
        const $spinner = $('#participantPerQuestChartSpinner');
        const $error = $('#participantPerQuestChartError');
        const $empty = $('#participantPerQuestChartEmpty');
        const $canvas = $('#participantPerQuestChart');

        hideElement($spinner);
        hideElement($error);
        hideElement($empty);
        hideElement($canvas);

        if (errorMessage) {
            $error.text(errorMessage).removeClass('d-none');
            return;
        }

        const labels = chartData && Array.isArray(chartData.participantQuestTitles) ? chartData.participantQuestTitles : [];
        const counts = chartData && Array.isArray(chartData.participantCounts) ? chartData.participantCounts : [];

        if (!labels.length || typeof Chart === 'undefined') {
            if (!labels.length) {
                $empty.removeClass('d-none');
            } else {
                $error.text('Chart library unavailable.').removeClass('d-none');
            }
            return;
        }

        destroyChart('participant');
        const ctx = $canvas[0].getContext('2d');
        charts.participant = new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Participants',
                    data: counts,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)'
                }]
            },
            options: {
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            precision: 0
                        }
                    }]
                }
            }
        });
        showElement($canvas);
    }

    function renderCharts(chartData, errorMessage) {
        renderReviewChart(chartData, errorMessage);
        renderRatingOverTimeChart(chartData, errorMessage);
        renderParticipantChart(chartData, errorMessage);
    }

    function handleError(message) {
        const errorMessage = message || 'Unable to load dashboard data. Please try again later.';
        renderOverview(null, errorMessage);
        renderUpcomingList(null, errorMessage);
        renderQuestReviewsTable(null, errorMessage);
        renderCharts(null, errorMessage);
        renderSuggestions(null, errorMessage);
        renderQuestLines(null, errorMessage);
        renderTopSections(null, errorMessage);
    }

    function loadDashboard(sessionToken) {
        if (!sessionToken) {
            handleError('Session expired. Please log in again.');
            return;
        }

        renderSectionState('suggestions', 'loading');
        renderSectionState('questLines', 'loading');
        renderSectionState('top', 'loading');

        $.ajax({
            url: '/api/v1/quest/dashboard.php',
            method: 'POST',
            dataType: 'json',
            data: { sessionToken }
        }).done((resp) => {
            if (resp && resp.success && resp.data) {
                renderOverview(resp.data.overview || null);
                renderUpcomingList(resp.data.upcoming || []);
                const reviewPayload = resp.data.reviews || {};
                renderQuestReviewsTable(reviewPayload.summaries || []);
                renderCharts(reviewPayload.chart || {});
                renderSuggestions(resp.data.suggestions || {});
                renderQuestLines(resp.data.questLines || {});
                renderTopSections(resp.data.top || {});
            } else {
                handleError(resp && resp.message ? resp.message : null);
            }
        }).fail(() => {
            handleError('Unable to load dashboard data. Please check your connection and try again.');
        });
    }

    $(function () {
        ensureStarHelper();
        const config = window.questDashboardConfig || {};
        loadDashboard(config.sessionToken || '');
    });
})(jQuery);
