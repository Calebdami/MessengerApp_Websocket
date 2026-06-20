#!/bin/bash

echo "=== Démarrage du conteneur ==="
echo "PORT=$PORT"

# Générer la config nginx avec le bon port (injecté par Render)
echo "--- Génération de la config nginx ---"
envsubst '${PORT}' < /etc/nginx/nginx.conf.template > /etc/nginx/sites-enabled/default
cat /etc/nginx/sites-enabled/default
echo "--- Test de la config nginx ---"
nginx -t

# Lance Reverb en arrière-plan (websocket, port interne 6001)
echo "--- Lancement de Reverb ---"
php artisan reverb:start --host=0.0.0.0 --port=6001 &
REVERB_PID=$!
sleep 2
echo "Reverb PID: $REVERB_PID"

# Lance le serveur PHP en arrière-plan (port interne 8000)
echo "--- Lancement du serveur PHP ---"
php artisan serve --host=0.0.0.0 --port=8000 &
WEB_PID=$!
sleep 2
echo "PHP serve PID: $WEB_PID"

# Lance nginx au premier plan (écoute sur le port public $PORT)
echo "--- Lancement de nginx ---"
nginx -g 'daemon off;' &
NGINX_PID=$!
sleep 2
echo "Nginx PID: $NGINX_PID"

echo "=== Tous les process sont lancés, en attente ==="

# Si l'un des trois process meurt, on arrête tout le conteneur
wait -n $REVERB_PID $WEB_PID $NGINX_PID
EXIT_CODE=$?
echo "Un des process s'est arrêté (code $EXIT_CODE), arrêt du conteneur."
kill $REVERB_PID $WEB_PID $NGINX_PID 2>/dev/null
exit $EXIT_CODE