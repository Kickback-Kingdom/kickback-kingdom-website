<?php
declare(strict_types=1);

use Kickback\Backend\Controllers\AccountController;
use Kickback\Services\Session;
use Kickback\Common\Version;

?>

<!-- League Viewer Modal -->
<div class="modal fade" id="leaguesModal" tabindex="-1" aria-labelledby="leaguesModalLabel" aria-hidden="true" onclick="toggleLeagueProgression();">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header bg-dark text-white d-none">
                <h5 id="leaguesModal_gameName2" class="modal-title">League Progression</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-0">
                <!-- Carousel Container with Overlay -->
                <div class="position-relative bg-dark">
                    <!-- League Carousel -->
                    <div id="leaguesModal_carouselLeagues" class="carousel slide" aria-live="polite">
                        <div class="carousel-inner">
                            <!-- Preloaded Slides -->
                            <div class="carousel-item active" data-league="Hatchling">
                                <img src="/assets/images/leagues/hatchling2.webp" class="d-block w-100 rounded" alt="Hatchling">
                            </div>

                            <div class="carousel-item" data-league="Wind Rider">
                                <img src="/assets/images/leagues/wind-rider2.webp" class="d-block w-100 rounded" alt="Wind Rider">
                            </div>

                            <div class="carousel-item" data-league="Branch Breaker">
                                <img src="/assets/images/leagues/branch-breaker2.webp" class="d-block w-100 rounded" alt="Branch Breaker">
                            </div>

                            <div class="carousel-item" data-league="Mountain Peak">
                                <img src="/assets/images/leagues/mountain-peak.webp" class="d-block w-100 rounded" alt="Mountain Peak">
                            </div>

                            <div class="carousel-item" data-league="Sky Breaker">
                                <img src="/assets/images/leagues/sky-breaker2.webp" class="d-block w-100 rounded" alt="Sky Breaker">
                            </div>

                            <div class="carousel-item" data-league="Storm Piercer">
                                <img src="/assets/images/leagues/storm-piercer.webp" class="d-block w-100 rounded" alt="Storm Piercer">
                            </div>

                            <div class="carousel-item" data-league="Twilight">
                                <img src="/assets/images/leagues/twilight.webp" class="d-block w-100 rounded" alt="Twilight">
                            </div>

                            <div class="carousel-item" data-league="Legends of Kicsi">
                                <img src="/assets/images/leagues/legends-of-kicsi.webp" class="d-block w-100 rounded" alt="Legends of Kicsi">
                            </div>

                        </div>
                    </div>

                    <!-- Overlay Content -->
                    <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center p-4">
                        <!-- Game Icon (Left) -->
                        <div class="col-3 d-lg-block d-md-none d-none me-4">
                            <img id="leaguesModal_gameIcon" src="" class="rounded border border-light shadow img-fluid">
                        </div>

                        <!-- Details (Right) -->
                        <div class="flex-grow-1">
                            <!-- Row with Second Image, Game Name, and ELO Info -->
                            <div class="d-flex align-items-center mb-3">
                                <!-- Second Image -->
                                <div class="col-2 d-block d-lg-none me-3">
                                    <img id="leaguesModal_gameIcon2" src="/assets/media/games/second-image.png" class="rounded border border-light shadow img-fluid">
                                </div>

                                <!-- Game Name and ELO Info -->
                                <div class="flex-grow-1">
                                    <!-- Game Name -->
                                    <h2 id="leaguesModal_gameName" class="text-white fw-bold mb-1 text-shadow">Game Name</h2>

                                    <!-- League Name, Current ELO, and ELO Gained -->
                                    <div class="d-flex justify-content-between align-items-center">
                                        <!-- League Name -->
                                        <p id="leaguesModal_leagueName" class="d-lg-block d-none fs-5 fw-bold mb-0 text-shadow text-white">
                                            <i class="fa-solid fa-trophy me-2"></i>
                                            <span id="leaguesModal_leagueTitle">League Name</span>
                                        </p>

                                        <!-- ELO Info -->
                                        <div class="text-end">
                                            <span id="leaguesModal_eloGained" class="badge bg-success p-2 fs-6 shadow">+20</span>
                                            <span id="leaguesModal_currentElo" class="badge bg-ranked-1 p-2 fs-6 shadow">1520</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="progress shadow" style="height: 30px; background-color: rgba(0, 0, 0, 0.5);">
                                <div id="leaguesModal_eloProgressBar" class="progress-bar bg-warning progress-bar-striped progress-bar-animated" style="width: 0%;"></div>
                            </div>

                            <h2 id="leaguesModal_leagueName2" class="d-lg-none d-md-block fw-bold mt-3 text-center text-shadow text-white">Civilization 6</h2>
                        </div>
                    </div>



                </div>

            </div>

            <!-- Modal Footer with Lore -->
            <div class="modal-footer bg-dark">
                <blockquote class="blockquote text-white w-100 text-center" style="margin: auto;">
                    <p id="leaguesModal_loreText" class="mb-0 fs-5" style="line-height: 1.6; font-size: 1.2rem; text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.8);">
                        <span class="fs-1 fw-bold">&ldquo;</span>
                        Explore the rich history and tales of this league!
                        <span class="fs-1 fw-bold">&rdquo;</span>
                    </p>
                </blockquote>
            </div>

        </div>
    </div>
</div>

<?php

$eloChangeResp = AccountController::getChangedEloRatings(Session::getCurrentAccount());
$eloChanges = $eloChangeResp->success ? json_encode($eloChangeResp->data) : '[]';
?>



<script>
    const leagues = [
                        {
                            "name": "Hatchling",
                            "max": 1599
                        },
                        {
                            "name": "Wind Rider",
                            "max": 1799
                        },
                        {
                            "name": "Branch Breaker",
                            "max": 1999
                        },
                        {
                            "name": "Mountain Peak",
                            "max": 2199
                        },
                        {
                            "name": "Sky Breaker",
                            "max": 2399
                        },
                        {
                            "name": "Storm Piercer",
                            "max": 2599
                        },
                        {
                            "name": "Twilight",
                            "max": 2799
                        },
                        {
                            "name": "Legends of Kicsi",
                            "max": 2147483647
                        }
                    ];

  const eloChanges = <?php echo $eloChanges; ?>;

  const eloPerSecond = 30;
  let currentGameIndex = 0;
  let eloInterval = null; // Global variable to store the current animation interval

function showNextEloProgress() {
  if (currentGameIndex >= eloChanges.length) return;

  const game = eloChanges[currentGameIndex];
  const modal = new bootstrap.Modal(document.getElementById("leaguesModal"));
  initializeLeagueProgressionModal(game);
  modal.show();

  // Clean up when the modal is hidden
  document.getElementById("leaguesModal").addEventListener("hidden.bs.modal", () => {
    clearInterval(eloInterval); // Clear any ongoing animation
    eloInterval = null; // Reset interval reference
    kkAPIUpdateLastELOSeen(currentGameIndex);
    currentGameIndex++;
    showNextEloProgress(); // Show the next game
  }, { once: true });
}

function kkAPIUpdateLastELOSeen(currentGameIndex)
{
    var eloChangeToUpdate = eloChanges[currentGameIndex];

    console.log(eloChangeToUpdate);

    CloseLeagueProgressionAPI(eloChangeToUpdate.gameId);//this needs figured out
}

function toggleLeagueProgression() {
    $("#leaguesModal").modal("hide");
}

function initializeLeagueProgressionModal(game) {
    const progressBar = document.getElementById("leaguesModal_eloProgressBar");
    const gameIcon = document.getElementById("leaguesModal_gameIcon");
    const gameIcon2 = document.getElementById("leaguesModal_gameIcon2");
    const gameName = document.getElementById("leaguesModal_gameName");
    const leagueName = document.getElementById("leaguesModal_leagueName");
    const leagueName2 = document.getElementById("leaguesModal_leagueName2");
    const eloGainedElement = document.getElementById("leaguesModal_eloGained");
    const currentEloElement = document.getElementById("leaguesModal_currentElo");

    // Set Game Details
    gameIcon.src = game.gameIcon;
    gameIcon2.src = game.gameIcon;
    gameName.textContent = game.gameName;

    let currentElo = game.previousElo;
    const targetElo = game.currentElo;
    const eloGained = targetElo - currentElo;

    let currentLeagueIndex = leagues.findIndex(league => currentElo <= league.max);
    let leagueMin = currentLeagueIndex === 0 ? 0 : leagues[currentLeagueIndex - 1].max;
    let leagueMax = leagues[currentLeagueIndex].max;



    if (currentLeagueIndex === leagues.length - 1) {
        // Last league: Set the progress bar to full and skip animation
        progressBar.style.transition = "none"; // Remove animation
        progressBar.style.width = "100%";      // Force full width
        progressBar.innerHTML = `<strong>${Math.round(currentElo)} ELO</strong>`; // Display ELO

        leagueName.innerHTML = `<i class="fa-sharp fa-solid fa-trophy-star me-2"></i> <strong>${leagues[currentLeagueIndex].name} League</strong>`;
        leagueName2.innerHTML = `<i class="fa-sharp fa-solid fa-trophy-star me-2"></i> <strong>${leagues[currentLeagueIndex].name} League</strong>`;
        eloGainedElement.textContent = `${eloGained > 0 ? "+" : ""}${eloGained}`;
        currentEloElement.textContent = `${Math.round(currentElo)}`;
        return; // Exit the function, as no animation is needed
    }

    // Teleport to the correct league
    updateLeagueProgressionCarouselSlide(currentLeagueIndex, true);
    updateLeagueDetails(currentElo, leagueMin, leagueMax, progressBar, eloGainedElement, leagueName, currentEloElement);

    const delayBeforeAnimation = 1000; // Delay in milliseconds (e.g., 1000ms = 1 second)
    setTimeout(() => {
        animateLeagueProgress();
    }, delayBeforeAnimation);

    function animateLeagueProgress() {
        const totalDuration = Math.abs(eloGained) / eloPerSecond * 1000; // Calculate duration
        const frameRate = 20;
        const totalSteps = totalDuration / frameRate;
        const increment = (targetElo - currentElo) / totalSteps;

        let step = 0;

        // Clear any existing animation
        if (eloInterval) {
            clearInterval(eloInterval);
        }

        // Start a new interval
        eloInterval = setInterval(() => {
            currentElo += increment;
        
            step++;

            // Prevent overshooting targetElo
            if ((eloGained > 0 && currentElo >= targetElo) || (eloGained < 0 && currentElo <= targetElo)) {
                currentElo = targetElo;
            }

            // Update progress bar and league details
            let progress;
            if (currentLeagueIndex === leagues.length - 1) {
                // If in the last league, always set the progress bar to 100%
                progress = 100;
            } else {
                // Calculate progress for other leagues
                progress = Math.min(((currentElo - leagueMin) / (leagueMax - leagueMin)) * 100, 100);
            }

            progressBar.style.width = `${progress}%`;
            progressBar.style.transition = "none"; // Remove animation


            eloGainedElement.classList.remove('bg-success', 'bg-danger');
            eloGainedElement.classList.add(eloGained < 0 ? 'bg-danger' : 'bg-success');
            leagueName.innerHTML = `<i class="fa-sharp fa-solid fa-trophy-star me-2"></i> <strong>${leagues[currentLeagueIndex].name} League</strong>`;
            leagueName2.innerHTML = `<i class="fa-sharp fa-solid fa-trophy-star me-2"></i> <strong>${leagues[currentLeagueIndex].name} League</strong>`;
            eloGainedElement.innerHTML = `${eloGained > 0 ? "+" : ""}${eloGained} <i class="fa-solid fa-feather"></i>`;
            currentEloElement.innerHTML = `${Math.round(currentElo)} <i class="fa-solid fa-feather"></i>`;


            // League Transition (Upward or Downward)
            if ((currentElo >= leagueMax && eloGained > 0 && currentLeagueIndex < leagues.length - 1) || (currentElo <= leagueMin && eloGained < 0 && currentLeagueIndex > 0)) 
            {
                if (progressBar.style.width === "100%") { // Check if bar is full
                    currentLeagueIndex += (eloGained > 0 ? 1 : -1); // Move to the next/previous league
                    leagueMin = currentLeagueIndex === 0 ? 0 : leagues[currentLeagueIndex - 1].max;
                    leagueMax = leagues[currentLeagueIndex].max;
                    
                    const isLastLeague = currentLeagueIndex === leagues.length - 1;
                    resetProgressBar(currentLeagueIndex, isLastLeague);


                    updateLeagueProgressionCarouselSlide(currentLeagueIndex);
                }
            }

            // Stop animation when done
            if (step >= totalSteps || currentElo === targetElo) {
                clearInterval(eloInterval);
                eloInterval = null;
            }
        }, frameRate);
    }

    function updateLeagueDetails(currentElo, leagueMin, leagueMax, progressBar, eloGainedElement, leagueName, currentEloElement)
    {
            // Update progress bar and league details
            let progress;
            if (currentLeagueIndex === leagues.length - 1) {
                // If in the last league, always set the progress bar to 100%
                progress = 100;
            } else {
                // Calculate progress for other leagues
                progress = Math.min(((currentElo - leagueMin) / (leagueMax - leagueMin)) * 100, 100);
            }

            progressBar.style.width = `${progress}%`;
            progressBar.style.transition = "none"; // Remove animation


            
            eloGainedElement.classList.remove('bg-success', 'bg-danger');
            eloGainedElement.classList.add(eloGained < 0 ? 'bg-danger' : 'bg-success');
            leagueName.innerHTML = `<i class="fa-sharp fa-solid fa-trophy-star me-2"></i> <strong>${leagues[currentLeagueIndex].name} League</strong>`;
            leagueName2.innerHTML = `<i class="fa-sharp fa-solid fa-trophy-star me-2"></i> <strong>${leagues[currentLeagueIndex].name} League</strong>`;
            eloGainedElement.innerHTML = `${eloGained > 0 ? "+" : ""}${eloGained} <i class="fa-solid fa-feather"></i>`;
            currentEloElement.innerHTML = `${Math.round(currentElo)} <i class="fa-solid fa-feather"></i>`;
    }

    function resetProgressBar(currentLeagueIndex, isLastLeague) {
        progressBar.style.transition = "none";
        if (isLastLeague) {
            progressBar.style.width = "100%"; // Set to full if it's the last league
        } else {
            progressBar.style.width = "0%"; // Reset to empty for other leagues
        }
        progressBar.offsetWidth; // Trigger reflow
    }

}

function CloseLeagueProgressionAPI(gameId) {
    const data = {
        gameId: gameId,
        accountId: <?php echo json_encode(Kickback\Services\Session::getCurrentAccount()->crand); ?>,
        sessionToken: <?php echo json_encode($_SESSION["sessionToken"]); ?>
    };

    // Create a URLSearchParams object for encoding the form data
    const params = new URLSearchParams(Object.entries(data));

    fetch('<?= Version::formatUrl("/api/v1/league/closeProgression.php?json"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: params
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json(); // Expect JSON response
    })
    .then(data => {
        if (data.success) {
            console.log("League progression successfully closed:", data);
        } else {
            console.error("Failed to close league progression:", data.message);
        }
    })
    .catch(error => {
        console.error("Error occurred during API call:", error);
    });
}


const passarokiLeagueLore = {
    "genericLore": [
        "The Pássaroki soar through life as they do the skies—undaunted by the vast unknown, guided by the wisdom of their Ancestors and the celestial glow of the moon Kicsi.",
        "Under the eternal dance of the moon phases, the Pássaroki find balance and strength, their spirits as untamed as the winds that carry them.",
        "To the Pássaroki, life is a flight, each league a new altitude, and every challenge an updraft propelling them to greatness.",
        "The wisdom of the ancient spirits, the Terrosi, whispers through the sacred groves, reminding the followers of the old Pássaroki faith of their bond to the natural and spiritual worlds.",
        "The phases of Kicsi, the moon, not only mark time and destiny but also inspire a cherished tradition, with each Pássaroki's middle name reflecting the phase of the moon on the day they are born.",
        "The Pássaroki culture thrives on friendly competition, where rivals inspire each other to achieve greatness and new heights",
        "From the sacred groves to the archives of Kickback Kingdom on planet Ostrinus, the Pássaroki spirit of excellence and competition has resonated throughout the Atlas Star System.",
        "Friendly rivalries fuel progress, as Pássaroki values inspire others to chase their dreams and become their best.",
        "For the Pássaroki, rivalry is a dance of mutual respect, where the winds of one's triumph lift others higher, creating a cycle of shared greatness.",
        "On the planet Magyarion, the Pássaroki embrace harmony with nature, seeing planets and moons as living entities. The moon Kicsi, a symbol of life's unity, holds a sacred place in their hearts.",
        "Aarok's return from Kicsi sparked awe and skepticism. His tale of meeting Ancestor spirits on the moon inspired a renewed belief in the limitless potential of the Pássaroki spirit.",
        "Among the Pássaroki, non-flyers like Dumont are celebrated for their ingenuity. Dumont dreams of building a machine to help all Pássaroki reach the skies.",
        "Among the Pássaroki, non-flyers are valued for their creativity and resilience. Though they cannot take to the skies, they contribute through innovation and resourcefulness, embodying the spirit of reaching new heights in their own unique ways.",
        "Lunar middle names among the Pássaroki honor Kicsi's phase at birth, symbolizing a cosmic bond. Each name reflects the unique alignment and traits associated with that moment, connecting the individual to Kicsi.",
        "The Pássaroki, though deeply spiritual, embrace the faiths of others, seeing wisdom across the Atlas Star System. Their openness fosters understanding among its diverse peoples.",
        "Aarok's tale of Ancestors on Kicsi anchors modern Pássaroki spirituality, inspiring pilgrims to gaze skyward in search of their guiding spirits.",
        "Guided by nature yet open to innovation, the Pássaroki honor tradition while embracing progress, blending wisdom with visionary pursuits."
    ],
    "leagueLore": [
        [
            "The Hatchling League celebrates the first steps of the journey, where fledgling wings are not yet ready to fly but dreams of the skies take root.",
            "Every champion begins as a hatchling, grounded yet full of potential, their gaze fixed upward toward the boundless horizon.",
            "In the Hatchling League, the world is a place of discovery, where curiosity fuels the courage to one day take flight.",
            "The Hatchling League symbolizes new beginnings, a time of learning and growth, where small steps lay the foundation for first flight.",
            "Here, the skies remain a distant promise, and the gentle winds whisper encouragement, nurturing the hope of one day embracing the skies."
        ],
        [
            "In the Wind Rider League, mastery of the skies begins with understanding the whispers of the wind.",
            "The Wind Rider League celebrates the first triumphant moments of flight, where fledgling wings catch the wind and lift off the ground for the first time.",
            "In the Wind Rider League, the skies become a partner, their gentle currents teaching the art of balance and trust.",
            "This league is a time of exhilaration, where every flight feels like a victory, and the winds themselves seem to cheer for the daring.",
            "The Wind Rider League marks the transition from grounded dreams to airborne reality, where champions learn to let the wind guide and carry them.",
            "Here, the skies are no longer a distant promise but a newfound freedom, a playground where the Pássaroki take their first soaring steps toward greatness."
        ],
        [
            "The Branch Breaker League celebrates the moment when fledgling wings gain the strength to defy the wind and break through the treetops into the open skies.",
            "In this league, champions embrace the challenge of resistance, mastering the winds and shattering barriers with determination and resolve.",
            "The Branch Breaker League honors those who face the winds head-on, breaking through the branches and treetops to claim their first taste of the open skies, a true testament to their growing strength and resolve.",
            "Here, the skies are earned through determination, with every branch broken symbolizing the Pássaroki spirit of resilience and growth.",
            "To be a Branch Breaker is to claim the open skies with power and purpose, leaving the shelter of the forest for the freedom of the vast horizon."
        ],
        [
            "The Mountain Peak League challenges the Pássaroki to master the high altitudes, where thin air tests their endurance and resolve.",
            "Flying above the jagged peaks, the Pássaroki prove their resilience, embracing the fierce winds that demand unwavering determination.",
            "In the Mountain Peak League, each flight through the rarefied air is a testament to the strength and spirit of those who dare to ascend.",
            "Here, the thin air and unforgiving heights push the Pássaroki to their limits, forging champions of unparalleled fortitude.",
            "The Mountain Peak League celebrates those who rise above, their wings strong enough to conquer the highest skies and boldest challenges.",
            "The Mountain Peak League tests the Pássaroki in the freezing winds and thin air of the highest altitudes, where resilience is key to survival.",
            "Flying above snow-capped peaks, the Pássaroki endure the biting cold, proving their strength and determination in the face of nature's harshest challenges.",
            "In the Mountain Peak League, the air is thin, the winds fierce, and the cold relentless, forging champions with unmatched endurance and spirit.",
            "Here, the icy gales and frosted heights push the Pássaroki to adapt, their wings cutting through the chill as they ascend to greatness.",
            "The Mountain Peak League celebrates those who brave the cold and conquer the heights, their persistence as unyielding as the mountains themselves."
        ],
        [
            "The Sky Breaker League celebrates the Pássaroki who rise above the clouds, where the heavens open to their fearless wings.",
            "In the Sky Breaker League, the clouds part beneath their wings, and the endless skies become their new domain.",
            "Here, the Pássaroki overcome the last barriers of the atmosphere, their boldness rewarded with the infinite freedom of the open heavens.",
            "Sky Breakers are the masters of the unseen winds, pushing beyond the known skies to discover what lies in the vast blue and beyond."
        ],
        [
            "The Storm Piercer League celebrates the fearless Pássaroki who conquer the chaos of storm clouds, flying through tempests as if they were mere whispers of wind.",
            "To be a Storm Piercer is to embrace the fury of the storm, navigating its violent winds with unshaken resolve and unmatched mastery.",
            "In the heart of the storm, where thunder roars and lightning dances, the Piercers soar, their wings unyielding against nature's might.",
            "Storm Piercers rise above the darkest clouds, proving that no force of nature can ground the indomitable spirit of the Pássaroki.",
            "This league honors those who transform chaos into opportunity, finding serenity and strength within the storm's relentless embrace."
        ],
        [
            "The Twilight League is a realm of rarity and wonder, where only the most extraordinary Pássaroki soar, between the highest clouds and the moon Kicsi itself.",
            "In this ethereal expanse, twilight reigns eternal, a delicate balance of shadow and light that tests the skill and spirit of every flier.",
            "Twilight champions embrace the thin air and profound quiet, their wings carrying them closer to Kicsi's celestial glow.",
            "Aarok was the first to reach these heights, paving the way for the boldest of Pássaroki to follow in his legendary path."
        ],
        [
            "The Legends of Kicsi League represents the ultimate achievement, where only the greatest Pássaroki ascend to the sacred moon, home to their honored Ancestors.",
            "Here, Aarok became the first living Pássaroki to set foot on Kicsi, returning to Magyarion with tales of spirits and the timeless wisdom they imparted.",
            "Temples dedicated to ancestral heroes stand in serene reverence, their halls echoing with the stories of those who shaped Pássaroki destiny.",
            "To join the Legends of Kicsi is to transcend mortality, earning a place among the eternal guardians of their people's legacy."
        ]
    ]
};

function getRandomPassarokiLeagueLore(leagueIndex) {
    const specificLore = passarokiLeagueLore.leagueLore[leagueIndex] || [];
    const allLore = [...specificLore, ...passarokiLeagueLore.genericLore];

    if (allLore.length === 0) {
        return "This league has no lore yet—be the first to make history!";
    }

    const randomIndex = Math.floor(Math.random() * allLore.length);
    return allLore[randomIndex];
}


function updateLeagueProgressionCarouselSlide(targetIndex, immediate = false) {
    const carouselElement = document.getElementById("leaguesModal_carouselLeagues");
    const carousel = bootstrap.Carousel.getInstance(carouselElement) || new bootstrap.Carousel(carouselElement);

    const activeIndex = [...carouselElement.querySelectorAll(".carousel-item")].findIndex(slide => slide.classList.contains("active"));


    
    const loreText = document.getElementById("leaguesModal_loreText");


    const loreContent = getRandomPassarokiLeagueLore(targetIndex);//passarokiLeagueLore.genericLore[15];//getRandomPassarokiLeagueLore(targetIndex);
    loreText.innerHTML = `<i class="fa-solid fa-quote-left"></i> ${loreContent} <i class="fa-solid fa-quote-right"></i>`;

    if (immediate) {
        carouselElement.querySelectorAll(".carousel-item").forEach((slide, index) => {
            slide.classList.toggle("active", index === targetIndex);
        });
    } else if (activeIndex !== targetIndex) {
        const direction = targetIndex > activeIndex ? 1 : -1;
        let currentIndex = activeIndex;

        const slideInterval = setInterval(() => {
            currentIndex += direction;
            carousel.to(currentIndex);

            if (currentIndex === targetIndex) {
                clearInterval(slideInterval);
            }
        }, 300);
    }
}



</script>