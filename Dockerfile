FROM php:8.2-cli

# Dépendances système
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpq-dev \
    nginx \
    gettext-base \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Installer Node (pour compiler les assets si besoin)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

WORKDIR /app

# Variables nécessaires au moment du build Vite (injectées par Render via Build Args)
ARG VITE_APP_NAME
ARG VITE_REVERB_APP_KEY
ARG VITE_REVERB_HOST
ARG VITE_REVERB_PORT
ARG VITE_REVERB_SCHEME
ENV VITE_APP_NAME=$VITE_APP_NAME
ENV VITE_REVERB_APP_KEY=$VITE_REVERB_APP_KEY
ENV VITE_REVERB_HOST=$VITE_REVERB_HOST
ENV VITE_REVERB_PORT=$VITE_REVERB_PORT
ENV VITE_REVERB_SCHEME=$VITE_REVERB_SCHEME

# Copier le code
COPY . .
COPY start.sh ./start.sh
COPY nginx.conf.template /etc/nginx/nginx.conf.template

# Retirer la config nginx par défaut pour éviter les conflits
RUN rm -f /etc/nginx/sites-enabled/default

# Installer les dépendances PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Laravel a besoin d'une APP_KEY valide pour exécuter les commandes artisan
# pendant le build (ex: ziggy:generate). Cette clé est temporaire et sera
# remplacée par la vraie APP_KEY définie dans les Environment Variables de Render au runtime.
RUN touch .env && php artisan key:generate --force

# Générer le fichier Ziggy (routes JS) avant le build Vite
RUN php artisan ziggy:generate

# Installer et builder les assets JS (si applicable)
RUN if [ -f package.json ]; then npm install && npm run build; fi

# Permissions Laravel
RUN mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Script de démarrage (lance le serveur web ET Reverb)
RUN chmod +x start.sh

EXPOSE 8080

CMD ["./start.sh"]
