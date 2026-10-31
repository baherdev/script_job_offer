#!/usr/bin/env python3
"""
Lit la première offre sans description et extrait le texte LinkedIn
depuis la page LinkedIn.
"""

import argparse
import html
import re
import requests
from bs4 import BeautifulSoup, Tag
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

    def extract_description(self, job_url: str, company: Optional[str] = None) -> tuple[Optional[str], Optional[str]]:
        if not job_url:
            print("❌ L'offre n'a pas d'URL.")
            return None, None

        try:
            response = requests.get(job_url, headers=self.headers, timeout=15)
        except requests.RequestException as e:
            print(f"❌ Erreur réseau: {e}")
            return None, None

        print(f"✓ Page LinkedIn HTTP: {response.status_code}")
        if response.status_code != 200:
            print(f"❌ Erreur HTTP {response.status_code}")
            return None, None

        soup = BeautifulSoup(response.text, 'html.parser')
        logo_url = self.log_company_logo(response.text, company)
        description_div = soup.select_one('div[id^="JobDetails_AboutTheJob"]')
        if description_div is None:
            description_div = soup.select_one('div.description__text .show-more-less-html__markup')
        if description_div is None:
            print("❌ Description introuvable sur la page LinkedIn.")
            return None, logo_url

        description = description_div.get_text("\n", strip=True)
        if not description:
            print("❌ La description LinkedIn est vide.")
            return None, logo_url

        print(f"✓ Description extraite ({len(description)} caractères)\n")
        print(description)
        return description, logo_url

    def log_company_logo(self, page_html: str, company: Optional[str]) -> Optional[str]:
        company_name = (company or "").strip()
        expected_alt = f"Company logo for, {company_name}."
        document = self.whole_document(page_html)
        soup = BeautifulSoup(document, "html.parser")

        logo = soup.find("img", alt=expected_alt)
        src = self.image_url(logo)
        if not src:
            src = self.logo_src_from_markup(document, expected_alt)
        if not src and company_name:
            src = self.company_logo_src(soup, company_name)

        if not src:
            print(f"ℹ️  Logo introuvable (alt=\"{expected_alt}\")")
            return None

        print(f"🖼️  Logo: {src}")
        return src

    def whole_document(self, page_html: str) -> str:
        decoded = (
            page_html.replace("\\u003c", "<")
            .replace("\\u003e", ">")
            .replace("\\u0026", "&")
            .replace("\\u0022", '"')
            .replace("\\/", "/")
            .replace('\\"', '"')
        )
        return html.unescape(decoded)

    def image_url(self, image: Optional[Tag]) -> str:
        if image is None:
            return ""
        return (image.get("src") or image.get("data-delayed-url") or "").strip()

    def logo_src_from_markup(self, document: str, expected_alt: str) -> str:
        for match in re.finditer(r"<img\b[^>]*>", document, flags=re.IGNORECASE):
            tag = match.group(0)
            alt_match = re.search(r"\balt\s*=\s*([\"'])(.*?)\1", tag, flags=re.IGNORECASE | re.DOTALL)
            if alt_match is None or html.unescape(alt_match.group(2)).strip() != expected_alt:
                continue
            src_match = re.search(
                r"\b(?:src|data-delayed-url)\s*=\s*([\"'])(.*?)\1",
                tag,
                flags=re.IGNORECASE | re.DOTALL,
            )
            if src_match is not None:
                return html.unescape(src_match.group(2)).strip()
        return ""

    def company_logo_src(self, soup: BeautifulSoup, company_name: str) -> str:
        for image in soup.find_all("img"):
            alt = (image.get("alt") or "").strip()
            src = self.image_url(image)
            if alt == f"Company logo for, {company_name}." and src:
                return src
            if alt == company_name and "company-logo" in src:
                return src
        return ""

    def update_description(self, update_url: str, offer_id: int, description: str, logo_url: Optional[str] = None) -> bool:
        payload = {'id': offer_id, 'description': description}
        if logo_url:
            payload['logo'] = logo_url

        print(f"\n📍 PUT {update_url}")
        try:
            response = requests.put(update_url, json=payload, timeout=15)
        except requests.RequestException as e:
            print(f"❌ Impossible de joindre le backend: {e}")
            return False

        if response.status_code != 202:
            print(f"❌ Backend HTTP {response.status_code}: {response.text}")
            return False

        print(f"✅ Mise à jour envoyée dans la file pour l'offre [{offer_id}]")
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

    description, logo_url = fetcher.extract_description(offer.get('url'), offer.get('company'))
    if description is None or offer.get('id') is None:
        return

    fetcher.update_description(job_offers_update_url(args.backend), offer['id'], description, logo_url)


if __name__ == "__main__":
    main()
