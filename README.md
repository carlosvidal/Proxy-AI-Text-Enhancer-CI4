# LLM Proxy

Un servicio proxy para APIs de modelos de lenguaje grande (LLM) desarrollado con CodeIgniter 4.

![LLM Proxy](https://via.placeholder.com/800x400?text=LLM+Proxy)

## Descripción

LLM Proxy es un servicio intermediario que unifica múltiples proveedores de LLM bajo una única API. Está diseñado para facilitar el acceso a diferentes modelos de lenguaje como OpenAI, Anthropic, Mistral y otros, proporcionando funcionalidades adicionales como gestión de cuotas, registro de uso y caché de respuestas.

## Características

- **API Unificada:** Accede a múltiples proveedores de LLM a través de un único endpoint
- **Gestión de Cuotas:** Controla el uso por tenant y usuario
- **Registro Detallado:** Seguimiento completo de todas las solicitudes y respuestas
- **Soporte de Streaming:** Recibe respuestas en tiempo real a medida que se generan
- **Caché de Respuestas:** Ahorra costos y mejora tiempos de respuesta
- **Soporte para Imágenes:** Compatible con modelos multimodales
- **Panel de Administración:** Visualiza estadísticas de uso y gestiona cuotas

## Proveedores Soportados

- OpenAI (GPT-3.5-Turbo, GPT-4-Turbo)
- Anthropic (Claude 3 Opus, Claude 3 Sonnet)
- Mistral (Mistral Large, Medium, Small)
- DeepSeek (DeepSeek Chat, DeepSeek Coder)
- Cohere (Command, Command Light)
- Google (Gemini Pro, Gemini Pro Vision)

## Requisitos

- PHP 7.4 o superior
- SQLite 3
- Apache/Nginx con mod_rewrite habilitado
- Composer (opcional, para dependencias)

## Instalación

1. **Clonar el repositorio:**

   ```bash
   git clone https://your-repository-url/llm-proxy.git
   cd llm-proxy
   ```

2. **Configurar el archivo .env:**

   Copie el archivo `env` a `.env` y configure sus claves API y otros parámetros:

   ```bash
   cp env .env
   ```

   Edite el archivo `.env` con sus claves API:

   ```ini
   OPENAI_API_KEY=sk-your-openai-api-key
   ANTHROPIC_API_KEY=sk-ant-api-key
   MISTRAL_API_KEY=your-mistral-api-key
   # ... otras claves API
   ```

3. **Crear la base de datos SQLite:**

   Asegúrese de que el directorio `application/database` tenga permisos de escritura:

   ```bash
   mkdir -p application/database
   chmod 755 application/database
   ```

4. **Ejecutar migraciones:**

   Acceda a la URL de migración para crear las tablas necesarias:

   ```
   http://your-site.com/migrate
   ```

   O desde la línea de comandos (si está disponible):

   ```bash
   php index.php migrate
   ```

5. **Configurar permisos:**

   Asegúrese de que los directorios de logs y caché tengan permisos adecuados:

   ```bash
   chmod -R 755 application/logs
   chmod -R 755 application/cache
   ```

## Uso

### Endpoint principal

```
POST /api/llm-proxy
```

#### Ejemplo de solicitud:

```json
{
  "provider": "openai",
  "model": "gpt-4-turbo",
  "messages": [
    {"role": "system", "content": "You are a helpful assistant."},
    {"role": "user", "content": "Tell me about LLM proxies."}
  ],
  "temperature": 0.7,
  "stream": true,
  "tenantId": "tenant123",
  "userId": "user456"
}
```

### Verificación de cuota

```
GET /api/quota?tenantId=tenant123&userId=user456
```

### Verificación de estado

```
GET /api/llm-proxy/status
```

### Dashboard de uso

```
GET /usage
```

## Configuración Avanzada

### Configuración CORS

Edite el archivo `application/config/llm_proxy.php` para ajustar la configuración CORS:

```php
$config['allowed_origins'] = ['http://your-site.com', 'https://your-app.com'];
```

### Configuración de cuotas

Ajuste las cuotas predeterminadas en `application/config/llm_proxy.php`:

```php
$config['default_quota'] = 100000; // Tokens por usuario
$config['rate_limit_requests'] = 10; // Solicitudes por minuto
```

## Componente Web Cliente

LLM Proxy incluye un componente web cliente denominado `ai-text-enhancer` que permite a los usuarios finales mejorar textos utilizando LLMs.

### Uso del componente:

```html
<script type="module" src="your-site.com/_/js/ai-text-enhancer.umd.js"></script>

<ai-text-enhancer
  editor-id="my-editor"
  api-provider="openai"
  api-model="gpt-4-turbo"
  language="es">
</ai-text-enhancer>

<textarea id="my-editor">
  Este es un texto de ejemplo que será mejorado usando IA.
</textarea>
```

## Estructura del Proyecto

```
llm-proxy/
├── application/
│   ├── config/
│   │   ├── llm_proxy.php       # Configuración principal
│   │   ├── routes.php          # Rutas de la API
│   │   └── ...
│   ├── controllers/
│   │   ├── LlmProxy.php        # Controlador principal
│   │   ├── Migrate.php         # Controlador de migraciones
│   │   └── Usage.php           # Controlador de dashboard
│   ├── migrations/
│   │   └── 001_create_llm_proxy_tables.php
│   ├── models/
│   │   └── Llm_proxy_model.php
│   └── views/
│       ├── usage/              # Vistas del dashboard
│       └── ...
├── .htaccess                   # Configuración Apache
├── .env                        # Variables de entorno
└── _/
    └── js/
        └── ai-text-enhancer.umd.js  # Componente web cliente
```

## Desarrollo

### Contribución

1. Fork el repositorio
2. Crea una rama para tu característica (`git checkout -b feature/amazing-feature`)
3. Haz commit de tus cambios (`git commit -m 'Add amazing feature'`)
4. Push a la rama (`git push origin feature/amazing-feature`)
5. Abre un Pull Request

### Entorno de Desarrollo

Para desarrollo local, se recomienda usar:

```
$config['base_url'] = 'http://llmproxy.test:8080/';
```

Y configurar un host virtual en su servidor web local.

## Licencia

Este proyecto está licenciado bajo la Licencia MIT - vea el archivo LICENSE para más detalles.

## Contacto

Nombre – su@email.com

Link del proyecto: [https://github.com/your-username/llm-proxy](https://github.com/your-username/llm-proxy)
