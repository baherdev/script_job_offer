#!/usr/bin/env python3
"""
Lit la première offre sans description et extrait le texte LinkedIn
depuis la page LinkedIn.
"""

import argparse
import requests
from bs4 import BeautifulSoup
from typing import Dict, Optional
from urllib.parse import urlparse, urlunparse


class LinkedInJobDescriptionFetcher:
    def __init__(self):
        self.headers = {
            'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language': 'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
        }

    def fetch_first_offer(self, backend_url: str) -> Optional[Dict]:
        print(f"📍 Endpoint: {backend_url}")

        try:
            response = requests.get(backend_url, timeout=15)
        except requests.RequestException as e:
            print(f"❌ Impossible de joindre le backend: {e}")
            return None

        print(f"✓ Statut HTTP: {response.status_code}")
        if response.status_code != 200:
            print(f"❌ Backend HTTP {response.status_code}: {response.text}")
            return None

        offers = response.json()
        if not isinstance(offers, list) or not offers:
            print("ℹ️  Aucune offre sans description.")
            return None

        offer = offers[0]
        print(f"✅ Première offre: [{offer.get('id')}] {offer.get('title')}")
        print(f"    🏢 {offer.get('company')}")
        print(f"    🔗 {offer.get('url')}\n")
        return offer

    def extract_description(self, job_url: str) -> Optional[str]:
        if not job_url:
            print("❌ L'offre n'a pas d'URL.")
            return None

        try:
            response = requests.get(job_url, headers=self.headers, timeout=15)
        except requests.RequestException as e:
            print(f"❌ Erreur réseau: {e}")
            return None

        print(f"✓ Page LinkedIn HTTP: {response.status_code}")
        if response.status_code != 200:
            print(f"❌ Erreur HTTP {response.status_code}")
            return None

        soup = BeautifulSoup(response.text, 'html.parser')
        description_div = soup.select_one('div[id^="JobDetails_AboutTheJob"]')
        if description_div is None:
            description_div = soup.select_one('div.description__text .show-more-less-html__markup')
        if description_div is None:
            print("❌ Description introuvable sur la page LinkedIn.")
            return None

        description = description_div.get_text("\n", strip=True)
        if not description:
            print("❌ La description LinkedIn est vide.")
            return None

        print(f"✓ Description extraite ({len(description)} caractères)\n")
        print(description)
        return description

    def update_description(self, update_url: str, offer_id: int, description: str) -> bool:
        print(f"\n📍 PUT {update_url}")
        try:
            response = requests.put(
                update_url,
                json={'id': offer_id, 'description': description},
                timeout=15,
            )
        except requests.RequestException as e:
            print(f"❌ Impossible de joindre le backend: {e}")
            return False

        if response.status_code != 200:
            print(f"❌ Backend HTTP {response.status_code}: {response.text}")
            return False

        print(f"✅ Description enregistrée pour l'offre [{offer_id}]")
        return True


def job_offers_update_url(backend_url: str) -> str:
    parsed = urlparse(backend_url)
    return urlunparse(parsed._replace(path='/api/job-offers', query='', fragment=''))


def main():
    parser = argparse.ArgumentParser(description='Extrait la description LinkedIn de la première offre sans description')
    parser.add_argument(
        '--backend',
        default='http://nginx/api/job-offers/without-description',
        help='URL de l\'endpoint JSON des offres sans description',
    )
    args = parser.parse_args()

    fetcher = LinkedInJobDescriptionFetcher()
    offer = fetcher.fetch_first_offer(args.backend)
    if offer is None:
        return

    description = fetcher.extract_description(offer.get('url'))
    if description is None or offer.get('id') is None:
        return

    fetcher.update_description(job_offers_update_url(args.backend), offer['id'], description)


if __name__ == "__main__":
    main()
