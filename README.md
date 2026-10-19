# MASS BUSINESS WEAR

Boutique en ligne mobile-first (PWA) — panier → commande WhatsApp — back-office 100 % smartphone.

**Stack** : PHP 8+ sans framework (mini-MVC maison), MySQL 8, Bootstrap 5 (vendor local), JS vanilla.
Aucune dépendance Composer, aucune librairie CDN : compatible hébergement PHP/MySQL classique.

## Installation (WAMP / Apache + MySQL)

```bash
# 1. Importer le schéma
mysql -u root < database/schema.sql

# 2. Configurer
cp .env.example .env          # puis adapter DB_* et APP_URL

# 3. Créer le premier admin (mot de passe min. 8 caractères)
php database/create_admin.php --login=admin --password=VotreMotDePasse

# 4. Ouvrir
#    http://localhost/mass-business-wear/           (front controller racine)
#    http://localhost/mass-business-wear/public/    (entrée directe)
#    http://localhost/mass-business-wear/public/admin/login
```

Le dossier exposé est `public/`. `app/`, `config/`, `database/`, `routes/`, `storage/` et `.env`
sont bloqués par `.htaccess` (racine) — ne jamais déplacer le front controller hors de `public/`.

## Structure

```
app/            Controllers, Services, Repositories, Middleware, Helpers, Views
config/         app.php (construit depuis .env)
database/       schema.sql, seed_dev.sql, create_admin.php
public/         index.php (front controller), assets/, uploads/
routes/         web.php, admin.php, api.php
storage/        logs/ cache/   (hors web)
```

## Phases livrées

**Phase 1 — Fondations** ✅
- `.env` + parseur maison, config centralisée
- PDO (requêtes préparées, transactions), routeur `{param}`, réponse HTML/JSON
- Sessions durcies + CSRF (header `X-CSRF-Token` ou champ `_token`)
- AdminAuth, RateLimit, login anti brute-force (délai progressif, table `login_attempts`)
- Schéma complet (14 tables, snapshot des prix, historique des statuts, journal admin)
- Pages : accueil témoin, login, dashboard squelette, erreurs 403/404/405/429/500
- `.htaccess` : réécriture + interdiction d'accès aux dossiers sensibles et aux uploads exécutables

**Phase 2 — Boutique & design system** ✅
- Tokens CSS + composants (cards, badges, sheet, skeleton, empty, toast, nav)
- Repositories Product/Category/Setting + API `/api/products` + `/api/search`
- Layout boutique (header sticky, bottom nav, footer) + accueil réelle + catalogue (recherche, filtres, tri, pagination)
- Seed dev + images placeholder générées par GD

**Phase 3 — Produit & panier** ✅
- Fiche produit : galerie swipeable (scroll-snap), variantes croisées taille/couleur, quantité, CTA sticky
- Ajout au panier (localStorage `mbw_cart_v1`), badges synchronisés
- Page panier réactive (lignes, quantités, suppression, total)
- Page checkout : formulaire (nom, téléphone, zone, adresse, note) + récap + zones de livraison (table `shipping_zones`) — validation serveur en Phase 4

**Phase 4 — Checkout serveur & WhatsApp** ✅
- `OrderService` : transaction MySQL, prix/stock/variantes **toujours** revérifiés côté serveur, décrémentation atomique (`FOR UPDATE`), snapshot dans `order_items`, historique de statut, mouvements de stock
- Référence `MBW-AAAAMMJJ-001` (séquence par jour sous verrou `GET_LOCK`)
- Idempotence par `X-Idempotency-Key` (retry réseau sans double commande)
- `WhatsAppService` : message structuré pré-rempli (référence, client, articles, total)
- `POST /api/orders` (JSON, CSRF, RateLimit) + page `/order/success?ref=…` (récap + CTA WhatsApp + vidage panier local)

**Phase 5 — Back-office mobile-first** ✅
- Layout admin : topbar sticky, bottom nav 5 onglets (Accueil, Commandes, Produits, Catégories, Réglages), flash toasts, animations fluides (stagger, transitions, `prefers-reduced-motion`)
- Dashboard : stats (CA, commandes, clients, produits), commandes récentes, stock faible, CA 7 jours, meilleures ventes
- Produits : liste (recherche, statut, featured), create/edit (slug auto, promo, SEO), variantes (taille/couleur/SKU/stock), images (upload MIME+2 Mo, principale, suppression), featured/status, zone dangereuse
- Commandes : liste (filtres statut + recherche), détail (client, articles, totaux), changement de statut (transaction + retour stock si annulation), historique timeline, suppression
- Catégories : CRUD (création + édition inline)
- Paramètres : boutique, contact, réseaux, zones de livraison CRUD
- Journal : actions admin paginées, filtre par admin
- `admin.css` + `admin.js` (confirm, double-submit guard, auto-submit, preview upload)
- Routes protégées `AdminAuth` sur toutes les mutations (POST uniquement, router sans PUT/DELETE)

**Phase 6 — Clients & statistiques** ✅
- Clients : liste agrégée (CA, commandes, dernière commande), recherche nom/téléphone, fiche détail (cumuls, 1ʳᵉ/dernière commande, lien WhatsApp, historique), pagination
- Statistiques : période 7/30/90 j, CA/commandes/panier moyen/ articles/clients actifs, comparaison vs période précédente (deltas %), histogramme CA/jour, top produits, top clients, répartition zones (barres), statuts, fidélité (récurrents vs uniques), 6 derniers mois
- `AdminCustomerRepository` + `AdminStatsRepository`
- Bottom nav : Clients remplace Catég. (catégories accessibles depuis Produits) ; Stats depuis le dashboard
- Routes GET `/admin/customers`, `/admin/customers/{id}`, `/admin/stats?days=` (AdminAuth)

**Phase 7 — PWA double + SEO + seed + durcissement** ✅
- **Deux PWA séparées** : boutique (`manifest.json`, scope `/public/`) et back-office (`manifest.admin.json`, scope `/public/admin/`) — icônes 192/512/maskable générées depuis le logo
- **Boutique SW** (`service-worker.js`) : shell offline, pages réseau-d'abord + fallback « hors ligne », assets stale-while-revalidate
- **Admin SW** (`admin-sw.js`) : cache uniquement des assets UI, HTML/API toujours réseau (données jamais stale), page offline de repli
- **Installation admin** : bouton « Installer » topbar (`beforeinstallprompt`), feuille d'instructions iOS (Safari → Partager → Écran d'accueil), état « Installée »
- **SEO** : `robots.txt` + `sitemap.xml` dynamiques (20 URLs : pages, catégories, produits), meta description/OG/Twitter sur le shop, JSON-LD `Product` (prix XOF, disponibilité), canonical
- **Seed démo** : `database/seed_orders_demo.sql` (12 commandes, 7 clients, 15 items sur 40 j) pour alimenter stats/clients — import après `seed_dev.sql`
- **Durcissement** : SW en `no-cache`, manifests `application/manifest+json`, assets statiques cache 1 j + `nosniff`/`SAMEORIGIN`

**Install admin** : ouvrir `/admin/login` sur le téléphone → bouton Installer (Chrome/Android automatique, iOS via instructions).

## Sécurité (rappels)

- Toute mutation POST exige un jeton CSRF valide.
- Les prix/stocks/variantes sont **toujours** revérifiés côté serveur (jamais le prix du navigateur).
- Uploads : extension + MIME réel + taille max + nom aléatoire + `php_flag engine off` dans `uploads/`.
- Sessions : HttpOnly, SameSite=Lax, régénération après login et toutes les 30 min.
