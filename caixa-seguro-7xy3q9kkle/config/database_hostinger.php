<?php
class Database {
    // Configurações para Hostinger
    private $host = "localhost";
    private $db_name = "u903648047_sis_restaurant";
    private $username = "u903648047_junior";
    private $password = "Ezequiel_2014"; // COLOQUE SUA SENHA AQUI
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4", 
                $this->username, 
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            error_log("Conexão com banco Hostinger estabelecida com sucesso");
        } catch(PDOException $exception) {
            error_log("Erro de conexão Hostinger: " . $exception->getMessage());
            die("Erro de conexão com o banco de dados. Contate o suporte.");
        }
        return $this->conn;
    }
}
?>