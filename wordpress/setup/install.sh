#!/usr/bin/env bash
# Идемпотентная установка WordPress + плагинов для ServiceHub.
#
# Ядро WordPress и wp-config.php разворачивает официальный образ `wordpress`
# при первом старте (из переменных WORDPRESS_DB_*). Поэтому здесь мы НЕ качаем
# ядро сами (избегаем гонки и нехватки памяти), а дожидаемся готовности файлов
# и выполняем install + плагины.
#
# Запуск: docker compose run --rm wpcli   (также вызывается при `up`)
set -euo pipefail

cd /var/www/html

echo "→ Жду, пока контейнер wordpress развернёт ядро и wp-config.php…"
for i in $(seq 1 60); do
  if [ -f wp-settings.php ] && wp config path >/dev/null 2>&1; then
    break
  fi
  sleep 2
done

if [ ! -f wp-settings.php ] || ! wp config path >/dev/null 2>&1; then
  echo "✗ WordPress-ядро/конфиг так и не появились. Проверьте контейнер wordpress." >&2
  exit 1
fi

if ! wp core is-installed >/dev/null 2>&1; then
  echo "→ Устанавливаю WordPress…"
  wp core install \
    --url="${WP_URL}" \
    --title="${WP_TITLE}" \
    --admin_user="${WP_ADMIN_USER}" \
    --admin_password="${WP_ADMIN_PASSWORD}" \
    --admin_email="${WP_ADMIN_EMAIL}" \
    --skip-email
else
  echo "→ WordPress уже установлен, пропускаю."
fi

# ЧПУ-постоянные ссылки (нужны для GraphQL/REST и красивых URL).
wp rewrite structure '/%postname%/' --hard >/dev/null 2>&1 || true

install_plugin () {
  local slug="$1"
  if wp plugin is-installed "$slug" >/dev/null 2>&1; then
    echo "  • $slug уже установлен"
  else
    echo "  • устанавливаю $slug…"
    wp plugin install "$slug" --activate || echo "    ! не удалось установить $slug (поставьте вручную)"
  fi
  wp plugin activate "$slug" >/dev/null 2>&1 || true
}

echo "→ Плагины:"
install_plugin wp-graphql              # обязательный — даёт /graphql
install_plugin advanced-custom-fields  # ACF (free); Pro ставится вручную по лицензии
install_plugin wpgraphql-acf           # мост ACF → GraphQL
# wp-graphql-jwt-authentication — только на GitHub, подключим в Спринте 5

echo "→ Готово. GraphQL endpoint: ${WP_URL}/graphql"
wp plugin list --status=active --field=name || true
