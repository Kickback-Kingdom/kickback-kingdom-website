<?php 
declare(strict_types=1);
use Kickback\Backend\Controllers\LootController;
use Kickback\Backend\Controllers\AccountController;
use Kickback\Common\Version;


$badgeCode = '';
for ($i=0; $i < count($_vPlayerCardAccount->badge_display) && $i < 5; $i++)
{
    $badge = $_vPlayerCardAccount->badge_display[$i];

    //$badgeCode .= '<li class="d-inline-block" style="margin-left:0px;"><img class="img-40" style="box-shadow: none; border: none; -webkit-box-shadow: none;" src="/assets/media/'.$badge["SmallImgPath"].'" alt=""></li>';
    $badgeCode .= '<span tabindex="0" data-bs-toggle="popover" data-bs-custom-class="custom-popover" data-bs-trigger="focus" data-bs-placement="top" data-bs-title="'.htmlspecialchars($badge->item->name).'" data-bs-content="'.htmlspecialchars($badge->item->description).'"><img src="'.$badge->item->iconSmall->getFullPath().'" class="loot-badge"></span>';
}

$rankCode = '';
$isRanked1 = false;
for ($i=0; $i < count($_vPlayerCardAccount->game_ranks); $i++) { 
    $rank = $_vPlayerCardAccount->game_ranks[$i];

    if ($rank['rank'] == null)
    {
        $rankCode .= '<div><a href="/g/'.$rank['locator'].'">'.htmlspecialchars($rank['name']).'</a> <span class="badge unranked float-end" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Unranked: '.($rank['minimum_ranked_matches_required']-$rank['ranked_matches']).' matches remaining">'.$rank['ranked_matches'].' / '.$rank['minimum_ranked_matches_required'].'</span></div>';
    }
    else{
        if ($rank["rank"]==1)
        {
          $isRanked1 = true;
        }
        $rankCode .= '<div><a href="/g/'.$rank['locator'].'">'.htmlspecialchars($rank['name']).'</a> <span class="badge ranked'.($rank['rank']==1?"-1":"").' float-end" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Ranked #'.$rank['rank'].' Kingdom Wide">#'.$rank['rank'].'</span></div>';
    }

    
}


for ($i = count($_vPlayerCardAccount->game_ranks); $i < 5; $i++ )
{
   $rankCode .= '<div><span class="badge float-start unranked" style="
    width: 160px;
    background-color: #00000029;
    color: transparent;
    margin-bottom:1px;
"> / </span><span class="badge unranked float-end" style="
    background: #00000073;
    color: transparent;
">X / X</span></div>';

}

?>


<div class="card player-card<?php echo ($isRanked1?" ranked-1":"")?>">
  <div class="ribbons-container">
      <div class="ribbon red"></div>
      
    <?php if ($_vPlayerCardAccount->isMerchant) { ?>
      <div class="ribbon blue"></div>
      <?php } ?>
      <?php if ($_vPlayerCardAccount->isSteward) { ?>
      <div class="ribbon green"></div>
      <?php } ?>
      <?php if (true == false) { ?>
      <div class="ribbon yellow"></div>
      <?php } ?>
      <?php if ($_vPlayerCardAccount->isSteward) { ?>
      <div class="ribbon purple"></div>
      <?php } ?>
  </div>

  <div class="card-header<?php echo ($isRanked1?" ranked-1":"")?>">
    <h5 class="player-card-name"><a href="<?php echo Version::urlBetaPrefix(); ?>/u/<?= htmlspecialchars($_vPlayerCardAccount->username); ?>" class="link-dark link-underline-opacity-0 <?= ($isRanked1?"link-ranked-1":"")?>"><?= htmlspecialchars($_vPlayerCardAccount->username); ?></a>
    
    <span> Level <?= $_vPlayerCardAccount->level; ?>
      <div class="progress" role="progressbar" aria-label="Animated striped example" aria-valuenow="<?= $_vPlayerCardAccount->expCurrent; ?>" aria-valuemin="0" aria-valuemax="<?= $_vPlayerCardAccount->expGoal; ?>"data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="<?= ($_vPlayerCardAccount->expCurrent*10).'/'.($_vPlayerCardAccount->expGoal*10); ?> EXP">
        <div class="progress-bar <?php echo ($isRanked1?"bg-progress-ranked-1":"bg-secondary")?> progress-bar-striped progress-bar-animated" style="width: <?= ($_vPlayerCardAccount->expCurrent/$_vPlayerCardAccount->expGoal)*100; ?>%">
        </div>
      </div>
    </span>
    </h5>
    <h6 class="player-card-account-title"><?= $_vPlayerCardAccount->title; ?>
    </h6>
  </div>
  <div class="card-body align-items-start d-flex justify-content-start<?php echo ($isRanked1?" ranked-1":"")?>">
    <img class="img-fluid img-thumbnail" src="<?= $_vPlayerCardAccount->profilePictureURL(); ?>" />
    <div class="player-card-ranks">
        <?= $rankCode; ?>
    </div>
  </div>
  <!--player card footer-->
  <div class="card-footer<?= ($isRanked1?" ranked-1":"")?>">
    <?= $badgeCode; ?>
    <span style="font-size: 1.3em;" class="float-end">
      <i class="fa-solid fa-<?= ($_vPlayerCardAccount->prestige<0?"biohazard":"crown")?>"></i> <?= abs($_vPlayerCardAccount->prestige); ?>
    </span>
  </div>
</div>
