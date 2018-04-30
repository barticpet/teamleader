# teamleader
Teamleader DiscountService

SETTINGS:

There are three possible ways of getting a discount:

    A customer who has already bought for over € 1000, gets a discount of 10% on the whole order.
Go in config and set $config['discounts']['discountByOrder']=
        [
            'minimumBuy'=>1000, //set min value for which we apply a disccount
            'percentVal'=>10 // set discount value in % on the whole order
        ];

    For every product of category "Switches" (id 2), when you buy five, you get a sixth for free.
$config['discounts']['discountByCategory']: 'categoryId'=>'minimumBuy' 
You can add more categoryId;

    If you buy two or more products of category "Tools" (id 1), you get a 20% discount on the cheapest product.
$config['discounts']['discountByCategoryMix']: 
'categoryId'=>[
            'minimumBuy'=>2,
            'percentVal'=>10
        ]

The customer list:
$config['customerListLink']: this url can be stored on same folder or external link.

The product list:
$config['productListLink']: this url can be stored on same folder or external link

The orders
$config['multipleOrders']=0; // if is set 1 put a path to the multiple orders folder
$config['orderLink']: this url can be stored on same folder or external link

If you put $config['multipleOrders']=1, $config['orderLink'] should be a path on current server to a folder with orders

How you get RESULTS:
include (BASE_PATH."/discountService.php");
$discount = new discountService($config);

Apply discount order:
$discount->setDiscountByOrder();

Apply discount by category and get next for free
$discount->setDiscountByCategory();

Apply discount by category and get percent from the cheapest product
$discount->setDiscountByCategoryMix();
    
Apply all discounts;
$discount->setAllDiscounts();
    
Create a json file:
$discount->getFileOrderAfterDiscount();
