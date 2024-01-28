<?php 

$badgesResp = GetBadgesByAccountId($playerCardAccount['Id']);
$badges = $badgesResp->Data;

$playerRankResp = GetAccountGameRanks($playerCardAccount['Id']);
$playerRanks = $playerRankResp->Data;

$badgeCode = '';
for ($i=0; $i < count($badges) && $i < 5; $i++) { 

    $badge = $badges[$i];


    //$badgeCode .= '<li class="d-inline-block" style="margin-left:0px;"><img class="img-40" style="box-shadow: none; border: none; -webkit-box-shadow: none;" src="/assets/media/'.$badge["SmallImgPath"].'" alt=""></li>';
    $badgeCode .= '<span tabindex="0" data-bs-toggle="popover" data-bs-custom-class="custom-popover" data-bs-trigger="focus" data-bs-placement="top" data-bs-title="'.htmlspecialchars($badge['name']).'" data-bs-content="'.htmlspecialchars($badge['desc']).'"><img src="/assets/media/'.$badge["SmallImgPath"].'" class="loot-badge"></span>';
}

$rankCode = '';
$isRanked1 = false;
for ($i=0; $i < count($playerRanks); $i++) { 
    $rank = $playerRanks[$i];

    if ($rank['rank'] == null)
    {
        $rankCode .= '<div>'.htmlspecialchars($rank['name']).' <span class="badge unranked float-end" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Unranked: '.($rank['minimum_ranked_matches_required']-$rank['ranked_matches']).' matches remaining">'.$rank['ranked_matches'].' / '.$rank['minimum_ranked_matches_required'].'</span></div>';
    }
    else{
        if ($rank["rank"]==1)
        {
          $isRanked1 = true;
        }
        $rankCode .= '<div>'.htmlspecialchars($rank['name']).' <span class="badge ranked'.($rank['rank']==1?"-1":"").' float-end" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Ranked #'.$rank['rank'].' Kingdom Wide">#'.$rank['rank'].'</span></div>';
    }

    
}

?>


<div class="card player-card<?php echo ($isRanked1?" ranked-1":"")?>">
  <div class="ribbons-container">
      <div class="ribbon red"></div>
      <div class="ribbon blue"></div>
      <div class="ribbon green"></div>
      <div class="ribbon yellow"></div>
      <div class="ribbon purple"></div>
  </div>

  <div class="card-header<?php echo ($isRanked1?" ranked-1":"")?>">
    <h5 class="player-card-name"><a href="<?php echo $urlPrefixBeta; ?>/u/<?php echo htmlspecialchars($playerCardAccount["Username"]); ?>" class="link-dark link-underline-opacity-0 <?php echo ($isRanked1?"link-ranked-1":"")?>"><?php echo htmlspecialchars($playerCardAccount["Username"]); ?></a>
    
    <span> Level <?php echo htmlspecialchars($playerCardAccount["level"]); ?>
      <div class="progress" role="progressbar" aria-label="Animated striped example" aria-valuenow="<?php echo htmlspecialchars($playerCardAccount["exp_current"]); ?>" aria-valuemin="0" aria-valuemax="<?php echo htmlspecialchars($playerCardAccount["exp_goal"]); ?>"data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="<?php echo ($playerCardAccount["exp_current"]*10).'/'.($playerCardAccount["exp_goal"]*10); ?> EXP">
        <div class="progress-bar <?php echo ($isRanked1?"bg-progress-ranked-1":"bg-secondary")?> progress-bar-striped progress-bar-animated" style="width: <?php echo htmlspecialchars(($playerCardAccount["exp_current"]/$playerCardAccount["exp_goal"])*100); ?>%">
        </div>
      </div>
    </span>
    </h5>
    <h6 class="player-card-account-title"><?php echo GetAccountTitle($playerCardAccount); ?>
    </h6>
  </div>
  <div class="card-body align-items-start d-flex justify-content-start<?php echo ($isRanked1?" ranked-1":"")?>">
    <img class="img-fluid img-thumbnail" src="/assets/media/<?php echo GetAccountProfilePicture($playerCardAccount); ?>" />
    <div class="player-card-ranks">
        <?php echo $rankCode; ?>
    </div>
  </div>
  <!--player card footer-->
  <div class="card-footer<?php echo ($isRanked1?" ranked-1":"")?>">
    <?php echo $badgeCode; ?>
    <span style="font-size: 1.3em;" class="float-end">
      <i class="fa-solid fa-<?php echo ($playerCardAccount["prestige"]<0?"biohazard":"crown")?>"></i> <?php echo abs($playerCardAccount["prestige"]); ?>
    </span>
  </div>
</div>