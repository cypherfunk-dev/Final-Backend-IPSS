<?php
class SwaggerController extends BaseController {
    public function getAction() {
        try {
            $strErrorDesc = '';
            $requestMethod = $_SERVER["REQUEST_METHOD"];
            
            if (strtoupper($requestMethod) == 'GET') {
                $swaggerFile = file_get_contents(PROJECT_ROOT_PATH . '/swagger.json');
                if ($swaggerFile === false) {
                    throw new Exception('No se pudo leer el archivo swagger.json');
                }
                $responseData = $swaggerFile;
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