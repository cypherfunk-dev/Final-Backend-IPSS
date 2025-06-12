<?php
require_once __DIR__ . "/Database.php";

class CountryModel extends Database
{
    /**
     * @throws Exception
     */
    public function getCountries($limit): array
    {
        $limit = (int)$limit;
        return $this->select("SELECT * FROM Country WHERE deleted IS NULL LIMIT $limit");
    }

    /**
     * @throws Exception
     */
    public function getCountryById(int $id): array
    {
        return $this->select("SELECT * FROM Country WHERE idcountry = :id AND deleted IS NULL", [":id" => $id]);
    }

    /**
     * @throws Exception
     */
    public function getCountryByName(string $name): array
    {
        return $this->select("SELECT * FROM Country WHERE name = :name AND deleted IS NULL", [":name" => $name]);
    }

    /**
     * @throws Exception
     */
    public function createCountry(array $data): array
    {
        $sql = "INSERT INTO Country (name, created) VALUES (:name, NOW())";
        
        $this->insert($sql, [
            ':name' => $data['name']
        ]);

        $countryId = $this->lastInsertId();
        return ['id' => $countryId];
    }

    /**
     * @throws Exception
     */
    public function updateCountry(int $id, array $data): void
    {
        $sql = "UPDATE Country SET name = :name, modified = NOW() WHERE idcountry = :id AND deleted IS NULL";

        $this->insert($sql, [
            ':name' => $data['name'],
            ':id' => $id
        ]);
    }

    /**
     * @throws Exception
     */
    public function deleteCountry(int $id): void
    {
        $sql = "UPDATE Country SET deleted = NOW() WHERE idcountry = :id";
        $this->insert($sql, [':id' => $id]);
    }
} 