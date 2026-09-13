#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
if [[ -n "$(git status --porcelain)" ]]; then
  echo 'Commit or stash website changes before publishing.' >&2
  exit 1
fi
npm run build
for product in court-score pocket-draw silent-stage delivery-ledger siteproof last-done row-count; do
  for page in index.html support/index.html privacy/index.html; do
    test -s "public/$product/$page"
  done
done
target=${SITE_SSH_TARGET:-root@107.175.92.73}
release="$(date -u +%Y%m%dT%H%M%SZ)-$(git rev-parse --short HEAD)"
release_path="/var/www/sawyer-site/releases/$release"
previous=$(ssh -o BatchMode=yes "$target" 'readlink -f /var/www/sawyer-site/current')
ssh -o BatchMode=yes "$target" "mkdir -p '$release_path'"
scp -q -r public/. "$target:$release_path/"
ssh -o BatchMode=yes "$target" "test -s '$release_path/row-count/privacy/index.html' && test -s '$release_path/last-done/support/index.html' && ln -s '$release_path' '/var/www/sawyer-site/current-$release' && mv -Tf '/var/www/sawyer-site/current-$release' /var/www/sawyer-site/current"
printf 'Published %s\nPrevious release: %s\n' "$release_path" "$previous"
