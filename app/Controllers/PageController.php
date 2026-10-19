<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\View;
use App\Repositories\SettingRepository;

/** Pages statiques : contact (Phase 2), à propos / légales (Phase 8). */
final class PageController
{
    public function contact(): never
    {
        View::echo('shop/contact', [
            'title' => 'Contact — MASS BUSINESS WEAR',
            'whatsapp' => SettingRepository::whatsapp(),
            'phone' => SettingRepository::get('phone'),
            'email' => SettingRepository::get('email'),
            'address' => SettingRepository::get('address'),
            'hours' => SettingRepository::get('hours'),
            'instagram' => SettingRepository::get('instagram'),
            'facebook' => SettingRepository::get('facebook'),
            'tiktok' => SettingRepository::get('tiktok'),
        ], 'layouts/shop');
    }

    public function about(): never
    {
        View::echo('shop/about', [
            'title' => 'À propos — MASS BUSINESS WEAR',
            'meta' => 'MASS BUSINESS WEAR : prêt-à-porter premium à Dakar. Des pièces soignées, une commande simple sur WhatsApp.',
            'shopName' => SettingRepository::get('shop_name', 'MASS BUSINESS WEAR'),
            'phone' => SettingRepository::get('phone'),
            'email' => SettingRepository::get('email'),
            'address' => SettingRepository::get('address'),
            'hours' => SettingRepository::get('hours'),
        ], 'layouts/shop');
    }

    public function privacy(): never
    {
        View::echo('shop/legal', [
            'title' => 'Politique de confidentialité — MASS BUSINESS WEAR',
            'meta' => 'Comment MASS BUSINESS WEAR collecte, utilise et protège vos données personnelles.',
            'heading' => 'Politique de confidentialité',
            'updated' => '19 octobre 2026',
            'sections' => [
                ['Données collectées', 'Lorsque vous passez commande, nous collectons uniquement les informations nécessaires à la livraison : votre nom, votre numéro de téléphone, votre zone et votre adresse de livraison, ainsi que le détail de votre panier.'],
                ['Utilisation', 'Ces données servent exclusivement à traiter votre commande, vous confirmer sa préparation et assurer la livraison. Elles ne sont ni vendues, ni louées, ni utilisées à des fins publicitaires.'],
                ['Partage', 'Vos coordonnées sont partagées uniquement avec le livreur chargé de votre commande. Aucun autre tiers n\u2019y a accès.'],
                ['Conservation', 'Les données de commande sont conservées pour la gestion commerciale et la comptabilité, pendant la durée légale applicable. Vous pouvez demander leur suppression à tout moment.'],
                ['Vos droits', 'Vous pouvez consulter, corriger ou demander la suppression de vos données en nous écrivant via la page Contact ou sur WhatsApp.'],
                ['Cookies', 'Le site utilise uniquement des cookies techniques nécessaires au panier et à la session. Aucun traceur publicitaire n\u2019est déployé.'],
            ],
        ], 'layouts/shop');
    }

    public function terms(): never
    {
        View::echo('shop/legal', [
            'title' => 'Conditions générales de vente — MASS BUSINESS WEAR',
            'meta' => 'Conditions de vente, de livraison et de retour de la boutique MASS BUSINESS WEAR.',
            'heading' => 'Conditions générales de vente',
            'updated' => '19 octobre 2026',
            'sections' => [
                ['Objet', 'Les présentes conditions régissent les ventes réalisées sur la boutique MASS BUSINESS WEAR. Toute commande implique leur acceptation sans réserve.'],
                ['Commandes', 'La commande est confirmée après validation de votre panier et de vos coordonnées. Un récapitulatif vous est envoyé sur WhatsApp pour confirmation finale. Les prix sont indiqués en FCFA, livraison comprise selon la zone choisie.'],
                ['Paiement', 'Le paiement s\u2019effectue à la livraison : vous réglez en espèces au livreur une fois la commande reçue et vérifiée.'],
                ['Livraison', 'Les livraisons sont assurées dans les zones desservies, sous 24 à 72 h ouvrées selon votre localisation. En cas d\u2019imprévable, nous vous prévenons sur WhatsApp.'],
                ['Disponibilité', 'Les produits sont proposés dans la limite des stocks disponibles. En cas de rupture, vous en êtes informé et la commande est adaptée ou remboursée.'],
                ['Échanges et retours', 'Tout article non porté, avec étiquette, peut être échangé dans les 7 jours suivant la réception, sur présentation de la commande. Les articles personnalisés ou soldés ne sont ni repris ni échangés.'],
                ['Litiges', 'En cas de problème, contactez-nous en priorité via la page Contact : nous nous engageons à trouver une solution amiable dans les meilleurs délais.'],
            ],
        ], 'layouts/shop');
    }
}
