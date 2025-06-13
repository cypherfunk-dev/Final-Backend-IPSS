<?php
class Database
{
    protected ?mysqli $connection = null;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        try {
            $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

            if (mysqli_connect_errno()) {
                throw new Exception("Could not connect to database" . $this->connection->connect_error);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function insert(string $query, array $params = []): void
    {
        $stmt = $this->connection->prepare($query);
        if (!$stmt) {
            throw new Exception("Error preparando consulta: " . $this->connection->error);
        }

        // Si hay parámetros, vincúlalos
        if (!empty($params)) {
            $types = str_repeat('s', count($params)); // todos string (puedes mejorar esto si lo necesitás)
            $stmt->bind_param($types, ...array_values($params));
        }

        if (!$stmt->execute()) {
            throw new Exception("Error ejecutando: " . $stmt->error);
        }

        $stmt->close();

    }


    public function lastInsertId(): int
    {
        return $this->connection->insert_id;
    }


    /**
     * @throws Exception
     */
    public function select($query = "", $params = []): array
    {
        try {
            $stmt = $this->executeStatement($query, $params);
            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            return $result;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    private function executeStatement($query = "", $params = []): mysqli_stmt
    {
        try {
            $stmt = $this->connection->prepare($query);
            if ($stmt === false) {
                throw new Exception("Unable to do prepared statement: " . $query . $this->connection->error);
            }
            if ($params) {
                $stmt->bind_param($params[0], $params[1]);
            }
            $stmt->execute();
            return $stmt;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getConnection(): mysqli
    {
        return $this->connection;
    }
}
