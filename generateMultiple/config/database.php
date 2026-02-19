<?php
class Database
{
    //private $host = "localhost";
    //private $db_name = "topicosweb";
    //private $username = "db_21031190";
    //private $password = "21031190";

    private $host = "127.0.0.1";
    private $db_name = "tap";
    private $username = "root";
    private $password = "rober";

   


    public $conn;

    public function getConnection()
    {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch (PDOException $e) {
            echo "Error de conexión: " . $e->getMessage();
        }
        return $this->conn;
    }
}
?>