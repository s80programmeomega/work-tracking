# Dossier d'Audit — Soumission du Projet

> **Projet :** Work Tracking v2.0
> **Date de constitution :** 2026-06-11
> **Branche de référence initiale :** `main`
> **Branche de livraison :** `jonas`

---

## Contenu du dossier

| Fichier | Description |
|---|---|
| [01_AUDIT_ETAT_INITIAL_MAIN.md](01_AUDIT_ETAT_INITIAL_MAIN.md) | Photographie complète du projet tel qu'il existait sur `main` avant toute intervention — pile technique, modèle de données, sécurité, performances, lacunes identifiées. |
| [02_RAPPORT_TRAVAUX_BRANCH_JONAS.md](02_RAPPORT_TRAVAUX_BRANCH_JONAS.md) | Récapitulatif exhaustif de tout ce qui a été réalisé sur `jonas` — 32 phases / tâches, 297 commits, 1 310 fichiers modifiés, +227 139 lignes, tableau comparatif avant/après. |
| [03_SEEDERS_PRODUCTION.md](03_SEEDERS_PRODUCTION.md) | Détail de chaque seeder : obligatoire vs démo, ordre d'exécution, procédure de déploiement production recommandée. |

---

## Chiffres clés

| Indicateur | `main` | `jonas` |
|---|---|---|
| Commits | 410 | 707 (+297) |
| Migrations DB | 68 | 103 (+35) |
| Tests PHPUnit | ~274 | **811 passés / 1 flake** |
| Tests Dusk | 0 | 20+ |
| Erreurs Larastan | ~154 | **0** |
| Bundle JS | ~1 MB | **197 KB (−81 %)** |
| Requêtes DB dashboard | ~74 | **~34 (−54 %)** |
| Packages Composer backend | 10 | 23 (+13) |

---

## Comment lire ce dossier

1. Commencer par **01_AUDIT_ETAT_INITIAL_MAIN.md** pour comprendre le point de départ.
2. Lire **02_RAPPORT_TRAVAUX_BRANCH_JONAS.md** pour suivre chaque phase de développement et mesurer l'écart entre l'état initial et l'état livré.
