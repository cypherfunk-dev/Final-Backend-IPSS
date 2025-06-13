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

    /**
     * Crea una nueva camiseta deportiva
     * 
     * @OA\Post(
     *     path="/api/sports-jersey",
     *     tags={"Sports Jersey"},
     *     summary="Crea una nueva camiseta deportiva",
     *     description="Crea una nueva camiseta deportiva con los datos proporcionados",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "color", "idcountry", "idclub", "sku", "price", "type"},
     *             @OA\Property(property="title", type="string", example="Camiseta de Ejemplo"),
     *             @OA\Property(property="color", type="string", example="Rojo"),
     *             @OA\Property(property="idcountry", type="integer", example=1),
     *             @OA\Property(property="idclub", type="integer", example=1),
     *             @OA\Property(property="sku", type="string", example="SKU123"),
     *             @OA\Property(property="price", type="number", format="float", example=29.99),
     *             @OA\Property(property="type", type="string", example="player", description="Tipo de camiseta (player, fan, etc.)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Camiseta creada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Camiseta creada exitosamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Camiseta de Ejemplo"),
     *                 @OA\Property(property="color", type="string", example="Rojo"),
     *                 @OA\Property(property="idcountry", type="integer", example=1),
     *                 @OA\Property(property="idclub", type="integer", example=1),
     *                 @OA\Property(property="sku", type="string", example="SKU123"),
     *                 @OA\Property(property="price", type="number", format="float", example=29.99),
     *                 @OA\Property(property="type", type="string", example="player")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Datos inválidos",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Campos requeridos faltantes: title, color")
     *         )
     *     ),
     *     @OA\Response(
     *         response=405,
     *         description="Método no permitido"
     *     )
     * )
     */
    public function createAction()
    {
        $strErrorDesc = '';
        $requestMethod = $_SERVER['REQUEST_METHOD'];

        if (strtoupper($requestMethod) === 'POST') {
            try {
                $input = json_decode(file_get_contents('php://input'), true);

                if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
                    throw new Exception('JSON inválido: ' . json_last_error_msg());
                }

                // Validar campos requeridos
                $requiredFields = ['title', 'color', 'idcountry', 'idclub', 'sku', 'price', 'type'];
                $missingFields = array_filter($requiredFields, function($field) use ($input) {
                    return !isset($input[$field]) || empty($input[$field]);
                });

                if (!empty($missingFields)) {
                    throw new Exception("Campos requeridos faltantes: " . implode(', ', $missingFields));
                }

                // Validar tipos de datos
                if (!is_string($input['title'])) {
                    throw new Exception("El título debe ser una cadena de texto");
                }
                if (!is_string($input['color'])) {
                    throw new Exception("El color debe ser una cadena de texto");
                }
                if (!is_numeric($input['idcountry'])) {
                    throw new Exception("El ID del país debe ser un número");
                }
                if (!is_numeric($input['idclub'])) {
                    throw new Exception("El ID del club debe ser un número");
                }
                if (!is_string($input['sku'])) {
                    throw new Exception("El SKU debe ser una cadena de texto");
                }
                if (!is_numeric($input['price'])) {
                    throw new Exception("El precio debe ser un número");
                }
                if (!is_string($input['type'])) {
                    throw new Exception("El tipo debe ser una cadena de texto");
                }

                $jerseyModel = new SportsJerseyModel();
                $result = $jerseyModel->createJersey($input);
                
                $responseData = json_encode([
                    'success' => true,
                    'message' => 'Camiseta creada exitosamente',
                    'data' => $result
                ]);
            } catch (Exception $e) {
                $strErrorDesc = $e->getMessage();
                $strErrorHeader = 'HTTP/1.1 400 Bad Request';
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

    // Obtener el ID desde la query o desde el último segmento de la URL
    $id = null;
    if (isset($arrQueryStringParams['id']) && is_numeric($arrQueryStringParams['id'])) {
        $id = (int)$arrQueryStringParams['id'];
    } else {
        $uriParts = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
        $lastPart = end($uriParts);
        if (is_numeric($lastPart)) {
            $id = (int)$lastPart;
        }
    }

    if (strtoupper($requestMethod) === 'PUT') {
        if (!$id) {
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
                    $jerseyModel->updateJersey($id, $input);
                    $responseData = json_encode([
                        'success' => true,
                        'message' => 'Camiseta actualizada exitosamente'
                    ]);
                } catch (Exception $e) {
                    $strErrorDesc = $e->getMessage();
                    $strErrorHeader = 'HTTP/1.1 400 Bad Request';
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
            ['Content-Type: application/json', 'HTTP/1.1 200 OK']
        );
    } else {
        $this->sendOutput(
            json_encode(['error' => $strErrorDesc]),
            ['Content-Type: application/json', $strErrorHeader]
        );
    }
}

    public function deleteAction()
    {
        $strErrorDesc = '';
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $arrQueryStringParams = $this->getQueryStringParams();

        if (strtoupper($requestMethod) === 'DELETE') {
            // Obtener el ID de la URL o de los parámetros de consulta
            $id = null;
            
            // Intentar obtener el ID de la URL
            $urlParts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
            $lastPart = end($urlParts);
            if (is_numeric($lastPart)) {
                $id = (int)$lastPart;
            }
            
            // Si no se encontró en la URL, intentar obtenerlo de los parámetros de consulta
            if ($id === null && isset($arrQueryStringParams['id'])) {
                $id = (int)$arrQueryStringParams['id'];
            }

            if ($id === null || !is_numeric($id)) {
                $strErrorDesc = 'ID no válido';
                $strErrorHeader = 'HTTP/1.1 400 Bad Request';
            } else {
                try {
                    $jerseyModel = new SportsJerseyModel();
                    $jerseyModel->deleteJersey($id);
                    $responseData = json_encode([
                        'success' => true,
                        'message' => 'Camiseta eliminada exitosamente'
                    ]);
                } catch (Exception $e) {
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

    /**
     * Obtiene una camiseta deportiva por su ID
     * 
     * @OA\Get(
     *     path="/api/sports-jersey/{id}",
     *     tags={"Sports Jersey"},
     *     summary="Obtiene una camiseta deportiva por su ID",
     *     description="Retorna los detalles de una camiseta deportiva específica",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la camiseta",
     *         required=true,
     *         @OA\Schema(type="integer", minimum=1, example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Camiseta encontrada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Camiseta de Ejemplo"),
     *                 @OA\Property(property="color", type="string", example="Rojo"),
     *                 @OA\Property(property="idcountry", type="integer", example=1),
     *                 @OA\Property(property="idclub", type="integer", example=1),
     *                 @OA\Property(property="sku", type="string", example="SKU123"),
     *                 @OA\Property(property="price", type="number", format="float", example=29.99),
     *                 @OA\Property(property="type", type="string", example="player")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Camiseta no encontrada"
     *     )
     * )
     */
    public function getAction()
    {
        $strErrorDesc = '';
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $arrQueryStringParams = $this->getQueryStringParams();

        if (strtoupper($requestMethod) === 'GET') {
            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                $strErrorDesc = 'ID no válido';
                $strErrorHeader = 'HTTP/1.1 400 Bad Request';
            } else {
                try {
                    $jerseyModel = new SportsJerseyModel();
                    $jersey = $jerseyModel->getJerseyById((int)$_GET['id']);
                    if (!$jersey) {
                        $strErrorDesc = 'Camiseta no encontrada';
                        $strErrorHeader = 'HTTP/1.1 404 Not Found';
                    } else {
                        $responseData = json_encode($jersey);
                    }
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

    public function __call($name, $arguments)
    {
        if ($name === 'createAction' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->createAction();
        }
        
        if ($name === 'putAction' && ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'PATCH')) {
            return $this->putAction();
        }
        
        if ($name === 'updateAction' && ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'PATCH')) {
            return $this->putAction();
        }
        
        $this->sendOutput(
            json_encode(array('error' => 'Método no soportado', 'method' => $name)),
            array('Content-Type: application/json', 'HTTP/1.1 405 Method Not Allowed')
        );
    }

    public function putAction()
    {
        $strErrorDesc = '';
        $requestMethod = $_SERVER['REQUEST_METHOD'];

        if (strtoupper($requestMethod) === 'PUT' || strtoupper($requestMethod) === 'PATCH') {
            // Obtener el ID de la URL
            $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $pathParts = explode('/', trim($uri, '/'));
            $id = null;
            
            // Buscar el ID en la URL
            foreach ($pathParts as $part) {
                if (is_numeric($part)) {
                    $id = (int)$part;
                    break;
                }
            }

            if ($id === null) {
                $strErrorDesc = 'ID no válido';
                $strErrorHeader = 'HTTP/1.1 400 Bad Request';
            } else {
                $input = json_decode(file_get_contents('php://input'), true);

                if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
                    $strErrorDesc = 'JSON inválido: ' . json_last_error_msg();
                    $strErrorHeader = 'HTTP/1.1 400 Bad Request';
                } else {
                    try {
                        // Validar campos requeridos
                        $requiredFields = ['title', 'color', 'idcountry', 'idclub', 'sku', 'price', 'type'];
                        $missingFields = array_filter($requiredFields, function($field) use ($input) {
                            return !isset($input[$field]) || empty($input[$field]);
                        });

                        if (!empty($missingFields)) {
                            throw new Exception("Campos requeridos faltantes: " . implode(', ', $missingFields));
                        }

                        // Validar tipos de datos
                        if (!is_string($input['title'])) {
                            throw new Exception("El título debe ser una cadena de texto");
                        }
                        if (!is_string($input['color'])) {
                            throw new Exception("El color debe ser una cadena de texto");
                        }
                        if (!is_numeric($input['idcountry'])) {
                            throw new Exception("El ID del país debe ser un número");
                        }
                        if (!is_numeric($input['idclub'])) {
                            throw new Exception("El ID del club debe ser un número");
                        }
                        if (!is_string($input['sku'])) {
                            throw new Exception("El SKU debe ser una cadena de texto");
                        }
                        if (!is_numeric($input['price'])) {
                            throw new Exception("El precio debe ser un número");
                        }
                        if (!is_string($input['type'])) {
                            throw new Exception("El tipo debe ser una cadena de texto");
                        }

                        $jerseyModel = new SportsJerseyModel();
                        $jerseyModel->updateJersey($id, $input);
                        $responseData = json_encode([
                            'success' => true,
                            'message' => 'Camiseta actualizada exitosamente'
                        ]);
                    } catch (Exception $e) {
                        $strErrorDesc = $e->getMessage();
                        $strErrorHeader = 'HTTP/1.1 400 Bad Request';
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

    /**
     * Obtiene camisetas deportivas por ID de club
     * 
     * @OA\Get(
     *     path="/api/sports-jersey/club/{clubId}",
     *     tags={"Sports Jersey"},
     *     summary="Obtiene camisetas deportivas por ID de club",
     *     description="Retorna una lista de camisetas deportivas asociadas a un club específico",
     *     @OA\Parameter(
     *         name="clubId",
     *         in="path",
     *         description="ID del club",
     *         required=true,
     *         @OA\Schema(type="integer", minimum=1, example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de camisetas obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Camiseta de Ejemplo"),
     *                     @OA\Property(property="color", type="string", example="Rojo"),
     *                     @OA\Property(property="idcountry", type="integer", example=1),
     *                     @OA\Property(property="idclub", type="integer", example=1),
     *                     @OA\Property(property="sku", type="string", example="SKU123"),
     *                     @OA\Property(property="price", type="number", format="float", example=29.99),
     *                     @OA\Property(property="type", type="string", example="player")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Club no encontrado"
     *     )
     * )
     */

    /**
     * Obtiene camisetas deportivas por ID de país
     * 
     * @OA\Get(
     *     path="/api/sports-jersey/country/{countryId}",
     *     tags={"Sports Jersey"},
     *     summary="Obtiene camisetas deportivas por ID de país",
     *     description="Retorna una lista de camisetas deportivas asociadas a un país específico",
     *     @OA\Parameter(
     *         name="countryId",
     *         in="path",
     *         description="ID del país",
     *         required=true,
     *         @OA\Schema(type="integer", minimum=1, example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de camisetas obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Camiseta de Ejemplo"),
     *                     @OA\Property(property="color", type="string", example="Rojo"),
     *                     @OA\Property(property="idcountry", type="integer", example=1),
     *                     @OA\Property(property="idclub", type="integer", example=1),
     *                     @OA\Property(property="sku", type="string", example="SKU123"),
     *                     @OA\Property(property="price", type="number", format="float", example=29.99),
     *                     @OA\Property(property="type", type="string", example="player")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="País no encontrado"
     *     )
     * )
     */
} 