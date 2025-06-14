<?php
require __DIR__ . "/Inc/Bootstrap.php";
require_once __DIR__ . "/Inc/config.php";

// Incluir controladores una sola vez
require_once __DIR__ . "/Controller/Api/BusinessController.php";
require_once __DIR__ . "/Controller/Api/SportsJerseyController.php";
require_once __DIR__ . "/Controller/Api/SizeController.php";
require_once __DIR__ . "/Controller/Api/CountryController.php";
require_once __DIR__ . "/Controller/Api/ClubController.php";
require_once __DIR__ . "/Controller/Api/OrderController.php";

// Verifica que REQUEST_URI esté definido (modo HTTP)
if (php_sapi_name() !== 'cli' && isset($_SERVER["REQUEST_URI"])) {
    $uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    $uri = explode("/", trim($uri, "/"));
} else {
    // Ejecutando desde CLI, simula URI
    $uri = [];
}

// Validación mínima para evitar errores
if (!isset($uri[0]) || $uri[0] !== 'api') {
    header("HTTP/1.1 404 Not Found");
    echo json_encode(["error" => "Ruta no válida"]);
    exit;
}

// Determinar qué controlador usar basado en la ruta
if (isset($uri[1])) {
    switch ($uri[1]) {
        case 'business':
            $objController = new BusinessController();
            break;
        case 'sports-jersey':
            $objController = new SportsJerseyController();
            break;
        case 'sizes':
            $objController = new SizeController();
            break;
        case 'countries':
            $objController = new CountryController();
            break;
        case 'clubs':
            $objController = new ClubController();
            break;
        case 'orders':
            $objController = new OrderController();
            break;
        default:
            header("HTTP/1.1 404 Not Found");
            echo json_encode(["error" => "Controlador no encontrado"]);
            exit;
    }

    // Verifica que el método exista antes de llamarlo
    if (isset($uri[2])) {
        // Si el segundo segmento es numérico, asumimos que es un ID
        if (is_numeric($uri[2])) {
            $_GET['id'] = $uri[2];
            // Determinar el método a llamar basado en el método HTTP
            $method = strtolower($_SERVER['REQUEST_METHOD']);
            $methodName = '';
            
            switch ($method) {
                case 'get':
                    $methodName = 'getAction';
                    break;
                case 'put':
                    $methodName = 'updateAction';
                    break;
                case 'delete':
                    $methodName = 'deleteAction';
                    break;
                default:
                    header("HTTP/1.1 405 Method Not Allowed");
                    echo json_encode([
                        "error" => "Método no permitido",
                        "method" => $method,
                        "availableMethods" => get_class_methods($objController)
                    ]);
                    exit;
            }
            
            if (method_exists($objController, $methodName)) {
                $objController->$methodName();
            } else {
                header("HTTP/1.1 405 Method Not Allowed");
                echo json_encode([
                    "error" => "Método no permitido",
                    "method" => $method,
                    "methodName" => $methodName,
                    "availableMethods" => get_class_methods($objController)
                ]);
                exit;
            }
        } else {
            // Si no es numérico, asumimos que es un nombre de método
            $strMethodName = $uri[2] . 'Action';
            if (method_exists($objController, $strMethodName)) {
                $objController->$strMethodName();
            } else {
                header("HTTP/1.1 404 Not Found");
                echo json_encode(["error" => "Método '$strMethodName' no encontrado"]);
                exit;
            }
        }
    } else {
        // Si no hay segundo segmento, determinamos el método basado en el método HTTP
        $method = strtolower($_SERVER['REQUEST_METHOD']);
        switch ($method) {
            case 'get':
                if (method_exists($objController, 'listAction')) {
                    $objController->listAction();
                } else {
                    header("HTTP/1.1 404 Not Found");
                    echo json_encode(["error" => "Método 'listAction' no encontrado"]);
                    exit;
                }
                break;
            case 'post':
                if (method_exists($objController, 'createAction')) {
                    $objController->createAction();
                } else {
                    header("HTTP/1.1 405 Method Not Allowed");
                    echo json_encode([
                        "error" => "Método no permitido",
                        "method" => $method,
                        "methodName" => "createAction",
                        "availableMethods" => get_class_methods($objController)
                    ]);
                    exit;
                }
                break;
            default:
                header("HTTP/1.1 405 Method Not Allowed");
                echo json_encode([
                    "error" => "Método no permitido",
                    "method" => $method,
                    "availableMethods" => get_class_methods($objController)
                ]);
                exit;
        }
    }
} else {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(["error" => "Controlador no especificado"]);
    exit;
}