#!/usr/bin/env python3
"""
Recherche d'emploi sur LinkedIn (page publique, sans compte)
Utilise requests + BeautifulSoup, pas besoin de Selenium
"""

import requests
from bs4 import BeautifulSoup
import urllib.parse
import json
import time
from typing import List, Dict


class LinkedInJobSearcher:
    def __init__(self):
        self.headers = {
            'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language': 'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
        }
        self.base_url = "https://www.linkedin.com/jobs/search/"

    def search_jobs(self, keyword: str, location: str = "Switzerland", num_results: int = 5, distance: int = None) -> List[Dict]:
        """
        Cherche des offres d'emploi sur LinkedIn (page publique)
        
        Args:
            keyword: Mot-clé de recherche (ex: drupal, symfony)
            location: Localisation (ex: Switzerland, Germany, Geneva)
            num_results: Nombre de résultats
            distance: Rayon de recherche en miles (ex: 10, 25, 50, 100)
                      None = pas de limite (toute la région)
        """
        params = {
            'keywords': keyword,
            'location': location,
            'start': 0
        }
        
        # Ajouter le rayon si spécifié
        if distance:
            params['distance'] = distance

        url = f"{self.base_url}?{urllib.parse.urlencode(params)}"
        print(f"🔍 Recherche LinkedIn: {keyword} @ {location}")
        print(f"📍 URL: {url}\n")

        results = []

        try:
            response = requests.get(url, headers=self.headers, timeout=15)
            print(f"✓ Statut HTTP: {response.status_code}")

            if response.status_code != 200:
                print(f"❌ Erreur HTTP {response.status_code}")
                return []

            soup = BeautifulSoup(response.text, 'html.parser')

            # Trouver les cartes d'offres d'emploi
            job_cards = soup.find_all('div', class_='base-card')

            if not job_cards:
                job_cards = soup.find_all('li', class_='jobs-search__results-list')

            if not job_cards:
                job_cards = soup.find_all('div', class_='job-search-card')

            print(f"✓ {len(job_cards)} offres trouvées\n")
            print("📝 Extraction des données...\n")

            for idx, card in enumerate(job_cards[:num_results], 1):
                try:
                    # Titre
                    title_elem = (
                        card.find('h3', class_='base-search-card__title') or
                        card.find('h3') or
                        card.find('h2')
                    )
                    title = title_elem.text.strip() if title_elem else "Pas de titre"

                    # Entreprise
                    company_elem = (
                        card.find('h4', class_='base-search-card__subtitle') or
                        card.find('a', class_='hidden-nested-link') or
                        card.find('h4')
                    )
                    company = company_elem.text.strip() if company_elem else "N/A"

                    # Localisation
                    location_elem = (
                        card.find('span', class_='job-search-card__location') or
                        card.find('span', class_='base-search-card__metadata')
                    )
                    job_location = location_elem.text.strip() if location_elem else "N/A"

                    # URL
                    link_elem = card.find('a', class_='base-card__full-link') or card.find('a', href=True)
                    job_url = link_elem['href'] if link_elem and 'href' in link_elem.attrs else "N/A"
                    # Nettoyer l'URL (enlever les paramètres de tracking)
                    if '?' in job_url:
                        job_url = job_url.split('?')[0]

                    # Date
                    date_elem = card.find('time')
                    date = date_elem.text.strip() if date_elem else "N/A"

                    results.append({
                        'position': idx,
                        'title': title,
                        'company': company,
                        'location': job_location,
                        'date': date,
                        'url': job_url
                    })

                    print(f"✅ [{idx}] {title}")
                    print(f"    🏢 {company} | 📍 {job_location} | 📅 {date}")
                    print(f"    🔗 {job_url[:80]}")
                    print()

                except Exception as e:
                    print(f"⚠️  Erreur parsing offre {idx}: {e}")
                    continue

            print(f"{'='*60}")
            print(f"✅ {len(results)} offres extraites")
            print(f"{'='*60}\n")

        except requests.RequestException as e:
            print(f"❌ Erreur réseau: {e}")

        return results

    def display_results(self, results: List[Dict]):
        """Affiche les résultats de manière formatée"""
        if not results:
            print("❌ Aucun résultat trouvé.")
            return

        print("\n" + "="*80)
        title_header = "OFFRES D'EMPLOI LINKEDIN"
        print(f"{title_header:^80}")
        print("="*80 + "\n")

        for job in results:
            print(f"[{job['position']}] {job['title']}")
            print(f"    🏢 Entreprise : {job['company']}")
            print(f"    📍 Lieu       : {job['location']}")
            print(f"    📅 Date       : {job['date']}")
            print(f"    🔗 Lien       : {job['url']}")
            print("-" * 80 + "\n")

    def export_json(self, results: List[Dict], keyword: str):
        """Exporte les résultats en JSON"""
        filename = f"jobs_linkedin_{keyword.replace(' ', '_')}.json"
        with open(filename, 'w', encoding='utf-8') as f:
            json.dump(results, f, ensure_ascii=False, indent=2)
        print(f"✓ Résultats exportés dans {filename}")


def main():
    print("="*60)
    print("RECHERCHE D'EMPLOI - LinkedIn")
    print("="*60)

    keyword = input("\nMot-clé (ex: drupal, symfony, php): ").strip()
    if not keyword:
        keyword = "drupal"
        print(f"Mot-clé par défaut: {keyword}")

    location = input("Localisation (ex: Switzerland, Germany, France): ").strip()
    if not location:
        location = "Switzerland"

    num = input("Nombre de résultats (défaut: 5): ").strip()
    num_results = int(num) if num.isdigit() else 5

    dist = input("Rayon de recherche en miles (ex: 10, 25, 50 — laisser vide = pas de limite): ").strip()
    distance = int(dist) if dist.isdigit() else None

    if distance:
        print(f"\n📍 Rayon: {distance} miles (~{round(distance * 1.6)} km)")

    print()
    searcher = LinkedInJobSearcher()
    results = searcher.search_jobs(keyword, location, num_results, distance)
    searcher.display_results(results)

    if results:
        export = input("Exporter en JSON? (o/n): ").strip().lower()
        if export == 'o':
            searcher.export_json(results, keyword)


if __name__ == "__main__":
    main()