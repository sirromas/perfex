<?php

class pdo_db
{

    private $databaseName;
    private $host;
    private $user;
    private $password;
    private $db;

    /**
     * pdo_db constructor.
     */
    function __construct()
    {
        $path = $_SERVER['DOCUMENT_ROOT'] . './db.xml';
        $config_data = file_get_contents($path);
        $config = new SimpleXMLElement($config_data);
        $this->databaseName = $config->db_name;
        $this->host = $config->db_host;
        $this->user = $config->db_user;
        $this->password = $config->db_pwd;
        $dsn = "mysql:dbname=$this->databaseName;host=$this->host";
        try {
            $db = new PDO($dsn, $this->user, $this->password);
            $this->db = $db;
        } catch (PDOException $e) {
            echo 'Connection failed: ' . $e->getMessage();
        }
    }

    /**
     * @param $query
     * @return int
     */
    public function numrows($query)
    {
        //echo "Query: ".$query."<br/>";
        $result = $this->db->query($query);
        return $result->rowCount();
    }

    /**
     * @param $query
     * @return PDOStatement
     */
    public function query($query)
    {
        //echo "Query: ".$query."<br/>";
        return $this->db->query($query);
    }
}