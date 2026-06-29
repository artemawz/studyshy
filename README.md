# Studyshy

Anonyme Matching-Plattform für Studierende – Vue.js Frontend mit Laravel REST-API und MariaDB.

## Schnellstart mit Docker Compose (empfohlen)

**Voraussetzungen:** [Docker Desktop](https://www.docker.com/products/docker-desktop/) (gestartet und lauffähig)

Alle Befehle immer im **Projektroot** ausführen:

```bash
cd /pfad/zu/studyshy
docker compose up --build
```

| Service | Container | URL / Port |
|---------|-----------|------------|
| Frontend (Vue + Vite) | `frontend` | http://localhost:5173 |
| Backend (Laravel) | `backend` | http://localhost:8000/api |
| MariaDB | `mariadb` | `localhost:3307` (nur externe Tools) |

Das Backend verbindet sich intern über den Hostnamen `mariadb:3306` – nicht über `127.0.0.1`.

**Demo-Zugang:** `student1@demo.studyshy` / `password123`

---

## Docker – ausführliche Dokumentation

### Architektur

```
Browser → localhost:5173 (Frontend)
              ↓ Proxy /api → http://backend:8000
         localhost:8000 (Backend / Laravel)
              ↓
         mariadb:3306 (MariaDB, internes Docker-Netz)
```

| Datei | Zweck |
|-------|--------|
| `docker-compose.yml` | Orchestriert MariaDB, Backend und Frontend |
| `backend/Dockerfile` | PHP 8.4 + Composer + Extensions (`pdo_mysql`, `intl`, …) |
| `backend/docker/entrypoint.sh` | Startskript: `.env` anpassen, `composer install`, Migrationen, Seeder, `artisan serve` |
| `docker/frontend/Dockerfile` | Node 22, `npm run dev` mit Hot-Reload |

Beim **ersten Start** passiert automatisch:

1. `composer install` (Vendor-Volume)
2. `.env` wird aus `.env.example` erzeugt (falls fehlend)
3. Docker-DB-Einstellungen werden in `.env` geschrieben (`DB_HOST=mariadb`)
4. `php artisan key:generate` (falls kein Key)
5. `php artisan migrate`
6. `php artisan db:seed` (einmalig, Marker: `backend/storage/app/.seeded`)
7. Laravel-Server auf Port 8000

### Wichtige Befehle

```bash
# Alles starten (mit Build)
docker compose up --build

# Im Hintergrund
docker compose up -d --build

# Status prüfen
docker compose ps

# Logs (alle Services oder einzeln)
docker compose logs -f
docker compose logs -f backend

# Stoppen
docker compose down

# Stoppen + Datenbank löschen (Neustart mit frischen Demo-Daten)
docker compose down -v
docker compose up --build

# Demo-Daten manuell neu laden
docker compose exec backend php artisan migrate:fresh --seed --force

# Shell im Backend-Container
docker compose exec backend sh

# API direkt testen
curl http://localhost:8000/api/stats
curl http://localhost:5173/api/stats   # über Vite-Proxy
```

### Was du **nicht** parallel starten solltest

Während Docker läuft, keine lokalen Prozesse auf denselben Ports:

| Port | Docker-Service | Konflikt mit |
|------|----------------|--------------|
| 5173 | Frontend | `npm run dev` |
| 8000 | Backend | `php artisan serve` |
| 3307 | MariaDB (Host) | lokale MariaDB/MySQL auf 3306 |

Lokale Server vorher mit **Ctrl+C** beenden. Belegte Ports prüfen:

```bash
lsof -i :5173 -i :8000 -i :3306 -i :3307
```

### Hybrid-Setup (optional)

Laravel **lokal** auf dem Mac, **nur MariaDB in Docker**:

```bash
docker compose up mariadb -d
```

In `backend/.env`:

```env
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=studyshy
DB_USERNAME=studyshy
DB_PASSWORD=studyshy
```

```bash
cd backend
composer install
php artisan migrate --seed
php artisan serve
npm run dev   # im Projektroot
```

---

### Häufige Fehler & Lösungen

#### Port 3306 already in use

Lokale MariaDB/MySQL blockiert den Port. Im Projekt ist MariaDB deshalb auf **Host-Port 3307** gemappt (`3307:3306` in `docker-compose.yml`). Kein Handlungsbedarf, solange alles über Docker läuft.

#### `Connection refused` – `Host: 127.0.0.1, Port: 3306`

Laravel verbindet sich mit der falschen Datenbank.

**Ursache:** `php artisan serve` läuft lokal, nutzt `backend/.env` mit `DB_HOST=127.0.0.1`, aber die DB läuft nur in Docker.

**Lösung:** Alles über Docker starten (siehe Schnellstart) **oder** Hybrid-Setup mit `DB_PORT=3307` (siehe oben).

Im Docker-Container setzt `entrypoint.sh` automatisch `DB_HOST=mariadb`.

#### `API-Fehler (502)` im Frontend

**Ursache:** Das Backend ist nicht erreichbar – meist hängt der Container beim Start oder ist abgestürzt.

**Prüfen:**

```bash
docker compose logs backend --tail 50
curl http://localhost:8000/api/stats
```

Erfolgreich, wenn JSON zurückkommt und in den Logs steht:

```
Server running on [http://0.0.0.0:8000]
```

**Typische Ursachen:**

- Backend-Image veraltet → `docker compose build --no-cache backend && docker compose up -d`
- PHP-Extension `intl` fehlt (behoben im Dockerfile; Image neu bauen)
- `composer install` schlägt fehl (PHP-Version, siehe unten)

#### Composer: PHP 8.4 required

Laravel 13 / Symfony 8.1 benötigen **PHP ≥ 8.4**. Das Backend-Dockerfile nutzt `php:8.4-cli-alpine`. Nach Änderungen am Dockerfile:

```bash
docker compose build --no-cache backend
docker compose up -d
```

#### `docker compose` aus falschem Verzeichnis

Befehle immer im Ordner mit `docker-compose.yml` ausführen (`studyshy/`), nicht in `backend/`.

#### Backend startet endlos / „Waiting for database…“

MariaDB ist noch nicht bereit oder DB-Zugangsdaten stimmen nicht. Logs prüfen:

```bash
docker compose logs mariadb
docker compose logs backend
```

MariaDB muss `healthy` sein (`docker compose ps`).

---

## Lokales Setup (ohne Docker)

### Voraussetzungen

- Node.js 20+ und npm
- PHP 8.4+ und Composer
- MariaDB (oder MySQL)

### 1. MariaDB

Datenbank und Benutzer anlegen:

```sql
CREATE DATABASE studyshy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'studyshy'@'localhost' IDENTIFIED BY 'studyshy';
GRANT ALL PRIVILEGES ON studyshy.* TO 'studyshy'@'localhost';
FLUSH PRIVILEGES;
```

### 2. Backend (Laravel)

```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan storage:link
php artisan migrate:fresh --seed
php artisan serve
```

Das Backend läuft auf http://localhost:8000

### 3. Frontend (Vue)

```bash
npm install
npm run dev
```

Das Frontend läuft auf http://localhost:5173 und leitet `/api`-Anfragen an Laravel weiter.

## Demo-Zugang

Nach dem Seeding stehen Demo-Accounts zur Verfügung:

| E-Mail | Passwort |
|--------|----------|
| student1@demo.studyshy | password123 |
| student2@demo.studyshy | password123 |
| … | password123 |

`student1@demo.studyshy` hat vorbefüllte Demo-Chats.

## REST-API

| Methode | Endpunkt | Auth | Beschreibung |
|---------|----------|------|--------------|
| POST | `/api/auth/register` | – | Registrierung |
| POST | `/api/auth/login` | – | Login → Bearer Token |
| POST | `/api/auth/logout` | ✓ | Logout |
| GET | `/api/auth/me` | ✓ | Aktuelles Profil |
| GET | `/api/students` | optional | Studierendenliste |
| GET | `/api/students/{id}` | – | Einzelprofil |
| GET | `/api/filters` | – | Filter-Optionen |
| GET | `/api/stats` | – | Plattform-Statistiken |
| PATCH | `/api/users/me` | ✓ | Profil bearbeiten |
| POST | `/api/users/me/avatar` | ✓ | Profilbild hochladen (multipart, max. 2 MB) |
| GET | `/api/chats` | ✓ | Eigene Chats |
| POST | `/api/chats` | ✓ | Chat starten (`partner_id`) |
| GET | `/api/chats/{id}/messages` | ✓ | Nachrichten laden |
| POST | `/api/chats/{id}/messages` | ✓ | Nachricht senden |

Authentifizierung: Laravel Sanctum mit `Authorization: Bearer {token}`.

## Datenmodell

- **users** – E-Mail (privat), Passwort, pub_name, uni, bio, avatar_url
- **user_courses** – Studiengang und Semester pro User
- **interests** / **interest_user** – Interessen (Many-to-Many)
- **chats** / **chat_participants** – Chats zwischen zwei Usern
- **messages** – Chat-Nachrichten

E-Mail-Adressen werden in öffentlichen API-Responses nie zurückgegeben.

## Tech-Stack

- **Frontend:** Vue 3, Vue Router, TypeScript, Vite
- **Backend:** Laravel 13, Sanctum
- **Datenbank:** MariaDB
- **Kommunikation:** REST (JSON)
- **Container:** Docker Compose

## Projektstruktur

```
studyshy/
├── backend/
│   ├── Dockerfile              # PHP 8.4 Backend-Image
│   └── docker/entrypoint.sh    # Container-Start (DB, Migration, Seed)
├── docker/
│   └── frontend/Dockerfile     # Node 22 Frontend-Image
├── docker-compose.yml          # Service-Definitionen
├── src/                        # Vue Frontend
├── public/                     # Statische Assets
└── vite.config.ts              # Dev-Proxy → Backend (Docker: backend:8000)
```
