
<?php
session_start();
include("connect.php");

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>tmpStg</title>
</head>
<body>
    <div style="text-align:center; padding:15%;">
      <p  style="font-size:50px; font-weight:bold;">
       Hello  <?php 
       if(isset($_SESSION['email'])){
        $email=$_SESSION['email'];


        // $query=mysqli_query($conn, "SELECT users.* FROM `users` WHERE users.email='$email'"); SQLI prevention steps 3
        $stmt = $conn->prepare("SELECT users.* FROM `users` WHERE users.email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $assc = $result->fetch_assoc();

        echo $assc['firstname'].' '.$assc['lastname'];
        //test
        //<button onclick="$result = $stmt->get_result();">Debug</button>
        //print_r($result->fetch_assoc()); 
        //    echo "<pre>";
        //    print_r($result->fetch_assoc());
        //    echo "</pre>";
        /*
        if (isset($_POST['debug'])) {
            echo "<pre>";
            print_r($assc);
            echo "</pre>";
        } */
       }
       ?>

<!--
        <form method="POST">
            <button type="submit" name="debug">Debug</button>
        </form>
-->

      </p>
        <a href="logout.php">Logout</a>
    </div>
</body>
</html>
