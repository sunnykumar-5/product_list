<?php 
$host = 'localhost';
$db = 'products';
$user = 'root';
$pass = '';

 

$query = "select * from order_items";
$result = mysqli_query($conn,$query);

if($result){
    while($row = mysqli_fetch_assoc($result)){
        echo $row['order_id'] . "<br>";
        echo $row['product_name'] . "<br>";
        echo $row['weight'] . "<br>";
        echo $row['price'] . "<br>";
    }
}



?>