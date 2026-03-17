# Agenda API - PHP + Supabase + Railway

## Estructura del proyecto

```
agenda-api/
├── index.php                  # Bienvenida / health check
├── nixpacks.toml              # Configuración para Railway
├── config/
│   ├── database.php           # Conexión a Supabase
│   └── helpers.php            # Funciones comunes
└── api/
    ├── contactos/index.php    # CRUD contactos
    ├── eventos/index.php      # CRUD eventos
    └── tareas/index.php       # CRUD tareas
```

---

## 1. Configurar Supabase

En `config/database.php` reemplaza con tus datos de Supabase:
`Settings > Database > Connection parameters`

O configura variables de entorno en Railway:

| Variable    | Valor                          |
|-------------|-------------------------------|
| DB_HOST     | db.xxxx.supabase.co           |
| DB_PORT     | 5432                          |
| DB_NAME     | postgres                      |
| DB_USER     | postgres                      |
| DB_PASSWORD | tu_password                   |

---

## 2. Subir a Railway

1. Crea una cuenta en [railway.app](https://railway.app)
2. Nuevo proyecto → "Deploy from GitHub repo"
3. Sube esta carpeta a un repositorio GitHub
4. Railway detecta PHP automáticamente con `nixpacks.toml`
5. Ve a Variables y agrega las variables de entorno de Supabase
6. Railway te dará una URL pública: `https://agenda-api.up.railway.app`

---

## 3. Endpoints disponibles

### CONTACTOS
| Método | URL                        | Descripción         |
|--------|----------------------------|---------------------|
| GET    | /api/contactos/            | Listar todos        |
| GET    | /api/contactos/?id=1       | Obtener uno         |
| POST   | /api/contactos/            | Crear contacto      |
| PUT    | /api/contactos/?id=1       | Actualizar contacto |
| DELETE | /api/contactos/?id=1       | Eliminar contacto   |

### EVENTOS
| Método | URL                        | Descripción         |
|--------|----------------------------|---------------------|
| GET    | /api/eventos/              | Listar todos        |
| GET    | /api/eventos/?id=1         | Obtener uno         |
| POST   | /api/eventos/              | Crear evento        |
| PUT    | /api/eventos/?id=1         | Actualizar evento   |
| DELETE | /api/eventos/?id=1         | Eliminar evento     |

### TAREAS
| Método | URL                              | Descripción              |
|--------|----------------------------------|--------------------------|
| GET    | /api/tareas/                     | Listar todas             |
| GET    | /api/tareas/?id=1                | Obtener una              |
| GET    | /api/tareas/?completada=false    | Filtrar pendientes       |
| POST   | /api/tareas/                     | Crear tarea              |
| PUT    | /api/tareas/?id=1                | Actualizar / completar   |
| DELETE | /api/tareas/?id=1                | Eliminar tarea           |

---

## 4. Ejemplos de uso en Android Studio (Retrofit)

### Dependencia build.gradle
```gradle
implementation 'com.squareup.retrofit2:retrofit:2.9.0'
implementation 'com.squareup.retrofit2:converter-gson:2.9.0'
```

### Llamada para obtener contactos
```java
// ApiService.java
@GET("api/contactos/")
Call<List<Contacto>> getContactos();

// MainActivity.java
ApiService api = new Retrofit.Builder()
    .baseUrl("https://TU-APP.up.railway.app/")
    .addConverterFactory(GsonConverterFactory.create())
    .build()
    .create(ApiService.class);

api.getContactos().enqueue(new Callback<List<Contacto>>() {
    @Override
    public void onResponse(Call<List<Contacto>> call, Response<List<Contacto>> response) {
        List<Contacto> lista = response.body();
    }
    @Override
    public void onFailure(Call<List<Contacto>> call, Throwable t) { }
});
```
