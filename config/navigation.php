<?php

return [
  /*
    |--------------------------------------------------------------------------
    | Grades avec accès complet
    |--------------------------------------------------------------------------
    */
  'full_access_grades' => ['administrateur', 'technicien'],

  /*
    |--------------------------------------------------------------------------
    | Raccourcis réception : masquer si non autorisé (les autres restent visibles mais désactivés)
    |--------------------------------------------------------------------------
    */
  'dashboard_hide_when_denied' => ['papeterie', 'imagerie'],

  /*
    |--------------------------------------------------------------------------
    | Zones de navigation par grade
    |--------------------------------------------------------------------------
    */
  'grades' => [
    'medecin' => ['reception', 'triage', 'consultation', 'hospitalisation', 'dossiers', 'groupe_hopitaux'],
    'infirmiere' => ['reception', 'triage', 'hospitalisation', 'dossiers', 'groupe_hopitaux'],
    'laborantin' => ['reception', 'laboratoire', 'dossiers', 'groupe_hopitaux'],
    'radiologue' => ['reception', 'imagerie', 'dossiers', 'groupe_hopitaux'],
    'secretaire' => ['reception', 'papeterie', 'services_base', 'dossiers'],
    'comptable' => ['reception', 'comptabilite', 'dossiers', 'groupe_hopitaux'],
    'pharmacien' => ['reception', 'pharmacie', 'dossiers', 'groupe_hopitaux'],
    'administrateur' => ['*'],
    'technicien' => ['*'],
  ],

  /*
    |--------------------------------------------------------------------------
    | Correspondance route → zone
    |--------------------------------------------------------------------------
    */
  'route_areas' => [
    'reception' => ['dashboard'],
    'papeterie' => ['reception.papeterie'],
    'services_base' => ['reception.services'],
    'triage' => ['consultation.triage'],
    'consultation' => ['consultation.*'],
    'comptabilite' => ['facturation.*'],
    'laboratoire' => ['laboratoire.*'],
    'imagerie' => ['imagerie.*'],
    'pharmacie' => ['pharmacie.*'],
    'hospitalisation' => ['hospital.*'],
    'dossiers' => ['patient.*'],
    'groupe_hopitaux' => ['groupe_hopitaux.*'],
    'analytics' => ['analytics', 'analytics.*'],
    'support_technique' => ['settings.*'],
  ],

  /*
    |--------------------------------------------------------------------------
    | Menu principal (sidebar)
    |--------------------------------------------------------------------------
    */
  'sidebar' => [
    ['area' => 'dossiers', 'label' => 'Nouvelle fiche médical', 'icon' => 'clipboard-plus', 'route' => 'patient.create', 'group' => 'actions'],
    ['area' => 'reception', 'label' => 'Réception', 'icon' => 'airplay', 'route' => 'dashboard', 'group' => 'main'],
    // ['area' => 'papeterie', 'label' => 'Papeterie', 'icon' => 'clipboard-document-list', 'route' => 'reception.papeterie', 'group' => 'main'],
    // ['area' => 'services_base', 'label' => 'Service de base', 'icon' => 'sparkles', 'route' => 'reception.services', 'group' => 'main'],
    ['area' => 'triage', 'label' => 'Triage', 'icon' => 'inbox', 'route' => 'consultation.triage', 'group' => 'main'],
    ['area' => 'consultation', 'label' => 'Consultations', 'icon' => 'stethoscope', 'route' => 'consultation.index', 'group' => 'main'],
    ['area' => 'comptabilite', 'label' => 'Comptabilité', 'icon' => 'banknotes', 'route' => 'facturation.dashboard', 'group' => 'main'],
    ['area' => 'laboratoire', 'label' => 'Laboratoire', 'icon' => 'beaker', 'route' => 'laboratoire.index', 'group' => 'main'],
    ['area' => 'imagerie', 'label' => 'Imagerie', 'icon' => 'photo', 'route' => 'imagerie.index', 'group' => 'main'],
    ['area' => 'pharmacie', 'label' => 'Pharmacie', 'icon' => 'pill', 'route' => 'pharmacie.dashboard', 'group' => 'main'],
    ['area' => 'hospitalisation', 'label' => 'Hospitalisation', 'icon' => 'home-modern', 'route' => 'hospital.index', 'group' => 'main'],
    ['area' => 'dossiers', 'label' => 'Dossiers médicaux', 'icon' => 'library-big', 'route' => 'patient.index', 'group' => 'main'],
  ],

  'sidebar_footer' => [
    ['area' => 'support_technique', 'label' => 'Support technique', 'icon' => 'cog-6-tooth', 'route' => 'settings.hopital.index'],
    ['area' => 'groupe_hopitaux', 'label' => "Groupe d'hopitaux", 'icon' => 'building-office-2', 'route' => 'groupe_hopitaux.index'],
  ],

  /*
    |--------------------------------------------------------------------------
    | Raccourcis du tableau de bord réception
    |--------------------------------------------------------------------------
    */
  'dashboard_shortcuts' => [
    ['area' => 'papeterie', 'label' => 'Papeterie', 'description' => 'Fournitures de bureau', 'icon' => 'clipboard-document-list', 'route' => 'reception.papeterie'],
    ['area' => 'services_base', 'label' => 'Service de base', 'description' => 'Equipement menager', 'icon' => 'briefcase', 'route' => 'reception.services'],
    ['area' => 'triage', 'label' => 'Triage', 'description' => 'Patients à orienter', 'icon' => null, 'route' => 'consultation.triage', 'badge' => 'triage'],
    ['area' => 'laboratoire', 'label' => 'Laboratoire', 'description' => 'Bons en circulation', 'icon' => null, 'route' => 'laboratoire.index', 'badge' => 'laboratoire'],
    ['area' => 'comptabilite', 'label' => 'Facturation', 'description' => 'Dossiers à traiter', 'icon' => null, 'route' => 'facturation.dashboard', 'badge' => 'facturation'],
    ['area' => 'imagerie', 'label' => 'Imagerie', 'description' => 'Demandes associées', 'icon' => null, 'route' => 'imagerie.index', 'badge' => 'imagerie'],
  ],
];
