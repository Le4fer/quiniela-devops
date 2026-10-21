#!/bin/bash

# ============================================
# Script de Backup Automático de MariaDB
# ============================================

# Configuración
DB_USER="quiniela_user"
DB_PASSWORD="quiniela_password"
DB_NAME="quiniela_db"
DB_HOST="quiniela_db"
BACKUP_DIR="/backups"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="${BACKUP_DIR}/quiniela_db_${DATE}.sql.gz"

# Crear directorio de backups si no existe
mkdir -p ${BACKUP_DIR}

# Realizar el backup
echo "🔄 Iniciando backup de ${DB_NAME}..."
mysqldump -h ${DB_HOST} -u ${DB_USER} -p${DB_PASSWORD} ${DB_NAME} | gzip > ${BACKUP_FILE}

if [ $? -eq 0 ]; then
    echo "✅ Backup completado: ${BACKUP_FILE}"
    echo "📦 Tamaño: $(du -h ${BACKUP_FILE} | cut -f1)"
    
    # Eliminar backups antiguos (mantener últimos 7 días)
    find ${BACKUP_DIR} -name "quiniela_db_*.sql.gz" -mtime +7 -delete
    echo "🗑️  Backups antiguos eliminados (mayores a 7 días)"
else
    echo "❌ Error al crear el backup"
    exit 1
fi
