# Changelog

Toutes les modifications notables de ce projet seront documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/lang/fr/).

## [Unreleased]

### Added
- Wizard d'installation web (en cours)

### Changed
- Mise à jour de la documentation

### Fixed
- Correction des problèmes de sécurité CORS

---

## [1.0.0] - 2025-XX-XX

### Added
- 🎉 Release initiale de Omersia
- Architecture modulaire avec 10 packages Laravel
- Page Builder avec 28+ widgets
- Système de thèmes personnalisables
- Gestion complète des produits et variantes
- Panier et processus de checkout
- Intégration Stripe pour les paiements
- Recherche instantanée avec MeiliSearch
- Interface d'administration complète
- API REST documentée (OpenAPI/Swagger)
- Storefront Next.js 16

### Backend Packages
- `omersia/core` - Shops, domaines, API keys
- `omersia/catalog` - Produits, catégories, commandes
- `omersia/customer` - Gestion des clients
- `omersia/payment` - Intégration Stripe
- `omersia/sales` - Remises et promotions
- `omersia/apparence` - Thèmes et personnalisation
- `omersia/admin` - Interface d'administration
- `omersia/storefront-api` - API pour le frontend
- `omersia/cms` - Gestion de contenu
- `omersia/shared` - Value Objects communs

### Frontend Features
- App Router Next.js 16
- Server Components & Client Components
- ISR pour le cache
- Tailwind CSS 4
- TypeScript strict

---

## Types de changements

- `Added` pour les nouvelles fonctionnalités
- `Changed` pour les changements de fonctionnalités existantes
- `Deprecated` pour les fonctionnalités qui seront supprimées
- `Removed` pour les fonctionnalités supprimées
- `Fixed` pour les corrections de bugs
- `Security` pour les corrections de vulnérabilités

[Unreleased]: https://github.com/omersia/omersia/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/omersia/omersia/releases/tag/v1.0.0