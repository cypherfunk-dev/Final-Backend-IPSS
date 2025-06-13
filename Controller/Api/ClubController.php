<?php
class ClubController {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function listAction() {
        try {
            $query = "SELECT * FROM Club";
            $stmt = $this->db->getConnection()->prepare($query);
            if (!$stmt) {
                throw new Exception("Error preparando consulta: " . $this->db->getConnection()->error);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $clubs = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            header('Content-Type: application/json');
            echo json_encode($clubs);
        } catch (Exception $e) {
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

            $query = "SELECT * FROM Club WHERE idclub = ?";
            $stmt = $this->db->getConnection()->prepare($query);
            if (!$stmt) {
                throw new Exception("Error preparando consulta: " . $this->db->getConnection()->error);
            }
            $stmt->bind_param('i', $_GET['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $club = $result->fetch_assoc();
            $stmt->close();

            if (!$club) {
                header("HTTP/1.1 404 Not Found");
                echo json_encode(["error" => "Club no encontrado"]);
                return;
            }

            header('Content-Type: application/json');
            echo json_encode($club);
        } catch (Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
            echo json_encode(["error" => "Error al obtener el club: " . $e->getMessage()]);
        }
    }

    public function createAction() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['name'])) {
                header("HTTP/1.1 400 Bad Request");
                echo json_encode(["error" => "Nombre del club no proporcionado"]);
                return;
            }

            $query = "INSERT INTO Club (name) VALUES (?)";
            $stmt = $this->db->getConnection()->prepare($query);
            if (!$stmt) {
                throw new Exception("Error preparando consulta: " . $this->db->getConnection()->error);
            }
            
            $stmt->bind_param('s', $data['name']);
            if (!$stmt->execute()) {
                throw new Exception("Error ejecutando consulta: " . $stmt->error);
            }

            $id = $stmt->insert_id;
            $stmt->close();

            header("HTTP/1.1 201 Created");
            echo json_encode([
                "message" => "Club creado exitosamente",
                "id" => $id
            ]);
        } catch (Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
            echo json_encode(["error" => "Error al crear el club: " . $e->getMessage()]);
        }
    }

    public function updateAction() {
        try {
            if (!isset($_GET['id'])) {
                header("HTTP/1.1 400 Bad Request");
                echo json_encode(["error" => "ID no proporcionado"]);
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['name'])) {
                header("HTTP/1.1 400 Bad Request");
                echo json_encode(["error" => "Nombre del club no proporcionado"]);
                return;
            }

            $query = "UPDATE Club SET name = ? WHERE idclub = ?";
            $stmt = $this->db->getConnection()->prepare($query);
            if (!$stmt) {
                throw new Exception("Error preparando consulta: " . $this->db->getConnection()->error);
            }

            $stmt->bind_param('si', $data['name'], $_GET['id']);
            if (!$stmt->execute()) {
                throw new Exception("Error ejecutando consulta: " . $stmt->error);
            }

            if ($stmt->affected_rows === 0) {
                header("HTTP/1.1 404 Not Found");
                echo json_encode(["error" => "Club no encontrado"]);
                return;
            }

            $stmt->close();
            header("HTTP/1.1 200 OK");
            echo json_encode(["message" => "Club actualizado exitosamente"]);
        } catch (Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
            echo json_encode(["error" => "Error al actualizar el club: " . $e->getMessage()]);
        }
    }

    public function deleteAction() {
        try {
            if (!isset($_GET['id'])) {
                header("HTTP/1.1 400 Bad Request");
                echo json_encode(["error" => "ID no proporcionado"]);
                return;
            }

            $query = "DELETE FROM Club WHERE idclub = ?";
            $stmt = $this->db->getConnection()->prepare($query);
            if (!$stmt) {
                throw new Exception("Error preparando consulta: " . $this->db->getConnection()->error);
            }

            $stmt->bind_param('i', $_GET['id']);
            if (!$stmt->execute()) {
                throw new Exception("Error ejecutando consulta: " . $stmt->error);
            }

            if ($stmt->affected_rows === 0) {
                header("HTTP/1.1 404 Not Found");
                echo json_encode(["error" => "Club no encontrado"]);
                return;
            }

            $stmt->close();
            header("HTTP/1.1 200 OK");
            echo json_encode(["message" => "Club eliminado exitosamente"]);
        } catch (Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
            echo json_encode(["error" => "Error al eliminar el club: " . $e->getMessage()]);
        }
    }
} 