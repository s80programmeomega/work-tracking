# Task Management System - Complete Implementation Scheme

## 🎯 Project Overview

A comprehensive Trello-like task management system built with **Laravel 10** (backend) and **Vue.js 3** (frontend), featuring hierarchical organization, real-time collaboration, and advanced task tracking capabilities.

---

## 📐 System Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        Client Layer                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   Vue.js 3   │  │  TailwindCSS │  │   PWA/Mobile │      │
│  │  + Vue Router│  │  + Components│  │   Support    │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            ▲
                            │ HTTP/WebSocket
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                     API Gateway Layer                        │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │Laravel Routes│  │  Middleware  │  │    Sanctum   │      │
│  │  (REST API)  │  │   (Auth/CORS)│  │   (Auth)     │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            ▲
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                   Business Logic Layer                       │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ Controllers  │  │   Services   │  │  Repositories│      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   Events     │  │   Listeners  │  │    Jobs      │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            ▲
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                      Data Layer                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │  Eloquent ORM│  │  MySQL/Postgres│ │    Redis     │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            ▲
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                   Infrastructure Layer                       │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ File Storage │  │  Queue Worker│  │  WebSockets  │      │
│  │   (S3/Local) │  │   (Redis)    │  │ (Pusher/Echo)│      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
```

---

## 🗄️ Database Schema

### Core Tables

```sql
-- Users & Authentication
CREATE TABLE `users` (
	`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	`nom` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`prenom` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`nom_complet` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`email` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`is_super_admin` TINYINT(1) NOT NULL DEFAULT '0',
	`email_verified_at` TIMESTAMP NULL DEFAULT NULL,
	`password` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`profile_photo_path` VARCHAR(2048) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`is_active` TINYINT(1) NOT NULL DEFAULT '1',
	`last_login_at` TIMESTAMP NULL DEFAULT NULL,
	`last_login_ip` VARCHAR(45) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`roless` ENUM('super_admin','manager','member','viewer','admin','cadre','stagiaire') NULL DEFAULT 'admin' COLLATE 'utf8mb4_unicode_ci',
	`fonction` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`avatar` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`bio` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`adresse` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`language` VARCHAR(10) NOT NULL DEFAULT 'fr' COLLATE 'utf8mb4_unicode_ci',
	`timezone` VARCHAR(50) NOT NULL DEFAULT 'UTC' COLLATE 'utf8mb4_unicode_ci',
	`notification_preferences` JSON NULL DEFAULT NULL,
	`two_factor_secret` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`two_factor_recovery_codes` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`two_factor_confirmed_at` TIMESTAMP NULL DEFAULT NULL,
	`remember_token` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`created_at` TIMESTAMP NULL DEFAULT NULL,
	`updated_at` TIMESTAMP NULL DEFAULT NULL,
	`deleted_at` TIMESTAMP NULL DEFAULT NULL,
	`current_workspace_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	PRIMARY KEY (`id`) USING BTREE,
	UNIQUE INDEX `users_email_unique` (`email`) USING BTREE,
	INDEX `users_is_active_index` (`is_active`) USING BTREE,
	INDEX `users_current_workspace_id_foreign` (`current_workspace_id`) USING BTREE,
	INDEX `users_role_index` (`roless`) USING BTREE,
	INDEX `idx_super_admin` (`is_super_admin`) USING BTREE,
	CONSTRAINT `users_current_workspace_id_foreign` FOREIGN KEY (`current_workspace_id`) REFERENCES `workspaces` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
AUTO_INCREMENT=7
;

CREATE TABLE `workspaces` (
	`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	`nom` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`description` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`code` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`owner_id` BIGINT(20) UNSIGNED NOT NULL,
	`settings` JSON NULL DEFAULT NULL,
	`is_active` TINYINT(1) NOT NULL DEFAULT '1',
	`logo` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`created_at` TIMESTAMP NULL DEFAULT NULL,
	`updated_at` TIMESTAMP NULL DEFAULT NULL,
	`deleted_at` TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY (`id`) USING BTREE,
	UNIQUE INDEX `workspaces_code_unique` (`code`) USING BTREE,
	INDEX `workspaces_owner_id_index` (`owner_id`) USING BTREE,
	INDEX `workspaces_is_active_index` (`is_active`) USING BTREE,
	INDEX `workspaces_created_at_index` (`created_at`) USING BTREE,
	CONSTRAINT `workspaces_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
AUTO_INCREMENT=9
;

CREATE TABLE `workspace_members` (
	`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	`workspace_id` BIGINT(20) UNSIGNED NOT NULL,
	`user_id` BIGINT(20) UNSIGNED NOT NULL,
	`role` ENUM('owner','admin','member','viewer') NOT NULL DEFAULT 'member' COLLATE 'utf8mb4_unicode_ci',
	`permissions` JSON NULL DEFAULT NULL,
	`invited_at` TIMESTAMP NULL DEFAULT NULL,
	`invited_by` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	`created_at` TIMESTAMP NULL DEFAULT NULL,
	`updated_at` TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY (`id`) USING BTREE,
	UNIQUE INDEX `workspace_members_workspace_id_user_id_unique` (`workspace_id`, `user_id`) USING BTREE,
	INDEX `workspace_members_invited_by_foreign` (`invited_by`) USING BTREE,
	INDEX `workspace_members_workspace_id_index` (`workspace_id`) USING BTREE,
	INDEX `workspace_members_user_id_index` (`user_id`) USING BTREE,
	INDEX `workspace_members_role_index` (`role`) USING BTREE,
	CONSTRAINT `workspace_members_invited_by_foreign` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL,
	CONSTRAINT `workspace_members_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT `workspace_members_workspace_id_foreign` FOREIGN KEY (`workspace_id`) REFERENCES `workspaces` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
AUTO_INCREMENT=14
;
Ex:           
workspace_members.persmission:"{\"can_invite_members\":false,\"can_create_projects\":true,\"can_manage_settings\":false}"

-- Projects (Top-level)
CREATE TABLE `projets` (
	`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	`workspace_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	`nom` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`description` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`code` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`date_debut` DATE NOT NULL DEFAULT '2025-11-07',
	`date_fin` DATE NOT NULL,
	`responsable_id` BIGINT(20) UNSIGNED NOT NULL,
	`budget` DECIMAL(15,2) NULL DEFAULT NULL,
	`progression` INT(10) NOT NULL DEFAULT '0',
	`is_template` TINYINT(1) NOT NULL DEFAULT '0',
	`is_favorite` TINYINT(1) NOT NULL DEFAULT '0',
	`objectifs` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`metadata` JSON NULL DEFAULT NULL,
	`archived_at` TIMESTAMP NULL DEFAULT NULL,
	`status` ENUM('active','archived','completed') NULL DEFAULT 'active' COLLATE 'utf8mb4_unicode_ci',
	`visibility` ENUM('public','private','team') NOT NULL DEFAULT 'team' COLLATE 'utf8mb4_unicode_ci',
	`couleur` VARCHAR(255) NOT NULL DEFAULT '#3B82F6' COLLATE 'utf8mb4_unicode_ci',
	`priorite` ENUM('basse','normale','haute','critique') NOT NULL DEFAULT 'normale' COLLATE 'utf8mb4_unicode_ci',
	`created_at` TIMESTAMP NULL DEFAULT NULL,
	`updated_at` TIMESTAMP NULL DEFAULT NULL,
	`deleted_at` TIMESTAMP NULL DEFAULT NULL,
	`created_by` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	PRIMARY KEY (`id`) USING BTREE,
	UNIQUE INDEX `projets_code_unique` (`code`) USING BTREE,
	INDEX `projets_responsable_id_foreign` (`responsable_id`) USING BTREE,
	INDEX `projets_workspace_id_index` (`workspace_id`) USING BTREE,
	INDEX `projets_created_by_foreign` (`created_by`) USING BTREE,
	CONSTRAINT `projets_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL,
	CONSTRAINT `projets_responsable_id_foreign` FOREIGN KEY (`responsable_id`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT `projets_workspace_id_foreign` FOREIGN KEY (`workspace_id`) REFERENCES `workspaces` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
AUTO_INCREMENT=15
;

CREATE TABLE `projet_user` (
	`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	`projet_id` BIGINT(20) UNSIGNED NOT NULL,
	`user_id` BIGINT(20) UNSIGNED NOT NULL,
	`role` ENUM('owner','admin','member','viewer') NOT NULL DEFAULT 'member' COLLATE 'utf8mb4_unicode_ci',
	`can_edit` TINYINT(1) NOT NULL DEFAULT '0',
	`can_delete` TINYINT(1) NOT NULL DEFAULT '0',
	`can_invite` TINYINT(1) NOT NULL DEFAULT '0',
	`can_delete_member` TINYINT(1) NOT NULL DEFAULT '0',
	`created_at` TIMESTAMP NULL DEFAULT NULL,
	`updated_at` TIMESTAMP NULL DEFAULT NULL,
	`Column 10` INT(10) NULL DEFAULT NULL,
	PRIMARY KEY (`id`) USING BTREE,
	UNIQUE INDEX `projet_user_projet_id_user_id_unique` (`projet_id`, `user_id`) USING BTREE,
	INDEX `projet_user_projet_id_index` (`projet_id`) USING BTREE,
	INDEX `projet_user_user_id_index` (`user_id`) USING BTREE,
	CONSTRAINT `projet_user_projet_id_foreign` FOREIGN KEY (`projet_id`) REFERENCES `projets` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT `projet_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
AUTO_INCREMENT=23
;



-- Activities (Middle-tier)
CREATE TABLE `activites` (
	`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	`projet_id` BIGINT(20) UNSIGNED NOT NULL,
	`nom` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`description` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`code` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`responsable_id` BIGINT(20) UNSIGNED NOT NULL,
	`created_by` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	`date_debut` DATE NULL DEFAULT NULL,
	`date_fin` DATE NULL DEFAULT NULL,
	`ordre` INT(10) NOT NULL DEFAULT '0',
	`status` ENUM('active','archived') NOT NULL DEFAULT 'active' COLLATE 'utf8mb4_unicode_ci',
	`progression` INT(10) NOT NULL DEFAULT '0',
	`couleur` VARCHAR(7) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`metadata` JSON NULL DEFAULT NULL,
	`archived_at` TIMESTAMP NULL DEFAULT NULL,
	`created_at` TIMESTAMP NULL DEFAULT NULL,
	`updated_at` TIMESTAMP NULL DEFAULT NULL,
	`deleted_at` TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY (`id`) USING BTREE,
	UNIQUE INDEX `activites_code_unique` (`code`) USING BTREE,
	INDEX `activites_projet_id_index` (`projet_id`) USING BTREE,
	INDEX `activites_responsable_id_index` (`responsable_id`) USING BTREE,
	INDEX `activites_status_index` (`status`) USING BTREE,
	INDEX `activites_ordre_index` (`ordre`) USING BTREE,
	INDEX `activites_created_by_foreign` (`created_by`) USING BTREE,
	CONSTRAINT `activites_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION,
	CONSTRAINT `activites_projet_id_foreign` FOREIGN KEY (`projet_id`) REFERENCES `projets` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT `activites_responsable_id_foreign` FOREIGN KEY (`responsable_id`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE RESTRICT
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
AUTO_INCREMENT=20
;

-- Activities-User (Many-to-Many)
activite_user
CREATE TABLE `activite_user` (
	`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	`activite_id` BIGINT(20) UNSIGNED NOT NULL,
	`user_id` BIGINT(20) UNSIGNED NOT NULL,
	`role` ENUM('responsable','collaborator','viewer') NOT NULL DEFAULT 'collaborator' COLLATE 'utf8mb4_unicode_ci',
	`can_edit_activity` TINYINT(1) NOT NULL DEFAULT '0',
	`can_delete_activity` TINYINT(1) NOT NULL DEFAULT '0',
	`can_create_tasks` TINYINT(1) NOT NULL DEFAULT '0',
	`can_edit_tasks` TINYINT(1) NOT NULL DEFAULT '0',
	`can_delete_tasks` TINYINT(1) NOT NULL DEFAULT '0',
	`can_validate_results` TINYINT(1) NOT NULL DEFAULT '0',
	`can_assign_users` TINYINT(1) NOT NULL DEFAULT '0',
	`created_at` TIMESTAMP NULL DEFAULT NULL,
	`updated_at` TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY (`id`) USING BTREE,
	UNIQUE INDEX `activite_user_unique` (`activite_id`, `user_id`) USING BTREE,
	INDEX `idx_role` (`role`) USING BTREE,
	INDEX `activite_user_user_id_foreign` (`user_id`) USING BTREE,
	CONSTRAINT `activite_user_activite_id_foreign` FOREIGN KEY (`activite_id`) REFERENCES `activites` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT `activite_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
AUTO_INCREMENT=26
;

CREATE TABLE `taches` (
	`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	`activite_id` BIGINT(20) UNSIGNED NOT NULL,
	`parent_tache_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	`titre` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`code` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`description` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`objectif` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`indicateurs_resultats` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`statut` ENUM('a_faire','en_cours','termine') NOT NULL DEFAULT 'a_faire' COLLATE 'utf8mb4_unicode_ci',
	`priorite` ENUM('faible','moyenne','elevee','critique') NOT NULL DEFAULT 'moyenne' COLLATE 'utf8mb4_unicode_ci',
	`echeance` DATE NULL DEFAULT NULL,
	`date_debut` DATE NULL DEFAULT NULL,
	`date_fin_reelle` DATE NULL DEFAULT NULL,
	`taux_realisation` INT(10) NOT NULL DEFAULT '0',
	`estimated_hours` INT(10) NOT NULL DEFAULT '0',
	`actual_hours` INT(10) NOT NULL DEFAULT '0',
	`week_number` INT(10) NULL DEFAULT NULL,
	`year` INT(10) NULL DEFAULT NULL,
	`validation_n1_required` TINYINT(1) NOT NULL DEFAULT '1',
	`validated_n1_by` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	`validated_n1_at` TIMESTAMP NULL DEFAULT NULL,
	`commentaire_n1` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`validation_n2_required` TINYINT(1) NOT NULL DEFAULT '1',
	`validated_n2_by` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	`validated_n2_at` TIMESTAMP NULL DEFAULT NULL,
	`commentaire_n2` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`validation_superieur` TINYINT(1) NOT NULL DEFAULT '0',
	`verrou_reevaluation` TINYINT(1) NOT NULL DEFAULT '0',
	`commentaire` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`validateur_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	`created_by` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	`position` INT(10) NULL DEFAULT NULL,
	`couleur` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`cover_image` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`metadata` JSON NULL DEFAULT NULL,
	`visibility` ENUM('public','private','members_only') NOT NULL DEFAULT 'members_only' COLLATE 'utf8mb4_unicode_ci',
	`archive_status` ENUM('active','archived') NOT NULL DEFAULT 'active' COLLATE 'utf8mb4_unicode_ci',
	`archived_at` TIMESTAMP NULL DEFAULT NULL,
	`deleted_at` TIMESTAMP NULL DEFAULT NULL,
	`created_at` TIMESTAMP NULL DEFAULT NULL,
	`updated_at` TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY (`id`) USING BTREE,
	UNIQUE INDEX `taches_activite_id_titre_unique` (`activite_id`, `titre`) USING BTREE,
	UNIQUE INDEX `taches_code_unique` (`code`) USING BTREE,
	INDEX `taches_validateur_id_foreign` (`validateur_id`) USING BTREE,
	INDEX `taches_archive_status_index` (`archive_status`) USING BTREE,
	INDEX `taches_parent_tache_id_index` (`parent_tache_id`) USING BTREE,
	INDEX `taches_visibility_index` (`visibility`) USING BTREE,
	INDEX `taches_created_by_foreign` (`created_by`) USING BTREE,
	INDEX `taches_week_number_year_index` (`week_number`, `year`) USING BTREE,
	INDEX `taches_validated_n1_at_index` (`validated_n1_at`) USING BTREE,
	INDEX `taches_validated_n2_at_index` (`validated_n2_at`) USING BTREE,
	INDEX `taches_validated_n1_by_foreign` (`validated_n1_by`) USING BTREE,
	INDEX `taches_validated_n2_by_foreign` (`validated_n2_by`) USING BTREE,
	CONSTRAINT `taches_activite_id_foreign` FOREIGN KEY (`activite_id`) REFERENCES `activites` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT `taches_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION,
	CONSTRAINT `taches_parent_tache_id_foreign` FOREIGN KEY (`parent_tache_id`) REFERENCES `taches` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT `taches_validated_n1_by_foreign` FOREIGN KEY (`validated_n1_by`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL,
	CONSTRAINT `taches_validated_n2_by_foreign` FOREIGN KEY (`validated_n2_by`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL,
	CONSTRAINT `taches_validateur_id_foreign` FOREIGN KEY (`validateur_id`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
AUTO_INCREMENT=27
;

CREATE TABLE `tache_user` (
	`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	`tache_id` BIGINT(20) UNSIGNED NOT NULL,
	`user_id` BIGINT(20) UNSIGNED NOT NULL,
	`role` ENUM('assignee','validator','observer') NOT NULL DEFAULT 'assignee' COLLATE 'utf8mb4_unicode_ci',
	`can_edit` TINYINT(1) NOT NULL DEFAULT '1',
	`can_complete` TINYINT(1) NOT NULL DEFAULT '1',
	`can_validate` TINYINT(1) NOT NULL DEFAULT '0',
	`statut_individuel` ENUM('a_faire','en_cours','termine') NOT NULL DEFAULT 'a_faire' COMMENT 'Statut individuel de l\'utilisateur pour cette tâche' COLLATE 'utf8mb4_unicode_ci',
	`progression_individuelle` INT(10) NOT NULL DEFAULT '0' COMMENT 'Progression personnelle en pourcentage',
	`started_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Date de début personnel',
	`completed_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Date de complétion personnelle',
	`notes_personnelles` TEXT NULL DEFAULT NULL COMMENT 'Notes privées de l\'assigné' COLLATE 'utf8mb4_unicode_ci',
	`created_at` TIMESTAMP NULL DEFAULT NULL,
	`updated_at` TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY (`id`) USING BTREE,
	UNIQUE INDEX `tache_user_tache_id_user_id_unique` (`tache_id`, `user_id`) USING BTREE,
	INDEX `tache_user_user_id_foreign` (`user_id`) USING BTREE,
	CONSTRAINT `tache_user_tache_id_foreign` FOREIGN KEY (`tache_id`) REFERENCES `taches` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT `tache_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
AUTO_INCREMENT=22
;

CREATE TABLE `tache_resultats` (
	`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	`tache_id` BIGINT(20) UNSIGNED NOT NULL,
	`user_id` BIGINT(20) UNSIGNED NOT NULL,
	`is_individual` TINYINT(1) NOT NULL DEFAULT '1' COMMENT 'true = résultat individuel, false = résultat global de la tâche',
	`resultats_attendus` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`resultats_obtenus` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`taux_realisation` INT(10) NOT NULL DEFAULT '0',
	`difficultes_rencontrees` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`solutions_envisagees` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`observations` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`soumis_le` TIMESTAMP NULL DEFAULT NULL,
	`valide_par_n1` TINYINT(1) NOT NULL DEFAULT '0',
	`validateur_n1_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	`valide_le_n1` TIMESTAMP NULL DEFAULT NULL,
	`commentaire_n1` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`valide_par_n2` TINYINT(1) NOT NULL DEFAULT '0',
	`validateur_n2_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	`valide_le_n2` TIMESTAMP NULL DEFAULT NULL,
	`commentaire_n2` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`created_at` TIMESTAMP NULL DEFAULT NULL,
	`updated_at` TIMESTAMP NULL DEFAULT NULL,
	`deleted_at` TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY (`id`) USING BTREE,
	UNIQUE INDEX `unique_tache_user_resultat` (`tache_id`, `user_id`) USING BTREE,
	INDEX `tache_resultats_validateur_n1_id_foreign` (`validateur_n1_id`) USING BTREE,
	INDEX `tache_resultats_validateur_n2_id_foreign` (`validateur_n2_id`) USING BTREE,
	INDEX `tache_resultats_tache_id_index` (`tache_id`) USING BTREE,
	INDEX `tache_resultats_user_id_index` (`user_id`) USING BTREE,
	INDEX `tache_resultats_valide_par_n1_valide_par_n2_index` (`valide_par_n1`, `valide_par_n2`) USING BTREE,
	INDEX `tache_resultats_soumis_le_index` (`soumis_le`) USING BTREE,
	CONSTRAINT `tache_resultats_tache_id_foreign` FOREIGN KEY (`tache_id`) REFERENCES `taches` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT `tache_resultats_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT `tache_resultats_validateur_n1_id_foreign` FOREIGN KEY (`validateur_n1_id`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL,
	CONSTRAINT `tache_resultats_validateur_n2_id_foreign` FOREIGN KEY (`validateur_n2_id`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
AUTO_INCREMENT=27
;
CREATE TABLE `documents` (
	`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	`workspace_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	`documentable_type` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`documentable_id` BIGINT(20) UNSIGNED NOT NULL,
	`nom` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`nom_stockage` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`extension` VARCHAR(10) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`mime_type` VARCHAR(100) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`taille` BIGINT(20) UNSIGNED NOT NULL,
	`chemin` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`disk` VARCHAR(255) NOT NULL DEFAULT 'local' COLLATE 'utf8mb4_unicode_ci',
	`description` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`metadata` JSON NULL DEFAULT NULL,
	`hash_sha256` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`parent_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	`version` INT(10) UNSIGNED NOT NULL DEFAULT '1',
	`is_latest_version` TINYINT(1) NOT NULL DEFAULT '1',
	`allow_duplicates` TINYINT(1) NOT NULL DEFAULT '1',
	`thumbnail_path` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`user_id` BIGINT(20) UNSIGNED NOT NULL,
	`download_count` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`last_downloaded_at` TIMESTAMP NULL DEFAULT NULL,
	`visibility` ENUM('private','team','public') NOT NULL DEFAULT 'team' COLLATE 'utf8mb4_unicode_ci',
	`created_at` TIMESTAMP NULL DEFAULT NULL,
	`updated_at` TIMESTAMP NULL DEFAULT NULL,
	`deleted_at` TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY (`id`) USING BTREE,
	UNIQUE INDEX `documents_nom_stockage_unique` (`nom_stockage`) USING BTREE,
	INDEX `documents_parent_id_foreign` (`parent_id`) USING BTREE,
	INDEX `documents_documentable_type_documentable_id_index` (`documentable_type`, `documentable_id`) USING BTREE,
	INDEX `documents_user_id_index` (`user_id`) USING BTREE,
	INDEX `documents_created_at_index` (`created_at`) USING BTREE,
	INDEX `documents_documentable_type_index` (`documentable_type`) USING BTREE,
	INDEX `documents_documentable_id_index` (`documentable_id`) USING BTREE,
	INDEX `documents_hash_sha256_index` (`hash_sha256`) USING BTREE,
	INDEX `FK_documents_workspaces` (`workspace_id`) USING BTREE,
	CONSTRAINT `documents_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `documents` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT `documents_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT `FK_documents_workspaces` FOREIGN KEY (`workspace_id`) REFERENCES `workspaces` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
AUTO_INCREMENT=23
;

CREATE TABLE `document_permissions` (
	`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	`document_id` BIGINT(20) UNSIGNED NOT NULL,
	`permissionable_type` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`permissionable_id` BIGINT(20) UNSIGNED NOT NULL,
	`can_view` TINYINT(1) NOT NULL DEFAULT '1',
	`can_download` TINYINT(1) NOT NULL DEFAULT '1',
	`can_edit` TINYINT(1) NOT NULL DEFAULT '0',
	`can_delete` TINYINT(1) NOT NULL DEFAULT '0',
	`can_share` TINYINT(1) NOT NULL DEFAULT '0',
	`expires_at` TIMESTAMP NULL DEFAULT NULL,
	`created_at` TIMESTAMP NULL DEFAULT NULL,
	`updated_at` TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY (`id`) USING BTREE,
	UNIQUE INDEX `doc_permission_unique` (`document_id`, `permissionable_type`, `permissionable_id`) USING BTREE,
	INDEX `document_permissions_permissionable_type_permissionable_id_index` (`permissionable_type`, `permissionable_id`) USING BTREE,
	INDEX `document_permissions_document_id_index` (`document_id`) USING BTREE,
	CONSTRAINT `document_permissions_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
AUTO_INCREMENT=21
;
   

-- Activity Logs (Audit Trail)
CREATE TABLE `activity_log` (
	`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	`log_name` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`description` TEXT NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`subject_type` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`event` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`subject_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	`causer_type` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`causer_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	`properties` JSON NULL DEFAULT NULL,
	`batch_uuid` CHAR(36) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`created_at` TIMESTAMP NULL DEFAULT NULL,
	`updated_at` TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY (`id`) USING BTREE,
	INDEX `subject` (`subject_type`, `subject_id`) USING BTREE,
	INDEX `causer` (`causer_type`, `causer_id`) USING BTREE,
	INDEX `activity_log_log_name_index` (`log_name`) USING BTREE
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
AUTO_INCREMENT=356
;
 

 

--- 
 
  
### 6. Events & Listeners

```php
// app/Events/TacheCreated.php
class TacheCreated
{
    public function __construct(public Tache $tache) {}
}

// app/Listeners/NotifyTaskAssignees.php
class NotifyTaskAssignees implements ShouldQueue
{
    public function handle(TacheCreated $event)
    {
        foreach ($event->tache->assignees as $user) {
            $user->notify(new TaskAssignedNotification($event->tache));
        }
    }
}

// app/Events/TacheMoved.php
class TacheMoved
{
    public function __construct(
        public Tache $tache,
        public int $oldActiviteId,
        public int $newActiviteId
    ) {}
}
```

### 7. Jobs (Queue)

```php
// app/Jobs/SendTaskReminderJob.php
class SendTaskReminderJob implements ShouldQueue
{
    public function handle()
    {
        $dueSoonTasks = Tache::where('echeance', '>=', now())
            ->where('echeance', '<=', now()->addDay())
            ->where('statut', '!=', StatusTache::TERMINE)
            ->get();

        foreach ($dueSoonTasks as $tache) {
            // Send notifications
        }
    }
}

// app/Jobs/GenerateReportJob.php
class GenerateReportJob implements ShouldQueue
{
    public function handle(User $user, array $filters)
    {
        // Generate PDF/Excel report
        // Email to user
    }
}
```

---

## 🎨 Frontend Implementation Plan

### 1. Vue Router Structure

```javascript
// resources/js/router/index.js
const routes = [
  { path: '/', component: Dashboard, meta: { auth: true } },

  // Projects
  { path: '/projets', component: ProjetIndex },
  { path: '/projets/:id', component: ProjetShow },
  { path: '/projets/:id/settings', component: ProjetSettings },

  // Board (Kanban)
  { path: '/projets/:id/board', component: BoardKanban },
  { path: '/projets/:id/list', component: BoardList },
  { path: '/projets/:id/calendar', component: BoardCalendar },
  { path: '/projets/:id/timeline', component: BoardTimeline },

  // Tasks
  { path: '/taches/:id', component: TacheDetail },

  // Reports
  { path: '/reports', component: ReportDashboard },

  // Settings
  { path: '/settings/profile', component: ProfileSettings },
  { path: '/settings/notifications', component: NotificationSettings },
]
```

### 2. Component Structure

```
resources/js/
├── pages/
│   ├── Dashboard.vue
│   ├── projets/
│   │   ├── ProjetIndex.vue
│   │   ├── ProjetShow.vue
│   │   ├── ProjetCreate.vue
│   │   └── ProjetSettings.vue
│   ├── board/
│   │   ├── BoardKanban.vue (main Trello-like view)
│   │   ├── BoardList.vue
│   │   ├── BoardCalendar.vue
│   │   └── BoardTimeline.vue
│   ├── taches/
│   │   ├── TacheDetail.vue (modal/sidebar)
│   │   └── TacheForm.vue
│   └── reports/
│       └── ReportDashboard.vue
├── components/
│   ├── board/
│   │   ├── KanbanColumn.vue (activity column)
│   │   ├── TaskCard.vue (draggable card)
│   │   ├── QuickAddTask.vue
│   │   └── ColumnHeader.vue
│   ├── task/
│   │   ├── TaskModal.vue (detail view)
│   │   ├── TaskDescription.vue
│   │   ├── TaskChecklist.vue
│   │   ├── TaskComments.vue
│   │   ├── TaskAttachments.vue
│   │   ├── TaskLabels.vue
│   │   ├── TaskAssignees.vue
│   │   └── TaskActivity.vue (history)
│   ├── common/
│   │   ├── UserAvatar.vue
│   │   ├── DatePicker.vue
│   │   ├── RichTextEditor.vue
│   │   ├── FileUploader.vue
│   │   ├── LabelPicker.vue
│   │   └── UserPicker.vue
│   ├── layout/
│   │   ├── AppSidebar.vue
│   │   ├── AppHeader.vue
│   │   ├── ThemeToggler.vue
│   │   └── NotificationCenter.vue
│   └── charts/
│       ├── ProgressChart.vue
│       ├── BurndownChart.vue
│       └── VelocityChart.vue
├── composables/
│   ├── useTasks.js
│   ├── useProjects.js
│   ├── useNotifications.js
│   ├── useDragDrop.js
│   └── useRealtime.js
└── stores/
    ├── auth.js (Pinia)
    ├── projects.js
    ├── tasks.js
    ├── notifications.js
    └── ui.js
```

### 3. Pinia Stores

```javascript
// resources/js/stores/tasks.js
import { defineStore } from 'pinia'

export const useTaskStore = defineStore('tasks', {
  state: () => ({
    tasks: [],
    currentTask: null,
    filters: {
      assignee: null,
      label: null,
      status: null,
      dueDate: null
    }
  }),

  actions: {
    async fetchTasks(activiteId) {
      const response = await api.get(`/activites/${activiteId}/taches`)
      this.tasks = response.data.data
    },

    async moveTask(taskId, newActiviteId, position) {
      await api.post(`/taches/${taskId}/move`, {
        activite_id: newActiviteId,
        position
      })
      // Update local state
    },

    async updateTask(taskId, data) {
      await api.patch(`/taches/${taskId}`, data)
    }
  },

  getters: {
    filteredTasks(state) {
      return state.tasks.filter(task => {
        // Apply filters
      })
    },

    tasksByStatus(state) {
      return (status) => state.tasks.filter(t => t.statut === status)
    }
  }
})
```

### 4. Drag & Drop Implementation

```vue
<!-- BoardKanban.vue -->
<template>
  <div class="kanban-board">
    <KanbanColumn
      v-for="activite in activites"
      :key="activite.id"
      :activite="activite"
      :tasks="getTasksByActivite(activite.id)"
      @task-moved="handleTaskMoved"
    />
  </div>
</template>

<script setup>
import { useDragDrop } from '@/composables/useDragDrop'

const { handleTaskMoved } = useDragDrop()
</script>

<!-- KanbanColumn.vue -->
<template>
  <div class="kanban-column">
    <ColumnHeader :activite="activite" />

    <draggable
      v-model="tasks"
      :group="{ name: 'tasks', pull: true, put: true }"
      :animation="200"
      @change="onTasksChanged"
      item-key="id"
    >
      <template #item="{ element }">
        <TaskCard :task="element" @click="openTaskModal(element)" />
      </template>
    </draggable>

    <QuickAddTask :activite-id="activite.id" />
  </div>
</template>
```

### 5. Real-time Updates

```javascript
// resources/js/composables/useRealtime.js
import { onMounted, onUnmounted } from 'vue'

export function useRealtime(projectId) {
  onMounted(() => {
    // Subscribe to project channel
    Echo.private(`project.${projectId}`)
      .listen('TaskCreated', (e) => {
        // Add task to board
      })
      .listen('TaskUpdated', (e) => {
        // Update task on board
      })
      .listen('TaskMoved', (e) => {
        // Move task between columns
      })
      .listen('CommentAdded', (e) => {
        // Show notification
      })
  })

  onUnmounted(() => {
    Echo.leave(`project.${projectId}`)
  })
}
```

---

## 🚀 Implementation Phases

### **Phase 1: Foundation (Weeks 1-4)**

**Week 1-2: Backend Setup**
- [ ] Setup Laravel project structure
- [ ] Create all migrations
- [ ] Setup Spatie Permissions
- [ ] Create all Models with relationships
- [ ] Create Enums
- [ ] Setup Laravel Sanctum
- [ ] Create seeders (users, roles, sample data)

**Week 3-4: Frontend Setup**
- [ ] Setup Vue 3 + Vite
- [ ] Configure Tailwind CSS v4
- [ ] Setup Vue Router
- [ ] Setup Pinia stores
- [ ] Create layout components
- [ ] Integrate TailAdmin components
- [ ] Setup authentication flow

**Deliverables**:
- Working authentication system
- Basic project/activity/task CRUD
- Database with sample data

---

### **Phase 2: Core Features (Weeks 5-8)**

**Week 5: Board Management**
- [ ] Kanban board view
- [ ] Drag & drop functionality (vue-draggable)
- [ ] Task cards with basic info
- [ ] Quick add task
- [ ] Column management

**Week 6: Task Details**
- [ ] Task modal/sidebar
- [ ] Rich text description editor
- [ ] Task assignees
- [ ] Due dates
- [ ] Priority & status
- [ ] Progress tracking

**Week 7: Comments & Files**
- [ ] Comment system
- [ ] File upload/attachments
- [ ] @mentions
- [ ] Activity feed
- [ ] File preview

**Week 8: Labels & Checklists**
- [ ] Label CRUD
- [ ] Label assignment
- [ ] Checklist system
- [ ] Checklist progress
- [ ] Label filtering

**Deliverables**:
- Full Trello-like board experience
- Complete task detail view
- Working collaboration features

---

### **Phase 3: Advanced Features (Weeks 9-12)**

**Week 9: Search & Notifications**
- [ ] Global search (Laravel Scout)
- [ ] Advanced filters
- [ ] Notification system
- [ ] Notification center UI
- [ ] Email notifications

**Week 10: Calendar & Timeline**
- [ ] Calendar view (FullCalendar)
- [ ] Timeline/Gantt view
- [ ] Drag to reschedule
- [ ] Calendar filters

**Week 11: Reporting**
- [ ] Dashboard widgets
- [ ] Progress charts
- [ ] Burndown charts
- [ ] Export to PDF/Excel
- [ ] Custom reports

**Week 12: Real-time Collaboration**
- [ ] WebSocket setup (Pusher/Reverb)
- [ ] Real-time task updates
- [ ] Live cursors (optional)
- [ ] Presence indicators
- [ ] Real-time notifications

**Deliverables**:
- Search functionality
- Comprehensive notification system
- Multiple view types
- Real-time collaboration

---

### **Phase 4: Polish & Extend (Weeks 13-16)**

**Week 13: Automation**
- [ ] Automation builder UI
- [ ] Rule engine backend
- [ ] Trigger system
- [ ] Action handlers
- [ ] Automation templates

**Week 14: Mobile & PWA**
- [ ] Responsive design optimization
- [ ] PWA manifest
- [ ] Service worker (offline support)
- [ ] Push notifications
- [ ] Install prompts

**Week 15: Integrations**
- [ ] API documentation (Swagger)
- [ ] Webhooks
- [ ] Email integration (create tasks via email)
- [ ] Calendar sync (Google/Outlook)
- [ ] Import from Trello/Asana

**Week 16: Testing & Optimization**
- [ ] Unit tests (Backend)
- [ ] Feature tests (Backend)
- [ ] Component tests (Frontend - Vitest)
- [ ] E2E tests (Cypress)
- [ ] Performance optimization
- [ ] Security audit

**Deliverables**:
- Automation system
- Mobile-friendly PWA
- Third-party integrations
- Comprehensive test coverage

---

## 🧪 Testing Strategy

### Backend Tests

```php
// tests/Feature/TacheTest.php
class TacheTest extends TestCase
{
    public function test_user_can_create_task()
    {
        $user = User::factory()->create();
        $activite = Activite::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/activites/'.$activite->id.'/taches', [
            'titre' => 'New Task',
            'description' => 'Task description',
            'priorite' => 'moyenne',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('taches', ['titre' => 'New Task']);
    }

    public function test_task_can_be_moved_between_activites()
    {
        // Test drag & drop logic
    }
}
```

### Frontend Tests

```javascript
// resources/js/components/__tests__/TaskCard.spec.js
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import TaskCard from '@/components/board/TaskCard.vue'

describe('TaskCard', () => {
  it('renders task title', () => {
    const wrapper = mount(TaskCard, {
      props: {
        task: {
          id: 1,
          titre: 'Test Task',
          priorite: 'elevee'
        }
      }
    })

    expect(wrapper.text()).toContain('Test Task')
  })

  it('shows priority indicator', () => {
    // Test priority badge rendering
  })
})
```

---

## 🔐 Security Considerations

1. **Authentication**:
   - Sanctum token-based auth
   - CSRF protection
   - Rate limiting on login

2. **Authorization**:
   - Policy-based access control
   - Role & permission checks
   - Team/workspace isolation

3. **Data Protection**:
   - Input validation (Form Requests)
   - XSS prevention (Vue escaping)
   - SQL injection prevention (Eloquent)
   - File upload validation

4. **API Security**:
   - API rate limiting
   - CORS configuration
   - Token expiration

---

## 📊 Performance Optimization

1. **Backend**:
   - Eager loading (N+1 prevention)
   - Database indexing
   - Query optimization
   - Redis caching
   - Queue jobs for heavy tasks

2. **Frontend**:
   - Code splitting (Vite)
   - Lazy loading routes
   - Virtual scrolling for long lists
   - Image optimization
   - Debouncing search/filters

3. **Infrastructure**:
   - CDN for static assets
   - Database connection pooling
   - Opcache (PHP)
   - Asset versioning

---

## 📚 Documentation Plan

1. **User Documentation**:
   - Getting started guide
   - Feature tutorials
   - Video walkthroughs
   - FAQ

2. **Developer Documentation**:
   - API documentation (Swagger/OpenAPI)
   - Architecture overview
   - Database schema
   - Contributing guidelines

3. **Deployment Documentation**:
   - Server requirements
   - Installation guide
   - Configuration guide
   - Troubleshooting

---

## 🚢 Deployment Strategy

### Development
```bash
npm run dev        # Frontend
php artisan serve  # Backend
php artisan queue:work  # Jobs
```

### Production
```bash
npm run build      # Build assets
php artisan optimize  # Cache routes, config
php artisan migrate --force  # Run migrations
php artisan queue:restart  # Restart workers
```

### Docker Setup
```dockerfile
# Dockerfile
FROM php:8.2-fpm
# Install dependencies, composer, node
# Copy application files
# Build assets
```

---

## 📈 Success Metrics

- [ ] User onboarding time < 5 minutes
- [ ] Page load time < 2 seconds
- [ ] API response time < 200ms (p95)
- [ ] Real-time latency < 100ms
- [ ] Test coverage > 80%
- [ ] Mobile usability score > 90
- [ ] Accessibility score > 95 (WCAG AA)

---

## 🎓 Learning Resources

- Laravel Documentation: https://laravel.com/docs
- Vue.js 3 Guide: https://vuejs.org/guide
- Tailwind CSS v4: https://tailwindcss.com/docs
- Trello API (for inspiration): https://developer.atlassian.com/cloud/trello
- Spatie Permissions: https://spatie.be/docs/laravel-permission

---

**Next Steps**: Begin with Phase 1 - Foundation setup and database schema implementation.
