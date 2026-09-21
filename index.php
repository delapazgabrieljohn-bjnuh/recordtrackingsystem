<?php
include("./connection/config.php");
include("./helpers/SystemOperators.php");

$con = connection();
$so = new SystemOperators();

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnSubmit'])){
    $uid = $so->encrypt(filter_input(INPUT_POST,'uid', FILTER_SANITIZE_SPECIAL_CHARS));
    $fname = $so->encrypt(filter_input(INPUT_POST,'firstname', FILTER_SANITIZE_SPECIAL_CHARS));
    $lname = $so->encrypt(filter_input(INPUT_POST,'lastname', FILTER_SANITIZE_SPECIAL_CHARS));
    $address = $so->encrypt(filter_input(INPUT_POST,'address', FILTER_SANITIZE_SPECIAL_CHARS));

    $select_query = "SELECT * FROM `users` WHERE `user_id` = ?";
    $select_stmt = $con->prepare($select_query);
    $select_stmt->bind_param('s', $uid);

    if($select_stmt->execute()){
        $result = $select_stmt->get_result();
        if($result->num_rows > 0){
            echo "<script> alert('User exist');</script>";
        }else{
            $insert_query = 'INSERT INTO `users` (`user_id`,`firstname`, `lastname`, `address`) VALUES (?, ?, ?, ?)';
            $inset_stmt = $con->prepare($insert_query);
            $inset_stmt->bind_param('ssss',$uid,$fname,$lname,$address);
            try{
                $inset_stmt->execute();
                echo "<script> alert('User information inserted successfully!');
                                window.location='index.php';
                </script>";
            }catch(mysqli_sql_exception $e){
                echo $e->getMessage();
            }
        }
    }else{
        echo "Failed to execute";
        $select_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form action="" method="post">
        <div class="form-fields">
            <div class="form-items">
                <label for="">Enter User ID:</label>
                <input type="text" name="uid" required placeholder="ID:001">
            </div>
            <div class="form-items">
                <label for="">Enter Firstname:</label>
                <input type="text" name="firstname" required>
            </div>
            <div class="form-items">
                <label for="">Enter Lastname:</label>
                <input type="text" name="lastname" required>
            </div>
            <div class="form-items">
                <label for="">Enter Address:</label>
                <input type="text" name="address" required>
            </div>
            <button type="submit" name="btnSubmit"> Submit</button>
        </div>
    </form>

    <table>
        <thead>
            <tr>
                <th>User ID</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Address</th>
            </tr>
        </thead>
        <tbody>
           
                <?php 
                $select_users = "SELECT * FROM `users`";
                $select_users_stmt = $con->prepare($select_users);
                $select_users_stmt->execute();
                $userlist = $select_users_stmt->get_result();

                while($row = $userlist->fetch_assoc()){
                    $userid = $so->decrypt($row['user_id']);
                    $fname = $so->decrypt($row['firstname']);
                    $lname = $so->decrypt($row['lastname']);
                    $address = $so->decrypt($row['address']);
                ?>
                 <tr>
                    <td><?php echo $userid;?></td>
                    <td><?php echo $fname;?></td>
                    <td><?php echo $lname;?></td>
                    <td><?php echo $address;?></td>
                </tr>
                <?php }?>
            
        </tbody>
    </table>
</body>
</html>