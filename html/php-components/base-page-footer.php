
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
  <div class="col-md-4 d-flex align-items-center">
    <a href="#" class="mb-3 me-2 mb-md-0 text-body-secondary text-decoration-none lh-1">
      <svg class="bi" width="30" height="24"><use xlink:href="#bootstrap"></use></svg>
    </a>
    <span class="mb-3 mb-md-0 text-body-secondary">© 2024 Kickback Kingdom - <a href="#" onclick="ShowVersionPopUp();">v<?= Version::current()->number(); ?></a></span>
  </div>

  <ul class="nav col-md-4 justify-content-end list-unstyled d-flex">
    <li class="ms-3"><a class="text-body-secondary" href="#"><i class="fa-brands fa-youtube"></i></a></li>
    <li class="ms-3"><a class="text-body-secondary" href="#"><i class="fa-brands fa-instagram"></i></a></li>
  </ul>
</footer>
<?php } ?>
