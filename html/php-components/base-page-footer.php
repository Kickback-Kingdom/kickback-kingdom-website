
<?php
use \Kickback\Common\Version;
?>

<!-- ERROR MODAL -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header text-bg-danger">
        <h1 class="modal-title fs-5" id="errorModalLabel">Modal title</h1>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p id="errorModalMessage"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn bg-ranked-1" data-bs-dismiss="modal">Okay</button>
      </div>
    </div>
  </div>
</div> 

<!-- SUCCESS MODAL -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="successModalLabel">Modal title</h1>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p id="successModalMessage"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn bg-ranked-1" data-bs-dismiss="modal">Okay</button>
      </div>
    </div>
  </div>
</div>

<!--LOADING MODAL-->
<div class="modal fade" id="loadingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"  aria-labelledby="loadingModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        
      <div class="d-flex align-items-center flex-fill">
        <h5 class="modal-title" id="loadingModalLabel">Loading...</h5>        
        
        <i class="fa-solid fa-slash fa-spin ms-auto"></i>
        </div>
      </div>
      <div class="modal-body">
        <div class="progress" id="loadingModalProgress" role="progressbar" aria-label="Animated striped example" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100">
            <div  id="loadingModalProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" style="width: 75%"></div>
        </div>
      </div> 
      <div class="modal-footer">
        Please Wait...
      </div>
    </div>
  </div>
</div>

<?php if (!isset($_GET['borderless'])) { ?>
<footer class="d-flex flex-wrap justify-content-between align-items-center py-3 my-4 border-top">
  <div class="col-md-8 d-flex align-items-center">
    <a href="#" class="mb-3 me-2 mb-md-0 text-body-secondary text-decoration-none lh-1">
      <svg class="bi" width="30" height="24"><use xlink:href="#bootstrap"></use></svg>
    </a>
    <span class="mb-3 mb-md-0 text-body-secondary">© 2024 Kickback Kingdom - <a href="#" onclick="ShowVersionPopUp();">v<?= Version::current()->number(); ?></a> - <a href="/privacy-policy.php">Privacy Policy</a> - <a href="/terms-of-service.php">Terms of Service</a></span>
  </div>

  <ul class="nav col-md-4 justify-content-end list-unstyled d-flex">
    <li class="ms-3"><a class="text-body-secondary" href="#"><i class="fa-brands fa-youtube"></i></a></li>
    <li class="ms-3"><a class="text-body-secondary" href="#"><i class="fa-brands fa-instagram"></i></a></li>
  </ul>
</footer>
<?php } ?>


<?php if (!empty($currentHiddenObjects)): ?>
    <div id="treasure-hidden-object-layer" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 1000;">
        <?php foreach ($currentHiddenObjects as $obj): ?>
            <div
                class="treasure-object"
                style="
                    position: absolute;
                    top: <?= $obj->yPercentage ?>%;
                    left: <?= $obj->xPercentage ?>%;
                    pointer-events: auto;
                    cursor: pointer;
                    z-index: 1000;
                "
                onclick="submitTreasureHuntFoundObject('<?= $obj->ctime ?>', <?= $obj->crand ?>, '<?= $obj->getURL(); ?>')"
            >
                <!-- Shine Spinner -->
                <img 
                    src="/assets/media/chests/0_o_s.png" 
                    class="shine-spin" 
                    alt="Shine"
                >

                <!-- Main Object -->
                <img
                    src="<?= $obj->media->url ?>"
                    alt="Hidden Treasure"
                    title="Hidden Treasure"
                    class="main-object-img"
                >
            </div>
        <?php endforeach; ?>
    </div>
    
    <style>
@keyframes treasure-rotate-pulse {
    0%   { transform: rotate(-10deg) scale(1);  }
    25%  { transform: rotate(0deg) scale(1.05);  }
    50%  { transform: rotate(10deg) scale(1.1);  }
    75%  { transform: rotate(0deg) scale(1.05); }
    100% { transform: rotate(-10deg) scale(1); }
}

@keyframes spin-slow {
    0%   { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.treasure-object {
    position: relative;
    width: 48px;
    height: 48px;
    transform: scale(3.5);
    transform-origin: center;
}

.treasure-object .main-object-img {
    position: relative;
    width: 100%;
    height: 100%;
    animation: treasure-rotate-pulse 3.5s ease-in-out infinite;
    transform-origin: center;
    z-index: 2;
}

.treasure-object .shine-spin {
    position: absolute;
    top: -20px;
    left: -20px;
    width: 88px;
    height: 88px;
    z-index: 1;
    pointer-events: none;
    user-select: none;
    animation: spin-slow 6s linear infinite;
    opacity: 0.6;
}

.treasure-object:hover .main-object-img {
    filter: drop-shadow(0 0 5px gold);
}
</style>



<?php endif; ?>
