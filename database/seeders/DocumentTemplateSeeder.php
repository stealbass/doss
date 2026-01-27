<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DocumentTemplate;
use Illuminate\Support\Str;

class DocumentTemplateSeeder extends Seeder
{
    public function run()
    {
        $countries = ['BJ', 'BF', 'CM', 'CI', 'CD', 'GA', 'GW', 'MG', 'ML', 'MA', 'NE', 'SN', 'TG', 'TN'];
        $plans = ['free', 'student', 'professional', 'enterprise'];
        
        $templates = [
            // Contrats
            [
                'title' => 'Contrat de Travail à Durée Indéterminée (CDI)',
                'title_en' => 'Permanent Employment Contract',
                'description' => 'Modèle standard de contrat de travail à durée indéterminée conforme au code du travail',
                'description_en' => 'Standard permanent employment contract template compliant with labor code',
                'category' => 'Contrat de Travail',
                'type' => 'contract',
                'file_type' => 'docx',
                'required_plan' => 'free',
                'usage_instructions' => 'Remplissez les informations de l\'employeur et du salarié. Adaptez les clauses selon votre besoin.',
                'usage_instructions_en' => 'Fill in employer and employee information. Adapt clauses as needed.',
            ],
            [
                'title' => 'Contrat de Travail à Durée Déterminée (CDD)',
                'title_en' => 'Fixed-Term Employment Contract',
                'description' => 'Contrat pour emploi temporaire avec date de fin précise',
                'description_en' => 'Contract for temporary employment with specific end date',
                'category' => 'Contrat de Travail',
                'type' => 'contract',
                'file_type' => 'docx',
                'required_plan' => 'student',
                'usage_instructions' => 'Précisez la durée et le motif du CDD. Maximum 2 renouvellements selon la loi.',
                'usage_instructions_en' => 'Specify duration and reason for fixed term. Maximum 2 renewals per law.',
            ],
            [
                'title' => 'Contrat de Prestation de Services',
                'title_en' => 'Service Agreement Contract',
                'description' => 'Contrat pour prestations de services entre entreprises',
                'description_en' => 'Service agreement contract between companies',
                'category' => 'Contrats Commerciaux',
                'type' => 'contract',
                'file_type' => 'pdf',
                'required_plan' => 'professional',
                'usage_instructions' => 'Définissez clairement les services, délais et modalités de paiement.',
                'usage_instructions_en' => 'Clearly define services, deadlines and payment terms.',
            ],
            
            // Statuts
            [
                'title' => 'Statuts de SARL',
                'title_en' => 'LLC Articles of Association',
                'description' => 'Statuts type pour création de Société à Responsabilité Limitée',
                'description_en' => 'Standard articles of association for Limited Liability Company',
                'category' => 'Création d\'Entreprise',
                'type' => 'statutes',
                'file_type' => 'docx',
                'required_plan' => 'professional',
                'usage_instructions' => 'Capital minimum selon le pays. Renseignez les associés et répartition du capital.',
                'usage_instructions_en' => 'Minimum capital per country. Enter shareholders and capital distribution.',
            ],
            [
                'title' => 'Statuts de SAS',
                'title_en' => 'SAS Articles of Association',
                'description' => 'Statuts pour Société par Actions Simplifiée',
                'description_en' => 'Articles of association for Simplified Joint Stock Company',
                'category' => 'Création d\'Entreprise',
                'type' => 'statutes',
                'file_type' => 'docx',
                'required_plan' => 'enterprise',
                'usage_instructions' => 'Plus de flexibilité que la SARL. Définissez le mode de gouvernance.',
                'usage_instructions_en' => 'More flexible than LLC. Define governance mode.',
            ],
            
            // Déclarations
            [
                'title' => 'Déclaration Fiscale Mensuelle',
                'title_en' => 'Monthly Tax Declaration',
                'description' => 'Formulaire de déclaration fiscale mensuelle des entreprises',
                'description_en' => 'Monthly business tax declaration form',
                'category' => 'Fiscalité',
                'type' => 'declaration',
                'file_type' => 'xlsx',
                'required_plan' => 'student',
                'usage_instructions' => 'À déposer avant le 15 du mois suivant. Calculez TVA et retenues à la source.',
                'usage_instructions_en' => 'File before 15th of following month. Calculate VAT and withholdings.',
            ],
            [
                'title' => 'Déclaration Annuelle de Salaires',
                'title_en' => 'Annual Payroll Declaration',
                'description' => 'Déclaration récapitulative annuelle des salaires versés',
                'description_en' => 'Annual summary declaration of paid salaries',
                'category' => 'Paie & Social',
                'type' => 'declaration',
                'file_type' => 'xlsx',
                'required_plan' => 'professional',
                'usage_instructions' => 'Récapitulatif des salaires bruts, charges et retenues de l\'année.',
                'usage_instructions_en' => 'Summary of gross salaries, charges and withholdings for the year.',
            ],
            
            // Courriers
            [
                'title' => 'Lettre de Démission',
                'title_en' => 'Resignation Letter',
                'description' => 'Modèle de lettre de démission d\'un salarié',
                'description_en' => 'Employee resignation letter template',
                'category' => 'Ressources Humaines',
                'type' => 'letter',
                'file_type' => 'docx',
                'required_plan' => 'free',
                'usage_instructions' => 'Respecter le préavis légal selon l\'ancienneté. Envoi en recommandé.',
                'usage_instructions_en' => 'Respect legal notice period per seniority. Send by registered mail.',
            ],
            [
                'title' => 'Lettre de Licenciement pour Motif Économique',
                'title_en' => 'Economic Dismissal Letter',
                'description' => 'Modèle de notification de licenciement économique',
                'description_en' => 'Economic dismissal notification template',
                'category' => 'Ressources Humaines',
                'type' => 'letter',
                'file_type' => 'docx',
                'required_plan' => 'professional',
                'usage_instructions' => 'Procédure stricte : convocation entretien, notification, préavis. Consultez un avocat.',
                'usage_instructions_en' => 'Strict procedure: interview summons, notification, notice. Consult a lawyer.',
            ],
            
            // Rapports
            [
                'title' => 'Rapport d\'Activité Annuel',
                'title_en' => 'Annual Activity Report',
                'description' => 'Template de rapport d\'activité annuel pour entreprise',
                'description_en' => 'Annual business activity report template',
                'category' => 'Gestion d\'Entreprise',
                'type' => 'report',
                'file_type' => 'docx',
                'required_plan' => 'professional',
                'usage_instructions' => 'Synthèse des activités, résultats financiers et perspectives.',
                'usage_instructions_en' => 'Summary of activities, financial results and outlook.',
            ],
            [
                'title' => 'Rapport de Gestion du Gérant',
                'title_en' => 'Manager\'s Management Report',
                'description' => 'Rapport annuel du gérant pour assemblée générale',
                'description_en' => 'Annual manager report for general meeting',
                'category' => 'Gestion d\'Entreprise',
                'type' => 'report',
                'file_type' => 'pdf',
                'required_plan' => 'enterprise',
                'usage_instructions' => 'Obligatoire pour AG annuelle. Présente la situation et la gestion.',
                'usage_instructions_en' => 'Required for annual GM. Presents situation and management.',
            ],
            
            // Formulaires
            [
                'title' => 'Formulaire de Demande de Congés',
                'title_en' => 'Leave Request Form',
                'description' => 'Formulaire standard de demande de congés payés',
                'description_en' => 'Standard paid leave request form',
                'category' => 'Ressources Humaines',
                'type' => 'form',
                'file_type' => 'pdf',
                'required_plan' => 'free',
                'usage_instructions' => 'À remplir 15 jours avant le départ. Validation par le responsable.',
                'usage_instructions_en' => 'Fill 15 days before departure. Supervisor approval required.',
            ],
            [
                'title' => 'Formulaire de Note de Frais',
                'title_en' => 'Expense Report Form',
                'description' => 'Formulaire de déclaration de frais professionnels',
                'description_en' => 'Professional expense declaration form',
                'category' => 'Gestion Administrative',
                'type' => 'form',
                'file_type' => 'xlsx',
                'required_plan' => 'student',
                'usage_instructions' => 'Joindre les justificatifs. Respecter les barèmes en vigueur.',
                'usage_instructions_en' => 'Attach receipts. Follow current rate schedules.',
            ],
        ];

        foreach ($templates as $templateData) {
            // Create template for each country
            foreach ($countries as $country) {
                DocumentTemplate::create([
                    'title' => $templateData['title'],
                    'title_en' => $templateData['title_en'],
                    'description' => $templateData['description'],
                    'description_en' => $templateData['description_en'],
                    'category' => $templateData['category'],
                    'type' => $templateData['type'],
                    'country' => $country,
                    'file_type' => $templateData['file_type'],
                    'file_path' => 'templates/' . Str::slug($templateData['title']) . '-' . $country . '.' . $templateData['file_type'],
                    'required_plan' => $templateData['required_plan'],
                    'usage_instructions' => $templateData['usage_instructions'],
                    'usage_instructions_en' => $templateData['usage_instructions_en'],
                    'tags' => json_encode(['juridique', 'entreprise', strtolower($templateData['category'])]),
                    'is_mobile_visible' => true,
                    'is_active' => true,
                    'views_count' => rand(10, 500),
                    'downloads_count' => rand(5, 250),
                ]);
            }
        }

        $this->command->info('Document templates seeded successfully!');
    }
}
