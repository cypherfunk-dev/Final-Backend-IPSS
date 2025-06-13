<?php
class CountryController
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function listAction()
    {
        try {
            $query = "SELECT * FROM Country";
            $stmt = $this->db->getConnection()->prepare($query);
            if (!$stmt) {
                throw new Exception("Error preparando consulta: " . $this->db->getConnection()->error);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $countries = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            header('Content-Type: application/json');
            echo json_encode($countries);
        } catch (Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
            echo json_encode(["error" => "Error al obtener los países: " . $e->getMessage()]);
        }
    }

    public function getAction()
    {
        try {
            if (!isset($_GET['id'])) {
                header("HTTP/1.1 400 Bad Request");
                echo json_encode(["error" => "ID no proporcionado"]);
                return;
            }

            $query = "SELECT * FROM Country WHERE idcountry = ?";
            $stmt = $this->db->getConnection()->prepare($query);
            if (!$stmt) {
                throw new Exception("Error preparando consulta: " . $this->db->getConnection()->error);
            }
            $stmt->bind_param('i', $_GET['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $country = $result->fetch_assoc();
            $stmt->close();

            if (!$country) {
                header("HTTP/1.1 404 Not Found");
                echo json_encode(["error" => "País no encontrado"]);
                return;
            }

            header('Content-Type: application/json');
            echo json_encode($country);
        } catch (Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
            echo json_encode(["error" => "Error al obtener el país: " . $e->getMessage()]);
        }
    }

    public function createAction()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['name'])) {
                header("HTTP/1.1 400 Bad Request");
                echo json_encode(["error" => "Nombre del país no proporcionado"]);
                return;
            }

            $query = "INSERT INTO Country (name) VALUES (?)";
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
                "message" => "País creado exitosamente",
                "id" => $id
            ]);
        } catch (Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
            echo json_encode(["error" => "Error al crear el país: " . $e->getMessage()]);
        }
    }

    public function updateAction()
    {
        try {
            if (!isset($_GET['id'])) {
                header("HTTP/1.1 400 Bad Request");
                echo json_encode(["error" => "ID no proporcionado"]);
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['name'])) {
                header("HTTP/1.1 400 Bad Request");
                echo json_encode(["error" => "Nombre del país no proporcionado"]);
                return;
            }

            $query = "UPDATE Country SET name = ? WHERE idcountry = ?";
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
                echo json_encode(["error" => "País no encontrado"]);
                return;
            }

            $stmt->close();
            header("HTTP/1.1 200 OK");
            echo json_encode(["message" => "País actualizado exitosamente"]);
        } catch (Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
            echo json_encode(["error" => "Error al actualizar el país: " . $e->getMessage()]);
        }
    }

    public function deleteAction()
    {
        try {
            if (!isset($_GET['id'])) {
                header("HTTP/1.1 400 Bad Request");
                echo json_encode(["error" => "ID no proporcionado"]);
                return;
            }

            $query = "DELETE FROM Country WHERE idcountry = ?";
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
                echo json_encode(["error" => "País no encontrado"]);
                return;
            }

            $stmt->close();
            header("HTTP/1.1 200 OK");
            echo json_encode(["message" => "País eliminado exitosamente"]);
        } catch (Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
            echo json_encode(["error" => "Error al eliminar el país: " . $e->getMessage()]);
        }
    }

    public function getByNameAction()
    {
        try {
            if (!isset($_GET['name'])) {
                header("HTTP/1.1 400 Bad Request");
                echo json_encode(["error" => "Nombre no proporcionado"]);
                return;
            }

            $query = "SELECT * FROM Country WHERE name = ?";
            $stmt = $this->db->getConnection()->prepare($query);
            if (!$stmt) {
                throw new Exception("Error preparando consulta: " . $this->db->getConnection()->error);
            }

            $stmt->bind_param('s', $_GET['name']);
            $stmt->execute();
            $result = $stmt->get_result();
            $country = $result->fetch_assoc();
            $stmt->close();

            if (!$country) {
                header("HTTP/1.1 404 Not Found");
                echo json_encode(["error" => "País no encontrado"]);
                return;
            }

            header('Content-Type: application/json');
            echo json_encode($country);
        } catch (Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
            echo json_encode(["error" => "Error al obtener el país: " . $e->getMessage()]);
        }
    }
} 