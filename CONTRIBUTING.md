# Guide de Contribution

Merci de votre intérêt pour contribuer à Omersia ! Ce document explique comment participer au projet.

## 📋 Table des matières

- [Code de Conduite](#code-de-conduite)
- [Comment Contribuer](#comment-contribuer)
- [Signaler un Bug](#signaler-un-bug)
- [Proposer une Fonctionnalité](#proposer-une-fonctionnalité)
- [Soumettre une Pull Request](#soumettre-une-pull-request)
- [Standards de Code](#standards-de-code)
- [Commits Conventionnels](#commits-conventionnels)
- [Structure des Branches](#structure-des-branches)
- [Processus de Review](#processus-de-review)
- [Questions ?](#questions-)

---

## Code de Conduite

Ce projet adhère au [Code de Conduite](CODE_OF_CONDUCT.md).  
En participant, vous vous engagez à respecter ces règles.

---

## Comment Contribuer

### 1. Fork et Clone

```bash
# Fork via GitHub, puis :
git clone https://github.com/VOTRE-USERNAME/omersia.git
cd omersia
git remote add upstream https://github.com/omersia/omersia.git
```

### 2. Créer une Branche

```bash
git checkout -b feature/ma-fonctionnalite
# ou
git checkout -b fix/correction-bug
```

### 3. Développer

- Suivez les **standards de code** décrits plus bas
- Ajoutez des **tests** pour toute nouvelle fonctionnalité ou correction
- Assurez-vous que l'application se lance correctement

### 4. Tester

```bash
# Backend
cd backend
php artisan test
./vendor/bin/pint --test

# Frontend
cd storefront
npm run lint
npm run test
```

### 5. Commit et Push

```bash
git add .
git commit -m "feat: description de la fonctionnalité"
git push origin feature/ma-fonctionnalite
```

### 6. Ouvrir une Pull Request

- Créez une PR vers la branche `main`
- Décrivez clairement :
  - Le problème résolu ou la fonctionnalité ajoutée
  - Le comportement avant / après
  - Comment tester

---

## Signaler un Bug

Avant de signaler un bug :

1. Vérifiez qu'il n'existe pas déjà dans les **issues**
2. Utilisez le **template de bug report** fourni
3. Incluez les informations suivantes :
   - Version de Omersia
   - Étapes pour reproduire
   - Comportement attendu vs observé
   - Screenshots si applicable
   - Extraits de logs d'erreur

---

## Proposer une Fonctionnalité

1. Ouvrez une **issue** avec le label `enhancement`
2. Décrivez le **problème** que cette fonctionnalité résout
3. Proposez une **solution** (UX, API, comportement)
4. Attendez la **validation** d'un mainteneur avant de développer

---

## Soumettre une Pull Request

### Checklist PR

- [ ] Tests ajoutés / mis à jour
- [ ] Documentation mise à jour si nécessaire
- [ ] Code formaté (`pint` pour PHP, `eslint`/`prettier` pour JS/TS)
- [ ] Commits suivent les **conventional commits**
- [ ] Branche à jour avec `main`
- [ ] PR liée à une issue si applicable

### Template PR

```md
## Description
[Description claire du changement]

## Type de changement
- [ ] Bug fix
- [ ] Nouvelle fonctionnalité
- [ ] Breaking change
- [ ] Documentation

## Comment tester
1. Étape 1
2. Étape 2

## Captures d'écran
[Si applicable]

## Issues liées
Fixes #123
```

---

## Standards de Code

### PHP (Backend)

- **Style** : PSR-12
- **Outil** : Laravel Pint
- **Typage** : `strict_types` recommandé

#### Vérifier le style

```bash
./vendor/bin/pint --test
```

#### Corriger automatiquement

```bash
./vendor/bin/pint
```

#### Conventions PHP

```php
<?php

declare(strict_types=1);

namespace Omersia\Package;

class ExampleClass
{
    public function __construct(
        private readonly string $property
    ) {
    }

    public function doSomething(): void
    {
        // ...
    }
}
```

### TypeScript / JavaScript (Frontend)

- **Style** : ESLint + Prettier
- **Typage** : TypeScript strict

#### Vérifier

```bash
npm run lint
```

#### Corriger

```bash
npm run lint:fix
```

#### Conventions TypeScript

```ts
// Composants React
export function ProductCard({ product }: ProductCardProps): JSX.Element {
  // ...
}

// Pas de 'any'
interface Product {
  id: number;
  name: string;
  price: number;
}
```

### Tests

- **Backend** : PHPUnit
- **Frontend** : Jest + Testing Library

#### Backend

```bash
php artisan test
php artisan test --coverage
```

#### Frontend

```bash
npm run test
npm run test:coverage
```

---

## Commits Conventionnels

Format :

```text
type(scope): description
```

### Types

| Type    | Description                    |
|---------|--------------------------------|
| feat    | Nouvelle fonctionnalité        |
| fix     | Correction de bug              |
| docs    | Documentation                  |
| style   | Formatage (pas de changement de logique) |
| refactor| Refactoring                    |
| test    | Ajout/modification de tests    |
| chore   | Maintenance (deps, config, etc.) |
| perf    | Amélioration de performance    |

### Exemples

```text
feat(catalog): add product variant management
fix(checkout): resolve payment validation error
docs(readme): update installation instructions
refactor(admin): extract discount calculation to service
test(api): add tests for order endpoints
chore(deps): update laravel to 10.48
```

### Scope (optionnel)

Exemples de scope :

- `catalog`, `admin`, `customer`, `payment`, `checkout`
- `api`, `frontend`, `docs`, `docker`

---

## Structure des Branches

```text
main                 # Production
├── develop          # Développement
├── feature/*        # Nouvelles fonctionnalités
├── fix/*            # Corrections
├── docs/*           # Documentation
└── release/*        # Préparation release
```

---

## Processus de Review

1. Au moins **1 approbation** requise
2. Tous les **checks CI** doivent passer
3. Pas de **conflits** avec `main`
4. Code review par un **mainteneur** du projet

---

## Questions ?

- 💬 Discussions GitHub
- 📧 Email : `contact@omersia.com`

Merci de contribuer à Omersia ! 🎉
