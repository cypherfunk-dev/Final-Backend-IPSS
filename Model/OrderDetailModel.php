<?php
class OrderDetailModel extends Database
{
    public function getOrderDetails($orderId)
    {
        try {
            $query = "SELECT od.*, sj.name as jersey_name, s.name as size_name 
                     FROM order_details od 
                     LEFT JOIN sports_jerseys sj ON od.jersey_id = sj.id 
                     LEFT JOIN sizes s ON od.size_id = s.id 
                     WHERE od.order_id = :order_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error al obtener detalles de orden: " . $e->getMessage());
        }
    }

    public function getOrderDetailById(int $id)
    {
        try {
            $query = "SELECT od.*, sj.name as jersey_name, s.name as size_name 
                     FROM order_details od 
                     LEFT JOIN sports_jerseys sj ON od.jersey_id = sj.id 
                     LEFT JOIN sizes s ON od.size_id = s.id 
                     WHERE od.id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error al obtener detalle de orden: " . $e->getMessage());
        }
    }

    public function createOrderDetail(array $data)
    {
        try {
            $this->conn->beginTransaction();

            // Verificar stock disponible
            $stockQuery = "SELECT stock FROM size_availability 
                          WHERE jersey_id = :jersey_id AND size_id = :size_id";
            $stockStmt = $this->conn->prepare($stockQuery);
            $stockStmt->bindParam(':jersey_id', $data['jersey_id'], PDO::PARAM_INT);
            $stockStmt->bindParam(':size_id', $data['size_id'], PDO::PARAM_INT);
            $stockStmt->execute();
            $stock = $stockStmt->fetch(PDO::FETCH_ASSOC);

            if (!$stock || $stock['stock'] < $data['quantity']) {
                throw new Exception("Stock insuficiente para la talla seleccionada");
            }

            // Insertar detalle de orden
            $query = "INSERT INTO order_details (order_id, jersey_id, size_id, quantity, price) 
                     VALUES (:order_id, :jersey_id, :size_id, :quantity, :price)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':order_id', $data['order_id'], PDO::PARAM_INT);
            $stmt->bindParam(':jersey_id', $data['jersey_id'], PDO::PARAM_INT);
            $stmt->bindParam(':size_id', $data['size_id'], PDO::PARAM_INT);
            $stmt->bindParam(':quantity', $data['quantity'], PDO::PARAM_INT);
            $stmt->bindParam(':price', $data['price'], PDO::PARAM_STR);
            $stmt->execute();
            
            $detailId = $this->conn->lastInsertId();

            // Actualizar stock
            $updateStockQuery = "UPDATE size_availability 
                               SET stock = stock - :quantity 
                               WHERE jersey_id = :jersey_id AND size_id = :size_id";
            $updateStockStmt = $this->conn->prepare($updateStockQuery);
            $updateStockStmt->bindParam(':quantity', $data['quantity'], PDO::PARAM_INT);
            $updateStockStmt->bindParam(':jersey_id', $data['jersey_id'], PDO::PARAM_INT);
            $updateStockStmt->bindParam(':size_id', $data['size_id'], PDO::PARAM_INT);
            $updateStockStmt->execute();
            
            $this->conn->commit();
            return ['id' => $detailId];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            throw new Exception("Error al crear detalle de orden: " . $e->getMessage());
        }
    }

    public function updateOrderDetail(int $id, array $data)
    {
        try {
            $this->conn->beginTransaction();

            // Obtener detalle actual
            $currentDetail = $this->getOrderDetailById($id);
            if (!$currentDetail) {
                throw new Exception("Detalle de orden no encontrado");
            }

            // Si se está actualizando la cantidad, verificar stock
            if (isset($data['quantity']) && $data['quantity'] != $currentDetail['quantity']) {
                $stockQuery = "SELECT stock FROM size_availability 
                             WHERE jersey_id = :jersey_id AND size_id = :size_id";
                $stockStmt = $this->conn->prepare($stockQuery);
                $stockStmt->bindParam(':jersey_id', $currentDetail['jersey_id'], PDO::PARAM_INT);
                $stockStmt->bindParam(':size_id', $currentDetail['size_id'], PDO::PARAM_INT);
                $stockStmt->execute();
                $stock = $stockStmt->fetch(PDO::FETCH_ASSOC);

                $newStock = $stock['stock'] + $currentDetail['quantity'] - $data['quantity'];
                if ($newStock < 0) {
                    throw new Exception("Stock insuficiente para la actualización");
                }

                // Actualizar stock
                $updateStockQuery = "UPDATE size_availability 
                                   SET stock = :stock 
                                   WHERE jersey_id = :jersey_id AND size_id = :size_id";
                $updateStockStmt = $this->conn->prepare($updateStockQuery);
                $updateStockStmt->bindParam(':stock', $newStock, PDO::PARAM_INT);
                $updateStockStmt->bindParam(':jersey_id', $currentDetail['jersey_id'], PDO::PARAM_INT);
                $updateStockStmt->bindParam(':size_id', $currentDetail['size_id'], PDO::PARAM_INT);
                $updateStockStmt->execute();
            }

            // Actualizar detalle
            $query = "UPDATE order_details 
                     SET quantity = :quantity, 
                         price = :price 
                     WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':quantity', $data['quantity'], PDO::PARAM_INT);
            $stmt->bindParam(':price', $data['price'], PDO::PARAM_STR);
            
            $stmt->execute();
            
            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            throw new Exception("Error al actualizar detalle de orden: " . $e->getMessage());
        }
    }

    public function deleteOrderDetail(int $id)
    {
        try {
            $this->conn->beginTransaction();

            // Obtener detalle actual
            $currentDetail = $this->getOrderDetailById($id);
            if (!$currentDetail) {
                throw new Exception("Detalle de orden no encontrado");
            }

            // Restaurar stock
            $updateStockQuery = "UPDATE size_availability 
                               SET stock = stock + :quantity 
                               WHERE jersey_id = :jersey_id AND size_id = :size_id";
            $updateStockStmt = $this->conn->prepare($updateStockQuery);
            $updateStockStmt->bindParam(':quantity', $currentDetail['quantity'], PDO::PARAM_INT);
            $updateStockStmt->bindParam(':jersey_id', $currentDetail['jersey_id'], PDO::PARAM_INT);
            $updateStockStmt->bindParam(':size_id', $currentDetail['size_id'], PDO::PARAM_INT);
            $updateStockStmt->execute();

            // Eliminar detalle
            $query = "DELETE FROM order_details WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            throw new Exception("Error al eliminar detalle de orden: " . $e->getMessage());
        }
    }
} 