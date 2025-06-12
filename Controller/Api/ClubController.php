<?php
class ClubController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function listAction() {
        try {
            $query = "SELECT c.*, co.name as country_name 
                     FROM Club c 
                     LEFT JOIN Country co ON c.country_id = co.id";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $clubs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode($clubs);
        } catch (PDOException $e) {
            header("HTTP/1.1 500 Internal Server Error");
            echo json_encode(["error" => "Error al obtener los clubes: " . $e->getMessage()]);
        }
    }

    public function getAction() {
        try {
            if (!isset($_GET['id'])) {
                header("HTTP/1.1 400 Bad Request");
                echo json_encode(["error" => "ID no proporcionado"]);
                return;
            }

            $query = "SELECT c.*, co.name as country_name 
                     FROM Club c 
                     LEFT JOIN Country co ON c.country_id = co.id 
                     WHERE c.id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $_GET['id'], PDO::PARAM_INT);
            $stmt->execute();
            $club = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$club) {
                header("HTTP/1.1 404 Not Found");
                echo json_encode(["error" => "Club no encontrado"]);
                return;
            }

            header('Content-Type: application/json');
            echo json_encode($club);
        } catch (PDOException $e) {
            header("HTTP/1.1 500 Internal Server Error");
            echo json_encode(["error" => "Error al obtener el club: " . $e->getMessage()]);
        }
    }

    public function createAction() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['name']) || !isset($data['country_id'])) {
                header("HTTP/1.1 400 Bad Request");
                echo json_encode(["error" => "Nombre del club o ID del país no proporcionados"]);
                return;
            }

            $query = "INSERT INTO Club (name, country_id) VALUES (:name, :country_id)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindParam(':country_id', $data['country_id'], PDO::PARAM_INT);
            $stmt->execute();

            $id = $this->db->lastInsertId();
            header("HTTP/1.1 201 Created");
            echo json_encode([
                "message" => "Club creado exitosamente",
                "id" => $id
            ]);
        } catch (PDOException $e) {
            header("HTTP/1.1 500 Internal Server Error");
            echo json_encode(["error" => "Error al crear el club: " . $e->getMessage()]);
        }
    }
} 