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
	
	$rootNodeId = 20;
	$rootCat = $objectManager->get('Magento\Catalog\Model\Category');
	$cat_info = $rootCat->load($rootNodeId);
	
	
	$opts = array(
    'http' => array(
	'header' => "Accept: application/xml"
    )
	);
	
	
	//for($j=1; $j <=8; $j++){
	$manufacture = array();
	$xml = simplexml_load_file('http://www.biludstyr.dk/dynamic.aspx?data=APIIMAGES&key=22846020:66822&output=xml&RowspPage=1000&PageNumber=5&dataonly=true');
	$i = 0;
	
	$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/apiproducdaddimage_page5.log');
	$logger = new \Zend\Log\Logger();
	$logger->addWriter($writer);
	
	foreach($xml->images as $images){
		
		$product = $objectManager->get('Magento\Catalog\Model\Product');
        if ($product->getIdBySku(trim($images->productid))) {
			$product_id = $product->getIdBySku(trim($images->productid));
			$product = $objectManager->create('Magento\Catalog\Model\Product')->load($product_id);
			
			 if(empty($product->getData('image'))){
			$imageUrl = ((isset($images->filename)) ? $images->filename : '' );
			
			$directory = $objectManager->get('\Magento\Framework\Filesystem\DirectoryList');
			
			
			
			
			$tmpDir = $directory->getPath('media') . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR;
			$newFileName = $tmpDir . baseName($imageUrl);
			$fileread = $objectManager->get('Magento\Framework\Filesystem\Io\File');
			
			$result = $fileread->read($imageUrl, $newFileName);
			
			if ($result) {
			$imageType = ['image', 'small_image', 'thumbnail'];
            /** add saved file to the $product gallery */
            $product->addImageToMediaGallery($newFileName, $imageType, true, $visible = true);
        }
		try {
				$product->save();
				$logger->info("product image added " . $product->getSku());
				} catch (Exception $ex) {
				echo $ex->getMessage();
			}
			
			
		}
		}
		//die('last');
		
	}
	//}
	
	
	
	
	
	
	
	
?>