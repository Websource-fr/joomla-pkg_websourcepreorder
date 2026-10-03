# Changelog

## 1.0.0 — 2026-10-03
- Première version publique, port Joomla / HikaShop du module PrestaShop « Websource Précommandes ».
- Package unique `pkg_websourcepreorder` : composant `com_websourcepreorder`, plugin système `plg_system_websourcepreorder`, plugin de tâche planifiée `plg_task_websourcepreorder`, modules `mod_websourcepreorder_featured` (produit à la une), `mod_websourcepreorder_ticker` (bandeau défilant) et `mod_websourcepreorder_subscribe` (formulaire d'alerte).
- Règles de précommande par produit ou variante HikaShop : activation, début, date de sortie, clôture automatique à la sortie, fin anticipée, quota total, maximum par commande et par client, option « interdire le mélange » précommandes / produits en stock.
- Prix de précommande (prix fixe HT, remise en %, remise en montant HT) appliqué par l'événement de prix HikaShop `onAfterLoadProductPrice` : le produit n'est jamais modifié.
- Commande hors stock pendant la précommande (quantité HikaShop passée à illimitée) puis restauration exacte de l'état d'origine à la clôture ; bouton « Disponible maintenant ».
- Réservations enregistrées à la création de la commande, annulation / remboursement qui libère le quota, e-mail de confirmation (client et administrateur), e-mail « produit disponible » à la sortie.
- Écrans d'administration : tableau de bord, règles, réservations (filtres, export CSV, renvoi d'e-mail, annulation), abonnés (ajout manuel, confirmation, suppression, export CSV), réglages par onglets.
- Affichage : badge et bloc fiche produit (date, compte à rebours en 5 modes, barre de progression, quantité restante, message), bouton d'achat renommé, badge dans les listes, notice panier, page publique « Toutes les précommandes » groupée par mois, page « Mes précommandes ».
- Blocs « prochaines précommandes » et formulaire d'alerte sur les pages connexion, inscription et mot de passe oublié de `com_users`.
- Formulaire d'alerte e-mail : double opt-in, case RGPD, pot de miel, délai minimal, limite par IP, jeton CSRF Joomla, désinscription en un clic.
- Récapitulatif périodique (x fois par jour / semaine / mois) par tâche planifiée Joomla et par URL de cron à jeton, envois par lots avec reprise, jamais d'envoi à vide.
- E-mails entièrement personnalisables par langue, enveloppe HTML aux couleurs du site, bouton « envoyer un test ».
- Neuf couleurs paramétrables (variables CSS `--wspo-*`), layouts surchargeables dans `html/layouts/com_websourcepreorder`.
- Encart « Besoin d'aller plus loin ? » (accompagnement Websource) sur le tableau de bord du composant : visible uniquement dans l'administration pour les utilisateurs ayant `core.admin` ou `core.manage`, bouton « Masquer » (30 jours, localStorage), aucune requête externe ; layout unique réutilisable `administrator/layouts/websource_support.php`.
- Traductions fr-FR et en-GB. Désinstallation propre avec option « conserver les données ».
- Testé sur Joomla 5.4.9 (PHP 8.5), Joomla 4.4.14 (PHP 8.1) et Joomla 6.1.4 (PHP 8.5) avec HikaShop Starter 6.6.0.
