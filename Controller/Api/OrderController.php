<?php
class OrderController extends BaseController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function listAction()
    {
        try {
            $query = "SELECT o.*, 
                     GROUP_CONCAT(
                         JSON_OBJECT(
                             'id', oi.id,
                             'sports_jersey_id', oi.sports_jersey_id,
                             'size_id', oi.size_id,
                             'quantity', oi.quantity,
                             'price', oi.price
                         )
                     ) as items
                     FROM `Order` o
                     LEFT JOIN OrderItem oi ON o.id = oi.order_id
                     GROUP BY o.id";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Procesar los items de cada orden
            foreach ($orders as &$order) {
                $order['items'] = json_decode('[' . $order['items'] . ']', true);
            }
            
            header('Content-Type: application/json');
            echo json_encode($orders);
        } catch (PDOException $e) {
            header("HTTP/1.1 500 Internal Server Error");
            echo json_encode(["error" => "Error al obtener las órdenes: " . $e->getMessage()]);
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

            $query = "SELECT o.*, 
                     GROUP_CONCAT(
                         JSON_OBJECT(
                             'id', oi.id,
                             'sports_jersey_id', oi.sports_jersey_id,
                             'size_id', oi.size_id,
                             'quantity', oi.quantity,
                             'price', oi.price
                         )
                     ) as items
                     FROM `Order` o
                     LEFT JOIN OrderItem oi ON o.id = oi.order_id
                     WHERE o.id = :id
                     GROUP BY o.id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $_GET['id'], PDO::PARAM_INT);
            $stmt->execute();
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                header("HTTP/1.1 404 Not Found");
                echo json_encode(["error" => "Orden no encontrada"]);
                return;
            }

            // Procesar los items de la orden
            $order['items'] = json_decode('[' . $order['items'] . ']', true);

            header('Content-Type: application/json');
            echo json_encode($order);
        } catch (PDOException $e) {
            header("HTTP/1.1 500 Internal Server Error");
            echo json_encode(["error" => "Error al obtener la orden: " . $e->getMessage()]);
        }
    }

    public function createAction()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['client_id']) || !isset($data['items']) || !is_array($data['items'])) {
                header("HTTP/1.1 400 Bad Request");
                echo json_encode(["error" => "Datos de orden inválidos"]);
                return;
            }

            $this->db->beginTransaction();

            // Crear la orden
            $query = "INSERT INTO `Order` (client_id, total) VALUES (:client_id, 0)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':client_id', $data['client_id'], PDO::PARAM_STR);
            $stmt->execute();
            $orderId = $this->db->lastInsertId();

            $total = 0;
            // Crear los items de la orden
            foreach ($data['items'] as $item) {
                if (!isset($item['sports_jersey_id']) || !isset($item['size_id']) || !isset($item['quantity'])) {
                    throw new Exception("Datos de item inválidos");
                }

                // Obtener el precio de la camiseta
                $query = "SELECT precio FROM Sports_jersey WHERE id = :id";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':id', $item['sports_jersey_id'], PDO::PARAM_INT);
                $stmt->execute();
                $jersey = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$jersey) {
                    throw new Exception("Camiseta no encontrada");
                }

                $price = $jersey['precio'];
                $itemTotal = $price * $item['quantity'];
                $total += $itemTotal;

                $query = "INSERT INTO OrderItem (order_id, sports_jersey_id, size_id, quantity, price) 
                         VALUES (:order_id, :sports_jersey_id, :size_id, :quantity, :price)";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
                $stmt->bindParam(':sports_jersey_id', $item['sports_jersey_id'], PDO::PARAM_INT);
                $stmt->bindParam(':size_id', $item['size_id'], PDO::PARAM_INT);
                $stmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
                $stmt->bindParam(':price', $price, PDO::PARAM_STR);
                $stmt->execute();
            }

            // Actualizar el total de la orden
            $query = "UPDATE `Order` SET total = :total WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':total', $total, PDO::PARAM_STR);
            $stmt->bindParam(':id', $orderId, PDO::PARAM_INT);
            $stmt->execute();

            $this->db->commit();

            header("HTTP/1.1 201 Created");
            echo json_encode([
                "message" => "Orden creada exitosamente",
                "id" => $orderId,
                "total" => $total
            ]);
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
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