<?php 

include 'connect.php';

if(isset($_POST['signUp'])){
    $firstName=$_POST['fName'];
    $lastName=$_POST['lName'];
    $email=$_POST['email'];
    $password=$_POST['password'];
    $password=md5($password);

    // SQL Injection prevention
    /*
    $checkEmail="SELECT * From users where email='$email'";
    $result=$conn->query($checkEmail);
    */
    $stmt=$conn->prepare("SELECT * From users where email=?"); // Przygotowanie zapytania SQL z parametrem
    $stmt->bind_param("s",$email); // Przypisanie wartości zmiennej $email do parametru w zapytaniu
    $stmt->execute(); // Wykonanie zapytania SQL
    $result=$stmt->get_result(); // GET i SET wyników zapytania

    if($result->num_rows>0){
        echo "Email Address Already Exists !";
    }
    else{
        $stmt2 = $conn->prepare("INSERT INTO users(firstName,lastName,email,password) VALUES (?, ?, ?, ?)");
        $stmt2->bind_param("ssss", $firstName, $lastName, $email, $password); // 4x s bo 4 warsci tekstweo
        //$stmt2->execute();
        /*
        $insertQuery="INSERT INTO users(firstName,lastName,email,password)
                       VALUES ('$firstName','$lastName','$email','$password')";
        */
                       // INSERT zwraca boola TYLKO do metody execute!
        if($stmt2->execute() == TRUE){
            header("location: /tmpstg/");
            exit;
        }
        else{
            echo "Error:".$stmt2->error;
        }
     }
   

}

if(isset($_POST['signIn'])){
   $email=$_POST['email'];
   $password=$_POST['password'];
   $password=md5($password) ;

   // SQL Injection prevention
   /*
   $sql="SELECT * FROM users WHERE email='$email' and password='$password'";
   $result=$conn->query($sql);
   */
    $stmt3=$conn->prepare("SELECT * FROM users WHERE email=? and password=?"); // Przygotowanie zapytania SQL z parametrami
    $stmt3->bind_param("ss",$email,$password); // Przypisanie wartości zmiennych $email i $password do parametrów w zapytaniu
    $stmt3->execute(); // Wykonanie zapytania SQL
    $result3=$stmt3->get_result(); // GET i SET wyników zapytania

   if($result3->num_rows>0){
    session_start();
    $row=$result3->fetch_assoc();
    $_SESSION['email']=$row['email'];
    header("Location: homepage.php");
    exit();
   }
   else{
    echo "Not Found, Incorrect Email or Password";
   }

}
?>