<?php
require_once __DIR__ . "/Database.php";

class ClubModel extends Database
{
    /**
     * @throws Exception
     */
    public function getClubs($limit): array
    {
        $limit = (int)$limit;
        return $this->select("SELECT * FROM Club WHERE deleted IS NULL LIMIT $limit");
    }

    /**
     * @throws Exception
     */
    public function getClubById(int $id): array
    {
        return $this->select("SELECT * FROM Club WHERE idclub = :id AND deleted IS NULL", [":id" => $id]);
    }

    /**
     * @throws Exception
     */
    public function getClubByName(string $name): array
    {
        return $this->select("SELECT * FROM Club WHERE name = :name AND deleted IS NULL", [":name" => $name]);
    }

    /**
     * @throws Exception
     */
    public function createClub(array $data): array
    {
        $sql = "INSERT INTO Club (name, created) VALUES (:name, NOW())";
        
        $this->insert($sql, [
            ':name' => $data['name']
        ]);

        $clubId = $this->lastInsertId();
        return ['id' => $clubId];
    }

    /**
     * @throws Exception
     */
    public function updateClub(int $id, array $data): void
    {
        $sql = "UPDATE Club SET name = :name, modified = NOW() WHERE idclub = :id AND deleted IS NULL";

        $this->insert($sql, [
            ':name' => $data['name'],
            ':id' => $id
        ]);
    }

    /**
     * @throws Exception
     */
    public function deleteClub(int $id): void
    {
        $sql = "UPDATE Club SET deleted = NOW() WHERE idclub = :id";
        $this->insert($sql, [':id' => $id]);
    }
} 