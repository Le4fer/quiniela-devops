#!/bin/bash

# Script de respaldo: Servidor → GitHub
# Se ejecuta automáticamente cada 6 horas

PROJECT_DIR="/home/invaca/quiniela-devops"
LOG_FILE="/var/log/quiniela-backup.log"
DATE=$(date '+%Y-%m-%d %H:%M:%S')

echo "[$DATE] Iniciando respaldo a GitHub..." >> $LOG_FILE

cd $PROJECT_DIR

# Verificar que hay cambios
if [ -z "$(git status --porcelain)" ]; then
    echo "[$DATE] No hay cambios para respaldar." >> $LOG_FILE
    exit 0
fi

# Añadir cambios (respetando .gitignore)
git add -A

# Commit con fecha
git commit -m "backup: auto-backup from server - $DATE"

# Push a GitHub
git push origin main 2>> $LOG_FILE

if [ $? -eq 0 ]; then
    echo "[$DATE] ✅ Respaldo completado exitosamente." >> $LOG_FILE
else
    echo "[$DATE] ❌ Error al hacer push. Revisar $LOG_FILE" >> $LOG_FILE
fi
