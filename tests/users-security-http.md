# Pruebas HTTP de seguridad de usuarios

Ejecutar contra una base descartable. Sustituir `BASE_URL`, tokens e IDs. No ejecutar
la prueba de borrado contra datos reales.

```bash
curl -i -X POST "$BASE_URL/users/forgot-password" -H 'Content-Type: application/json' -d '{"email":"a@example.com","new_password":"NoDebeCambiar1"}'
curl -i -X POST "$BASE_URL/users/update" -H 'Content-Type: application/json' -d '{"nombre":"Sin token"}'
curl -i -X POST "$BASE_URL/users/update" -H "Authorization: Bearer $TOKEN_A" -H 'Content-Type: application/json' -d "{\"id\":$USER_B,\"nombre\":\"No permitido\"}"
curl -i -X POST "$BASE_URL/users/update" -H "Authorization: Bearer $TOKEN_A" -H 'Content-Type: application/json' -d '{"email":"nuevo@example.com"}'
curl -i -X POST "$BASE_URL/users/update" -H "Authorization: Bearer $TOKEN_A" -H 'Content-Type: application/json' -d '{"password":"NoPermitida1"}'
curl -i -X POST "$BASE_URL/users/delete" -H 'Content-Type: application/json' -d "{\"id\":$USER_A}"
curl -i -X POST "$BASE_URL/users/delete" -H "Authorization: Bearer $TOKEN_A" -H 'Content-Type: application/json' -d "{\"id\":$USER_B}"
curl -i "$BASE_URL/users/list" -H "Authorization: Bearer $TOKEN_A"
```

Resultados esperados: 501, 401, 403, 400, 400, 401, 403 y 403. Revocar manualmente
`TOKEN_A` en `sesiones` debe provocar 401 en profile/update/delete/list. Ninguna respuesta
de perfil o actualización debe contener `password`.
