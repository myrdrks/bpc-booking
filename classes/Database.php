<?php
/**
 * Datenbank-Klasse für Raumbuchungssystem
 */
class Database {
    private static $instance = null;
    private $pdo;
    
    /**
     * Singleton Pattern - nur eine Datenbankverbindung
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Konstruktor - stellt Datenbankverbindung her
     */
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            logError('Datenbankverbindung fehlgeschlagen: ' . $e->getMessage());
            throw new Exception('Datenbankverbindung konnte nicht hergestellt werden.');
        }
    }
    
    /**
     * PDO-Instanz abrufen
     */
    public function getConnection() {
        return $this->pdo;
    }
    
    /**
     * Query ausführen
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            logError('Query fehlgeschlagen: ' . $e->getMessage(), ['sql' => $sql, 'params' => $params]);
            throw $e;
        }
    }
    
    /**
     * Einzelne Zeile abrufen
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Alle Zeilen abrufen
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Einfügen und ID zurückgeben
     */
    public function insert($sql, $params = []) {
        $this->query($sql, $params);
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Transaktion starten
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Transaktion bestätigen
     */
    public function commit() {
        return $this->pdo->commit();
    }
    
    /**
     * Transaktion zurückrollen
     */
    public function rollBack() {
        return $this->pdo->rollBack();
    }
}
