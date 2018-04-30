<?php

/**
 * 
 *
 * @author		Petrica Bartic
 * @link		
 */
class discountService {

    /**
     * Api version 
     * 
     * @var string
     */
    private $version = '1.0';

    /**
     * If this is on, request data payload gets printed
     * 
     * @var Boolean
     */
    private $debug = true;

    /**
     * If this is 1, will load a folder with orders
     * 
     * @var Boolean
     */
    private $multipleOrders = 0;

    
    /**
     * Stores erros at wrapper level
     * 
     * @var array
     */
    private $errors=array();
    
    /**
     * Output file format
     *  * json
     *  
     */
    public $outputFile;

    /**
     * Input products list
     *  * array
     *  
     */
    private $productList;

    
    /**
     * Input  customer list
     *  * array
     *  
     */
    private $customerList;

    
    /**
     * Input  order list
     *  * array
     *  
     */
    public $orderList = [];

    /**
     * Array of json messages
     * 
     */

    protected $_messages = array(
        JSON_ERROR_NONE => 'No error has occurred',
        JSON_ERROR_DEPTH => 'The maximum stack depth has been exceeded',
        JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON',
        JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
        JSON_ERROR_SYNTAX => 'Syntax error',
        JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded'
    );

    /**
     * Current type of discounts
     *  
     * @var array
     */
    private $discountByOrder;
    private $discountByCategory;
    private $discountByCategoryMix;
    

    /**
     * Initiate
     */
    public function __construct($config) {

        if (isset($config['debug']))
            $this->debug = $config['debug'];
        
        if (isset($config['multipleOrders']))
            $this->multipleOrders = $config['multipleOrders'];
  
        if (isset($config['productListLink']))
            $this->getProductList($config['productListLink']);
        else $this->registerError("No product file link");
        
        if (isset($config['customerListLink']))
            $this->getCustomerList($config['customerListLink']);
        else $this->registerError("No customer file link");
  
        if (isset($config['orderLink'])){
            $this->getOrders($config['orderLink']);
        }else $this->registerError("No order link set");
        

        if (isset($config['discounts']['discountByOrder']))
            $this->discountByOrder = $config['discounts']['discountByOrder'];
        
        if (isset($config['discounts']['discountByCategory']))
            $this->discountByCategory = $config['discounts']['discountByCategory'];
        
        if (isset($config['discounts']['discountByCategoryMix']))
            $this->discountByCategoryMix = $config['discounts']['discountByCategoryMix'];

        if($this->debug) {
            $data=$this->getErrors();
            print_r($data);
        }
    }


    /**
     * Apply all discounts
     * 
     */
    public function setAllDiscounts(){

        $this->setDiscountByOrder();
        $this->setDiscountByCategory();
        $this->setDiscountByCategoryMix();

    }
    
    /**
     * Apply discount by order for a customer with a minimum buy
     * 
     */
    public function setDiscountByOrder(){
        
        foreach ($this->orderList as $key=>$order){
            // verify if customer exist in our list
            if ( array_key_exists($order['customer-id'],$this->customerList)){ 
                // check if revenue until now is bigger than discount minimum value
                if ($this->discountByOrder['minimumBuy'] <= $this->customerList[$order['customer-id']]['revenue']){
                    $this->orderList[$key]['discount']['discountByOrder']['config-minimumBuy']=$this->discountByOrder['minimumBuy'];
                    $this->orderList[$key]['discount']['discountByOrder']['config-percentVal']=$this->discountByOrder['percentVal'];
                    $this->orderList[$key]['discount']['discountByOrder']['customer-id']=$order['customer-id'];
                    $this->orderList[$key]['discount']['discountByOrder']['customer-name']=$this->customerList[$order['customer-id']]['name'];
                    $this->orderList[$key]['discount']['discountByOrder']['customer-revenue']=$this->customerList[$order['customer-id']]['revenue'];
                    $this->orderList[$key]['discount']['discountByOrder']['sumDiscount']=$this->discountByOrder['percentVal']/100*$order['total'];
                }
            }
        }
        
    } 

    /**
     * Apply discount free product after set no products buy from a category
     * 
     */
    public function setDiscountByCategory(){
        
        foreach ($this->orderList as $key=>$order){
            foreach ($order['items'] as $product){
                // get product category
                if (isset($this->productList[$product['product-id']]['category'])){
                    $category=$this->productList[$product['product-id']]['category'];
                    //check if category of the product exist in discount list
                    if (array_key_exists($category,$this->discountByCategory) && $product['quantity'] > $this->discountByCategory[$category]){
                        $noOfProductsDiscounted=floor(($product['quantity']) / ($this->discountByCategory[$category]+1));
                        $this->orderList[$key]['discount']['discountByCategory'][$category]['customer-id']=$order['customer-id'];
                        $this->orderList[$key]['discount']['discountByCategory'][$category]['customer-name']=$this->customerList[$order['customer-id']]['name'];
                        $this->orderList[$key]['discount']['discountByCategory'][$category]['customer-revenue']=$this->customerList[$order['customer-id']]['revenue'];
                        $this->orderList[$key]['discount']['discountByCategory'][$category]['config-categoryId']=$category;
                        $this->orderList[$key]['discount']['discountByCategory'][$category]['config-minimumBuy']=$this->discountByCategory[$category];
                        $this->orderList[$key]['discount']['discountByCategory'][$category]['discountedProductId']=$product['product-id'];
                        $this->orderList[$key]['discount']['discountByCategory'][$category]['discountedProductDescription']=$this->productList[$product['product-id']]['description'];
                        $this->orderList[$key]['discount']['discountByCategory'][$category]['noOfProductsDiscounted']=$noOfProductsDiscounted;
                        $this->orderList[$key]['discount']['discountByCategory'][$category]['sumDiscount']=$product['unit-price']*$noOfProductsDiscounted;
                    }
                }
            }
        }

    } 

    /**
     * Apply discount percent per minim products buy from a category
     * 
     */
    public function setDiscountByCategoryMix(){
        
        foreach ($this->orderList as $key=>$order){
            
            $applyDiscountByCategoryMix=false;
            
            unset($theCheapestProduct);
            
            foreach ($order['items'] as $product){
                // get product category
                if (isset($this->productList[$product['product-id']]['category'])){
                    if (!isset($theCheapestProduct['cost']) || $product['unit-price'] < $theCheapestProduct['cost']){
                        $theCheapestProduct['cost'] = $product['unit-price'];
                        $theCheapestProduct['id'] = $product['product-id'];
                    }
                    $category=$this->productList[$product['product-id']]['category'];
                    //check if category of the product exist in discount list
                    if (array_key_exists($category,$this->discountByCategoryMix) && $product['quantity'] >= $this->discountByCategoryMix[$category]['minimumBuy'])
                        $applyDiscountByCategoryMix[$category]=$category;
                    
                }
            }
            if ($applyDiscountByCategoryMix)
                foreach($applyDiscountByCategoryMix as $category){
                    $this->orderList[$key]['discount']['discountByCategoryMix'][$category]['customer-id']=$order['customer-id'];
                    $this->orderList[$key]['discount']['discountByCategoryMix'][$category]['customer-name']=$this->customerList[$order['customer-id']]['name'];
                    $this->orderList[$key]['discount']['discountByCategoryMix'][$category]['customer-revenue']=$this->customerList[$order['customer-id']]['revenue'];    
                    $this->orderList[$key]['discount']['discountByCategoryMix'][$category]['config-categoryId']=$category;
                    $this->orderList[$key]['discount']['discountByCategoryMix'][$category]['config-percentVal']=$this->discountByCategoryMix[$category]['percentVal'];
                    $this->orderList[$key]['discount']['discountByCategoryMix'][$category]['config-minimumBuy']=$this->discountByCategoryMix[$category]['minimumBuy'];
                    $this->orderList[$key]['discount']['discountByCategoryMix'][$category]['sumDiscount']=$theCheapestProduct['cost']*$this->discountByCategoryMix[$category]['percentVal']/100;
                    $this->orderList[$key]['discount']['discountByCategoryMix'][$category]['discountedProductId']=$theCheapestProduct['id'];
                    $this->orderList[$key]['discount']['discountByCategoryMix'][$category]['discountedProductDescription']=$this->productList[$theCheapestProduct['id']]['description'];
                    $this->orderList[$key]['discount']['discountByCategoryMix'][$category]['discountedProductCost']=$theCheapestProduct['cost'];                
                }
        }

    } 
    
    /**
     * Set Products details list
     * @param $url
     */
    public function getProductList($url){

        if ($this->requestedByTheSameDomain($url))
            $productList=$this->jsonDecode(file_get_contents($url));
        else {
            if (!$this->isValidUrl($url))
                $this->registerError("getproductList: Failed opening {$url} for reading");    
            else
                $productList=$this->jsonDecode($this->send($url));
        }
        
        if (isset($productList) && count($productList)){
                foreach($productList as $product)
                $this->productList[$product['id']]=$product;
        }

    }
    
    /**
     * Get Customers details list
     * @param $url
     */
    public function getCustomerList($url){

        if ($this->requestedByTheSameDomain($url))
            $customerList=$this->jsonDecode(file_get_contents($url));
        else {
            if (!$this->isValidUrl($url))
                $this->registerError("getCustomerList: Failed opening {$url} for reading");    
            else
                $customerList=$this->jsonDecode($this->send($url));
        }
        
        if (isset($customerList) && count($customerList)){
                foreach($customerList as $customer)
                $this->customerList[$customer['id']]=$customer;
        }
        
        
    }

    /**
     * Get Order content
     * @param $url
     */
    public function getOrders($url){
        
        if ($this->multipleOrders==0){
            
            if ($this->requestedByTheSameDomain($url))
                $this->orderList[]=$this->jsonDecode(file_get_contents($url));
            
            elseif (!$this->isValidUrl($url))
                $this->registerError("getOrders: Failed opening {$url} for reading");    
            
            else
                $this->orderList[]=$this->jsonDecode($this->send($url));
        }
        else {
            $orderFiles = $this->getFileList($url);
            
            if (count($orderFiles)){
                foreach ($orderFiles as $file){
                    $this->orderList[]=$this->jsonDecode(file_get_contents($file['path']));
                }
            } else $this->registerError("getOrders: No file in order folder");
        }

    }

    /**
     * Create a file in the root folder and write the order after apply the discounts
     * @return filename 
     */
    public function getFileOrderAfterDiscount()
    {
        $filename=time().'.json';
        $fh = fopen($filename, 'w');
        fwrite($fh, $this->jsonEncode($this->orderList)); // here we write the data to the file.
        fclose($fh); 
        $this->outputFile=$filename;
    }

    /**
     * Get content of folder
     * @param $dir
     * @return array 
     */
    public function getFileList($dir)
    {

        // array to hold return value
        $retFiles = [];

        // add trailing slash if missing
        if(substr($dir, -1) != "/")
            $dir .= "/";        

        // open pointer to directory and read list of files
        if ($d = @dir($dir)){            
            while(FALSE !== ($entry = $d->read())) {
                // skip hidden files
                if($entry{0} == ".") continue;
                if(is_dir("{$dir}{$entry}")) {
                    $retFiles[] = [
                    'path' => "{$dir}{$entry}/",
                    'type' => filetype("{$dir}{$entry}"),
                    'size' => 0,
                    'lastmod' => filemtime("{$dir}{$entry}")
                    ];
                } elseif(is_readable("{$dir}{$entry}")) {
                    $retFiles[] = [
                    'path' => "{$dir}{$entry}",
                    'type' => mime_content_type("{$dir}{$entry}"),
                    'size' => filesize("{$dir}{$entry}"),
                    'lastmod' => filemtime("{$dir}{$entry}")
                    ];
                }
            }
            $d->close();
    
        } else {
            $this->registerError("getFileList: Failed opening directory {$dir} for reading");
        }

        return $retFiles;

    }

    /**
     * Encode json string
     * @param string $json
     * @return json
     */
    public function jsonEncode($value, $options = 0) {
        
        $result = json_encode($value, $options);

        if($result)  {
            return $result;
        }

        $this->registerError($this->_messages[json_last_error()]);

    }
    
    /**
     * Decode json sring
     * @param string $json
     * @return array
     */
    public function jsonDecode($json, $assoc = true) {
        
        $result = json_decode($json, $assoc);
        if($result) {
            return $result;
        }

        $this->registerError($this->_messages[json_last_error()]);

    }

    /**
     * Send the built request via curl
     * @param url $url
     * @return string
     */
    public function send($url) {
 
        // print request configuration, before send
        if ($this->debug) {
           echo $this->debug."<br>The request: ".print_r($url);
        } 
        
        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $url);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, 2);
        
        $response = curl_exec($curl_handle);   
        
        $info = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
         //print_r($info);//die();

        if ($this->debug) {
            echo "<br>Header: http->$info";
            echo "<br>Curl version: ".print_r(curl_version(),true);
            echo "<br>The response: ".print_r($response,true);
            echo  "<br>Wrapper errors:: ".print_r($this->errors,true);
        }
        
         //a curl server error?
        if($info!=200 and $response==""){
            $errcode=  curl_errno($curl_handle);
            $errmsg= curl_error($curl_handle);
            $this->registerError("a problem occured while sending the request: $errcode. $errmsg" );  
            $response = false;
        }  
        
        curl_close($curl_handle);

        return $response;
      
    }

    /**
     *  Check if url is valid
     * 
     * @param $url
     * @return boolean
     */
    function requestedByTheSameDomain($url) {
        return parse_url($_SERVER['SCRIPT_URI'], PHP_URL_HOST) === parse_url($url, PHP_URL_HOST);
    }

   /**
     *  Check if url is valid
     * 
     * @param $url
     * @return boolean
     */
    function isValidUrl($url){
        
        $response = array();
        //Check if URL is empty
        if(!empty($url)) {
            $response = get_headers($url);
        }
        return (bool)in_array("HTTP/1.1 200 OK", $response, true);

    }

    /**
     *  Pushes the wrapper error array to the stack
     * 
     * @param type $err
     * @return array
     * 
     */
    public function registerError($err=  array()){
        
        $this->errors[]=$err;
        return $this;

    }

    
    /**
     * Return the error stack
     * 
     * @return array
     */
    public function getErrors(){
        
        return array("status"=>"error","msg"=>$this->errors);

    }


}

?>