<?php
require_once __DIR__ . "/Database.php";

class SizeAvailabilityModel extends Database
{
    /**
     * @throws Exception
     */
    public function getAvailabilities($limit): array
    {
        $limit = (int)$limit;
        return $this->select("SELECT * FROM Size_availability WHERE deleted IS NULL LIMIT $limit");
    }

    /**
     * @throws Exception
     */
    public function getAvailabilityById(int $id): array
    {
        return $this->select("SELECT * FROM Size_availability WHERE idavailability = :id AND deleted IS NULL", [":id" => $id]);
    }

    /**
     * @throws Exception
     */
    public function getAvailabilitiesByItem(int $itemId): array
    {
        return $this->select("SELECT * FROM Size_availability WHERE iditem = :itemId AND deleted IS NULL", [":itemId" => $itemId]);
    }

    /**
     * @throws Exception
     */
    public function getAvailabilitiesBySize(int $sizeId): array
    {
        return $this->select("SELECT * FROM Size_availability WHERE idsize = :sizeId AND deleted IS NULL", [":sizeId" => $sizeId]);
    }

    /**
     * @throws Exception
     */
    public function createAvailability(array $data): array
    {
        $sql = "INSERT INTO Size_availability (iditem, idsize, stock, created) 
                VALUES (:iditem, :idsize, :stock, NOW())";
        
        $this->insert($sql, [
            ':iditem' => $data['iditem'],
            ':idsize' => $data['idsize'],
            ':stock' => $data['stock']
        ]);

        $availabilityId = $this->lastInsertId();
        return ['id' => $availabilityId];
    }

    /**
     * @throws Exception
     */
    public function updateAvailability(int $id, array $data): void
    {
        $sql = "UPDATE Size_availability 
                SET stock = :stock, modified = NOW() 
                WHERE idavailability = :id AND deleted IS NULL";

        $this->insert($sql, [
            ':stock' => $data['stock'],
            ':id' => $id
        ]);
    }

    /**
     * @throws Exception
     */
    public function updateStock(int $id, int $quantity): void
    {
        $sql = "UPDATE Size_availability 
                SET stock = stock + :quantity, modified = NOW() 
                WHERE idavailability = :id AND deleted IS NULL";

        $this->insert($sql, [
            ':quantity' => $quantity,
            ':id' => $id
        ]);
    }

    /**
     * @throws Exception
     */
    public function deleteAvailability(int $id): void
    {
        $sql = "UPDATE Size_availability SET deleted = NOW() WHERE idavailability = :id";
        $this->insert($sql, [':id' => $id]);
    }

    /**
     * @throws Exception
     */
    public function checkStock(int $itemId, int $sizeId): array
    {
        return $this->select(
            "SELECT stock FROM Size_availability 
            WHERE iditem = :itemId AND idsize = :sizeId AND deleted IS NULL",
            [
                ':itemId' => $itemId,
                ':sizeId' => $sizeId
            ]
        );
    }
} 