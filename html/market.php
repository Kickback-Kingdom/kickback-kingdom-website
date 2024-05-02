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
                
                
                $activePageName = "Market";
                require("php-components/base-page-breadcrumbs.php"); 
                
                ?>

                <!-- Products Section -->
                <div class="row row-cols-1 row-cols-md-3 g-4">
                    <?php
                    // Example array of products
                    $products = [
                        ['name' => 'Product 1', 'price' => '15 Coins', 'image' => 'path/to/image1.jpg'],
                        ['name' => 'Product 2', 'price' => '30 Coins', 'image' => 'path/to/image2.jpg'],
                        ['name' => 'Product 3', 'price' => '45 Coins', 'image' => 'path/to/image3.jpg']
                    ];

                    foreach ($products as $product) {
                        echo '<div class="col">';
                        echo '<div class="card h-100">';
                        echo '<img src="'. $product['image'] .'" class="card-img-top" alt="'. $product['name'] .'">';
                        echo '<div class="card-body">';
                        echo '<h5 class="card-title">'. $product['name'] .'</h5>';
                        echo '<p class="card-text">Price: '. $product['price'] .'</p>';
                        echo '</div>';
                        echo '<div class="card-footer"><a href="#" class="btn btn-primary">Add to Cart</a></div>';
                        echo '</div>';
                        echo '</div>';
                    }
                    ?>
                </div>
                <!-- Products Section End -->

            </div>
            
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>

    
    <?php require("php-components/base-page-javascript.php"); ?>

</body>

</html>
