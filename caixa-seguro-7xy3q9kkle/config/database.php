<?php
class Database {
    private $host = "localhost";
    private $db_name = "u903648047_sis_caixa";
    private $username = "u903648047_juniior";
    private $password = "Ezequiel_2014";
    public $conn;
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            
            // ⏰ CONFIGURAR TIMEZONE DA SESSÃO - FUNCIONA NA HOSTINGER
            $this->conn->exec("SET SESSION time_zone = '-03:00'");
            
            // Configurar modo de erro
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            error_log("Conexão com banco de dados estabelecida com sucesso - Timezone: -03:00");
            
        } catch(PDOException $exception) {
            error_log("Erro de conexão: " . $exception->getMessage());
            echo "Erro de conexão: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}

// ⏰ CONFIGURAR TIMEZONE DO PHP TAMBÉM
date_default_timezone_set('America/Sao_Paulo');
?>