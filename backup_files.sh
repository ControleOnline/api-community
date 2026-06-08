#!/usr/bin/env bash

set -euo pipefail

# ==========================================================
# CONFIGURAÇÕES
# ==========================================================

# Diretório onde está este script
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Diretório base: um nível acima do diretório onde está este script
# Exemplo:
# SCRIPT_DIR=/home/apilavego/sistemas
# BASE_DIR=/home/apilavego
BASE_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

# Nome da pasta atual
SOURCE_DIR_NAME="$(basename "${SCRIPT_DIR}")"

# Diretório onde os backups serão salvos
BACKUP_DIR="${BASE_DIR}/backups"

# Diretório e arquivo de log
LOG_DIR="${BASE_DIR}/logs"
LOG_FILE="${LOG_DIR}/backups.log"

# Retenção dos backups
RETENTION_DAYS=30

# Caminho do comando zip
ZIP_BIN="/usr/bin/zip"

# Nome do arquivo de backup
DATE_BACKUP="$(date '+%Y-%m-%d_%H-%M-%S')"
BACKUP_FILE="${BACKUP_DIR}/${SOURCE_DIR_NAME}_${DATE_BACKUP}.zip"

# ==========================================================
# FUNÇÕES
# ==========================================================

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "${LOG_FILE}"
}

# ==========================================================
# INÍCIO
# ==========================================================

mkdir -p "${BACKUP_DIR}"
mkdir -p "${LOG_DIR}"

log "Iniciando backup de arquivos"
log "Diretório do script: ${SCRIPT_DIR}"
log "Diretório base: ${BASE_DIR}"
log "Diretório de origem: ${SCRIPT_DIR}"
log "Diretório de backup: ${BACKUP_DIR}"
log "Arquivo de log: ${LOG_FILE}"
log "Arquivo de destino: ${BACKUP_FILE}"

if [[ ! -d "${SCRIPT_DIR}" ]]; then
    log "Erro: diretório de origem não encontrado: ${SCRIPT_DIR}"
    exit 1
fi

if [[ ! -x "${ZIP_BIN}" ]]; then
    log "Erro: comando zip não encontrado ou sem permissão em: ${ZIP_BIN}"
    exit 1
fi

# Entra no diretório pai para zipar a pasta inteira com nome relativo
cd "${BASE_DIR}"

"${ZIP_BIN}" -r "${BACKUP_FILE}" "${SOURCE_DIR_NAME}" \
    -x "${SOURCE_DIR_NAME}/var/cache/*" \
    -x "${SOURCE_DIR_NAME}/var/log/*" \
    -x "${SOURCE_DIR_NAME}/vendor/*" \
    -x "${SOURCE_DIR_NAME}/node_modules/*" \
    -x "${SOURCE_DIR_NAME}/.git/*" \
    -x "${SOURCE_DIR_NAME}/backups/*"

chmod 600 "${BACKUP_FILE}"

log "Backup de arquivos criado em: ${BACKUP_FILE}"

find "${BACKUP_DIR}" \
    -type f \
    -name "${SOURCE_DIR_NAME}_*.zip" \
    -mtime +"${RETENTION_DAYS}" \
    -delete

log "Backups de arquivos com mais de ${RETENTION_DAYS} dias removidos."
log "Backup de arquivos finalizado com sucesso."