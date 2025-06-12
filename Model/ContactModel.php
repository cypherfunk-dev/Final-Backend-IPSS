<?php
require_once __DIR__ . "/Database.php";

class UserModel extends Database
{
    /**
     * @throws Exception
     */
    public function getContacts($limit): array
    {
        $limit = (int)$limit;
        return $this->select("SELECT * FROM Contact WHERE deleted IS NULL LIMIT $limit");
    }

    /**
     * @throws Exception
     */
    public function getContactByEmail(string $email): array
    {
        return $this->select("SELECT * FROM Contact WHERE email = :email AND deleted IS NULL", [":email" => $email]);
    }

    /**
     * @throws Exception  */
    public function getContactById(int $id): array
    {
        return $this->select("SELECT * FROM Contact WHERE idcontact = :id AND deleted IS NULL", [":id" => $id]);
    }

    /**
     * @throws Exception
     */
    public function createContact(array $data): array
    {
        $sql = "INSERT INTO Contact (first_name, last_name, email, phone, phone2, created)
                VALUES (:first_name, :last_name, :email, :phone, :phone2, NOW())";
        
        $this->insert($sql, [
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':email' => $data['email'],
            ':phone' => $data['phone'],
            ':phone2' => $data['phone2'] ?? null
        ]);

        $contactId = $this->lastInsertId();
        return ['id' => $contactId];
    }

    /**
     * @throws Exception
     */
    public function updateContact(int $id, array $data): void
    {
        $sql = "UPDATE Contact SET 
                first_name = :first_name,
                last_name = :last_name,
                email = :email,
                phone = :phone,
                phone2 = :phone2,
                modified = NOW()
                WHERE idcontact = :id AND deleted IS NULL";

        $this->insert($sql, [
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':email' => $data['email'],
            ':phone' => $data['phone'],
            ':phone2' => $data['phone2'] ?? null,
            ':id' => $id
        ]);
    }

    /**
     * @throws Exception
     */
    public function deleteContact(int $id): void
    {
        $sql = "UPDATE Contact SET deleted = NOW() WHERE idcontact = :id";
        $this->insert($sql, [':id' => $id]);
    }
}
