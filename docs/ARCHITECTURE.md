# Architecture

## Vue générale

```text
React 19 / Vite
  -> client API central (cookies + erreurs)
  -> Laravel 13 / Sanctum SPA
  -> FormRequests + Policies + Resources
  -> Services métier transactionnels
  -> Eloquent / MySQL
```

## Backend

- `app/Http/Controllers/Api/Store` : endpoints publics et client
- `app/Http/Controllers/Api/Admin` : endpoints protégés par `auth:sanctum`, `active`, `admin`
- `app/Http/Requests` : validation et interdiction des champs pilotés par le serveur
- `app/Policies` : ownership et rôles
- `app/Http/Resources` : contrats JSON public/admin séparés
- `app/Services` : catalogue admin, panier, checkout, commandes et inventaire

Le checkout verrouille le panier et les lignes d’inventaire dans une transaction, recalcule les prix, crée la commande et ses snapshots, applique le coupon, crée paiement/livraison/historique, trace les mouvements de stock puis convertit le panier.

## Frontend

- `src/app` : providers d’authentification et panier
- `src/services/api.js` : URL via `VITE_API_URL`, cookies Sanctum, JSON et erreurs HTTP
- `src/components` : layout, protections de routes, cartes et états asynchrones
- `src/pages` : storefront, compte, checkout et dashboard admin
- `src/index.css` : tokens Figma et responsive

Le frontend ne contient aucun catalogue métier simulé. Les produits, prix, stocks, commandes et métriques proviennent de Laravel.

Après une transition d'authentification, `CartAuthSync` recharge le panier serveur. L'interface reflète ainsi immédiatement la fusion du panier invité après connexion et le nouveau contexte invité après déconnexion.

## Données

Les montants sont stockés en `DECIMAL(12,2)`. Les commandes enregistrent des snapshots client, produit, adresse et prix pour préserver l’historique. `InventoryMovement` assure la traçabilité du stock.

## Intégration continue

Le workflow `.github/workflows/ci.yml` sépare les contrôles backend, frontend et sécurité/conteneur. Le backend est testé avec MySQL 8.4, conformément à l'environnement de test du projet. Le frontend utilise Vitest et jsdom sans dépendre de l'API publique. Gitleaks inspecte l'historique et le Dockerfile racine est construit comme artefact de release.
