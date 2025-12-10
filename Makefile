# Makefile für Raumbuchungssystem Docker

.PHONY: help up down restart logs shell composer install clean

help: ## Zeigt diese Hilfe an
	@echo "Verfügbare Befehle:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'

up: ## Startet die Container
	docker-compose up -d
	@echo ""
	@echo "✓ Container gestartet!"
	@echo "→ Öffne http://localhost:8080"

down: ## Stoppt und entfernt die Container
	docker-compose down

restart: ## Startet die Container neu
	docker-compose restart
	@echo "✓ Container neu gestartet!"

logs: ## Zeigt die Logs an
	docker-compose logs -f

shell: ## Öffnet eine Shell im Container
	docker exec -it bpc-buchung-web bash

composer: ## Installiert Composer-Abhängigkeiten
	docker-compose run --rm composer install

install: ## Erste Installation (DB + Container + Composer)
	@echo "→ Erstelle Datenbank..."
	@mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS buchung CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" || true
	@echo "→ Importiere Schema..."
	@mysql -u root -p buchung < database/schema.sql || true
	@echo "→ Starte Container..."
	@make up
	@echo ""
	@echo "✓ Installation abgeschlossen!"
	@echo "→ Öffne http://localhost:8080/raeume.php"

clean: ## Entfernt Container, Volumes und temporäre Dateien
	docker-compose down -v
	rm -rf logs/* uploads/*
	@echo "✓ Bereinigung abgeschlossen!"

status: ## Zeigt den Status der Container
	docker-compose ps

update: ## Aktualisiert Composer-Abhängigkeiten
	docker-compose run --rm composer update

db-import: ## Importiert das Datenbankschema (erfordert MySQL-Passwort)
	mysql -u root -p buchung < database/schema.sql
	@echo "✓ Schema importiert!"

db-update: ## Aktualisiert Raumdaten in der Datenbank
	mysql -u root -p buchung < database/update_rooms.sql
	@echo "✓ Raumdaten aktualisiert!"
