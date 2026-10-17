import subprocess
import os

BACKEND_URL = os.getenv('BACKEND_URL', 'http://nginx/api/job-offers')

if __name__ == '__main__':
    cmd = ['python3', '/app/linkedin_job_search.py', '--backend', BACKEND_URL]
    print(f"Lancement : {' '.join(cmd)}")
    subprocess.run(cmd)
    print("Scraping terminé.")
