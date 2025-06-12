<?php
require_once __DIR__ . "/Database.php";

class BusinessModel extends Database
{
    /**
     * @throws Exception
     */
    public function getBusiness($limit): array
    {
        $limit = (int)$limit;
        return $this->select("SELECT * FROM Business LIMIT $limit");
    }

    /**
     * @throws Exception
     */
    public function getBusinessByName(string $name): array
    {
        return $this->select("SELECT * FROM Business WHERE name = :name", [":name" => $name]);
    }

    /**
     * @throws Exception
     */
    public function getBusinessById(int $id): array
    {
        return $this->select("SELECT * FROM Business WHERE id = :id", [":id" => $id]);
    }

    /**
     * @throws Exception
     */
    public function createBusiness(array $data): array
    {
        $sqlBusiness = "INSERT INTO Business (tax_id_number, business_name, business_address, offer_percentage, idcontact, categoryid)
                    VALUES (:tax_id, :name, :address, :offer, :idcontact, :categoryid)";
        $this->insert($sqlBusiness, [
            ':tax_id'     => $data['tax_id_number'],
            ':name'       => $data['business_name'],
            ':address'    => $data['business_address'],
            ':offer'      => $data['offer_percentage'],
            ':idcontact'  => $data['idcontact'],
            ':categoryid' => $data['categoryid'],
        ]);

        $businessId = $this->lastInsertId();
        return ['id' => $businessId];
    }

    public function updateBusiness(array $data): array
    {
        $sqlBusiness = "UPDATE Business SET business_name = :name, business_address = :address, offer_percentage = :offer, categoryid = :categoryid WHERE id = :id";
        $this->update($sqlBusiness, [
            ':name' => $data['business_name'],
        ]);
    }

    public function deleteBusiness(int $id): array
    {
        $sqlBusiness = "DELETE FROM Business WHERE id = :id";
        $this->delete($sqlBusiness, [
            ':id' => $id,
        ]);
    }
}


