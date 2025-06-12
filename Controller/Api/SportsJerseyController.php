<?php
class SportsJerseyController extends BaseController
{
    public function listAction()
    {
        try {
            $strErrorDesc = '';
            $requestMethod = $_SERVER["REQUEST_METHOD"];
            $arrQueryStringParams = $this->getQueryStringParams();
            
            if (strtoupper($requestMethod) == 'GET') {
                $limit = isset($arrQueryStringParams['limit']) ? $arrQueryStringParams['limit'] : 10;
                $clientId = isset($arrQueryStringParams['client_id']) ? $arrQueryStringParams['client_id'] : null;
                
                $sportsJerseyModel = new SportsJerseyModel();
                $arrJerseys = $sportsJerseyModel->getJerseys($limit, $clientId);
                
                $responseData = json_encode($arrJerseys);
            } else {
                $strErrorDesc = 'Método no soportado';
                $strErrorHeader = 'HTTP/1.1 422 Unprocessable Entity';
            }
        } catch (Error $e) {
            $strErrorDesc = $e->getMessage().'Algo salió mal!';
            $strErrorHeader = 'HTTP/1.1 500 Internal Server Error';
        }
        
        if (!$strErrorDesc) {
            $this->sendOutput(
                $responseData,
                array('Content-Type: application/json', 'HTTP/1.1 200 OK')
            );
        } else {
            $this->sendOutput(json_encode(array('error' => $strErrorDesc)), 
                array('Content-Type: application/json', $strErrorHeader)
            );
        }
    }

    public function createAction()
    {
        $strErrorDesc = '';
        $requestMethod = $_SERVER['REQUEST_METHOD'];

        if (strtoupper($requestMethod) === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
                $strErrorDesc = 'JSON inválido';
                $strErrorHeader = 'HTTP/1.1 400 Bad Request';
            } else {
                try {
                    $jerseyModel = new SportsJerseyModel();
                    $created = $jerseyModel->createJersey($input);
                    $responseData = json_encode(['success' => true, 'id' => $created['id']]);
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
                array('Content-Type: application/json', 'HTTP/1.1 201 Created')
            );
        } else {
            $this->sendOutput(
                json_encode(array('error' => $strErrorDesc)),
                array('Content-Type: application/json', $strErrorHeader)
            );
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
                        $jerseyModel = new SportsJerseyModel();
                        $jerseyModel->updateJersey((int)$arrQueryStringParams['id'], $input);
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
                    $jerseyModel = new SportsJerseyModel();
                    $jerseyModel->deleteJersey((int)$arrQueryStringParams['id']);
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

    public function getBySkuAction()
    {
        $strErrorDesc = '';
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $arrQueryStringParams = $this->getQueryStringParams();

        if (strtoupper($requestMethod) === 'GET') {
            if (!isset($arrQueryStringParams['sku'])) {
                $strErrorDesc = 'SKU no proporcionado';
                $strErrorHeader = 'HTTP/1.1 400 Bad Request';
            } else {
                try {
                    $jerseyModel = new SportsJerseyModel();
                    $jersey = $jerseyModel->getJerseyBySku($arrQueryStringParams['sku']);
                    $responseData = json_encode($jersey);
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

    public function getByClubAction()
    {
        try {
            $strErrorDesc = '';
            $requestMethod = $_SERVER["REQUEST_METHOD"];
            $arrQueryStringParams = $this->getQueryStringParams();
            
            if (strtoupper($requestMethod) == 'GET') {
                if (isset($arrQueryStringParams['club_id']) && $arrQueryStringParams['club_id']) {
                    $clubId = $arrQueryStringParams['club_id'];
                    $clientId = isset($arrQueryStringParams['client_id']) ? $arrQueryStringParams['client_id'] : null;
                    
                    $sportsJerseyModel = new SportsJerseyModel();
                    $arrJerseys = $sportsJerseyModel->getJerseysByClub($clubId, $clientId);
                    
                    $responseData = json_encode($arrJerseys);
                } else {
                    $strErrorDesc = 'ID de club no proporcionado';
                    $strErrorHeader = 'HTTP/1.1 400 Bad Request';
                }
            } else {
                $strErrorDesc = 'Método no soportado';
                $strErrorHeader = 'HTTP/1.1 422 Unprocessable Entity';
            }
        } catch (Error $e) {
            $strErrorDesc = $e->getMessage().'Algo salió mal!';
            $strErrorHeader = 'HTTP/1.1 500 Internal Server Error';
        }
        
        if (!$strErrorDesc) {
            $this->sendOutput(
                $responseData,
                array('Content-Type: application/json', 'HTTP/1.1 200 OK')
            );
        } else {
            $this->sendOutput(json_encode(array('error' => $strErrorDesc)), 
                array('Content-Type: application/json', $strErrorHeader)
            );
        }
    }

    public function getByCountryAction()
    {
        try {
            $strErrorDesc = '';
            $requestMethod = $_SERVER["REQUEST_METHOD"];
            $arrQueryStringParams = $this->getQueryStringParams();
            
            if (strtoupper($requestMethod) == 'GET') {
                if (isset($arrQueryStringParams['country_id']) && $arrQueryStringParams['country_id']) {
                    $countryId = $arrQueryStringParams['country_id'];
                    $clientId = isset($arrQueryStringParams['client_id']) ? $arrQueryStringParams['client_id'] : null;
                    
                    $sportsJerseyModel = new SportsJerseyModel();
                    $arrJerseys = $sportsJerseyModel->getJerseysByCountry($countryId, $clientId);
                    
                    $responseData = json_encode($arrJerseys);
                } else {
                    $strErrorDesc = 'ID de país no proporcionado';
                    $strErrorHeader = 'HTTP/1.1 400 Bad Request';
                }
            } else {
                $strErrorDesc = 'Método no soportado';
                $strErrorHeader = 'HTTP/1.1 422 Unprocessable Entity';
            }
        } catch (Error $e) {
            $strErrorDesc = $e->getMessage().'Algo salió mal!';
            $strErrorHeader = 'HTTP/1.1 500 Internal Server Error';
        }
        
        if (!$strErrorDesc) {
            $this->sendOutput(
                $responseData,
                array('Content-Type: application/json', 'HTTP/1.1 200 OK')
            );
        } else {
            $this->sendOutput(json_encode(array('error' => $strErrorDesc)), 
                array('Content-Type: application/json', $strErrorHeader)
            );
        }
    }
} 