# 🔍 Job Scraper — Backend Symfony 7.4

Scraping d'offres d'emploi LinkedIn avec un backend Symfony 7.4, file de messages RabbitMQ, persistance MySQL et interface d'administration EasyAdmin.

---

## Prérequis

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) installé et démarré
- Git
- Python 3.10+ *(uniquement pour les scripts de scraping)*

---

## Installation

### 1. Cloner le repo

```bash
git clone git@github.com:baherdev/script_job_offer.git
cd script_job_offer
```

### 2. Créer le fichier d'environnement

```bash
cp backend/.env backend/.env.local
```

Le fichier `.env.local` est déjà pré-configuré pour Docker. Aucune modification nécessaire en local.

### 3. Construire et démarrer les containers

```bash
docker compose up -d --build
```

> ⏳ La première fois, le build prend 2–3 minutes.
> MySQL met ~1 minute à démarrer. Attends que tous les services soient `healthy` avant de continuer.

Vérifie l'état des services :

```bash
docker compose ps
```

### 4. Créer la base de données et lancer les migrations

```bash
docker compose exec php bin/console doctrine:database:create
docker compose exec php bin/console doctrine:migrations:migrate
```

### C'est prêt ✅

---

## Accès

| Interface        | URL                             | Credentials   |
|------------------|---------------------------------|---------------|
| API              | http://localhost:8080           | —             |
| EasyAdmin        | http://localhost:8080/admin     | —             |
| RabbitMQ UI      | http://localhost:15672          | guest / guest |
| Adminer (MySQL)  | http://localhost:8081           | root / root   |

---

## Tester l'API

```bash
curl -X POST http://localhost:8080/api/job-offers \
  -H "Content-Type: application/json" \
  -d '{
    "offers": [
      {
        "title": "Développeur Symfony Senior",
        "company": "Acme Corp",
        "location": "Genève, Suisse",
        "description": "Poste senior Symfony 7...",
        "url": "https://example.com/job/123"
      }
    ]
  }'
```

Réponse attendue **(HTTP 202)** :

```json
{ "message": "1 offer(s) dispatched to the queue.", "dispatched": 1 }
```

L'offre est visible dans EasyAdmin après quelques secondes (le worker la consomme et la persiste en base).

---

## Scripts Python — Scraping LinkedIn

```bash
cd scripts/
python3 -m venv venv
source venv/bin/activate        # Windows : venv\Scripts\activate
pip install -r requirements.txt
```

```bash
# Recherche + envoi automatique au backend
python linkedin_job_search.py drupal --location "Switzerland" --results 5

# Scraping seul, sans envoi au backend
python linkedin_job_search.py drupal --no-send
```

| Argument     | Défaut                                 | Description                        |
|--------------|----------------------------------------|------------------------------------|
| `keyword`    | `drupal`                               | Mot-clé de recherche               |
| `--location` | `Switzerland`                          | Lieu de recherche                  |
| `--results`  | `5`                                    | Nombre d'offres                    |
| `--distance` | —                                      | Rayon en miles (10 / 25 / 50 / 100)|
| `--backend`  | `http://localhost:8080/api/job-offers` | URL du backend                     |
| `--no-send`  | —                                      | Scraping seul, sans envoi          |

---

## Arrêter le projet

```bash
docker compose down
```

Pour tout supprimer (base de données incluse) :

```bash
docker compose down -v
```

---

## Stack technique

| Composant  | Version        |
|------------|----------------|
| PHP        | 8.3-FPM        |
| Symfony    | 7.4 LTS        |
| MySQL      | 8.0            |
| RabbitMQ   | 3 (management) |
| Nginx      | alpine         |
| EasyAdmin  | 5.x            |

---

## Architecture