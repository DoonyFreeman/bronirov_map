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

# Найти ID компании по точному заголовку (кириллица — через env, без кавычек).
company_id_by_title () {
  export SH_TITLE="$1"
  command wp --allow-root eval '$q=new WP_Query(["post_type"=>"company","post_status"=>"publish","title"=>getenv("SH_TITLE"),"fields"=>"ids","posts_per_page"=>1]); echo $q->posts[0] ?? "";' 2>/dev/null
}

# Услуга: title|company_title|price|duration(min)
seed_service () {
  local name="$1" company_title="$2" price="$3" duration="$4"
  local company_id
  company_id=$(company_id_by_title "$company_title")
  [ -z "$company_id" ] && { echo "  ! нет компании «$company_title»"; return; }
  # Идемпотентность: услуга с таким названием у этой компании уже есть?
  export SH_SVC="$name" SH_CID="$company_id"
  if [ -n "$(command wp --allow-root eval '$q=new WP_Query(["post_type"=>"service","title"=>getenv("SH_SVC"),"meta_key"=>"service_company","meta_value"=>getenv("SH_CID"),"fields"=>"ids","posts_per_page"=>1]); echo $q->posts[0] ?? "";' 2>/dev/null)" ]; then
    echo "  • услуга «$name» уже есть"; return
  fi
  local id
  id=$(wp post create --post_type=service --post_status=publish \
    --post_title="$name" --post_content="$name" --porcelain)
  wp post meta update "$id" service_price "$price" >/dev/null
  wp post meta update "$id" service_duration "$duration" >/dev/null
  wp post meta update "$id" service_company "$company_id" >/dev/null
  echo "  + услуга: $name (#$id → компания #$company_id)"
}

# Отзыв: company_title|author|rating|text|verified(0/1)
seed_review () {
  local company_title="$1" author="$2" rating="$3" text="$4" verified="$5"
  local company_id title
  company_id=$(company_id_by_title "$company_title")
  [ -z "$company_id" ] && { echo "  ! нет компании «$company_title»"; return; }
  title="Отзыв: $author — $company_title"
  export SH_RTITLE="$title"
  if [ -n "$(command wp --allow-root eval '$q=new WP_Query(["post_type"=>"review","title"=>getenv("SH_RTITLE"),"fields"=>"ids","posts_per_page"=>1]); echo $q->posts[0] ?? "";' 2>/dev/null)" ]; then
    echo "  • отзыв «$author» уже есть"; return
  fi
  local id
  id=$(wp post create --post_type=review --post_status=publish --post_title="$title" --porcelain)
  wp post meta update "$id" review_company "$company_id" >/dev/null
  wp post meta update "$id" review_author "$author" >/dev/null
  wp post meta update "$id" review_rating "$rating" >/dev/null
  wp post meta update "$id" review_text "$text" >/dev/null
  wp post meta update "$id" review_verified "$verified" >/dev/null
  # Перезапускаем save_post с уже заполненной meta → пересчёт кэш-рейтинга.
  wp post update "$id" --post_excerpt="seeded" >/dev/null
  echo "  + отзыв: $author ($rating* → компания #$company_id)"
}

echo "→ Услуги…"
seed_service "Мужская стрижка" "Барбершоп Борода" 1500 45
seed_service "Стрижка бороды"  "Барбершоп Борода" 1000 30
seed_service "Женская стрижка" "Студия Локон"     2000 60
seed_service "Лечение кариеса" "Дентал Плюс"      3500 60
seed_service "Замена масла"    "АвтоМастер СПб"   2500 40

echo "→ Отзывы…"
seed_review "Барбершоп Борода" "Иван" 5 "Отличный мастер, рекомендую!" 1
seed_review "Барбершоп Борода" "Пётр" 4 "Хорошо, но дороговато."       0
seed_review "Дентал Плюс"      "Анна" 5 "Безболезненно и аккуратно."   1

echo "→ Часы работы (Барбершоп Борода)…"
hbc=$(company_id_by_title "Барбершоп Борода")
if [ -n "$hbc" ]; then
  export SH_HID="$hbc"
  command wp --allow-root eval 'if(function_exists("update_field")){update_field("company_hours",[["day"=>"mon","open_time"=>"10:00","close_time"=>"20:00"],["day"=>"tue","open_time"=>"10:00","close_time"=>"20:00"],["day"=>"sat","open_time"=>"11:00","close_time"=>"18:00"]],(int)getenv("SH_HID"));}' >/dev/null 2>&1 || true
  echo "  + часы заданы для #$hbc"
fi

echo "→ Готово."
echo "  компаний: $(wp post list --post_type=company --post_status=publish --format=count)"
echo "  услуг:    $(wp post list --post_type=service --post_status=publish --format=count)"
echo "  отзывов:  $(wp post list --post_type=review --post_status=publish --format=count)"
