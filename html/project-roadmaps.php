<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");
?>

<!DOCTYPE html>
<html lang="en">


<?php require("php-components/base-page-head.php"); ?>

<body class="bg-body-secondary container p-0">
    
    <?php 
    
    require("php-components/base-page-components.php"); 
    
    require("php-components/ad-carousel.php"); 
    
    ?>

    <style>
.timeline {
    position: relative;
    padding-left: 30px; /* Space for the line and date */
    margin-top: 25px;
}

.timeline::before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    width: 2px;
    background-color: #ddd;
    left: 10px;  /* Positioned more to the left */
}

.timeline-date {
    background-color: #ddd;
    padding: 5px 10px;
    margin-bottom: 10px;
}


.timeline-content {
    background-color: #fff;
    padding: 15px;
    box-shadow: 0 0 5px rgba(0,0,0,0.2);
    margin-bottom: 20px;  /* Spacing between each event */
}

.timeline-item:last-child .timeline-content {
    margin-bottom: 0; /* To remove margin from the last event */
}

    </style>

    <!--MAIN CONTENT-->
    <main class="container pt-3 bg-body" style="margin-bottom: 56px;">
        <div class="row">
            <div class="col-12 col-xl-9">
                
                
                <?php 
                
                
                $activePageName = "Project Roadmaps";
                require("php-components/base-page-breadcrumbs.php"); 
                
                ?>
                <h1>Grand Strategy</h1>
<p><strong>Goal:</strong> To cement Kickback Kingdom as a pioneering, community-driven gaming realm where members not only play but also collaborate, create, learn, and earn. Our objective is to cultivate a self-sustaining ecosystem where every gamer, creator, and enthusiast becomes a stakeholder, empowered to shape the Kingdom's future, from game development to community events. Through guilds and collaborative opportunities, we strive to recognize and reward every contribution, ensuring that the spirit of belonging, creation, and leadership thrives in every corner of the Kingdom.</p>

                <ol>
                    <li><strong>Develop Website & Automate Growth</strong>
                        <ul>
                            <li><strong>Phase 1 - Planning:</strong> Define official features, plan out a roadmap timeline, write TOS and consult a lawyer</li>
                            <li><strong>Phase 2 - Build & Test:</strong> Adopt Agile practices for development and engage the community for beta testing. Create social media accounts.</li>
                            <li><strong>Phase 3 - Launch:</strong> Roll out the website and each guild</li>
                            <li><strong>Phase 4 - Grow:</strong> Plan out quests/community events such as raffles and tournaments</li>
                            <li><strong>Goal:</strong> Finished & released website and all legal questions answered</li>
                        </ul>
                    </li>
                    <li><strong>Marketing Campaign for Adventurers' Guild</strong>
                        <ul>
                            <li>Run ads to garner new members for kickback kingdom website.</li>
                            <li>Create Marketing Materials</li>
                            <li><strong>Goal:</strong> Kickback Kingdom is on auto pilot growth with paid ads and social media posts</li>
                        </ul>
                    </li>
                    <li><strong>Create Revenue Generation</strong>
                        <ul>
                            <li>Offer premium assets on the Unity Asset Store. <strong>~$1,000 USD/Monthly</strong></li>
                            <li>Introduce a subscription model for the Merchants' Guild. <strong>~$500 USD/Monthly</strong></li>
                            <li>Design branded merchandise for sale</li>
                            <li>Crowdfunding initiatives.</li>
                            <li>Seek potential investors for financial backing.</li>
                            <li>Monetize engagement on Twitter. <strong>~$500 USD/Monthly</strong></li>
                            <li>Affiliate Marketing</li>
                            <li><strong>Goal:</strong> Accumulate $2,000 USD a month passive income</li>
                        </ul>
                    </li>
                    <li><strong>Hire First Steward</strong>
                        <ul>
                            <li>Create a job posting in the Stewards' Guild</li>
                            <li>Search within the Kingdom and out of the Kingdom for a person suitable to be a Marketing Manager in the Stewards' Guild</li>
                            <li><strong>Goal:</strong> Obtain 1 Marketing Steward</li>
                        </ul>
                    </li>
                    <li><strong>Develop Share Payout System</strong>
                        <ul>
                            <li>Develop a program to handle automatic payments of guild treasuries to the guild share holders in ADA</li>
                            <li><strong>Goal:</strong> Fully automated payout system for guild shares</li>
                        </ul>
                    </li>
                    <li><strong>Expand the Atlas System Lore</strong>
                        <ul>
                            <li>Detail the celestial bodies within the star system.</li>
                            <li>Shape major storylines and detail pivotal characters.</li>
                            <li>Develop and showcase visual art and designs.</li>
                            <li>Initiate a dedicated blog and wiki for lore documentation.</li>
                            <li><strong>Goal:</strong> Published blog and wiki for the lore</li>
                        </ul>
                    </li>
                    <li><strong>Develop Atlas System Game Engine</strong>
                        <ul>
                            <li><strong>Phase 1 - Planning:</strong> Define essential engine capabilities.</li>
                            <li><strong>Phase 2 - Development:</strong> Begin the foundational building of the engine, ensuring modular design for flexibility and scalability.</li>
                            <li><strong>Phase 3 - Stepwise Building:</strong> Construct and validate engine components sequentially, ensuring each module integrates seamlessly with the core.</li>
                            <li><strong>Phase 4 - Community Testing:</strong> Engage the community in testing phases, capturing real-world feedback and user experiences.</li>
                            <li><strong>Phase 5 - Refinement and Bug Fixing:</strong> Utilize community feedback and the established bug reporting mechanism to enhance the engine and resolve issues.</li>
                            <li><strong>Goal:</strong> Fully functioning game engine for our future games</li>
                        </ul>
                    </li>

                    <li><strong>Develop and Release Twilight Racer</strong>
                        <ul>
                            <li>Chart a development schedule highlighting key deliverables.</li>
                            <li>Roll out beta versions to select community members for early feedback.</li>
                            <li>Finalize a sustainable monetization approach.</li>
                            <li>Organize marketing and promotional events.</li>
                            <li><strong>Goal:</strong> Released Version of Twilight Racer</li>
                        </ul>
                    </li>

                    <li><strong>Develop and Release End of Empires</strong>
                        <ul>
                            <li>Chart a development schedule highlighting key deliverables.</li>
                            <li>Roll out beta versions to select community members for early feedback.</li>
                            <li>Finalize a sustainable monetization approach.</li>
                            <li>Organize marketing and promotional events.</li>
                            <li><strong>Goal:</strong> Released Version of End of Empires</li>
                        </ul>
                    </li>

                    <li><strong>Release Kickback Kingdom Game Publishing</strong>
                        <ul>
                            <li>Release L.I.C.H. as first published game</li>
                            <li><strong>Goal:</strong> Have a method for game studios to apply to be published by Kickback Kingdom</li>
                        </ul>
                    </li>

                    <li><strong>Keep releasing new games in Atlas System</strong>
                        <ul>
                            <li>On going</li>
                        </ul>
                    </li>
                </ol>



                <h1>Roadmap</h1>
                <div class="timeline">
                    
                </div>

            </div>
            
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>

    
    <?php require("php-components/base-page-javascript.php"); ?>
    <script>
    // Mocked data, replace this with your AJAX call or data fetch mechanism
    var accounts = {1: "Alibaba", 2: "Fly", 3: "DonFrio"};
var events = [
    {
        group: 'Develop Website & Automate Growth - Phase 1',
        title: 'Kickback Kingdom - Planning',
        description: '',
        checklist: [
            [false, null, "Finish grand strategy planning"],
            [false, null, "Finish roadmap for the grand strategy "],
            [false, null, "Define official features"],
            [false, 2, "Write document about how the guild share payment system will work"],
            [false, 2, "Build presentation for lawyer consultation"],
            [false, 2, "Write TOS for the website/guilds"],
            [false, 2, "Find a lawyer to consult with about legalities"],
            [false, 2, "Find and setup a password manager"],
            [false, 1, "Make a dropdown for each step in the grand strategy in the roadmap"],
            [false, null, "consult with lawyer"],
        ]
    },
    {
        group: 'Develop Website & Automate Growth - Phase 2',
        title: 'Website - Security Audit',
        description: '',
        checklist: [
            [true, 3, "Fix SSL Chain Issue"],
            [false, 1, "Do an entire API Audit"],
        ]
    },
    {
        group: 'Develop Website & Automate Growth - Phase 2',
        title: 'Website - New User Experience',
        description: '',
        checklist: [
            [false, 1, "Create a way to have a Kickback Kingdom account without being in the adventurers guild."],
            [false, 1, "test the sign up process"],
            [false, 1, "test the forgot password feature"],
            [false, 1, "require email verification"],
            [false, 1, "write blog post about new feature"],
        ]
    },
    {
        group: 'Develop Website & Automate Growth - Phase 2',
        title: 'Website - Base Experience',
        description: 'The \'Base Experience\' enhancements focus on refreshing the platform\'s visuals and user feedback cues, introducing new loading animations for the website and account searches, along with unveiling a reimagined website logo.',
        checklist: [
            [false, 2, "new loading animation for base website loading"],
            [false, 2, "Add loading animation when searching for accounts"],
            [false, 2, "new website logo"],
            [false, 2, "finalize website background and flag"],
            [false, 2, "create a banner for the castles page"],
            [false, 2, "create a banner for the schedule page"],
            [false, 2, "create a banner for the town square page"],
            [false, 2, "create a banner for the forums page"],
            [false, 2, "create a banner for the custom lobby (ranked challenges) page"],
            [false, 2, "create a banner for the blogs page"],
            [false, 2, "create a banner for the games page"],
            [false, 2, "create a banner for the guild halls page"],
            [false, 2, "create a banner for the adventurers guild page"],
            [false, 2, "create a banner for the merchants guild page"],
            [false, 2, "create a banner for the craftsmens guild page"],
            [false, 2, "create a banner for the stewards guild page"],
            [false, 2, "create a banner for the apprentices guild page"],
            [false, 2, "create a banner for the admin dashboard page"],
            [false, 2, "create a banner for the home feed page"],
            [false, 2, "review all the images currently uploaded and replace any with the 'Bing' logo"],
            [false, 2, "Identify bad load time areas"],
            [false, 1, "Optimize the identified bad load time areas if they are code based"],
            [false, 2, "Optimize the identified bad load time areas if they are image based"],
        ]
    },
    {
        group: 'Develop Website & Automate Growth - Phase 2',
        title: 'Website - Maintenance Systems',
        description: '',
        checklist: [
            [false, 1, "Create a SQL and File backup system"],
            [false, 1, "Image Reviewer tool to be able to delete and replace images"],
            [true, 1, "A way to push beta to production with a easy way to revert"],
            [false, 1, "write blog post about new feature"],
        ]
    },
    {
        group: 'Develop Website & Automate Growth - Phase 2',
        title: 'Website - Content Editor',
        description: 'The \'Content Editor\' updates focus on refining media integrations, streamlining the user interface, and enhancing user experience. Key changes involve improved media elements, the introduction of GIF support, intuitive toolbars, and smarter image upload processes, all geared towards facilitating seamless content creation.',
        checklist: [
            [false, 1, "Debug media element usability for Hansibaba"],
            [false, 1, "Finish dev on the header element"],
            [false, 1, "Add a GIF feature for media element"],
            [false, 1, "Change add new element button the bottom to be a toolbar"],
            [false, 1, "The plus button should open dropdown"],
            [false, 1, "Photo upload doesnt clear and bring you back to the first step"],
            [false, 1, "After uploading it should auto select the image you just uploaded to be used for image selection"],
            [false, 1, "Highlight the last moved item"],
            [false, 1, "write blog post about new feature"],
        ]
    },
    {
        group: 'Develop Website & Automate Growth - Phase 2',
        title: 'Website - Town Square',
        description: 'A collection of UI/UX enhancements for the \'Town Square\' platform feature. Changes span from visual consistency in player cards to improved search functionalities and tooltip interactions, all designed to refine the user experience.',
        checklist: [
            [true, 1, "Put gray placeholder boxes on game rankings for player cards that dont have 4 stats"],
            [false, 1, "When a user clicks on a badge, open the item modal instead of the tooltip"],
            [true, 1, "Fix tooltip hover for ranking stats"],
            [true, 1, "Guild ribbons on player cards to show accurate information"],
            [true, 1, "Hoving over experience bar doesnt show exp anymore"],
            [true, 1, "Put an icon to the left of the search bar"],
            [true, 1, "Make it so the search bar isnt stretching across the screen."],
            [true, 1, "Make it so that if you aren't logged in you can still use the town square"],
            [true, 1, "Add footer to the page"],
            [false, 1, "write blog post about new feature"],

        ]
    },
    {
        group: 'Develop Website & Automate Growth - Phase 2',
        title: 'Website - Notifications',
        description: '',
        checklist: [
            [false, 1, "when a user logs-in have a popup that says 'you have unclaimed rewards' and then it opens your notifications tab - only if a notification has pending rewards"],
            [false, 1, "email the accounts when they recieve a notification"],
            [false, 1, "Get notified if someone comments on your post in a forum or in a blog or on your quest"],
            [false, 1, "Collect raffle tickets when people review your quests"],
            [false, 1, "Remove prestige from the notifications"],
            [false, 1, "write blog post about new feature"],
        ]
    },
    {
        group: 'Develop Website & Automate Growth - Phase 2',
        title: 'Website - Prestige',
        description: '',
        checklist: [
            [false, 1, "Collect a raffle ticket when you use a prestige token"],
            [false, 1, "use form token to prevent using multiple prestige tokens"],
            [false, 1, "write blog post about new feature"],
        ]
    },
    {
        group: 'Develop Website & Automate Growth - Phase 2',
        title: 'Website - Adventurers\' Guild',
        description: '',
        checklist: [
            [false, 1, "Make it cost 50 raffle tickets to create a quest"],
            [false, 1, "Make it so hosts will receive 1 raffle ticket per star rating on host and quest review per participant"],
            [false, 1, "Allow host to submit quest for review"],
            [false, 1, "Allow admins to published quests after being reviewed"],
            [false, 1, "Allow admins to reject quests with response after being reviewed"],
            [false, 1, "Allow hosts to setup their prizes based on their owned items"],
            [false, 1, "Add comment section to quests"],
            [false, 1, "add proof submission section to quests"],
            [false, 1, "write blog post about new feature"],
        ]
    },
    {
        group: 'Develop Website & Automate Growth - Phase 2',
        title: 'Website - Admin Dashboard',
        description: '',
        checklist: [
            [true, 1, "Finish merchant guild share processing system"],
            [false, 1, "Account management (banning/unbanning)"],
            [false, 1, "write blog post about new feature"],
        ]
    },
    {
        group: 'Develop Website & Automate Growth - Phase 2',
        title: 'Website - Data Analyst Dashboard',
        description: '',
        checklist: [
            [false, 1, "data on user retention rate"],
            [false, 1, "write blog post about new feature"],
        ]
    },
    {
        group: 'Develop Website & Automate Growth - Phase 2',
        title: 'Website - Merchant\'s Guild',
        description: '',
        checklist: [
            [false, 1, "Add subscription plan maker"],
            [false, 1, "write blog post about new feature"],
        ]
    },
    {
        group: 'Develop Website & Automate Growth - Phase 2',
        title: 'Website - Trading',
        description: '',
        checklist: [
            [false, 1, "write blog post about new feature"],
        ]
    },
    {
        group: 'Develop Website & Automate Growth - Phase 2',
        title: 'Website - Forums',
        description: 'Enhancements to the \'Forums\' segment of our platform include foundational features allowing users to create, interact with, and moderate content. This update suite introduces forum creation, post interactions, a dedicated moderator dashboard, and robust moderation tools, ensuring a structured and safe community space.',
        checklist: [
            [false, 1, "Ability to create forums"],
            [false, 1, "Ability to create posts"],
            [false, 1, "Ability to comment on posts"],
            [false, 1, "Moderation can delete a post or comment"],
            [false, 2, "Forum moderator badge"],
            [false, 1, "Users can flag a post for review"],
            [false, 1, "Moderator Dashboard where flagged comments can be seen"],
            [false, 1, "write blog post about new feature"],
        ]
    },
    {
        group: 'Develop Website & Automate Growth - Phase 2',
        title: 'Website - Games Page',
        description: 'Upgrades to the \'Games Page\' provide detailed game listings, individual game insights, ranking tabs, and interactive features like game suggestions. The revamp ensures a more organized and user-friendly gaming directory.',
        checklist: [
            [false, 1, "List all the games Kickback Kingdom supports with searchable options"],
            [false, 1, "Each game has its own page"],
            [false, 1, "Tab to show all the available quests for this game"],
            [false, 1, "Tab for player rankings"],
            [false, 1, "Tab for the gold card member"],
            [false, 1, "History of gold card members"],
            [false, 1, "Tab for information on the game"],
            [false, 1, "Button for landing page if applicable"],
            [false, 1, "Button and modal to suggest a game to be added to kickback kingdom"],
            [false, 1, "write blog post about new feature"],
        ]
    },
    {
        group: 'Develop Website & Automate Growth - Phase 2',
        title: 'Website - Custom Lobby',
        description: 'The \'Custom Lobby\' feature provides hosts with expansive control over game lobbies, from creation to detailed settings like password protection and play style. Players can easily join, chat, select characters, and vote on match outcomes. Post-match, results can be uploaded, disputed, and, if necessary, reviewed by moderators, ensuring fair play and organized matchmaking.',
        checklist: [
            [false, 1, "Host can create lobby"],
            [false, 1, "lobby shows up in a server list"],
            [false, 1, "host can set password to lobby"],
            [false, 1, "host can set lobby name"],
            [false, 1, "host can set lobby to invite only (which will hide from server list)"],
            [false, 1, "host can invite other players (which will show up in notifications)"],
            [false, 1, "players can click a join button on the server list to join the lobby"],
            [false, 1, "host can set lobby style (casual, ranked, hardcore)"],
            [false, 1, "chatbox in lobby to converse with players"],
            [false, 1, "rules list box that host can edit"],
            [false, 1, "players can select their characters (or random)"],
            [false, 1, "ready button to initiate the game and lock the settings"],
            [false, 1, "when the lobby is locked nothing can be edited"],
            [false, 1, "once the lobby is locked a void vote option is available"],
            [false, 1, "if all players vote to void then the lobby match is canceled"],
            [false, 1, "after lobby is locked there is an area to upload results proof, scores, and select player characters if the lobby is ranked"],
            [false, 1, "after all results are submitted or enough type has elapsed the system will trigger a final results screen"],
            [false, 1, "the final results screen will show the results of the match and allow players to dispute them"],
            [false, 1, "if all players aggree then the match is over"],
            [false, 1, "if a player disputes the results then a moderator is called to review the results"],
            [false, 1, "write blog post about new feature"],
        ]
    },
    {
        group: 'Develop Website & Automate Growth - Phase 2',
        title: 'Website - Schedule',
        description: '',
        checklist: [
            [false, 1, "write blog post about new feature"],
        ]
    },
    {
        group: 'Develop Website & Automate Growth - Phase 2',
        title: 'Website - Profile Page',
        description: '',
        checklist: [
            [false, 1, "write blog post about new feature"],
        ]
    },
    {
        group: 'Develop Website & Automate Growth - Phase 2',
        title: 'Website - Guild Halls',
        description: '',
        checklist: [
            [false, 1, "write blog post about new feature"],
        ]
    },
    {
        group: 'Develop Website & Automate Growth - Phase 2',
        title: 'Website - Craftsmen\'s Guild',
        description: '',
        checklist: [
            [false, 1, "write blog post about new feature"],
        ]
    },
    {
        group: 'Develop Website & Automate Growth - Phase 2',
        title: 'Website - Apprentice\'s Guild',
        description: '',
        checklist: [
            [false, 1, "write blog post about new feature"],
        ]
    },
    {
        group: 'Develop Website & Automate Growth - Phase 2',
        title: 'Website - Steward\'s Guild',
        description: '',
        checklist: [
            [false, 1, "write blog post about new feature"],
        ]
    },
    {
        group: 'Develop Website & Automate Growth - Phase 2',
        title: 'Website - Castles Page',
        description: '',
        checklist: [
            [false, 1, "write blog post about new feature"],
        ]
    },
    {
        group: 'Develop Website & Automate Growth - Phase 2',
        title: 'Website - Twilight Racer Landing Page',
        description: '',
        checklist: [
            [false, 1, "write blog post about new feature"],
        ]
    },
    {
        group: 'Develop Website & Automate Growth - Phase 2',
        title: 'Marketing - Social Media',
        description: '',
        checklist: [
            [true, 2, "Create Twitter (X) Account"],
            [false, 1, "write blog post about new feature"],
        ]
    },
    {
        group: 'Develop Website & Automate Growth - Phase 2',
        title: 'Website - Crafting',
        description: '',
        checklist: [
            [false, 1, "write blog post about new feature"],
        ]
    },
    {
        group: 'Develop Website & Automate Growth - Phase 2',
        title: 'Website - Equipment',
        description: '',
        checklist: [
            [false, 1, "write blog post about new feature"],
        ]
    },
    {
        group: 'Develop Website & Automate Growth - Phase 2',
        title: 'Website - Item Forge',
        description: '',
        checklist: [
            [false, 1, "write blog post about new feature"],
        ]
    },
    {
        group: 'Develop Website & Automate Growth - Phase 2',
        title: 'Website - Item Shop',
        description: '',
        checklist: [
            [false, 1, "write blog post about new feature"],
        ]
    },
    {
        group: 'Develop Website & Automate Growth - Phase 2',
        title: 'Website - Item Imbue',
        description: '',
        checklist: [
            [false, 1, "write blog post about new feature"],
        ]
    },
    {
        group: 'Develop Website & Automate Growth - Phase 2',
        title: 'Website - Tavern',
        description: '',
        checklist: [
            [false, 1, "write blog post about new feature"],
        ]
    },
    {
        group: 'Develop Website & Automate Growth - Phase 3',
        title: 'Website - Launch',
        description: '',
        checklist: [
        ]
    },
    {
        group: 'Develop Website & Automate Growth - Phase 4',
        title: 'Website - Grow',
        description: '',
        checklist: [
        ]
    },
    {
        group: 'Marketing Campaign for the Adventurers\' Guild',
        title: 'Marketing Campaign',
        description: 'Run ads to garner new members for kickback kingdom website.',
        checklist: [
            
        ]
    },
    {
        group: 'Create Revenue Generation',
        title: 'Revenue Generation',
        description: 'These tasks are for creating revenue generation for kickback kingdom',
        checklist: [
            [false, 2, "Build \"Asset Creation Template\" for blender for making sellable asset packs"],
            [false, 2, "Get twitter account to the point where we can make money from engagement"],
        ]
    },
    {
        group: "Hire First Steward",
        title: "Hire First Steward",
        description: "",
        checklist: [

        ]
    },
    {
        group: "Develop Share Payout System",
        title: "Guild Shares - Develop Payout System",
        description: "",
        checklist: [

        ]
    },
    {
        group: 'Expand the Atlas System Lore',
        title: 'Atlas Lore - Base Lore',
        description: '',
        checklist: [
        ]
    },
    {
        group: 'Develop Atlas System Game Engine',
        title: 'Planning',
        description: '',
        checklist: [
        ]
    },
    {
        group: 'Develop Atlas System Game Engine',
        title: 'Development',
        description: '',
        checklist: [
        ]
    },
    {
        group: 'Develop Atlas System Game Engine',
        title: 'Stepwise Building',
        description: '',
        checklist: [
        ]
    },
    {
        group: 'Develop Atlas System Game Engine',
        title: 'Community Testing',
        description: '',
        checklist: [
        ]
    },
    {
        group: 'Develop Atlas System Game Engine',
        title: 'Refinement and Bug Fixing',
        description: '',
        checklist: [
        ]
    },
    {
        group: 'Develop and Release Twilight Racer',
        title: 'Twilight Racer',
        description: '',
        checklist: [
            [false, 2, "Finish the Twilight Racer teaser video"],
            [false, 2, "Finish first fully modular Star Car"],
            [false, 2, "Build a Star Car part brand"],
            [false, 2, "Build first parts set"],
        ]
    },
    {
        group: 'Develop and Release End of Empires',
        title: 'End of Empires',
        description: '',
        checklist: [
            [false, 1, "Build Hex Grid Engine"]
        ]
    },
    {
        group: 'Release Kickback Kingdom Game Publishing',
        title: 'Twilight Racer',
        description: '',
        checklist: [
        ]
    },
    {
        group: 'Keep releasing new games in Atlas System',
        title: 'Game Ideas',
        description: '',
        checklist: [
            [false, null, "Arena First Person Shooter"],
            [false, null, "Space Bouty Hunter"],
            [false, null, "Zombie Game"],
            [false, null, "Industry Builder Game"],
        ]
    },
];


$(document).ready(function() {
    let currentMonth = '';
    
    events.forEach(function(event) {
        if (currentMonth !== event.group) {
            currentMonth = event.group;
            $('.timeline').append(`<div class="timeline-date">${currentMonth}</div>`);
        }
        
        let checklistHtml = '';
        <?php if (IsLoggedIn()) { ?>
        event.checklist.forEach(function(item) {
            let username = '';
            if (item[1] != null)
                username = `<a href="<?php echo $urlPrefixBeta; ?>/u/${accounts[item[1]]}" class="username">${accounts[item[1]]}</a>`
                
            let icon = item[0] ? '<i class="fa-solid fa-square-check"></i>' : '<i class="fa-regular fa-square"></i>'; // Replace with your preferred icons
            checklistHtml += `<div class="checklist-item " ${item[0] ? "style='text-decoration: line-through;'" : ""} >${icon} ${username} ${item[2]}</div>`;
        });
        <?php } ?>
        
        var eventItem = `
            <div class="timeline-item">
                <div class="timeline-content">
                    <h4>${event.title}</h4>
                    <p>${event.description}</p>
                    ${checklistHtml}
                </div>
            </div>
        `;
        
        $('.timeline').append(eventItem);
    });
});
    </script>
</body>

</html>
