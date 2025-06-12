<?php
require_once __DIR__ . "/Database.php";

class SizeModel extends Database
{
    /**
     * @throws Exception
     */
    public function getSizes($limit): array
    {
        $limit = (int)$limit;
        return $this->select("SELECT * FROM Size WHERE deleted IS NULL LIMIT $limit");
    }

    /**
     * @throws Exception
     */
    public function getSizeById(int $id): array
    {
        return $this->select("SELECT * FROM Size WHERE idsize = :id AND deleted IS NULL", [":id" => $id]);
    }

    /**
     * @throws Exception
     */
    public function getSizeByName(string $name): array
    {
        return $this->select("SELECT * FROM Size WHERE name = :name AND deleted IS NULL", [":name" => $name]);
    }

    /**
     * @throws Exception
     */
    public function createSize(array $data): array
    {
        $sql = "INSERT INTO Size (name, created) VALUES (:name, NOW())";
        
        $this->insert($sql, [
            ':name' => $data['name']
        ]);

        $sizeId = $this->lastInsertId();
        return ['id' => $sizeId];
    }

    /**
     * @throws Exception
     */
    public function updateSize(int $id, array $data): void
    {
        $sql = "UPDATE Size SET name = :name, modified = NOW() WHERE idsize = :id AND deleted IS NULL";

        $this->insert($sql, [
            ':name' => $data['name'],
            ':id' => $id
        ]);
    }

    /**
     * @throws Exception
     */
    public function deleteSize(int $id): void
    {
        $sql = "UPDATE Size SET deleted = NOW() WHERE idsize = :id";
        $this->insert($sql, [':id' => $id]);
    }
} 