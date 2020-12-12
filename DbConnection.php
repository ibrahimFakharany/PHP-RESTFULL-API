<?php

/**
 * Database connection
 */
class DbConnection
{

        /*private $server ="localhost";
        private $dbname = 'jwtapi';
        private $user = 'root';
        private $pass = '';*/

        private $server ="207.154.213.142";
        private $dbname = 'ceramica';
        private $user = 'ceramica';
        private $pass = 'pH45oWuUd8MmS5nLO5rG1b';
        public function connect(){

            try{$conn  = new PDO('mysql:host='. $this->server.';dbname='.$this->dbname, $this->user,
                $this->pass);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                return $conn;
            }catch (Exception $e){
                echo 'database error '. $e->getMessage();
            }
        }
}

?>