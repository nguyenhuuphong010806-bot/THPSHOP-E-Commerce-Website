<?php 
    /* Lớp Database xử lý kết nối MySQL cơ bản */
    class Database {
        private $host = "localhost";
        private $username = "root";
        private $password = "";
        private $dbname = "web";
        public $conn;
        
        // Khởi tạo kết nối MySQLi
        public function __construct() {
            $this->conn = new mysqli ($this->host, $this->username, $this->password, $this->dbname);
            if ($this->conn->connect_error) {
                die("Kết nối thất bại: ". $this->conn->connect_error );
            }
            $this->conn->set_charset("utf8");
        }
        
        // Query SELECT dữ liệu
        public function select ($sql) {
            return $this->conn->query($sql);
        }
        
        // Thực hiện INSERT/UPDATE/DELETE
        public function execute($sql) {
            return $this->conn->query($sql);
        }
        
        // Prepared statement
        public function prepare($sql) {
            return $this->conn->prepare($sql);
        }
        
        // Đóng kết nối
        public function close(){
            $this->conn->close();
        }
    }
?>