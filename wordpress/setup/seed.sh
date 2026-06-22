#!/usr/bin/env bash
# Демо-данные каталога для разработки: города, категории, компании.
# Идемпотентно: повторный запуск не плодит дубли (проверка по слагу).
#
# Запуск: docker compose run --rm --entrypoint bash wpcli /setup/seed.sh
set -euo pipefail

cd /var/www/html
wp() { command wp --allow-root "$@"; }

echo "→ Термины: города и категории…"
ensure_term () {
  local tax="$1" name="$2" slug="$3"
  if ! wp term list "$tax" --slug="$slug" --field=term_id 2>/dev/null | grep -q .; then
    wp term create "$tax" "$name" --slug="$slug" >/dev/null
    echo "  + $tax: $name"
  fi
}

ensure_term city "Москва"            moscow
ensure_term city "Санкт-Петербург"   spb
ensure_term service_category "Парикмахерская" hairdresser
ensure_term service_category "Стоматология"   dentist
ensure_term service_category "Автосервис"      auto

# Создать компанию: name|city|category|price|lat|lng|phone|address
seed_company () {
  local name="$1" city="$2" cat="$3" price="$4" lat="$5" lng="$6" phone="$7" addr="$8"
  local slug
  slug=$(echo "$name" | tr '[:upper:] ' '[:lower:]-')
  if wp post list --post_type=company --name="$slug" --field=ID 2>/dev/null | grep -q .; then
    echo "  • $name уже есть"
    return
  fi
  local id
  id=$(wp post create --post_type=company --post_status=publish \
    --post_title="$name" --post_name="$slug" \
    --post_content="Описание: $name." --porcelain)
  wp post meta update "$id" company_phone "$phone" >/dev/null
  wp post meta update "$id" company_address "$addr" >/dev/null
  wp post meta update "$id" company_latitude "$lat" >/dev/null
  wp post meta update "$id" company_longitude "$lng" >/dev/null
  wp post meta update "$id" company_price_from "$price" >/dev/null
  wp post term set "$id" city "$city" >/dev/null
  wp post term set "$id" service_category "$cat" >/dev/null
  echo "  + компания: $name (#$id)"
}

echo "→ Компании…"
seed_company "Барбершоп Борода"   moscow hairdresser 1500 55.7558 37.6173 "+7 495 111-22-33" "Москва, ул. Тверская, 1"
seed_company "Студия Локон"       moscow hairdresser 1200 55.7600 37.6100 "+7 495 222-33-44" "Москва, ул. Арбат, 10"
seed_company "Дентал Плюс"        moscow dentist     3000 55.7700 37.6000 "+7 495 333-44-55" "Москва, Ленинский пр-т, 5"
seed_company "Невские Ножницы"    spb    hairdresser 1400 59.9343 30.3351 "+7 812 444-55-66" "СПб, Невский пр-т, 20"
seed_company "АвтоМастер СПб"     spb    auto        2500 59.9300 30.3600 "+7 812 555-66-77" "СПб, Лиговский пр-т, 50"

echo "→ Готово. Компаний: $(wp post list --post_type=company --post_status=publish --format=count)"
