<?php
class OrderModel extends Database
{
    public function getOrders($limit = 10)
    {
        try {
            $query = "SELECT o.*, u.username as user_name 
                     FROM orders o 
                     LEFT JOIN users u ON o.user_id = u.id 
                     WHERE o.deleted IS NULL 
                     ORDER BY o.created_at DESC 
                     LIMIT :limit";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error al obtener órdenes: " . $e->getMessage());
        }
    }

    public function getOrderById(int $id)
    {
        try {
            $query = "SELECT o.*, u.username as user_name 
                     FROM orders o 
                     LEFT JOIN users u ON o.user_id = u.id 
                     WHERE o.id = :id AND o.deleted IS NULL";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error al obtener orden: " . $e->getMessage());
        }
    }

    public function getOrdersByUser(int $userId)
    {
        try {
            $query = "SELECT o.*, u.username as user_name 
                     FROM orders o 
                     LEFT JOIN users u ON o.user_id = u.id 
                     WHERE o.user_id = :userId AND o.deleted IS NULL 
                     ORDER BY o.created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error al obtener órdenes del usuario: " . $e->getMessage());
        }
    }

    public function createOrder(array $data)
    {
        try {
            $this->conn->beginTransaction();

            $query = "INSERT INTO orders (user_id, total_amount, status, shipping_address, payment_method) 
                     VALUES (:user_id, :total_amount, :status, :shipping_address, :payment_method)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(':total_amount', $data['total_amount'], PDO::PARAM_STR);
            $stmt->bindParam(':status', $data['status'], PDO::PARAM_STR);
            $stmt->bindParam(':shipping_address', $data['shipping_address'], PDO::PARAM_STR);
            $stmt->bindParam(':payment_method', $data['payment_method'], PDO::PARAM_STR);
            $stmt->execute();
            
            $orderId = $this->conn->lastInsertId();
            
            $this->conn->commit();
            return ['id' => $orderId];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            throw new Exception("Error al crear orden: " . $e->getMessage());
        }
    }

    public function updateOrder(int $id, array $data)
    {
        try {
            $query = "UPDATE orders 
                     SET status = :status, 
                         shipping_address = :shipping_address, 
                         payment_method = :payment_method, 
                         updated_at = CURRENT_TIMESTAMP 
                     WHERE id = :id AND deleted IS NULL";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':status', $data['status'], PDO::PARAM_STR);
            $stmt->bindParam(':shipping_address', $data['shipping_address'], PDO::PARAM_STR);
            $stmt->bindParam(':payment_method', $data['payment_method'], PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("Error al actualizar orden: " . $e->getMessage());
        }
    }

    public function updateOrderStatus(int $id, string $status)
    {
        try {
            $query = "UPDATE orders 
                     SET status = :status, 
                         updated_at = CURRENT_TIMESTAMP 
                     WHERE id = :id AND deleted IS NULL";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("Error al actualizar estado de orden: " . $e->getMessage());
        }
    }

    public function deleteOrder(int $id)
    {
        try {
            $query = "UPDATE orders 
                     SET deleted = CURRENT_TIMESTAMP 
                     WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("Error al eliminar orden: " . $e->getMessage());
        }
    }

    public function getOrderWithDetails(int $id)
    {
        try {
            $order = $this->getOrderById($id);
            if (!$order) {
                return null;
            }

            $query = "SELECT od.*, sj.name as jersey_name, s.name as size_name 
                     FROM order_details od 
                     LEFT JOIN sports_jerseys sj ON od.jersey_id = sj.id 
                     LEFT JOIN sizes s ON od.size_id = s.id 
                     WHERE od.order_id = :order_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':order_id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $order['details'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $order;
        } catch (PDOException $e) {
            throw new Exception("Error al obtener orden con detalles: " . $e->getMessage());
        }
    }
} 