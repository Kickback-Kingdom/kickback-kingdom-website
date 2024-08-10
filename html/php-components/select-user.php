<?php 

if (!isset($selectUserFormId))
{
    $selectUserFormId = "Default";
}

if (!isset($selectUsersFormPageSize))
{
    $selectUsersFormPageSize = 6;
}

if (!isset($selectUsersPageIndex))
{
    $selectUsersPageIndex = 1;
}

if (!isset($selectUsersClickableFunction))
{
    $selectUsersClickableFunction = "null";
}

if (!isset($selectUsersFilter))
{
    $selectUsersFilter = "{}";
}

?>

<div class="col-lg-6 offset-lg-3">
<div class="input-group">
<span class="input-group-text text-bg-primary" ><i class="fa-solid fa-address-card"></i></span>
  <input type="text" class="form-control" onchange='OnSelectAccountChangeSearchParams("<?= $selectUserFormId; ?>", <?= $selectUsersPageIndex; ?>, <?= $selectUsersClickableFunction; ?>, <?= $selectUsersFilter; ?>)' id="<?php echo $selectUserFormId; ?>selectAccountSearchTerm">
  <button class="btn btn-primary" type="button" onclick='OnSelectAccountChangeSearchParams("<?= $selectUserFormId; ?>", <?= $selectUsersPageIndex; ?>, <?= $selectUsersClickableFunction; ?>, <?= $selectUsersFilter; ?>)'>Search</button>

</div>
</div>
<div class="card mt-3">
    <div class="card-body">
        <div class="row">
            <div class="col-12">
                <div id="<?php echo $selectUserFormId; ?>selectUserPagination">
                    <!-- Pagination buttons will be inserted here via JS -->
                </div>
                <div  class="d-flex flex-wrap justify-content-evenly align-items-center mt-3" id="<?php echo $selectUserFormId; ?>selectAccountSearchResults" data-users-per-page="<?php echo $selectUsersFormPageSize; ?>" style="border-style: none;">
                    
                </div>
            </div>
        </div> 
    </div>
</div>
