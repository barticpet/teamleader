# teamleader
Teamleader DiscountService

## How discounts work

For now, there are three possible ways of getting a discount:

- A customer who has already bought for over € minimumBuy, gets a discount of percentVal% on the whole order.

Go in config and set $config['discounts']['discountByOrder']=
        [
            'minimumBuy'=>1000,
            'percentVal'=>10
        ];

- For every product of category "categoryId" , when you buy minimumBuy, you get next for free.

Go in config and set $config['discounts']['discountByCategory']=['categoryId'=>'minimumBuy']; 
You can add more categories in this array;

- If you buy minimumBuy or more products of category "categoryId" , you get a percentval% discount on the cheapest product.

$config['discounts']['discountByCategoryMix']=[
'categoryId'=>[
            'minimumBuy'=>2,
            'percentVal'=>20
        ]
];


By the way: there may become more ways of granting customers discounts in the future: You can add new discount in $config['discounts'];

## APIs
 - The customer list:
 
$config['customerListLink']: this url can be stored on same folder or external link.

 - The product list:
 
$config['productListLink']: this url can be stored on same folder or external link

 - The orders:
 
$config['multipleOrders']=0; // if is set 1 put a path to the multiple orders folder
$config['orderLink']: this url can be stored on same folder or external link

If you put $config['multipleOrders']=1, $config['orderLink'] should be a path on current server to a folder with orders

## How you get RESULTS:
include "config.php";

include "discountService.php";

call $discount = new discountService($config);

 - Apply discount order:

$discount->setDiscountByOrder();

 - Apply discount by category and get next for free

$discount->setDiscountByCategory();

 - Apply discount by category and get percent from the cheapest product

$discount->setDiscountByCategoryMix();
    
 - Apply all discounts;

$discount->setAllDiscounts();
    
 - Create a json file:

$discount->getFileOrderAfterDiscount();
