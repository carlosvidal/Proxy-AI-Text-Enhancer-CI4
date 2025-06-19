# AI Text Enhancer Proxy API Documentation

Esta documentación describe el API del proxy para AI Text Enhancer, que permite el acceso controlado a múltiples proveedores de Large Language Models (LLM) con gestión de cuotas, autenticación y logging.

## 📋 Índice

- [Características](#características)
- [Documentación](#documentación)
- [Autenticación](#autenticación)
- [Endpoints](#endpoints)
- [Códigos de Error](#códigos-de-error)
- [Ejemplos de Uso](#ejemplos-de-uso)
- [Límites y Cuotas](#límites-y-cuotas)

## ✨ Características

- **Múltiples Proveedores**: Soporte para OpenAI, Anthropic, Google, Mistral, DeepSeek y Azure
- **Control de Cuotas**: Gestión de límites diarios y mensuales por usuario
- **Auto-creación de Usuarios**: Creación automática de usuarios al primer uso
- **Streaming en Tiempo Real**: Respuestas streaming con Server-Sent Events
- **Soporte Multi-modal**: Texto e imágenes (modelos compatibles)
- **Logging Detallado**: Registro completo de uso para facturación y análisis
- **Autenticación por Dominio**: Control de acceso basado en origen CORS

## 📚 Documentación

### OpenAPI/Swagger

```bash
# Archivo de especificación OpenAPI 3.0
./docs/openapi.yaml
```

**Visualizar documentación:**
- [Swagger Editor](https://editor.swagger.io/) - Pega el contenido del archivo YAML
- [Swagger UI](https://swagger.io/tools/swagger-ui/) - Para interfaces interactivas

### Postman Collection

```bash
# Colección de Postman con ejemplos completos
./docs/AI-Text-Enhancer-Proxy.postman_collection.json
```

**Importar en Postman:**
1. Abrir Postman
2. File → Import
3. Seleccionar el archivo JSON
4. Configurar variables de entorno

## 🔐 Autenticación

El API utiliza un sistema de autenticación basado en:

1. **Tenant ID**: Identificador del inquilino (`tenantId`)
2. **Button ID**: Configuración del botón específico (`buttonId`)
3. **Domain Validation**: Validación CORS por origen
4. **User ID**: Identificador del usuario externo (`userId`)

### Variables Requeridas

```json
{
  "tenantId": "ten-xxxxxxxx-xxxxxxxx",
  "buttonId": "btn-xxx",
  "userId": "USER_IDENTIFIER"
}
```

## 🛠 Endpoints

### POST /api/llm-proxy

Endpoint principal para procesar requests a modelos de lenguaje.

**URL:** `https://llmproxy.mitienda.host/index.php/api/llm-proxy`

#### Request Body

```json
{
  "messages": [
    {
      "role": "system|user|assistant",
      "content": "string | array"
    }
  ],
  "temperature": 0.7,
  "stream": false,
  "tenantId": "ten-xxxxxxxx-xxxxxxxx",
  "userId": "USER_ID",
  "buttonId": "btn-xxx",
  "hasImage": false
}
```

#### Response (Éxito)

```json
{
  "success": true,
  "response": "Generated text response...",
  "tokens_in": 50,
  "tokens_out": 150,
  "quota": {
    "monthly_remaining": 8850,
    "daily_remaining": 9850,
    "monthly_used": 1150,
    "daily_used": 150
  }
}
```

## ⚠️ Códigos de Error

| Código | Tipo | Descripción |
|--------|------|-------------|
| `200` | ✅ Success | Request procesado exitosamente |
| `400` | ❌ Bad Request | Parámetros inválidos o faltantes |
| `401` | 🔒 Unauthorized | Tenant o configuración inválida |
| `403` | 🚫 Forbidden | Dominio no autorizado |
| `429` | 🚦 Too Many Requests | Cuota excedida |
| `500` | 💥 Internal Error | Error interno del servidor |

### Tipos de Error

- `validation_error`: Parámetros faltantes o inválidos
- `authentication_error`: Credenciales inválidas
- `authorization_error`: Permisos insuficientes
- `quota_exceeded`: Límite de tokens excedido
- `server_error`: Error interno

## 💡 Ejemplos de Uso

### 1. Enhancement Básico

```bash
curl -X POST "https://llmproxy.mitienda.host/index.php/api/llm-proxy" \
  -H "Content-Type: application/json" \
  -H "Origin: https://example.com" \
  -d '{
    "messages": [
      {
        "role": "system",
        "content": "You are a professional content enhancer."
      },
      {
        "role": "user", 
        "content": "Improve this text: Hello world"
      }
    ],
    "temperature": 0.7,
    "stream": false,
    "tenantId": "ten-684cc05b-5d6457e5",
    "userId": "DEMO",
    "buttonId": "btn-001"
  }'
```

### 2. Respuesta Streaming

```javascript
// JavaScript con fetch para streaming
const response = await fetch('/api/llm-proxy', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Origin': 'https://example.com'
  },
  body: JSON.stringify({
    messages: [
      {
        role: "user",
        content: "Write a short story about AI"
      }
    ],
    stream: true,
    tenantId: "ten-684cc05b-5d6457e5",
    userId: "USER123",
    buttonId: "btn-002"
  })
});

// Procesar stream
const reader = response.body.getReader();
while (true) {
  const { done, value } = await reader.read();
  if (done) break;
  
  const chunk = new TextDecoder().decode(value);
  console.log('Received:', chunk);
}
```

### 3. Request Multi-modal

```json
{
  "messages": [
    {
      "role": "user",
      "content": [
        {
          "type": "text",
          "text": "Describe this image:"
        },
        {
          "type": "image_url",
          "image_url": {
            "url": "data:image/jpeg;base64,..."
          }
        }
      ]
    }
  ],
  "tenantId": "ten-684cc05b-5d6457e5",
  "userId": "USER123",
  "buttonId": "btn-vision",
  "hasImage": true
}
```

## 📊 Límites y Cuotas

### Cuotas por Usuario

- **Cuota Diaria**: Configurable por usuario (default: 10,000 tokens)
- **Cuota Mensual**: Configurable por usuario (default: 10,000 tokens)
- **Reset Automático**: Diario a medianoche, mensual el día 1

### Información de Cuota en Respuestas

```json
{
  "quota": {
    "monthly_remaining": 8850,    // Tokens restantes este mes
    "daily_remaining": 9850,      // Tokens restantes hoy  
    "monthly_used": 1150,         // Tokens usados este mes
    "daily_used": 150             // Tokens usados hoy
  }
}
```

### Respuesta de Cuota Excedida

```json
{
  "success": false,
  "error": "Daily quota exceeded. Used: 10000/10000 tokens. Quota resets tomorrow.",
  "error_type": "quota_exceeded"
}
```

## 🔧 Configuración

### Variables de Entorno Postman

```json
{
  "base_url": "https://llmproxy.mitienda.host/index.php",
  "tenant_id": "ten-684cc05b-5d6457e5",
  "button_id": "btn-001", 
  "user_id": "DEMO",
  "origin_domain": "https://example.com"
}
```

### Headers Requeridos

```http
Content-Type: application/json
Origin: https://your-domain.com
```

## 📝 Notas Importantes

1. **CORS**: El origen debe estar configurado en el tenant
2. **Auto-creación**: Los usuarios se crean automáticamente en el primer uso
3. **Streaming**: Use `Accept: text/plain` para responses streaming
4. **Modelos**: Cada botón está configurado con un proveedor y modelo específico
5. **Rate Limiting**: Las cuotas se aplican por usuario y tenant

## 🤝 Soporte

Para soporte técnico o preguntas sobre la API:

- **GitHub**: [AI-Text-Enhancer Issues](https://github.com/carlosvidal/AI-Text-Enhancer/issues)
- **Documentación**: Esta guía y los archivos OpenAPI/Postman
- **Logs**: Revise los logs del servidor para debugging detallado