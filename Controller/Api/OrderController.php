<?php
class OrderController extends BaseController
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function listAction()
    {
        try {
            $query = "SELECT o.*, 
                     GROUP_CONCAT(
                         JSON_OBJECT(
                             'id', oi.iddetail,
                             'sports_jersey_id', oi.iditem,
                             'quantity', oi.quantity,
                             'price', oi.price_final,
                             'unit_price', oi.unit_price
                         )
                     ) as items
                     FROM `Orders` o
                     LEFT JOIN Order_detail oi ON o.idorders = oi.orderid
                     GROUP BY o.idorders";
            $stmt = $this->db->getConnection()->prepare($query);
            if (!$stmt) {
                throw new Exception("Error preparando consulta: " . $this->db->getConnection()->error);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $orders = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            // Procesar los items de cada orden
            foreach ($orders as &$order) {
                $order['items'] = json_decode('[' . $order['items'] . ']', true);
            }
            
            header('Content-Type: application/json');
            echo json_encode($orders);
        } catch (Exception $e) {
            header("HTTP/1.1 500 Internal Server Error");
            echo json_encode(["error" => "Error al obtener las órdenes: " . $e->getMessage()]);
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
                    $orderModel = new OrderModel();
                    $order = $orderModel->getOrderById((int)$_GET['id']);
                    if (!$order) {
                        $strErrorDesc = 'Pedido no encontrado';
                        $strErrorHeader = 'HTTP/1.1 404 Not Found';
                    } else {
                        $responseData = json_encode($order);
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

    public function createAction()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['businessid']) || !isset($data['items']) || !is_array($data['items'])) {
                header("HTTP/1.1 400 Bad Request");
                echo json_encode(["error" => "Datos de orden inválidos"]);
                return;
            }

            $this->db->getConnection()->begin_transaction();

            // Obtener la categoría del cliente y su porcentaje de descuento
            $query = "SELECT cc.offer_percentage 
                     FROM Business b 
                     JOIN Client_category cc ON b.categoryid = cc.idcategory 
                     WHERE b.idbusiness = ?";
            $stmt = $this->db->getConnection()->prepare($query);
            if (!$stmt) {
                throw new Exception("Error preparando consulta: " . $this->db->getConnection()->error);
            }
            $stmt->bind_param("i", $data['businessid']);
            $stmt->execute();
            $result = $stmt->get_result();
            $business = $result->fetch_assoc();

            if (!$business) {
                throw new Exception("Negocio no encontrado");
            }

            $discountPercentage = $business['offer_percentage'] / 100;

            // Crear la orden
            $query = "INSERT INTO `Orders` (businessid) VALUES (?)";
            $stmt = $this->db->getConnection()->prepare($query);
            if (!$stmt) {
                throw new Exception("Error preparando consulta: " . $this->db->getConnection()->error);
            }
            $stmt->bind_param("i", $data['businessid']);
            $stmt->execute();
            $orderId = $stmt->insert_id;

            $total = 0;

            // Procesar cada item de la orden
            foreach ($data['items'] as $item) {
                if (!isset($item['iditem']) || !isset($item['quantity'])) {
                    throw new Exception("Datos de item inválidos");
                }

                // Obtener el precio base de la camiseta
                $query = "SELECT price FROM Sports_jersey WHERE iditem = ?";
                $stmt = $this->db->getConnection()->prepare($query);
                if (!$stmt) {
                    throw new Exception("Error preparando consulta: " . $this->db->getConnection()->error);
                }
                $stmt->bind_param("i", $item['iditem']);
                $stmt->execute();
                $result = $stmt->get_result();
                $jersey = $result->fetch_assoc();

                if (!$jersey) {
                    throw new Exception("Camiseta no encontrada");
                }

                // Calcular el precio con descuento
                $basePrice = $jersey['price'];
                $unitPrice = $basePrice * (1 - $discountPercentage);
                $itemTotal = $unitPrice * $item['quantity'];
                $total += $itemTotal;

                // Insertar el detalle de la orden
                $query = "INSERT INTO Order_detail (orderid, iditem, quantity, unit_price, price_final) 
                         VALUES (?, ?, ?, ?, ?)";
                $stmt = $this->db->getConnection()->prepare($query);
                if (!$stmt) {
                    throw new Exception("Error preparando consulta: " . $this->db->getConnection()->error);
                }
                $stmt->bind_param("iiidi", $orderId, $item['iditem'], $item['quantity'], $unitPrice, $itemTotal);
                $stmt->execute();
            }

            $this->db->getConnection()->commit();

            header("HTTP/1.1 201 Created");
            echo json_encode([
                "message" => "Orden creada exitosamente",
                "id" => $orderId,
                "total" => $total
            ]);
        } catch (Exception $e) {
            if ($this->db->getConnection()->in_transaction) {
                $this->db->getConnection()->rollback();
            }
            header("HTTP/1.1 500 Internal Server Error");
            echo json_encode(["error" => "Error al crear la orden: " . $e->getMessage()]);
        }
    }

    public function getByUserAction()
    {
        $strErrorDesc = '';
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $arrQueryStringParams = $this->getQueryStringParams();

        if (strtoupper($requestMethod) === 'GET') {
            if (!isset($arrQueryStringParams['userId']) || !is_numeric($arrQueryStringParams['userId'])) {
                $strErrorDesc = 'ID de usuario no válido';
                $strErrorHeader = 'HTTP/1.1 400 Bad Request';
            } else {
                try {
                    $orderModel = new OrderModel();
                    $orders = $orderModel->getOrdersByUser((int)$arrQueryStringParams['userId']);
                    $responseData = json_encode($orders);
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
                        $orderModel = new OrderModel();
                        $orderModel->updateOrder((int)$arrQueryStringParams['id'], $input);
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

    public function updateStatusAction()
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

                if (json_last_error() !== JSON_ERROR_NONE || !is_array($input) || !isset($input['status'])) {
                    $strErrorDesc = 'JSON inválido o estado no proporcionado';
                    $strErrorHeader = 'HTTP/1.1 400 Bad Request';
                } else {
                    try {
                        $orderModel = new OrderModel();
                        $orderModel->updateOrderStatus((int)$arrQueryStringParams['id'], $input['status']);
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
                    $orderModel = new OrderModel();
                    $orderModel->deleteOrder((int)$arrQueryStringParams['id']);
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

    public function getWithDetailsAction()
    {
        $strErrorDesc = '';
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $arrQueryStringParams = $this->getQueryStringParams();

        if (strtoupper($requestMethod) === 'GET') {
            if (!isset($arrQueryStringParams['id']) || !is_numeric($arrQueryStringParams['id'])) {
                $strErrorDesc = 'ID no válido';
                $strErrorHeader = 'HTTP/1.1 400 Bad Request';
            } else {
                try {
                    $orderModel = new OrderModel();
                    $order = $orderModel->getOrderWithDetails((int)$arrQueryStringParams['id']);
                    if (!$order) {
                        $strErrorDesc = 'Orden no encontrada';
                        $strErrorHeader = 'HTTP/1.1 404 Not Found';
                    } else {
                        $responseData = json_encode($order);
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