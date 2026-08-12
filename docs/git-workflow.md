# Flujo de Git

## Clonar e instalar

```bash
git clone <repositorio>
cd GestionFinanzasApp-Backend
composer install --no-interaction
```

No se versionan `vendor/` ni `node_modules/`. Este backend no requiere npm para
ejecutarse; sus dependencias PHP se restauran desde `composer.lock`.

## Antes de publicar

```bash
git status
git diff --check
composer validate --no-check-publish
```

Comprobar que no se incluyan:

- `.env` o `config.local.ini`;
- credenciales o tokens en colecciones Postman;
- dumps o respaldos de bases de datos;
- carpetas `vendor/` o `node_modules/`;
- archivos `.DS_Store`, logs o caché.

Las credenciales de producción deben configurarse en el hosting y nunca dentro del
repositorio. `.env.example` documenta nombres de variables, no valores reales.
