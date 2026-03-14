# Документация по проекту

## Назначение проекта

Проект представляет собой Symfony-монолит, в котором:

- пользователь работает с проектами;
- внутри проекта создает версии моделей;
- данные по вкладкам модели сохраняются в БД;
- по данным модели генерируется Excel-файл через отдельный Go-сервис.

На текущем этапе основная бизнес-ветка связана с:

- проектами;
- моделями;
- вкладкой `Временные параметры`;
- экспортом этой вкладки в Excel.

## Технологический стек

- Symfony 8
- PHP 8.4+
- Twig
- Turbo
- Stimulus
- Importmap
- PostgreSQL
- Go + `excelize` для генерации Excel

## Общая структура проекта

### Бэкенд

- `src/Controller` - HTTP-контроллеры
- `src/Service` - бизнес-логика
- `src/Entity` - Doctrine-сущности
- `src/Repository` - запросы к БД
- `src/Form` - Symfony Form
- `src/DTO` - DTO для слоя представления
- `src/Mapper` - преобразование сущностей в DTO
- `src/Exception` - доменные исключения

### Шаблоны

- `templates/project` - страницы проектов
- `templates/model` - страницы моделей
- `front` - статические мок-шаблоны, которые использовались как версточные заготовки

### Фронтенд

- `assets/entries` - entrypoints
- `assets/controllers` - Stimulus-контроллеры
- `assets/styles` - стили

### Excel

- `src/Service/Excel` - PHP-часть Excel-экспорта
- `go/excel-hydrator` - Go-сервис, который физически собирает `.xlsx`
- `excel/output` - сгенерированные файлы
- `excel/templates` - опциональные Excel-шаблоны

## Архитектура по слоям

## 1. HTTP-слой

### Контроллеры

- `src/Controller/ProjectController.php`
- `src/Controller/ModelController.php`
- `src/Controller/ExcelController.php`

Задача контроллеров:

- принять запрос;
- проверить доступ к данным текущего пользователя;
- вызвать сервис;
- подготовить ответ;
- отрендерить Twig или вернуть JSON/redirect.

Контроллеры не должны хранить бизнес-логику.

### Base controller

- `src/Controller/BaseAbstractController.php`

Сейчас там есть общий метод:

- `getAuthorizedUser()` - возвращает авторизованного пользователя как `User`

## 2. Сервисный слой

### Работа с проектами

- `src/Service/ProjectService.php`

Отвечает за:

- получение проектов пользователя;
- создание нового проекта;
- генерацию `shortId`;
- сохранение проекта;
- поиск проекта по `shortId` внутри пользователя.

### Работа с моделями

- `src/Service/ModelService.php`

Отвечает за:

- создание новой модели внутри проекта;
- расчет следующей версии;
- генерацию `shortId`;
- сохранение модели;
- поиск модели внутри проекта;
- чтение и запись данных вкладки `time_params`.

## 3. Слой данных

### Сущности

- `src/Entity/Project.php`
- `src/Entity/Model.php`
- `src/Entity/ModelTabData.php`

### Связи

- `Project` -> имеет много `Model`
- `Model` -> имеет много `ModelTabData`
- `ModelTabData` -> хранит данные конкретной вкладки модели

### Идея хранения вкладок

`Model` не содержит все поля всех вкладок напрямую.

Вместо этого:

- у каждой вкладки есть `tab_key`;
- данные вкладки лежат в `payload` как массив/JSON.

Это позволяет добавлять вкладки постепенно, не раздувая таблицу `model`.

## 4. Слой представления

### DTO

- `src/DTO/Projects`

### Мапперы

- `src/Mapper/ProjectPageMapper.php`
- `src/Mapper/ProjectContentMapper.php`
- `src/Mapper/ProjectSidebarItemMapper.php`
- `src/Mapper/ProjectModelStubMapper.php`

Идея:

- контроллер получает сущности;
- мапперы превращают сущности в DTO;
- Twig работает с DTO, а не напрямую с Doctrine-сущностями.

Это полезно для будущего перехода к API, потому что DTO уже задают контракт представления.

## 5. Excel-слой

### PHP-часть

- `src/Service/Excel/ModelExcelPayloadBuilder.php`
- `src/Service/Excel/TimeAxisBuilder.php`
- `src/Service/ExcelModelGenerator.php`
- `src/Service/Excel/GoExcelHydrator.php`
- `src/Controller/ExcelController.php`

### Go-часть

- `go/excel-hydrator/main.go`

Поток:

1. `ModelController` инициирует экспорт.
2. `ModelExcelPayloadBuilder` собирает payload для Excel.
3. `ExcelModelGenerator` валидирует и оркестрирует генерацию.
4. `GoExcelHydrator` отправляет payload в Go-сервис.
5. Go-сервис создает `.xlsx`.
6. Symfony отдает пользователю ссылку/скачивание файла.

## Пользовательские сценарии

## 1. Проекты

Основной контроллер:

- `src/Controller/ProjectController.php`

Сценарии:

- `GET /project`
- `GET /project/{shortId}`
- `GET|POST /project/create`
- `GET|POST /project/{shortId}/edit`

Ключевые файлы:

- `src/Form/ProjectType.php`
- `templates/project/index.html.twig`
- `templates/project/blocks/*`

Turbo:

- правый блок страницы рендерится в `turbo-frame` с `id="project_content"`

## 2. Модели

Основной контроллер:

- `src/Controller/ModelController.php`

Сценарии:

- создание модели;
- редактирование вкладки `time_params`;
- переименование модели;
- экспорт модели в Excel.

Ключевые файлы:

- `src/Form/ModelCreateType.php`
- `templates/model/create.html.twig`
- `templates/model/blocks/_model_create_layout.html.twig`
- `templates/model/blocks/_model_create_time_params_form.html.twig`

Turbo:

- контент модели рендерится в `turbo-frame` с `id="model_content"`

## 3. Переименование модели

Сейчас реализовано через:

- Stimulus на фронте;
- POST endpoint в `ModelController::rename()`;
- поддержку JSON-ответа.

Ключевой фронтенд-файл:

- `assets/controllers/model_rename_controller.js`

## Работа с вкладкой `Временные параметры`

## Форма

Файл:

- `src/Form/ModelCreateType.php`

Поля:

- `investmentStartMonth`
- `investmentDurationMonths`
- `commercialOperationDurationMonths`
- `forecastStep`

Форма работает с массивом, а не с entity.

## Сохранение

Файл:

- `src/Service/ModelService.php`

Метод:

- `upsertTimeParamsTabData()`

Фактическое хранение в `ModelTabData.payload`:

- `investment_start_date`
- `investment_duration_months`
- `commercial_operation_duration_months`
- `forecast_step`

## Excel-экспорт

## Что делает `ModelExcelPayloadBuilder`

Файл:

- `src/Service/Excel/ModelExcelPayloadBuilder.php`

Отвечает за:

- чтение данных `time_params` из модели;
- построение листа `Входные данные`;
- построение листа `Временные параметры`;
- генерацию формул;
- проставление семантики ячеек:
  - `input`
  - `calculated`
  - `reference`
  - `technical`
  - `hint`

## Что делает `TimeAxisBuilder`

Файл:

- `src/Service/Excel/TimeAxisBuilder.php`

Отвечает за:

- расчет длины временной оси;
- расчет шага;
- расчет названий колонок Excel;
- расчет подписей начала и конца периода.

## Что делает Go-гидратор

Файл:

- `go/excel-hydrator/main.go`

Отвечает за:

- создание workbook;
- создание листов;
- установку значений и формул;
- dropdown;
- стили;
- ширину колонок;
- сохранение файла.

## Где менять код в зависимости от задачи

## Изменить поведение проекта

- `src/Service/ProjectService.php`
- `src/Form/ProjectType.php`
- `src/Entity/Project.php`

## Изменить отображение проектов

- `templates/project/blocks/*`
- `assets/styles/pages/projects.css`

## Изменить вкладку модели

- `src/Form/ModelCreateType.php`
- `src/Service/ModelService.php`
- `templates/model/blocks/*`

## Добавить новую вкладку модели

Нужно затронуть:

1. `ModelTabData` и договоренность по `tab_key`
2. новый form type или DTO для вкладки
3. `ModelService` для чтения/сохранения
4. `ModelController` для маршрутов/рендера
5. Twig для отображения вкладки
6. Excel builder, если вкладка должна попадать в Excel

## Изменить Excel-логику

- `src/Service/Excel/ModelExcelPayloadBuilder.php`
- `src/Service/Excel/TimeAxisBuilder.php`

## Изменить техническую генерацию Excel

- `go/excel-hydrator/main.go`

## Frontend

Текущие важные entrypoints и контроллеры:

- `assets/entries/project.js`
- `assets/controllers/project_form_validation_controller.js`
- `assets/controllers/flash_alert_controller.js`
- `assets/controllers/month_picker_controller.js`
- `assets/controllers/model_rename_controller.js`

Назначение:

- `project_form_validation_controller` - фронтовая валидация проекта
- `flash_alert_controller` - автоскрытие flash-сообщений
- `month_picker_controller` - month picker
- `model_rename_controller` - inline rename модели

## Полезные команды

```bash
php bin/console lint:container
php bin/console lint:twig templates
php -l src/Controller/ProjectController.php
php -l src/Controller/ModelController.php
php -l src/Service/ModelService.php
php -l src/Service/Excel/ModelExcelPayloadBuilder.php
```

Для Go:

```bash
cd go/excel-hydrator
gofmt -w main.go
GOCACHE=/tmp/go-build-cache GOMODCACHE=/tmp/go-mod-cache go build ./...
```

Для Docker:

```bash
docker compose build excel-hydrator
docker compose up -d excel-hydrator
```

## Как входить в код, если нужно быстро разобраться

Рекомендуемый порядок чтения:

1. `src/Controller/ProjectController.php`
2. `src/Service/ProjectService.php`
3. `src/Controller/ModelController.php`
4. `src/Service/ModelService.php`
5. `src/Service/Excel/ModelExcelPayloadBuilder.php`
6. `go/excel-hydrator/main.go`

Так быстрее всего складывается общая картина.
