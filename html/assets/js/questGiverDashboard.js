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
                const meta = $('<p class="text-muted small mb-2"></p>').text(formatted.text);
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
    }

    function loadDashboard(sessionToken) {
        if (!sessionToken) {
            handleError('Session expired. Please log in again.');
            return;
        }

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
