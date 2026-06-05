# Cinema

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![XAMPP](https://img.shields.io/badge/XAMPP-Local%20Server-FB7A24?style=for-the-badge&logo=xampp&logoColor=white)
![Status](https://img.shields.io/badge/status-educational%20project-2E8B57?style=for-the-badge)

Веб-приложение для бронирования билетов в кинотеатр. Пользователь выбирает фильм, сеанс и места в зале, после чего переходит к странице оплаты и получает билет на email. Администратор управляет фильмами, расписанием сеансов и списком бронирований.

## Содержание

- [Возможности](#возможности)
- [Стек](#стек)
- [Структура проекта](#структура-проекта)
- [Быстрый старт](#быстрый-старт)
- [База данных](#база-данных)
- [Администратор](#администратор)
- [Тестирование](#тестирование)
- [Документация](#документация)

## Возможности

| Роль | Функции |
| --- | --- |
| Гость | просмотр будущих сеансов, поиск фильмов, регистрация и вход |
| Пользователь | выбор мест, бронирование билетов, оплата по QR-ссылке, просмотр и отмена своих бронирований |
| Администратор | управление фильмами, расписанием сеансов и просмотр всех бронирований |

Дополнительно:

- защита пользовательских и административных страниц через сессии;
- хеширование паролей через `password_hash`;
- проверка занятых мест перед созданием брони;
- расчет итоговой стоимости выбранных билетов;
- отправка билета на email после подтверждения оплаты;
- функциональные тесты для основных пользовательских сценариев.

## Стек

- PHP 8.x
- MySQL / MariaDB
- PDO
- HTML5, CSS3, JavaScript
- XAMPP для локального запуска
- PowerShell для функциональных тестов

## Структура проекта

```text
cinema/
|-- admin/                 # Панель администратора
|   |-- dashboard.php       # Статистика и быстрые действия
|   |-- films.php           # Управление фильмами
|   |-- sessions.php        # Управление сеансами
|   |-- bookings.php        # Просмотр бронирований
|   |-- edit_film.php       # Редактирование фильма
|   `-- edit_session.php    # Редактирование сеанса
|-- assets/
|   `-- css/style.css       # Общие стили интерфейса
|-- diagramms/              # Draw.io диаграммы проекта
|-- includes/
|   |-- config.php          # Настройки базы данных и почты
|   |-- db.php              # PDO-подключение
|   `-- ticket_mailer.php   # Формирование и отправка билета
|-- sql/
|   `-- cinema_db.sql       # SQL-дамп базы данных
|-- tests/
|   `-- run_project_tests.ps1
|-- index.php               # Главная страница с сеансами
|-- booking.php             # Выбор мест
|-- payment.php             # Оплата и отправка билета
|-- my_bookings.php         # Личный кабинет пользователя
|-- login.php
|-- register.php
`-- logout.php
```

## Быстрый старт

1. Скопируйте проект в папку XAMPP:

```text
C:\xampp\htdocs\cinema
```

2. Запустите в XAMPP:

- Apache;
- MySQL.

3. Создайте базу данных `cinema_db` в phpMyAdmin или через консоль MySQL.

4. Проверьте настройки подключения в [includes/config.php](includes/config.php):

```php
$host = 'localhost';
$dbname = 'cinema_db';
$username = 'root';
$password = '';
```

5. Откройте проект в браузере:

```text
http://localhost/cinema
```

## База данных

Проект использует четыре основные таблицы:

- `clients` - пользователи и администраторы;
- `films` - фильмы;
- `sessions` - расписание сеансов;
- `booking` - забронированные места.

Если SQL-дамп в `sql/cinema_db.sql` еще не заполнен, можно создать стартовую структуру вручную:

```sql
CREATE DATABASE IF NOT EXISTS cinema_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE cinema_db;

CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE films (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    duration INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL
);

CREATE TABLE sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    film_id INT NOT NULL,
    session_date DATE NOT NULL,
    session_time TIME NOT NULL,
    hall_name VARCHAR(100) NOT NULL,
    CONSTRAINT fk_sessions_film
        FOREIGN KEY (film_id)
        REFERENCES films(id)
        ON DELETE CASCADE
);

CREATE TABLE booking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    session_id INT NOT NULL,
    seat_row INT NOT NULL,
    seat_number INT NOT NULL,
    booked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_session_seat (session_id, seat_row, seat_number),
    CONSTRAINT fk_booking_client
        FOREIGN KEY (client_id)
        REFERENCES clients(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_booking_session
        FOREIGN KEY (session_id)
        REFERENCES sessions(id)
        ON DELETE CASCADE
);
```

Для проверки главной страницы нужен хотя бы один будущий сеанс. Пример тестовых данных:

```sql
INSERT INTO films (title, description, duration, price)
VALUES
('Интерстеллар', 'Научно-фантастический фильм о космосе, времени и семье.', 169, 450.00),
('Начало', 'Триллер о снах, идеях и многоуровневой реальности.', 148, 400.00);

INSERT INTO sessions (film_id, session_date, session_time, hall_name)
VALUES
(1, '2099-01-01', '18:30:00', 'Зал 1'),
(2, '2099-01-01', '21:00:00', 'Зал 2');
```

## Администратор

Самый простой способ создать администратора:

1. Зарегистрируйте пользователя через `register.php`.
2. Измените его роль в базе данных:

```sql
UPDATE clients
SET role = 'admin'
WHERE email = 'admin@example.com';
```

После входа пользователь с ролью `admin` будет перенаправлен в панель администратора:

```text
http://localhost/cinema/admin/dashboard.php
```

## Тестирование

В проекте есть функциональный тестовый сценарий для PowerShell. Он проверяет:

- синтаксис PHP-файлов;
- наличие нужных таблиц;
- доступность основных страниц;
- защиту закрытых страниц;
- регистрацию, вход, бронирование и оплату;
- создание фильма и сеанса через админку.

Запуск из корня проекта:

```powershell
.\tests\run_project_tests.ps1
```

При нестандартных путях или URL можно передать параметры:

```powershell
.\tests\run_project_tests.ps1 `
  -BaseUrl "http://localhost/cinema" `
  -PhpPath "C:\xampp\php\php.exe" `
  -MysqlPath "C:\xampp\mysql\bin\mysql.exe" `
  -Database "cinema_db" `
  -MysqlUser "root"
```

## Документация

В папке [diagramms](diagramms) лежат диаграммы Draw.io:

- диаграмма структуры базы данных;
- диаграммы процессов бронирования и оплаты;
- диаграммы состояний;
- диаграмма компонентов;
- диаграмма развертывания;
- IDEF0 и дерево бизнес-процессов.

Также в репозитории есть материалы для защиты проекта:

- [defense_speech_vorobyev.md](defense_speech_vorobyev.md)
- [build_defense_presentation.py](build_defense_presentation.py)
- [build_defense_docx.py](build_defense_docx.py)

## Основные страницы

| Страница | Назначение |
| --- | --- |
| `index.php` | список актуальных сеансов и поиск фильмов |
| `booking.php?session_id=1` | выбор мест в зале |
| `payment.php` | оплата бронирования и отправка билета |
| `my_bookings.php` | список бронирований пользователя |
| `admin/dashboard.php` | главная страница администратора |
| `admin/films.php` | управление фильмами |
| `admin/sessions.php` | управление сеансами |
| `admin/bookings.php` | просмотр всех бронирований |

## Примечания

- Главная страница показывает только будущие сеансы.
- Размер зала в текущей реализации: 5 рядов по 8 мест.
- Для отправки email используется стандартная функция PHP `mail()`, поэтому в локальном XAMPP может потребоваться настройка почты.
- QR-код оплаты формируется через внешний URL с помощью сервиса `api.qrserver.com`.
