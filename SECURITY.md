# Politique de Sécurité

## Versions Supportées

| Version | Supportée          |
| ------- | ------------------ |
| 1.x.x   | :white_check_mark: |
| < 1.0   | :x:                |

## Signaler une Vulnérabilité

**⚠️ Ne créez PAS d'issue publique pour les vulnérabilités de sécurité.**

### Comment signaler

1. **Email** : Envoyez un email à **contact@omersia.com**
2. **Sujet** : `[SECURITY] Description brève`
3. **Contenu** :
   - Description de la vulnérabilité
   - Étapes pour reproduire
   - Impact potentiel
   - Suggestion de correction (si disponible)

### Ce que nous nous engageons à faire

- Accusé de réception sous 48h
- Évaluation initiale sous 7 jours
- Mise à jour régulière sur le statut
- Crédit dans le CHANGELOG (si souhaité)

### Processus de divulgation

1. Vous nous signalez la vulnérabilité
2. Nous confirmons et évaluons
3. Nous développons un correctif
4. Nous publions une mise à jour
5. Nous divulguons publiquement (après 90 jours ou après correction)

## Bonnes Pratiques de Sécurité

### Pour les déploiements

- [ ] `APP_DEBUG=false` en production
- [ ] Secrets dans des variables d'environnement sécurisées
- [ ] HTTPS obligatoire
- [ ] Headers de sécurité activés
- [ ] CORS restreint aux domaines autorisés
- [ ] Rate limiting activé
- [ ] Logs des accès configurés

### Configuration recommandée

```env
APP_DEBUG=false
APP_ENV=production
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
```

### Hall of Fame


### Merci de nous aider à garder Omersia sécurisé ! 🔒