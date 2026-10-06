# 🔍 Job Scraper — Backend Symfony 7.4

Application complète de scraping d'offres d'emploi avec authentification utilisateur, file de messages asynchrone RabbitMQ, persistance MySQL et interface d'administration EasyAdmin v5.

---

## Stack technique

| Composant     | Version          |
|---------------|------------------|
| PHP           | 8.3-FPM          |
| Symfony       | 7.4 LTS          |
| MySQL         | 8.0              |
| RabbitMQ      | 3 (management)   |
| Nginx         | alpine           |
| EasyAdmin     | 5.x              |
| Adminer       | latest           |

---

## Comment ça fonctionne — Vue d'ensemble

L'application repose sur trois piliers :

1. **Scraping** : Un script Python scrape LinkedIn et envoie les offres vers le backend via une API REST.
2. **Traitement asynchrone** : Symfony ne traite pas les offres immédiatement — il les place dans une file RabbitMQ et répond instantanément. Un worker tourne en arrière-plan et consomme la file à son rythme.
3. **Persistance & administration** : Les offres validées sont enregistrées en MySQL et consultables via l'interface EasyAdmin.

---

## Architecture détaillée

```
Scripts Python (scraping LinkedIn)
       │  POST /api/job-offers  (HTTP 202 immédiat)
       ▼
┌─────────────────────────────┐
│     JobOfferController      │  ← reçoit le JSON, valide les données
│  src/Controller/            │
└────────────┬────────────────┘
             │  dispatch(JobOfferMessage)
             ▼
┌─────────────────────────────┐
│      JobOfferService        │  ← crée un message par offre
│  src/Service/               │
└────────────┬────────────────┘
             │  via Symfony Messenger
             ▼
┌─────────────────────────────┐
│         RabbitMQ            │  ← stocke les messages dans la file
│  exchange: job_offers       │    (persistant, survivre aux redémarrages)
└────────────┬────────────────┘
             │  consommé par le worker (processus séparé)
             ▼
┌─────────────────────────────┐
│  JobOfferMessageHandler     │  ← crée l'entité JobOffer
│  src/MessageHandler/        │     et la persiste en base
└────────────┬────────────────┘
             │
             ▼
        MySQL (table: job_offer)
```

---

## Pourquoi RabbitMQ ? Le traitement asynchrone

Sans file de messages, l'API devrait scraper LinkedIn **et** enregistrer en base **dans la même requête HTTP**. Si LinkedIn est lent, si la base est surchargée, ou si 500 offres arrivent d'un coup, l'utilisateur attend — ou la requête timeout.

Avec RabbitMQ, le flux est **découplé** :

- Le contrôleur reçoit les offres et répond **202 Accepted** en quelques millisecondes.
- Les messages sont stockés dans la file RabbitMQ, indépendamment de l'API.
- Le **worker** (un processus PHP séparé) consomme la file à son rythme : une offre à la fois, avec gestion des erreurs et des retries automatiques.
- Si le worker plante, les messages restent dans la file et sont retraités au redémarrage.

Ce pattern s'appelle **Producer / Consumer** :
- **Producer** : `JobOfferService` (pousse dans la file)
- **Consumer** : `JobOfferMessageHandler` (lit et traite)

---

## Entités Doctrine (base de données)

### `JobOffer` — `src/Entity/JobOffer.php`

Représente une offre d'emploi scrappée et persistée.

| Champ         | Type                  | Description                              |
|---------------|-----------------------|------------------------------------------|
| `id`          | int (auto)            | Identifiant unique                       |
| `title`       | string(255)           | Intitulé du poste                        |
| `company`     | string(255)           | Nom de l'entreprise                      |
| `location`    | string(255), nullable | Lieu du poste                            |
| `description` | text, nullable        | Description complète                     |
| `url`         | string(500), nullable | Lien vers l'offre originale              |
| `status`      | string(50)            | État : `pending`, `processed`, `ignored` |
| `createdAt`   | DateTimeImmutable     | Date de création (set par le handler)    |
| `processedAt` | DateTimeImmutable     | Date de traitement par le worker         |

### `User` — `src/Entity/User.php`

Utilisateur de l'application (authentification Symfony Security).

| Champ            | Type              | Description                          |
|------------------|-------------------|--------------------------------------|
| `id`             | int (auto)        | Identifiant unique                   |
| `email`          | string(180)       | Email — identifiant de connexion     |
| `roles`          | json              | Tableau de rôles (`ROLE_USER`, etc.) |
| `password`       | string            | Mot de passe hashé (bcrypt)          |
| `searchQueries`  | OneToMany         | Requêtes de recherche de l'utilisateur |

### `SearchQuery` — `src/Entity/SearchQuery.php`

Requête de recherche enregistrée par un utilisateur. Le cron s'en sert pour lancer les scrapings automatiques.

| Champ       | Type              | Description                               |
|-------------|-------------------|-------------------------------------------|
| `id`        | int (auto)        | Identifiant unique                        |
| `keyword`   | string(255)       | Mot-clé de recherche (ex: `drupal`)       |
| `location`  | string(255), null | Lieu (ex: `Switzerland`)                  |
| `distance`  | integer, null     | Rayon en miles (10, 25, 50, 100)          |
| `isActive`  | boolean           | Si `false`, le cron ignore cette requête  |
| `createdAt` | DateTimeImmutable | Date de création                          |
| `user`      | ManyToOne → User  | Utilisateur propriétaire                  |

---

## Contrôleurs

### `JobOfferController` — `src/Controller/JobOfferController.php`

Point d'entrée de l'API REST. Accessible sans authentification (`/api` est public dans `security.yaml`).

| Route               | Méthode | Accès  | Description                          |
|---------------------|---------|--------|--------------------------------------|
| `POST /api/job-offers` | POST | PUBLIC | Reçoit les offres JSON, dispatche dans RabbitMQ |

### `SecurityController` — `src/Controller/SecurityController.php`

Généré par `make:security:form-login`. Gère la page de connexion.

| Route        | Méthode   | Description                        |
|--------------|-----------|------------------------------------|
| `/login`     | GET/POST  | Formulaire de connexion            |
| `/logout`    | GET       | Déconnexion (géré par le firewall) |

### `DashboardController` — `src/Controller/Admin/DashboardController.php`

Tableau de bord EasyAdmin v5. Accessible uniquement aux utilisateurs connectés (`ROLE_USER`).

| Route    | Accès     | Description              |
|----------|-----------|--------------------------|
| `/admin` | ROLE_USER | Interface d'administration |

### `JobOfferCrudController` — `src/Controller/Admin/JobOfferCrudController.php`

CRUD EasyAdmin pour les offres d'emploi : liste, détail, édition, suppression.

---

## Messages Symfony Messenger

### `JobOfferMessage` — `src/Message/JobOfferMessage.php`

Simple objet PHP (DTO) transporté dans la file RabbitMQ. Contient les données brutes d'une offre : `title`, `company`, `location`, `description`, `url`.

### `JobOfferMessageHandler` — `src/MessageHandler/JobOfferMessageHandler.php`

Déclenché automatiquement par le worker quand un `JobOfferMessage` sort de la file. Crée une entité `JobOffer`, définit les dates, et persiste en base via Doctrine.

```php
#[AsMessageHandler]
class JobOfferMessageHandler
{
    public function __invoke(JobOfferMessage $message): void
    {
        $offer = new JobOffer();
        $offer->setTitle($message->getTitle());
        // ...
        $offer->setCreatedAt(new \DateTimeImmutable());
        $offer->setProcessedAt(new \DateTimeImmutable());
        $this->em->persist($offer);
        $this->em->flush();
    }
}
```

---

## Authentification (Symfony Security)

La sécurité est configurée dans `config/packages/security.yaml`.

**Règles d'accès :**

| Chemin    | Accès requis   |
|-----------|----------------|
| `/login`  | PUBLIC         |
| `/api/**` | PUBLIC         |
| `/admin/**` | `ROLE_USER`  |
| `/**`     | `ROLE_USER`   |

Les mots de passe sont hashés avec **bcrypt** (algorithme `auto` de Symfony). Pour créer un hash :

```bash
docker compose exec php bin/console security:hash-password
```

Pour créer un utilisateur en base directement (développement) :

```sql
INSERT INTO user (email, roles, password)
VALUES ('admin@example.com', '["ROLE_USER"]', '$2y$13$...');
```

---

## Scripts Python — Scraping LinkedIn

Le script `scripts/linkedin_job_search.py` scrape LinkedIn et envoie les résultats au backend.

### Installation

```bash
cd scripts/
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
```

### Utilisation

```bash
# Recherche + envoi automatique au backend (Docker doit tourner)
python linkedin_job_search.py drupal --location "Switzerland" --results 5

# Avec rayon de 25 miles
python linkedin_job_search.py symfony --location "Genève" --distance 25

# Scraping seul, sans envoi au backend (mode debug)
python linkedin_job_search.py drupal --no-send

# Backend sur une autre URL
python linkedin_job_search.py drupal --backend http://mon-serveur:8080/api/job-offers
```

### Arguments disponibles

| Argument     | Défaut                                  | Description                        |
|--------------|-----------------------------------------|------------------------------------|
| `keyword`    | —                                       | Mot-clé de recherche (obligatoire) |
| `--location` | `Switzerland`                           | Lieu de recherche                  |
| `--results`  | `5`                                     | Nombre d'offres à récupérer        |
| `--distance` | —                                       | Rayon en miles (10 / 25 / 50 / 100)|
| `--backend`  | `http://localhost:8080/api/job-offers`  | URL du contrôleur Symfony          |
| `--no-send`  | —                                       | Scraping seul, sans envoi backend  |

---

## Cron automatique (container Docker)

Le service `scraper` dans `docker-compose.yml` est un container Python avec `cron` intégré. Il tourne en arrière-plan et exécute le script selon la planification définie dans `docker/scraper/crontab`.

Par défaut : toutes les 6h, recherche `drupal` en Suisse.

Pour modifier la fréquence ou le mot-clé, édite `docker/scraper/crontab` et rebuilde :

```bash
docker compose build scraper
docker compose up -d scraper
```

---

## Démarrage rapide

### Prérequis

- Docker + Docker Compose
- Git

### 1. Cloner le repo

```bash
git clone git@github.com:baherdev/script_job_offer.git
cd script_job_offer
```

### 2. Lancer les containers

```bash
docker compose up -d --build
```

> ⏳ MySQL prend ~1 min à démarrer sur WSL. Attends que tous les services soient `healthy`.

### 3. Créer la base et lancer les migrations

```bash
docker compose exec php bin/console doctrine:database:create
docker compose exec php bin/console doctrine:migrations:migrate
```

### 4. Créer le premier utilisateur admin

```bash
docker compose exec php bin/console security:hash-password
# copie le hash généré

docker compose exec mysql mysql -uroot -proot job_scraper -e \
  "INSERT INTO user (email, roles, password) VALUES ('admin@example.com', '[\"ROLE_USER\"]', 'HASH_ICI');"
```

### 5. Accéder à l'application

| Interface          | URL                               | Credentials      |
|--------------------|-----------------------------------|------------------|
| Application        | http://localhost:8080/login       | ton email/mdp    |
| EasyAdmin          | http://localhost:8080/admin       | ton email/mdp    |
| RabbitMQ UI        | http://localhost:15672            | guest / guest    |
| Adminer (MySQL)    | http://localhost:8081             | root / root      |

---

## Structure du projet

```
script_job_offer/
├── backend/                              # Application Symfony 7.4
│   ├── src/
│   │   ├── Controller/
│   │   │   ├── Admin/
│   │   │   │   ├── DashboardController.php   # Tableau de bord EasyAdmin
│   │   │   │   └── JobOfferCrudController.php # CRUD offres d'emploi
│   │   │   ├── JobOfferController.php         # API REST POST /api/job-offers
│   │   │   └── SecurityController.php         # Login / Logout
│   │   ├── Entity/
│   │   │   ├── JobOffer.php                   # Offre d'emploi
│   │   │   ├── User.php                       # Utilisateur (auth)
│   │   │   └── SearchQuery.php                # Requête de recherche (cron)
│   │   ├── Message/
│   │   │   └── JobOfferMessage.php            # DTO transporté dans RabbitMQ
│   │   ├── MessageHandler/
│   │   │   └── JobOfferMessageHandler.php     # Worker : consomme la file
│   │   ├── Repository/
│   │   │   ├── JobOfferRepository.php
│   │   │   ├── UserRepository.php
│   │   │   └── SearchQueryRepository.php
│   │   └── Service/
│   │       └── JobOfferService.php            # Dispatch des messages
│   ├── config/
│   │   └── packages/
│   │       ├── messenger.yaml                 # Config RabbitMQ transport
│   │       └── security.yaml                  # Firewall, access_control
│   └── migrations/
├── docker/
│   ├── php/Dockerfile                         # PHP 8.3 + intl + amqp
│   ├── nginx/default.conf
│   └── scraper/
│       ├── Dockerfile                         # Python + cron
│       └── crontab                            # Planification du scraping
├── scripts/
│   ├── linkedin_job_search.py                 # Scraper LinkedIn (argparse)
│   └── requirements.txt
├── docker-compose.yml
└── .gitignore
```

---

## API — POST /api/job-offers

```bash
curl -X POST http://localhost:8080/api/job-offers \
  -H "Content-Type: application/json" \
  -d '{
    "offers": [
      {
        "title": "Développeur Drupal Senior",
        "company": "Acme Corp",
        "location": "Genève, Suisse",
        "description": "Poste senior Drupal 10...",
        "url": "https://example.com/job/123"
      }
    ]
  }'
```

**Réponse (HTTP 202 Accepted) :**

```json
{
  "message": "1 offer(s) dispatched to the queue.",
  "dispatched": 1
}
```

---

## Commandes utiles

```bash
# Lancer le worker manuellement (hors container worker)
docker compose exec php bin/console messenger:consume async -vv

# Voir les logs du worker
docker compose logs -f worker

# Voir les logs du cron scraper
docker compose logs -f scraper

# Vider le cache Symfony
docker compose exec php bin/console cache:clear

# Voir toutes les routes
docker compose exec php bin/console debug:router

# Voir les offres en base
docker compose exec mysql mysql -uroot -proot job_scraper \
  -e "SELECT id, title, company, status, created_at FROM job_offer"
```

---

## Variables d'environnement

Fichier `backend/.env.local` (non versionné) :

```env
APP_ENV=dev
APP_SECRET=changeme
DATABASE_URL="mysql://root:root@mysql:3306/job_scraper?serverVersion=8.0&charset=utf8mb4"
MESSENGER_TRANSPORT_DSN=amqp://guest:guest@rabbitmq:5672/%2f/job_offers
```

---

## Auteur

**Baher Rais** — [github.com/baherdev](https://github.com/baherdev)  
Freelancemodule Sàrl — Orbe, Suisse