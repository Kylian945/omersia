# Guide Docker - Omersia

## 🚀 Démarrage Rapide

### 1. Première installation

```bash
# Copier le fichier d'environnement Docker
cp .env.docker.example .env.docker

# Éditer .env.docker si nécessaire (DB config, etc.)
nano .env.docker

# Lancer les conteneurs
docker compose up -d

# Attendre que tout soit prêt (30-60 secondes)
# Le backend compile automatiquement les assets Vite au démarrage
```

### 2. Accéder à l'application

- **Application complète** : http://localhost:8000
- **Admin backend** : http://localhost:8000/admin
- **Storefront** : http://localhost:8000 (pages produits)
- **Mailpit** (emails de test) : http://localhost:8025
- **Meilisearch** : http://localhost:7700

## 📦 Services Inclus

| Service | Conteneur | Port(s) | Description |
|---------|-----------|---------|-------------|
| **Backend Laravel** | `omersia-backend` | 8001 (interne) | API + Admin |
| **Storefront Next.js** | `omersia-storefront` | 3000 (interne) | Frontend e-commerce |
| **Nginx** | `omersia-nginx` | 8000 | Reverse proxy |
| **Meilisearch** | `omersia-meilisearch` | 7700 | Moteur de recherche |
| **Mailpit** | `omersia-mailpit` | 8025, 1025 | Email testing |
| **MySQL** | `omersia-mysql` | 3306 | Base de données (sur host) |

## 🔧 Mode Développement avec Hot Reload

Le **hot reload** Vite est activé par défaut dans `docker-compose.yml`.

Le port 5173 (Vite HMR) est exposé et les assets se recompilent automatiquement à chaque modification.

## 🛠️ Commandes Utiles

### Gestion des conteneurs

```bash
# Démarrer
docker compose up -d

# Arrêter
docker compose down

# Voir les logs
docker compose logs -f

# Logs d'un service spécifique
docker compose logs -f backend
docker compose logs -f storefront

# Rebuild après modification des Dockerfiles
docker compose up -d --build

# Tout supprimer (conteneurs + volumes)
docker compose down -v
```

### Commandes Laravel (backend)

```bash
# Entrer dans le conteneur backend
docker compose exec backend sh

# Lancer une commande Artisan
docker compose exec backend php artisan migrate
docker compose exec backend php artisan db:seed

# Installer les dépendances
docker compose exec backend composer install

# Compiler les assets manuellement
docker compose exec backend npm run build

# Lancer Vite en mode dev
docker compose exec backend npm run dev
```

### Commandes Next.js (storefront)

```bash
# Entrer dans le conteneur storefront
docker compose exec storefront sh

# Installer les dépendances
docker compose exec storefront npm install

# Build production
docker compose exec storefront npm run build
```

### Commandes Meilisearch

```bash
# Configurer les index
docker compose exec backend php artisan products:meili-config

# Indexer les produits
docker compose exec backend php artisan products:index

# Vérifier l'état de Meilisearch
curl http://localhost:7700/health
```

## 🗄️ Base de Données

Par défaut, MySQL tourne sur votre **machine locale** (pas dans Docker) sur le port **8889**.

### Configuration MAMP/MAMP Pro

Si vous utilisez MAMP :
1. Vérifiez que MySQL tourne sur le port 8889
2. Créez la base de données `omersia`
3. Les conteneurs Docker se connectent via `host.docker.internal:8889`

### Configuration alternative (MySQL dans Docker)

Pour mettre MySQL dans Docker, éditez `docker-compose.yml` et ajoutez :

```yaml
services:
  mysql:
    image: mysql:8.0
    container_name: omersia_mysql_local
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: omersia
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql
    networks:
      - omersia_network

volumes:
  mysql_data:
```

Puis modifiez la config backend :
```yaml
backend:
  environment:
    - DB_HOST=mysql  # au lieu de host.docker.internal
    - DB_PORT=3306   # au lieu de 8889
```

## 🎨 Problèmes de Style Cassé

Si le style du backend est cassé après `docker compose up` :

### Solution 1 : Rebuild les assets

```bash
docker compose exec backend npm run build
```

### Solution 2 : Vérifier que Node.js est installé

```bash
docker compose exec backend node --version
docker compose exec backend npm --version
```

Si les commandes échouent, rebuildez l'image :

```bash
docker compose up -d --build backend
```

### Solution 3 : Vérifier les fichiers générés

```bash
docker compose exec backend ls -la public/build/
```

Vous devriez voir :
- `manifest.json`
- `assets/app-*.css`
- `assets/app-*.js`

Si les fichiers sont absents, forcez la compilation :

```bash
docker compose exec backend npm install
docker compose exec backend npm run build
```

## 🔄 Workflow de Développement Complet

### Premier lancement

```bash
# 1. Configuration
cp .env.docker.example .env.docker

# 2. Lancer Docker
docker compose up -d

# 3. Attendre que tout soit prêt
docker compose logs -f backend

# 4. Créer la base de données (si pas déjà fait)
# Via MAMP ou phpMyAdmin, créer la DB "omersia"

# 5. Migrations et seeders
docker compose exec backend php artisan migrate
docker compose exec backend php artisan db:seed

# 6. Créer un admin
docker compose exec backend php artisan make:admin

# 7. Indexer les produits
docker compose exec backend php artisan products:meili-config
docker compose exec backend php artisan products:index

# 8. Ouvrir l'app
open http://localhost:8000
```

### Développement quotidien

```bash
# Démarrer
docker compose up -d

# (Développer normalement...)

# Arrêter en fin de journée
docker compose down
```

### Hot Reload

Les assets backend se recompilent automatiquement grâce à Vite (port 5173).

## 🚨 Dépannage

### "Connection refused" à la base de données

```bash
# Vérifier que MySQL tourne sur le host
mysql -h 127.0.0.1 -P 8889 -u root -p

# Vérifier depuis le conteneur
docker compose exec backend ping host.docker.internal
```

### "Cannot connect to Docker daemon"

```bash
# Vérifier que Docker Desktop tourne
docker --version

# Redémarrer Docker Desktop si nécessaire
```

### Port 8000 déjà utilisé

```bash
# Trouver quel processus utilise le port
lsof -i :8000

# Modifier le port dans docker-compose.yml
# nginx -> ports: "8080:80" (au lieu de 8000:80)
```

### Vite ne compile pas

```bash
# Vérifier les logs
docker compose logs backend

# Forcer la compilation
docker compose exec backend npm run build

# En cas d'erreur, nettoyer et réinstaller
docker compose exec backend rm -rf node_modules package-lock.json
docker compose exec backend npm install
docker compose exec backend npm run build
```

### Meilisearch ne démarre pas

```bash
# Vérifier le health check
docker compose ps

# Voir les logs
docker compose logs meilisearch

# Redémarrer uniquement Meilisearch
docker compose restart meilisearch
```

## 📚 Fichiers de Configuration

- **docker-compose.yml** : Configuration principale (développement local)
- **docker-compose.override.yml** : Overrides locaux optionnels (gitignored)
- **.env.docker** : Variables d'environnement Docker (gitignored, créé depuis .example)
- **.env.docker.example** : Template des variables d'environnement

## 🏗️ Architecture Docker

```
┌─────────────────────────────────────────┐
│         Nginx (Port 8000)               │
│         Reverse Proxy                   │
└─────────────┬───────────────────────────┘
              │
      ┌───────┴────────┐
      │                │
┌─────▼──────┐  ┌──────▼─────┐
│  Backend   │  │ Storefront │
│  Laravel   │  │  Next.js   │
│  :8001     │  │   :3000    │
└─────┬──────┘  └──────┬─────┘
      │                │
      │         ┌──────▼─────────┐
      │         │  Meilisearch   │
      │         │     :7700      │
      │         └────────────────┘
      │
      │         ┌────────────────┐
      │         │    Mailpit     │
      │         │  :8025, :1025  │
      │         └────────────────┘
      │
┌─────▼──────────┐
│  MySQL (Host)  │
│     :8889      │
└────────────────┘
```

## 🎯 Prochaines Étapes

1. Connexion à l'admin : http://localhost:8000/admin
2. Créer vos premiers produits
3. Configurer le thème dans Admin > Apparence
4. Tester le checkout avec Stripe test mode
5. Consulter les emails dans Mailpit

---

**Besoin d'aide ?** Consultez la documentation complète ou ouvrez une issue sur GitHub.
