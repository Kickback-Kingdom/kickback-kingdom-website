<?php

declare(strict_types =1);

namespace Kickback\Backend\Controllers;

use \Kickback\Backend\Models\Account;
use \Kickback\Backend\Models\RecordId;
use \Kickback\Backend\Models\Store;
use \Kickback\Services\Database;

use \Exception;
use Kickback\Backend\Models\Product;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vRecordId;

class StoreTester
{
    public static function testStoreController()
    {
        $database = static::createTestEnviroment();

        $storeController = new StoreController;

        $testStore = static::testAddStore($storeController);
        static::testUpsertProduct_Insert($testStore);
        static::testUpsertProduct_Update($testStore);
        static::testRemoveProduct($testStore);

        static::cleanupTestEnviroment($database);
    }  

    public static function testAddStore(StoreController $storeController) : store
    {
        $store = static::returnTestStore();

        try
        {
            $resp = StoreController::addStore($store);
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while testing addStore : $e");
        }

        return $store;
    }

    private static function testUpsertProduct_Update(vRecordId $store) : void
    {
        try
        {
            $product = static::returnTestProduct($store);

            StoreController::upsertProduct($product);

            $getProduct = StoreController::getProductById($product->getVRecordId());

            if($getProduct->data->locator != "Test product locator")
            {
                throw new Exception("Locator did not match expected before product was updated. Expected : 'Test product locator' | Actual : '$getProduct->data->locator'");
            }

            $product->locator = "this was updated!";

            StoreController::upsertProduct($product);

            $getProduct = StoreController::getProductById($product->getVRecordId());

            if($getProduct->data->locator != $product->locator)
            {
                throw new Exception("Locator did not match expected after product was updated. Expected : '$product->locator' | Actual : '$getProduct->data->locator'");
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while testing upsertProduct : $e");
        }
    }

    private static function testUpsertProduct_Insert(vRecordId $store) : void
    {
        try
        {
            $product = static::returnTestProduct($store);

            StoreController::upsertProduct($product );
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while testing upsertProduct : $e");
        }
    }

    private static function testRemoveProduct(vRecordId $store) : void
    {
        try
        {
            $product = static::returnTestProduct($store);
            $product->locator = "Delete this locator";

            StoreController::upsertProduct($product);
            $deleteResp = StoreController::removeProductById($product);

            $existResp = StoreController::doesProductExistByLocator($product->locator);

            if($existResp->success)
            {
                if($existResp->data)
                {
                    throw new Exception("Object still exists after deletion : $deleteResp->message");
                }
            }
            else
            {
                throw new Exception("Failed to check products existance after deletion : $existResp->message");
            }
        }
        catch(Exception $e)
        {
            throw new Exception("Exception caught while testing removeProduct : $e");
        }
    }

    public static function createTestEnviroment() : string
    {
        $randomId = new RecordId;
        $database = "TEST_DATABASE_STORE_DELETE_IF_STILL_PRESENT_$randomId->crand";

        $query = "CREATE DATABASE $database";
        Database::executeSqlQuery($query, []);

        Database::changeDatabase($database);


        //Account
        static::createAccountTable();
        static::createAccountView();
        static::insertTestAccount();
        $buyer  = static::getTestAccount();

        //Media
        static::createMediaTable();
        static::createMediaView();
        static::insertRaffleTicketMedia();
        static::insertTableTopSimluatorMedia();

        //Item
        static::createItemTable();
        static::insertRaffleTicketItem();
        static::insertTableTopSimluatorItem();
        static::createItemView();

        //Store
        static::createStoreTable();
        static::createStoreView();

        //Loot
        static::createLootTable();
        static::createLootView();
        static::insertRaffleTicketsForTestAccount(5, $buyer);

        //Product
        static::createProductTable();
        static::createProductView();

        //Cart
        static::createCartTable();
        static::createCartView();

        //CartProductLink
        static::createProductCartLinkTable();
        static::createProductCartLinkView();

        return $database;
    }

    public static function cleanupTestEnviroment(string $database) : void
    {
        $sql = "DROP DATABASE $database";

        Database::changeDatabase($database);
        Database::executeSqlQuery($sql, []);
    }

    //Product Cart Link

    private static function createProductCartLinkTable() : void
    {
        $query = "CREATE TABLE cart_product_link (
            ctime datetime not null,
            crand int not null,
            removed boolean not null default 0,
            checked_out boolean not null default 0,
            ref_cart_ctime datetime not null,
            ref_cart_crand int not null,
            ref_product_ctime datetime not null,
            ref_product_crand int not null,
            
            PRIMARY KEY (ctime, crand),
                
            CONSTRAINT fk_cart_product_link_ref_cart_ctime_crand FOREIGN KEY (ref_cart_ctime, ref_cart_crand) REFERENCES cart(ctime, crand),
            CONSTRAINT fk_cart_product_link_ref_product_ctime_crand FOREIGN KEY (ref_product_ctime, ref_product_crand) REFERENCES product(ctime, crand)
        );";

        database::executeSqlQuery($query, []);
    }

    private static function createProductCartLinkView() : void
    {
        $query = "CREATE VIEW v_cart_product_link AS (
        SELECT
            cplink.ctime,
            cplink.crand,
            vp.name,
            vp.description,
            vp.locator,
            cplink.removed,
            cplink.checked_out,
            vc.ref_account_username,
            vc.ref_store_name, 
            vp.ref_store_locator,
            cplink.ref_cart_ctime,
            cplink.ref_cart_crand,
            cplink.ref_product_ctime,
            cplink.ref_product_crand,
            vc.ref_account_ctime,
            vc.ref_account_crand,
            vc.ref_store_ctime,
            vc.ref_store_crand,
            vp.large_media_media_path,
            vp.small_media_media_path,
            vp.back_media_media_path
        FROM cart_product_link cplink
            LEFT JOIN v_product vp on cplink.ref_product_ctime = vp.ctime and cplink.ref_product_crand = vp.crand
            LEFT JOIN v_cart vc on cplink.ref_cart_ctime = vc.ctime and cplink.ref_cart_crand = vc.crand
        );
        ";

        database::executeSqlQuery($query, []);
    }

    //Cart

    private static function createCartTable() : void
    {  
        $query = "CREATE TABLE cart
        (
            ctime datetime not null,
            crand int not null,
            checked_out boolean not null default 0,
            void boolean not null default 0,
            ref_account_ctime datetime not null,
            ref_account_crand int not null,
            ref_store_ctime datetime not null,
            ref_store_crand int not null,
            ref_transaction_ctime datetime,
            ref_transaction_crand int,
            
            primary key (ctime, crand),
            
            UNIQUE KEY unique_cart_ref_account_ctime_crand_ref_store_ctime_crand (ref_account_ctime, ref_account_crand, ref_store_ctime, ref_store_crand),
            
            CONSTRAINT fk_cart_ref_account_ctime_crand_account_ctime_crand FOREIGN KEY (ref_account_crand) REFERENCES account(id),
            CONSTRAINT fk_cart_ref_store_ctime_crand_store_ctime_crand FOREIGN KEY (ref_store_ctime, ref_store_crand) REFERENCES store(ctime, crand)
        );";

        database::executeSqlQuery($query, []);
    }

    private static function createCartView() : void
    {
        $query = "CREATE VIEW v_cart AS (
        SELECT
            c.ctime,
            c.crand,
            a.Username as `ref_account_username`,
            s.name as `ref_store_name`,
            s.locator as `ref_store_locator`,
            a.DateCreated AS `ref_account_ctime`,
            a.Id as `ref_account_crand`,
            s.ctime as `ref_store_ctime`,
            s.crand as `ref_store_crand`
            FROM cart c
            LEFT JOIN store s on c.ref_store_ctime = s.ctime AND c.ref_store_crand = s.crand
            LEFT JOIN account a on a.id = c.ref_account_crand
        );";

        database::executeSqlQuery($query, []);
    }

    //PRODUCT

    private static function createProductTable() : void
    {
        $query = "CREATE TABLE product
            (
                ctime DATETIME not null,
                crand int not null,
                `name` varchar(50) not null,
                `description` varchar(200),
                locator varchar(50) not null,
                ref_store_ctime DATETIME not null,
                ref_store_crand int not null,
                ref_item_ctime DATETIME not null,
                ref_item_crand int not null,
                
                PRIMARY KEY (ctime, crand),
                
                CONSTRAINT fk_product_ref_store_ctime_crand_store_ctime_crand FOREIGN KEY (ref_store_ctime, ref_store_crand) REFERENCES store(ctime, crand),
                CONSTRAINT fk_product_ref_item_ctime_crand FOREIGN KEY (ref_item_crand) REFERENCES item(id)
            );";

        Database::executeSqlQuery($query, []);
    }

    private static function createProductView() : void
    {
        $query = "
            CREATE VIEW v_product AS (
    SELECT
        p.ctime,
        p.crand, 
        p.name,
        p.description,
        p.locator,
        s.name AS `ref_store_name`,
        s.locator AS `ref_store_locator`,
        s.description AS `ref_store_description`,
        s.Username AS `ref_store_owner_username`,
        s.ownerCtime AS `ref_store_owner_ctime`,
        s.ownerCrand AS `ref_store_owner_crand`,
    	s.ctime as `ref_store_ctime`,
    	s.crand as `ref_store_crand`,
    	'' as `ref_item_ctime`,
    	i.Id as `ref_item_crand`,
        i.equipable,
        i.is_container,
        i.container_size,
        i.container_item_category,
        vi.large_image AS `large_media_media_path`,
        vi.small_image AS `small_media_media_path`,
        mback.mediaPath AS `back_media_media_path`
    FROM product p 
    LEFT JOIN item i ON p.ref_item_crand = i.Id
    LEFT JOIN v_store s ON s.ctime = p.ref_store_ctime AND s.crand = p.ref_store_crand
    LEFT JOIN v_item_info vi on vi.id = p.ref_item_crand
    LEFT JOIN v_media mback on mback.id = i.media_id_back
);";

        database::executeSqlQuery($query, []);
    }

    private static function returnTestProduct(vRecordId $storeId) : Product
    {
        $testProduct = new Product("CheapTestProduct", "Test-Product-Cheap", "Test product locator", $storeId->ctime, $storeId->crand, '2024-01-09 16:58:40', 81);


        return $testProduct;
    }

    //ACCOUNT
    private static function createAccountTable() : void
    {
        $query = "CREATE TABLE account (
            Id int(11) NOT NULL AUTO_INCREMENT,
            Email varchar(30) NOT NULL,
            `Password` varchar(255) NOT NULL,
            FirstName varchar(30) NOT NULL,
            LastName varchar(30) NOT NULL,
            DateCreated timestamp NOT NULL DEFAULT utc_timestamp(),
            Username varchar(30) NOT NULL,
            Banned tinyint(1) NOT NULL DEFAULT 0,
            pass_reset int(11) DEFAULT NULL,
            passage_id int(11) DEFAULT NULL,
            PRIMARY KEY (Id),
            UNIQUE KEY Email (Email),
            UNIQUE KEY unique_passage_id (passage_id),
            FULLTEXT KEY idx_fulltext_name_search (Username,FirstName,LastName,Email)
        )";

        Database::executeSqlQuery($query, []);
    } 

    private static function createAccountView() : void
    {
        $query = "CREATE VIEW v_account_info AS 
            select 
            account.Username AS Username,
            account.FirstName AS FirstName,
            account.LastName AS LastName,
            account.Id AS Id,
            account.Banned AS Banned,
            account.pass_reset AS pass_reset,
            account.Email AS email,
            0 AS exp,
            0 AS level,
            0 AS exp_needed,
            0 AS exp_started,
            0 AS prestige,
            0 AS badges,
            0 AS exp_current,
            0 AS exp_goal,
            0 AS IsAdmin,
            0 AS IsArtist,
            0 AS IsQuestGiver,
            0 AS IsProgressScribe,
            0 AS IsMerchant,
            0 AS IsApprentice,
            0 AS IsMaster,
            0 AS IsSteward,
            0 AS IsMagisterOfAdventurers,
            0 AS IsChancellorOfExpansion,
            0 AS IsChancellorOfTechnology,
            0 AS IsStewardOfExpansion,
            0 AS IsStewardOfTechnology,
            0 AS IsServantOfTheLich,
            0 AS avatar_media,
            0 AS player_card_border_media,
            0 AS banner_media,
            0 AS background_media,
            0 AS charm_media,
            0 AS companion_media 
            from account 
        ;";

        Database::executeSqlQuery($query, []);
    }

    public static function insertTestAccount() : void
    {
        $account = static::returnTestAccount();

        $query = "INSERT INTO account 
        (id, email, `password`, firstName, lastName, DateCreated, Username, Banned, pass_reset, passage_id) VALUES 
        (1, '$account->email', 'somepasswordhash', '$account->firstName', '$account->lastName', '2022-10-06 16:46:07', '$account->username', 0, 0, -1);";

        Database::executeSqlQuery($query, []);
    }

    public static function returnTestAccount() : Account
    {
        $account = new Account();

        $account->email = "testemail@gmail.com";
        $account->firstName = "Joe";
        $account->lastName = "Doe";
        $account->username = "JoeDoe";
        $account->banned = false;

        return $account;
    }

    public static function getTestAccount() : vAccount
    {
        $account = static::returnTestAccount();

        $resp = AccountController::getAccountByUsername($account->username);

        if($resp->success)
        {
            return $resp->data;
        }
        else
        {
            throw new Exception("failed to get test account from database");
        }
    }

    //STORE

    public static function createStoreTable() : void
    {
        $query = "CREATE TABLE store
            (
                ctime datetime not null,
                crand int not null,
                `name` varchar(50) not null,
                locator varchar(50) not null,
                `description` varchar(200) not null,
                ref_account_ctime datetime not null,
                ref_account_crand int not null,
                
                PRIMARY KEY (ctime, crand),
                
                CONSTRAINT fk_store_ref_account_ctime_crand_account_ctime_crand FOREIGN KEY (ref_account_crand) references account(Id) 
            );";

        Database::executeSqlQuery($query, []);
    }

    public static function createStoreView() : void
    {
        $query = "CREATE VIEW v_store AS (
            SELECT
                s.ctime,
                s.crand,
                s.name,
                s.locator,
                s.description,
                a.Username,
                a.DateCreated as 'ownerCtime',
                a.Id as 'ownerCrand'
            FROM store s
                LEFT JOIN account a ON s.ref_account_crand = a.Id
            );";

        Database::executeSqlQuery($query, []);
    }

    public static function returnTestStore() : Store
    {
        $accountId = static::getTestAccount();

        $store = new Store("testStore", "testLocator", "testDescription", $accountId);

        return $store;
    }

    //ITEM

    private static function createItemTable() : void
    {

        $query = "CREATE TABLE item (
            Id int(11) NOT NULL AUTO_INCREMENT,
            `type` int(11) NOT NULL,
            rarity int(11) NOT NULL,
            media_id_large int(11) NOT NULL,
            media_id_small int(11) NOT NULL,
            `desc` varchar(1024) NOT NULL,
            `name` varchar(255) DEFAULT NULL,
            nominated_by_id int(11) DEFAULT NULL,
            collection_id int(11) DEFAULT NULL,
            equipable tinyint(4) NOT NULL DEFAULT 0,
            equipment_slot enum('AVATAR','PC_BORDER','BANNER','BACKGROUND','CHARM','PET') DEFAULT NULL,
            redeemable tinyint(4) NOT NULL DEFAULT 0,
            useable boolean not null default 0,
            is_container boolean not null default 0,
            container_size int not null default -1,
            container_item_category int,
            item_category int,
            media_id_back int,
            PRIMARY KEY (Id)
            )";

        Database::executeSqlQuery($query, []);
    }

    private static function createItemView() : void
    {
        $query = "CREATE VIEW v_item_info AS 
            select 
                i.Id AS Id,
                i.type AS `type`,
                i.rarity AS rarity,
                i.media_id_large AS media_id_large,
                i.media_id_small AS media_id_small,
                i.desc AS `desc`,
                i.name AS `name`,
                i.nominated_by_id AS nominated_by_id,
                i.collection_id AS collection_id,
                null AS item_collection_name,
                null AS item_collection_desc,
                i.equipable AS equipable,
                i.equipment_slot AS equipment_slot,
                i.redeemable AS redeemable,
                i.useable AS useable,
                concat(large_image.Directory,'/',large_image.Id,'.',large_image.extension) AS large_image,
                concat(small_image.Directory,'/',small_image.Id,'.',small_image.extension) AS small_image,
                account_artist.Username AS artist,
                account_artist.Id AS artist_id,
                account_nominator.Username AS nominator,
                account_nominator.Id AS nominator_id,
                large_image.DateCreated AS DateCreated 
            from item i 
            left join media large_image on(i.media_id_large = large_image.Id)
            left join media small_image on(i.media_id_small = small_image.Id)
            left join account account_artist on(large_image.author_id = account_artist.Id) 
            left join account account_nominator on(i.nominated_by_id = account_nominator.Id)";

        database::executeSqlQuery($query, []);
    }

    private static function insertRaffleTicketItem() : void
    {

        $query = "INSERT INTO item (Id, `type`, rarity, media_id_large, media_id_small, `desc`, `name`, nominated_by_id, collection_id, equipable, equipment_slot, redeemable, useable, is_container, container_size, container_item_category, item_category, media_id_back) 
        VALUES(4, 2, 0, 21, 21, 'Can be used in Raffle Events to win rewards!', 'Raffle Ticket', NULL, NULL, 0, NULL, 0, 0, 0, -1, null, null, 21)";

        Database::executeSqlQuery($query, []);
    }

    private static function insertTableTopSimluatorItem() : void
    {
        $sql = "INSERT INTO item (Id, `type`, rarity, media_id_large, media_id_small, `desc`, `name`, nominated_by_id, collection_id, equipable, equipment_slot, redeemable, useable, is_container, container_size, container_item_category, item_category, media_id_back)
        VALUES(81, 3, 0, 282, 282, 'test table top simulator copy', 'tabletestsimulator', null, null, 0, null, 1, 0, 0, -1, null, null, 282)";

        Database::executeSqlQuery($sql, []);
    }

    //MEDIA
    private static function createMediaTable() : void
    {

        $query = "CREATE TABLE media (
            Id int(11) NOT NULL AUTO_INCREMENT,
            ServiceKey varchar(36) NOT NULL,
            `name` varchar(45) NOT NULL,
            `desc` varchar(255) NOT NULL,
            author_id int(11) NOT NULL,
            DateCreated timestamp NOT NULL DEFAULT utc_timestamp(),
            extension varchar(10) NOT NULL,
            Directory varchar(255) NOT NULL,
            PRIMARY KEY (Id),
            KEY idx_media_id (DateCreated,Id)
            ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci";

        Database::executeSqlQuery($query, []);
    }

    private static function createMediaView() : void
    {

        $query = "CREATE VIEW v_media AS (
            select media.Id AS Id,
            media.name AS `name`,
            media.desc AS `desc`,
            media.author_id AS author_id,
            media.DateCreated AS DateCreated,
            media.extension AS extension,
            media.Directory AS directory,
            concat(media.Directory,'/',media.Id,'.',media.extension) AS mediaPath 
            from media)";

        Database::executeSqlQuery($query, []);
    }

    private static function insertRaffleTicketMedia() : void
    {
        $query = "INSERT INTO media (Id, ServiceKey, `name`, `desc`, author_id, dateCreated, extension, directory)
        VALUES (21, 'fdsafdsafdsafdsafdsafdsa', 'raffle ticket', 'raffle ticket', '1', '2023-02-21 20:30:39', 'png', 'items')";

        Database::executeSqlQuery($query, []);
    }

    private static function insertTableTopSimluatorMedia() : void
    {
        $query = "INSERT INTO media (Id, ServiceKey, `name`, `desc`, author_id, dateCreated, extension, directory)
        VALUES (282, 'asdfasdfasdfasdfasdfasf', 'table test simulator logo', 'table top simulator logo', '1', '2023-02-21 20:30:39', 'png', 'games')";

        Database::executeSqlQuery($query, []);
    }

    //LOOT
    private static function createLootTable() : void
    {

        $query = " 	CREATE TABLE loot (
            Id int(11) NOT NULL AUTO_INCREMENT,
            opened tinyint(4) NOT NULL DEFAULT 0,
            account_id int(11) DEFAULT NULL,
            item_id int(11) NOT NULL DEFAULT 0,
            quest_id int(11) DEFAULT NULL,
            dateObtained datetime NOT NULL DEFAULT current_timestamp(),
            redeemed tinyint(4) NOT NULL DEFAULT 0,
            PRIMARY KEY (Id)
            ) ENGINE=InnoDB AUTO_INCREMENT=2993 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci";

        Database::executeSqlQuery($query, []);
    }

    private static function createLootView() : void
    {

        $query = "CREATE VIEW v_loot_item AS select 
            a.Id AS Id,
            a.opened AS opened,
            a.account_id AS account_id,
            a.item_id AS item_id,
            a.quest_id AS quest_id,
            b.media_id_small AS media_id_small,
            b.media_id_large AS media_id_large,
            b.type AS loot_type,
            b.desc AS `desc`,
            b.rarity AS rarity,
            a.dateObtained AS dateObtained 
            from (loot a join item b on(a.item_id = b.Id))";

        Database::executeSqlQuery($query, []);
    }

    private static function insertRaffleTicketsForTestAccount(int $numberOfRaffleTicketsToGive, vRecordId $accountId) : void
    {

        if($numberOfRaffleTicketsToGive <= 0)
        {
            return;
        }

        $insertPart = "(28, 1, $accountId->crand, 4, NULL, '2023-01-14 17:01:39', 1)";

        for($numOfLoot = 1; $numOfLoot < $numberOfRaffleTicketsToGive; $numOfLoot++)
        {
            $insertPart = $insertPart.",(28, 1, $accountId->crand, 4, NULL, '2023-01-14 17:01:39', 1)";
        }

        $query = "INSERT INTO `loot` (`Id`, `opened`, `account_id`, `item_id`, `quest_id`, `dateObtained`, `redeemed`) VALUES
            (28, 1, $accountId->crand, 4, NULL, '2023-01-14 17:01:39', 1);";

        Database::executeSqlQuery($query, []);
    }

    //TRADE
    private static function createTradeTable() : void
    {

        $query = "CREATE TABLE trade (
            id int(11) NOT NULL AUTO_INCREMENT,
            from_account_id int(11) DEFAULT NULL,
            to_account_id int(11) DEFAULT NULL,
            loot_id int(11) DEFAULT NULL,
            trade_date timestamp NOT NULL DEFAULT utc_timestamp(),
            from_account_obtain_date datetime DEFAULT NULL,
            PRIMARY KEY (id)
            )";

        Database::executeSqlQuery($query, []);
    }

    private static function createTradeView() : void
    {

        $query = "CREATE VIEW v_trade AS (select trade.id AS id,
            trade.from_account_id AS from_account_id,
            trade.to_account_id AS to_account_id,
            trade.loot_id AS loot_id,
            trade.trade_date AS trade_date,
            trade.from_account_obtain_date AS from_account_obtain_date from trade)";

        Database::executeSqlQuery($query, []);
    }

    
}

?>