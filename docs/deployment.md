# Production deployment: Docker + EC2 + GHCR

Стек: PHP 8.5-FPM, Laravel 13, Livewire, Tailwind CSS (зібраний під час
Docker build), MySQL 8.4, Nginx, GitHub Actions → GHCR → EC2 (Docker Compose
через SSH). Черги та сесії/кеш використовують `database`-драйвер — окремого
Redis у стеку немає.

Файли цього рішення:

| # | Файл |
|---|------|
| 1 | `Dockerfile` (multi-stage: `frontend-build`, `composer-build`, `app`, `nginx`) |
| 2 | `compose.yaml` |
| 3 | `docker/nginx/nginx.conf`, `docker/nginx/conf.d/app.conf` |
| 4 | `.github/workflows/deploy.yml` |
| 5-11 | цей документ |

---

## 5. Необхідні GitHub Secrets / Variables

Налаштовуються в `Settings → Secrets and variables → Actions` репозиторію.

**Secrets (обов'язкові):**

| Secret | Призначення |
|---|---|
| `EC2_HOST` | IP або DNS-ім'я EC2-інстансу |
| `EC2_SSH_USER` | Користувач для SSH (напр. `ubuntu`) |
| `EC2_SSH_KEY` | Приватний SSH-ключ (PEM, повний вміст файлу) для доступу до EC2 |

**Secrets (опційні):**

| Secret | За замовчуванням | Призначення |
|---|---|---|
| `EC2_SSH_PORT` | `22` | Якщо SSH слухає нестандартний порт |

**Variables (опційні):**

| Variable | За замовчуванням | Призначення |
|---|---|---|
| `EC2_DEPLOY_PATH` | `/opt/publish-flow` | Шлях до compose-проєкту на EC2 |

**Чого НЕ потрібно зберігати в GitHub Secrets:**

- Production `.env` / `DB_PASSWORD` / `APP_KEY` — вони існують лише на EC2
  (розділ 6-7), workflow їх ніколи не читає і не передає.
- Токен для `docker login ghcr.io` в GitHub Actions — push у GHCR
  виконується вбудованим `GITHUB_TOKEN` (workflow має `permissions:
  packages: write`), додатковий PAT не потрібен.
- Токен для `docker login ghcr.io` **на самому EC2** — це не GitHub Secret,
  а одноразова локальна дія під час initial setup (розділ 7), якщо GHCR
  package приватний.

---

## 6. Структура файлів на EC2

```
/opt/publish-flow/
├── compose.yaml              # скопійований з репозиторію один раз (розділ 7)
├── .env                      # ← PERSISTENT, створюється вручну, ніколи не в git/image
├── .deploy.env                # non-secret: поточні APP_IMAGE/NGINX_IMAGE, перезаписується CI щодеплою
├── docker/
│   └── nginx/
│       ├── nginx.conf         # скопійовано з репозиторію
│       └── conf.d/app.conf    # домен/SSL правляться тут вручну, без ребілду
├── ssl/                        # ← PERSISTENT: сертифікати (напр. Let's Encrypt live/)
├── certbot-webroot/            # ← webroot для ACME http-01 challenge
└── storage/
    └── app/                    # ← PERSISTENT: те саме, що storage/app у Laravel
        ├── public/              #   'public' disk (symlink public/storage)
        ├── private/              #   'local' disk
        └── videos/               #   'videos' disk — uploaded/generated відео
```

`compose.yaml` та `docker/nginx/**` — це конфігурація, її повторно
копіюють/оновлюють з репозиторію при зміні (вручну або окремим CI-кроком,
за потреби). `.env`, `.deploy.env`, `ssl/`, `certbot-webroot/` та
`storage/app/**` — це стан, який deploy-пайплайн ніколи не видаляє і не
перезаписує (крім `.deploy.env`, який CI навмисно оновлює щодеплою — там
лише тег образу, не секрет).

---

## 7. Initial EC2 setup (одноразово)

**Мінімальний розмір інстансу: `t3.small` (2GB RAM).** `t3.micro`/`t2.micro`
(1GB) перевірено не вистачає — MySQL + 3 PHP-FPM контейнери (app/queue/
scheduler) + nginx одночасно займають майже весь 1GB без жодного запасу,
без swap система йде в OOM ще на старті, MySQL не встигає стабільно
піднятись → `Connection refused`/`Access denied` на перших деплоях.
`t3.medium` (4GB) — комфортніший запас, якщо не критично +$.

**Важливо — заповнюй `.env` (крок 4) ДО першого `docker compose up`.**
`MYSQL_DATABASE`/`MYSQL_USER`/`MYSQL_PASSWORD` застосовуються офіційним
MySQL-образом лише один раз — при ініціалізації **порожнього**
`mysql-data` volume. Якщо запустити стек із заглушками/неповним `.env`, а
потім виправити значення — БД і юзер уже НЕ переініціалізуються, і
`DB_PASSWORD`/`DB_USERNAME` з оновленого `.env` просто не збігатимуться з
тим, що реально в MySQL (`Access denied`). Виправити без втрати даних:
```bash
DB_PASSWORD=$(grep -E '^DB_PASSWORD=' .env | cut -d= -f2-)
MYSQL_ROOT_PASSWORD=$(grep -E '^MYSQL_ROOT_PASSWORD=' .env | cut -d= -f2-)
docker compose --env-file .deploy.env exec -T mysql mysql -uroot -p"$MYSQL_ROOT_PASSWORD" \
  -e "CREATE USER IF NOT EXISTS 'publish_flow'@'%' IDENTIFIED BY '$DB_PASSWORD'; GRANT ALL PRIVILEGES ON publish_flow.* TO 'publish_flow'@'%'; FLUSH PRIVILEGES;"
```
(або, якщо реальних даних ще нема, простіше видалити volume `mysql-data`
вручну й дати MySQL переініціалізуватись начисто — але це осмислена дія
самого оператора, ніколи не частина деплой-пайплайна.)

```bash
# 1. Docker Engine + Compose plugin (Ubuntu 22.04/24.04)
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker "$USER"
newgrp docker

# 2. Каталоги проєкту з правильним власником.
#    UID/GID 1000 має збігатися з APP_UID/APP_GID, з якими зібраний образ
#    (build-arg за замовчуванням у Dockerfile — 1000; це типовий UID
#    користувача ubuntu/ec2-user на стокових AMI).
sudo mkdir -p /opt/publish-flow/{docker/nginx/conf.d,ssl,certbot-webroot,storage/app/public,storage/app/private,storage/app/videos}
sudo chown -R 1000:1000 /opt/publish-flow
cd /opt/publish-flow

# 3. Скопіювати конфігурацію з репозиторію (один раз; SCP, git archive
#    через свою машину, або вручну — EC2 сам git не потребує).
#    Потрібні: compose.yaml, docker/nginx/nginx.conf, docker/nginx/conf.d/app.conf

# 4. Персистентний .env — створити на основі .env.production.example з репо,
#    заповнити реальні значення (APP_KEY, DB_*, MYSQL_*, APP_URL тощо).
nano .env
chmod 600 .env

# 5. Якщо GHCR-пакет приватний — залогінитись один раз, докер закешує
#    креденшли в ~/.docker/config.json, наступні `docker compose pull`
#    (в тому числі з CI) працюватимуть без повторного логіну.
#    Потрібен GitHub PAT (classic або fine-grained) зі scope read:packages.
echo "<PAT>" | docker login ghcr.io -u <github-username> --password-stdin

# 6. TLS-сертифікат (Let's Encrypt, приклад через standalone-контейнер;
#    домен у docker/nginx/conf.d/app.conf має бути вже прописаний,
#    DNS має вказувати на цей EC2 до запуску):
docker run --rm -p 80:80 \
  -v /opt/publish-flow/ssl:/etc/letsencrypt \
  -v /opt/publish-flow/certbot-webroot:/var/www/certbot \
  certbot/certbot certonly --standalone \
  -d example.com -d www.example.com \
  --email you@example.com --agree-tos --non-interactive
# nginx тут ще не запущений (порт 80 вільний) — тому --standalone.

# 6b. Auto-renewal: після того як nginx уже запущений (крок 8), standalone
#     більше не використовується — порт 80 зайнятий nginx, який сам віддає
#     /.well-known/acme-challenge/ з certbot-webroot (location вже є в
#     app.conf). Renewal — тим самим webroot, без зупинки nginx:
( crontab -l 2>/dev/null; echo "17 3 * * * docker run --rm \
  -v /opt/publish-flow/ssl:/etc/letsencrypt \
  -v /opt/publish-flow/certbot-webroot:/var/www/certbot \
  certbot/certbot renew --webroot -w /var/www/certbot --quiet \
  && docker compose -f /opt/publish-flow/compose.yaml --env-file /opt/publish-flow/.deploy.env exec -T nginx nginx -s reload" ) | crontab -

# 7. Security Group / firewall: відкрити 80 і 443 назовні,
#    22 (SSH) — тільки з довірених IP. Порт MySQL (3306) НЕ відкривати —
#    він у Compose навіть не публікується на host.

# 8. Перший запуск (без образів з CI ще нема — можна пропустити цей крок
#    і одразу зробити push у main, workflow задеплоїть сам):
# echo "APP_IMAGE=ghcr.io/<owner>/<repo>/app:latest" > .deploy.env
# echo "NGINX_IMAGE=ghcr.io/<owner>/<repo>/nginx:latest" >> .deploy.env
# docker compose --env-file .deploy.env pull
# docker compose --env-file .deploy.env up -d
```

---

## 8. Deployment flow

1. Push у `main` (або ручний запуск `workflow_dispatch`) тригерить
   `.github/workflows/deploy.yml`.
2. Job **build**: `actions/checkout` → Buildx → build+push двох образів
   (`Dockerfile`, target `app` і target `nginx`) у GHCR, теги
   `sha-<12-символів-коміту>` та `latest`. Усередині build-у виконуються
   `npm ci && npm run build` (frontend-build stage) і
   `composer install --no-dev --prefer-dist --optimize-autoloader`
   (composer-build stage) — повністю на runner'і GitHub Actions.
3. Job **deploy**: SSH на EC2 (`appleboy/ssh-action`), на хості:
   - записується `.deploy.env` з новими тегами образів (не секрет,
     перезаписується щоразу — це навмисно, `.env` із секретами файл цей
     крок не чіпає);
   - `docker compose --env-file .deploy.env pull`;
   - `docker compose --env-file .deploy.env up -d` — перестворює `app`,
     `queue`, `scheduler`, `nginx` (restart policy `unless-stopped`
     гарантує, що `queue` й `scheduler` самі піднімуться після recreate);
   - очікування готовності `app`-контейнера, потім
     `php artisan migrate --force` і `php artisan optimize` через
     `docker compose exec`.
4. Ніде в пайплайні немає `composer update`, `docker compose down -v`,
   видалення volume/bind-mount або дропу БД.

---

## 9. Rollback strategy

Образи в GHCR іммутабельні й тегуються `sha-<commit>` — стара версія
залишається доступною після наступного деплою.

**Спосіб 1 — через workflow (рекомендовано):**
`Actions → Build and deploy → Run workflow`, поле `image_tag` =
попередній відомий тег (напр. `sha-abc123def456`, видно в історії запусків
або в GHCR package versions). Job **build** пропускається, job **deploy**
одразу перевикочує вказаний тег на EC2 — без ребілду, за секунди.

**Спосіб 2 — вручну на EC2** (якщо CI недоступний):

```bash
cd /opt/publish-flow
cat > .deploy.env <<EOF
APP_IMAGE=ghcr.io/<owner>/<repo>/app:sha-<previous>
NGINX_IMAGE=ghcr.io/<owner>/<repo>/nginx:sha-<previous>
EOF
docker compose --env-file .deploy.env pull
docker compose --env-file .deploy.env up -d
docker compose --env-file .deploy.env exec -T app php artisan optimize
```

**Про міграції при rollback:** rollback-крок навмисно НЕ запускає
`migrate:rollback` автоматично — відкат схеми БД є руйнівною операцією і
має виконуватись усвідомлено, окремою командою, тільки якщо конкретна
міграція справді несумісна зі старим кодом:

```bash
docker compose --env-file .deploy.env exec -T app php artisan migrate:rollback --step=1 --force
```

Здебільшого достатньо відкотити лише образ — Laravel-міграції в цьому
пайплайні пишуться прямою (forward-only) сумісною, і застосований
`migrate --force` попереднього деплою зазвичай не заважає старому коду.

---

## 10. Persistent storage strategy: MySQL і videos

**MySQL** — іменований Docker volume `mysql-data`, змонтований у
`mysql`-сервіс на `/var/lib/mysql`. Керується самим Docker-демоном (не
bind mount), не залежить від шляхів на хості. Видаляється лише явним
`docker volume rm mysql-data` або `docker compose down -v` — жоден з цих
двох ніде в deploy-пайплайні не викликається.

**Video storage** — bind mount `/opt/publish-flow/storage/app` (host) →
`/var/www/html/storage/app` (контейнери `app`/`queue`/`scheduler`, RW), і
той самий шлях, але лише підкаталог `.../storage/app/public` →
`/var/www/html/storage/app/public` (контейнер `nginx`, RO — потрібен лише
для роздачі файлів з публічного 'public' диска через симлінк
`public/storage`). Диск `videos` (`config/filesystems.php`, `driver:
local, root: storage_path('app/videos')`) навмисно приватний
(`visibility: private`) — файли не роздаються напряму nginx, доступ лише
через application-логіку (контрольований доступ, підписані URL тощо), що
природно узгоджується з вимогою "не видаляти файли, поки публікація не
підтверджена" — видалення відео залишається виключно application-логікою
(job/команда), deploy-пайплайн до файлів у `storage/app/videos` взагалі не
торкається.

**Чому bind mount, а не named volume для відео:** відео-файли мають бути
видимі на хості як звичайні файли — для бекапів (`rsync`/`tar` напряму),
моніторингу дискового простору й ручного аудиту. Для MySQL це не потрібно
(і небажано — прямий доступ до InnoDB-файлів у обхід сервера СУБД
ризикований), тому там навпаки обрано named volume.

**Docker image ніколи не містить самих відео** — `.dockerignore` виключає
вміст `storage/app/videos/*` (лишає тільки `.gitignore`-плейсхолдер) із
build context, а Dockerfile лише відтворює порожню структуру директорій і
симлінк `public/storage` (`php artisan storage:link`), не копіюючи жодних
даних усередину образу.

---

## 11. Що саме переживає redeploy

| Шлях / volume | Переживає redeploy? | Чому |
|---|---|---|
| `mysql-data` (named volume) | ✅ | Docker volume, не чіпається жодною командою деплою |
| `/opt/publish-flow/storage/app/videos` | ✅ | Bind mount поза образом; новий `app`-контейнер монтує той самий каталог |
| `/opt/publish-flow/storage/app/public` | ✅ | Те саме, bind mount |
| `/opt/publish-flow/storage/app/private` | ✅ | Те саме, bind mount |
| `/opt/publish-flow/.env` | ✅ | Файл на хості, деплой його не читає й не пише |
| `/opt/publish-flow/ssl`, `certbot-webroot` | ✅ | Bind mount, не пов'язаний з образом |
| Код застосунку, `vendor/`, `public/build` | ❌ (і не повинен) | Приходить із нового image-тега щоразу — застосунок disposable за дизайном |
| `storage/framework/*` (view cache, sessions-файли, якщо раптом FILESYSTEM-сесії) | ❌ | Не змонтовано; втім, `SESSION_DRIVER=database` і `CACHE_STORE=database` — тому це й не потрібно: стан, який має survive, і так живе в MySQL, а не у файлах контейнера |
| `storage/logs` | ❌ (за дизайном) | `LOG_CHANNEL=stderr` — логи йдуть у `docker compose logs`, а не у файл усередині контейнера |
| Сам контейнер (app/queue/scheduler/nginx) | ❌ | Перестворюється щодеплою — це і є "disposable & safe for recreation" |

Підсумок: усе, що повинно пережити redeploy (БД, відео, `.env`, TLS-серти),
живе поза образом — на іменованому volume або bind mount на EC2. Усе, що
всередині образу (код, vendor, зібрані assets), навмисно одноразове й
перестворюється при кожному деплої.
