# Sécurité

## Contrôles en place

- Sanctum SPA stateful avec cookies et CSRF
- rate limiting sur authentification, catalogue, panier et checkout
- middleware `active` et `admin`
- Policies et contrôle d’ownership sur adresses, commandes, avis, coupons et inventaire
- FormRequests et listes de champs validés; champs prix, total, statut, rôle et utilisateur prohibés
- Resources publiques sans mots de passe, tokens, coûts internes, références fournisseur ou métadonnées de paiement
- uploads image validés et stockés par Laravel dans le module admin existant
- checkout sous transaction et `lockForUpdate` sur panier, coupon et inventaire
- prix et remises recalculés côté serveur; stock négatif refusé
- unicité DB sur wishlist, avis, coupons, SKU, panier/variante et usage coupon/commande

## Paiements

Aucun prestataire bancaire n’est configuré. Toute méthode crée un paiement `pending` avec le marqueur interne `development_pending`. Le navigateur ne peut pas déclarer un paiement réussi.

## Environnements

- `.env` et `.env.testing` ne doivent pas être versionnés ni copiés dans des rapports.
- `.env.example` ne contient que des valeurs fictives.
- PHPUnit doit utiliser exclusivement MySQL `ecommerce_testing`.
- Ne jamais lancer `migrate:fresh` sur `ecommerce` ou une base non explicitement jetable.

## Limites connues

- Un fournisseur de paiement réel exigera webhooks signés, idempotency keys persistantes et vérification serveur.
- Les workflows complets de retour/remboursement et les mises à jour détaillées de livraison restent à finaliser.
- En production, limiter précisément `FRONTEND_URL`, `SANCTUM_STATEFUL_DOMAINS`, les proxies de confiance et les domaines CORS.
