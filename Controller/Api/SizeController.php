<?php
class SizeController {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function listAction() {
        try {
            $query = "SELECT * FROM Size";
            $stmt = $this->db->getConnection()->prepare($query);
            if (!$stmt) {
                throw new Exception("Error preparando consulta: " . $this->db->getConnection()->error);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $sizes = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            header('Content-Type: application/json');
            echo json_encode($sizes);
        } catch (Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
            echo json_encode(["error" => "Error al obtener las tallas: " . $e->getMessage()]);
        }
    }

    public function getAction() {
        try {
            if (!isset($_GET['id'])) {
                header("HTTP/1.1 400 Bad Request");
                echo json_encode(["error" => "ID no proporcionado"]);
                return;
            }

            $query = "SELECT * FROM Size WHERE id = ?";
            $stmt = $this->db->getConnection()->prepare($query);
            if (!$stmt) {
                throw new Exception("Error preparando consulta: " . $this->db->getConnection()->error);
            }
            $stmt->bind_param('i', $_GET['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $size = $result->fetch_assoc();
            $stmt->close();

            if (!$size) {
                header("HTTP/1.1 404 Not Found");
                echo json_encode(["error" => "Talla no encontrada"]);
                return;
            }

            header('Content-Type: application/json');
            echo json_encode($size);
        } catch (Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
            echo json_encode(["error" => "Error al obtener la talla: " . $e->getMessage()]);
        }
    }

    public function createAction() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['name'])) {
                header("HTTP/1.1 400 Bad Request");
                echo json_encode(["error" => "Nombre de talla no proporcionado"]);
                return;
            }

            $query = "INSERT INTO Size (name) VALUES (?)";
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
                "message" => "Talla creada exitosamente",
                "id" => $id
            ]);
        } catch (Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
            echo json_encode(["error" => "Error al crear la talla: " . $e->getMessage()]);
        }
    }

    public function updateAction()
    {
        $strErrorDesc = '';
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $arrQueryStringParams = $this->getQueryStringParams();

        if (strtoupper($requestMethod) === 'PUT') {
            if (!isset($arrQueryStringParams['id']) || !is_numeric($arrQueryStringParams['id'])) {
                $strErrorDesc = 'ID no válido';
                $strErrorHeader = 'HTTP/1.1 400 Bad Request';
            } else {
                $input = json_decode(file_get_contents('php://input'), true);

                if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
                    $strErrorDesc = 'JSON inválido';
                    $strErrorHeader = 'HTTP/1.1 400 Bad Request';
                } else {
                    try {
                        $sizeModel = new SizeModel();
                        $sizeModel->updateSize((int)$arrQueryStringParams['id'], $input);
                        $responseData = json_encode(['success' => true]);
                    } catch (Error | Exception $e) {
                        $strErrorDesc = $e->getMessage();
                        $strErrorHeader = 'HTTP/1.1 500 Internal Server Error';
                    }
                }
            }
        } else {
            $strErrorDesc = "Método no permitido";
            $strErrorHeader = 'HTTP/1.1 405 Method Not Allowed';
        }

        if (!$strErrorDesc) {
            $this->sendOutput(
                $responseData,
                array('Content-Type: application/json', 'HTTP/1.1 200 OK')
            );
        } else {
            $this->sendOutput(
                json_encode(array('error' => $strErrorDesc)),
                array('Content-Type: application/json', $strErrorHeader)
            );
        }
    }

    public function deleteAction()
    {
        $strErrorDesc = '';
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $arrQueryStringParams = $this->getQueryStringParams();

        if (strtoupper($requestMethod) === 'DELETE') {
            if (!isset($arrQueryStringParams['id']) || !is_numeric($arrQueryStringParams['id'])) {
                $strErrorDesc = 'ID no válido';
                $strErrorHeader = 'HTTP/1.1 400 Bad Request';
            } else {
                try {
                    $sizeModel = new SizeModel();
                    $sizeModel->deleteSize((int)$arrQueryStringParams['id']);
                    $responseData = json_encode(['success' => true]);
                } catch (Error | Exception $e) {
                    $strErrorDesc = $e->getMessage();
                    $strErrorHeader = 'HTTP/1.1 500 Internal Server Error';
                }
            }
        } else {
            $strErrorDesc = "Método no permitido";
            $strErrorHeader = 'HTTP/1.1 405 Method Not Allowed';
        }

        if (!$strErrorDesc) {
            $this->sendOutput(
                $responseData,
                array('Content-Type: application/json', 'HTTP/1.1 200 OK')
            );
        } else {
            $this->sendOutput(
                json_encode(array('error' => $strErrorDesc)),
                array('Content-Type: application/json', $strErrorHeader)
            );
        }
    }

    public function getByNameAction()
    {
        $strErrorDesc = '';
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $arrQueryStringParams = $this->getQueryStringParams();

        if (strtoupper($requestMethod) === 'GET') {
            if (!isset($arrQueryStringParams['name'])) {
                $strErrorDesc = 'Nombre no proporcionado';
                $strErrorHeader = 'HTTP/1.1 400 Bad Request';
            } else {
                try {
                    $sizeModel = new SizeModel();
                    $size = $sizeModel->getSizeByName($arrQueryStringParams['name']);
                    $responseData = json_encode($size);
                } catch (Error | Exception $e) {
                    $strErrorDesc = $e->getMessage();
                    $strErrorHeader = 'HTTP/1.1 500 Internal Server Error';
                }
            }
        } else {
            $strErrorDesc = "Método no permitido";
            $strErrorHeader = 'HTTP/1.1 405 Method Not Allowed';
        }

        if (!$strErrorDesc) {
            $this->sendOutput(
                $responseData,
                array('Content-Type: application/json', 'HTTP/1.1 200 OK')
            );
        } else {
            $this->sendOutput(
                json_encode(array('error' => $strErrorDesc)),
                array('Content-Type: application/json', $strErrorHeader)
            );
        }
    }
} 