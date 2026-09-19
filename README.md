# 🔍 Job Search Scripts

Scripts Python pour rechercher des offres d'emploi automatiquement sur **Google** et **LinkedIn**.

## 📁 Scripts disponibles

| Script | Source | Méthode |
|--------|--------|---------|
| `job_search_simple.py` | Google | Selenium (navigateur réel) |
| `linkedin_job_search.py` | LinkedIn | Requests + BeautifulSoup |

---

## ⚙️ Installation

### 1. Cloner le repo

```bash
git clone git@github.com:baherdev/script_job_offer.git
cd script_job_offer
```

### 2. Créer un environnement virtuel

```bash
# Créer
python3 -m venv venv

# Activer (macOS / Linux / WSL)
source venv/bin/activate

# Activer (Windows CMD)
venv\Scripts\activate.bat

# Activer (Windows PowerShell)
venv\Scripts\Activate.ps1
```

### 3. Installer les dépendances

```bash
pip install requests beautifulsoup4 selenium webdriver-manager
```

---

## 🔎 Script 1 — Google (`job_search_simple.py`)

Ouvre un vrai navigateur Chrome via **Selenium** et effectue la recherche sur Google.

### Prérequis

- Google Chrome installé
- ChromeDriver géré automatiquement par `webdriver-manager`

### Utilisation

```bash
python job_search_simple.py
```

### Exemple

```
Mot-clé: drupal
Localisation: Suisse
Mode invisible (o/n): n
Garder navigateur ouvert (o/n): n
```

### Résultat

```
✅ [1] Drupal Développeur offres d'emploi en Suisse
    🔗 https://swissdevjobs.ch/fr/jobs/Drupal/all

✅ [2] 7 postes pour Drupal
    🔗 https://www.jobs.ch/fr/offres-emplois/?term=drupal

✅ [3] Drupal Software Developpers - 12 Months - Geneva
    🔗 https://www.michaelpage.ch/...
```

---

## 💼 Script 2 — LinkedIn (`linkedin_job_search.py`)

Scrape la **page publique LinkedIn Jobs** (sans compte requis) via Requests + BeautifulSoup.

### Utilisation

```bash
python linkedin_job_search.py
```

### Exemple

```
Mot-clé: drupal
Localisation: Switzerland
Nombre de résultats: 5
Rayon de recherche en miles (laisser vide = pas de limite): 25
```

### Résultat

```
[1] Senior Drupal/PHP-Entwickler:in (m/w/d)
    🏢 Entreprise : PROGRESSIVE digital
    📍 Lieu       : Allemagne
    📅 Date       : il y a 1 semaine
    🔗 Lien       : https://de.linkedin.com/jobs/view/...

[2] Marketing Technology Web Developer (Drupal & Marketing Automation)
    🏢 Entreprise : Utimaco
    📍 Lieu       : Düsseldorf, Allemagne
    ...
```

### Paramètre Distance (rayon)

LinkedIn utilise des miles en interne :

| Miles | ~Km | Usage |
|-------|-----|-------|
| 10 | 16 km | Ville uniquement |
| 25 | 40 km | Région proche |
| 50 | 80 km | Grande région |
| 100 | 160 km | Large périmètre |
| vide | — | Tout le pays |

---

## 🖥️ Compatibilité

| OS | Google Script | LinkedIn Script |
|----|--------------|-----------------|
| macOS | ✅ | ✅ |
| Linux | ✅ | ✅ |
| Windows | ✅ | ✅ |
| WSL | ✅ (mode headless) | ✅ |

> **WSL** : Pour le script Google, choisir le mode invisible (`o`) car il n'y a pas d'interface graphique.

---

## 📦 Dépendances

```
requests
beautifulsoup4
selenium
webdriver-manager
```

---

## 🗂️ Structure du projet

```
script_job_offer/
├── job_search_simple.py      # Recherche Google via Selenium
├── linkedin_job_search.py    # Recherche LinkedIn via Requests
├── README.md
└── venv/                     # Environnement virtuel (non versionné)
```

> ⚠️ Le dossier `venv/` ne doit pas être commité. Ajouter un `.gitignore` :

```
venv/
__pycache__/
*.pyc
*.json
```

---

## 👤 Auteur

**Baher** — [github.com/baherdev](https://github.com/baherdev)