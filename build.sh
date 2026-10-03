#!/bin/bash
# Construit le package installable Websource Précommandes (un seul zip à installer d'un coup).
#
# Usage : ./build.sh [dossier-de-sortie]
#   Sans argument, l'archive est écrite dans ce dossier :  pkg_websourcepreorder-<version>.zip
# Prérequis : zip et python3 (régénération des fichiers de langue). Aucun accès réseau.
set -euo pipefail

HERE="$(cd "$(dirname "$0")" && pwd)"
OUT="${1:-$HERE}"
VERSION="$(sed -n 's:.*<version>\(.*\)</version>.*:\1:p' "$HERE/pkg_websourcepreorder.xml" | head -n1)"
CONST="$(sed -n "s:.*const VERSION = '\(.*\)';.*:\1:p" "$HERE/com_websourcepreorder/administrator/src/Service/Settings.php" | head -n1)"
if [ "$CONST" != "$VERSION" ]; then echo "Version incohérente : manifeste $VERSION, constante Settings::VERSION $CONST" >&2; exit 1; fi
STAGE="$(mktemp -d)"
trap 'rm -rf "$STAGE"' EXIT

python3 "$HERE/build-tools/gen_lang.py" >/dev/null
mkdir -p "$STAGE/packages" "$OUT"

for ext in com_websourcepreorder plg_system_websourcepreorder plg_task_websourcepreorder \
           mod_websourcepreorder_featured mod_websourcepreorder_ticker mod_websourcepreorder_subscribe; do
  # Le manifeste doit se trouver à la racine de chaque archive.
  ( cd "$HERE/$ext" && zip -qr -X "$STAGE/packages/$ext.zip" . -x '*.git*' )
done

cp "$HERE/pkg_websourcepreorder.xml" "$HERE/script.php" "$STAGE/"
cp -r "$HERE/language" "$STAGE/language"
rm -f "$OUT/pkg_websourcepreorder-$VERSION.zip"
( cd "$STAGE" && zip -qr -X "$OUT/pkg_websourcepreorder-$VERSION.zip" . )
echo "Package : $OUT/pkg_websourcepreorder-$VERSION.zip"
