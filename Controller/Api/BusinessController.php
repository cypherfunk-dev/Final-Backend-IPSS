<?php
class BusinessController extends BaseController
{
    public function listAction()
    {
        $strErrorDesc = '';
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $arrQueryStringParams = $this->getQueryStringParams();
        if (strtoupper($requestMethod) == 'GET') {
            try {
                $businessModel = new BusinessModel();
                $intLimit = isset($arrQueryStringParams['limit']) && is_numeric($arrQueryStringParams['limit']) ? (int)$arrQueryStringParams['limit'] : 10;
                $arrBusinesses = $businessModel->getBusiness($intLimit);
                $responseData = json_encode($arrBusinesses);
            } catch (Error $e) {
                $strErrorDesc = $e->getMessage();
                $strErrorHeader = 'HTTP/1.1 500 Internal Server Error';
            }
        } else {
            $strErrorDesc = "Method Not Allowed";
            $strErrorHeader = 'HTTP/1.1 422 Unprocessable Entity';
        }
        //send output
        if (!$strErrorDesc) {
            $this->sendOutput(
                $responseData,
                array('Content-Type: application/json', 'HTTP/1.1 200 OK'),
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
                    // Primero crear el contacto
                    $contactModel = new UserModel();
                    $contactData = $input['contact'];
                    $contactCreated = $contactModel->createContact($contactData);

                    // Luego crear el negocio con el ID del contacto
                    $businessModel = new BusinessModel();
                    $businessData = $input;
                    $businessData['idcontact'] = $contactCreated['id'];
                    $created = $businessModel->createBusiness($businessData);
                    
                    $responseData = json_encode([
                        'success' => true, 
                        'business_id' => $created['id'],
                        'contact_id' => $contactCreated['id']
                    ]);
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
            $this->sendOutput($responseData, [
                'Content-Type: application/json',
                'HTTP/1.1 201 Created'
            ]);
        } else {
            $this->sendOutput(json_encode(['error' => $strErrorDesc]), [
                'Content-Type: application/json',
                $strErrorHeader
            ]);
        }
    }

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
                    $businessModel = new BusinessModel();
                    $business = $businessModel->getBusinessById((int)$_GET['id']);
                    if (!$business) {
                        $strErrorDesc = 'Negocio no encontrado';
                        $strErrorHeader = 'HTTP/1.1 404 Not Found';
                    } else {
                        $responseData = json_encode($business);
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
}