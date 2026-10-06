import subprocess
import pymysql
import os

DB_HOST = os.getenv('DB_HOST', 'mysql')
DB_USER = os.getenv('DB_USER', 'root')
DB_PASS = os.getenv('DB_PASS', 'root')
DB_NAME = os.getenv('DB_NAME', 'job_scraper')
BACKEND_URL = os.getenv('BACKEND_URL', 'http://nginx/api/job-offers')

def get_active_queries():
    conn = pymysql.connect(host=DB_HOST, user=DB_USER, password=DB_PASS, database=DB_NAME)
    cursor = conn.cursor(pymysql.cursors.DictCursor)
    cursor.execute("SELECT keyword, location, distance FROM search_query WHERE is_active = 1")
    rows = cursor.fetchall()
    conn.close()
    return rows

def run_scraper(query):
    cmd = ['python3', '/app/linkedin_job_search.py', query['keyword']]
    if query.get('location'):
        cmd += ['--location', query['location']]
    if query.get('distance'):
        cmd += ['--distance', str(query['distance'])]
    cmd += ['--backend', BACKEND_URL]
    print(f"Lancement : {' '.join(cmd)}")
    subprocess.run(cmd)

if __name__ == '__main__':
    queries = get_active_queries()
    if not queries:
        print("Aucune recherche active.")
    else:
        print(f"{len(queries)} recherche(s) active(s) trouvée(s).")
        for q in queries:
            run_scraper(q)
    print("Scraping terminé.")
