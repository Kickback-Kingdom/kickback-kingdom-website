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

    

    <!--MAIN CONTENT-->
    <main class="container pt-3 bg-body" style="margin-bottom: 56px;">
        <div class="row">
            <div class="col-12 col-xl-9">
                
                
                <?php 
                
                
                $activePageName = "Business Plan";
                require("php-components/base-page-breadcrumbs.php"); 
                
                ?>
                <div class="card">
                    <div class="card-header pb-0 text-center">
                        <h1>Kickback Kingdom Business Plan</h1>
                    </div>
                    <div class="card-body">
                        <h4 style="padding-top: 20px;">Executive Summary</h4>
                        <p>
                            Kickback Kingdom is a fully transparent community-driven platform that aims to bring people together to collaborate on projects and earn revenue. Our business model is to create a community of people who like to hang out or work together, and give them the opportunity to earn shares for their contributions. As the community grows and revenue is generated, these shares can earn crypto currency dividends, creating a self-sustaining ecosystem that benefits everyone involved.
                        </p>
                        <h4 style="padding-top: 20px;">Products and Services</h4>
                        <p>
                        Kickback Kingdom offers a variety of services to its members, including:
                        </p>
                        <ul style="margin-left: 0px;padding-left: 0px;padding-top: 0px;padding-bottom: 0px;margin-bottom: 0px;list-style: none;">
                                <li style="padding-bottom: 10px; width: -webkit-fill-available;"><i class="fa fa-angle-double-right txt-primary me-3"></i><strong>A platform for collaborative projects:</strong> Members can connect with each other and work on projects together, pooling their resources and expertise to achieve common goals.</li>
                                <li style="padding-bottom: 10px; width: -webkit-fill-available;"><i class="fa fa-angle-double-right txt-primary me-3"></i><strong>A referral system:</strong> Members can invite others to join the community and earn rewards for doing so.</li>
                                <li style="padding-bottom: 10px; width: -webkit-fill-available;"><i class="fa fa-angle-double-right txt-primary me-3"></i><strong>Regular community events:</strong> Members can participate in a range of activities and events organized by the community to keep them engaged and active.</li>
                                <li style="padding-bottom: 10px; width: -webkit-fill-available;"><i class="fa fa-angle-double-right txt-primary me-3"></i><strong>Game development:</strong> We are currently developing three games - End of Empires, Twilight Racer, and L.I.C.H. - which members can participate in and contribute to.</li>
                            </ul>
                        <h4 style="padding-top: 20px;">Market Analysis</h4>
                        <p>
                            Our target market is the gaming community, specifically those who enjoy multiplayer games and are interested in collaborating with others. There is a growing trend towards community-driven gaming, with many players seeking out ways to connect with others and work on projects together. By offering a platform that facilitates this type of collaboration, we believe that we can tap into this market and provide a valuable service to our members.
                        </p>
                        <h4 style="padding-top: 20px;">Marketing Strategy</h4>
                        <p>
                        Our marketing strategy is focused on building brand awareness and attracting new members to the community. We will leverage a variety of channels to achieve this, including:
                        </p>
                        <ul style="margin-left: 0px;padding-left: 0px;padding-top: 0px;padding-bottom: 0px;margin-bottom: 0px;list-style: none;">
                                <li style="padding-bottom: 10px; width: -webkit-fill-available;"><i class="fa fa-angle-double-right txt-primary me-3"></i><strong>Social media:</strong> We will develop a strong social media presence on platforms like Twitter, Tik-Tok, Meta, and Discord to engage with our target audience and promote our services.</li>
                                <li style="padding-bottom: 10px; width: -webkit-fill-available;"><i class="fa fa-angle-double-right txt-primary me-3"></i><strong>Influencer marketing:</strong> We will partner with popular gaming influencers to promote Kickback Kingdom and attract new members to the community.</li>
                                <li style="padding-bottom: 10px; width: -webkit-fill-available;"><i class="fa fa-angle-double-right txt-primary me-3"></i><strong>Advertising:</strong> We will explore various advertising channels, such as Google AdWords and Meta Ads, to reach our target audience and drive traffic to our website.</li>
                                <li style="padding-bottom: 10px; width: -webkit-fill-available;"><i class="fa fa-angle-double-right txt-primary me-3"></i><strong>Content marketing:</strong> We will create valuable content, such as blog posts and video tutorials, to attract visitors to our website and demonstrate our expertise in the gaming industry.</li>
                            </ul>

                        
                        <h4 style="padding-top: 20px;">Financial Plan</h4>
                        <p>Our financial plan includes the following:</p>
                        
                        <ul style="margin-left: 0px;padding-left: 0px;padding-top: 0px;padding-bottom: 0px;margin-bottom: 0px;list-style: none;">
                                <li style="padding-bottom: 10px; width: -webkit-fill-available;"><i class="fa fa-angle-double-right txt-primary me-3"></i><strong>Start-up costs:</strong> Approximately $15,000 for equipment, software, and other expenses related to getting the business up and running.</li>
                                <li style="padding-bottom: 10px; width: -webkit-fill-available;"><i class="fa fa-angle-double-right txt-primary me-3"></i><strong>Operating costs:</strong> Approximately $1,000 per month to hosting fees, software, and other ongoing expenses.</li>
                                <li style="padding-bottom: 10px; width: -webkit-fill-available;"><i class="fa fa-angle-double-right txt-primary me-3"></i><strong>Revenue:</strong> Our revenue is generated through a variety of sources, including sponsoring events, selling video games, selling video game assets and advertising.</li>
                            </ul>

                            <h4 style="padding-top: 20px;">Conclusion</h4>
                            <p>Kickback Kingdom is a unique and innovative platform that offers a range of services to its members, including collaborative projects, regular community events, and game development. Our profit-sharing system creates a self-sustaining ecosystem that benefits everyone involved, and incentivizes members to continue contributing to the community.</p>
                            <p>By focusing on building a strong foundation, expanding the community, developing engaging content, and generating revenue through game sales and other sources, we believe that Kickback Kingdom can become a leading platform in the gaming industry.</p>
                            <p>We are excited about the potential of this business, and believe that our experienced team, innovative approach, and commitment to community building and revenue sharing will set us apart from other platforms in the market. We look forward to growing and evolving with our community, and to building a platform that delivers value to its members and to the wider gaming industry.</p>
                    </div>
                </div>
                
            </div>
            
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
    </main>

    
    <?php require("php-components/base-page-javascript.php"); ?>

</body>

</html>
