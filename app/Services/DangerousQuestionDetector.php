<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class DangerousQuestionDetector
{
    /**
     * Catégories de questions dangereuses
     */
    private const DANGEROUS_PATTERNS = [
        'illegal_activity' => [
            'patterns' => [
                '/comment\s+(frauder|voler|escroquer|blanchir)/i',
                '/comment\s+(cacher|dissimuler)\s+(argent|revenus|bien)/i',
                '/comment\s+ne\s+pas\s+payer\s+(impôt|taxe)/i',
                '/évasion\s+fiscale/i',
                '/contourner\s+(la\s+)?loi/i',
                '/activité\s+illégale/i',
                '/trafic\s+de/i',
                '/corruption/i',
                '/pot[- ]de[- ]vin/i',
            ],
            'severity' => 'high',
            'message' => "Je ne peux pas fournir d'assistance pour des activités illégales ou contraires à l'éthique.",
        ],
        'tax_evasion' => [
            'patterns' => [
                '/comment\s+(éviter|ne\s+pas\s+payer)\s+(impôt|taxe)/i',
                '/montage\s+fiscal\s+agressif/i',
                '/paradis\s+fiscal/i',
                '/optimisation\s+fiscale\s+agressive/i',
            ],
            'severity' => 'high',
            'message' => "L'évasion fiscale est illégale. Je peux vous aider avec l'optimisation fiscale légale et la planification conforme.",
        ],
        'violence' => [
            'patterns' => [
                '/comment\s+(tuer|blesser|faire\s+du\s+mal)/i',
                '/violence\s+(physique|conjugale)/i',
                '/agression/i',
            ],
            'severity' => 'critical',
            'message' => "Je ne peux pas répondre à des questions concernant la violence. Si vous êtes en danger, contactez les autorités locales.",
        ],
        'discrimination' => [
            'patterns' => [
                '/discriminer\s+sur\s+base/i',
                '/comment\s+(virer|licencier)\s+sans\s+raison/i',
                '/éviter\s+d\'embaucher\s+(femme|handicapé)/i',
            ],
            'severity' => 'high',
            'message' => "La discrimination est illégale. Je peux vous informer sur les lois anti-discrimination et les pratiques d'embauche équitables.",
        ],
        'harmful_advice' => [
            'patterns' => [
                '/comment\s+ne\s+pas\s+(respecter|suivre)\s+(contrat|accord)/i',
                '/rompre\s+contrat\s+sans\s+conséquence/i',
                '/ne\s+pas\s+honorer\s+(engagement|obligation)/i',
            ],
            'severity' => 'medium',
            'message' => "Je ne peux pas conseiller sur le non-respect d'obligations légales. Je peux vous informer sur les conséquences et alternatives légales.",
        ],
    ];

    /**
     * Mots-clés sensibles nécessitant une modération
     */
    private const SENSITIVE_KEYWORDS = [
        'arme', 'drogue', 'explosif', 'terrorisme', 'blanchiment', 
        'contrefaçon', 'piratage', 'harcèlement', 'menace',
    ];

    /**
     * Détecter si une question est dangereuse
     * 
     * @param string $question
     * @return array ['is_dangerous' => bool, 'category' => string|null, 'severity' => string|null, 'message' => string|null]
     */
    public function detect(string $question): array
    {
        $question = strtolower($question);

        // Vérifier les patterns dangereux
        foreach (self::DANGEROUS_PATTERNS as $category => $config) {
            foreach ($config['patterns'] as $pattern) {
                if (preg_match($pattern, $question)) {
                    Log::warning('Dangerous question detected', [
                        'category' => $category,
                        'severity' => $config['severity'],
                        'question_preview' => substr($question, 0, 100),
                    ]);

                    return [
                        'is_dangerous' => true,
                        'category' => $category,
                        'severity' => $config['severity'],
                        'message' => $config['message'],
                        'should_block' => $config['severity'] === 'critical',
                    ];
                }
            }
        }

        // Vérifier les mots-clés sensibles
        $sensitiveFound = [];
        foreach (self::SENSITIVE_KEYWORDS as $keyword) {
            if (stripos($question, $keyword) !== false) {
                $sensitiveFound[] = $keyword;
            }
        }

        if (!empty($sensitiveFound)) {
            Log::info('Sensitive keywords detected', [
                'keywords' => $sensitiveFound,
                'question_preview' => substr($question, 0, 100),
            ]);

            return [
                'is_dangerous' => false,
                'is_sensitive' => true,
                'keywords' => $sensitiveFound,
                'severity' => 'low',
                'message' => null,
                'should_moderate' => true,
            ];
        }

        return [
            'is_dangerous' => false,
            'is_sensitive' => false,
            'severity' => null,
            'message' => null,
        ];
    }

    /**
     * Générer une réponse de refus appropriée
     * 
     * @param string $category
     * @param string $severity
     * @return string
     */
    public function getDenialResponse(string $category, string $severity): string
    {
        if (isset(self::DANGEROUS_PATTERNS[$category])) {
            return self::DANGEROUS_PATTERNS[$category]['message'];
        }

        return "Je ne peux pas fournir d'assistance pour cette demande car elle pourrait être contraire aux lois et règlements en vigueur. "
            . "Si vous avez des questions légitimes sur vos droits et obligations légales, je serais ravi de vous aider.";
    }
}
