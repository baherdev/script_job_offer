FROM python:3.11-slim

RUN apt-get update && apt-get install -y cron curl && apt-get clean

WORKDIR /app

COPY scripts/requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY scripts/ .

# Copie le crontab
COPY docker/scraper/crontab /etc/cron.d/scraper
RUN chmod 0644 /etc/cron.d/scraper && crontab /etc/cron.d/scraper

# Crée le fichier de log
RUN touch /var/log/scraper.log

CMD ["cron", "-f"]
