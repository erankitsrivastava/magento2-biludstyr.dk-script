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
	
	$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/product-simple.log');
	$logger = new \Zend\Log\Logger();
	$logger->addWriter($writer);
	
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
	$manufacture = array();
	$xml = simplexml_load_file('http://www.biludstyr.dk/dynamic.aspx?data=APISTOCK&key=22846020:66822&output=xml&RowspPage=10&PageNumber=1&dataonly=true');
	$i = 0;
	
	echo "<pre>";
	print_r($xml);
	
	
	
	
	
?>