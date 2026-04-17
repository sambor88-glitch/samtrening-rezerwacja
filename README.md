# SAMtrening – Laravel Backend

## Szybki start (Docker)

### 1. Sklonuj repozytorium
```bash
git clone https://gitlab.com/YOUR_USERNAME/samtrening.git
cd samtrening
```

### 2. Skonfiguruj środowisko
```bash
cp .env.example .env
# Opcjonalnie zmień APP_PORT, DB_PASSWORD, ADMIN_PASSWORD
```

### 3. Uruchom aplikację
```bash
docker-compose up -d
```

Aplikacja będzie dostępna pod: **http://localhost:8080**

> Pierwsze uruchomienie może chwilę potrwać – pobieranie obrazów Docker i instalacja Composer.

### 4. Adresy paneli
| Panel     | URL                          |
|-----------|------------------------------|
| Logowanie | http://localhost:8080/login  |
| Trener    | http://localhost:8080/trainer|
| Klient    | http://localhost:8080/client |
| Admin     | http://localhost:8080/admin  |

### 5. Domyślne konta trenerów
| Login  | Hasło      |
|--------|------------|
| kasia  | kasia123   |
| maciek | maciek123  |
| kuba   | kuba123    |

Hasło admina: `samtrening2024` (można zmienić w `.env` → `ADMIN_PASSWORD`)

---

## Zatrzymanie
```bash
docker-compose down          # zatrzymaj kontenery
docker-compose down -v       # zatrzymaj + usuń bazę danych
```

## Logi
```bash
docker-compose logs -f app    # logi PHP/Laravel
docker-compose logs -f nginx  # logi Nginx
docker-compose logs -f db     # logi PostgreSQL
```

## Migracje / Seed (ręcznie)
```bash
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
```

---

## Struktura projektu
```
samtrening-laravel/
├── app/
│   ├── Http/
│   │   ├── Controllers/   # AuthController, TrainerController, ClientController, AdminController
│   │   └── Middleware/    # TrainerAuth, ClientAuth, AdminAuth
│   └── Models/            # 14 modeli Eloquent
├── database/
│   ├── migrations/        # 15 migracji
│   └── seeders/           # TrainerSeeder (kasia, maciek, kuba)
├── docker/
│   ├── nginx/default.conf
│   └── php/Dockerfile + entrypoint.sh
├── public/                # HTML/JS frontend + index.php (Laravel)
├── routes/
│   ├── api.php            # 50+ endpointów API
│   └── web.php
├── docker-compose.yml
└── .gitlab-ci.yml         # CI/CD pipeline
```

---

## GitLab CI/CD

Pipeline automatycznie:
1. **Build** – buduje obraz Docker i pushuje do GitLab Container Registry
2. **Test** – uruchamia testy (z bazą testową PostgreSQL)
3. **Deploy** – ręczny deployment na serwer produkcyjny (wymaga konfiguracji zmiennych CI)

### Zmienne CI do ustawienia (GitLab → Settings → CI/CD → Variables)
| Zmienna         | Opis                        |
|-----------------|-----------------------------|
| `DEPLOY_HOST`   | IP/hostname serwera         |
| `DEPLOY_USER`   | Użytkownik SSH              |
| `DEPLOY_PATH`   | Ścieżka projektu na serwerze|
| `DEPLOY_SSH_KEY`| Prywatny klucz SSH          |
