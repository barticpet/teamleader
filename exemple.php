<?php
require_once('config.php');

if ($config['debug'])
  error_reporting(E_ALL & ~E_NOTICE);
  else error_reporting(0);

if(isset($_POST['Submit'])) {
    if ($_POST['customerListLink'])
        $config['customerListLink']=$_POST['customerListLink'];
    if ($_POST['productListLink'])
        $config['productListLink']=$_POST['productListLink'];
    if ($_POST['orderLink'])
        $config['orderLink']=$_POST['orderLink'];

    include (BASE_PATH."/discountService.php");
    $discount = new discountService($config);
    //$discount->setDiscountByOrder();
    //$discount->setDiscountByCategory();
    //$discount->setDiscountByCategoryMix();
    $discount->setAllDiscounts();
    $discount->getFileOrderAfterDiscount();
    $outputTextDiscount = '';
    if (count($discount->orderList))
        foreach ($discount->orderList as $order){
            if (array_key_exists('discount',$order)){
                $outputTextDiscount .= '<div class="alert alert-info alert-dismissible fade show" role="alert">
                    <strong><a href="'.BASE_URL.$discount->outputFile.'" target="_blank">Open json result file</a><br></strong>';
                foreach ($order['discount'] as $typeDiscount=>$discountDetails){
                    if ($typeDiscount == 'discountByOrder') { // we can have only one of this type
                        $outputTextDiscount .= "<p> For order with id: {$order['id']}, the customer {$discountDetails['customer-name']}, will have {$discountDetails['sumDiscount']}€ discount<br> <small>Applied Discount who has already bought for over {$discountDetails['config-minimumBuy']}€, gets a discount of {$discountDetails['config-percentVal']}% on the whole order. {$discountDetails['customer-name']} has already bought {$discountDetails['customer-revenue']}€ </small></p>";
                    } elseif ($typeDiscount == 'discountByCategory') {
                        foreach ($discountDetails as $categoryId=>$discountCategoryDetails){
                            $outputTextDiscount .= "<p>  For order with id: {$order['id']},the customer {$discountCategoryDetails['customer-name']}, will have {$discountCategoryDetails['sumDiscount']}€ discount, will take {$discountCategoryDetails['noOfProductsDiscounted']} free product of {$discountCategoryDetails['discountedProductDescription']}.<br> <small>For every product of category id {$categoryId}, when you buy {$discountCategoryDetails['config-minimumBuy']}, you get next for free.</small></p>";

                        }
                    }elseif ($typeDiscount == 'discountByCategoryMix') {
                        foreach ($discountDetails as $categoryId=>$discountCategoryDetails){
                            $outputTextDiscount .= "<p>  For order with id: {$order['id']},the customer {$discountCategoryDetails['customer-name']}, will have {$discountCategoryDetails['sumDiscount']}€ discount, for product {$discountCategoryDetails['discountedProductDescription']}.<br> <small>If you buy {$discountCategoryDetails['config-minimumBuy']} products of category id: {$categoryId}, you get a {$discountCategoryDetails['config-percentVal']}% discount on the cheapest product.</small></p>";

                        }
                    }
                }
                $outputTextDiscount.='<button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>';
            }
        }
  }
?> 
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>Discount Test Teamleader</title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
</head>

<body>
<div class="container-fluid">
<?php
    if (isset($outputTextDiscount)){
        echo $outputTextDiscount;
    }
?>

<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
  <div class="form-group">
    <label for="customerListLink">Customer List</label>
    <input type="text" class="form-control" id="customerListLink"  name="customerListLink" value="<?php echo $config['customerListLink'] ?>">
    <small  class="form-text text-muted">Set the customer list link.</small>
  </div>
  <div class="form-group">
    <label for="customerListLink">Product List</label>
    <input type="text" class="form-control" id="productListLink" name="productListLink" value="<?php echo $config['productListLink'] ?>">
    <small class="form-text text-muted">Set the product list link.</small>
  </div>
  <div class="form-group">
    <label for="orderLink">The order</label>
    <input type="text" class="form-control" id="orderLink" name="orderLink" value="<?php echo $config['orderLink'] ?>">
    <small class="form-text text-muted">Set the orderlink.</small>
  </div>
  <button type="submit" name="Submit" class="btn btn-primary">Submit</button>
  </div>

  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</body>
</html>
