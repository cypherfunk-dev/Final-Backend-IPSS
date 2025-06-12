# API de Camisetas Deportivas

## Requisitos Previos
- PHP 7.4 o superior
- Apache con mod_rewrite habilitado
- MySQL 5.7 o superior
- Composer (para gestionar dependencias)

## Instalación

docker-compose up -d  

docker logs final-backend-ipss-mysql-1

php -S localhost:8000 -t .

## Acceso a la API

### Documentación
La documentación de la API está disponible en:
```
http://localhost/swagger-ui.html
```

### Endpoints Principales

1. **Camisetas**
```
GET    /api/sports-jersey          # Listar camisetas
POST   /api/sports-jersey          # Crear camiseta
GET    /api/sports-jersey/{id}     # Obtener camiseta por ID
PUT    /api/sports-jersey/{id}     # Actualizar camiseta
DELETE /api/sports-jersey/{id}     # Eliminar camiseta
```

2. **Tallas**
```
GET    /api/size                   # Listar tallas
POST   /api/size                   # Crear talla
GET    /api/size/{id}             # Obtener talla por ID
PUT    /api/size/{id}             # Actualizar talla
DELETE /api/size/{id}             # Eliminar talla
```

3. **Países**
```
GET    /api/country               # Listar países
POST   /api/country               # Crear país
GET    /api/country/{id}         # Obtener país por ID
PUT    /api/country/{id}         # Actualizar país
DELETE /api/country/{id}         # Eliminar país
```

4. **Clubes**
```
GET    /api/club                  # Listar clubes
POST   /api/club                  # Crear club
GET    /api/club/{id}            # Obtener club por ID
PUT    /api/club/{id}            # Actualizar club
DELETE /api/club/{id}            # Eliminar club
```

5. **Órdenes**
```
GET    /api/order                 # Listar órdenes
POST   /api/order                 # Crear orden
GET    /api/order/{id}           # Obtener orden por ID
PUT    /api/order/{id}           # Actualizar orden
DELETE /api/order/{id}           # Eliminar orden
```

### Ejemplos de Uso

1. **Listar camisetas con precio para cliente preferencial**:
```bash
curl -X GET "http://localhost/api/sports-jersey?client_id=90minutos"
```

2. **Listar camisetas con precio base**:
```bash
curl -X GET "http://localhost/api/sports-jersey?client_id=tdeportes"
```

3. **Crear una nueva camiseta**:
```bash
curl -X POST "http://localhost/api/sports-jersey" \
     -H "Content-Type: application/json" \
     -d '{
           "name": "Camiseta Barcelona 2024",
           "description": "Camiseta oficial del FC Barcelona",
           "precio": 89.99,
           "precio_oferta": 79.99,
           "country_id": 1,
           "club_id": 1
         }'
```

### Parámetros Comunes

- `limit`: Número máximo de resultados (por defecto: 10)
- `client_id`: ID del cliente ("90minutos" o "tdeportes")

### Respuestas

La API devuelve respuestas en formato JSON con los siguientes códigos HTTP:
- 200: Éxito
- 201: Creado
- 400: Error en la solicitud
- 404: No encontrado
- 500: Error interno del servidor

## Soporte

Para reportar problemas o solicitar ayuda, por favor crea un issue en el repositorio.