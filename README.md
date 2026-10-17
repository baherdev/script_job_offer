# 🔍 Job Scraper — Backend Symfony 7.4

Application complète de scraping d'offres d'emploi avec authentification utilisateur, file de messages asynchrone RabbitMQ, persistance MySQL, interface d'administration EasyAdmin v5 et **scoring automatique CV↔offre par pipeline TF-IDF**.

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
| Python        | 3.x (scraper)    |
| scikit-learn  | ≥ 1.3 (TF-IDF)   |

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

### 2. Préparer le CV (fichier personnel)

Le scoring RAG nécessite un fichier `cv.txt` dans `scripts/`. Ce fichier est personnel et n'est **pas versionné** (`.gitignore`).

```bash
cp /chemin/vers/votre/cv.txt scripts/cv.txt
```

### 3. Lancer les containers

```bash
docker compose up -d --build
```

> ⏳ MySQL prend ~1 min à démarrer sur WSL. Attends que tous les services soient `healthy`.

### 4. Créer la base et lancer les migrations

```bash
docker compose exec php bin/console doctrine:database:create
docker compose exec php bin/console doctrine:migrations:migrate
```

### 5. Créer le premier utilisateur admin

```bash
docker compose exec php bin/console security:hash-password
# copie le hash généré

docker compose exec mysql mysql -uroot -proot job_scraper -e \
  "INSERT INTO user (email, roles, password) VALUES ('admin@example.com', '[\"ROLE_USER\"]', 'HASH_ICI');"
```

### 6. Ajouter une recherche planifiée

Va dans EasyAdmin → **Search Queries** → **Add** :
- Keyword : `drupal`
- Location : `Switzerland`
- isActive : ✅

Le cron s'en chargera automatiquement. Pour tester immédiatement :

```bash
docker compose exec scraper python3 /app/scrape_from_db.py
```

### 7. Accéder à l'application

| Interface       | URL                               | Credentials   |
|-----------------|-----------------------------------|---------------|
| Application     | http://localhost:8080/login       | ton email/mdp |
| EasyAdmin       | http://localhost:8080/admin       | ton email/mdp |
| RabbitMQ UI     | http://localhost:15672            | guest / guest |
| Adminer (MySQL) | http://localhost:8081             | root / root   |

---

## Comment ça fonctionne — Vue d'ensemble

L'application repose sur quatre piliers :

1. **Scraping** : Un script Python scrape LinkedIn et envoie les offres vers le backend via une API REST.
2. **Traitement asynchrone** : Symfony ne traite pas les offres immédiatement — il les place dans une file RabbitMQ et répond instantanément. Un worker tourne en arrière-plan et consomme la file à son rythme.
3. **Persistance & administration** : Les offres validées sont enregistrées en MySQL et consultables via l'interface EasyAdmin.
4. **Scoring RAG** : 5 minutes après chaque scraping, un pipeline TF-IDF compare chaque nouvelle offre au CV et calcule un score de pertinence (0–100 %).

---

## Architecture détaillée

```
┌──────────────────────────────────────────────────┐
│  Container scraper (Python + cron)               │
│                                                  │
│  scrape_from_db.py  ← lit SearchQuery en MySQL   │
│       │  lance linkedin_job_search.py par query  │
│       │  POST /api/job-offers                    │
│       │                                          │
│  score_offers.py    ← 5 min après le scraping    │
│       │  lit job_offer WHERE match_score IS NULL │
│       │  pipeline TF-IDF (generer_lettre.py)     │
│       └─ écrit match_score, matched/missing kw   │
└───────┼──────────────────────────────────────────┘
        │
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
│  exchange: job_offers       │    (persistant, survit aux redémarrages)
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

## Scoring RAG — CV ↔ Offre (TF-IDF)

### Principe

À chaque cycle de scraping, le script `score_offers.py` analyse les nouvelles offres (celles dont `match_score IS NULL`) et leur attribue un score de pertinence par rapport au CV.

Le pipeline utilise **TF-IDF avec n-grammes** (importé depuis `generer_lettre.py`) :

1. **Extraction des mots-clés** de l'offre (`title + company + location + description`) via TF-IDF
2. **Construction d'un index** des segments du CV
3. **Association** : chaque mot-clé de l'offre est comparé aux segments CV (seuil cosinus 0.22)
4. **Score final** : `100 × mots-clés couverts / total mots-clés`

Les résultats sont écrits en base sur trois champs :

| Champ               | Type    | Valeur typique                        |
|---------------------|---------|---------------------------------------|
| `match_score`       | float   | `42.5` (%)                            |
| `matched_keywords`  | JSON    | `["symfony", "drupal", "api rest"]`   |
| `missing_keywords`  | JSON    | `["solr", "graphql", "kubernetes"]`   |

### Fichiers requis dans `scripts/`

| Fichier              | Statut    | Description                              |
|----------------------|-----------|------------------------------------------|
| `generer_lettre.py`  | versionné | Pipeline TF-IDF (module RAG)             |
| `cv.txt`             | **.gitignore** | Votre CV en texte brut             |
| `score_offers.py`    | versionné | Script principal de scoring              |

> ⚠️ `cv.txt` contient des données personnelles — ne jamais le committer.

### Lancement manuel

```bash
# Dry-run : affiche les scores sans écrire en base
docker compose exec scraper python3 /app/score_offers.py --dry-run

# Scoring réel (toutes les offres non scorées)
docker compose exec scraper python3 /app/score_offers.py

# Avec un CV alternatif
docker compose exec scraper python3 /app/score_offers.py --cv /app/cv_freelance.txt
```

### Interface EasyAdmin

Dans EasyAdmin → **Job Offers** :
- Colonne **Score %** visible sur la liste, triable (tri par défaut : meilleur score en premier)
- Dans la **fiche détail** : liste des mots-clés couverts (✓) et manquants (✗)

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

| Champ               | Type                  | Description                              |
|---------------------|-----------------------|------------------------------------------|
| `id`                | int (auto)            | Identifiant unique                       |
| `title`             | string(255)           | Intitulé du poste                        |
| `company`           | string(255)           | Nom de l'entreprise                      |
| `location`          | string(255), nullable | Lieu du poste                            |
| `description`       | text, nullable        | Description complète                     |
| `url`               | string(500), nullable | Lien vers l'offre originale              |
| `status`            | string(50)            | État : `pending`, `processed`, `ignored` |
| `createdAt`         | DateTimeImmutable     | Date de création (set par le handler)    |
| `processedAt`       | DateTimeImmutable     | Date de traitement par le worker         |
| `matchScore`        | float, nullable       | Score TF-IDF CV↔offre (0–100 %)         |
| `matchedKeywords`   | json, nullable        | Mots-clés de l'offre couverts par le CV  |
| `missingKeywords`   | json, nullable        | Mots-clés de l'offre absents du CV       |

### `User` — `src/Entity/User.php`

Utilisateur de l'application (authentification Symfony Security).

| Champ            | Type              | Description                            |
|------------------|-------------------|----------------------------------------|
| `id`             | int (auto)        | Identifiant unique                     |
| `email`          | string(180)       | Email — identifiant de connexion       |
| `roles`          | json              | Tableau de rôles (`ROLE_USER`, etc.)   |
| `password`       | string            | Mot de passe hashé (bcrypt)            |
| `searchQueries`  | OneToMany         | Requêtes de recherche de l'utilisateur |

### `SearchQuery` — `src/Entity/SearchQuery.php`

Requête de recherche enregistrée par un utilisateur. Le cron s'en sert pour lancer les scrapings automatiques 3x/jour. Chaque `SearchQuery` active génère une exécution du scraper LinkedIn avec ses paramètres.

| Champ       | Type              | Description                               |
|-------------|-------------------|-------------------------------------------|
| `id`        | int (auto)        | Identifiant unique                        |
| `keyword`   | string(255)       | Mot-clé de recherche (ex: `drupal`)       |
| `location`  | string(255), null | Lieu (ex: `Switzerland`)                  |
| `distance`  | integer, null     | Rayon en miles (10, 25, 50, 100)          |
| `isActive`  | boolean           | Si `false`, le cron ignore cette requête  |
| `createdAt` | DateTimeImmutable | Date de création (auto via constructeur)  |
| `user`      | ManyToOne → User  | Utilisateur propriétaire                  |

---

## Contrôleurs

### `JobOfferController` — `src/Controller/JobOfferController.php`

Point d'entrée de l'API REST. Accessible sans authentification (`/api` est public dans `security.yaml`).

| Route                  | Méthode | Accès  | Description                                     |
|------------------------|---------|--------|-------------------------------------------------|
| `POST /api/job-offers` | POST    | PUBLIC | Reçoit les offres JSON, dispatche dans RabbitMQ |

### `SecurityController` — `src/Controller/SecurityController.php`

Généré par `make:security:form-login`. Gère la page de connexion.

| Route     | Méthode  | Description                        |
|-----------|----------|------------------------------------|
| `/login`  | GET/POST | Formulaire de connexion            |
| `/logout` | GET      | Déconnexion (géré par le firewall) |

### `DashboardController` — `src/Controller/Admin/DashboardController.php`

Tableau de bord EasyAdmin v5. Accessible uniquement aux utilisateurs connectés (`ROLE_USER`).

| Route    | Accès     | Description                |
|----------|-----------|----------------------------|
| `/admin` | ROLE_USER | Interface d'administration |

### `JobOfferCrudController` — `src/Controller/Admin/JobOfferCrudController.php`

CRUD EasyAdmin pour les offres d'emploi : liste, détail, édition, suppression. Affiche la colonne **Score %** triable et les mots-clés couverts/manquants dans la fiche détail.

### `SearchQueryCrudController` — `src/Controller/Admin/SearchQueryCrudController.php`

CRUD EasyAdmin pour les requêtes de recherche. Permet à l'admin de créer, activer/désactiver et supprimer des recherches planifiées. Les requêtes avec `isActive = true` sont automatiquement récupérées par le cron.

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

## Commande Symfony — Debug des recherches actives

### `AppScrapeActiveQueriesCommand` — `src/Command/AppScrapeActiveQueriesCommand.php`

Commande de debug : affiche quelles recherches actives seraient lancées et génère les commandes Python correspondantes. Utile pour vérifier que les `SearchQuery` sont bien lues depuis la base sans avoir à attendre le cron.

```bash
docker compose exec php bin/console app:scrape-active-queries
```

Exemple de sortie :

```
[INFO] 1 recherche(s) active(s) trouvée(s).
Lancement : python /app/linkedin_job_search.py 'drupal' --location 'Switzerland' --backend http://nginx/api/job-offers
[OK] Scraping terminé.
```

> Note : `python: not found` est normal dans le container PHP. Cette commande sert uniquement à vérifier la logique. Le vrai scraping est effectué par le container `scraper`.

---

## Authentification (Symfony Security)

La sécurité est configurée dans `config/packages/security.yaml`.

**Règles d'accès :**

| Chemin      | Accès requis |
|-------------|--------------|
| `/login`    | PUBLIC       |
| `/api/**`   | PUBLIC       |
| `/admin/**` | `ROLE_USER`  |
| `/**`       | `ROLE_USER`  |

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

### `scripts/linkedin_job_search.py` — Scraper de base

Scrape LinkedIn pour un mot-clé donné et envoie les résultats au backend. Utilisable manuellement ou appelé par `scrape_from_db.py`.

```bash
cd scripts/
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt

# Recherche + envoi automatique au backend
python linkedin_job_search.py drupal --location "Switzerland" --results 5

# Scraping seul, sans envoi au backend (mode debug)
python linkedin_job_search.py drupal --no-send
```

| Argument     | Défaut                                 | Description                         |
|--------------|----------------------------------------|-------------------------------------|
| `keyword`    | —                                      | Mot-clé de recherche (obligatoire)  |
| `--location` | `Switzerland`                          | Lieu de recherche                   |
| `--results`  | `5`                                    | Nombre d'offres à récupérer         |
| `--distance` | —                                      | Rayon en miles (10 / 25 / 50 / 100) |
| `--backend`  | `http://localhost:8080/api/job-offers` | URL du contrôleur Symfony           |
| `--no-send`  | —                                      | Scraping seul, sans envoi backend   |

### `scripts/scrape_from_db.py` — Orchestrateur du cron

Script appelé par le cron 3x/jour. Se connecte directement à MySQL, récupère toutes les `SearchQuery` où `isActive = true`, et lance `linkedin_job_search.py` pour chacune avec ses paramètres.

```python
# Flux simplifié
queries = SELECT * FROM search_query WHERE is_active = 1
for query in queries:
    subprocess.run(['python3', 'linkedin_job_search.py', query.keyword, ...])
```

Variables d'environnement supportées (avec valeurs par défaut Docker) :

| Variable      | Défaut                          |
|---------------|---------------------------------|
| `DB_HOST`     | `mysql`                         |
| `DB_USER`     | `root`                          |
| `DB_PASS`     | `root`                          |
| `DB_NAME`     | `job_scraper`                   |
| `BACKEND_URL` | `http://nginx/api/job-offers`   |

Pour tester manuellement :

```bash
docker compose exec scraper python3 /app/scrape_from_db.py
```

### `scripts/score_offers.py` — Scoring RAG CV↔offre

Script appelé automatiquement 5 minutes après chaque scraping. Lit les offres sans score (`match_score IS NULL`), calcule la pertinence via le pipeline TF-IDF de `generer_lettre.py`, et écrit les résultats en base.

```bash
# Dry-run : affiche les scores sans écrire en base
docker compose exec scraper python3 /app/score_offers.py --dry-run

# Scoring réel
docker compose exec scraper python3 /app/score_offers.py
```

Variables d'environnement supportées :

| Variable   | Défaut         | Description              |
|------------|----------------|--------------------------|
| `DB_HOST`  | `mysql`        | Hôte MySQL               |
| `DB_USER`  | `root`         | Utilisateur MySQL        |
| `DB_PASS`  | `root`         | Mot de passe MySQL       |
| `DB_NAME`  | `job_scraper`  | Base de données          |
| `CV_PATH`  | `/app/cv.txt`  | Chemin vers le CV texte  |

### `scripts/generer_lettre.py` — Module RAG (TF-IDF)

Module Python contenant le pipeline de traitement linguistique : découpage du CV en segments, extraction de mots-clés par TF-IDF, indexation et calcul de similarité cosinus. Importé par `score_offers.py`.

> Ce fichier est versionné car il contient uniquement la logique algorithmique, sans données personnelles.

---

## Cron automatique (container Docker)

Le service `scraper` dans `docker-compose.yml` est un container Python avec `cron` intégré. Il tourne en arrière-plan et exécute les scripts 3x/jour selon la planification dans `docker/scraper/crontab`.

**Planification par défaut :**

```
0 8,13,18 * * * root python3 /app/scrape_from_db.py >> /var/log/scraper.log 2>&1
5 8,13,18 * * * root python3 /app/score_offers.py   >> /var/log/scraper.log 2>&1
```

- **8h00 / 13h00 / 18h00** — scraping LinkedIn
- **8h05 / 13h05 / 18h05** — scoring RAG des nouvelles offres

Pour modifier la fréquence, édite `docker/scraper/crontab` et rebuilde :

```bash
docker compose build scraper
docker compose up -d scraper
```

> ⚠️ Le fichier `crontab` doit se terminer par une ligne vide, sinon cron refuse de l'installer.

---

## Structure du projet

```
script_job_offer/
├── backend/                              # Application Symfony 7.4
│   ├── src/
│   │   ├── Command/
│   │   │   └── AppScrapeActiveQueriesCommand.php  # Debug : liste les queries actives
│   │   ├── Controller/
│   │   │   ├── Admin/
│   │   │   │   ├── DashboardController.php         # Tableau de bord EasyAdmin
│   │   │   │   ├── JobOfferCrudController.php      # CRUD offres + colonne Score %
│   │   │   │   └── SearchQueryCrudController.php   # CRUD recherches planifiées
│   │   │   ├── JobOfferController.php              # API REST POST /api/job-offers
│   │   │   └── SecurityController.php              # Login / Logout
│   │   ├── Entity/
│   │   │   ├── JobOffer.php                        # Offre d'emploi (+ matchScore, keywords)
│   │   │   ├── User.php                            # Utilisateur (auth)
│   │   │   └── SearchQuery.php                     # Requête de recherche (cron)
│   │   ├── Message/
│   │   │   └── JobOfferMessage.php                 # DTO transporté dans RabbitMQ
│   │   ├── MessageHandler/
│   │   │   └── JobOfferMessageHandler.php          # Worker : consomme la file
│   │   ├── Repository/
│   │   │   ├── JobOfferRepository.php
│   │   │   ├── UserRepository.php
│   │   │   └── SearchQueryRepository.php
│   │   └── Service/
│   │       └── JobOfferService.php                 # Dispatch des messages
│   ├── config/
│   │   └── packages/
│   │       ├── messenger.yaml                      # Config RabbitMQ transport
│   │       └── security.yaml                       # Firewall, access_control
│   └── migrations/
│       └── Version20261017*.php                    # Migration : matchScore + keywords
├── docker/
│   ├── php/Dockerfile                              # PHP 8.3 + intl + amqp
│   ├── nginx/default.conf
│   └── scraper/
│       ├── Dockerfile                              # Python + cron + pymysql + scikit-learn
│       └── crontab                                 # scraping 8h/13h/18h + scoring +5min
├── scripts/
│   ├── linkedin_job_search.py                      # Scraper LinkedIn (argparse)
│   ├── scrape_from_db.py                           # Orchestrateur cron → MySQL → scraper
│   ├── score_offers.py                             # Scoring RAG CV↔offre (TF-IDF)
│   ├── generer_lettre.py                           # Pipeline TF-IDF (module RAG)
│   ├── cv.txt                                      # ← PERSONNEL, non versionné (.gitignore)
│   └── requirements.txt                            # pymysql, numpy, scikit-learn, requests
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
# Lancer le worker manuellement
docker compose exec php bin/console messenger:consume async -vv

# Voir les logs du worker
docker compose logs -f worker

# Voir les logs du cron scraper
docker compose logs -f scraper

# Lancer le scraping immédiatement (sans attendre le cron)
docker compose exec scraper python3 /app/scrape_from_db.py

# Lancer le scoring immédiatement (toutes les offres non scorées)
docker compose exec scraper python3 /app/score_offers.py

# Scorer sans écrire en base (dry-run)
docker compose exec scraper python3 /app/score_offers.py --dry-run

# Rescorer toutes les offres (réinitialise les scores en base, puis relance)
docker compose exec mysql mysql -uroot -proot job_scraper \
  -e "UPDATE job_offer SET match_score=NULL, matched_keywords=NULL, missing_keywords=NULL;"
docker compose exec scraper python3 /app/score_offers.py

# Vérifier quelles SearchQuery seraient lancées (debug PHP)
docker compose exec php bin/console app:scrape-active-queries

# Voir les offres avec leur score
docker compose exec mysql mysql -uroot -proot job_scraper \
  -e "SELECT id, title, company, match_score FROM job_offer ORDER BY match_score DESC LIMIT 10;"

# Vider le cache Symfony
docker compose exec php bin/console cache:clear

# Voir toutes les routes
docker compose exec php bin/console debug:router

# Rebuilder le container scraper (après modif requirements.txt ou crontab)
docker compose build scraper && docker compose up -d scraper
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

Variables du container scraper (dans `docker-compose.yml`) :

```env
DB_HOST=mysql
DB_USER=root
DB_PASS=root
DB_NAME=job_scraper
CV_PATH=/app/cv.txt
```

---

## Auteur

**Baher Rais** — [github.com/baherdev](https://github.com/baherdev)  
Freelancemodule Sàrl — Orbe, Suisse