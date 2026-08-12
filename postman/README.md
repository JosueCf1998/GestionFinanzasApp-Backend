# Postman

Las colecciones locales se excluyen de Git porque pueden contener JWT, emails,
identificadores y cuerpos de solicitudes sensibles.

Para publicar una colección de ejemplo nueva:

1. usar `{{base_url}}` para la URL del backend;
2. usar `Bearer {{token}}` y dejar `token` sin valor exportado;
3. usar exclusivamente identidades `example.com`;
4. eliminar cookies y valores actuales de variables;
5. comprobar que no existan JWT (`eyJ...`), contraseñas o IDs reales;
6. guardar el ejemplo con un nombre que no termine en `.postman_collection.json`,
   o añadirlo expresamente como excepción en `.gitignore` después de revisarlo.
