# Script de Démo en Direct — Application Work Tracking
**Audience :** Équipe de M. Kemtio
**Durée :** 60–75 min | **Vous pilotez, ils observent**
**Histoire :** Vous êtes le **Directeur de CERD Africa**. C'est votre journée de travail.

---

## Préparation avant la présentation (à faire avant l'arrivée du public)

```bash
# Réinitialiser la base de données avec des données réalistes
php artisan migrate:fresh --seed

# Démarrer les 4 serveurs (4 terminaux)
php artisan serve
npm run dev
php artisan queue:work
php artisan reverb:start
```

**Ouvrir 3 onglets dans le navigateur et se connecter avant l'arrivée du public :**

| Onglet | Email | Mot de passe | Rôle dans la démo |
|--------|-------|--------------|-------------------|
| Onglet 1 | `directeur@worktracking.com` | `password` | Vous — le Directeur |
| Onglet 2 | `collaborateur@worktracking.com` | `password` | Kofi Mensah — un collaborateur |
| Onglet 3 | `superadmin@worktracking.com` | `password` | L'administrateur plateforme |

---

## L'histoire

> *"CERD Africa est une organisation en pleine croissance avec trois départements :*
> - *La **Direction Générale** (RH & Stratégie) — 5 projets actifs*
> - *Le **Département Technique** (IT & Infrastructure) — 4 projets dont une migration cloud critique*
> - *Le **Pôle Innovation** — 3 projets expérimentaux (chatbot, BI, RPA)*
>
> *Vous êtes le Directeur. Voici votre journée."*

---

## Scène 1 — Votre tableau de bord du matin
**Onglet :** 1 (directeur) | **Page :** `/` | **Durée :** ~5 min

### Ce qu'il faut dire et exactement sur quoi cliquer :

**1. Arriver sur le tableau de bord.**
> "Première chose le matin, j'ouvre mon tableau de bord. Quatre indicateurs me donnent immédiatement l'état de santé de mon espace de travail."

Pointer chaque carte :
- **Projets actifs** → affiche vos 5 projets actifs, avec une flèche de tendance vs le mois dernier
- **Mes tâches** → nombre de tâches qui vous sont personnellement assignées
- **Taux de complétion** → taux global calculé sur toutes les tâches (données réelles de la BD)
- **Tâches en retard** → affiché en rouge — ces tâches nécessitent une attention immédiate

> "Ces chiffres sont en temps réel — calculés depuis les données réelles à chaque chargement de page. Les flèches indiquent si la situation s'améliore ou se dégrade par rapport au mois précédent."

**2. Tableau Kanban.**
> "Sous les indicateurs, mes projets sont présentés en tableau — Actifs à gauche, Terminés à droite."

Vous verrez des cartes pour :
- Déploiement ERP RH & Paie
- Portail Client Self-Service
- Gouvernance Documentaire
- Stratégie Communication Interne

> "Je peux faire glisser le projet 'Gouvernance Documentaire' vers Terminé pour vous montrer comment ça fonctionne."
Faire glisser la carte. Elle s'anime en douceur.
La remettre en place.

**3. Graphique de progression mensuelle.**
> "Ce graphique en aire montre trois mois d'activité — total projets, tâches créées et tâches terminées. Je vois immédiatement si mon équipe tient ses engagements ou prend du retard."

**4. Panneaux latéraux.**
> "À droite : ma liste de tâches personnelles pour aujourd'hui, et mes membres d'équipe — chacun avec le nombre de tâches en cours."

Pointer Éric Kouassi, Aïcha Traoré, Kofi Mensah — chacun avec un badge de comptage.

---

## Scène 2 — La hiérarchie (Projet → Activité → Tâche)
**Onglet :** 1 (directeur) | **Durée :** ~10 min

### Étape 1 — Ouvrir un projet
Naviguer vers **Barre latérale → Projets → Mes Projets**.

> "La Direction Générale de CERD Africa gère 5 projets. Ouvrons le plus critique — le déploiement ERP."

Cliquer sur **"Déploiement ERP RH & Paie"**.

On voit le détail du projet : description, dates (commencé il y a 3 mois, se termine dans 4 mois), responsable (Éric Kouassi), et la liste des activités.

### Étape 2 — Entrer dans une activité
> "Un projet est divisé en Activités — des phases de travail. Ce projet ERP en a 3 phases."

On voit :
- ✅ **Cadrage et analyse des besoins** — Terminé
- 🔵 **Paramétrage et développement** — En cours
- ⬜ **Tests et mise en production** — Pas encore commencé

Cliquer sur **"Paramétrage et développement"**.

> "Regardons la phase en cours."

### Étape 3 — La liste des tâches (vue tableau)
On arrive sur le détail de l'activité. La liste des tâches affiche :

| Tâche | Avancement | Statut | Priorité |
|-------|-----------|--------|----------|
| Configurer le module paie | 45% | EN_COURS | CRITIQUE |
| Développer le module de reporting RH | 30% | EN_COURS | Normal |
| Mettre en place la gestion des congés | 0% | À_FAIRE | Normal |
| Configurer l'interface de gestion des contrats | 0% | À_FAIRE | Normal |
| Développer le portail self-service employé | 20% | **EN_RETARD** | ÉLEVÉE |

> "Cinq tâches. Quatre sont dans les délais. Mais cette dernière — le portail self-service employé — est en retard. Elle aurait dû avancer davantage."

### Étape 4 — Édition en ligne (en direct, sans modal)
> "Je peux corriger le statut directement dans le tableau. Regardez."

Cliquer sur la cellule **EN_RETARD** de la tâche portail self-service.
→ Un menu déroulant apparaît. Changer en `EN_COURS`.
→ Sauvegarde instantanée, la cellule se met à jour.

Cliquer sur la cellule **priorité** → changer en `CRITIQUE`.
→ Sauvegarde instantanée.

> "Pas de formulaire, pas de bouton sauvegarder, pas de rechargement de page. Édition directe sur place."

### Étape 5 — Ouvrir le détail complet de la tâche
Cliquer sur le nom de la tâche **"Développer le portail self-service employé"**.

Parcourir chaque onglet :

**Onglet Info :**
> "La fiche complète de la tâche. Avancement à 20% — le responsable est Kofi Mensah. L'échéance était la semaine dernière."
Pointer : description, barre taux_réalisation, badge priorité, date d'échéance (en rouge car en retard), membres assignés.

**Onglet Sous-tâches :**
> "Cette tâche est décomposée en 3 sous-tâches avec progression pondérée."

| Sous-tâche | Avancement | Statut |
|-----------|-----------|--------|
| Préparation et analyse | 40% | TERMINÉ |
| Exécution et développement | 60% | EN_COURS |
| Livraison et validation | 0% | À_FAIRE |

> "La barre de progression de la tâche parente est calculée automatiquement depuis ces sous-tâches. Quand les trois sont terminées, la tâche est automatiquement marquée comme complète."

**Onglet Commentaires :**
> "Il y a déjà un commentaire d'Éric Kouassi expliquant le retard."

On verra un commentaire du type :
> *"Le portail self-service est bloqué en attente du module d'authentification — prévu pour la semaine prochaine."*

> "L'équipe communique le contexte directement sur la tâche. Tout est traçable."

**Onglet Documents :**
> "Tout fichier lié à cette tâche peut y être attaché."

Téléverser un fichier de test (un PDF ou Word depuis votre bureau).
→ Il apparaît dans la liste avec le nom de l'auteur, la date et la taille.

**Onglet Activité (audit) :**
> "Chaque modification de cette tâche est enregistrée de manière immuable — qui a changé quoi, et quand."

On voit le changement de statut que vous venez d'effectuer (EN_RETARD → EN_COURS) avec horodatage.

---

## Scène 3 — Les rôles et permissions en action
**Onglets :** 1 et 2 | **Durée :** ~8 min

### Étape 1 — Vue restreinte du collaborateur
Basculer sur **l'Onglet 2** (Kofi Mensah — collaborateur).

> "Voyons ce que Kofi — un collaborateur — voit quand il se connecte."

Comparer la barre latérale avec l'Onglet 1 (directeur) :
- **Pas** de menu "Tous les Projets" (voit uniquement ses tâches assignées)
- **Pas** de "Tâches du Workspace" (réservé aux managers et plus)
- **Pas** de "Tableau de Bord Évaluation"
- **Pas** de section "Administration"

> "L'interface s'adapte complètement au rôle. Un collaborateur ne voit que ce qu'il est autorisé à voir. Ce n'est pas seulement visuel — le serveur l'impose aussi."

### Étape 2 — Tenter d'accéder à une URL restreinte directement
Sur l'Onglet 2 (collaborateur), taper `/admin/dashboard` directement dans la barre d'adresse. Appuyer sur Entrée.

→ Redirection vers la page **403 Non Autorisé**.

> "Même si Kofi connaît l'URL, il ne peut pas y accéder. Le serveur rejette la requête — ce n'est pas seulement un bouton caché."

### Étape 3 — Vue globale des tâches (pouvoir directeur)
Revenir sur **l'Onglet 1** (directeur). Naviguer vers **Barre latérale → Tâches du Workspace** (`/workspace/taches`).

> "En tant que Directeur, j'ai une vue d'ensemble — toutes les tâches de toutes les activités de mon workspace. Je peux filtrer, trier et rechercher."

Filtrer par **Statut : EN_RETARD** → toutes les tâches en retard de TOUS les projets apparaissent en une seule liste.

> "C'est la vue de pilotage. Je n'ai pas besoin de cliquer dans chaque projet pour identifier les problèmes."

Filtrer par **Projet : Déploiement ERP RH & Paie** → restreint aux tâches de ce projet.

Cliquer **Exporter Excel** → télécharge un tableur des tâches filtrées.

> "Un seul clic — un export Excel complet de ce que je consulte."

---

## Scène 4 — Le circuit de validation + Notifications en temps réel
**Onglets :** 1 et 2 | **Durée :** ~10 min

> "CERD Africa utilise un circuit de validation formalisé. Un collaborateur soumet ses résultats de travail. Son supérieur N0 examine et approuve ou renvoie. Puis le Directeur N1 confirme. Rien n'est accepté sans approbation — et chaque action est horodatée."

### Étape 1 — Kofi soumet un résultat
Basculer sur **l'Onglet 2** (Kofi Mensah — collaborateur).

Naviguer vers **Mes Tâches** → trouver **"Configurer le module paie"** (45%, EN_COURS).

Cliquer **Soumettre un résultat**.

Remplir le formulaire :
- **Description du résultat :** `"Configuration du module paie effectuée à 45%. Les règles de calcul des cotisations sociales sont paramétrées. En attente de validation des règles fiscales."`
- **Avancement :** faire glisser le curseur à 45%
- Joindre un document (ex. un PDF de test)

Cliquer **Soumettre**.

> "Kofi vient de soumettre son travail pour révision. Regardez ce qui se passe sur l'autre onglet."

### Étape 2 — La notification en temps réel se déclenche
Basculer immédiatement sur **l'Onglet 1** (directeur).

> "Regardez l'icône cloche dans la barre supérieure."

La cloche affiche un **badge rouge avec "1"** (notification non lue).

Cliquer la cloche → le menu déroulant s'ouvre et affiche :
> *"Kofi Mensah a soumis un résultat pour : Configurer le module paie"*
Avec horodatage : il y a quelques secondes.

> "En temps réel. Sans rechargement de page. La notification est arrivée par WebSocket au moment même où Kofi a cliqué Soumettre."

Cliquer la notification → redirige directement vers la page du résultat.

### Étape 3 — Le N0 examine la soumission
Sur la page du résultat, le directeur voit :
- La description de Kofi
- Le document joint
- Le niveau d'avancement à 45%
- Deux boutons d'action : **Approuver** et **Renvoyer**

**Option A — Approuver :**
Cliquer **Approuver**.
→ Le résultat passe dans la file de validation N1.
> "Approuvé. Le résultat est maintenant dans ma file N1 pour confirmation finale."

**Option B (plus percutant — à privilégier) :**
Cliquer **Renvoyer**.
Saisir un commentaire : `"La documentation des règles fiscales est manquante. Merci de joindre le fichier de paramétrage complet avant validation."`
Cliquer Soumettre.

> "Je renvoie avec un commentaire précis. Kofi sera notifié immédiatement."

Basculer sur **l'Onglet 2** (Kofi) → la cloche affiche une nouvelle notification :
> *"Votre résultat a été renvoyé par Test Directeur — Configurer le module paie"*

> "Kofi sait exactement ce qu'il doit corriger. Le circuit continue jusqu'à ce que le travail soit conforme au standard."

### Étape 4 — Afficher la piste d'audit
Revenir sur **l'Onglet 1**, ouvrir le **Journal d'audit** de la tâche.

> "Chaque étape de ce circuit est enregistrée en permanence — heure de soumission, heure du rejet, le commentaire, l'acteur. C'est votre registre de conformité."

---

## Scène 5 — Communication d'équipe et chat en temps réel
**Onglet :** 1 (directeur) | **Durée :** ~5 min

Naviguer vers **Barre latérale → Équipes**.

> "Au-delà des tâches, les équipes de CERD Africa communiquent en temps réel — comme une messagerie intégrée."

Cliquer dans une équipe (ex. **"Équipe Direction Générale"**).

> "C'est un chat d'équipe en temps réel. Les messages apparaissent instantanément pour tous les membres."

Taper et envoyer un message :
`"@Éric Kouassi — le module paie est en retard critique. Merci de prioriser cette semaine."`

> "J'ai utilisé la mention @Éric pour le notifier directement. Il verra une notification sur son icône cloche et recevra un ping dans le chat."

Faire défiler l'historique du chat — les messages précédents sont visibles avec horodatages et avatars.

> "L'historique complet des échanges est conservé. Les nouveaux membres qui rejoignent l'équipe peuvent remonter dans le temps et lire le contexte."

---

## Scène 6 — Recherche et traçabilité complète
**Onglet :** 1 (directeur) | **Durée :** ~5 min

### Palette de commandes globale (Cmd+K)
Appuyer sur **Cmd+K** (ou Ctrl+K sur Windows) depuis n'importe où.

> "Depuis n'importe quel écran de l'application — un raccourci clavier ouvre la recherche globale."

Taper `ERP` → les résultats apparaissent instantanément :
- Le projet "Déploiement ERP RH & Paie"
- Des tâches mentionnant ERP
- Des documents attachés aux tâches ERP

Appuyer sur **Échap**.

### Page de recherche complète
Cliquer l'**icône de recherche** dans la barre supérieure, ou naviguer vers `/search`.

Taper `sécurité` → résultats groupés par type :
- **Projets :** "Audit et Renforcement Sécurité SI" (Workspace 2 — Département Technique)
- **Tâches :** "Réaliser le pentest du SI" (55%, EN_COURS, CRITIQUE), "Mettre en place le MFA sur tous les accès" (0%, À_FAIRE)
- **Documents :** tout fichier lié à la sécurité

Cliquer sur l'onglet **Tâches** → uniquement les tâches.
Cliquer sur **"Mettre en place le MFA sur tous les accès"** → un modal de prévisualisation en lecture seule s'ouvre.

> "Je peux prévisualiser n'importe quel résultat sans quitter la page de recherche."

Fermer le modal.

Cliquer **Exporter** → choisir la taille → confirmer → un fichier Excel se télécharge avec tous les résultats.

> "8 types de contenu sont indexés : projets, activités, tâches, sous-tâches, documents, messages d'équipe, utilisateurs et notifications. Tout est recherchable."

---

## Scène 7 — Performance et évaluation
**Onglet :** 1 (directeur) | **Durée :** ~8 min

### Tableau de bord des évaluations
Naviguer vers **Barre latérale → Évaluations → Tableau de Bord** (`/evaluations/tableau-de-bord`).

> "Voici ma vue d'ensemble des performances de l'équipe — calculées automatiquement depuis l'historique de validation. Aucune saisie manuelle."

**Section Top Performers :**
> "Les 5 meilleurs collaborateurs sont classés avec or, argent et bronze — scores calculés sur 8 critères : taux de complétion, respect des délais, qualité des résultats, validation au premier essai, et plus encore."

Pointer les couleurs de score :
- Vert (bon score) → ex. Éric Kouassi
- Jaune (moyen)
- Rouge (à surveiller)

**Section Alertes :**
> "Deux types d'alertes automatiques :"
- **Escalades abusives** (drapeau rouge) → un membre qui contourne répétitivement le circuit normal
- **Taux d'inaction élevé** (orange) → un membre avec des tâches assignées mais aucune soumission récente

Modifier le **filtre de période** → les scores se mettent à jour.
> "Je peux analyser n'importe quelle période — hebdomadaire, mensuelle, trimestrielle."

### Fiche individuelle de l'agent
Cliquer sur le nom d'un membre (ex. Kofi Mensah).

→ S'ouvre `/evaluations/personnel/{id}/historique`

> "Chaque membre de l'équipe a une fiche d'évaluation individuelle."

Afficher :
- **Score global** en haut avec indicateur de couleur
- **8 barres de critères** — chaque critère sous forme de barre horizontale avec valeur
- **Historique de validation** — liste paginée de chaque résultat soumis par Kofi, avec issue (approuvé, rejeté, renvoyé)

> "Le management dispose d'une vue objective et documentée de la performance de chaque personne — basée sur les résultats réels, pas sur des impressions subjectives."

### Performance de l'équipe
Naviguer vers **Performance Équipe**.
Sélectionner l'activité **"Paramétrage et développement"** dans le menu déroulant.

> "Vue de performance au niveau de l'activité : comment se porte chaque membre sur cette phase de travail spécifique ?"

On voit un tableau :

| Membre | Total | Terminées | En cours | En retard | Taux |
|--------|-------|-----------|----------|-----------|------|
| Kofi Mensah | 3 | 0 | 2 | 1 | 33% |
| Éric Kouassi | 2 | 1 | 1 | 0 | 50% |

Chaque ligne a une **barre de progression intégrée** (verte/orange/rouge selon le pourcentage).

> "D'un coup d'œil, je sais qui produit et qui a besoin de soutien."

---

## Scène 8 — Gestion des documents
**Onglet :** 1 (directeur) | **Durée :** ~3 min

Naviguer vers **Barre latérale → Documents → Documents du Workspace**.

> "Chaque document téléversé sur n'importe quelle tâche, activité ou projet de ce workspace est accessible ici — une bibliothèque documentaire centralisée."

Afficher la liste des documents : nom, type, auteur, date, projet/tâche lié.

Filtrer par **Projet : Déploiement ERP RH & Paie** → restreint aux documents ERP.

Cliquer un document → prévisualisation s'ouvre.

Cliquer **Partager par Email** :
- Saisir une adresse externe (ex. un auditeur ou client)
- Cliquer Envoyer

> "Je peux partager un document avec quelqu'un d'externe — un auditeur, un client, un partenaire — sans lui donner accès à l'application. Il reçoit un lien par email."

---

## Scène 9 — Sécurité : Authentification à deux facteurs (MFA)
**Onglet :** 1 (directeur) | **Durée :** ~4 min

Naviguer vers **Profil** (avatar en haut à droite → Profil) → **Onglet Sécurité**.

> "CERD Africa exige que tous les utilisateurs protègent leur compte avec l'authentification à deux facteurs."

**Activer TOTP :**
Cliquer **"Activer TOTP"**.
→ Un QR code apparaît avec une clé de configuration manuelle en dessous.

> "L'utilisateur scanne ce QR code avec Google Authenticator ou Authy sur son téléphone. À partir de ce moment, chaque connexion nécessite le mot de passe ET un code à 6 chiffres qui change toutes les 30 secondes."

*(Si vous avez votre téléphone prêt : scanner et saisir le code. Sinon : montrer le QR et continuer.)*

Cliquer Continuer → saisir un code → confirmer.

→ **Les codes de récupération** apparaissent avec une animation en cascade — 8 codes, chacun dans un bloc monospace.

> "Les codes de récupération sont à usage unique — stockés en sécurité par l'utilisateur en cas de perte de son téléphone. Le système les génère à la configuration."

> "Avec le MFA actif : même si un mot de passe est compromis, le compte est protégé. L'attaquant aurait également besoin de l'accès physique au téléphone de l'utilisateur."

---

## Scène 10 — Préférences de notifications et email
**Onglet :** 1 (directeur) | **Durée :** ~3 min

Naviguer vers **Barre latérale → Préférences de Notifications** (`/notification-preferences`).

> "Chaque utilisateur contrôle exactement quels événements le notifient, et par quel canal."

Afficher les interrupteurs groupés par type d'événement :
- Tâche assignée → in-app ✅, email ✅, push ✅
- Résultat soumis → in-app ✅, email ✅
- Résultat approuvé → in-app ✅
- Essai expirant → email ✅ (signal fort, toujours envoyé)

Afficher le panneau **Heures de silence** :
> "L'utilisateur peut définir des plages horaires sans notification — par exemple pas de notification entre 22h et 7h. En dehors de ces heures, tout arrive en temps réel."

Afficher le panneau **Notifications Push Web** :
> "Si l'utilisateur a autorisé les notifications navigateur, il reçoit des alertes même quand l'application est fermée — comme une notification mobile sur ordinateur."

Naviguer vers **Barre latérale → Notifications** (`/notifications`).

> "Le centre de notifications regroupe tout — lu et non lu, avec horodatages et liens directs vers la tâche ou l'événement concerné."

Cliquer une notification → elle passe en lue et navigue vers la ressource.
Cliquer **Tout marquer comme lu**.

---

## Scène 11 — Abonnements, facturation et limites de ressources
**Onglet :** 1 (directeur) | **Durée :** ~5 min

Naviguer vers **Barre latérale → Abonnement** (`/workspaces/{id}/subscription`).

> "Chaque workspace fonctionne sur un plan d'abonnement. En ce moment, le workspace Direction Générale est en période d'essai de 30 jours."

Afficher :
- Jours restants dans l'essai (ex. "12 jours restants")
- Membres utilisés vs limite
- Stockage utilisé vs limite

Naviguer vers **Abonnement → Plans** (`/subscription/plans`).

> "Trois plans disponibles :"

Pointer chaque carte :
- **Gratuit** → 0 XAF, 5 membres max, 100 Mo de stockage
- **Starter** → 15 000 XAF/mois, 25 membres, 5 Go de stockage
- **Pro** → 50 000 XAF/mois, membres illimités, stockage illimité

> "La tarification est en XAF — la devise locale. Les plans sont conçus pour les PME africaines."

Cliquer **"Choisir"** sur le plan Pro → le modal de paiement s'ouvre.

> "Le paiement se fait via MTN Mobile Money ou Orange Money — les deux principaux opérateurs de paiement mobile de la région."

Afficher le modal :
- Sélection du fournisseur : MTN MoMo / Orange Money
- Champ numéro de téléphone

> "L'utilisateur saisit son numéro de téléphone. MTN envoie une demande de paiement push sur son téléphone. Au moment où il confirme sur son téléphone, notre système reçoit un webhook de MTN, le vérifie indépendamment, et active le plan. Nous ne faisons jamais confiance au navigateur seul — l'activation est confirmée par webhook."

Fermer le modal.

**Démonstration du dépassement de limite :**
> "Voyons ce qui se passe quand un workspace dépasse les limites de son plan."

Naviguer vers **Paramètres du Workspace → Membres** → cliquer **Inviter un membre** → saisir un email.

→ Si le workspace est sur le plan Gratuit et a déjà 5 membres, un toast d'erreur s'affiche :
> *"Limite atteinte — votre plan actuel autorise 5 membres maximum. Passez à un plan supérieur pour inviter davantage de collaborateurs."*

> "Le serveur impose cette limite sur chaque requête. L'interface le reflète, mais le serveur est le vrai gardien."

---

## Scène 12 — Administration de la plateforme
**Onglet :** 3 (superadmin) | **Durée :** ~5 min

Basculer sur **l'Onglet 3** (`superadmin@worktracking.com`).

Naviguer vers **Admin → Tableau de Bord Plateforme** (`/admin/dashboard`).

> "Le super administrateur voit l'intégralité de la plateforme — chaque workspace, chaque utilisateur, chaque abonnement — dans un seul tableau de bord."

Pointer les cartes de statistiques :

**Statistiques Workspaces (6 cartes) :**
- Total workspaces : 3
- Actifs : 3
- En essai : 2
- Payants : 1
- Expirant bientôt : 1
- Expirés : 0

**Statistiques Utilisateurs (4 cartes) :**
- Total utilisateurs : 16
- Actifs ces 30 derniers jours
- Nouveaux cette semaine
- Super admins : 1

**Tableau Workspaces récents :**
Affiche "Direction Générale", "Département Technique", "Pôle Innovation" avec badges d'abonnement (Essai/Payant), nombres de membres, et comptes à rebours.

> "L'admin voit quels workspaces sont sur le point d'expirer — pour intervenir proactivement."

Naviguer vers **Admin → Workspaces**.

> "Je peux gérer n'importe quel workspace depuis ici."

Trouver "Direction Générale" → cliquer **Prolonger l'essai** → saisir 15 jours → confirmer.
→ Toast : "Essai étendu de 15 jours. Le directeur a été notifié par email."

Naviguer vers **Admin → Plans**.

> "Je gère le catalogue des plans d'abonnement — je peux créer de nouveaux plans, ajuster les prix et limites, ou archiver des plans obsolètes."

Afficher les 3 plans (Gratuit, Starter, Pro) dans un tableau avec boutons d'édition.

Naviguer vers **Admin → Utilisateurs**.

> "Répertoire complet des utilisateurs — les 16 utilisateurs de tous les workspaces, recherchables par nom ou email."

Naviguer vers **Admin → Journaux** → cliquer l'onglet **Journal d'Audit de Validation**.

> "Chaque action de validation N0, N1 — approbation ou renvoi — est enregistrée ici de façon permanente. Horodatage, acteur, tâche cible, action effectuée. C'est le registre de conformité — il ne peut pas être modifié ni supprimé."

Filtrer par plage de dates → afficher les résultats filtrés.

---

## Scène 13 — Centre d'aide
**Onglet :** 1 (directeur) | **Durée :** ~2 min

Naviguer vers **Barre latérale → Aide** (`/help`).

> "Les utilisateurs disposent d'un centre d'aide intégré — bilingue français/anglais, et entièrement recherchable."

Afficher la grille de catégories (icônes + titres + nombres d'articles).

Cliquer une catégorie → liste des articles.
Cliquer un article → contenu en texte enrichi avec sections formatées.

Taper une requête dans la barre de recherche du centre d'aide (ex. `"validation"`) → les articles pertinents apparaissent instantanément.

> "Le contenu de l'aide est également inclus dans la recherche globale — les utilisateurs n'ont pas besoin de savoir où chercher."

Basculer sur **l'Onglet 3** (superadmin) → **Admin → Articles d'Aide**.

> "Les administrateurs rédigent le contenu d'aide avec un éditeur de texte enrichi. Les articles s'auto-sauvegardent en brouillon toutes les 5 secondes. Si l'auteur quitte et revient, une bannière propose de restaurer le brouillon non sauvegardé."

---

## Conclusion — Ce qu'ils ne voient pas (oral uniquement, sans clic)
**Durée :** ~2 min

> "Derrière tout ce que vous venez de voir :
>
> - **840+ tests automatisés** s'exécutent à chaque modification du code — aucune fonctionnalité n'est livrée sans être testée
> - **L'analyse statique** détecte les erreurs de type avant qu'elles n'atteignent la production
> - L'application entière est **bilingue** — chaque libellé, notification, email et message d'erreur existe en français et en anglais
> - Toutes les vérifications de permissions sont effectuées **côté serveur** — l'interface n'est qu'un miroir, pas le gardien
> - Chaque page est **responsive mobile** — la même application fonctionne sur téléphone, tablette et ordinateur
> - Le **mode sombre** est pris en charge sur toutes les pages"

---

## Guide de durée

| Scène | Fonctionnalité | Min |
|-------|---------------|-----|
| 1 | Tableau de bord — indicateurs, Kanban, graphique | 5 |
| 2 | Hiérarchie complète + édition en ligne + sous-tâches + audit | 10 |
| 3 | Rôles & permissions — vue restreinte + blocage serveur | 8 |
| 4 | Circuit de validation + notification en temps réel | 10 |
| 5 | Chat d'équipe + @mentions | 5 |
| 6 | Recherche globale (Cmd+K + page) + export Excel | 5 |
| 7 | Tableau évaluations + fiche agent + performance équipe | 8 |
| 8 | Gestion documentaire + partage par email | 3 |
| 9 | MFA / Authentification à deux facteurs | 4 |
| 10 | Préférences de notifications + email + push | 3 |
| 11 | Plans d'abonnement + paiement MTN/Orange + limites | 5 |
| 12 | Administration plateforme — workspaces, utilisateurs, audit | 5 |
| 13 | Centre d'aide | 2 |
| — | Conclusion | 2 |
| **Total** | | **~75 min** |

**Pour réduire à 60 min :** supprimer les Scènes 8, 10 et 13.

---

## Risques à éviter

| Risque | Solution |
|--------|---------|
| La cloche ne se met pas à jour en temps réel | `php artisan reverb:start` doit être actif |
| L'export Excel se bloque | `php artisan queue:work` doit être actif |
| La recherche ne retourne rien | Chercher `"ERP"`, `"sécurité"` ou `"monitoring"` — présents dans les données de démo |
| Le modal de paiement affiche une erreur | Attendu en dev — montrer l'interface et expliquer le flux webhook verbalement |
| La démo de limite d'abonnement ne se déclenche pas | Vérifier que le workspace est sur le plan Gratuit (max 5 membres) après le seed |
| Mauvais onglet actif | Garder les onglets étiquetés : Onglet 1 = Directeur, Onglet 2 = Kofi, Onglet 3 = Superadmin |
