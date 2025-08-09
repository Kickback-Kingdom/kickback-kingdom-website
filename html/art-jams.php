<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");



// ===================== ART JAM CONFIG (themed like the raffle timer) =====================
$scheduleString = "Friday 11PM BRT";     // Wednesdays, 8–11pm São Paulo time
$tz = new DateTimeZone('America/Sao_Paulo');
$jamDuration = new DateInterval('PT3H');   // 3 hours
$subjectUnlockLead = new DateInterval('PT5M'); // subject unlocks 5 minutes before start

// ---- Helpers (namespaced) ----
if (!function_exists('aj_parseSchedule')) {
    function aj_parseSchedule($str) {
        $days = '(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday|Mon|Tue|Wed|Thu|Fri|Sat|Sun)';
        $regex = '/^\s*' . $days . '\s+(\d{1,2})(?::(\d{2}))?\s*(AM|PM)?\b/iu';
        if (!preg_match($regex, $str, $m)) throw new InvalidArgumentException("Bad schedule string.");
        $day = $m[1]; $hour = (int)$m[2]; $min = isset($m[3]) && $m[3] !== '' ? (int)$m[3] : 0; $ampm = isset($m[4]) ? strtoupper($m[4]) : null;
        if ($ampm) { if ($hour === 12) $hour = 0; if ($ampm === 'PM') $hour += 12; }
        $map = ['Mon'=>'Monday','Tue'=>'Tuesday','Wed'=>'Wednesday','Thu'=>'Thursday','Fri'=>'Friday','Sat'=>'Saturday','Sun'=>'Sunday'];
        if (isset($map[$day])) $day = $map[$day];
        return [$day, $hour, $min];
    }
    function aj_nextOccurrence(DateTimeImmutable $base, string $weekday, int $hour, int $minute, DateTimeZone $tz): DateTimeImmutable {
        $base = $base->setTimezone($tz);
        $candidate = $base->modify("this $weekday")->setTime($hour,$minute,0);
        if ($candidate < $base) $candidate = $base->modify("next $weekday")->setTime($hour,$minute,0);
        return $candidate;
    }
    function aj_weekSeed(DateTimeImmutable $dt): string { return $dt->format('o') . 'W' . $dt->format('W'); }
    function aj_pickStable(array $list, string $seed, string $salt=''): string { $idx = crc32($seed.'|'.$salt)%count($list); return $list[$idx]; }
    function aj_combineStable(array $a, array $b, string $seed, string $salt=''): string {
        $p1 = $a[crc32($seed.'|'.$salt.'|a')%count($a)];
        $p2 = $b[crc32($seed.'|'.$salt.'|b')%count($b)];
        return ucfirst("$p1 $p2");
    }
    function aj_humanInterval(int $s): string {
        if ($s<=0) return 'now';
        $out=[]; $units=['day'=>86400,'hour'=>3600,'minute'=>60,'second'=>1];
        foreach($units as $n=>$len){$v=intdiv($s,$len); if($v>0){$out[]=$v.' '.$n.($v>1?'s':''); $s-=$v*$len;} if(count($out)>=2)break;}
        return implode(', ',$out);
    }
}

// ---- Content pools (edit as you like) ----
$mediums = ['3D Prop','3D Character','Environment Concept Art','Logo Design','UI/Web Asset Set','Marketing Banner Set','Motion Graphics (Video)','Game Asset Pack','Texture/Material Study','Product Render','VFX Shot','Animation Loop','Isometric Illustration','Poster/Key Art'];
$subjectAdj = ['bioluminescent','weathered','modular','stylized','photoreal','ceremonial','retro-futuristic','minimalist','noir','neon-lit','ornate','industrial','eco-friendly','compact','collapsible'];
$subjectNouns = ['vending machine','ancient gate','courier drone','desert city','forest shrine','chef character','space helmet','retro console','alchemical lab','battle mech','street food cart','sailing airship','robot companion','arcane library','market stall','security camera'];
$constraints = ['two colorways','three variations','mobile and desktop sizes','PBR textures only','looping under 6 seconds','≤ 2k tris per asset','AO + normal + roughness maps','hand-painted style','subsurface scattering','animated reveal','print-ready CMYK mockup','procedural materials only'];

// ---- Time & picks ----
$now = new DateTimeImmutable('now', $tz);
try {
    [$weekday,$hour,$minute] = aj_parseSchedule($scheduleString);

    // This week's jam (or next if the previous one just ended)
    // Subtract the duration so a jam currently in progress is recognized
    $jamStart = aj_nextOccurrence($now->sub($jamDuration),$weekday,$hour,$minute,$tz);
    $jamEnd   = $jamStart->add($jamDuration);
    $unlockAt = $jamStart->sub($subjectUnlockLead);

    // Last week (the previous occurrence of the same weekday/time)
    $prevJamStart = $jamStart->modify('-1 week');
    $prevJamEnd   = $prevJamStart->add($jamDuration);

    // Seeds
    $seedPrev = aj_weekSeed($prevJamStart);
    $seedThis = aj_weekSeed($jamStart);
    $seedNext = aj_weekSeed($jamStart->modify('+1 week'));

    // Picks
    $prevMedium     = aj_pickStable($mediums,$seedPrev,'medium');
    $prevSubject    = aj_combineStable($subjectAdj,$subjectNouns,$seedPrev,'subject');
    $prevConstraint = aj_pickStable($constraints,$seedPrev,'constraint');

    $thisMedium     = aj_pickStable($mediums,$seedThis,'medium');
    $thisSubject    = aj_combineStable($subjectAdj,$subjectNouns,$seedThis,'subject');
    $thisConstraint = aj_pickStable($constraints,$seedThis,'constraint');

    $nextMedium     = aj_pickStable($mediums,$seedNext,'medium');

    // Lock status for *this* week only
    $subjectLocked = $now < $unlockAt;
    $countToStart  = max(0,$jamStart->getTimestamp() - $now->getTimestamp());
    $countToUnlock = max(0,$unlockAt->getTimestamp() - $now->getTimestamp());
} catch (Exception $e) { $ajError = $e->getMessage(); }

?>

<!DOCTYPE html>
<html lang="en">


<?php require("php-components/base-page-head.php"); ?>

<body class="bg-body-secondary container p-0">
<style>
        .winner-announcement {
    margin-bottom: 30px;
}

.winner-title {
    font-size: 2.5rem;
    color: #4CAF50;
}

.text-highlight {
    color: #f77f00;
    font-weight: bold;
}

.countdown-timer h2 {
    margin-bottom: 20px;
}

.timer {
    display: flex;
    justify-content: center;
}

.time-segment span, .time-segment small {
    display: block;
    color: #333;
}

.time-segment span {
    font-size: 2rem;
    font-weight: bold;
}

.time-segment small {
    font-size: 1rem;
    color: #555;
}

.raffle-jar img {
    max-width: 100%;
    height: auto;
    margin-top: 2rem;
}

/* Base styles */
.time-segment {
    border-radius: 10px;
    padding: 1rem;
    margin: 0.5rem;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    flex: 1 0 20%; /* Adjust the basis to 20% for each segment */
    text-align: center; /* Center text alignment */
}

.time-segment span, .time-segment small {
    display: block;
    color: #333;
}

.time-segment span {
    font-size: 2rem;
    font-weight: bold;
}

.time-segment small {
    font-size: 1rem;
    color: #555;
}

/* Responsive adjustments */
@media (max-width: 767px) {
    .timer {
        flex-wrap: wrap; /* Allow the timer to wrap on small screens */
    }

    .time-segment {
        /*flex: 1 0 40%; /* Increase the basis to 40% for better spacing */
        margin-bottom: 10px; /* Add more space between the rows */
    }

    /* Adjust font size for smaller screens */
    .time-segment span {
        font-size: 1.5rem;
    }

    .time-segment small {
        font-size: 0.8rem;
    }
}

@media (max-width: 400px) {
    .time-segment {
        /*flex: 1 0 100%; /* Each segment takes full width */
    }

    /* Further adjust font size for very small screens */
    .time-segment span {
        font-size: 1.2rem;
    }
}

.winner-title {
    margin-bottom: 20px; /* Adds space between the title and the player card */
    font-size: 2.5rem;
}
.animate-raffle-jar {
    animation: pulse;
    animation-duration: 1s;
}
.animate-winner {
    animation: tada;
    animation-duration: 1s;
}

#aj-theme {
    background-color: #fff3cd;
    border: 2px dashed #f77f00;
    padding: 1rem;
    border-radius: 0.5rem;
    text-align: center;
}

#aj-theme-banner.theme-reveal {
    animation: fadeIn 0.5s ease-in-out;
}

#aj-theme.theme-reveal {
    animation: fadeIn 0.5s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}

    </style>
    <?php
    
    require("php-components/base-page-components.php");

    require("php-components/ad-carousel.php");

    

    ?>

    

    <!--MAIN CONTENT-->
    <main class="container pt-3 bg-body" style="margin-bottom: 56px;">
        <div class="row">
            <div class="col-12 col-xl-9">
                
                
                <?php 
                
                
                $activePageName = "Weekly Art Jams!";
                require("php-components/base-page-breadcrumbs.php");
                ?>

<?php if (!isset($ajError)): ?>

<!-- ART JAM: Themed like raffle page -->
<div class="row mt-3">
  <div class="col-12">
    <div class="card mb-3">
      <div class="bg-ranked-1 card-body">
        <div class="d-flex align-items-center">
          <h3 class="mb-0">Weekly Art Jam — <?= htmlspecialchars($scheduleString) ?> (America/São_Paulo)</h3>
          <div class="ms-auto text-end">
            <div class="small">Next Jam:</div>
            <div class="fw-semibold"><?= $jamStart->format('l, F j, Y \a\t H:i') ?>–<?= $jamEnd->format('H:i') ?> BRT</div>
            <div class="text-muted">Starts in <?= aj_humanInterval($countToStart) ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Countdown (raffle-style segments) -->
  <div class="col-12">
    <div class="card mb-3">
      <div class="card-body">
        <div class="countdown-timer my-2">
        <h4 id="aj-title" class="mb-3">Countdown to Jam Start</h4>

          <div id="aj-countdown" class="timer d-flex flex-wrap justify-content-center my-3">
            <div class="bg-ranked-1 time-segment col-6 col-md-3"><span id="aj-days"></span><small>Days</small></div>
            <div class="bg-ranked-1 time-segment col-6 col-md-3"><span id="aj-hours"></span><small>Hours</small></div>
            <div class="bg-ranked-1 time-segment col-6 col-md-3"><span id="aj-minutes"></span><small>Minutes</small></div>
            <div class="bg-ranked-1 time-segment col-6 col-md-3"><span id="aj-seconds"></span><small>Seconds</small></div>
          </div>
          <div class="text-muted small">
            Subject unlocks at <strong><?= $unlockAt->format('H:i') ?> BRT</strong>
            <span id="aj-hint" class="ms-1"></span>
            <?php if (!empty($subjectLocked)): ?> (in <?= aj_humanInterval($countToUnlock) ?>)<?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php if ($now >= $jamStart && $now < $jamEnd): ?>
    <div id="aj-theme-banner" class="alert alert-warning text-center d-none fs-4" role="alert">
      <div class="fw-bold">Medium: <?= htmlspecialchars($thisMedium ?? '') ?> &mdash; Subject: <?= htmlspecialchars($thisSubject ?? '') ?></div>
      <?php if (!empty($thisConstraint)): ?>
        <div class="small">Constraint: <?= htmlspecialchars($thisConstraint ?? '') ?></div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- Last Week / This Week / Next Week -->
  <div class="col-12 col-lg-4">
    <div class="card shadow-sm h-100">
      <div class="card-header">
        <h5 class="mb-0">Last Week</h5>
        <div class="small text-muted"><?= $prevJamStart->format('D, M j, Y H:i') ?>–<?= $prevJamEnd->format('H:i') ?> BRT</div>
      </div>
      <div class="card-body">
        <div class="mb-2">
          <div class="text-uppercase text-muted small">Medium</div>
          <div class="fs-6 fw-semibold"><?= htmlspecialchars($prevMedium ?? '') ?></div>
        </div>
        <div class="mb-2">
          <div class="text-uppercase text-muted small">Subject</div>
          <div class="fw-semibold"><?= htmlspecialchars($prevSubject ?? '') ?></div>
          <div class="text-muted small">Constraint: <?= htmlspecialchars($prevConstraint ?? '') ?></div>
        </div>
        <!-- Optional: attach links or uploads for last week's submissions -->
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-4">
    <div class="card shadow-sm h-100">
      <div class="card-header">
        <h5 class="mb-0">This Week</h5>
        <div class="small text-muted"><?= $jamStart->format('D, M j, Y H:i') ?>–<?= $jamEnd->format('H:i') ?> BRT</div>
      </div>
      <div class="card-body">
        <div class="mb-2">
          <div class="text-uppercase text-muted small">Medium</div>
          <div class="fs-6 fw-semibold"><?= htmlspecialchars($thisMedium ?? '') ?></div>
        </div>
        <div class="mb-2">
          <div class="text-uppercase text-muted small">Subject</div>
          <?php if (!empty($subjectLocked)): ?>
            <span class="badge text-bg-secondary mb-1">Locked</span>
            <div class="fw-semibold">Subject appears 5 minutes before start.</div>
          <?php else: ?>
            <div id="aj-theme" class="d-none">
              <div class="fw-semibold"><?= htmlspecialchars($thisSubject ?? '') ?></div>
              <div class="text-muted small">Constraint: <?= htmlspecialchars($thisConstraint ?? '') ?></div>
            </div>
          <?php endif; ?>
        </div>
        <div class="alert alert-info mt-3 mb-0">
          Deliverables: hourly progress screenshots (1h & 2h) + final render/video + short self-critique.
        </div>
      </div>
      <div class="card-footer text-muted small">
        Current time: <?= $now->format('l, F j, Y \a\t H:i:s') ?> BRT
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-4">
    <div class="card shadow-sm h-100">
      <div class="card-header">
        <h5 class="mb-0">Next Week Preview</h5>
        <div class="small text-muted"><?= $jamStart->modify('+1 week')->format('D, M j, Y H:i') ?>–<?= $jamEnd->modify('+1 week')->format('H:i') ?> BRT</div>
      </div>
      <div class="card-body">
        <div class="text-uppercase text-muted small">Medium (Next Week)</div>
        <div class="fs-6 fw-semibold"><?= htmlspecialchars($nextMedium ?? '') ?></div>
        <div class="text-muted mt-2 small">Picks are deterministic per ISO week.</div>
      </div>
    </div>
  </div>
</div>


<!-- Countdown JS (namespaced IDs) -->
<?php else: ?>
  <div class="alert alert-danger mt-3"><?= htmlspecialchars($ajError) ?></div>
<?php endif; ?>






            </div>
            
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>

    
    <?php require("php-components/base-page-javascript.php"); ?>

    <script>
$(function() {
  const second = 1000, minute = 60*second, hour = 60*minute, day = 24*hour;

  // Use epoch ms from PHP (server is in America/Sao_Paulo)
  const startAt  = <?= $jamStart->getTimestamp()  ?> * 1000;
  const unlockAt = <?= $unlockAt->getTimestamp() ?> * 1000;
  const endAt    = <?= $jamEnd->getTimestamp()    ?> * 1000; // exactly 3 hours after start


  let intervalId = null;


  function phase(now){
    if (now >= endAt)   return 'ended';
    if (now >= startAt) return 'in-progress';
    if (now >= unlockAt)return 'pre-start';
    return 'pre-unlock';
  }

  function nextTargetFor(p){
    if (p === 'pre-unlock') return unlockAt;
    if (p === 'pre-start')  return startAt;
    if (p === 'in-progress')return endAt;
    return null; // ended
  }

  function updateCountdown(toTs){
    const now = Date.now();
    const d = Math.max(0, toTs - now);

    const days = Math.floor(d / day);
    const hours = Math.floor((d % day) / hour);
    const minutes = Math.floor((d % hour) / minute);
    const seconds = Math.floor((d % minute) / second);

    $('#aj-days').text(days);
    $('#aj-hours').text(hours);
    $('#aj-minutes').text(minutes);
    $('#aj-seconds').text(seconds);
  }

  function tick(){
    const now = Date.now();
    const p = phase(now);
    const target = nextTargetFor(p);

    // Swap the title based on phase
      const $title = $('#aj-title');
      const $theme = $('#aj-theme');
      const $banner = $('#aj-theme-banner');
      if (p === 'in-progress') {
        $title.text('Time Remaining in Jam');
        if ($theme.hasClass('d-none')) {
          $theme.removeClass('d-none').addClass('theme-reveal');
        }
        if ($banner.length && $banner.hasClass('d-none')) {
          $banner.removeClass('d-none').addClass('theme-reveal');
          if (!$banner.data('scrolled')) {
            $banner[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            $banner.data('scrolled', true);
          }
        }
      } else if (p === 'ended') {
        $title.text('Jam Ended');
        $theme.addClass('d-none');
        $banner.addClass('d-none');
      } else {
        $title.text('Countdown to Jam Start');
        $theme.addClass('d-none');
        $banner.addClass('d-none');
      }

    // crossed a boundary → hard refresh to reveal new state/subject
    if (!target) {
      updateCountdown(now); // show zeros
      clearInterval(intervalId);
      return;
    }
    if (target - now <= 0) {
      location.replace(location.pathname + location.search);
      return;
    }

    updateCountdown(target);

    // live hint text
    const $hint = $('#aj-hint');
    if (p === 'pre-unlock') {
      const mins = Math.ceil((unlockAt - now)/minute);
      $hint.text(`(in ${mins} min)`);
    } else if (p === 'pre-start') {
      const mins = Math.ceil((startAt - now)/minute);
      $hint.text(`(starts in ${mins} min)`);
    } else if (p === 'in-progress') {
      const minsLeft = Math.ceil((endAt - now)/minute);
      $hint.text(`(ends in ${minsLeft} min)`);
    } else {
      $hint.text('');
    }
  }

  function startTicker(){
    if (intervalId) clearInterval(intervalId);
    tick(); // immediate paint
    intervalId = setInterval(tick, 1000);
  }

  // Pause when tab hidden to save CPU; resume on focus
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      if (intervalId) clearInterval(intervalId);
      intervalId = null;
    } else {
      startTicker();
    }
  });

  startTicker();
});
</script>
</body>

</html>
