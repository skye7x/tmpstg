
<?php 

include 'connect.php';

if(isset($_POST['signIn'])){
   $email=$_POST['email']; //Hasło zostaje surowe, bez haszowania.
   $password=$_POST['password'];

   // SQL Injection prevention
   /*
   $sql="SELECT * FROM users WHERE email='$email' and password='$password'";
   $result=$conn->query($sql);
   */
    // szukanie usera po mailu
    $stmt3=$conn->prepare("SELECT * FROM users WHERE email=?"); // Przygotowanie zapytania SQL z parametrami
    $stmt3->bind_param("s",$email); // Przypisanie wartości zmiennych $email i $password do parametrów w zapytaniu
    $stmt3->execute(); // Wykonanie zapytania SQL
    $result3=$stmt3->get_result(); // GET i SET wyników zapytania
    //print_r($result3);
    
    $row=$result3->fetch_assoc(); // $row to tablica asocjacyjna => ['passsword'] -> xxxxxxxxxx

    // function password_verify(#[\SensitiveParameter] string $password, string $hash): bool
    // znaleziono użytkownika po emailu: $row to tabelka z danymi (to liczy się jako “prawda”),

    // z row bierzemy osozcwglone wartosci id mail paswd..
    if($row != NULL) {
        $hash = $row['password'];

        //if( $row && password_verify($password, $hash) ){
        // https://www.php.net/manual/en/function.password-verify.php
        if(password_verify($password, $hash) == TRUE ){
            session_start();
            session_regenerate_id(true); // nowy nr sesji PO JEJ OTWARCIU
            // $_SESSION['email']=$row['email']; ID ZAMIAST MAILA

            $id=$row['id'];
            $_SESSION['usrid']= $id;

            header("Location: homepage.php");
            exit();
        }
        else{
        echo "Not Found, Incorrect Email or Password";
        }
    }
    else{
        echo "Not Found, Incorrect Email or Password";
    }

}
