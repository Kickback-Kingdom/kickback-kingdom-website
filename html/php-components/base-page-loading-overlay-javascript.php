
<script>

$(window).on('load', function() {
    $('#loading-overlay').fadeOut('slow', function() {
        $('body').addClass('body-finished-loading');  // add class to restore scrolling
        showNextEloProgress();
    });
});

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