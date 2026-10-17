#!/usr/bin/env python3
"""
score_offers.py — Score RAG des offres LinkedIn par rapport au CV.

Lit les offres sans score (match_score IS NULL) depuis MySQL,
calcule la couverture CV ↔ offre avec le même pipeline TF-IDF que
generer_lettre.py, et écrit match_score / matched_keywords / missing_keywords
en base.

Usage :
  python3 score_offers.py                 # lit /app/cv.txt par défaut
  python3 score_offers.py --cv cv.txt     # chemin personnalisé
  python3 score_offers.py --dry-run       # affiche sans écrire en base
  CV_PATH=/app/cv.txt python3 score_offers.py
"""

import argparse
import json
import os
import sys

import pymysql

# Import du pipeline depuis generer_lettre.py (même répertoire /app/)
try:
    from generer_lettre import decouper, extraire_mots_cles, IndexCV, associer, lire
except ImportError:
    print("ERREUR : generer_lettre.py introuvable dans le répertoire courant.", file=sys.stderr)
    sys.exit(1)

# ── Connexion MySQL ────────────────────────────────────────────────────────── #
DB_HOST = os.getenv("DB_HOST", "mysql")
DB_USER = os.getenv("DB_USER", "root")
DB_PASS = os.getenv("DB_PASS", "root")
DB_NAME = os.getenv("DB_NAME", "job_scraper")

DEFAULT_CV_PATH = os.getenv("CV_PATH", "/app/cv.txt")


# ── Requêtes MySQL ─────────────────────────────────────────────────────────── #
def get_unscored_offers(conn):
    with conn.cursor(pymysql.cursors.DictCursor) as cur:
        cur.execute(
            """
            SELECT id, title, company, location, description
            FROM job_offer
            WHERE match_score IS NULL
            ORDER BY created_at DESC
            """
        )
        return cur.fetchall()


def save_score(conn, offer_id, score, matched, missing):
    with conn.cursor() as cur:
        cur.execute(
            """
            UPDATE job_offer
            SET match_score       = %s,
                matched_keywords  = %s,
                missing_keywords  = %s
            WHERE id = %s
            """,
            (
                round(score, 2),
                json.dumps(matched, ensure_ascii=False),
                json.dumps(missing, ensure_ascii=False),
                offer_id,
            ),
        )
    conn.commit()


# ── Pipeline de scoring ────────────────────────────────────────────────────── #
def score_offer(cv_segments, offer):
    """
    Retourne (score_pct: float, matched: list[str], missing: list[str]).
    score_pct est un pourcentage (0–100).
    """
    texte_offre = "\n".join(
        filter(
            None,
            [
                offer.get("title", ""),
                offer.get("company", ""),
                offer.get("location", ""),
                offer.get("description", "") or "",
            ],
        )
    )

    if not texte_offre.strip():
        return 0.0, [], []

    mots_cles = extraire_mots_cles(texte_offre, n=25)
    if not mots_cles:
        return 0.0, [], []

    index = IndexCV(cv_segments)
    correspondances = associer(index, mots_cles, seuil=0.22)

    matched = [c.mot_cle for c in correspondances if c.couvert]
    missing = [c.mot_cle for c in correspondances if not c.couvert]
    score_pct = 100.0 * len(matched) / max(1, len(correspondances))

    return score_pct, matched, missing


# ── Main ───────────────────────────────────────────────────────────────────── #
def main():
    ap = argparse.ArgumentParser(description="Score RAG des offres LinkedIn vs CV")
    ap.add_argument("--cv", default=DEFAULT_CV_PATH, help="Chemin vers cv.txt")
    ap.add_argument(
        "--dry-run",
        action="store_true",
        help="Affiche les scores sans écrire en base",
    )
    args = ap.parse_args()

    # Lecture du CV
    if not os.path.exists(args.cv):
        print(f"ERREUR : CV introuvable : {args.cv}", file=sys.stderr)
        print(
            "Copiez votre cv.txt dans le projet (scripts/cv.txt) et montez-le en volume,",
            file=sys.stderr,
        )
        print(
            "ou passez CV_PATH=/chemin/cv.txt ou --cv /chemin/cv.txt.",
            file=sys.stderr,
        )
        sys.exit(1)

    cv_texte = lire(args.cv)
    cv_segments = decouper(cv_texte)
    print(f"CV : {len(cv_segments)} segments indexés  ({args.cv})")

    conn = pymysql.connect(
        host=DB_HOST, user=DB_USER, password=DB_PASS, database=DB_NAME
    )
    offers = get_unscored_offers(conn)

    if not offers:
        print("Aucune offre à scorer.")
        conn.close()
        return

    print(f"{len(offers)} offre(s) à scorer…\n")
    for offer in offers:
        score, matched, missing = score_offer(cv_segments, offer)
        label = f"{offer['title'][:45]:<45} | {offer['company'] or '?'}"
        bar = "█" * int(score / 5) + "░" * (20 - int(score / 5))
        print(
            f"  [{offer['id']:>4}] {label}  {bar} {score:5.1f}%  "
            f"({len(matched)}/{len(matched)+len(missing)} mots-clés)"
        )
        if args.dry_run:
            if matched:
                print(f"         ✓ {', '.join(matched[:8])}" + (" …" if len(matched) > 8 else ""))
            if missing:
                print(f"         ✗ {', '.join(missing[:8])}" + (" …" if len(missing) > 8 else ""))
        else:
            save_score(conn, offer["id"], score, matched, missing)

    conn.close()
    if args.dry_run:
        print("\n[dry-run] Aucune écriture en base.")
    else:
        print("\nScoring terminé.")


if __name__ == "__main__":
    main()
