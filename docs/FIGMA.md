# Figma

Project: Seef E-commerce

Design:  
https://www.figma.com/make/Ws3iRp8Gy7gfcv0BQiKxOs/Ecommerce-store-design-model?t=ICyVwlBLiHwf3BQ3-20&fullscreen=1

Public site:  
https://ide-mural-42346251.figma.site/

Designer / Developer:  
Youssef BOUGHIOUL

GitHub:  
https://github.com/5eef

## Rôle du Figma

La maquette est la source visuelle du storefront. Le bundle public a permis de relever la palette, les typographies, les libellés, le hero, la grille catalogue, la capsule éditoriale, les cartes produit et les ruptures responsive.

## Design system relevé

- Titres : Playfair Display
- Texte et interface : Outfit
- Fond : `#F8F5F0`; fond secondaire : `#F0EBE3`
- Texte : `#16120E`; texte secondaire : `#7A6A5E`
- Accent : `#B85C38`; hover : `#96421F`
- Bordure : `#E2D9CE`
- Layout : hero 50/50 sur desktop, catalogue quatre colonnes, navigation mobile fixe sous 1024 px

## Pages et composants correspondants

- Accueil : hero « Intemporel, par nature », produits vedettes et capsule Hiver 2026
- Boutique : filtres, catégories, tri et grille de cartes réelles provenant de l’API
- Produit : galerie, variantes, disponibilité et ajout panier
- Panier, checkout, authentification, wishlist, compte et commandes : même design system
- Admin : dashboard utilisant la même palette et la même hiérarchie
- Composants : header, navigation mobile, `ProductCard`, boutons, badges, panels, formulaires et états asynchrones

## Différences techniques justifiées

- Le connecteur Figma Make a refusé l’accès au fichier source faute de droit éditeur. Le site Figma public fourni ensuite a servi de référence accessible.
- Les produits statiques de la maquette ne sont pas copiés dans React : les cartes affichent uniquement les données Laravel. Les seeders fournissent un jeu de démonstration cohérent.
- Les icônes décoratives propriétaires ne sont pas redessinées : la navigation emploie du texte sémantique afin de ne pas inventer d’assets.
- Les écrans administratifs détaillés absents du site public restent fonctionnels côté API mais ne disposent pas encore tous d’une page React dédiée.
