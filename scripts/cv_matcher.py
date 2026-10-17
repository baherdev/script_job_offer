#!/usr/bin/env python3
"""
Génère une lettre de motivation à partir d'un CV (texte) et d'une offre d'emploi (texte).

Pipeline :
  1. Extraction des mots-clés de l'offre (TF-IDF n-grammes + détection de termes techniques)
  2. RAG : le CV est découpé en segments indexés ; pour chaque mot-clé on récupère
     les passages du CV qui le prouvent (similarité cosinus TF-IDF caractères)
  3. Construction d'un prompt ancré (mots-clés couverts + preuves, mots-clés non couverts)
  4. Requête HTTP vers une LLM externe (Anthropic ou toute API compatible OpenAI :
     OpenAI, Mistral, Groq, Ollama, LM Studio...)

Usage :
  python generer_lettre.py cv.txt offre.txt -o lettre.txt
  python generer_lettre.py cv.txt offre.txt --dry-run          # sans appel API
  python generer_lettre.py cv.txt offre.txt --provider openai --model gpt-4o-mini
  python generer_lettre.py cv.txt offre.txt --provider openai \
         --base-url http://localhost:11434/v1 --model llama3.1   # Ollama local

Clés API (variables d'environnement) : ANTHROPIC_API_KEY ou OPENAI_API_KEY
(ou --api-key).
"""

from __future__ import annotations

import argparse
import json
import os
import re
import sys
from dataclasses import dataclass, field
from pathlib import Path

import numpy as np
import requests
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity

# --------------------------------------------------------------------------- #
# Stopwords FR + EN + bruit typique des offres d'emploi
# --------------------------------------------------------------------------- #
STOPWORDS = set("""
a à au aux avec ce ces cet cette dans de des du elle en et être eux il ils je la le les leur
leurs lui ma mais me même mes moi mon ne nos notre nous on ou où par pas pour qu que qui sa se
ses son sur ta te tes toi ton tu un une vos votre vous y d l n s t c j m qu est sont sera
afin ainsi alors aussi autre autres avoir bien car chez comme dont donc entre fait faire
hors ici leur lors moins notamment peu plus plusieurs selon sans sous tout tous toute toutes
très via vers déjà etc ayant étant être
the and or of to in for on with by at from as is are be an this that these those it its
we you our your will would can could should may must have has had not but if than then
also such into over under about within across all any each other more most very
poste mission missions profil candidat candidate candidature offre recherchons recherche
nous rejoindre rejoignez équipe team job role position company entreprise société
souhaité souhaitée requis requise idéalement atout appréciée apprécié capable capacité
bonne bonnes bon bons excellent excellente maîtrise connaissance connaissances
expérience experience years ans année années minimum min jour jours
travailler concevoir participer maintenir développer intégrer assurer gérer contribuer
mettre place sein cadre tiers services service sites site amélioration chaîne senior junior
work working develop maintain ensure manage participate build strong good knowledge plus
""".split())

# Termes techniques : contiennent chiffres, majuscules internes, ., #, +, / (ex: PHP8, Node.js, C#, CI/CD)
TECH_RE = re.compile(r"\b(?:[A-Za-z]+[0-9+#./][A-Za-z0-9+#./]*|[A-Z][a-z]+[A-Z]\w*|[A-Z]{2,}[A-Za-z0-9]*)\b")


# --------------------------------------------------------------------------- #
# Lecture / découpage
# --------------------------------------------------------------------------- #
def lire(path: str) -> str:
    p = Path(path)
    for enc in ("utf-8", "utf-8-sig", "latin-1"):
        try:
            return p.read_text(encoding=enc)
        except UnicodeDecodeError:
            continue
    raise ValueError(f"Impossible de décoder {path}")


def decouper(texte: str, taille: int = 350, chevauchement: int = 80) -> list[str]:
    """Découpe le CV en segments : d'abord par blocs (lignes vides), puis par fenêtre."""
    blocs = [b.strip() for b in re.split(r"\n\s*\n", texte) if b.strip()]
    segments: list[str] = []
    for bloc in blocs:
        bloc = re.sub(r"[ \t]+", " ", bloc)
        if len(bloc) <= taille:
            segments.append(bloc)
            continue
        debut = 0
        while debut < len(bloc):
            fin = min(len(bloc), debut + taille)
            # couper proprement sur une fin de ligne / phrase si possible
            coupe = max(bloc.rfind("\n", debut, fin), bloc.rfind(". ", debut, fin))
            if fin < len(bloc) and coupe > debut + taille // 2:
                fin = coupe + 1
            segments.append(bloc[debut:fin].strip())
            if fin >= len(bloc):
                break
            debut = max(fin - chevauchement, debut + 1)
    return [s for s in segments if len(s) > 15]


# --------------------------------------------------------------------------- #
# 1. Extraction des mots-clés de l'offre
# --------------------------------------------------------------------------- #
def extraire_mots_cles(offre: str, n: int = 25) -> list[str]:
    phrases = [p.strip() for p in re.split(r"[\n.;•\-–]\s*", offre) if len(p.strip()) > 3]
    if len(phrases) < 2:
        phrases = [offre]

    vec = TfidfVectorizer(
        ngram_range=(1, 3),
        stop_words=list(STOPWORDS),
        token_pattern=r"(?u)\b[\w][\w+#./-]*[\w+#]|\b\w\b",
        lowercase=True,
        sublinear_tf=True,
    )
    X = vec.fit_transform(phrases)
    # score = somme TF-IDF * fréquence documentaire (un terme répété dans l'offre est important)
    scores = np.asarray(X.sum(axis=0)).ravel() * np.log1p((X > 0).sum(axis=0).A1)
    termes = vec.get_feature_names_out()

    candidats: dict[str, float] = {}
    for t, s in zip(termes, scores):
        if len(t) < 2 or any(tok.isdigit() for tok in t.split()):
            continue
        candidats[t] = float(s)

    # bonus termes techniques
    for m in TECH_RE.findall(offre):
        k = m.lower().strip("./")
        if len(k) >= 2 and k not in STOPWORDS and not k.isdigit():
            candidats[k] = candidats.get(k, 0.0) + 1.5

    tries = sorted(candidats.items(), key=lambda kv: kv[1], reverse=True)

    # dédoublonnage : on écarte un n-gramme inclus dans un n-gramme déjà retenu (et vice-versa)
    racine = lambda x: re.split(r"[./]", x)[0]
    retenus: list[str] = []
    for terme, _ in tries:
        if any(racine(terme) == racine(r) for r in retenus):  # vue / vue.js
            continue
        if any(terme in r.split() or terme == r for r in retenus):
            continue
        if any(r in terme.split() for r in retenus) and len(terme.split()) > 1:
            continue
        retenus.append(terme)
        if len(retenus) >= n:
            break
    return retenus


# --------------------------------------------------------------------------- #
# 2. RAG sur le CV
# --------------------------------------------------------------------------- #
@dataclass
class Correspondance:
    mot_cle: str
    score: float
    passages: list[str] = field(default_factory=list)

    @property
    def couvert(self) -> bool:
        return bool(self.passages)


class IndexCV:
    """Index vectoriel du CV (TF-IDF n-grammes de caractères : robuste aux flexions, accents, casse)."""

    def __init__(self, segments: list[str]):
        self.segments = segments
        self.vec = TfidfVectorizer(analyzer="char_wb", ngram_range=(3, 5), lowercase=True, sublinear_tf=True)
        self.M = self.vec.fit_transform(segments)

    def rechercher(self, requete: str, k: int = 3) -> list[tuple[int, float]]:
        q = self.vec.transform([requete])
        sims = cosine_similarity(q, self.M).ravel()
        idx = np.argsort(-sims)[:k]
        return [(int(i), float(sims[i])) for i in idx]


def normaliser(s: str) -> str:
    import unicodedata
    s = unicodedata.normalize("NFD", s.lower())
    return "".join(c for c in s if unicodedata.category(c) != "Mn")


def associer(index: IndexCV, mots_cles: list[str], seuil: float = 0.22, k: int = 2) -> list[Correspondance]:
    resultats = []
    for mc in mots_cles:
        mc_n = normaliser(mc)
        hits = index.rechercher(mc, k=k + 2)
        passages, meilleur = [], 0.0
        for i, s in hits:
            seg = index.segments[i]
            litteral = re.search(rf"(?<!\w){re.escape(mc_n)}(?!\w)", normaliser(seg)) is not None
            if litteral or s >= seuil:
                passages.append(seg)
                meilleur = max(meilleur, s + (0.5 if litteral else 0))
            if len(passages) >= k:
                break
        resultats.append(Correspondance(mc, round(meilleur, 3), passages))
    return resultats


# --------------------------------------------------------------------------- #
# 3. Construction du prompt
# --------------------------------------------------------------------------- #
LANGUES = {"fr": "français", "en": "anglais", "de": "allemand", "it": "italien"}


def construire_prompt(cv: str, offre: str, corr: list[Correspondance], langue: str, mots: int, ton: str,
                      infos: str) -> tuple[str, str]:
    couverts = [c for c in corr if c.couvert]
    manquants = [c.mot_cle for c in corr if not c.couvert]

    # passages de preuve dédoublonnés
    preuves: dict[str, list[str]] = {}
    for c in couverts:
        for p in c.passages:
            preuves.setdefault(p, []).append(c.mot_cle)

    bloc_preuves = "\n\n".join(
        f"[Passage {i + 1} | mots-clés : {', '.join(ks)}]\n{p}" for i, (p, ks) in enumerate(preuves.items())
    )

    systeme = (
        "Tu es un expert en recrutement et en rédaction de lettres de motivation. "
        "Tu écris des lettres concrètes, sobres et personnalisées, sans formules creuses. "
        "Règle absolue : tu n'inventes AUCUNE expérience, compétence, diplôme ou chiffre "
        "absent du CV fourni."
    )

    utilisateur = f"""Rédige une lettre de motivation en {LANGUES.get(langue, langue)}, d'environ {mots} mots, ton {ton}.

## Offre d'emploi
{offre.strip()}

## Mots-clés de l'offre couverts par le CV (à intégrer naturellement)
{', '.join(c.mot_cle for c in couverts) or '(aucun)'}

## Passages du CV qui prouvent ces compétences (récupérés par RAG — appuie-toi dessus)
{bloc_preuves or '(aucun)'}

## Mots-clés de l'offre NON trouvés dans le CV
{', '.join(manquants) or '(aucun)'}
→ Ne prétends pas les maîtriser. Si pertinent, mentionne une compétence proche réellement présente
dans le CV ou une volonté d'apprendre, sans exagérer.

## CV complet (référence factuelle)
{cv.strip()}
{f'''
## Informations complémentaires du candidat
{infos.strip()}''' if infos else ''}

## Consignes
- Structure : accroche liée au poste/à l'organisation, 2-3 paragraphes reliant expériences concrètes
  et besoins de l'offre, conclusion avec demande d'entretien.
- Reprends le vocabulaire de l'offre là où le CV le justifie.
- Pas de liste à puces, pas de titres markdown.
- Commence directement par la formule d'appel (ex. « Madame, Monsieur, ») et termine par
  une formule de politesse et le nom du candidat tel qu'il figure dans le CV.
- Ne renvoie QUE le texte de la lettre.
"""
    return systeme, utilisateur


# --------------------------------------------------------------------------- #
# 4. Appel API LLM externe
# --------------------------------------------------------------------------- #
def appeler_llm(provider: str, model: str, systeme: str, utilisateur: str, api_key: str | None,
                base_url: str | None, temperature: float, max_tokens: int, timeout: int = 600,
                num_ctx: int = 8192) -> str:
    if provider == "ollama":
        # API native Ollama : permet de fixer num_ctx (sinon le prompt est tronqué à 2-4k tokens)
        url = (base_url or "http://localhost:11434").rstrip("/") + "/api/chat"
        try:
            r = requests.post(
                url,
                json={
                    "model": model,
                    "stream": False,
                    "messages": [{"role": "system", "content": systeme}, {"role": "user", "content": utilisateur}],
                    # num_predict large : les modèles de raisonnement consomment des tokens dans <think>
                    "options": {"temperature": temperature, "num_ctx": num_ctx, "num_predict": max_tokens * 3},
                },
                timeout=timeout,
            )
        except requests.ConnectionError:
            raise SystemExit(f"Ollama injoignable sur {url} — lance `ollama serve` dans un autre terminal.")
        _verifier(r)
        return nettoyer_reponse(r.json()["message"]["content"])

    if provider == "anthropic":
        key = api_key or os.environ.get("ANTHROPIC_API_KEY")
        if not key:
            raise SystemExit("ANTHROPIC_API_KEY manquante (ou --api-key).")
        url = (base_url or "https://api.anthropic.com").rstrip("/") + "/v1/messages"
        r = requests.post(
            url,
            headers={"x-api-key": key, "anthropic-version": "2023-06-01", "content-type": "application/json"},
            json={
                "model": model,
                "max_tokens": max_tokens,
                "temperature": temperature,
                "system": systeme,
                "messages": [{"role": "user", "content": utilisateur}],
            },
            timeout=timeout,
        )
        _verifier(r)
        return "".join(b.get("text", "") for b in r.json().get("content", []) if b.get("type") == "text").strip()

    # API compatible OpenAI (OpenAI, Mistral, Groq, Ollama, LM Studio, vLLM...)
    key = api_key or os.environ.get("OPENAI_API_KEY") or "local"
    url = (base_url or "https://api.openai.com/v1").rstrip("/") + "/chat/completions"
    r = requests.post(
        url,
        headers={"Authorization": f"Bearer {key}", "Content-Type": "application/json"},
        json={
            "model": model,
            "temperature": temperature,
            "max_tokens": max_tokens,
            "messages": [{"role": "system", "content": systeme}, {"role": "user", "content": utilisateur}],
        },
        timeout=timeout,
    )
    _verifier(r)
    return nettoyer_reponse(r.json()["choices"][0]["message"]["content"])


def nettoyer_reponse(texte: str) -> str:
    """Retire le raisonnement <think>…</think> des modèles type DeepSeek-R1 / Qwen3."""
    texte = re.sub(r"<think>.*?</think>", "", texte, flags=re.S)
    texte = re.sub(r"^.*?</think>", "", texte, flags=re.S)  # balise ouvrante parfois absente
    return texte.strip()


def _verifier(r: requests.Response) -> None:
    if r.status_code >= 400:
        try:
            detail = json.dumps(r.json(), ensure_ascii=False)
        except ValueError:
            detail = r.text[:500]
        raise SystemExit(f"Erreur API {r.status_code} : {detail}")


# --------------------------------------------------------------------------- #
# CLI
# --------------------------------------------------------------------------- #
MODELES_DEFAUT = {"anthropic": "claude-sonnet-5-5", "openai": "gpt-4o-mini", "ollama": "deepseek-r1:7b"}


def main() -> None:
    ap = argparse.ArgumentParser(description="Lettre de motivation : CV + offre → RAG → LLM")
    ap.add_argument("cv", help="Fichier texte du CV")
    ap.add_argument("offre", help="Fichier texte de l'offre d'emploi")
    ap.add_argument("-o", "--output", help="Fichier de sortie de la lettre (défaut : stdout)")
    ap.add_argument("--provider", choices=["anthropic", "openai", "ollama"],
                    default=os.environ.get("LLM_PROVIDER", "anthropic"),
                    help="anthropic, ollama (local), ou openai = toute API compatible OpenAI")
    ap.add_argument("--model", default=os.environ.get("LLM_MODEL"))
    ap.add_argument("--base-url", default=os.environ.get("LLM_BASE_URL"), help="URL de l'API (Mistral, Ollama...)")
    ap.add_argument("--api-key")
    ap.add_argument("--langue", default="fr", help="fr, en, de, it...")
    ap.add_argument("--mots", type=int, default=350, help="Longueur cible en mots")
    ap.add_argument("--ton", default="professionnel et chaleureux")
    ap.add_argument("--infos", help="Fichier texte d'infos complémentaires (motivation perso, disponibilité...)")
    ap.add_argument("--nb-mots-cles", type=int, default=25)
    ap.add_argument("--seuil", type=float, default=0.22, help="Seuil de similarité RAG (0-1)")
    ap.add_argument("--temperature", type=float, default=0.5)
    ap.add_argument("--max-tokens", type=int, default=1500)
    ap.add_argument("--num-ctx", type=int, default=8192, help="Fenêtre de contexte Ollama (tokens)")
    ap.add_argument("--dry-run", action="store_true", help="Affiche mots-clés et prompt sans appeler l'API")
    ap.add_argument("--rapport", help="Écrit un rapport JSON (mots-clés, couverture, passages)")
    a = ap.parse_args()

    cv, offre = lire(a.cv), lire(a.offre)
    infos = lire(a.infos) if a.infos else ""

    # 1. mots-clés
    mots_cles = extraire_mots_cles(offre, a.nb_mots_cles)
    # 2. RAG
    index = IndexCV(decouper(cv))
    corr = associer(index, mots_cles, seuil=a.seuil)
    couverts = sum(c.couvert for c in corr)

    print(f"→ {len(index.segments)} segments CV indexés, {len(mots_cles)} mots-clés extraits, "
          f"{couverts}/{len(corr)} couverts par le CV ({100 * couverts // max(1, len(corr))} %)", file=sys.stderr)
    for c in corr:
        print(f"   {'✓' if c.couvert else '✗'} {c.mot_cle:<35} {c.score:.2f}", file=sys.stderr)

    if a.rapport:
        Path(a.rapport).write_text(json.dumps(
            [{"mot_cle": c.mot_cle, "couvert": c.couvert, "score": c.score, "passages": c.passages} for c in corr],
            ensure_ascii=False, indent=2), encoding="utf-8")

    # 3. prompt
    systeme, utilisateur = construire_prompt(cv, offre, corr, a.langue, a.mots, a.ton, infos)

    if a.dry_run:
        print("\n===== SYSTEM =====\n" + systeme + "\n\n===== USER =====\n" + utilisateur)
        return

    # 4. LLM
    model = a.model or MODELES_DEFAUT[a.provider]
    print(f"→ Appel {a.provider} ({model})…", file=sys.stderr)
    lettre = appeler_llm(a.provider, model, systeme, utilisateur, a.api_key, a.base_url, a.temperature, a.max_tokens,
                         num_ctx=a.num_ctx)

    if a.output:
        Path(a.output).write_text(lettre + "\n", encoding="utf-8")
        print(f"✓ Lettre écrite dans {a.output}", file=sys.stderr)
    else:
        print(lettre)


if __name__ == "__main__":
    main()
