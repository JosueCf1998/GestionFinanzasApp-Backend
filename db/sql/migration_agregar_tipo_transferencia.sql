-- Migración: Agregar tipo_transferencia a tabla transferencias
-- Fecha: 26-01-2026

-- Agregar columna tipo_transferencia
ALTER TABLE `transferencias` 
ADD COLUMN `tipo_transferencia` VARCHAR(20) DEFAULT 'Realizado' AFTER `comentario`;

-- Actualizar registros existentes para que tengan tipo 'Realizado'
UPDATE `transferencias` 
SET `tipo_transferencia` = 'Realizado' 
WHERE `tipo_transferencia` IS NULL OR `tipo_transferencia` = '';

-- Agregar índice para optimizar consultas por tipo
ALTER TABLE `transferencias` 
ADD INDEX `idx_tipo_transferencia` (`tipo_transferencia`);

-- Comentario: Los valores válidos son:
-- 'Inicial': Cuando se crea una cuenta con un monto fijo (registro inicial)
-- 'Ajuste': Cuando se edita la cuenta (diferencia entre monto nuevo y actual)
-- 'Realizado': Cuando se hace una transferencia normal entre cuentas
