<?php
declare(strict_types=1);

use Kickback\Common\Version;

?>
<script>

var selectAccountModalCallerId = -1;

function OpenSelectAccountModal(prevModal = null, clickableFunction = null) {

    var formId = "modal-";
    if (prevModal != null)
    {
        $("#"+prevModal).modal("hide");
        selectAccountModalCallerId = prevModal;
    }

    ReopenSelectAccountModal(formId, clickableFunction);
}

function ReopenSelectAccountModal(formId, clickableFunction = null) {
    
    $("#selectAccountModal").modal("show");

    SearchForAccount(formId, 1, clickableFunction);
}

function SearchForAccount(formId, pageIndex = 1, clickableFunction = null, filters = {})
{
    var usersPerPage = $('#'+formId+'selectAccountSearchResults').data('users-per-page');

    ClearSearchAccountResults(formId); 
    const data = {
        searchTerm: $("#"+formId+"selectAccountSearchTerm").val(),
        sessionToken: "<?php echo $_SESSION["sessionToken"] ?? ""; ?>",
        page: pageIndex,
        itemsPerPage: usersPerPage,
        filters: filters
    };

    const params = new URLSearchParams();

    for (const [key, value] of Object.entries(data)) {
        if (key === 'filters') {
            for (const [filterKey, filterValue] of Object.entries(value)) {
                params.append(`filters[${filterKey}]`, filterValue);
            }
        } else {
            params.append(key, value);
        }
    }

    fetch('<?= Version::formatUrl("/api/v1/account/search.php?json"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: params
    }).then(response=>response.text()).then(data=>LoadSearchAccountResults(formId, data, usersPerPage, pageIndex, clickableFunction));
}


function OnSelectAccountChangeSearchParams(formId, pageIndex, clickableFunction = null, filters = {})
{
    console.log("search for Account!");
    SearchForAccount(formId, pageIndex, clickableFunction, filters);
}

function LoadSearchAccountResults(formId, data, usersPerPage, pageIndex, clickableFunction = null)
{
    var response = JSON.parse(data);
    var results = response.data.accountItems;
    ClearSearchAccountResults(formId); 
    for (let index = 0; index < results.length; index++) {
        const Account = results[index];
        console.log(Account);
        AddSearchAccountResult(formId, Account, clickableFunction);
    }

    generatePaginationSelectAccount(formId, response.data.total, usersPerPage, pageIndex);
}

function ClearSearchAccountResults(formId)
{
    $("#"+formId+"selectAccountSearchResults").html("");
}


function AddSearchAccountResult(formId, account, clickableFunction = null) {
    // Card structure
    var cardHtml = generatePlayerCardHTML(account, clickableFunction);

    // Append the card to the results container
    //$("#"+formId+"selectAccountSearchResults").append(cardHtml);
    
    // Append the card to the results container
    var container = $("#"+formId+"selectAccountSearchResults");
    container.append(cardHtml);

    // Initialize tooltips for the newly appended card
    container.find('.player-card').last().find('[data-bs-toggle="tooltip"]').tooltip();


    // Optional: Event listener for the select button
    $('.select-account-btn').last().on('click', function() {
        var accountId = $(this).data('account-id');
        // Handle the account selection here
    });
}



function generatePaginationSelectAccount(formId, totalItems, itemsPerPage, currentPage) {
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    let paginationHtml = '<div class="btn-group me-2" role="group" aria-label="Pagination group">';
    
    for (let i = 1; i <= totalPages; i++) {
        if (i === currentPage) {
            paginationHtml += `<button type="button" class="btn bg-ranked-1 active">${i}</button>`;
        } else {
            paginationHtml += `<button type="button" class="btn btn-primary" onclick="onPaginationClickSelectAccount('${formId}',${i})">${i}</button>`;
        }
    }
    
    paginationHtml += '</div>';
    
    $("#"+formId+"selectUserPagination").html(paginationHtml);
}

function onPaginationClickSelectAccount(formId, pageNumber) {
    SearchForAccount(formId, pageNumber);
}


function generatePlayerCardHTML(playerCardAccount, clickableFunction = null) {

let badges = playerCardAccount.badge_display;
let playerRanks = playerCardAccount.game_ranks;

let badgeCode = '';
for (let i = 0; i < badges.length && i < 5; i++) {
    let badge = badges[i];
    badgeCode += `<span tabindex="0" data-bs-toggle="popover" data-bs-custom-class="custom-popover" data-bs-trigger="focus" data-bs-placement="top" data-bs-title="${badge.item.name}" data-bs-content="${badge.item.description}"><img src="${badge.item.iconSmall.url}" class="loot-badge"></span>`;
}

let rankCode = '';
let isRanked1 = playerCardAccount.isGoldCardHolder;
let ranks = 0;
for (let rank of playerRanks) {
    ranks++;
    if (rank.rank === null) {
        rankCode += `<div>${rank.name} <span class="badge unranked float-end" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Unranked: ${(rank.minimum_ranked_matches_required - rank.ranked_matches)} matches remaining">${rank.ranked_matches} / ${rank.minimum_ranked_matches_required}</span></div>`;
    } else {
        rankCode += `<div>${rank.name} <span class="badge ranked${rank.rank == 1 ? "-1" : ""} float-end" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Ranked #${rank.rank} Kingdom Wide">#${rank.rank}</span></div>`;
    }
}
for (var i = ranks; i < 5; i ++ )
{
    rankCode += `<div><span class="badge float-start unranked" style="
    width: 160px;
    background-color: #00000029;
    color: transparent;
    margin-bottom:1px;
"> / </span><span class="badge unranked float-end" style="
    background: #00000073;
    color: transparent;
">X / X</span></div>`;

}
const clickableLayer = clickableFunction ? `<div class="clickable-layer" onclick="${clickableFunction}(${playerCardAccount.Id})"></div>` : '';

let playerCardHTML = `<div class="card player-card${isRanked1 ? " ranked-1" : ""}">
${clickableLayer}
        <div class="ribbons-container">
            ${true ? "<div class='ribbon red'></div>" : ""}
            ${playerCardAccount["isMerchant"] == 1 ? "<div class='ribbon blue'></div>" : ""}
            ${false ? "<div class='ribbon green'></div>" : ""}
            ${false ? "<div class='ribbon yellow'></div>" : ""}
            ${false ? "<div class='ribbon purple'></div>" : ""}
        </div>

        <div class="card-header${isRanked1 ? " ranked-1" : ""}">
            <h5 class="player-card-name">
                <a href="/u/${playerCardAccount.username}" class="link-dark link-underline-opacity-0 ${isRanked1 ? "link-ranked-1" : ""}">
                    ${playerCardAccount.username}
                </a>
                <span> Level ${playerCardAccount.level}
                    <div class="progress" role="progressbar" aria-label="Animated striped example" aria-valuenow="${playerCardAccount.expCurrent}" aria-valuemin="0" aria-valuemax="${playerCardAccount.expGoal}" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="${playerCardAccount.expCurrent * 10}/${playerCardAccount.expGoal * 10} EXP">
                        <div class="progress-bar ${isRanked1 ? "bg-progress-ranked-1" : "bg-secondary"} progress-bar-striped progress-bar-animated" style="width: ${(playerCardAccount.expCurrent / playerCardAccount.expGoal) * 100}%">
                        </div>
                    </div>
                </span>
            </h5>
            <h6 class="player-card-account-title">${playerCardAccount.title}</h6>
        </div>
        <div class="card-body align-items-start d-flex justify-content-start${isRanked1 ? " ranked-1" : ""}">
            <img class="img-fluid img-thumbnail" src="${playerCardAccount.avatar.url}" />
            <div class="player-card-ranks">
                ${rankCode}
            </div>
        </div>
        <div class="card-footer${isRanked1 ? " ranked-1" : ""}">
            ${badgeCode}
            <span style="font-size: 1.3em;" class="float-end">
                <i class="fa-solid fa-${playerCardAccount.prestige < 0 ? "biohazard" : "crown"}"></i> ${Math.abs(playerCardAccount.prestige)}
            </span>
        </div>
    </div>
    `;

return playerCardHTML;
}
</script>