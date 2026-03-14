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

```bash
composer install
docker compose up -d --build
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate -n
symfony server:start -d
```

Если Symfony CLI не используется, подними локальный веб-сервер с `public/` как document root.

## Основные маршруты

- `GET /project` - рабочее пространство проектов
- `GET /project/{shortId}` - выбранный проект
- `GET|POST /project/create` - создание проекта
- `GET|POST /project/{shortId}/edit` - редактирование проекта
- `GET|POST /project/{projectShortId}/models/create` - создание модели
- `GET|POST /project/{projectShortId}/models/{modelShortId}/edit` - редактирование модели
- `POST /project/{projectShortId}/models/{modelShortId}/rename` - переименование модели
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

