#!/bin/bash
set -e

# Générer la config nginx avec le bon port (injecté par Render)
envsubst '${PORT}' < /etc/nginx/nginx.conf.template > /etc/nginx/sites-enabled/default

# Lance Reverb en arrière-plan (websocket, port interne 6001)
php artisan reverb:start --host=0.0.0.0 --port=6001 &
REVERB_PID=$!

# Lance le serveur PHP en arrière-plan (port interne 8000)
php artisan serve --host=0.0.0.0 --port=8000 &
WEB_PID=$!

# Lance nginx en arrière-plan (écoute sur le port public $PORT)
nginx -g 'daemon off;' &
NGINX_PID=$!

# Si l'un des trois process meurt, on arrête tout le conteneur
wait -n $REVERB_PID $WEB_PID $NGINX_PID
EXIT_CODE=$?
echo "Un des process s'est arrêté (code $EXIT_CODE), arrêt du conteneur."
kill $REVERB_PID $WEB_PID $NGINX_PID 2>/dev/null || true
exit $EXIT_CODE

