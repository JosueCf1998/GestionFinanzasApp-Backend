# GestionFinanzasApp Backend

API REST desarrollada con PHP 8, Fat-Free Framework y MySQL/MariaDB.

## Requisitos

- PHP 8.0 o posterior
- MySQL/MariaDB
- Composer 2
- Apache con `mod_rewrite`

## Instalación

```bash
git clone <url-del-repositorio>
cd GestionFinanzasApp-Backend
composer install --no-interaction
```

Las dependencias no se almacenan en Git. `composer install` las restaura de forma
reproducible desde `composer.lock`.

## Configuración

No guardes credenciales en el repositorio. Usa variables de entorno o una
configuración local ignorada por Git. Consulta `.env.example` para conocer los
nombres esperados y `docs/` para la documentación disponible.

## Base de datos

- Usa una base independiente para desarrollo.
- Versiona cambios estructurales mediante migraciones.
- No publiques dumps con usuarios, sesiones o datos financieros.
- Prueba cada migración localmente antes de aplicarla en producción.

## Validación antes de un commit

```bash
git status
git diff --check
composer validate --no-check-publish
find controllers helpers models -name '*.php' -print0 | xargs -0 -n1 php -l
```

No deben aparecer en el commit:

- `vendor/` o `node_modules/`;
- `.env`, `config.local.ini` o credenciales;
- respaldos de base de datos;
- JWT, cookies o colecciones Postman locales;
- logs, caché o `.DS_Store`.

## Despliegue

El servidor debe recibir el mismo código validado en desarrollo, ejecutar
`composer install --no-dev --optimize-autoloader` y proporcionar su propia
configuración mediante variables de entorno. Nunca edites credenciales dentro del
código para alternar entre local y producción.
