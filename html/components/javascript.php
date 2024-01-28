<!-- latest jquery-->
<script src="../assets/js/jquery-3.5.1.min.js"></script>
<!-- Bootstrap js-->
<script src="../assets/js/bootstrap/bootstrap.bundle.min.js"></script>
<!-- feather icon js-->
<script src="../assets/js/icons/feather-icon/feather.min.js"></script>
<script src="../assets/js/icons/feather-icon/feather-icon.js"></script>
<!-- scrollbar js-->
<script src="../assets/js/scrollbar/simplebar.js"></script>
<script src="../assets/js/scrollbar/custom.js"></script>
<!-- Sidebar jquery-->
<script src="../assets/js/config.js"></script>
<!-- Plugins JS start-->
<script src="../assets/js/sidebar-menu.js"></script>
<script src="../assets/js/prism/prism.min.js"></script>
<script src="../assets/js/clipboard/clipboard.min.js"></script>
<script src="../assets/js/custom-card/custom-card.js"></script>
<!--<script src="../assets/js/typeahead/handlebars.js"></script>
<script src="../assets/js/typeahead/typeahead.bundle.js"></script>
<script src="../assets/js/typeahead/typeahead.custom.js"></script>
<script src="../assets/js/typeahead-search/handlebars.js"></script>
<script src="../assets/js/typeahead-search/typeahead-custom.js"></script>-->
<script src="../assets/js/tooltip-init.js"></script>
<script src="../assets/js/datatable/datatables/jquery.dataTables.min.js"></script>
<script src="../assets/js/photoswipe/photoswipe.min.js"></script>
<script src="../assets/js/photoswipe/photoswipe-ui-default.min.js"></script>
<script src="../assets/js/photoswipe/photoswipe.js"></script>
    <script src="../assets/js/select2/select2.full.min.js"></script>
<!-- Plugins JS Ends-->
<!-- Theme js-->
<script src="../assets/js/script.js"></script>
<!--<script src="../assets/js/theme-customizer/customizer.js"></script>-->
<!-- login js-->
<!-- Plugin used-->


<script>
$(document).ready(function() {
                                    console.log("JQuery OnReady");
    $('#basic-1').DataTable();


    $('#myModal').modal({
        backdrop: 'static',
        keyboard: false
    })


    if (isBetaEnabled())
        {
            $("#btnEnableBeta").hide();
        }
        else{

            $("#btnDisableBeta").hide();
        }

    <?php if (IsLoggedIn()){ ?>
    OpenAllChests();
    
    if (sessionStorage.getItem('showActionModal') === 'true') {
        // Show the modal
        $('#actionModal').modal("show");
        DisableShowActionModal();
    }
            $(".js-example-basic-single").select2();

            <?php
            if (isset($thisQuest))
            {
                echo "//".$thisQuest["name"];
                if ($thisQuest["tournament_id"] != null)
                {
                    ?>

                    

            <?php
                }
            }
            ?>
           
    <?php } ?>

    



});


function SetShowActionModal()
{
    sessionStorage.setItem('showActionModal', 'true');
}

function DisableShowActionModal()
{
    sessionStorage.setItem('showActionModal', 'false');
}

<?php
if (IsLoggedIn())
{


?>

var chestElement = document.getElementById("imgChest");
var imgShineBackground = document.getElementById("imgShineBackground");
var imgShineForeground = document.getElementById("imgShineForeground");
var imgItem = document.getElementById("imgItem");
var showAnimation = "jackInTheBox";
var openAnimation = "tada";
var itemAnimation = "flip";

function animate(id, lastAnimation, newAnimation) {
    $('#' + id).removeClass("animated");
    $('#' + id).removeClass(lastAnimation);
    $('#' + id).addClass(newAnimation);
    $('#' + id).addClass("animated");
}

function OpenChest() {
    //var chestRarityArray = [9,9,9,9,9,9];
    chestElement.src = "../assets/media/chests/Loot_Box_0"+(parseInt(chests[0]["rarity"])+1)+"_02_Star.png";
    animate("myModal", showAnimation, openAnimation);
    $('#imgItem').addClass(itemAnimation);
    $('#imgItem').addClass("animated");
    imgShineForeground.style.visibility = "visible";
    imgShineBackground.style.visibility = "hidden";
    imgShineForeground.src = "../assets/media/chests/"+chests[0]["rarity"]+"_o_s.png";
    imgItem.style.visibility = "visible";
}

function ShowChest() {
    StartConfetti();
    if (chests[0]["Id"] % 8 == 0)
    {
        chests[0]["rarity"] = 0;
    }
    //var chestRarityArray = [10,10,10,10,10,10];
    chestElement.src = "../assets/media/chests/Loot_Box_0"+(parseInt(chests[0]["rarity"])+1)+"_01_Star.png";
    animate("myModal", openAnimation, showAnimation);
    $('#imgItem').removeClass("animated");
    $('#imgItem').removeClass(itemAnimation);
    imgShineBackground.style.visibility = "visible";
    imgShineForeground.style.visibility = "hidden";
    imgShineBackground.src = "../assets/media/chests/"+chests[0]["rarity"]+"_c_s.png";
    imgItem.style.visibility = "hidden";
    imgItem.src = "../assets/media/"+chests[0]["ItemImg"];

    $("#myModal").modal("show");
}

function CloseChest() {

    $("#myModal").modal("hide");

    const data = {
                chestId: chests[0]["Id"],
                accountId: <?php echo $GLOBALS["account"]["Id"]; ?>,
                sessionToken: "<?php echo $GLOBALS["account"]["SessionToken"]; ?>"
            };
    chests.shift();
    const params = new URLSearchParams();

    for (const [key, value] of Object.entries(data)) {
    params.append(key, value);
    }

    fetch('/api/v1/chest/close.php?json', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: params
    })
    .then(response => response.text())
    .then(data => console.log(data));
}

function ToggleChest() {
    if ($('#myModal').hasClass('show')) {
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
        setTimeout(() => {
            ShowChest();
        }, (500));

    }
    else{
        StopConfetti();
    }
}

<?php

}
?>

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

</script>