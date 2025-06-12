<?php
class CountryController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function listAction()
    {
        try {
            $query = "SELECT * FROM Country";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $countries = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode($countries);
        } catch (PDOException $e) {
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

            $query = "SELECT * FROM Country WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $_GET['id'], PDO::PARAM_INT);
            $stmt->execute();
            $country = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$country) {
                header("HTTP/1.1 404 Not Found");
                echo json_encode(["error" => "País no encontrado"]);
                return;
            }

            header('Content-Type: application/json');
            echo json_encode($country);
        } catch (PDOException $e) {
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

            $query = "INSERT INTO Country (name) VALUES (:name)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
            $stmt->execute();

            $id = $this->db->lastInsertId();
            header("HTTP/1.1 201 Created");
            echo json_encode([
                "message" => "País creado exitosamente",
                "id" => $id
            ]);
        } catch (PDOException $e) {
            header("HTTP/1.1 500 Internal Server Error");
            echo json_encode(["error" => "Error al crear el país: " . $e->getMessage()]);
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
                        $countryModel = new CountryModel();
                        $countryModel->updateCountry((int)$arrQueryStringParams['id'], $input);
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
                    $countryModel = new CountryModel();
                    $countryModel->deleteCountry((int)$arrQueryStringParams['id']);
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
                    $countryModel = new CountryModel();
                    $country = $countryModel->getCountryByName($arrQueryStringParams['name']);
                    $responseData = json_encode($country);
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