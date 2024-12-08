<?php
declare(strict_types=1);

use Kickback\Common\Version;

?>
<script>

function GetMatchHistory(gameId, pageIndex = 1, matchesPerPage = 10)
{

    const data = {
        gameId: gameId,
        sessionToken: "<?= $_SESSION["sessionToken"] ?? ""; ?>",
        page: pageIndex,
        itemsPerPage: matchesPerPage
    };

    const params = new URLSearchParams();

    for (const [key, value] of Object.entries(data)) {
        
        params.append(key, value);
    }

    fetch('<?= Version::formatUrl("/api/v1/match/history.php?json"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: params
    }).then(response=>response.text()).then(data=>LoadMatchHistoryResults(data, matchesPerPage, pageIndex));
}

function LoadMatchHistoryResults(data, matchesPerPage, pageIndex)
{
    var response = JSON.parse(data);
    
    console.log(data);
    console.log(matchesPerPage);
    console.log(pageIndex);
}

</script>