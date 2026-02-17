# Guide Docker - Omersia (Makefile First)

Ce projet est piloté via le `Makefile`.

Commande recommandée pour une première installation :

```bash
make install
```

`make install` démarre déjà les services.
`make dev` sert surtout à relancer l'environnement après un arrêt.

## Accès aux services

- **Storefront** : http://localhost:8000
- **Admin backend** : http://localhost:8000/admin
- **API** : http://localhost:8000/api/v1
- **Mailpit** : http://localhost:8025
- **Meilisearch** : http://localhost:7700

## Services Docker inclus

| Service | Conteneur | Port(s) | Description |
|---------|-----------|---------|-------------|
| Backend Laravel | `omersia-backend` | 5173, 8080 | API + Admin + Vite/Reverb |
| Storefront Next.js | `omersia-storefront` | 3000 (interne) | Frontend e-commerce |
| Nginx | `omersia-nginx` | 8000 | Reverse proxy |
| MySQL | `omersia-mysql` | 3306 | Base de données |
| Meilisearch | `omersia-meilisearch` | 7700 | Moteur de recherche |
| Mailpit | `omersia-mailpit` | 8025, 1025 | Emails de test |

## Commandes quotidiennes (Makefile)

```bash
make dev             # Relancer l'environnement
make docker-down     # Arrêter les conteneurs
make docker-logs     # Suivre les logs
make docker-rebuild  # Rebuild complet des images
make clean           # Nettoyer les caches applicatifs
make refresh-styles  # Régénérer les styles frontend
```

## Setup applicatif (Makefile)

```bash
make setup-db                     # Migrations + rôles/permissions
make migrate                      # Migrations uniquement
make migrate-fresh CONFIRM_WIPE=yes
make admin                        # Créer un admin (interactif)
make apikey                       # Générer/synchroniser la clé API
```

## Qualité et build (Makefile)

```bash
make test
make lint
make lint-fix
make build
```

## Opérations avancées (fallback Docker)

Le Makefile couvre les besoins courants. Pour du debug ciblé par service :

```bash
# Logs d'un service précis
docker compose logs -f backend
docker compose logs -f storefront

# Shell dans les conteneurs
docker compose exec backend sh
docker compose exec storefront sh

# Commandes métier ponctuelles
docker compose exec -T backend php artisan products:meili-config
docker compose exec -T backend php artisan products:index
```

## Workflow recommandé

```bash
# 1) Installation complète (1 seule fois)
make install

# 2) Développer / tester
make lint
make test

# 3) Arrêter
make docker-down

# 4) Relancer plus tard
make dev
```

## Dépannage

### Docker daemon indisponible

```bash
docker --version
# Puis démarrer Docker Desktop
```

### Port déjà utilisé

```bash
lsof -i :8000
# Ajuster les ports dans docker-compose.yml si nécessaire
```

### Styles backend cassés

```bash
make refresh-styles
make build
make docker-logs
```

### MySQL ou Meilisearch ne répond pas

```bash
make docker-logs
# Debug ciblé :
docker compose logs -f mysql
docker compose logs -f meilisearch
```

### Reset complet

```bash
make docker-down

docker compose down -v
rm -rf backend/vendor backend/.env
rm -rf storefront/node_modules storefront/.env.local

make install
```

## Fichiers de configuration

- `Makefile` : point d'entrée principal
- `docker-compose.yml` : orchestration des services
- `.env.docker` : variables Docker locales
- `.env.docker.example` : template de variables
