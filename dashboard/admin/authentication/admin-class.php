<?php 
    require_once __DIR__. '/../../../database/dbconnection.php';
    include_once __DIR__. '/../../../config/settings-configuration.php';

    class ADMIN
        {
        private $conn;
        public function __construct(){

            $database = new Database();
            $this->conn = $database->dbConnection();
        }

        public function addAdmin($csfr_token, $username, $email, $password){
            $stmt = $this->conn->prepare("SELECT * FROM user WHERE email = :email");
            $stmt->execute(array(":email => $email"));

            if($stmt->rowCount() > 0){
                echo "<script>alert('Email already exists.'); window.location.href = '../../../';</script>";
                exit;
            }

            if(!isset($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)){
                echo "<script>alert('Invalid CSRF token.'); window.location.href = '../../../';</script>";
                exit;
            }

            unset($_SESSION['csrf_token']);

            $hash_password = md5($password);
            
            $stmt = $this->runQuery('INSERT INTO user (username, email, password) VALUES(:username, :email, :password)');
            $exec = $stmt->execute(array(
                ":username" => $username,
                ":email" => $email,
                ":password" => $hash_password
            ));

            if($exec){
                echo "<script>alert('Admin Added Successfully.'); window.location.href = '../../../';</script>";
                exit;
            } else {
                echo "<script>alert('Failed to add admin.'); window.location.href = '../../../';</script>";
                exit;
            }

        }

        public function adminSignin($email, $password, $csfr_token){
            try{
                if(!isset($csrf_token) || !hash_equals($_SESSION['csrf_token'], $csrf_token)){
                    echo "<script>alert('Invalid CSRF token.'); window.location.href = '../../../';</script>";
                    exit;
                }
                unset($_SESSION['csrf_token']);
                
                $stmt = $this->conn->prepare('SELECT * FROM user WHERE email = :email');
                $stmt->execute(array(":email => $email"));
                $userRow = $stmt->fetch(PDO::FETCH_ASSOC); 

                if($stmt->rowCount() == 1 && $userRow['password'] == md5($password)){
                    $activity = "Has Successfully Signed In";
                    $user_ID = $userRow['id'];
                    $this->logs($activity, $user_ID);

                    $_SESSION['adminSession'] = $user_ID;
                    echo "<script>alert('Welcome.'); window.location.href = '../';</script>";
                    exit;
                }else{
                    echo "<script>alert('Invalid Credentials.'); window.location.href = '../../../';</script>";
                    exit;
                }
                
            }catch(PDOException $ex) {
                echo $ex->getMessage();
            
        }

    }


        public function adminSignout(){
            unset($_SESSION['adminSession']);
            echo "<script>alert('Sign Out Successfully.'); window.location.href = '../../../';</script>";
                    exit;
        }

        public function logs($activity, $user_ID){
            $stmt = $this->conn->prepare("INSERT INTO logs (user_ID, activity) VALUES (:user_ID, :activity)");
            $stmt->execute(array(":user_ID" => $user_ID, ":activity" => $activity ));
        }

        public function isUserLoggedIn(){
            if(isset($_SESSION['adminSession'])){
                return true;
            }
        }

        public function redirect(){
            echo "<script>alert('Admin must login first'); window.location.href = '../../../';</script>";
                    exit;
        }

        public function runQuery($sql){
            $stmt = $this->conn->prepare($sql);
            return $stmt;

        }

    }

    if(isset($_POST['btn_signup'])){
        $csfr_token = trim($_POST['csfr_token']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        
        $addADMIN = new ADMIN();
        $addADMIN->addAdmin($csfr_token, $username, $email, $password);
    }

    if(isset($_POST['btn_signin'])){
        $csfr_token = trim($_POST['csfr_token']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        $adminSignIn = new ADMIN();
        $adminSignIn->adminSignin( $csfr_token, $email, $password);
    }

    if(isset($_GET['admin_signout'])){

        $adminSignOut = new ADMIN();
        $adminSignOut-> adminSignout();
    }

?>