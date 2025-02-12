
<script>

$(window).on('load', function() {
    $('#loading-overlay').fadeOut('slow', function() {
        $('body').addClass('body-finished-loading');  // add class to restore scrolling
        showNextPopups();
    });
});


function showNextPopups()
{   
    console.log("Showing all popups...");

    //elo progress
    if (typeof showNextEloProgress === 'function' && !showEloIsDone()) 
    {
        console.log("Starting with elo progress");
        showNextEloProgress();
        return;
    }
    else
    {
        console.log("no need to show elo progress");
    }

    //chests
    if (typeof OpenAllChests === "function" && Array.isArray(chests) && chests.length > 0) {
        console.log("Starting with chests");
        OpenAllChests();
        return;
    } else {
        console.log("no need to show chests");
    }
    // Prestige reviews
    if (typeof notificationsJSON !== "undefined" && Array.isArray(notificationsJSON) && notificationsJSON.length > 0) {
        var oldestPrestigeReviewIndex = findOldestPrestigeReviewIndex(); // Use the correct function

        if (oldestPrestigeReviewIndex >= 0) {
            LoadNotificationViewPrestige(oldestPrestigeReviewIndex);
            return;
        }
    }

    console.log("No need to show prestige");


}

function findOldestPrestigeReviewIndex() {
    var reviews = notificationsJSON.filter(review => review.type === "Prestige");
    if (!reviews || reviews.length === 0) return -1; // Return -1 if no Prestige reviews

    return notificationsJSON.indexOf(reviews[reviews.length - 1]); // Find index in original array
}

$('a').click(function(event) {
    var href = $(this).attr('href');

    // Check if href is valid and not just a placeholder like '#'
    if (href && href != '#' && !href.startsWith('#')) {
        event.preventDefault();  // prevent the default action

        $('body').css('overflow', 'hidden');  // prevent scrolling

        $('#loading-overlay').fadeIn('slow', function() {
            // when the fade-in is complete, navigate to the new page
            window.location.href = href;
        });
    }
});



</script>