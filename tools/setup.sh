#!/usr/bin/env bash
set -euo pipefail

jp_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
jp_plugin="$jp_root/wp-content/plugins/liston-legal-webops-core"
jp_theme="$jp_root/wp-content/themes/the7-justicepoint-child"

if [[ -x /Applications/MAMP/bin/php/php8.2.0/bin/php && -f /Applications/MAMP/Library/bin/wp ]]; then
  jp_wp=(/Applications/MAMP/bin/php/php8.2.0/bin/php /Applications/MAMP/Library/bin/wp --path="$jp_root")
else
  jp_wp=(wp --path="$jp_root")
fi

composer install --working-dir="$jp_plugin"
npm install --prefix "$jp_plugin"
npm install --prefix "$jp_theme"
npm run build --prefix "$jp_plugin"
npm run build --prefix "$jp_theme"
"${jp_wp[@]}" plugin activate liston-legal-webops-core
"${jp_wp[@]}" theme activate the7-justicepoint-child
"${jp_wp[@]}" liston-webops seed

echo "JusticePoint setup complete. Open the configured WordPress home URL."
