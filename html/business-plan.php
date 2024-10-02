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
                        Kickback Kingdom is a fully transparent, community-driven platform that aims to bring people together to collaborate on projects, earn revenue, and share in the success. The platform operates as a community business, where members contribute their skills and ideas to various projects, earning guild shares in return. These guild shares are tied to cryptocurrency dividends, creating a self-sustaining ecosystem where contributors can directly benefit from their efforts as the community grows.
                        </p>
                        <p>
                        Our vision is to create a thriving environment for game development, collaborative projects, and community-driven creativity, all within a structured framework that rewards participants for their contributions. By fostering a strong community spirit and leveraging the potential of blockchain technology, Kickback Kingdom is positioned to become a leader in the gaming and creative industries.
                        </p>
                        <h4 style="padding-top: 20px;">Products and Services</h4>
                        <p>
                        Kickback Kingdom offers a range of services to its members, designed to foster collaboration and engagement:
                        </p>
                        <ul style="margin-left: 0px;padding-left: 0px;padding-top: 0px;padding-bottom: 0px;margin-bottom: 0px;list-style: none;">
                                <li style="padding-bottom: 10px; width: -webkit-fill-available;"><i class="fa fa-angle-double-right txt-primary me-3"></i><strong>Collaborative project platform:</strong> Members can connect with each other to work on projects, pooling resources and expertise. Projects span across game development, artwork, music, and event organization. We provide tools like project management, file sharing, and real-time collaboration spaces to make teamwork seamless.</li>
                                <li style="padding-bottom: 10px; width: -webkit-fill-available;"><i class="fa fa-angle-double-right txt-primary me-3"></i><strong>A referral system:</strong> Members can invite others to join the community and earn rewards for doing so.</li>
                                <li style="padding-bottom: 10px; width: -webkit-fill-available;"><i class="fa fa-angle-double-right txt-primary me-3"></i><strong>Regular community events:</strong> Our community frequently organizes Game Jams, tournaments, and other creative events, allowing members to showcase their work, meet others, and engage in friendly competition.</li>
                                <li style="padding-bottom: 10px; width: -webkit-fill-available;"><i class="fa fa-angle-double-right txt-primary me-3"></i><strong>Game development:</strong> We are currently developing two flagship games, Atlas Odyssey and L.I.C.H., which members can participate in by contributing ideas, artwork, coding, or testing. As these games grow, so do the opportunities for our community members to benefit from their success.</li>
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
                                <li style="padding-bottom: 10px; width: -webkit-fill-available;"><i class="fa fa-angle-double-right txt-primary me-3"></i><strong>Social media:</strong> We will develop a strong social media presence on platforms like Youtube, Tik-Tok, Meta, and Discord to engage with our target audience and promote our services.</li>
                                <li style="padding-bottom: 10px; width: -webkit-fill-available;"><i class="fa fa-angle-double-right txt-primary me-3"></i><strong>Influencer marketing:</strong> We will partner with popular gaming influencers to promote Kickback Kingdom and attract new members to the community.</li>
                                <li style="padding-bottom: 10px; width: -webkit-fill-available;"><i class="fa fa-angle-double-right txt-primary me-3"></i><strong>Advertising:</strong> We will explore various advertising channels, such as Google AdWords and Meta Ads, to reach our target audience and drive traffic to our website.</li>
                                <li style="padding-bottom: 10px; width: -webkit-fill-available;"><i class="fa fa-angle-double-right txt-primary me-3"></i><strong>Content marketing:</strong> We will create valuable content, such as blog posts and video tutorials, to attract visitors to our website and demonstrate our expertise in the gaming industry.</li>
                            </ul>

                        
                        <h4 style="padding-top: 20px;">Financial Plan</h4>
                        <p>Our financial plan includes the following:</p>
                        
                        <ul style="margin-left: 0px;padding-left: 0px;padding-top: 0px;padding-bottom: 0px;margin-bottom: 0px;list-style: none;">
                                <li style="padding-bottom: 10px; width: -webkit-fill-available;"><i class="fa fa-angle-double-right txt-primary me-3"></i><strong>Start-up costs:</strong> Approximately $15,000 for equipment, software, and other expenses related to getting the business up and running.</li>
                                <li style="padding-bottom: 10px; width: -webkit-fill-available;"><i class="fa fa-angle-double-right txt-primary me-3"></i><strong>Operating costs:</strong> Approximately $2,000 per month to hosting fees, software, and other ongoing expenses.</li>
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
