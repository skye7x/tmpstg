<?php 

include 'connect.php';

if(isset($_POST['signUp'])){
    $firstName = trim($_POST['fName']);
    $lastName=trim($_POST['lName']);
    $email=trim($_POST['email']);
    $password=$_POST['password'];

    if($firstName === '' || mb_strlen($firstName) > 50 || $lastName === '' || mb_strlen($lastName) > 50 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254 || strlen($password) < 10 || strlen($password) > 128){
        echo "Invalid input. Maybe check password length?";
        echo ' <a href="/tmpStg/" style="
            background-color: black;
            color: yellow;
            padding: 2px 10px;
            text-decoration: none;
            display: inline-block;
            font-size: 13px;
        ">Register again here</a> !';
        exit;
    }
    // SQL Injection prevention
    /*
    $checkEmail="SELECT * From users where email='$email'";
    $result=$conn->query($checkEmail);
    */
    $stmt=$conn->prepare("SELECT * From users where email=?"); // Przygotowanie zapytania SQL z parametrem
    $stmt->bind_param("s",$email); // Przypisanie wartości zmiennej $email do parametru w zapytaniu
    $stmt->execute(); // Wykonanie zapytania SQL
    $result=$stmt->get_result(); // GET i SET wyników zapytania


    
    $password=password_hash($password, PASSWORD_DEFAULT);


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