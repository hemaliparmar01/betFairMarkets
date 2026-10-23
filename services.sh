#!/bin/sh
set -eu
PROJECT_ROOT=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
exec "${SERVICES_PYTHON:-python3}" "$PROJECT_ROOT/services-manager.py" "$@"
