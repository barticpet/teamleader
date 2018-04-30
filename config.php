<?php

    define('BASE_PATH', $_SERVER['DOCUMENT_ROOT'].'/teamleader/');
    define('BASE_URL','http://'.$_SERVER['HTTP_HOST'].'/teamleader/'); // URL
    
    $config['debug'] = false;
    
    $config['customerListLink']='https://raw.githubusercontent.com/teamleadercrm/coding-test/master/data/customers.json';
    //$config['customerListLink']=BASE_URL.'data/customers.json';

    $config['productListLink']='https://raw.githubusercontent.com/teamleadercrm/coding-test/master/data/products.json';
    //$config['productListLink']=BASE_URL.'data/products.json';
       
    //link to the order
    $config['multipleOrders']=0; // if is set 1 put a path to the multiple orders folder
    $config['orderLink']='https://raw.githubusercontent.com/teamleadercrm/coding-test/master/example-orders/order2.json';
   // $config['orderLink']=BASE_URL.'orders/order3.json';
    //$config['orderLink']=BASE_PATH.'orders/';
    
    //A customer who has already bought for over € minimum, gets a discount of percentVal% on the whole order
    $config['discounts']['discountByOrder']=
        [
            'minimumBuy'=>1000, //set min value for which we apply a disccount
            'percentVal'=>10 // set discount value in % on the whole order
        ];

    //arrays of category for which we can set discount to get next product free
    //For every product of categoryId, when you buy minimumBuy, you get next for free.
    //'categoryId'=>'minimumBuy'            
    $config['discounts']['discountByCategory']=
        [
            '2'=>'5'
        ];

    /*
        If you buy minimumBuy products of categoryId, you get a percentVal discount on the cheapest product.      
        'categoryId'=>[
            'minimumBuy'=>2,
            'percentVal'=>10
        ]
    */
    $config['discounts']['discountByCategoryMix']=
        [

            '1'=>[
                'minimumBuy'=>2,
                'percentVal'=>20
            ]
        ];

?>