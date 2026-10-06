#!/usr/bin/env python3
"""
Recherche d'emploi sur LinkedIn (page publique, sans compte)
Utilise requests + BeautifulSoup
"""

import requests
from bs4 import BeautifulSoup
import urllib.parse
import json
import time
import argparse
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
        params = {
            'keywords': keyword,
            'location': location,
            'start': 0
        }
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
            job_cards = soup.find_all('div', class_='base-card')
            if not job_cards:
                job_cards = soup.find_all('div', class_='job-search-card')

            print(f"✓ {len(job_cards)} offres trouvées\n")

            for idx, card in enumerate(job_cards[:num_results], 1):
                try:
                    title_elem = card.find('h3', class_='base-search-card__title') or card.find('h3') or card.find('h2')
                    title = title_elem.text.strip() if title_elem else "Pas de titre"

                    company_elem = card.find('h4', class_='base-search-card__subtitle') or card.find('a', class_='hidden-nested-link') or card.find('h4')
                    company = company_elem.text.strip() if company_elem else "N/A"

                    location_elem = card.find('span', class_='job-search-card__location') or card.find('span', class_='base-search-card__metadata')
                    job_location = location_elem.text.strip() if location_elem else "N/A"

                    link_elem = card.find('a', class_='base-card__full-link') or card.find('a', href=True)
                    job_url = link_elem['href'] if link_elem and 'href' in link_elem.attrs else "N/A"
                    if '?' in job_url:
                        job_url = job_url.split('?')[0]

                    date_elem = card.find('time')
                    date = date_elem.text.strip() if date_elem else "N/A"

                    results.append({
                        'title': title,
                        'company': company,
                        'location': job_location,
                        'date': date,
                        'url': job_url
                    })

                    print(f"✅ [{idx}] {title}")
                    print(f"    🏢 {company} | 📍 {job_location} | 📅 {date}")
                    print(f"    🔗 {job_url[:80]}\n")

                except Exception as e:
                    print(f"⚠️  Erreur parsing offre {idx}: {e}")
                    continue

            print(f"✅ {len(results)} offres extraites\n")

        except requests.RequestException as e:
            print(f"❌ Erreur réseau: {e}")

        return results

    def send_to_backend(self, results: List[Dict], backend_url: str):
        if not results:
            print("ℹ️  Aucune offre à envoyer.")
            return

        payload = {"offers": results}
        try:
            response = requests.post(backend_url, json=payload, timeout=10)
            if response.status_code == 202:
                data = response.json()
                print(f"✅ Backend: {data.get('message')}")
            else:
                print(f"❌ Backend HTTP {response.status_code}: {response.text}")
        except requests.RequestException as e:
            print(f"❌ Impossible de joindre le backend: {e}")

    def export_json(self, results: List[Dict], keyword: str):
        filename = f"jobs_linkedin_{keyword.replace(' ', '_')}.json"
        with open(filename, 'w', encoding='utf-8') as f:
            json.dump(results, f, ensure_ascii=False, indent=2)
        print(f"✓ Résultats exportés dans {filename}")


def main():
    parser = argparse.ArgumentParser(description='Scraper LinkedIn Jobs')
    parser.add_argument('keyword', nargs='?', default='drupal', help='Mot-clé de recherche')
    parser.add_argument('--location', default='Switzerland', help='Localisation')
    parser.add_argument('--results', type=int, default=5, help='Nombre de résultats')
    parser.add_argument('--distance', type=int, default=None, help='Rayon en miles')
    parser.add_argument('--backend', default='http://nginx/api/job-offers', help='URL du backend Symfony')
    parser.add_argument('--no-send', action='store_true', help='Ne pas envoyer au backend')
    args = parser.parse_args()

    searcher = LinkedInJobSearcher()
    results = searcher.search_jobs(args.keyword, args.location, args.results, args.distance)

    if not args.no_send:
        searcher.send_to_backend(results, args.backend)
    else:
        print("ℹ️  Mode --no-send : résultats non envoyés au backend.")


if __name__ == "__main__":
    main()
