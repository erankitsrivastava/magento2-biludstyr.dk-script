<?php

use Magento\Framework\App\Bootstrap;

/**
 * If your external file is in root folder
 */
require __DIR__ . '../../app/bootstrap.php';

/**
 * If your external file is NOT in root folder
 * Let's suppose, your file is inside a folder named 'xyz'
 *
 * And, let's suppose, your root directory path is
 * /var/www/html/magento2
 */
// $rootDirectoryPath = '/var/www/html/magento2';
// require $rootDirectoryPath . '/app/bootstrap.php';

$params = $_SERVER;
error_reporting(E_ALL);
ini_set('display_errors', 1);

$bootstrap = Bootstrap::create(BP, $params);
$objectManager = $bootstrap->getObjectManager();
$state = $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode('adminhtml');
$directory = $objectManager->get('\Magento\Framework\Filesystem\DirectoryList');
$rootPath = $directory->getRoot();

$importDir = $rootPath . '/pub/media/import/';

$storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
$indexCollection = $objectManager->get('\Magento\Indexer\Model\Indexer\CollectionFactory');
$indexFactory = $objectManager->get('\Magento\Indexer\Model\IndexerFactory');
$categoryFactory = $objectManager->get('\Magento\Catalog\Model\CategoryFactory');

$rootNodeId = 1580;
$rootCat = $objectManager->get('Magento\Catalog\Model\Category');
$cat_info = $rootCat->load($rootNodeId);
$opts = array('http' => array('header' => "Accept: application/xml"));

function createCategory($categoryTitle) {
    global $objectManager;
    $CollectionFactory = $objectManager->get('Magento\Catalog\Model\CategoryFactory')->create();

    $categoryId = array();
    $collection = $CollectionFactory->getCollection()->addFieldToFilter('name', ['in' => $categoryTitle]);

    if ($collection->getSize()) {
        $categoryId[] = $collection->getFirstItem()->getId();
        return $categoryId;
    } else {
        $categoryTmp = $objectManager->get('\Magento\Catalog\Model\CategoryFactory')->create();
        // Add a new sub category under root category

        $categoryTmp->setName($categoryTitle);
        $categoryTmp->setParentId(1580);
        $categoryTmp->setIsActive(true);
        $objectManager->get('\Magento\Catalog\Api\CategoryRepositoryInterface')->save($categoryTmp);
        //$categoryTmp->save();
        $categoryId[] = $categoryTmp->getId();
        return $categoryId;
    }
}

// function getmyCategory($categoryTitle) {
//     global $objectManager;
//     $CollectionFactory = $objectManager->get('Magento\Catalog\Model\CategoryFactory')->create();

//     $categoryId = array();
//     $collection = $CollectionFactory->getCollection()->addFieldToFilter('name', ['in' => $categoryTitle]);

//     if ($collection->getSize()) {
//         $categoryId[] = $collection->getFirstItem()->getId();
//         return $categoryId;
//     } else {
//         return '';
//     }
// }

//for ($j = 1; $j <= 2; $j++) {

    $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/productimportapi_page26.log');
    $logger = new \Zend\Log\Logger();
    $logger->addWriter($writer);

    $manufacture = array();
    $xml = simplexml_load_file('https://www.biludstyr.dk/dynamic.aspx?data=APIPRODUCTS&key=22846020:66822&output=xml&RowspPage=1000&PageNumber=26&dataonly=true');
    $i = 0;

    foreach ($xml->products as $dataproducts) {
        print_r($dataproducts);
         die("fkdsfhks");
        //if($i > 10){die("dsjlfkdlsk");}

        $product = $objectManager->get('Magento\Catalog\Model\Product');
        if ($product->getIdBySku(trim($dataproducts->productid))) {

            $prodid = $product->getIdBySku(trim($dataproducts->productid));
            $catName = str_replace("_", " ", $dataproducts->category); // Category Name
            $categoryTitle = trim(ucfirst($catName));
            $pcatids = createCategory($categoryTitle);

            $myprd = $objectManager->get('Magento\Catalog\Model\Product')->load($prodid);
            if ($pcatids) {
                $myprd->setCategoryIds($pcatids);
            }

            try {
                $myprd->save();
                $logger->info("product updated " . $myprd->getSku());
            } catch (Exception $ex) {
                echo $ex->getMessage();
            }

            echo "Product exist " . $prodid . "<br/>";
        } else {
            $_product = $objectManager->create('\Magento\Catalog\Model\Product');

            $eavConfig = $objectManager->get('\Magento\Eav\Model\Config');

            $catName = str_replace("_", " ", $dataproducts->category); // Category Name
            $categoryTitle = trim(ucfirst($catName));
            $pcatids = getmyCategory($categoryTitle);

            // echo "<pre>";
            // print_r($pcatids);
            // die('RRRRRRRRRRR');
            $brand = "";
            /* add manufacture option start */
            if (trim($dataproducts->manufacturer)) {
                $attribute = $eavConfig->getAttribute('catalog_product', 'brand');
                $options = $attribute->getSource()->getAllOptions();
                $options2 = $attribute->getSource()->getAllOptions();
                foreach ($options2 as $option3) {
                    if ($option3['label'] == trim($dataproducts->manufacturer)) {
                        $brand = $option3['value'];
                    }
                }
            }

            $url = "";
            $urlsku = "";

            $name = trim($dataproducts->header);
            $description = trim($dataproducts->description);
            $sku = trim($dataproducts->productid);
            $qty = $dataproducts->instock;

            $url = preg_replace('#[^0-9a-z]+#i', '-', $name);
            $urlsku = preg_replace('#[^0-9a-z]+#i', '-', $sku);
            $url = strtolower($url) . "-" . strtolower($urlsku);

            $_product->setUrlKey($url);
            $_product->setSku($sku);
            $_product->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
            $_product->setTypeId('simple');
            $_product->setAttributeSetId(4);
            $_product->setWebsiteIds(array(1));
            $_product->setVisibility(4);
            $_product->setName($name);
            $_product->setDescription($description);
            $_product->setShortDescription($description);
            $_product->setData('volume', trim($dataproducts->volume));
            $_product->setData('weight', trim($dataproducts->weight));
            $_product->setData('ean', trim($dataproducts->ean));
            $_product->setData('usr_sca_length', trim($dataproducts->usr_sca_length));
            $_product->setData('usr_sca_width', trim($dataproducts->usr_sca_width));
            $_product->setData('usr_sca_height', trim($dataproducts->usr_sca_height));

            if ($brand) {
                $_product->setData('brand', $brand);
            }
            $_product->setPrice($dataproducts->priceretail);
            $_product->setCategoryIds($pcatids);

            $_product->setStockData(array(
                'use_config_manage_stock' => 0, //'Use config settings' checkbox
                'manage_stock' => 1, //manage stock
                'min_sale_qty' => 1, //Minimum Qty Allowed in Shopping Cart
                'max_sale_qty' => 10000, //Maximum Qty Allowed in Shopping Cart
                'is_in_stock' => 1, //Stock Availability
                'qty' => $qty //qty
                    )
            );
            try {
                $_product->save();
                $logger->info("product added " . $_product->getSku() . " and qty is " . $qty);
            } catch (Exception $ex) {
                echo $ex->getMessage();
            }



            $i++;
        }
    }
//}
echo $i;
echo "<br/>";
echo $i . " products added";
?>