APP_NAME=Messenger
APP_ENV=local
APP_KEY=base64:ZuniY6FKAzTksropiQ3MB002gwCuh/kTUJ1QLZtWQ1s=
APP_DEBUG=true
APP_URL=http://localhost

APP_LOCALE=fr
APP_FALLBACK_LOCALE=fr
APP_FAKER_LOCALE=fr_FR

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# ─── Supabase PostgreSQL ───────────────────────────────────────────
DB_CONNECTION=pgsql
DB_HOST=aws-0-eu-west-3.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.momgzwojjhgvrsdkkkum
DB_PASSWORD=Messangerapp
DB_SCHEMA=public

# ─── Supabase Storage ─────────────────────────────────────────────
SUPABASE_URL=https://momgzwojjhgvrsdkkkum.supabase.co
SUPABASE_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im1vbWd6d29qamhndnJzZGtra3VtIiwicm9sZSI6ImFub24iLCJpYXQiOjE3Nzk5MzA1MzcsImV4cCI6MjA5NTUwNjUzN30.ZazATFbJbkG6zfJNAd0iVvr5NsqAt0jRZXEOuyqzFwI
SUPABASE_SECRET=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im1vbWd6d29qamhndnJzZGtra3VtIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc3OTkzMDUzNywiZXhwIjoyMDk1NTA2NTM3fQ._4kDxhseJ2NwcAbr3sTkCqZQJoUv5N8toJioEldIKp4
SUPABASE_BUCKET=messenger-files

# ─── Session / Queue / Cache ──────────────────────────────────────
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=reverb
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=database

# ─── Mail ─────────────────────────────────────────────────────────
MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

# ─── Reverb WebSocket ─────────────────────────────────────────────
REVERB_APP_ID=382245
REVERB_APP_KEY=7zpfzf9sawqbfelqhzfa
REVERB_APP_SECRET=quuqlxq5fcmllqenscoa
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

# ─── Vite (frontend) ──────────────────────────────────────────────
VITE_APP_NAME=Messenger
VITE_REVERB_APP_KEY=7zpfzf9sawqbfelqhzfa
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http