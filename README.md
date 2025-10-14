# R2AS Vote

Application de votes en ligne sans inscription. Création rapide d’un vote, ajout de propositions, invitation des votants, dépôt des votes, consultation des résultats.

## Fonctionnalités

- Création d’un vote (nom, description, mail d’admin).
- Ajout de propositions (Pour / Contre / Abstention).
- Invitation de votants (individuel et import CSV), pondération des voix.
- Vote par lien personnel (Pour, Contre, Abstention).
- Résultats agrégés par proposition.
- Mise à jour en temps réel via Mercure (notification et mise à jour des compteurs en direct).

## Prérequis

- Docker et docker-compose.
- Ou PHP 8.2+, Composer, extensions requises et un serveur web.

## Démarrage rapide (Docker)

1. Variables par défaut (.env déjà fourni) adaptées à docker-compose:

   - DATABASE_URL: SQLite dans `var/app.db`.
   - MAILER_DSN: à définir dans `.env.local` selon votre fournisseur (Mailgun, SMTP externe, etc.).
   - MAIL_FROM: adresse d’envoi par défaut.
   - SENTRY_DSN: optionnel, renseigner pour activer Sentry.
   - Mercure

2. Lancer:

   - `docker-compose up --build`

3. Ouvrir l’application:

   - http://localhost:8080

## Configuration

### Variables d’environnement principales

- DATABASE_URL
  - Par défaut (SQLite): `sqlite:///%kernel.project_dir%/var/app.db`
- MAILER_DSN
  - Exemple Mailgun: `mailgun://KEY:DOMAIN@default?region=us`
- MAIL_FROM
  - Adresse d’expédition par défaut des emails (ex: `no-reply@r2as.org`).
- SENTRY_DSN (optionnel)
  - Active l’envoi d’erreurs, logs et traces à Sentry (dev et prod).
- Mercure (realtime)
  - MERCURE_PUBLIC_URL: URL côté navigateur (dev: `http://localhost:8080/.well-known/mercure`).
  - MERCURE_URL: URL interne côté PHP (dev: `http://localhost/.well-known/mercure`).
  - MERCURE_JWT_SECRET: secret de signature JWT du hub.

### Caddy / FrankenPHP

En production, désactiver `anonymous` et activer `subscriber_jwt {$MERCURE_JWT_SECRET}` puis fournir un JWT abonné côté client.

## Licence

Logiciel libre publié sous licence WTFPLv2.
