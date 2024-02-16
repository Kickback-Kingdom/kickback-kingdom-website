<!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="<?php echo $urlPrefixBeta; ?>/assets/vendors/jquery/jquery-3.7.0.min.js"></script>
    <!--<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>-->
    <script src="<?php echo $urlPrefixBeta; ?>/assets/vendors/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prettify/r298/run_prettify.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.js"></script>

    <!--<script src="assets/owl-carousel/owl.carousel.js"></script>-->
    <script>
        var play_styles = <?php echo GetPlayStyleJSON(); ?>;
        $(document).ready(function () {

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

            if (IsLoggedIn())
            {
            ?>
            OpenAllChests();
            <?php 

            }
            ?>

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
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

        const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]')
        const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl))


        <?php 

        if (IsLoggedIn())
        {
        ?>

        var chests = <?php echo  $chestsJSON; ?>;
        var notificationsJSON = <?php echo $activeAccountInfo->notificationsJSON; ?>;
    
        var chestElement = document.getElementById("imgChest");
        var imgShineBackground = document.getElementById("imgShineBackground");
        var imgShineForeground = document.getElementById("imgShineForeground");
        var imgItem = document.getElementById("imgItem");


        function OpenChest() {
            //var chestRarityArray = [9,9,9,9,9,9];
            chestElement.src = "/assets/media/chests/Loot_Box_0" + (parseInt(chests[0]["rarity"]) + 1) + "_02_Star.png";
            $('#imgItem').addClass('chest-item-animate');
            $('#modalChest').addClass('chest-open-animate');
            imgShineForeground.style.visibility = "visible";
            imgShineBackground.style.visibility = "hidden";
            imgShineForeground.src = "/assets/media/chests/" + chests[0]["rarity"] + "_o_s.png";
            imgItem.style.visibility = "visible";
        }

        function ShowChest() {
            StartConfetti();
            if (chests[0]["Id"] % 8 == 0) {
                chests[0]["rarity"] = 0;
            }
            //https://kickback-kingdom.com/assets/media/chests/Loot_Box_02_01_Star.png
            //var chestRarityArray = [10,10,10,10,10,10];
            chestElement.src = "/assets/media/chests/Loot_Box_0" + (parseInt(chests[0]["rarity"]) + 1) + "_01_Star.png";
            $('#imgItem').removeClass('chest-item-animate');
            $('#modalChest').removeClass('chest-open-animate');
            imgShineBackground.style.visibility = "visible";
            imgShineForeground.style.visibility = "hidden";
            imgShineBackground.src = "/assets/media/chests/" + chests[0]["rarity"] + "_c_s.png";
            imgItem.style.visibility = "hidden";
            imgItem.src = "/assets/media/" + chests[0]["ItemImg"];

            $("#modalChest").modal("show");
        }

        function CloseChest() {

            $("#modalChest").modal("hide");

            const data = {
                chestId: chests[0]["Id"],
                accountId: <?php echo $_SESSION["account"]["Id"]; ?>,
                sessionToken: "<?php echo $_SESSION["sessionToken"]; ?>"
            };
            chests.shift();
            const params = new URLSearchParams();

            for (const [key,value] of Object.entries(data)) {
                params.append(key, value);
            }

            fetch('/api/v1/chest/close.php?json', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: params
            }).then(response=>response.text()).then(data=>console.log(data));
        
        }

        function ToggleChest() {
            if ($('#modalChest').hasClass('show')) {
                if (imgItem.style.visibility == "hidden") {

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
            }
        }
        <?php

        }
        
        ?>



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
            // Set "betaEnabled" to "true" in sessionStorage
            sessionStorage.setItem("betaEnabled", "true");
            window.location.href = "/beta/?beta=1";
        }

        function disableBeta() {
            // Set "betaEnabled" to "false" in sessionStorage
            sessionStorage.setItem("betaEnabled", "false");
            window.location.href = "/?beta=0";
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
            // Get the value of "betaEnabled" from sessionStorage
            let betaEnabled = sessionStorage.getItem("betaEnabled");

            // If "betaEnabled" is the string "true", return true. Otherwise, return false.
            return betaEnabled === "true";
        }

        var itemInformation = <?php echo (isset($itemInformationJSON)?$itemInformationJSON:"[]"); ?>;
        function ShowInventoryItemModal(itemId) 
        {
            var item = GetItemInformationById(itemId);
            console.log(item);
            $("#inventoryItemImage").attr("src", "/assets/media/"+item.image);
            $("#inventoryItemDescription").text(item.desc);
            $("#inventoryItemTitle").text(item.name);
            $("#inventoryItemArtist").text(item.artist);
            $("#inventoryItemArtist").attr("href", "<?php echo $urlPrefixBeta; ?>/u/"+item.artist);
            $("#inventoryItemDate").text(item.date_created);
            $("#inventoryItemModal").modal("show");
            setTimeout(function() {
            $('[data-bs-toggle="tooltip"]').tooltip('hide');
            }, 10);
        }

        function GetItemInformationById(id)
        {
            for (let index = 0; index < itemInformation.length; index++) {
                var item = itemInformation[index];
                if (item.Id == id)
                {
                    return item;
                }
            }
            return null;
        }

        function LoadQuestHostReviewModal(id)
        {

        }

        function LoadQuestReviewModal(id)
        {
            var notification = notificationsJSON[id];

            $("#quest-review-quest-image").attr("src", "/assets/media/"+notification.image);
            $("#quest-review-quest-title-link").attr("href", "<?php echo $urlPrefixBeta; ?>/q/"+notification.locator);
            $("#quest-review-quest-title").text(notification.name);
            $("#quest-review-quest-host-1").attr("href", "<?php echo $urlPrefixBeta; ?>/u/"+notification.host_name);
            $("#quest-review-quest-host-1").text(notification.host_name);
            $("#quest-review-quest-host-2").attr("href", "<?php echo $urlPrefixBeta; ?>/u/"+notification.host_name_2);
            $("#quest-review-quest-host-2").text(notification.host_name_2);
            if (notification.host_name_2 == null)
                $("#quest-review-quest-host-2-span").attr("class","d-none");
            else
                $("#quest-review-quest-host-2-span").attr("class","d-inline");
                

            let dateObject = new Date(notification.date);

            let options = { year: 'numeric', month: 'short', day: 'numeric' };
            let formattedDate = dateObject.toLocaleDateString(undefined, options);

            $("#quest-review-quest-date").text(formattedDate);


            $("#quest-review-play-style").attr("class","quest-tag quest-tag-"+play_styles[notification.style][0].toLowerCase());
            $("#quest-review-play-style").text(play_styles[notification.style][0]);
            $("#quest-review-quest-summary").text(notification.text);
            $("#quest-review-quest-id").attr("value",notification.quest_id);
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
<?php if (IsAdmin()) { ?>
    function UseDelegateAccess(accountId)
    {
        window.location.href = "https://www.kickback-kingdom.com<?php echo $urlPrefixBeta; ?>/?delegateAccess="+accountId;
    }

<?php } ?>
    </script>

<?php 

require("base-page-loading-overlay-javascript.php"); 
require("base-page-javascript-account-search.php"); 


?>
