<?php
session_start();
if (!isset($_SESSION['email'])) header('location: /');
$email = $_SESSION['email'];
include("../php/db_connect.php");


switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        if(isset($_POST['qty'])){ //Manage quantity. Increase or Decrease
            $counter = $_POST['counter'];
            echo manageQuantity($counter);
        }
        else if(isset($_POST['sugar']) && isset($_POST['form'])){ //Insert to Cart
            echo InsertCoffeeToCart($_POST['form'], $_POST['sugar'], $_POST['sugarType'], $_POST['milk'], $_POST['cinnamon'], $_POST['choco']);
        }
        else if(isset($_POST['count']) && is_numeric($_POST['count'])){ //Remove One coffee from cart
            $countToDelete = $_POST['count'];
            echo removeCoffeeFromCart($countToDelete);
        }
        else if(!empty($_POST['orderagain'])){
            echo orderAgain();
        }
        break;
    case 'GET':
        break;
    default:
        echo false;
        break;
}

function InsertCoffeeToCart($code, $sugar, $sugarType, $milk, $cinnamon, $choco){
    global $pdo, $email;
    $sqlGetCoffee = "SELECT code,name,price FROM cc_coffees WHERE code = ?";
    $stmtNames =  $pdo -> prepare($sqlGetCoffee);
    $stmtNames -> execute([$code]);
    $coffees = $stmtNames -> fetch();
    $basePriceOfCoffee = $coffees['price'];
    $nameCheckDup = $coffees['name'];
    //Checking duplicates in cart, if there is one increase quantity and price
    $checkDupQuery = "SELECT coffee, sugar, sugarType, milk, cinnamon, choco, qty FROM cc_cart WHERE email = ? AND coffee = ? AND sugar = ? AND sugarType = ? AND milk = ? AND cinnamon = ? AND choco = ?";
    $stmtCheckDup = $pdo -> prepare($checkDupQuery);
    $stmtCheckDup -> execute([$email, $nameCheckDup, $sugar, $sugarType, $milk, $cinnamon, $choco]);
    if($stmtCheckDup -> rowCount() == 1){
        //Increase quantity if there is a duplicate coffee
        $rowOne = $stmtCheckDup -> fetch();
        $quantity = $rowOne['qty'] + 1;
        $newPrice = $basePriceOfCoffee * $quantity;
        $updateQuantity = "UPDATE cc_cart SET price = ? , qty = ? WHERE email = ? AND coffee = ? AND sugar = ? AND sugarType = ? AND milk = ? AND cinnamon = ? AND choco = ?";
        $stmtUpdateQty = $pdo -> prepare($updateQuantity);
        $stmtUpdateQty -> execute([$newPrice, $quantity, $email, $nameCheckDup, $sugar, $sugarType, $milk, $cinnamon, $choco]);
        return true;
    }
    else{
        //Keep a counter in cart
        $cart_query = "SELECT coffee, sugar, sugarType, milk, cinnamon, choco FROM cc_cart WHERE email = ?";
        $stmtCart = $pdo -> prepare($cart_query);
        $stmtCart -> execute([$email]);
        $count = $stmtCart -> rowCount();
        //Insert coffee to cart
        $name = $coffees['name'];
        $price = $coffees['price'];
        $code = $coffees['code'];
        $count++;
        $cart_query = "INSERT INTO cc_cart (email, count, code, coffee, sugar, sugarType, milk, cinnamon, choco, price, qty) VALUES( ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , 1)";
        $stmtCartInsert = $pdo -> prepare($cart_query);
        $stmtCartInsert -> execute([$email, $count, $code, $name, $sugar, $sugarType, $milk, $cinnamon, $choco, $price]);
        if($stmtCartInsert){
            $_SESSION['isCartEmpty'] = true;
            return true;
        }
        else{
            return false;
        }
    }
    return false;
}

function manageQuantity($counter){
    global $pdo, $email;
    //get one certain coffee
    $sqlPlus = "SELECT code, price, qty FROM cc_cart WHERE email = ? AND count = ? LIMIT 1";
    $stmtPlus = $pdo -> prepare($sqlPlus);
    $stmtPlus -> execute([$email, $counter]);
    $row = $stmtPlus -> fetch();
    $codeCoffee = $row['code'];
    //fetch the base price of one coffee
    $sqlCode = "SELECT price FROM cc_coffees WHERE code = ?";
    $stmtGetPrice = $pdo -> prepare($sqlCode);
    $stmtGetPrice -> execute([$codeCoffee]);
    $rowGetPrice = $stmtGetPrice -> fetch();
    $price = $rowGetPrice['price'];
    //depend on post value adjust the quantity
    if($_POST['qty'] === "minus") $quantity = $row['qty'] - 1;
    else if($_POST['qty'] === "plus") $quantity = $row['qty'] + 1;
    //update price, quantity for duplicate coffee in cart
    $newPrice = $price * $quantity;
    $sqlUpdate = "UPDATE cc_cart SET price = ?, qty = ? WHERE email = ? AND count = ?";
    $stmtUpdate = $pdo -> prepare($sqlUpdate);
    $stmtUpdate -> execute([$newPrice, $quantity, $email, $counter]);
    if($stmtUpdate){
        return true;
    }
    return false;
}

function removeCoffeeFromCart($countToDelete){
    global $pdo, $email;
    //delete coffee that user clicked
    $deleteQuery = "DELETE FROM cc_cart WHERE email = ? AND count = ?";
    $stmtDeleteCoffee = $pdo -> prepare($deleteQuery);
    $stmtDeleteCoffee -> execute([$email, $countToDelete]);
    if ($stmtDeleteCoffee) {
        isCartEmpty();
        return true;
    }
    else{
        return false;
    }
}

function orderAgain(){
    global $pdo;
    //Get products of order selected
    $sqlOrderAgain = "SELECT 
                    cc_orders.*, 
                    GROUP_CONCAT(cc_orders_products.coffee) as coffees,
                    GROUP_CONCAT(cc_orders_products.price) as price,
                    GROUP_CONCAT(cc_orders_products.qty) as qty
                    FROM cc_orders 
                    JOIN cc_orders_products ON cc_orders.id = cc_orders_products.id 
                    WHERE email = ?
                    GROUP BY cc_orders.id
                    ORDER BY cc_orders.id DESC";
    $stmtOrderAgain = $pdo -> prepare($sqlOrderAgain);
    $stmtOrderAgain -> execute([$_POST['orderagain']]);
    if ($stmtOrderAgain && clearCart()) { //clear the cart and check the query ran
        while ($order = $stmtOrderAgain -> fetch()) {
            //Keep a counter in cart
            $cart_query = "SELECT coffee, sugar, sugarType, milk, cinnamon, choco FROM cc_cart WHERE email = ?";
            $stmtCart = $pdo -> prepare($cart_query);
            $stmtCart -> execute([$_SESSION['email']]);
            $count = $stmtCart -> rowCount();
            //Insert coffee to cart
            $count++;
            $cart_query = "INSERT INTO cc_cart (email, count, coffee, sugar, sugarType, milk, cinnamon, choco, price, qty) VALUES( ? , ? , ? , ? , ? , ? , ? , ? , ? , ?)";
            $stmtCartInsert = $pdo -> prepare($cart_query);
            $stmtCartInsert -> execute([$_SESSION['email'], $count, $order['coffee'], $order['sugar'], $order['sugarType'], $order['milk'], $order['cinnamon'], $order['choco'], $order['price'], $order['qty']]);
        }
        if ($stmtCartInsert) {
            $_SESSION['isCartEmpty'] = true;
            return true;
        }
    }
    return false;
}

function clearCart(){
    global $pdo;
    $sqlClearCart = "DELETE FROM cc_cart WHERE email = ?";
    $stmtClearCart = $pdo -> prepare($sqlClearCart);
    $stmtClearCart -> execute([$_SESSION['email']]);
    if ($stmtClearCart) {
        $_SESSION['isCartEmpty'] = false;
        return true;
    }
    else return false;
}

function isCartEmpty(){
    global $pdo;
    try{
        $sqlIsCartEmpty = "SELECT COUNT(*) as count FROM cc_cart WHERE email = ?";
        $stmtIsCartEmpty = $pdo -> prepare($sqlIsCartEmpty);
        $stmtIsCartEmpty -> execute([$_SESSION['email']]);
        $numCart = $stmtIsCartEmpty -> fetch();
        if ($stmtIsCartEmpty && $numCart > 0){
            $_SESSION['isCartEmpty'] = true;
            return true;
        }
        else if ($numCart == 0){
            $_SESSION['isCartEmpty'] = false;
            return true;
        }
        else {
            return false;
        }
    }
    catch (PDOException $e){
        return false;
    }
}