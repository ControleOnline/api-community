#!/usr/bin/env bash

set -euo pipefail

# ==========================================================
# CONFIGURAÇÕES
# ==========================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BASE_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

ENV_FILE="${SCRIPT_DIR}/.env.local"

BACKUP_DIR="${BASE_DIR}/backups"
LOG_DIR="${BASE_DIR}/logs"
LOG_FILE="${LOG_DIR}/backups.log"

RETENTION_DAYS=7

MYSQL_BIN="/usr/bin/mysql"
MYSQLDUMP_BIN="/usr/bin/mysqldump"
GZIP_BIN="/usr/bin/gzip"

# Banco auxiliar para teste de restore
BACKUP_DB_NAME="apilavego_bkp"

# ==========================================================
# FUNÇÕES
# ==========================================================

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "${LOG_FILE}"
}

clean_env_value() {
    local value="$1"
    value="${value%\"}"
    value="${value#\"}"
    value="${value%\'}"
    value="${value#\'}"
    echo "${value}"
}

get_env_value() {
    local key="$1"
    grep -E "^${key}=" "${ENV_FILE}" | tail -n 1 | cut -d '=' -f 2-
}

# ==========================================================
# INÍCIO
# ==========================================================

mkdir -p "${BACKUP_DIR}"
mkdir -p "${LOG_DIR}"

log "Iniciando rotina de backup do banco de dados"
log "Diretório do script: ${SCRIPT_DIR}"
log "Diretório base: ${BASE_DIR}"
log "Diretório de backup: ${BACKUP_DIR}"
log "Arquivo de log: ${LOG_FILE}"

if [[ ! -f "${ENV_FILE}" ]]; then
    log "Erro: arquivo .env.local não encontrado em: ${ENV_FILE}"
    exit 1
fi

DATABASE_HOST="$(clean_env_value "$(get_env_value DATABASE_HOST)")"
DATABASE_PORT="$(clean_env_value "$(get_env_value DATABASE_PORT)")"
DATABASE_DRIVER="$(clean_env_value "$(get_env_value DATABASE_DRIVER)")"
DATABASE_NAME="$(clean_env_value "$(get_env_value DATABASE_NAME)")"
DATABASE_USER="$(clean_env_value "$(get_env_value DATABASE_USER)")"
DATABASE_PASSWORD="$(clean_env_value "$(get_env_value DATABASE_PASSWORD)")"

for var in DATABASE_HOST DATABASE_PORT DATABASE_DRIVER DATABASE_NAME DATABASE_USER DATABASE_PASSWORD; do
    if [[ -z "${!var}" ]]; then
        log "Erro: ${var} não definido no .env.local"
        exit 1
    fi
done

if [[ "${DATABASE_DRIVER}" != "pdo_mysql" ]]; then
    log "Erro: este script suporta apenas DATABASE_DRIVER=pdo_mysql"
    log "Driver encontrado: ${DATABASE_DRIVER}"
    exit 1
fi

DATE_BACKUP="$(date '+%Y-%m-%d_%H-%M-%S')"
BACKUP_PREFIX="${DATABASE_NAME}"

DUMP_FILE="${BACKUP_DIR}/${BACKUP_PREFIX}_${DATE_BACKUP}.sql"
BACKUP_FILE="${DUMP_FILE}.gz"

log "Banco origem: ${DATABASE_NAME}"
log "Banco destino (teste restore): ${BACKUP_DB_NAME}"
log "Host: ${DATABASE_HOST}:${DATABASE_PORT}"

# ==========================================================
# 1 - DROP E CREATE DO BANCO DE BACKUP
# ==========================================================

log "Recriando banco ${BACKUP_DB_NAME}..."

MYSQL_PWD="${DATABASE_PASSWORD}" \
"${MYSQL_BIN}" \
    --host="${DATABASE_HOST}" \
    --port="${DATABASE_PORT}" \
    --user="${DATABASE_USER}" \
    -e "DROP DATABASE IF EXISTS \`${BACKUP_DB_NAME}\`; CREATE DATABASE \`${BACKUP_DB_NAME}\`;"

log "Banco ${BACKUP_DB_NAME} recriado com sucesso."

# ==========================================================
# 2 - GERANDO DUMP (SEM COMPRESSÃO)
# ==========================================================

log "Gerando dump do banco ${DATABASE_NAME}..."

MYSQL_PWD="${DATABASE_PASSWORD}" \
"${MYSQLDUMP_BIN}" \
    --host="${DATABASE_HOST}" \
    --port="${DATABASE_PORT}" \
    --user="${DATABASE_USER}" \
    --single-transaction \
    --quick \
    --routines \
    --triggers \
    --events \
    "${DATABASE_NAME}" \
> "${DUMP_FILE}"

log "Dump criado em: ${DUMP_FILE}"

# ==========================================================
# 3 - RESTAURANDO NO BANCO DE BACKUP
# ==========================================================

log "Restaurando dump no banco ${BACKUP_DB_NAME}..."

MYSQL_PWD="${DATABASE_PASSWORD}" \
"${MYSQL_BIN}" \
    --host="${DATABASE_HOST}" \
    --port="${DATABASE_PORT}" \
    --user="${DATABASE_USER}" \
    "${BACKUP_DB_NAME}" \
< "${DUMP_FILE}"

log "Restauração concluída com sucesso."

# ==========================================================
# 4 - COMPRESSÃO
# ==========================================================

log "Compactando dump..."

"${GZIP_BIN}" "${DUMP_FILE}"

chmod 600 "${BACKUP_FILE}"

log "Backup compactado em: ${BACKUP_FILE}"

# ==========================================================
# 5 - RETENÇÃO
# ==========================================================

find "${BACKUP_DIR}" \
    -type f \
    -name "${BACKUP_PREFIX}_*.sql.gz" \
    -mtime +"${RETENTION_DAYS}" \
    -delete

log "Backups com mais de ${RETENTION_DAYS} dias removidos."
log "Backup do banco de dados finalizado com sucesso."