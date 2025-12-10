#!/bin/bash

# Stop-Skript für Raumbuchungssystem Docker Container

echo "Stoppe Raumbuchungssystem Container..."

docker compose down

echo ""
echo "✓ Container gestoppt und entfernt"
echo ""
echo "Zum erneuten Starten:"
echo "  ./start.sh"
echo "  oder"
echo "  docker compose up -d"
