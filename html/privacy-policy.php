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
                    $activePageName = "Privacy Policy";
                    require("php-components/base-page-breadcrumbs.php"); 
                ?>
                <div class="privacy-policy container py-4">
                    <p><strong>Effective Date:</strong> Feb, 10 2025</p>
                    <p><strong>Company:</strong> Atlas Holdings International LLC</p>
                    <p><strong>Contact:</strong> <a href="mailto:help@kickback-kingdom.com">help@kickback-kingdom.com</a></p>

                    <h4 class="mt-5 mb-3">1. Information We Collect</h4>
                    <ul>
                        <li><strong>Account Information:</strong> Name, username, email address</li>
                        <li><strong>Gameplay Data:</strong> Guild progress, quest completions, event participation</li>
                        <li><strong>Technical Data:</strong> IP address, browser type, device info, and analytics</li>
                        <li><strong>Payment Info:</strong> We do not store credit card information. Payments are handled by third-party services (e.g., Steam, Stripe)</li>
                        <li><strong>Communications:</strong> Feedback, support inquiries, and messages</li>
                        <li><strong>Client Project Data:</strong> For IT service clients, we may store project-related data, including source code and content under contract</li>
                    </ul>

                    <h4 class="mt-5 mb-3">2. How We Use Your Information</h4>
                    <ul>
                        <li>Operate and improve the Kickback Kingdom platform</li>
                        <li>Manage accounts, guild shares, and in-game progress</li>
                        <li>Deliver customer support and IT services</li>
                        <li>Send updates, events, and platform news (you can opt out anytime)</li>
                        <li>Monitor for abuse, bugs, and suspicious behavior</li>
                    </ul>

                    <h4 class="mt-5 mb-3">3. How We Share Your Information</h4>
                    <p>We do <strong>not</strong> sell your personal information. We may share limited data with:</p>
                    <ul>
                        <li>Service providers (e.g. analytics, hosting, email tools)</li>
                        <li>Payment processors (e.g. Steam, Stripe)</li>
                        <li>Contractors working on IT services under NDA</li>
                        <li>Law enforcement if legally required</li>
                    </ul>

                    <h4 class="mt-5 mb-3">4. Data Storage & Security</h4>
                    <p>We use modern encryption and access controls to protect your data. Information is stored securely using cloud services located in the U.S.</p>

                    <h4 class="mt-5 mb-3">5. Your Rights</h4>
                    <p>You may:</p>
                    <ul>
                        <li>Request to view, modify, or delete your personal data</li>
                        <li>Opt out of marketing communications</li>
                        <li>Close your account at any time</li>
                    </ul>
                    <p>To make a request, contact us at: <a href="mailto:help@kickback-kingdom.com">help@kickback-kingdom.com</a></p>

                    <h4 class="mt-5 mb-3">6. Cookies and Tracking</h4>
                    <p>We use cookies to track basic session info and site activity. You may disable cookies in your browser, though this may affect gameplay features.</p>

                    <h4 class="mt-5 mb-3">7. Children's Privacy</h4>
                    <p>Our services are not directed to children under 13. If we learn that a child has provided personal info, we will delete it promptly.</p>

                    <h4 class="mt-5 mb-3">8. Changes to This Policy</h4>
                    <p>We may update this Privacy Policy. We'll notify users through the platform or by email if the changes are significant.</p>

                    <h5 class="mt-5">📌 Need help or have questions?</h5>
                    <p>Contact us at: <a href="mailto:help@kickback-kingdom.com">help@kickback-kingdom.com</a></p>
                </div>


            </div>

            
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>

    
    <?php require("php-components/base-page-javascript.php"); ?>
</body>

</html>
