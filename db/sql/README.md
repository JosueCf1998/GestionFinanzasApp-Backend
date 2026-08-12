# SQL y migraciones

Este directorio debe versionar migraciones, scripts de esquema sin datos sensibles,
diagramas y documentación.

`backup.sql` y `finanzas_db.sql` permanecen disponibles localmente, pero se excluyen
de Git porque contienen filas de usuarios, sesiones, tokens y hashes de contraseñas.

Para compartir una base de prueba se debe generar un dump anonimizado que elimine
sesiones y sustituya identidades, emails, contraseñas y datos financieros reales.
