# API

Base locale Seef : `http://localhost:8017/api`. Les réponses sont JSON. Les routes client et admin utilisent Sanctum SPA (`credentials: include`).

| Méthode | Endpoint | Auth | Rôle | Description |
|---|---|---:|---|---|
| POST | `/auth/register` | Non | Visiteur | Créer un compte client |
| POST | `/auth/login` | Non | Visiteur | Ouvrir une session |
| POST | `/auth/forgot-password` | Non | Visiteur | Demander un lien de réinitialisation |
| POST | `/auth/reset-password` | Non | Visiteur | Réinitialiser le mot de passe |
| GET | `/auth/me` | Oui | Actif | Utilisateur courant |
| POST | `/auth/logout` | Oui | Actif | Fermer la session |
| GET | `/categories` | Non | Public | Catégories actives |
| GET | `/categories/{slug}` | Non | Public | Détail d’une catégorie |
| GET | `/products` | Non | Public | Catalogue, recherche, filtres, tri et pagination |
| GET | `/products/{slug}` | Non | Public | Fiche produit publiée |
| GET | `/products/{slug}/reviews` | Non | Public | Avis approuvés |
| POST | `/products/{slug}/reviews` | Oui | Client actif | Créer un avis après achat livré |
| GET | `/cart` | Non | Public | Lire/créer le panier courant |
| POST | `/cart/items` | Non | Public | Ajouter une variante au prix serveur |
| PATCH | `/cart/items/{item}` | Non | Public | Modifier une quantité |
| DELETE | `/cart/items/{item}` | Non | Public | Retirer une ligne |
| DELETE | `/cart` | Non | Public | Vider le panier |
| POST | `/cart/merge` | Oui | Client actif | Fusionner le panier invité |
| GET | `/wishlist` | Oui | Client actif | Lire la wishlist |
| POST | `/wishlist/items` | Oui | Client actif | Ajouter un produit sans doublon |
| DELETE | `/wishlist/items/{product}` | Oui | Client actif | Retirer un produit |
| GET | `/addresses` | Oui | Client actif | Lister ses adresses |
| POST | `/addresses` | Oui | Client actif | Créer une adresse |
| GET | `/addresses/{address}` | Oui | Propriétaire | Lire une adresse |
| PUT/PATCH | `/addresses/{address}` | Oui | Propriétaire | Modifier une adresse |
| DELETE | `/addresses/{address}` | Oui | Propriétaire | Supprimer une adresse |
| POST | `/checkout` | Non | Public | Checkout transactionnel invité/client |
| GET | `/orders` | Oui | Client actif | Lister ses commandes |
| GET | `/orders/{order}` | Oui | Propriétaire | Détail, lignes, paiement, livraison et historique |
| GET | `/admin/dashboard` | Oui | Admin actif | Métriques DB et commandes récentes |
| GET/POST | `/admin/categories` | Oui | Admin actif | Lister/créer des catégories |
| GET/PATCH/DELETE | `/admin/categories/{category}` | Oui | Admin actif | Lire/modifier/supprimer une catégorie |
| GET/POST | `/admin/products` | Oui | Admin actif | Lister/créer des produits |
| GET/PATCH/DELETE | `/admin/products/{product}` | Oui | Admin actif | Lire/modifier/archiver un produit |
| POST | `/admin/products/{product}/restore` | Oui | Admin actif | Restaurer un produit |
| GET/POST | `/admin/products/{product}/options` | Oui | Admin actif | Options produit |
| GET/PATCH/DELETE | `/admin/products/{product}/options/{option}` | Oui | Admin actif | Gérer une option |
| POST | `/admin/products/{product}/options/{option}/values` | Oui | Admin actif | Créer une valeur |
| PATCH/DELETE | `/admin/products/{product}/options/{option}/values/{value}` | Oui | Admin actif | Modifier/supprimer une valeur |
| GET/POST | `/admin/products/{product}/variants` | Oui | Admin actif | Variantes produit |
| GET/PATCH/DELETE | `/admin/products/{product}/variants/{variant}` | Oui | Admin actif | Gérer une variante |
| POST | `/admin/products/{product}/variants/{variant}/restore` | Oui | Admin actif | Restaurer une variante |
| GET/POST | `/admin/products/{product}/images` | Oui | Admin actif | Images produit |
| GET/PATCH/DELETE | `/admin/products/{product}/images/{image}` | Oui | Admin actif | Gérer une image |
| GET | `/admin/customers` | Oui | Admin actif | Rechercher et paginer les clients |
| GET | `/admin/customers/{customer}` | Oui | Admin actif | Fiche client |
| PATCH | `/admin/customers/{customer}/status` | Oui | Admin actif | Statut client |
| GET | `/admin/orders` | Oui | Admin actif | Rechercher les commandes |
| GET | `/admin/orders/{order}` | Oui | Admin actif | Détail interne commande |
| PATCH | `/admin/orders/{order}/status` | Oui | Admin actif | Transition de statut contrôlée |
| GET/POST | `/admin/coupons` | Oui | Admin actif | Lister/créer les coupons |
| GET/PATCH/DELETE | `/admin/coupons/{coupon}` | Oui | Admin actif | Lire/modifier/désactiver un coupon |
| GET | `/admin/inventory` | Oui | Admin actif | Stock, recherche et filtre stock faible |
| GET | `/admin/inventory/{inventory}` | Oui | Admin actif | Stock et mouvements récents |
| PATCH | `/admin/inventory/{inventory}` | Oui | Admin actif | Ajustement atomique et audité |
| GET | `/admin/reviews` | Oui | Admin actif | Avis à modérer |
| PATCH | `/admin/reviews/{review}` | Oui | Admin actif | Approuver/rejeter un avis |

## Erreurs

- `401` non authentifié
- `403` compte/rôle/ownership refusé
- `404` ressource absente ou non publiée
- `409` conflit métier lorsqu’un module existant l’emploie
- `422` validation ou règle métier
- `429` limite de débit
