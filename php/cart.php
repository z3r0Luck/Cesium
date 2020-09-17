<?php
session_start();
$email = $_SESSION['email'];
include("db_connect.php");
//Manage quantity. Increase or Decrease
if(isset($_POST['qty'])){
    $counter = $_POST['counter'];
    $resQuantity = manageQuantity($counter);
    echo $resQuantity;
}
else if(isset($_POST['sugar']) && isset($_POST['form'])){ //Cart
    $res = InsertCoffeeToCart($_POST['form'], $_POST['sugar'], $_POST['sugarType'], $_POST['milk'], $_POST['cinnamon'], $_POST['choco']);
    echo $res;
}
else if(isset($_POST['count']) && is_numeric($_POST['count'])){
    $countToDelete = $_POST['count'];
    $resRemove = removeCoffeeFromCart($countToDelete);
    echo $resRemove;
}
else{
    echo false;
}


function InsertCoffeeToCart($code, $sugar, $sugarType, $milk, $cinnamon, $choco){
    global $pdo, $email;
    $sqlGetCoffee = "SELECT code,name,price FROM cc_coffees WHERE code = ?";
    $stmtNames =  $pdo -> prepare($sqlGetCoffee);
    $stmtNames -> execute([$code]);
    $coffees = $stmtNames -> fetchAll();
    foreach($coffees as $rowCheck){
        $basePriceOfCoffee = $rowCheck['price'];
        $nameCheckDup = $rowCheck['name'];
        //Maybe validate milk, cinn, choco values
    }
    //checking duplicates in cart, if there is one increase quantity and price
    $checkDupQuery = "SELECT coffee, sugar, sugarType, milk, cinnamon, choco, qty FROM cc_cart WHERE email = ? AND coffee = ? AND sugar = ? AND sugarType = ? AND milk = ? AND cinnamon = ? AND choco = ?";
    $stmtCheckDup = $pdo -> prepare($checkDupQuery);
    $stmtCheckDup -> execute([$email, $nameCheckDup, $sugar, $sugarType, $milk, $cinnamon, $choco]);
    if($stmtCheckDup -> rowCount() == 1){
        //Increase quantity if coffee is same
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
        foreach($coffees as $row){
            $name = $row['name'];
            $price = $row['price'];
            $code = $row['code'];
            if (isset($sugar)){
                $count++;
                $cart_query = "INSERT INTO cc_cart (email, count, code, coffee, sugar, sugarType, milk, cinnamon, choco, price, qty) VALUES( ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , 1)";
                $stmtCartInsert = $pdo -> prepare($cart_query);
                $stmtCartInsert -> execute([$email, $count, $code, $name, $sugar, $sugarType, $milk, $cinnamon, $choco, $price]);
            }
        }
        return true;
    }
    return false;
}

function manageQuantity($counter){
    global $pdo, $email;
    $sqlPlus = "SELECT code, price, qty FROM cc_cart WHERE email = ? AND count = ? LIMIT 1";
    $stmtPlus = $pdo -> prepare($sqlPlus);
    $stmtPlus -> execute([$email, $counter]);
    $row = $stmtPlus -> fetch();
    $codeCoffee = $row['code'];
    $sqlCode = "SELECT price FROM cc_coffees WHERE code = ?";
    $stmtGetPrice = $pdo -> prepare($sqlCode);
    $stmtGetPrice -> execute([$codeCoffee]);
    $rowGetPrice = $stmtGetPrice -> fetch();
    $price = $rowGetPrice['price'];
    if($_POST['qty'] === "minus") {
        $quantity = $row['qty'] - 1;
    }
    else if($_POST['qty'] === "plus"){
        $quantity = $row['qty'] + 1;
    }
    $newPrice = $price * $quantity;
    $sqlUpdate = "UPDATE cc_cart SET price = ?, qty = ? WHERE email = ? AND count = ?";
    $stmtUpdate = $pdo -> prepare($sqlUpdate);
    $stmtUpdate -> execute([$newPrice, $quantity, $email, $counter]);
    if($stmtUpdate){
        return true;
    }
    else{
        return false;
    }
}

function removeCoffeeFromCart($countToDelete){
    global $pdo, $email;
    $deleteQuery = "DELETE FROM cc_cart WHERE email = ? AND count = ?";
    $stmtDeleteCoffee = $pdo -> prepare($deleteQuery);
    $stmtDeleteCoffee -> execute([$email, $countToDelete]);
    if ($stmtDeleteCoffee) {
        return true;
    }
    else{
        return false;
    }
}
?>