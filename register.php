<?php 

include 'connect.php';

if(isset($_POST['signUp'])){
    $firstName=$_POST['fName'];
    $lastName=$_POST['lName'];
    $email=$_POST['email'];
    $password=$_POST['password'];
    $password=password_hash($password, PASSWORD_DEFAULT);

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
?>