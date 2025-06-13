<?php
class OrderDetailModel extends Database
{
    public function getOrderDetails($orderId)
    {
        try {
            $query = "SELECT od.*, sj.name as jersey_name, s.name as size_name 
                     FROM Order_detail od 
                     LEFT JOIN Sports_jersey sj ON od.iditem = sj.id 
                     LEFT JOIN Size s ON od.idsize = s.id 
                     WHERE od.orderid = ?";
            
            $stmt = $this->getConnection()->prepare($query);
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta: " . $this->getConnection()->error);
            }
            
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error al obtener detalles de orden: " . $e->getMessage());
        }
    }

    public function getOrderDetailById(int $id)
    {
        try {
            $query = "SELECT od.*, sj.name as jersey_name, s.name as size_name 
                     FROM Order_detail od 
                     LEFT JOIN Sports_jersey sj ON od.iditem = sj.id 
                     LEFT JOIN Size s ON od.idsize = s.id 
                     WHERE od.iddetail = ?";
            
            $stmt = $this->getConnection()->prepare($query);
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta: " . $this->getConnection()->error);
            }
            
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            return $result->fetch_assoc();
        } catch (Exception $e) {
            throw new Exception("Error al obtener detalle de orden: " . $e->getMessage());
        }
    }

    public function createOrderDetail(array $data)
    {
        try {
            $this->getConnection()->begin_transaction();

            // Verificar stock disponible
            $stockQuery = "SELECT stock FROM Size_availability 
                          WHERE iditem = ? AND idsize = ?";
            $stockStmt = $this->getConnection()->prepare($stockQuery);
            if (!$stockStmt) {
                throw new Exception("Error al preparar la consulta de stock: " . $this->getConnection()->error);
            }
            
            $stockStmt->bind_param("ii", $data['iditem'], $data['idsize']);
            $stockStmt->execute();
            $result = $stockStmt->get_result();
            $stock = $result->fetch_assoc();

            if (!$stock || $stock['stock'] < $data['quantity']) {
                throw new Exception("Stock insuficiente para la talla seleccionada");
            }

            // Insertar detalle de orden
            $query = "INSERT INTO Order_detail (orderid, iditem, idsize, quantity, price) 
                     VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $this->getConnection()->prepare($query);
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta de inserción: " . $this->getConnection()->error);
            }
            
            $stmt->bind_param("iiids", 
                $data['orderid'], 
                $data['iditem'], 
                $data['idsize'], 
                $data['quantity'], 
                $data['price']
            );
            $stmt->execute();
            
            $detailId = $stmt->insert_id;

            // Actualizar stock
            $updateStockQuery = "UPDATE Size_availability 
                               SET stock = stock - ? 
                               WHERE iditem = ? AND idsize = ?";
            $updateStockStmt = $this->getConnection()->prepare($updateStockQuery);
            if (!$updateStockStmt) {
                throw new Exception("Error al preparar la actualización de stock: " . $this->getConnection()->error);
            }
            
            $updateStockStmt->bind_param("iii", $data['quantity'], $data['iditem'], $data['idsize']);
            $updateStockStmt->execute();
            
            $this->getConnection()->commit();
            return ['id' => $detailId];
        } catch (Exception $e) {
            $this->getConnection()->rollback();
            throw new Exception("Error al crear detalle de orden: " . $e->getMessage());
        }
    }

    public function updateOrderDetail(int $id, array $data)
    {
        try {
            $this->getConnection()->begin_transaction();

            // Obtener detalle actual
            $currentDetail = $this->getOrderDetailById($id);
            if (!$currentDetail) {
                throw new Exception("Detalle de orden no encontrado");
            }

            // Si se está actualizando la cantidad, verificar stock
            if (isset($data['quantity']) && $data['quantity'] != $currentDetail['quantity']) {
                $stockQuery = "SELECT stock FROM Size_availability 
                             WHERE iditem = ? AND idsize = ?";
                $stockStmt = $this->getConnection()->prepare($stockQuery);
                if (!$stockStmt) {
                    throw new Exception("Error al preparar la consulta de stock: " . $this->getConnection()->error);
                }
                
                $stockStmt->bind_param("ii", $currentDetail['iditem'], $currentDetail['idsize']);
                $stockStmt->execute();
                $result = $stockStmt->get_result();
                $stock = $result->fetch_assoc();

                $newStock = $stock['stock'] + $currentDetail['quantity'] - $data['quantity'];
                if ($newStock < 0) {
                    throw new Exception("Stock insuficiente para la actualización");
                }

                // Actualizar stock
                $updateStockQuery = "UPDATE Size_availability 
                                   SET stock = ? 
                                   WHERE iditem = ? AND idsize = ?";
                $updateStockStmt = $this->getConnection()->prepare($updateStockQuery);
                if (!$updateStockStmt) {
                    throw new Exception("Error al preparar la actualización de stock: " . $this->getConnection()->error);
                }
                
                $updateStockStmt->bind_param("iii", $newStock, $currentDetail['iditem'], $currentDetail['idsize']);
                $updateStockStmt->execute();
            }

            // Actualizar detalle
            $query = "UPDATE Order_detail 
                     SET quantity = ?, 
                         price = ? 
                     WHERE iddetail = ?";
            
            $stmt = $this->getConnection()->prepare($query);
            if (!$stmt) {
                throw new Exception("Error al preparar la actualización: " . $this->getConnection()->error);
            }
            
            $stmt->bind_param("isi", $data['quantity'], $data['price'], $id);
            $stmt->execute();
            
            $this->getConnection()->commit();
            return true;
        } catch (Exception $e) {
            $this->getConnection()->rollback();
            throw new Exception("Error al actualizar detalle de orden: " . $e->getMessage());
        }
    }

    public function deleteOrderDetail(int $id)
    {
        try {
            $this->getConnection()->begin_transaction();

            // Obtener detalle actual
            $currentDetail = $this->getOrderDetailById($id);
            if (!$currentDetail) {
                throw new Exception("Detalle de orden no encontrado");
            }

            // Restaurar stock
            $updateStockQuery = "UPDATE Size_availability 
                               SET stock = stock + ? 
                               WHERE iditem = ? AND idsize = ?";
            $updateStockStmt = $this->getConnection()->prepare($updateStockQuery);
            if (!$updateStockStmt) {
                throw new Exception("Error al preparar la actualización de stock: " . $this->getConnection()->error);
            }
            
            $updateStockStmt->bind_param("iii", $currentDetail['quantity'], $currentDetail['iditem'], $currentDetail['idsize']);
            $updateStockStmt->execute();

            // Eliminar detalle
            $query = "DELETE FROM Order_detail WHERE iddetail = ?";
            $stmt = $this->getConnection()->prepare($query);
            if (!$stmt) {
                throw new Exception("Error al preparar la eliminación: " . $this->getConnection()->error);
            }
            
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            $this->getConnection()->commit();
            return true;
        } catch (Exception $e) {
            $this->getConnection()->rollback();
            throw new Exception("Error al eliminar detalle de orden: " . $e->getMessage());
        }
    }
} 