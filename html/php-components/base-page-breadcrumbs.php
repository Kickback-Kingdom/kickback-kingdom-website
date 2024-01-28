<?php

if (!isset($activePageName))
{
    $activePageName = "No Page Name Set";
}

?>

<div class="card mb-3 bg-primary"  data-bs-theme="dark">
    <div class="border-0 card-header">
        <h5 class="card-title mb-0">
            <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item active" aria-current="page"><?php echo $activePageName; ?></li>
                </ol>
            </nav>
        </h5>
    </div>
</div>