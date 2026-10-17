#!/usr/bin/env python3
"""
Génère une lettre de motivation pour une offre LinkedIn via Ollama (deepseek-r1:7b).
Usage : python3 generer_lettre.py --offer-id 42
"""
import argparse, json, os, sys
import pymysql
import requests
from cv_matcher import decouper, extraire_mots_cles, IndexCV, associer, lire

DB_HOST    = os.getenv("DB_HOST", "mysql")
DB_USER    = os.getenv("DB_USER", "root")
DB_PASS    = os.getenv("DB_PASS", "root")
DB_NAME    = os.getenv("DB_NAME", "job_scraper")
CV_PATH    = os.getenv("CV_PATH", "/app/cv.txt")
OLLAMA_URL = os.getenv("OLLAMA_URL", "http://host.docker.internal:11434/api/generate")
MODEL      = os.getenv("OLLAMA_MODEL", "deepseek-r1:7b")

def get_offer(conn, offer_id):
    with conn.cursor(pymysql.cursors.DictCursor) as cur:
        cur.execute("SELECT * FROM job_offer WHERE id = %s", (offer_id,))
        return cur.fetchone()

def build_prompt(cv_text, offer, matched, missing):
    return f"""Tu es un expert en rédaction de lettres de motivation professionnelles.

CV du candidat :
{cv_text}

Offre d'emploi :
Poste : {offer['title']}
Entreprise : {offer['company']}
Lieu : {offer.get('location', '')}
Description : {(offer.get('description', '') or '')[:2000]}

Mots-clés du CV qui correspondent à l'offre : {', '.join(matched)}
Mots-clés de l'offre non couverts par le CV : {', '.join(missing)}

Rédige une lettre de motivation professionnelle en français, structurée en 4 paragraphes :
1. Accroche avec le poste et l'entreprise
2. Compétences du CV qui correspondent à l'offre
3. Motivation et valeur ajoutée pour l'entreprise
4. Formule de politesse

Lettre :"""

def call_ollama(prompt, model):
    try:
        resp = requests.post(OLLAMA_URL, json={
            "model": model,
            "prompt": prompt,
            "stream": False
        }, timeout=300)
        resp.raise_for_status()
        data = resp.json()
        # Supprimer le bloc <think>...</think> de deepseek-r1
        text = data.get("response", "")
        if "<think>" in text:
            text = text.split("</think>")[-1].strip()
        return text
    except requests.exceptions.ConnectionError:
        print(f"ERREUR : Impossible de joindre Ollama sur {OLLAMA_URL}", file=sys.stderr)
        print("Vérifie qu'Ollama tourne sur ta machine et que OLLAMA_URL est correct.", file=sys.stderr)
        sys.exit(1)

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--offer-id", type=int, required=True, help="ID de l'offre en base")
    ap.add_argument("--cv", default=CV_PATH)
    ap.add_argument("--model", default=MODEL)
    args = ap.parse_args()

    if not os.path.exists(args.cv):
        print(f"ERREUR : CV introuvable : {args.cv}", file=sys.stderr)
        sys.exit(1)

    cv_text = lire(args.cv)
    cv_segments = decouper(cv_text)

    conn = pymysql.connect(host=DB_HOST, user=DB_USER, password=DB_PASS, database=DB_NAME)
    offer = get_offer(conn, args.offer_id)
    conn.close()

    if not offer:
        print(f"ERREUR : Offre {args.offer_id} introuvable.", file=sys.stderr)
        sys.exit(1)

    # Recalculer les mots-clés si pas encore scorée
    matched = offer.get("matched_keywords") or []
    missing = offer.get("missing_keywords") or []
    if isinstance(matched, str):
        matched = json.loads(matched)
    if isinstance(missing, str):
        missing = json.loads(missing)

    if not matched and not missing:
        texte_offre = "\n".join(filter(None, [offer.get("title",""), offer.get("description","") or ""]))
        mots_cles = extraire_mots_cles(texte_offre, n=25)
        index = IndexCV(cv_segments)
        correspondances = associer(index, mots_cles, seuil=0.22)
        matched = [c.mot_cle for c in correspondances if c.couvert]
        missing = [c.mot_cle for c in correspondances if not c.couvert]

    print(f"\n{'='*60}")
    print(f"  {offer['title']} — {offer['company']}")
    print(f"  Score : {offer.get('match_score') or '(non scorée)'} %")
    print(f"{'='*60}\n")
    print("Génération en cours avec", args.model, "...\n")

    prompt = build_prompt(cv_text, offer, matched, missing)
    lettre = call_ollama(prompt, args.model)
    
    print(lettre)

if __name__ == "__main__":
    main()
