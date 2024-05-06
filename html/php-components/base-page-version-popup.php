


<!-- VERSION CHANGELOG MODAL /blogpost.php?blogLocator=Kickback-Kingdom&postLocator=introduction&borderless -->
<div class="modal fade" id="versionModal" tabindex="-1" aria-labelledby="versionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="versionModalLabel">New Update!</h1>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <select class="form-select" id="versionSelect" onchange="showChangelog()" aria-label="Select version">
                <?php foreach ($_globalVersionInfo as $version => $path): ?>
                    <option value="<?= htmlspecialchars($path) ?>"><?= htmlspecialchars($version) ?></option>
                <?php endforeach; ?>
        </select>
        <iframe id="changelogIframe" style="width:100%; height:70vh; margin-top:20px;" frameborder="0" src=""></iframe>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn bg-ranked-1" data-bs-dismiss="modal">Okay</button>
      </div>
    </div>
  </div>
</div>
<script>
    var shouldShowVersionPopup = <?= ((!isset($_COOKIE['popupShownVersion']) || $_COOKIE['popupShownVersion'] != $_globalVersionCurrent) && !isset($changelogVersion) && !$_globalDoNotShowNewVersionPopup)?"true":"false"; ?>;
    
    function ShowVersionPopUp() {
        $("#versionModal").modal('show');

        document.getElementById('changelogIframe').src = "<?= $urlPrefixBeta."/blogpost.php?borderless&blogLocator=Kickback-Kingdom&postLocator=".$_globalVersionInfo[$_globalVersionCurrent];?>";
        document.cookie = "popupShownVersion=<?= $_globalVersionCurrent ?>; path=/; expires=Fri, 31 Dec 9999 23:59:59 GMT";
        shouldShowVersionPopup = false;
    }

    function LoadVersionIframe() {
        var selectedPath = document.getElementById('versionSelect').value;
            document.getElementById('changelogIframe').src = "<?= $urlPrefixBeta."/blogpost.php?borderless&blogLocator=Kickback-Kingdom&postLocator="; ?>"+selectedPath;
    }

    // Adjust iframe height dynamically based on its content
    function adjustIframeHeight(iframe) {
        iframe.style.height = iframe.contentWindow.document.documentElement.scrollHeight + 'px';
    }

    document.getElementById('changelogIframe').onload = function() {
        adjustIframeHeight(this);
    };
</script>