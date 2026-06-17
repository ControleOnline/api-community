#!/usr/bin/env bash

set -euo pipefail

# ==========================================================
# CONFIGURAÇÕES
# ==========================================================

# Diretório onde está este script e também o arquivo .env.local
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Diretório base: um nível acima do diretório onde está este script
# Exemplo:
# SCRIPT_DIR=/home/apilavego/sistemas
# BASE_DIR=/home/apilavego
BASE_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

# Arquivo de ambiente Symfony
ENV_FILE="${SCRIPT_DIR}/.env.local"

# Diretório onde os backups serão salvos
BACKUP_DIR="${BASE_DIR}/backups"

# Diretório e arquivo de log
LOG_DIR="${BASE_DIR}/logs"
LOG_FILE="${LOG_DIR}/backups.log"

# Retenção dos backups
RETENTION_DAYS=30

# Caminho dos comandos
MYSQLDUMP_BIN="/usr/bin/mysqldump"
GZIP_BIN="/usr/bin/gzip"

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

if [[ -z "${DATABASE_HOST}" ]]; then
    log "Erro: DATABASE_HOST não definido no .env.local"
    exit 1
fi

if [[ -z "${DATABASE_PORT}" ]]; then
    log "Erro: DATABASE_PORT não definido no .env.local"
    exit 1
fi

if [[ -z "${DATABASE_DRIVER}" ]]; then
    log "Erro: DATABASE_DRIVER não definido no .env.local"
    exit 1
fi

if [[ -z "${DATABASE_NAME}" ]]; then
    log "Erro: DATABASE_NAME não definido no .env.local"
    exit 1
fi

if [[ -z "${DATABASE_USER}" ]]; then
    log "Erro: DATABASE_USER não definido no .env.local"
    exit 1
fi

if [[ -z "${DATABASE_PASSWORD}" ]]; then
    log "Erro: DATABASE_PASSWORD não definido no .env.local"
    exit 1
fi

if [[ "${DATABASE_DRIVER}" != "pdo_mysql" ]]; then
    log "Erro: este script suporta apenas DATABASE_DRIVER=pdo_mysql"
    log "Driver encontrado: ${DATABASE_DRIVER}"
    exit 1
fi

DATE_BACKUP="$(date '+%Y-%m-%d_%H-%M-%S')"
BACKUP_PREFIX="${DATABASE_NAME}"
BACKUP_FILE="${BACKUP_DIR}/${BACKUP_PREFIX}_${DATE_BACKUP}.sql.gz"

log "Banco: ${DATABASE_NAME}"
log "Host: ${DATABASE_HOST}:${DATABASE_PORT}"
log "Gerando backup..."

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
    --databases "${DATABASE_NAME}" \
| "${GZIP_BIN}" > "${BACKUP_FILE}"

chmod 600 "${BACKUP_FILE}"

log "Backup criado em: ${BACKUP_FILE}"

find "${BACKUP_DIR}" \
    -type f \
    -name "${BACKUP_PREFIX}_*.sql.gz" \
    -mtime +"${RETENTION_DAYS}" \
    -delete

log "Backups do banco com mais de ${RETENTION_DAYS} dias removidos."
log "Backup do banco de dados finalizado com sucesso."