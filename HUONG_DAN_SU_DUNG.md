# PostMate (AutoPostSocial) — Hướng dẫn cài đặt & sử dụng

Ứng dụng quản lý và tự động đăng bài mạng xã hội (Facebook). Gồm 2 phần:

| Thành phần | Công nghệ | Vai trò | Cổng mặc định |
|-----------|-----------|---------|---------------|
| **Backend** | PHP 8.1–8.3 + Laminas MVC + PostgreSQL | REST API (`/api/...`) | `8080` |
| **Frontend** | Angular 20 + TailwindCSS | Giao diện quản trị | `4200` (dev) |

Frontend (khi chạy `ng serve`) tự động **proxy** mọi request `/api` sang backend `http://localhost:8080` (xem `frontend/proxy.conf.json`).

---

## 1. Yêu cầu môi trường

| Phần mềm | Phiên bản | Ghi chú |
|----------|-----------|---------|
| PHP | 8.1 / 8.2 / 8.3 | Cần bật extension `pdo_pgsql`, `intl`, `mbstring` |
| Composer | 2.x | Quản lý thư viện PHP |
| PostgreSQL | 13+ | Database tên `postmate` |
| Node.js | 20+ | Kèm npm, để chạy Angular |
| Angular CLI | 20 | Có thể dùng qua `npx ng` nếu không cài global |

> Kiểm tra nhanh: `php -v`, `composer --version`, `node -v`, `psql --version`.
> Máy hiện tại đã có sẵn PHP 8.3.28, Composer 2.9.2. Thư mục `vendor/` (backend) và `frontend/node_modules/` cũng đã được cài sẵn. WAMP đi kèm `php_pdo_pgsql.dll` nhưng **mặc định đang tắt** — xem mục "Bật extension pdo_pgsql cho WAMP" bên dưới.

---

## 2. Cài đặt cơ sở dữ liệu

Database mặc định tên **`postmate`** (khai báo trong `config/autoload/local.php`), dùng **PostgreSQL**.

```bash
# 1. Tạo database
createdb -U postgres postmate
# (hoặc: psql -U postgres -c "CREATE DATABASE postmate ENCODING 'UTF8';")

# 2. Import toàn bộ bảng (users, posts, post_media, fanpages, browser_profiles, cookies, settings, ...)
psql -U postgres -d postmate -f data/db/postmate.sql
```

> File `data/db/postmate.sql` chứa đầy đủ schema theo `docs/PHAN_TICH_HE_THONG.md`. Không thể gộp bước tạo database và bước import vào 1 lệnh psql duy nhất — PostgreSQL yêu cầu kết nối tới đúng database trước khi tạo bảng trong đó (khác với MySQL `CREATE DATABASE` + `USE`).

### Chỉnh thông tin kết nối DB

Sửa file `config/autoload/local.php` cho khớp PostgreSQL của bạn:

```php
'db' => [
    'driver'   => 'Pdo_Pgsql',
    'host'     => 'localhost',
    'port'     => 5432,
    'database' => 'postmate',
    'username' => 'postgres',
    'password' => '',        // <-- điền mật khẩu PostgreSQL của bạn
    'charset'  => 'utf8',
],
```

### Bật extension `pdo_pgsql` cho WAMP

WAMP đã có sẵn file `php_pdo_pgsql.dll` nhưng mặc định **tắt**. Cần bật thủ công cho cả PHP dùng bởi Apache lẫn PHP CLI:

1. Click icon WAMP (khay hệ thống) → **PHP → PHP extensions** → tick **pdo_pgsql**. WAMP tự áp dụng cho Apache và gợi ý restart.
2. Hoặc sửa tay: mở `php.ini` đang dùng (xem `php --ini` / icon WAMP → PHP → php.ini) và bỏ dấu `;` trước dòng `;extension=pdo_pgsql`.
3. Click icon WAMP → **Restart All Services** để Apache nhận extension mới (đừng dùng `Restart-Service` qua PowerShell nâng quyền).

---

## 3-A. Chạy bằng WAMP (Apache) — cách đang dùng

Phục vụ toàn bộ ứng dụng (giao diện Angular đã build trong `public/` + API PHP) qua Apache của WAMP, **không cần** `composer serve` hay `ng serve`. Truy cập tại **http://localhost:8080**.

**Điều kiện:**
- Apache của WAMP đang dùng **PHP 8.2.0 / 8.3.x** (bản dự án hỗ trợ) và đã bật extension `pdo_pgsql` (xem mục 2 → "Bật extension `pdo_pgsql` cho WAMP" — **mặc định WAMP tắt extension này**, khác với `pdo_mysql`).
- Thư mục `public/` đã chứa bản build Angular. Nếu sửa code frontend và muốn cập nhật giao diện, chạy lại `cd frontend && npm run build` (build xuất thẳng vào `public/`).

**Cấu hình VirtualHost** (đã thêm vào `C:\wamp64\bin\apache\apache2.4.54.2\conf\extra\httpd-vhosts.conf`):

```apache
# PostMate — truy cập qua http://localhost:8080
Listen 8080
<VirtualHost *:8080>
    ServerName localhost
    DocumentRoot "E:/My Tool/AutoPostSocial/public"
    <Directory "E:/My Tool/AutoPostSocial/public">
        AllowOverride All
        Require local
        Options -Indexes +FollowSymLinks
    </Directory>
</VirtualHost>
```

> File `public/.htaccess` tự định tuyến: `/api/*` → `index.php` (Laminas), route SPA còn lại → `index.html` (Angular). Cần bật `mod_rewrite` (WAMP mặc định đã bật).

**Các bước:**
1. Đảm bảo không có tiến trình nào chiếm port 8080 (tắt `composer serve` cũ nếu còn: Ctrl+C, hoặc End task `php.exe` trong Task Manager).
2. Cài PostgreSQL riêng (WAMP không kèm sẵn PostgreSQL server, chỉ kèm PHP driver) rồi tạo + import DB theo lệnh ở mục 2 (`createdb` + `psql -f`).
3. Bật extension `pdo_pgsql` theo hướng dẫn ở mục 2, rồi click icon **WAMP** → **Restart All Services**.
4. Mở **http://localhost:8080**.

> **Đổi PHP version cho Apache:** click icon WAMP → **PHP → Version → chọn 8.1/8.2/8.3** (tránh 8.4/8.5 vì dự án chưa hỗ trợ). Sau khi đổi, kiểm tra `pdo_pgsql` đã bật trong **PHP → PHP extensions**.

---

## 3-B. Chạy Backend bằng CLI (Laminas / PHP) — tuỳ chọn

```bash
# Tại thư mục gốc dự án: E:\My Tool\AutoPostSocial

# (Chỉ lần đầu, hoặc khi vendor/ chưa có) cài thư viện PHP
composer install

# Bật chế độ development (hiện lỗi chi tiết) — khuyến nghị khi dev
composer development-enable

# Khởi động server API tại http://localhost:8080
composer serve
```

`composer serve` tương đương lệnh:

```bash
php -S 0.0.0.0:8080 -t public
```

Kiểm tra backend đã chạy: mở `http://localhost:8080` — nếu load được là OK. Các API nằm dưới tiền tố `/api`, ví dụ:

- `POST /api/user/auth/login`
- `GET  /api/posting/post`
- `GET  /api/posting/dashboard`
- `GET  /api/facebook/account` · `/api/facebook/fanpage` · `/api/facebook/cookie`
- `GET  /api/infra/browser-profile`
- `GET  /api/setting`

> **Lưu ý:** giữ cửa sổ terminal này mở trong suốt quá trình phát triển.

---

## 4. Chạy Frontend (Angular)

Mở **một terminal mới**:

```bash
cd frontend

# (Chỉ lần đầu, hoặc khi node_modules/ chưa có) cài thư viện
npm install

# Khởi động dev server tại http://localhost:4200
npm start
```

`npm start` tương đương `ng serve` — có sẵn cấu hình proxy `/api → http://localhost:8080`.

Mở trình duyệt: **http://localhost:4200**

> Cần chạy **cả backend (bước 3) và frontend (bước 4) cùng lúc**: backend port 8080, frontend port 4200. Frontend gọi API qua proxy nên bạn chỉ cần mở `http://localhost:4200`.

---

## 5. Build production (triển khai thật)

Angular được cấu hình build thẳng vào thư mục `public/` của backend (xem `angular.json` → `outputPath.base = ../public`). Khi đó chỉ cần chạy backend PHP là phục vụ được cả giao diện lẫn API.

```bash
# 1. Build frontend vào public/
cd frontend
npm run build          # = ng build

# 2. Tắt development mode cho backend (tối ưu, ẩn lỗi chi tiết)
cd ..
composer development-disable

# 3. Phục vụ toàn bộ ứng dụng qua PHP (hoặc dùng Apache/Nginx trỏ vào public/)
composer serve         # http://localhost:8080  -> giao diện + API cùng cổng
```

### Hoặc dùng Docker

```bash
docker-compose up --build     # ứng dụng chạy tại http://localhost:8080
```

> **Lưu ý Docker:** `Dockerfile` đã bật sẵn extension `pdo_pgsql`. Trỏ `host` DB trong `local.php` tới PostgreSQL phù hợp (không phải `localhost` của container, trừ khi PostgreSQL cũng chạy trong cùng mạng Docker).

---

## 6. Tóm tắt lệnh nhanh

```bash
# ===== Terminal 1: Backend =====
composer install            # lần đầu
createdb -U postgres postmate                       # lần đầu
psql -U postgres -d postmate -f data/db/postmate.sql   # lần đầu
composer development-enable
composer serve              # -> http://localhost:8080

# ===== Terminal 2: Frontend =====
cd frontend
npm install                 # lần đầu
npm start                   # -> http://localhost:4200
```

Mở **http://localhost:4200** để sử dụng ứng dụng.

---

## 7. Lệnh hữu ích khác

| Lệnh | Tác dụng |
|------|----------|
| `composer development-status` | Xem đang ở chế độ dev hay production |
| `composer clear-config-cache` | Xóa cache config (bắt buộc chạy sau khi sửa file config) |
| `composer test` | Chạy unit test (PHPUnit) |
| `composer cs-check` / `composer cs-fix` | Kiểm tra / tự sửa coding standard |
| `composer static-analysis` | Phân tích tĩnh bằng Psalm |
| `cd frontend && npm run build` | Build frontend vào `public/` |
| `cd frontend && npm test` | Chạy test frontend (Karma) |

---

## 8. Xử lý sự cố thường gặp

| Triệu chứng | Nguyên nhân & cách xử lý |
|-------------|--------------------------|
| `could not find driver` khi gọi API | Chưa bật extension `pdo_pgsql` trong `php.ini` của PHP đang chạy. WAMP có `php.ini` cho CLI và Apache **riêng biệt** — bật cho đúng cái đang dùng (xem mục 2) |
| Frontend gọi API bị lỗi 404 / CORS | Backend chưa chạy ở port 8080, hoặc `proxy.conf.json` trỏ sai target |
| `password authentication failed for user "postgres"` | Sai `username`/`password` trong `config/autoload/local.php`, hoặc `pg_hba.conf` của PostgreSQL đang yêu cầu phương thức xác thực khác (vd `scram-sha-256`/`peer`) |
| Sửa config nhưng không có tác dụng | Chạy `composer clear-config-cache` |
| `Unable to load application` | Chưa chạy `composer install` (thiếu thư mục `vendor/`) |
| `ng: command not found` | Dùng `npx ng ...` hoặc `npm start` thay vì gọi `ng` trực tiếp |
| Đổi port backend | `php -S 0.0.0.0:<port> -t public` và sửa `target` trong `frontend/proxy.conf.json` |

---

## 9. Cấu trúc thư mục chính

```
AutoPostSocial/
├── config/              # Cấu hình Laminas (autoload/local.php = DB, session)
├── data/db/postmate.sql # Schema cơ sở dữ liệu
├── docs/                # Tài liệu phân tích hệ thống & tính năng
├── module/              # Code backend (mỗi domain 1 module)
│   ├── Application/      #   Lớp nền dùng chung (base controller/mapper/model)
│   ├── User/            #   Xác thực đăng nhập (/api/user/auth)
│   ├── Posting/         #   Bài đăng + Dashboard (/api/posting/...)
│   ├── Facebook/        #   Tài khoản / Fanpage / Cookie (/api/facebook/...)
│   ├── Infra/           #   Trình duyệt / Server / Proxy (/api/infra/...)
│   └── Setting/         #   Cấu hình + Meta App (/api/setting/...)
├── public/              # Web root (index.php + bản build Angular)
├── frontend/            # Mã nguồn Angular 20
│   ├── src/app/         #   Các trang & service gọi API
│   └── proxy.conf.json  #   Proxy /api -> localhost:8080
├── vendor/              # Thư viện PHP (composer)
├── composer.json        # Khai báo backend + script serve/test
├── Dockerfile           # Image PHP 8.3 + Apache
└── docker-compose.yml   # Chạy nhanh bằng Docker (port 8080)
```
