<?php
require_once __DIR__ . "/Database.php";

class SportsJerseyModel extends Database
{
    /**
     * @throws Exception
     */
    public function getJerseys($limit = 10, $clientId = null): array
    {
        try {
            $query = "SELECT sj.*, 
                            c.name as country_name, 
                            cl.name as club_name 
                     FROM Sports_jersey sj 
                     LEFT JOIN Country c ON sj.idcountry = c.idcountry 
                     LEFT JOIN Club cl ON sj.idclub = cl.idclub 
                     WHERE sj.deleted IS NULL 
                     ORDER BY sj.iditem DESC 
                     LIMIT ?";
            
            $stmt = $this->connection->prepare($query);
            if (!$stmt) {
                throw new Exception("Error preparando consulta: " . $this->connection->error);
            }
            
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            $results = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            // Calcular precio final para cada jersey
            foreach ($results as &$jersey) {
                $jersey['precio_final'] = $this->calculateFinalPrice($jersey, $clientId);
            }
            
            return $results;
        } catch(Exception $e) {
            throw new Exception("Error al obtener las camisetas: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function getJerseyBySku(string $sku): array
    {
        return $this->select("SELECT * FROM Sports_jersey WHERE sku = ? AND deleted IS NULL", ["s", $sku]);
    }

    /**
     * @throws Exception
     */
    public function getJerseyById(int $id, $clientId = null): array
    {
        try {
            $query = "SELECT sj.*, 
                            c.name as country_name, 
                            cl.name as club_name 
                     FROM Sports_jersey sj 
                     LEFT JOIN Country c ON sj.idcountry = c.idcountry 
                     LEFT JOIN Club cl ON sj.idclub = cl.idclub 
                     WHERE sj.iditem = ? AND sj.deleted IS NULL";
            
            $stmt = $this->connection->prepare($query);
            if (!$stmt) {
                throw new Exception("Error preparando consulta: " . $this->connection->error);
            }
            
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if ($result) {
                $result['precio_final'] = $this->calculateFinalPrice($result, $clientId);
            }
            
            return $result ?: [];
        } catch(Exception $e) {
            throw new Exception("Error al obtener la camiseta: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function getJerseysByClub(int $clubId, $clientId = null): array
    {
        try {
            $query = "SELECT sj.*, 
                            c.name as country_name, 
                            cl.name as club_name 
                     FROM Sports_jersey sj 
                     LEFT JOIN Country c ON sj.idcountry = c.idcountry 
                     LEFT JOIN Club cl ON sj.idclub = cl.idclub 
                     WHERE sj.idclub = ? AND sj.deleted IS NULL";
            
            $stmt = $this->connection->prepare($query);
            if (!$stmt) {
                throw new Exception("Error preparando consulta: " . $this->connection->error);
            }
            
            $stmt->bind_param("i", $clubId);
            $stmt->execute();
            $result = $stmt->get_result();
            $results = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            // Calcular precio final para cada jersey
            foreach ($results as &$jersey) {
                $jersey['precio_final'] = $this->calculateFinalPrice($jersey, $clientId);
            }
            
            return $results;
        } catch(Exception $e) {
            throw new Exception("Error al obtener las camisetas por club: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function getJerseysByCountry(int $countryId, $clientId = null): array
    {
        try {
            $query = "SELECT sj.*, 
                            c.name as country_name, 
                            cl.name as club_name 
                     FROM Sports_jersey sj 
                     LEFT JOIN Country c ON sj.idcountry = c.idcountry 
                     LEFT JOIN Club cl ON sj.idclub = cl.idclub 
                     WHERE sj.idcountry = ? AND sj.deleted IS NULL";
            
            $stmt = $this->connection->prepare($query);
            if (!$stmt) {
                throw new Exception("Error preparando consulta: " . $this->connection->error);
            }
            
            $stmt->bind_param("i", $countryId);
            $stmt->execute();
            $result = $stmt->get_result();
            $results = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            // Calcular precio final para cada jersey
            foreach ($results as &$jersey) {
                $jersey['precio_final'] = $this->calculateFinalPrice($jersey, $clientId);
            }
            
            return $results;
        } catch(Exception $e) {
            throw new Exception("Error al obtener las camisetas por país: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function createJersey(array $data): array
    {
        $sql = "INSERT INTO Sports_jersey (title, color, idcountry, idclub, sku, price, type, description, created)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparando consulta: " . $this->connection->error);
        }

        $stmt->bind_param("ssiissds", 
            $data['title'],
            $data['color'],
            $data['idcountry'],
            $data['idclub'],
            $data['sku'],
            $data['price'],
            $data['type'],
            $data['description']
        );

        if (!$stmt->execute()) {
            throw new Exception("Error ejecutando consulta: " . $stmt->error);
        }

        $jerseyId = $this->connection->insert_id;
        $stmt->close();

        return ['id' => $jerseyId];
    }

    /**
     * @throws Exception
     */
    public function updateJersey(int $id, array $data): void
    {
        $sql = "UPDATE Sports_jersey SET 
                title = ?,
                color = ?,
                idcountry = ?,
                idclub = ?,
                sku = ?,
                price = ?,
                type = ?,
                description = ?,
                modified = NOW()
                WHERE iditem = ? AND deleted IS NULL";

        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparando consulta: " . $this->connection->error);
        }

        $stmt->bind_param("ssiissdsi", 
            $data['title'],
            $data['color'],
            $data['idcountry'],
            $data['idclub'],
            $data['sku'],
            $data['price'],
            $data['type'],
            $data['description'],
            $id
        );

        if (!$stmt->execute()) {
            throw new Exception("Error ejecutando consulta: " . $stmt->error);
        }

        $stmt->close();
    }

    /**
     * @throws Exception
     */
    public function deleteJersey(int $id): void
    {
        $sql = "UPDATE Sports_jersey SET deleted = NOW() WHERE iditem = ?";
        
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparando consulta: " . $this->connection->error);
        }

        $stmt->bind_param("i", $id);

        if (!$stmt->execute()) {
            throw new Exception("Error ejecutando consulta: " . $stmt->error);
        }

        $stmt->close();
    }

    /**
     * Calcula el precio final de una camiseta según el cliente
     * @throws Exception
     */
    private function calculateFinalPrice(array $jersey, ?string $clientId): float
    {
        $basePrice = $jersey['price'];
        
        // Si no hay cliente especificado, retornar el precio base
        if (!$clientId) {
            return $basePrice;
        }

        try {
            // Obtener el descuento desde la tabla Client_category
            $query = "SELECT discount FROM Client_category WHERE client_id = ?";
            $stmt = $this->connection->prepare($query);
            if (!$stmt) {
                throw new Exception("Error preparando consulta de descuento: " . $this->connection->error);
            }

            $stmt->bind_param("s", $clientId);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            // Si no se encuentra el cliente o no tiene descuento, retornar precio base
            if (!$result || !isset($result['discount'])) {
                return $basePrice;
            }

            // Aplicar el descuento
            $discount = floatval($result['discount']);
            return $basePrice * (1 - ($discount / 100));
        } catch(Exception $e) {
            // En caso de error, retornar el precio base
            return $basePrice;
        }
    }
} 