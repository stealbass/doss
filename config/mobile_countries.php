<?php

return [
    /**
     * Liste des pays supportés par l'application mobile DOSSY Chat IA
     * Utilisé pour la catégorisation des documents juridiques et le filtrage AI
     */
    'supported_countries' => [
        // Pays francophones d'Afrique de l'Ouest (UEMOA)
        'BJ' => [
            'code' => 'BJ',
            'name' => 'Bénin',
            'name_en' => 'Benin',
            'flag' => '🇧🇯',
            'region' => 'West Africa',
            'currency' => 'XOF',
            'legal_systems' => ['OHADA', 'Civil Law'],
            'official_languages' => ['fr'],
        ],
        'BF' => [
            'code' => 'BF',
            'name' => 'Burkina Faso',
            'name_en' => 'Burkina Faso',
            'flag' => '🇧🇫',
            'region' => 'West Africa',
            'currency' => 'XOF',
            'legal_systems' => ['OHADA', 'Civil Law'],
            'official_languages' => ['fr'],
        ],
        'CI' => [
            'code' => 'CI',
            'name' => 'Côte d\'Ivoire',
            'name_en' => 'Ivory Coast',
            'flag' => '🇨🇮',
            'region' => 'West Africa',
            'currency' => 'XOF',
            'legal_systems' => ['OHADA', 'Civil Law'],
            'official_languages' => ['fr'],
        ],
        'GW' => [
            'code' => 'GW',
            'name' => 'Guinée-Bissau',
            'name_en' => 'Guinea-Bissau',
            'flag' => '🇬🇼',
            'region' => 'West Africa',
            'currency' => 'XOF',
            'legal_systems' => ['OHADA', 'Civil Law'],
            'official_languages' => ['pt', 'fr'],
        ],
        'ML' => [
            'code' => 'ML',
            'name' => 'Mali',
            'name_en' => 'Mali',
            'flag' => '🇲🇱',
            'region' => 'West Africa',
            'currency' => 'XOF',
            'legal_systems' => ['OHADA', 'Civil Law'],
            'official_languages' => ['fr'],
        ],
        'NE' => [
            'code' => 'NE',
            'name' => 'Niger',
            'name_en' => 'Niger',
            'flag' => '🇳🇪',
            'region' => 'West Africa',
            'currency' => 'XOF',
            'legal_systems' => ['OHADA', 'Civil Law'],
            'official_languages' => ['fr'],
        ],
        'SN' => [
            'code' => 'SN',
            'name' => 'Sénégal',
            'name_en' => 'Senegal',
            'flag' => '🇸🇳',
            'region' => 'West Africa',
            'currency' => 'XOF',
            'legal_systems' => ['OHADA', 'Civil Law'],
            'official_languages' => ['fr'],
        ],
        'TG' => [
            'code' => 'TG',
            'name' => 'Togo',
            'name_en' => 'Togo',
            'flag' => '🇹🇬',
            'region' => 'West Africa',
            'currency' => 'XOF',
            'legal_systems' => ['OHADA', 'Civil Law'],
            'official_languages' => ['fr'],
        ],

        // Pays francophones d'Afrique Centrale (CEMAC)
        'CM' => [
            'code' => 'CM',
            'name' => 'Cameroun',
            'name_en' => 'Cameroon',
            'flag' => '🇨🇲',
            'region' => 'Central Africa',
            'currency' => 'XAF',
            'legal_systems' => ['OHADA', 'Civil Law', 'Common Law'],
            'official_languages' => ['fr', 'en'],
        ],
        'CD' => [
            'code' => 'CD',
            'name' => 'RD Congo',
            'name_en' => 'Democratic Republic of Congo',
            'flag' => '🇨🇩',
            'region' => 'Central Africa',
            'currency' => 'CDF',
            'legal_systems' => ['OHADA', 'Civil Law'],
            'official_languages' => ['fr'],
        ],
        'GA' => [
            'code' => 'GA',
            'name' => 'Gabon',
            'name_en' => 'Gabon',
            'flag' => '🇬🇦',
            'region' => 'Central Africa',
            'currency' => 'XAF',
            'legal_systems' => ['OHADA', 'Civil Law'],
            'official_languages' => ['fr'],
        ],

        // Pays de l'océan Indien
        'MG' => [
            'code' => 'MG',
            'name' => 'Madagascar',
            'name_en' => 'Madagascar',
            'flag' => '🇲🇬',
            'region' => 'Indian Ocean',
            'currency' => 'MGA',
            'legal_systems' => ['Civil Law'],
            'official_languages' => ['fr', 'mg'],
        ],

        // Pays d'Afrique du Nord (Maghreb)
        'MA' => [
            'code' => 'MA',
            'name' => 'Maroc',
            'name_en' => 'Morocco',
            'flag' => '🇲🇦',
            'region' => 'North Africa',
            'currency' => 'MAD',
            'legal_systems' => ['Islamic Law', 'Civil Law'],
            'official_languages' => ['ar', 'fr'],
        ],
        'TN' => [
            'code' => 'TN',
            'name' => 'Tunisie',
            'name_en' => 'Tunisia',
            'flag' => '🇹🇳',
            'region' => 'North Africa',
            'currency' => 'TND',
            'legal_systems' => ['Islamic Law', 'Civil Law'],
            'official_languages' => ['ar', 'fr'],
        ],
    ],

    /**
     * Groupes de pays par région (pour l'interface admin)
     */
    'regions' => [
        'West Africa' => ['BJ', 'BF', 'CI', 'GW', 'ML', 'NE', 'SN', 'TG'],
        'Central Africa' => ['CM', 'CD', 'GA'],
        'Indian Ocean' => ['MG'],
        'North Africa' => ['MA', 'TN'],
    ],

    /**
     * Systèmes juridiques communs
     */
    'legal_systems' => [
        'OHADA' => [
            'name' => 'OHADA',
            'full_name' => 'Organisation pour l\'Harmonisation en Afrique du Droit des Affaires',
            'countries' => ['BJ', 'BF', 'CI', 'GW', 'ML', 'NE', 'SN', 'TG', 'CM', 'CD', 'GA'],
            'description' => 'Harmonisation du droit des affaires en Afrique',
            'actes_uniformes' => [
                'Droit commercial général',
                'Droit des sociétés commerciales et GIE',
                'Droit des sûretés',
                'Procédures simplifiées de recouvrement et voies d\'exécution',
                'Procédures collectives d\'apurement du passif',
                'Droit de l\'arbitrage',
                'Comptabilité des entreprises',
                'Contrats de transport de marchandises par route',
                'Droit des sociétés coopératives',
            ],
        ],
        'Civil Law' => [
            'name' => 'Droit civil',
            'countries' => ['BJ', 'BF', 'CI', 'GW', 'ML', 'NE', 'SN', 'TG', 'CM', 'CD', 'GA', 'MG', 'MA', 'TN'],
        ],
        'Common Law' => [
            'name' => 'Common Law',
            'countries' => ['CM'], // Cameroun uniquement (système mixte)
        ],
        'Islamic Law' => [
            'name' => 'Droit islamique',
            'countries' => ['MA', 'TN'],
        ],
    ],

    /**
     * Contexte AI par pays pour les prompts intelligents
     */
    'ai_context' => [
        'default' => "Tu es un assistant juridique expert en droit africain francophone. Utilise les Actes Uniformes OHADA pour les questions de droit des affaires.",
        
        'country_specific' => [
            'SN' => "Pour le Sénégal, privilégie le Code de la Famille du Sénégal pour les questions familiales, le Code du Travail sénégalais pour le droit du travail, et les Actes Uniformes OHADA pour le droit des affaires.",
            'CM' => "Pour le Cameroun, utilise le Code civil camerounais, le Code du Travail camerounais, et les Actes Uniformes OHADA pour le droit des affaires. Note : le Cameroun a un système mixte (Common Law et Civil Law).",
            'MA' => "Pour le Maroc, référence le Code de la Famille marocain (Moudawana), le Code du Travail marocain, et le Code de Commerce. Tiens compte de l'influence du droit islamique.",
            'TN' => "Pour la Tunisie, utilise le Code du Statut Personnel tunisien, le Code du Travail tunisien, et le Code de Commerce. Tiens compte de l'influence du droit islamique.",
            'MG' => "Pour Madagascar, privilégie le Code civil malgache, le Code du Travail malgache, et les textes spécifiques à Madagascar.",
        ],
    ],
];
