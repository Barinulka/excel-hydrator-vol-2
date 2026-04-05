# excel-hydrator-vol-2

Symfony 8 монолит с Twig + Turbo + Stimulus + Importmap и отдельным Go-сервисом для генерации Excel.

## Что смотреть в первую очередь

- Общая документация по архитектуре и коду: [docs/PROJECT_GUIDE.md](docs/PROJECT_GUIDE.md)

## Стек

- PHP `>= 8.4`
- Symfony 8
- Twig
- Turbo
- Stimulus
- Importmap
- PostgreSQL
- Go-сервис для Excel (`excelize`)
- Docker + Docker Compose

## Быстрый старт

### Docker

```bash
docker compose up -d --build
docker compose exec app php bin/console doctrine:migrations:migrate -n
```

Приложение будет доступно на `http://127.0.0.1:8080`.

### Native

```bash
composer install
docker compose up -d database excel-hydrator
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate -n
symfony server:start -d
```

Если Symfony CLI не используется, подними локальный веб-сервер с `public/` как document root.

## Docker

Проект теперь можно поднимать одной схемой и локально, и на сервере:

- `nginx` - внешний web server и reverse proxy
- `app` - Symfony + PHP-FPM
- `database` - PostgreSQL
- `excel-hydrator` - Go сервис генерации Excel

Локально `docker compose` автоматически подхватывает [compose.override.yaml](/Users/aleksejbarinov/Документы/Work/excel-hydrator-vol-2/compose.override.yaml):

- код монтируется в контейнер через bind mount
- `app` работает в `dev`
- наружу опубликован только `nginx`
- PostgreSQL доступен с хоста на `127.0.0.1:6101`
- Go hydrator доступен на `127.0.0.1:8081`

Запуск:

```bash
docker compose up -d --build
docker compose exec app php bin/console doctrine:migrations:migrate -n
```

Приложение будет доступно на `http://127.0.0.1:8080`.

Для сервера можно использовать тот же [compose.yaml](/Users/aleksejbarinov/Документы/Work/excel-hydrator-vol-2/compose.yaml) без override. Перед запуском задай реальные значения:

```bash
export APP_ENV=prod
export APP_DEBUG=0
export APP_SECRET=change-me
export POSTGRES_DB=excel-hydrator
export POSTGRES_USER=excel-hydrator
export POSTGRES_PASSWORD=change-me
docker compose up -d --build
docker compose exec app php bin/console doctrine:migrations:migrate -n
```

Для production override используется `Caddy`, который сам получает и продлевает HTTPS-сертификаты. Для этого домен из `CADDY_HOSTS` должен уже смотреть на сервер, а порты `80` и `443` должны быть открыты.

## Основные маршруты

- `GET /project` - рабочее пространство проектов
- `GET /project/{shortId}` - выбранный проект
- `GET /project/create` - страница создания проекта
- `GET /project/{shortId}/edit` - страница редактирования проекта
- `POST /api/projects` - создание проекта через API
- `PATCH /api/projects/{shortId}` - обновление проекта через API
- `GET /project/{projectShortId}/models/create` - страница создания модели
- `GET /project/{projectShortId}/models/{modelShortId}/time-params` - вкладка временных параметров модели
- `POST /api/projects/{projectShortId}/models` - создание модели через API
- `PATCH /api/projects/{projectShortId}/models/{modelShortId}/time-params` - обновление временных параметров
- `PATCH /api/projects/{projectShortId}/models/{modelShortId}/title` - переименование модели через API
- `POST /project/{projectShortId}/models/{modelShortId}/export-excel` - экспорт модели в Excel
- `POST /excel` - PHP endpoint для генерации Excel через Go
- `GET /excel/output/{filename}` - скачивание сгенерированного файла
- `ANY /front/{slug}` - статические мок-страницы из `front/`

## Где искать код

- Контроллеры: `src/Controller`
- Сервисы: `src/Service`
- Сущности: `src/Entity`
- Формы: `src/Form`
- DTO и мапперы: `src/DTO`, `src/Mapper`
- Twig-шаблоны: `templates`
- Фронтенд-стили и Stimulus: `assets`
- Go-гидратор Excel: `go/excel-hydrator`

## Полезные команды

```bash
php bin/console lint:container
php bin/console lint:twig templates
php -l src/Controller/ProjectController.php
php -l src/Controller/ModelController.php
php -l src/Service/ModelService.php
php -l src/Service/Excel/ModelExcelPayloadBuilder.php
```

## Excel-гидратор

Переменная окружения:

```dotenv
EXCEL_HYDRATOR_URL=http://127.0.0.1:8081
```

Папки:

- `excel/output` - готовые файлы
- `excel/templates` - Excel-шаблоны, если понадобится генерация не с нуля, а из заготовки

Go-сервис:

- код: `go/excel-hydrator/main.go`
- compose service: `excel-hydrator`
- health endpoint: `GET http://127.0.0.1:8081/health`

## Текущее состояние функциональности

- Проекты: список, просмотр, создание, редактирование
- Модели: создание, редактирование, переименование
- Реализованная вкладка модели: `Временные параметры`
- Экспорт в Excel:
  - лист `Входные данные`
  - лист `Временные параметры`
  - формулы
  - стили ячеек
  - выпадающий список `Шаг прогнозирования`
