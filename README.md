# ServiceHub

Платформа каталога услуг и онлайн-бронирования. Архитектура — **headless WordPress (CMS/бэкенд) + Next.js (фронтенд)**.

- Слой данных: **WPGraphQL**
- Карты: **Leaflet + OpenStreetMap**
- Уведомления: **Telegram-бот** (онлайн-оплата — в более поздней фазе)

Полный план и спринты: [`~/.claude/plans/quirky-stargazing-quiche.md`](#).

## Структура репозитория

```
.
├── docker-compose.yml          # WordPress + MariaDB + WP-CLI
├── .env.example                # шаблон переменных окружения
├── wordpress/
│   ├── mu-plugins/             # монтируется в wp-content/mu-plugins
│   │   ├── servicehub-core-loader.php
│   │   └── servicehub-core/    # основной плагин (CPT, GraphQL, сервисы)
│   ├── setup/install.sh        # установка WP + плагинов через WP-CLI
│   ├── composer.json           # dev-инструменты (PHPCS/WPCS)
│   └── phpcs.xml.dist
└── web/                        # Next.js (App Router, TypeScript)
```

## Быстрый старт

```bash
cp .env.example .env

# 1. Поднять WordPress (установится автоматически через WP-CLI)
docker compose up -d
docker compose run --rm wpcli   # установка core + плагинов (идемпотентно)

# 2. Фронтенд
cd web
npm install
npm run dev
```

- WordPress admin: `http://localhost:8080/wp-admin` (admin / admin)
- GraphQL endpoint: `http://localhost:8080/graphql`
- GraphQL IDE: `http://localhost:8080/wp-admin/admin.php?page=graphiql-ide`
- Next.js: `http://localhost:3000`

## Текущий статус: Спринт 0 — Фундамент

- [x] Каркас монорепо
- [x] Docker-среда WordPress + MariaDB + WP-CLI
- [x] Каркас mu-plugin `servicehub-core`
- [x] Скаффолд Next.js + GraphQL-клиент
- [x] CI (PHPCS, ESLint/Prettier, codegen, build)

CPT, контент-модель и каталог — Спринт 1.
