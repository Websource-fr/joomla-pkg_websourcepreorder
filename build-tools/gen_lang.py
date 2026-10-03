# -*- coding: utf-8 -*-
import os, sys
sys.path.insert(0, os.path.dirname(__file__))
from lang_com import K
root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
HDR = "; Websource Précommandes %s\n; (C) 2026 Websource. Licence Websource, usage libre non commercial (voir LICENSE)\n; Fichier encodé en UTF-8\n\n"

def esc(v): return v.replace('"', "'")
def write(path, entries, label):
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, 'w', encoding='utf-8') as f:
        f.write(HDR % label)
        for k, v in entries: f.write('%s="%s"\n' % (k, esc(v)))

for idx, tag in ((0, 'fr-FR'), (1, 'en-GB')):
    full = [(k, v[idx]) for k, v in K.items()]
    for base in ('com_websourcepreorder/administrator/language', 'com_websourcepreorder/site/language'):
        write('%s/%s/%s/%s.com_websourcepreorder.ini' % (root, base, tag, tag), full, 'com_websourcepreorder')
    sysk = [('COM_WEBSOURCEPREORDER', K['COM_WEBSOURCEPREORDER'][idx]), ('COM_WEBSOURCEPREORDER_XML_DESCRIPTION', K['COM_WEBSOURCEPREORDER_XML_DESCRIPTION'][idx])]
    for n in ('DASHBOARD', 'RULES', 'BOOKINGS', 'SUBSCRIBERS', 'SETTINGS'):
        sysk.append(('COM_WEBSOURCEPREORDER_SUBMENU_' + n, K['COM_WEBSOURCEPREORDER_SUBMENU_' + n][idx]))
    for n in ('LIST', 'MYPREORDERS'):
        sysk += [('COM_WEBSOURCEPREORDER_VIEW_%s_TITLE' % n, K['COM_WEBSOURCEPREORDER_VIEW_%s_TITLE' % n][idx]), ('COM_WEBSOURCEPREORDER_VIEW_%s_DESC' % n, K['COM_WEBSOURCEPREORDER_VIEW_%s_DESC' % n][idx])]
    write('%s/com_websourcepreorder/administrator/language/%s/%s.com_websourcepreorder.sys.ini' % (root, tag, tag), sysk, 'com_websourcepreorder (sys)')

    # --- plugins
    P = {
     'fr-FR': {'sysname': 'Système - Websource Précommandes', 'sysdesc': "Moteur des précommandes HikaShop : prix de précommande, stock, limites du panier, réservations à la commande, blocs sur les pages de comptes et synchronisation automatique. Réglages dans Composants > Websource Précommandes.",
               'taskname': 'Tâche - Websource Précommandes', 'taskdesc': 'Tâches planifiées des précommandes : synchronisation et récapitulatif périodique.',
               'sync': ('Précommandes : synchroniser', "Ouvre et clôture les précommandes aux bonnes dates et prévient les acheteurs. À planifier toutes les 15 minutes."),
               'digest': ('Précommandes : récapitulatif périodique', "Envoie le récapitulatif aux abonnés quand l'échéance réglée dans le composant est atteinte. À planifier toutes les 15 minutes à 1 heure.")},
     'en-GB': {'sysname': 'System - Websource Pre-orders', 'sysdesc': 'HikaShop pre-order engine: pre-order price, stock, cart limits, bookings at checkout, blocks on account pages and automatic synchronisation. Settings in Components > Websource Pre-orders.',
               'taskname': 'Task - Websource Pre-orders', 'taskdesc': 'Pre-order scheduled tasks: synchronisation and periodic digest.',
               'sync': ('Pre-orders: synchronise', 'Opens and closes pre-orders at the right dates and notifies buyers. Schedule every 15 minutes.'),
               'digest': ('Pre-orders: periodic digest', 'Sends the digest to subscribers when the schedule set in the component is due. Schedule every 15 minutes to 1 hour.')},
    }[tag]
    write('%s/plg_system_websourcepreorder/language/%s/plg_system_websourcepreorder.ini' % (root, tag), [('PLG_SYSTEM_WEBSOURCEPREORDER', P['sysname']), ('PLG_SYSTEM_WEBSOURCEPREORDER_XML_DESCRIPTION', P['sysdesc'])], 'plg_system')
    write('%s/plg_system_websourcepreorder/language/%s/plg_system_websourcepreorder.sys.ini' % (root, tag), [('PLG_SYSTEM_WEBSOURCEPREORDER', P['sysname']), ('PLG_SYSTEM_WEBSOURCEPREORDER_XML_DESCRIPTION', P['sysdesc'])], 'plg_system sys')
    tk = [('PLG_TASK_WEBSOURCEPREORDER', P['taskname']), ('PLG_TASK_WEBSOURCEPREORDER_XML_DESCRIPTION', P['taskdesc']),
          ('PLG_TASK_WEBSOURCEPREORDER_SYNC_TITLE', P['sync'][0]), ('PLG_TASK_WEBSOURCEPREORDER_SYNC_DESC', P['sync'][1]),
          ('PLG_TASK_WEBSOURCEPREORDER_DIGEST_TITLE', P['digest'][0]), ('PLG_TASK_WEBSOURCEPREORDER_DIGEST_DESC', P['digest'][1])]
    write('%s/plg_task_websourcepreorder/language/%s/plg_task_websourcepreorder.ini' % (root, tag, ), tk, 'plg_task') if False else None
    write('%s/plg_task_websourcepreorder/language/%s/plg_task_websourcepreorder.ini' % (root, tag), tk, 'plg_task')
    write('%s/plg_task_websourcepreorder/language/%s/plg_task_websourcepreorder.sys.ini' % (root, tag), tk, 'plg_task sys')

    # --- modules
    M = {
     'fr-FR': {
       'featured': [('MOD_WEBSOURCEPREORDER_FEATURED', 'Websource Précommande à la une'), ('MOD_WEBSOURCEPREORDER_FEATURED_XML_DESCRIPTION', "Met en avant un produit en précommande (image, date de sortie, compte à rebours, prix). À placer en page d'accueil."),
          ('MOD_WEBSOURCEPREORDER_FEATURED_DEFAULT_TITLE', 'Précommande à la une'), ('MOD_WEBSOURCEPREORDER_FEATURED_FIELD_TITLE', 'Titre'), ('MOD_WEBSOURCEPREORDER_FEATURED_FIELD_TITLE_DESC', 'Vide = titre défini dans les réglages du composant.'),
          ('MOD_WEBSOURCEPREORDER_FEATURED_FIELD_FALLBACK', 'Sinon la prochaine sortie'), ('MOD_WEBSOURCEPREORDER_FEATURED_FIELD_FALLBACK_DESC', "Si aucun produit n'est « mis à la une », présente la précommande qui sort le plus tôt."),
          ('MOD_WEBSOURCEPREORDER_FEATURED_FIELD_SUB', "Ajouter le formulaire d'alerte"), ('MOD_WEBSOURCEPREORDER_FEATURED_FIELD_SUB_DESC', "Affiche le formulaire d'alerte e-mail sous le produit.")],
       'ticker': [('MOD_WEBSOURCEPREORDER_TICKER', 'Websource Bandeau des précommandes'), ('MOD_WEBSOURCEPREORDER_TICKER_XML_DESCRIPTION', "Bandeau défilant des produits en précommande. Placez-le dans la position de votre choix (haut de page, sous l'en-tête…). Respecte prefers-reduced-motion."),
          ('MOD_WEBSOURCEPREORDER_TICKER_ARIA', 'Produits en précommande'), ('MOD_WEBSOURCEPREORDER_TICKER_DEFAULT_PREFIX', 'Précommande'),
          ('MOD_WEBSOURCEPREORDER_TICKER_FIELD_PREFIX', 'Préfixe'), ('MOD_WEBSOURCEPREORDER_TICKER_FIELD_PREFIX_DESC', 'Vide = réglage du composant.'),
          ('MOD_WEBSOURCEPREORDER_TICKER_FIELD_MAX', 'Nombre maximum de produits'), ('MOD_WEBSOURCEPREORDER_TICKER_FIELD_MAX_DESC', '0 = réglage du composant.'),
          ('MOD_WEBSOURCEPREORDER_TICKER_FIELD_SPEED', 'Durée du défilement (secondes)'), ('MOD_WEBSOURCEPREORDER_TICKER_FIELD_SPEED_DESC', '0 = réglage du composant. Plus la valeur est grande, plus le bandeau est lent.'),
          ('MOD_WEBSOURCEPREORDER_TICKER_FIELD_PRICE', 'Afficher le prix'),
          ('MOD_WEBSOURCEPREORDER_TICKER_FIELD_HOME_ONLY', "Page d'accueil uniquement"), ('MOD_WEBSOURCEPREORDER_TICKER_FIELD_HOME_ONLY_DESC', "Masque le bandeau sur les autres pages (sinon utilisez l'affectation du module aux éléments de menu).")],
       'subscribe': [('MOD_WEBSOURCEPREORDER_SUBSCRIBE', 'Websource Alerte précommandes'), ('MOD_WEBSOURCEPREORDER_SUBSCRIBE_XML_DESCRIPTION', "Formulaire d'inscription aux alertes e-mail de précommandes (double opt-in, case RGPD, anti-spam). À placer en pied de page, accueil, fiche produit ou colonnes."),
          ('MOD_WEBSOURCEPREORDER_SUBSCRIBE_FIELD_PLACE', 'Emplacement'), ('MOD_WEBSOURCEPREORDER_SUBSCRIBE_FIELD_PLACE_DESC', "Adapte la mise en forme et sert de source dans la liste des abonnés. Choisissez la position du module dans l'onglet Module."),
          ('MOD_WEBSOURCEPREORDER_SUBSCRIBE_PLACE_FOOTER', 'Pied de page'), ('MOD_WEBSOURCEPREORDER_SUBSCRIBE_PLACE_HOME', 'Accueil'), ('MOD_WEBSOURCEPREORDER_SUBSCRIBE_PLACE_PRODUCT', 'Fiche produit'),
          ('MOD_WEBSOURCEPREORDER_SUBSCRIBE_PLACE_COLUMN_LEFT', 'Colonne gauche'), ('MOD_WEBSOURCEPREORDER_SUBSCRIBE_PLACE_COLUMN_RIGHT', 'Colonne droite'),
          ('MOD_WEBSOURCEPREORDER_SUBSCRIBE_FIELD_TITLE', 'Titre'), ('MOD_WEBSOURCEPREORDER_SUBSCRIBE_FIELD_TITLE_DESC', 'Vide = réglage du composant.'),
          ('MOD_WEBSOURCEPREORDER_SUBSCRIBE_FIELD_TEXT', 'Texte'), ('MOD_WEBSOURCEPREORDER_SUBSCRIBE_FIELD_TEXT_DESC', 'Vide = réglage du composant.'),
          ('MOD_WEBSOURCEPREORDER_SUBSCRIBE_FIELD_ONLY', 'Seulement s\'il y a des précommandes'), ('MOD_WEBSOURCEPREORDER_SUBSCRIBE_FIELD_ONLY_DESC', "Masque le formulaire quand aucune précommande n'est ouverte.")],
     },
     'en-GB': {
       'featured': [('MOD_WEBSOURCEPREORDER_FEATURED', 'Websource Featured pre-order'), ('MOD_WEBSOURCEPREORDER_FEATURED_XML_DESCRIPTION', 'Highlights a pre-ordered product (image, release date, countdown, price). Place it on the home page.'),
          ('MOD_WEBSOURCEPREORDER_FEATURED_DEFAULT_TITLE', 'Featured pre-order'), ('MOD_WEBSOURCEPREORDER_FEATURED_FIELD_TITLE', 'Title'), ('MOD_WEBSOURCEPREORDER_FEATURED_FIELD_TITLE_DESC', 'Empty = title from the component settings.'),
          ('MOD_WEBSOURCEPREORDER_FEATURED_FIELD_FALLBACK', 'Otherwise the next release'), ('MOD_WEBSOURCEPREORDER_FEATURED_FIELD_FALLBACK_DESC', 'If no product is "featured", shows the pre-order releasing first.'),
          ('MOD_WEBSOURCEPREORDER_FEATURED_FIELD_SUB', 'Add the alert form'), ('MOD_WEBSOURCEPREORDER_FEATURED_FIELD_SUB_DESC', 'Shows the email alert form under the product.')],
       'ticker': [('MOD_WEBSOURCEPREORDER_TICKER', 'Websource Pre-order ticker'), ('MOD_WEBSOURCEPREORDER_TICKER_XML_DESCRIPTION', 'Scrolling ticker of pre-ordered products. Place it in the position of your choice. Honors prefers-reduced-motion.'),
          ('MOD_WEBSOURCEPREORDER_TICKER_ARIA', 'Pre-ordered products'), ('MOD_WEBSOURCEPREORDER_TICKER_DEFAULT_PREFIX', 'Pre-order'),
          ('MOD_WEBSOURCEPREORDER_TICKER_FIELD_PREFIX', 'Prefix'), ('MOD_WEBSOURCEPREORDER_TICKER_FIELD_PREFIX_DESC', 'Empty = component setting.'),
          ('MOD_WEBSOURCEPREORDER_TICKER_FIELD_MAX', 'Maximum number of products'), ('MOD_WEBSOURCEPREORDER_TICKER_FIELD_MAX_DESC', '0 = component setting.'),
          ('MOD_WEBSOURCEPREORDER_TICKER_FIELD_SPEED', 'Scroll duration (seconds)'), ('MOD_WEBSOURCEPREORDER_TICKER_FIELD_SPEED_DESC', '0 = component setting. The higher the value, the slower the ticker.'),
          ('MOD_WEBSOURCEPREORDER_TICKER_FIELD_PRICE', 'Show price'),
          ('MOD_WEBSOURCEPREORDER_TICKER_FIELD_HOME_ONLY', 'Home page only'), ('MOD_WEBSOURCEPREORDER_TICKER_FIELD_HOME_ONLY_DESC', 'Hides the ticker on other pages (otherwise use the module menu assignment).')],
       'subscribe': [('MOD_WEBSOURCEPREORDER_SUBSCRIBE', 'Websource Pre-order alerts'), ('MOD_WEBSOURCEPREORDER_SUBSCRIBE_XML_DESCRIPTION', 'Pre-order email alert subscription form (double opt-in, GDPR checkbox, anti-spam). Place in the footer, home, product page or columns.'),
          ('MOD_WEBSOURCEPREORDER_SUBSCRIBE_FIELD_PLACE', 'Placement'), ('MOD_WEBSOURCEPREORDER_SUBSCRIBE_FIELD_PLACE_DESC', 'Adapts the layout and is used as the source in the subscribers list. Choose the module position in the Module tab.'),
          ('MOD_WEBSOURCEPREORDER_SUBSCRIBE_PLACE_FOOTER', 'Footer'), ('MOD_WEBSOURCEPREORDER_SUBSCRIBE_PLACE_HOME', 'Home'), ('MOD_WEBSOURCEPREORDER_SUBSCRIBE_PLACE_PRODUCT', 'Product page'),
          ('MOD_WEBSOURCEPREORDER_SUBSCRIBE_PLACE_COLUMN_LEFT', 'Left column'), ('MOD_WEBSOURCEPREORDER_SUBSCRIBE_PLACE_COLUMN_RIGHT', 'Right column'),
          ('MOD_WEBSOURCEPREORDER_SUBSCRIBE_FIELD_TITLE', 'Title'), ('MOD_WEBSOURCEPREORDER_SUBSCRIBE_FIELD_TITLE_DESC', 'Empty = component setting.'),
          ('MOD_WEBSOURCEPREORDER_SUBSCRIBE_FIELD_TEXT', 'Text'), ('MOD_WEBSOURCEPREORDER_SUBSCRIBE_FIELD_TEXT_DESC', 'Empty = component setting.'),
          ('MOD_WEBSOURCEPREORDER_SUBSCRIBE_FIELD_ONLY', 'Only when pre-orders exist'), ('MOD_WEBSOURCEPREORDER_SUBSCRIBE_FIELD_ONLY_DESC', 'Hides the form when no pre-order is open.')],
     }}[tag]
    for m, entries in M.items():
        d = 'mod_websourcepreorder_' + m
        write('%s/%s/language/%s/%s.ini' % (root, d, tag, d), entries, d)
        write('%s/%s/language/%s/%s.sys.ini' % (root, d, tag, d), entries[:2], d + ' sys')

    # --- package
    pk = [('PKG_WEBSOURCEPREORDER', 'Websource Précommandes (HikaShop)' if idx == 0 else 'Websource Pre-orders (HikaShop)'),
          ('PKG_WEBSOURCEPREORDER_XML_DESCRIPTION', "Package complet de précommandes pour HikaShop : composant, plugin système, tâche planifiée et trois modules. Après installation, ouvrez Composants > Websource Précommandes." if idx == 0 else 'Complete HikaShop pre-order package: component, system plugin, scheduled task and three modules. After installation open Components > Websource Pre-orders.'),
          ('PKG_WEBSOURCEPREORDER_NEEDS_HIKASHOP', "HikaShop n'est pas installé : le package est installé mais inactif tant que HikaShop (version Starter gratuite suffisante) n'est pas présent." if idx == 0 else 'HikaShop is not installed: the package is installed but inactive until HikaShop (the free Starter version is enough) is present.'),
          ('PKG_WEBSOURCEPREORDER_INSTALLED', "Websource Précommandes est installé et activé. Étapes suivantes : Composants > Websource Précommandes > Réglages, puis planifiez le cron (voir le guide)." if idx == 0 else 'Websource Pre-orders is installed and enabled. Next: Components > Websource Pre-orders > Settings, then schedule the cron (see the guide).'),
          ('PKG_WEBSOURCEPREORDER_PHP_TOO_OLD', 'Ce package nécessite PHP 8.1 ou supérieur.' if idx == 0 else 'This package requires PHP 8.1 or newer.'),
          ('PKG_WEBSOURCEPREORDER_JOOMLA_TOO_OLD', 'Ce package nécessite Joomla 4.4 ou supérieur.' if idx == 0 else 'This package requires Joomla 4.4 or newer.')]
    write('%s/language/%s/%s.pkg_websourcepreorder.sys.ini' % (root, tag, tag), pk, 'pkg_websourcepreorder sys')
print('ok')
