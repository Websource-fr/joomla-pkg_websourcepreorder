# Websource Précommandes — package Joomla pour HikaShop

Système **complet et paramétrable** de précommandes pour **HikaShop** : date de sortie, compte à rebours, prix de précommande, quotas, produit à la une, bandeau défilant, page « Toutes les précommandes », alertes e-mail avec double opt-in, récapitulatif périodique par tâche planifiée ou cron, e-mails personnalisables. Port du [module PrestaShop Websource Précommandes](https://github.com/Websource-fr/websourcepreorder).

- **Éditeur** : [Websource](https://www.websource.fr)
- **Version** : 1.0.0 — **Licence** : usage libre non commercial (voir [LICENSE](LICENSE))
- **Guide utilisateur (PDF)** : [docs/guide-utilisateur.pdf](docs/guide-utilisateur.pdf)

## Contenu du package

| Extension | Rôle |
|---|---|
| `com_websourcepreorder` | Composant : règles, réservations, abonnés, réglages, page publique « Toutes les précommandes », « Mes précommandes », URL de cron |
| `plg_system_websourcepreorder` | Prix de précommande, stock, limites du panier, réservations à la commande, e-mails, blocs des pages de comptes, synchronisation automatique |
| `plg_task_websourcepreorder` | Tâches planifiées Joomla : synchronisation et récapitulatif périodique |
| `mod_websourcepreorder_featured` | Produit en précommande à la une (position au choix) |
| `mod_websourcepreorder_ticker` | Bandeau défilant des produits en précommande |
| `mod_websourcepreorder_subscribe` | Formulaire d'alerte e-mail (pied de page, accueil, fiche produit, colonnes) |

## Compatibilité

| | |
|---|---|
| Joomla | 4.4, 5.x, 6.x |
| PHP | 8.1 à 8.5 |
| HikaShop | 6.x (version Starter gratuite suffisante) |
| Templates | Cassiopeia et tout template standard ; layouts surchargeables (`html/layouts/com_websourcepreorder`) |

**Niveau de validation** : installation d'un seul coup, règles, prix, commandes réelles, limites, annulation, clôture, e-mails, alertes, récapitulatifs, tâches planifiées, modules, pages de comptes, réglages et désinstallation testés de bout en bout sur Joomla 5.4.9 avec HikaShop Starter 6.6.0 ; parcours principaux (installation, prix, limites, commande, réservation, administration, e-mails) rejoués sur Joomla 4.4.14 (PHP 8.1) et Joomla 6.1.4. Sur Joomla 4.4, la commande CLI `scheduler:run` du cœur échouait dans notre environnement (hors package) : tâche planifiée non validée sur 4.4. Voir « Limites connues » dans le guide.

## Installation

1. Téléchargez `joomla-pkg_websourcepreorder.zip` (page [Releases](../../releases)).
2. *Système → Installer → Extensions* : glissez le zip. Les six extensions sont installées d'un coup, les plugins sont activés.
3. *Composants → Websource Précommandes → Réglages* : configurez, puis créez une première règle (*Précommandes → Nouvelle précommande*).
4. Planifiez le cron (tâche planifiée Joomla ou URL affichée dans *Réglages → Cron & récapitulatif*).

Construire le zip depuis les sources : `./build.sh` (nécessite `zip` et `python3`).

## Fonctionnalités

- **Précommandes par produit ou variante** : début, date de sortie, clôture automatique, fin anticipée, activation.
- **Compte à rebours** (automatique, jours, jours+heures, +minutes, +secondes) et barre de progression ; horloge du serveur (pas de dérive du navigateur).
- **Prix de précommande** : prix fixe HT, remise %, remise en montant, appliqué à la volée sans modifier le produit.
- **Stock** : commande hors stock pendant la précommande, état d'origine restauré à l'identique à la clôture.
- **Limites** : quota total, maximum par commande et par client, interdiction de mélange avec des produits en stock.
- **Réservations** à la commande, annulation / remboursement qui libère le quota, e-mails confirmation / disponible.
- **Alertes e-mail** : double opt-in, RGPD, anti-spam (pot de miel, délai, limite par IP, CSRF), désinscription en un clic.
- **Récapitulatif périodique** : x fois par jour / semaine / mois, toutes les précommandes ou seulement les nouveautés, lots avec reprise.
- **E-mails personnalisables** par langue avec variables, enveloppe aux couleurs du site, envoi de test.
- **Sécurité** : jetons CSRF, ACL Joomla (`core.manage`, `core.admin`, `core.edit`…), entrées filtrées, requêtes préparées, sorties échappées.

## Personnalisation avancée

Copiez un layout vers votre template pour le modifier :

```
components/com_websourcepreorder/layouts/box.php
  → templates/VOTRE_TEMPLATE/html/layouts/com_websourcepreorder/box.php
```

Layouts disponibles : `box` (fiche produit), `card` (page liste), `subscribe` (formulaire d'alerte), `account_block` (pages de comptes), `cart_notice` (panier). Les couleurs sont des variables CSS (`--wspo-accent`, `--wspo-box-bg`…).

## Encart « accompagnement Websource »

Le tableau de bord du composant affiche, aux seuls utilisateurs de l'administration ayant `core.admin` ou `core.manage`, un encart discret « Besoin d'aller plus loin ? » avec deux boutons (contact et rendez-vous) vers www.websource.fr. Il se masque 30 jours en un clic (localStorage), n'émet aucune requête externe et n'est jamais affiché en front ni dans les e-mails. Seul un clic sur les boutons transmet à Websource le nom et la version de l'extension, la version de Joomla et l'adresse du site (paramètres `utm_*` et `ws_*` de l'URL). Le layout est unique et réutilisable : `com_websourcepreorder/administrator/layouts/websource_support.php`.

## Désinstallation

Les précommandes en cours sont terminées proprement (stock restauré). Par défaut, règles, réservations et abonnés sont **conservés** (réglage *Conserver les données à la désinstallation*) ; désactivez-le avant de désinstaller pour tout supprimer.

## Licence

Usage libre non commercial, voir [LICENSE](LICENSE). Questions : contact@websource.fr.
