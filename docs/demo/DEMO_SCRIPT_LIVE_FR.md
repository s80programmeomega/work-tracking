# Script de Démo en Direct — Saisie Réelle des Données
**Audience :** Équipe de M. Kemtio
**Durée :** 60–75 min | **Vous pilotez, vous créez tout en direct**
**Approche :** Pas de données pré-chargées. Vous partez d'une base vide et construisez
l'organisation CERD Africa sous les yeux du public.

---

## Préparation avant la présentation

```bash
# Réinitialiser sans données (juste les rôles, plans et permissions)
php artisan migrate:fresh && php artisan db:seed --class=RolePermissionSeeder && php artisan db:seed --class=PlanSeeder


# Démarrer les 4 serveurs
php artisan serve
npm run dev
php artisan queue:work
php artisan reverb:start
```

**Ouvrir 2 onglets navigateur :**

| Onglet | Email | Mot de passe | Usage |
|--------|-------|--------------|-------|
| Onglet 1 | *(vous vous inscrivez en direct)* | — | Compte Directeur |
| Onglet 2 | `superadmin@worktracking.com` | `password` | Compte Admin plateforme |

> **Note :** L'onglet superadmin est pré-connecté car ce compte est créé par le RolePermissionSeeder.
> Tout le reste est créé devant le public.

---

## L'histoire

> *"Je vais vous montrer comment CERD Africa utilise cette plateforme depuis le premier jour —
> en créant l'organisation, les projets et les équipes en direct, exactement comme votre équipe
> le ferait le jour du déploiement."*

---

## Acte 1 — Création du compte Directeur
**Durée :** ~3 min | **Page :** `/signup`

### Ce qu'il faut dire et exactement quoi saisir :

Ouvrir `/signup` sur l'Onglet 1.

> "Première étape — le Directeur crée son compte. Je saisis ses informations."

Remplir le formulaire :

| Champ | Valeur à saisir |
|-------|----------------|
| Prénom | `Jean-Baptiste` |
| Nom | `Kemtio` |
| Email | `directeur@cerd-africa.com` |
| Mot de passe | `Cerd@2025!` |
| Confirmation | `Cerd@2025!` |
| Conditions d'utilisation | ✅ cocher |

Cliquer **S'inscrire**.

→ Redirection vers le tableau de bord — vide pour l'instant.

> "Le compte est créé. Nous sommes maintenant connectés en tant que Directeur de CERD Africa.
> Le tableau de bord est vide — normal, nous n'avons encore rien créé."

Montrer l'écran vide :
- Kanban : *"Aucun projet trouvé"*
- Mes tâches : *"Aucune tâche assignée — vous êtes à jour !"*
- Membres : *"Aucun membre — invitez des membres à rejoindre le workspace"*

> "C'est le point de départ de n'importe quelle organisation."

---

## Acte 2 — Création de l'espace de travail
**Durée :** ~3 min | **Page :** `/workspaces/create`

Naviguer vers **Sidebar → Workspaces → Créer un workspace**.

> "Nous créons maintenant l'espace de travail principal de CERD Africa —
> la Direction Générale."

Remplir le formulaire :

| Champ | Valeur à saisir |
|-------|----------------|
| Nom du workspace | `Direction Générale — CERD Africa` |
| Description | `Espace de pilotage des projets stratégiques de la direction.` |
| Visibilité par défaut des projets | `Équipe` |
| ☑ Validation des tâches requise | cocher |
| ☑ Les membres peuvent créer des projets | laisser décoché |

Cliquer **Créer**.

→ Redirection vers le workspace nouvellement créé.

> "L'espace de travail est créé. Remarquez que le tableau de bord affiche maintenant
> le nom 'Direction Générale — CERD Africa'. Nous sommes propriétaires de cet espace."

---

## Acte 3 — Invitation des membres de l'équipe
**Durée :** ~5 min

> "Un espace de travail sans équipe ne sert à rien. Invitons les membres."

Naviguer vers **Paramètres du Workspace → Membres** ou cliquer le bouton **Inviter des membres**.

### Invitation 1 — Le Manager de projet
Cliquer **Inviter un membre**.

| Champ | Valeur |
|-------|--------|
| Email | `eric.kouassi@cerd-africa.com` |
| Rôle | `Manager` |
| Message | `Bienvenue dans notre espace de travail, Éric. Vous serez responsable du suivi des projets.` |

Cliquer **Inviter**.

→ Toast de confirmation : invitation envoyée.

> "Éric Kouassi reçoit un email avec un lien d'invitation. En attendant qu'il accepte,
> continuons à construire la structure."

### Invitation 2 — La Cadre
Sans fermer la zone d'invitation, en ajouter une autre :

| Champ | Valeur |
|-------|--------|
| Email | `aicha.traore@cerd-africa.com` |
| Rôle | `Cadre` |

Cliquer **Inviter**.

### Invitation 3 — Le Collaborateur (Kofi)
| Champ | Valeur |
|-------|--------|
| Email | `kofi.mensah@cerd-africa.com` |
| Rôle | `Collaborateur` |

Cliquer **Inviter**.

> "Trois invitations envoyées. Chaque personne reçoit un email personnalisé avec un lien
> pour rejoindre l'espace. Le système gère les relances automatiquement si l'invitation expire."

Montrer la liste des invitations en attente dans l'onglet **Invitations**.

> "Je vois ici toutes les invitations en attente — avec la date d'expiration et le rôle attribué.
> Je peux relancer ou annuler à tout moment."

---

## Acte 4 — Acceptation d'une invitation (en direct)
**Durée :** ~3 min

> "Montrons maintenant ce que voit Kofi quand il reçoit son invitation."

Ouvrir un **onglet de navigation privée** (Ctrl+Shift+N).

Naviguer vers `/signup` dans cet onglet.

> "Kofi reçoit l'email, clique sur le lien, et crée son compte."

Créer le compte Kofi :

| Champ | Valeur |
|-------|--------|
| Prénom | `Kofi` |
| Nom | `Mensah` |
| Email | `kofi.mensah@cerd-africa.com` |
| Mot de passe | `Kofi@2025!` |

Cliquer **S'inscrire**.

→ Le système détecte l'invitation en attente et lui propose de rejoindre le workspace.

Cliquer **Accepter l'invitation**.

→ Kofi arrive sur le tableau de bord de la Direction Générale.

> "Kofi est maintenant membre de l'espace de travail. Remarquez que son interface est
> différente de celle du Directeur — il ne voit pas les mêmes menus."

Montrer rapidement la barre latérale de Kofi (moins d'options).

Fermer l'onglet navigation privée. Revenir sur l'Onglet 1 (Directeur).

---

## Acte 5 — Création du premier projet
**Durée :** ~4 min | **Page :** `/projets/create`

Naviguer vers **Sidebar → Projets → Mes Projets → Créer un projet**.

> "Créons maintenant le premier projet stratégique de CERD Africa."

Remplir le formulaire :

| Champ | Valeur à saisir |
|-------|----------------|
| Nom du projet | `Déploiement ERP RH & Paie` |
| Description | `Déploiement et paramétrage du système ERP pour la gestion des ressources humaines et de la paie.` |
| Date de début | *(aujourd'hui)* |
| Date de fin | *(dans 6 mois)* |
| Responsable | `Jean-Baptiste Kemtio` (vous-même) |
| Statut | `Actif` |
| Visibilité | `Équipe` |

Cliquer **Créer le projet**.

→ Page de détail du projet.

> "Le projet est créé. Ajoutons Éric comme responsable de ce projet."

Cliquer **Ajouter des membres** dans la page du projet → sélectionner Kofi Mensah → rôle `Collaborateur` → confirmer.

> "Kofi est maintenant assigné à ce projet. Il recevra une notification."

---

## Acte 6 — Création d'une activité
**Durée :** ~3 min

Depuis la page du projet, cliquer **Nouvelle activité** (ou naviguer dans le projet).

> "Un projet est découpé en Activités — des phases de travail. Créons la première."

Remplir le formulaire :

| Champ | Valeur à saisir |
|-------|----------------|
| Nom de l'activité | `Cadrage et analyse des besoins` |
| Projet | `Déploiement ERP RH & Paie` *(pré-sélectionné)* |
| Description | `Recueil et formalisation des besoins RH, finance et DSI.` |
| Responsable | `Jean-Baptiste Kemtio` |
| Priorité | `Haute` |
| Date de début | *(aujourd'hui)* |
| Date de fin | *(dans 3 semaines)* |
| Statut | `En cours` |

Cliquer **Créer**.

→ L'activité apparaît dans le projet.

> "La hiérarchie commence à prendre forme : Direction Générale → ERP RH & Paie →
> Cadrage et analyse des besoins. Ajoutons maintenant les tâches concrètes."

---

## Acte 7 — Création d'une tâche via l'assistant (wizard)
**Durée :** ~5 min

Depuis la page de l'activité, cliquer **Nouvelle tâche**.

→ L'assistant de création s'ouvre en 4 étapes.

> "La création d'une tâche est guidée en 4 étapes. Voyons ça."

### Étape 1 — Informations de base

| Champ | Valeur à saisir |
|-------|----------------|
| Titre | `Rédiger le cahier des charges fonctionnel` |
| Description | `Formaliser toutes les exigences validées avec les parties prenantes RH, finance et DSI.` |
| Objectif | `Produire un CDC signé par toutes les parties avant le démarrage du paramétrage.` |
| Priorité | `Élevée` |
| Échéance | *(dans 10 jours)* |

Cliquer **Suivant**.

> "Étape 1 validée. Passons à l'assignation."

### Étape 2 — Assignation

| Champ | Valeur |
|-------|--------|
| Responsable | `Kofi Mensah` |
| Autres intervenants | *(laisser vide pour l'instant)* |

Cliquer **Suivant**.

> "Kofi est responsable de cette tâche. Il recevra une notification par email et dans l'application."

### Étape 3 — Ressources

> "On peut joindre des fichiers ou des liens à la tâche dès la création."

Ajouter un lien externe :
- URL : `https://docs.google.com/...` *(n'importe quelle URL)* 
- Titre : `Modèle de CDC RH`

Cliquer **Suivant**.

### Étape 4 — Options de validation

Cocher les deux cases :
- ☑ **Validation N1 requise** (par le cadre)
- ☑ **Validation N2 requise** (par le manager)

> "Cette tâche doit être validée par deux niveaux hiérarchiques avant d'être considérée
> comme terminée. C'est le circuit de validation de CERD Africa."

Vérifier le récapitulatif affiché :
- Titre ✅
- Priorité : Élevée ✅
- Échéance ✅
- Responsable : Kofi Mensah ✅

Cliquer **Créer la tâche**.

→ La tâche apparaît dans la liste de l'activité.

> "La tâche est créée. Kofi vient de recevoir une notification — regardons ça."

---

## Acte 8 — Notification en temps réel (démonstration)
**Durée :** ~3 min

Ouvrir un **nouvel onglet de navigation privée** → se connecter avec `kofi.mensah@cerd-africa.com`.

→ Sur le tableau de bord de Kofi, la **cloche affiche un badge rouge**.

> "Kofi vient de recevoir une notification : une tâche lui a été assignée."

Cliquer la cloche → le menu déroulant affiche :
> *"Vous avez été assigné à : Rédiger le cahier des charges fonctionnel"*

> "En temps réel, sans rechargement de page. La notification est arrivée par WebSocket
> au moment exact où j'ai cliqué Créer."

Cliquer la notification → Kofi arrive directement sur la tâche.

> "Le lien dans la notification amène directement à la ressource concernée.
> Pas de recherche, pas de navigation manuelle."

Fermer l'onglet navigation privée.

---

## Acte 9 — Édition en ligne et sous-tâches
**Durée :** ~4 min

Revenir sur l'Onglet 1 (Directeur). Ouvrir la tâche **"Rédiger le cahier des charges fonctionnel"**.

> "Regardons le détail de cette tâche. Je vais d'abord ajuster la priorité directement
> dans le tableau."

Revenir sur la liste des tâches de l'activité.

Cliquer la cellule **Priorité** de la tâche → changer de `Élevée` à `Critique`.
→ Sauvegarde instantanée, la cellule se met à jour.

Cliquer la cellule **Statut** → changer à `EN_COURS`.
→ Sauvegarde instantanée.

> "Édition directe dans le tableau — pas de modal, pas de rechargement."

Ouvrir la tâche complète. Aller dans **l'onglet Sous-tâches**.

> "Je vais décomposer cette tâche en étapes concrètes."

Créer 3 sous-tâches :

**Sous-tâche 1 :**
| Champ | Valeur |
|-------|--------|
| Titre | `Interviewer les responsables RH` |
| Poids | `30` |
| Échéance | *(dans 3 jours)* |

**Sous-tâche 2 :**
| Champ | Valeur |
|-------|--------|
| Titre | `Interviewer les responsables Finance` |
| Poids | `30` |
| Échéance | *(dans 5 jours)* |

**Sous-tâche 3 :**
| Champ | Valeur |
|-------|--------|
| Titre | `Rédiger et faire valider le document final` |
| Poids | `40` |
| Échéance | *(dans 10 jours)* |

→ La barre de progression de la tâche parente affiche **0%** (aucune sous-tâche terminée).

Cocher la première sous-tâche comme terminée → barre passe à **30%** automatiquement.

> "La progression est calculée automatiquement selon le poids de chaque sous-tâche.
> 30% car la première sous-tâche pèse 30% du total."

---

## Acte 10 — Le circuit de validation (soumission de résultat)
**Durée :** ~7 min

> "Simulons maintenant le flux complet de validation. Kofi a terminé son travail
> et soumet ses résultats."

Ouvrir un **nouvel onglet navigation privée** → se connecter avec `kofi.mensah@cerd-africa.com`.

Naviguer vers **Mes Tâches** → ouvrir **"Rédiger le cahier des charges fonctionnel"**.

Cliquer **Soumettre un résultat**.

Remplir le formulaire de soumission :

| Champ | Valeur à saisir |
|-------|----------------|
| Résultats attendus | `Cahier des charges fonctionnel signé par toutes les parties prenantes.` |
| Résultats obtenus | `CDC rédigé et soumis à validation. 12 entretiens conduits (RH, Finance, DSI). Document de 47 pages couvrant tous les modules ERP requis.` |
| Taux de réalisation | `85%` *(faire glisser le curseur)* |
| Difficultés rencontrées | `Disponibilité limitée du directeur financier — 2 réunions reportées.` |
| Solutions envisagées | `Sessions en visioconférence organisées pour les profils indisponibles en présentiel.` |

Joindre un fichier (un PDF quelconque depuis le bureau).

Cliquer **Soumettre**.

→ Confirmation : résultat soumis.

> "Kofi vient de soumettre ses résultats. Le Directeur est notifié immédiatement."

Fermer l'onglet navigation privée. Revenir sur l'Onglet 1 (Directeur).

**La cloche affiche un nouveau badge rouge.**

Cliquer la cloche → notification :
> *"Kofi Mensah a soumis un résultat pour : Rédiger le cahier des charges fonctionnel"*

Cliquer la notification → arriver sur le résultat de Kofi.

> "Le directeur voit le résumé complet de Kofi, le document joint, et les 85% de complétion."

Cliquer **Renvoyer**.

Saisir le commentaire de renvoi :
`"Le fichier de paramétrage des modules fiscaux est manquant. Merci de compléter la section 4 du CDC et de le soumettre à nouveau."`

Cliquer **Renvoyer**.

> "Je renvoie avec un commentaire précis. Kofi sait exactement quoi corriger.
> Voyons la piste d'audit."

Aller dans l'**onglet Activité** de la tâche.

→ On voit la ligne :
- Soumission par Kofi Mensah — `[heure]`
- Renvoyé par Jean-Baptiste Kemtio — `[heure]` — avec le commentaire

> "Chaque action est enregistrée de façon immuable. C'est votre registre de conformité."

---

## Acte 11 — Recherche globale
**Durée :** ~3 min

Appuyer sur **Cmd+K** (ou Ctrl+K).

> "Depuis n'importe quel écran, un raccourci clavier ouvre la recherche globale."

Taper `ERP` → résultats instantanés :
- Le projet **Déploiement ERP RH & Paie**
- La tâche **Rédiger le cahier des charges fonctionnel**

> "Tout ce que nous venons de créer est déjà indexé et recherchable.
> Projects, tâches, documents, messages — tout."

Appuyer sur **Échap**. Naviguer vers `/search`.

Taper `cahier des charges` → la tâche apparaît dans les résultats.

Cliquer sur le résultat → prévisualisation en lecture seule s'ouvre.

> "Je peux prévisualiser n'importe quel résultat sans quitter la page de recherche."

---

## Acte 12 — Communication d'équipe
**Durée :** ~3 min

Naviguer vers **Sidebar → Équipes → Créer une équipe**.

Remplir :

| Champ | Valeur |
|-------|--------|
| Nom | `Équipe ERP — Direction Générale` |
| Description | `Canal de coordination pour le projet de déploiement ERP RH & Paie.` |
| Responsable | `Jean-Baptiste Kemtio` |

Cliquer **Créer**.

→ Arriver sur la page de l'équipe avec le chat vide.

> "L'équipe est créée. C'est le canal de communication en temps réel pour ce projet."

Taper et envoyer un message :
`"Bonjour à tous. Le CDC est en cours de finalisation par Kofi. Réunion de revue prévue vendredi à 14h."`

> "Le message est envoyé. Tous les membres de l'équipe le voient en temps réel."

Ajouter Kofi comme membre de l'équipe.

Ouvrir un onglet navigation privée → se connecter comme Kofi → naviguer vers la même équipe.

→ L'équipe et le message apparaissent déjà dans l'interface de Kofi.

Kofi envoie un message :
`"Compris. Je finalise les sections manquantes cet après-midi."`

→ Sur l'Onglet 1 (Directeur), le message de Kofi apparaît instantanément — **sans rechargement**.

> "Temps réel. Les deux utilisateurs voient les messages de l'autre sans aucun rechargement."

Fermer l'onglet navigation privée.

---

## Acte 13 — Sécurité : Activation du MFA
**Durée :** ~3 min

Naviguer vers **Profil** → **Onglet Sécurité**.

> "CERD Africa exige le MFA pour tous les comptes à responsabilité."

Cliquer **Activer TOTP**.

→ QR code affiché.

> "Je scanne ce code avec Google Authenticator sur mon téléphone."

*(Scanner si vous avez votre téléphone, ou juste montrer le QR.)*

Saisir le code à 6 chiffres → Confirmer.

→ Les **codes de récupération** apparaissent en cascade (animation stagger).

> "8 codes de récupération générés — à conserver en lieu sûr.
> Désormais, même si mon mot de passe est compromis, mon compte reste protégé."

---

## Acte 14 — Rôles, permissions et blocage serveur
**Durée :** ~4 min

> "Voyons maintenant la gestion des rôles et la protection côté serveur."

Naviguer vers **Workspace → Utilisateurs** (`/workspace/users`).

→ On voit Kofi Mensah dans la liste avec son rôle `Collaborateur`.

Cliquer **Modifier le rôle** de Kofi → changer à `Observateur`.

→ Toast de confirmation.

> "Kofi est maintenant Observateur. Voyons l'impact immédiat sur son interface."

Ouvrir un onglet navigation privée → se connecter comme Kofi.

→ Sa barre latérale est encore plus restreinte : il ne peut plus créer de tâches ni soumettre de résultats.

Tenter d'accéder à `/admin/dashboard` directement dans l'URL.

→ Redirection vers la **page 403 Non Autorisé**.

> "Le serveur rejette la requête. Ce n'est pas seulement un bouton caché — la protection
> est réelle et s'applique à chaque requête HTTP."

Fermer l'onglet navigation privée.

Remettre Kofi en `Collaborateur`.

---

## Acte 15 — Abonnements et limites
**Durée :** ~4 min

Naviguer vers **Abonnement → Plans** (`/subscription/plans`).

> "CERD Africa est actuellement sur le plan Gratuit — limité à 5 membres."

Montrer les 3 cartes de plans :
- **Gratuit** → 0 XAF, 5 membres max, 100 Mo
- **Starter** → 15 000 XAF/mois, 25 membres, 5 Go
- **Pro** → 50 000 XAF/mois, illimité

> "Nous avons déjà 4 membres dans notre workspace. Tentons d'en inviter un 5ème, puis un 6ème."

Inviter un 5ème membre (succès).

Tenter d'inviter un 6ème :

→ Toast d'erreur :
> *"Limite atteinte — votre plan actuel autorise 5 membres maximum. Passez à un plan
> supérieur pour inviter davantage de collaborateurs."*

> "La limite est imposée par le serveur. L'interface l'affiche clairement."

Cliquer **Choisir** sur le plan Pro → modal de paiement s'ouvre.

> "Le paiement se fait via MTN Mobile Money ou Orange Money."

Montrer la sélection du fournisseur et le champ numéro de téléphone.

> "L'utilisateur saisit son numéro. MTN envoie une demande de paiement sur son téléphone.
> Notre système attend la confirmation webhook avant d'activer le plan —
> nous ne faisons jamais confiance au navigateur seul."

Fermer le modal.

---

## Acte 16 — Vue Administration plateforme
**Durée :** ~4 min

Basculer sur **l'Onglet 2** (`superadmin@worktracking.com`).

Naviguer vers **Admin → Tableau de Bord Plateforme** (`/admin/dashboard`).

> "Le super administrateur voit l'intégralité de la plateforme."

Pointer les statistiques :

| Statistiques Workspaces | Valeur |
|------------------------|--------|
| Total workspaces | 1 *(celui qu'on vient de créer)* |
| Actifs | 1 |
| En essai | 1 |

| Statistiques Utilisateurs | Valeur |
|--------------------------|--------|
| Total utilisateurs | 3 *(directeur + Kofi + les invités)* |
| Actifs ces 30 derniers jours | 3 |
| Nouveaux cette semaine | 3 |

> "Tout ce que nous venons de créer est visible ici — en temps réel."

Naviguer vers **Admin → Workspaces**.

Trouver **"Direction Générale — CERD Africa"** → cliquer **Prolonger l'essai** → saisir 30 jours → confirmer.

→ Toast : essai prolongé, le directeur est notifié par email.

> "L'administrateur peut gérer n'importe quel workspace sans se connecter avec son compte."

Naviguer vers **Admin → Journaux → Journal d'Audit de Validation**.

→ On voit toutes les actions de validation qu'on vient d'effectuer :
- Soumission de Kofi
- Renvoi par le Directeur avec le commentaire

> "Ce journal est immuable. Il ne peut pas être modifié ni supprimé.
> C'est la preuve de conformité de votre organisation."

---

## Acte 17 — Centre d'aide (création d'un article)
**Durée :** ~3 min

Toujours sur l'Onglet 2 (superadmin). Naviguer vers **Admin → Articles d'Aide → Créer un article**.

> "Le responsable peut documenter les procédures directement dans l'application."

Remplir :

| Champ | Valeur |
|-------|--------|
| Catégorie | `Gestion des tâches` *(créer si nécessaire)* |
| Titre (FR) | `Comment soumettre un résultat de tâche` |
| Titre (EN) | `How to submit a task result` |
| Corps (FR) | *Taper quelques lignes dans l'éditeur riche* : `"Pour soumettre un résultat, ouvrez la tâche concernée et cliquez sur Soumettre un résultat. Remplissez les champs requis..."` |

Attendre 6 secondes sans cliquer.

→ Le message **"Brouillon enregistré à [heure]"** apparaît automatiquement sous l'éditeur.

> "La sauvegarde automatique des brouillons est active — toutes les 5 secondes.
> L'auteur ne perd jamais son travail."

Fermer l'onglet et le rouvrir.

→ Une bannière apparaît : **"Un brouillon non publié existe — Restaurer / Ignorer"**

> "Si l'auteur ferme accidentellement la page, il retrouve son brouillon au retour."

Cliquer **Restaurer** → le contenu réapparaît.

Basculer sur l'Onglet 1 (Directeur). Naviguer vers `/help`.

→ L'article n'est pas encore visible (non publié).

Revenir sur l'Onglet 2 → cliquer **Publier l'article**.

Revenir sur l'Onglet 1 → actualiser `/help`.

→ L'article apparaît maintenant dans le centre d'aide.

> "Les articles publiés sont immédiatement accessibles à tous les membres."

---

## Acte 18 — Ticket de support
**Durée :** ~2 min

Sur l'Onglet 1 (Directeur). Naviguer vers **Sidebar → Support** (`/support`).

> "Si un utilisateur rencontre un problème, il soumet un ticket directement depuis l'application."

Remplir :

| Champ | Valeur |
|-------|--------|
| Catégorie | `Bug` |
| Sujet | `Impossible de joindre un fichier PDF supérieur à 5 Mo` |
| Message | `Lors de la soumission d'un résultat de tâche, les fichiers PDF de plus de 5 Mo sont rejetés sans message d'erreur clair. Reproduit sur Chrome et Firefox.` |
| Reproductibilité | `Toujours` |

Cliquer **Soumettre**.

→ Le ticket apparaît immédiatement dans **Mes tickets** avec un numéro (#TKT-001) et le statut `Ouvert`.

> "Le ticket est créé. L'équipe support reçoit une notification et peut répondre
> directement depuis le panneau d'administration."

---

## Conclusion — Ce qu'ils viennent de voir
**Durée :** ~2 min | Oral uniquement, sans clic

> "En 60 minutes, à partir d'une base complètement vide, nous avons construit :
>
> - **Une organisation complète** avec 3 rôles distincts
> - **Un projet réel** avec une activité et une tâche décomposée en sous-tâches
> - **Un circuit de validation formel** — soumission, renvoi, piste d'audit immuable
> - **Des notifications en temps réel** — WebSocket, sans rechargement
> - **Une protection complète** — les rôles sont imposés côté serveur, pas juste masqués
> - **Un système de paiement** intégré (MTN / Orange Money)
> - **Un centre d'aide** avec auto-sauvegarde de brouillons
> - **Un support intégré** avec numérotation des tickets
>
> Et derrière tout ça :
> - **840+ tests automatisés** sur chaque modification du code
> - L'application entière est **bilingue** — français et anglais
> - Chaque page est **responsive mobile**
> - Le **mode sombre** est disponible partout"

---

## Guide de durée

| Acte | Action | Min |
|------|--------|-----|
| 1 | Inscription du Directeur | 3 |
| 2 | Création du workspace | 3 |
| 3 | Invitation des membres | 5 |
| 4 | Acceptation d'une invitation (en direct) | 3 |
| 5 | Création du projet | 4 |
| 6 | Création de l'activité | 3 |
| 7 | Création de la tâche (wizard 4 étapes) | 5 |
| 8 | Notification en temps réel | 3 |
| 9 | Édition en ligne + sous-tâches | 4 |
| 10 | Circuit de validation complet | 7 |
| 11 | Recherche globale (Cmd+K + page) | 3 |
| 12 | Chat d'équipe en temps réel | 3 |
| 13 | Activation MFA | 3 |
| 14 | Rôles, permissions et blocage serveur | 4 |
| 15 | Abonnements et limites | 4 |
| 16 | Administration plateforme | 4 |
| 17 | Centre d'aide (article + auto-save) | 3 |
| 18 | Ticket de support | 2 |
| — | Conclusion | 2 |
| **Total** | | **~70 min** |

**Pour réduire à 55 min :** supprimer les Actes 13, 17 et 18.

---

## Données à préparer avant la présentation

Aucune donnée pré-chargée — tout est créé en direct. Mais préparez ces éléments :

| À préparer | Détail |
|-----------|--------|
| Un fichier PDF de test | Pour la soumission de résultat (Acte 10) et le ticket (Acte 18) |
| Votre téléphone avec Google Authenticator | Pour l'Acte 13 (MFA) — optionnel mais plus impressionnant |
| Les valeurs à saisir imprimées | Ce script, ou les tableaux de valeurs ci-dessus sur votre téléphone |
| Une URL quelconque en presse-papier | Pour le lien externe de la tâche (Acte 7, Étape 3) |

---

## Risques à éviter

| Risque | Solution |
|--------|---------|
| Email d'invitation n'arrive pas à temps | Ouvrir l'onglet privé et s'inscrire avec le même email — le système détecte l'invitation automatiquement |
| La cloche ne se met pas à jour en temps réel | `php artisan reverb:start` doit être actif |
| L'export ou les emails ne partent pas | `php artisan queue:work` doit être actif |
| Oubli des valeurs à saisir | Garder ce script ouvert sur un 2ème écran ou imprimé |
| Limite d'abonnement ne se déclenche pas | Vérifier que le plan Gratuit est assigné au workspace (max 5 membres) |
| Chat temps réel ne fonctionne pas | Reverb doit être actif ; tester avant la présentation |
