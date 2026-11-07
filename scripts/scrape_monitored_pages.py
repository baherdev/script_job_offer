#!/usr/bin/env python3
"""
Charge les pages surveillées depuis GET /api/monitored-pages,
télécharge chaque URL et calcule le SHA-256 du contenu,
puis renvoie url, hash et userId à POST /api/monitored-pages.
"""

import argparse
import hashlib
import requests
from typing import Dict, List, Optional


class MonitoredPageScraper:
    def __init__(self):
        self.headers = {
            'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language': 'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
        }

    def fetch_pages(self, backend_url: str) -> List[Dict]:
        print(f"📥 Pages surveillées: {backend_url}")

        try:
            response = requests.get(backend_url, timeout=15)
        except requests.RequestException as e:
            print(f"❌ Impossible de joindre l'API des pages surveillées: {e}")
            return []

        print(f"✓ Statut HTTP: {response.status_code}")
        if response.status_code != 200:
            print(f"❌ Erreur HTTP {response.status_code}: {response.text}")
            return []

        try:
            pages = response.json()
        except ValueError as e:
            print(f"❌ Réponse JSON invalide: {e}")
            return []

        if not isinstance(pages, list):
            print("❌ La réponse attendue est une liste de pages surveillées.")
            return []

        print(f"✓ {len(pages)} page(s) surveillée(s)\n")
        return pages

    def compute_hash(self, url: str) -> Optional[str]:
        print(f"🌐 {url}")

        try:
            response = requests.get(url, headers=self.headers, timeout=15)
        except requests.RequestException as e:
            print(f"❌ Erreur réseau: {e}\n")
            return None

        print(f"✓ Statut HTTP: {response.status_code}")
        if response.status_code != 200:
            print(f"❌ Erreur HTTP {response.status_code}\n")
            return None

        digest = hashlib.sha256(response.content).hexdigest()
        print(f"✓ Hash SHA-256: {digest} ({len(response.content)} octets)\n")
        return digest

    def send_pages(self, pages: List[Dict], backend_url: str):
        if not pages:
            print("ℹ️  Aucune page à envoyer.")
            return

        try:
            response = requests.post(backend_url, json={'monitoredPages': pages}, timeout=15)
        except requests.RequestException as e:
            print(f"❌ Impossible de joindre le backend: {e}")
            return

        if response.status_code == 202:
            data = response.json()
            print(f"✅ Backend: {data.get('message')}")
            return

        print(f"❌ Backend HTTP {response.status_code}: {response.text}")


def main():
    parser = argparse.ArgumentParser(description='Calcule le hash des pages surveillées')
    parser.add_argument('--backend', default='http://nginx/api/monitored-pages', help='URL de l\'API des pages surveillées')
    parser.add_argument('--no-send', action='store_true', help='Ne pas envoyer les hash au backend')
    args = parser.parse_args()

    scraper = MonitoredPageScraper()
    pages = scraper.fetch_pages(args.backend)
    if not pages:
        print("ℹ️  Aucune page surveillée.")
        return

    updates = []
    for page in pages:
        if not isinstance(page, dict):
            print(f"⚠️  Page ignorée : format inattendu ({page!r})")
            continue

        url = str(page.get('url') or '').strip()
        user_id = page.get('userId')
        if not url or not isinstance(user_id, int):
            print(f"⚠️  Page {page.get('id')} ignorée : url ou userId manquant.")
            continue

        digest = scraper.compute_hash(url)
        if digest is None:
            continue

        previous = page.get('hash')
        if previous == digest:
            print(f"ℹ️  Hash inchangé pour {url}\n")

        updates.append({
            'url': url,
            'hash': digest,
            'userId': user_id,
        })

    if args.no_send:
        print(f"ℹ️  Mode --no-send : {len(updates)} hash non envoyé(s).")
        return

    scraper.send_pages(updates, args.backend)


if __name__ == '__main__':
    main()
