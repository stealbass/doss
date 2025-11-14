<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cases extends Model
{
    use HasFactory;

    protected $table = 'cases';
    protected $fillable = [
        'court',
        'highcourt',
        'bench',
        'casetype',
        'casenumber',
        'diarybumber',
        'year',
        'case_number',
        'filing_date',
        'title',
        'description',
        'under_acts',
        'under_sections',
        'FIR_number',
        'FIR_year',
        'court_room',
        'judge',
        'police_station',
        'your_party',
        'your_party_name',
        'opp_party_name',
        'stage',
        'advocates',
        'opp_adv',
        'case_docs',
        'journey',
        'motion',
    ];

    public static function getCasesById($id)
    {
        $cases = Cases::whereIn('id', explode(',', $id))->pluck('title')->toArray();
        return implode(',', $cases);
    }

    public static function caseType()
    {
        $types = [
            'Arbitration Petition' => 'Arbitration Petition',
            'Civil Appeal' => 'Civil Appeal',
            'Contempt Petition (Civil)' => 'Contempt Petition (Civil)',
            'Contempt Petition (Criminal)' => 'Contempt Petition (Criminal)',
            'Criminal Appeal' => 'Criminal Appeal',
            'Curative Petition(Civil)' => 'Curative Petition(Civil)',
            'Curative Petition(Criminal)' => 'Curative Petition(Criminal)',
            'Criminal Case' => 'Criminal Case',
            'DEATH REFERENCE CASE' => 'DEATH REFERENCE CASE',
            'DIARY NO.' => 'DIARY NO.',
            'DIARYNO AND DIARYYR' => 'DIARYNO AND DIARYYR',
            'Election Petition (Civil)' => 'Election Petition (Civil)',
            'FILE NUMBER' => 'FILE NUMBER',
            'MISCELLANEOUS APPLICATION' => 'MISCELLANEOUS APPLICATION',
            'Motion Case(Crl.)' => 'Motion Case(Crl.)',
            'Original Suit' => 'Original Suit',
            'Probate Case' => 'Probate Case',
            'Family Law Case' => 'Family Law Case',
            'Workers\' Compensation Case' => 'Workers\' Compensation Case',
            'Intellectual Property Case' => 'Intellectual Property Case',
            'REF. U/A 317(1)' => 'REF. U/A 317(1)',
            'REF. U/S 14 RTI' => 'REF. U/S 14 RTI',
            'REF. U/S 143' => 'REF. U/S 143',
            'REF. U/S 17 RTI' => 'REF. U/S 17 RTI',
            'Review Petition (Civil)' => 'Review Petition (Civil)',
            'Review Petition (Criminal)' => 'Review Petition (Criminal)',
            'SLP (Civil)' => 'SLP (Civil)',
            'SLP (Criminal)' => 'SLP (Criminal)',
            'SPECIAL LEAVE TO PETITION (CIVIL)' => 'SPECIAL LEAVE TO PETITION (CIVIL)',
            'SPECIAL LEAVE TO PETITION (CRIMINAL)' => 'SPECIAL LEAVE TO PETITION (CRIMINAL)',
            'Special Reference Case' => 'Special Reference Case',
            'Suo-Moto Contempt Pet.(Civil) D' => 'Suo-Moto Contempt Pet.(Civil) D',
            'Suo-Moto Contempt Pet.(Criminal) D' => 'Suo-Moto Contempt Pet.(Criminal) D',
            'Suo-Moto W.P(Civil) D' => 'Suo-Moto W.P(Civil) D',
            'Suo-Moto W.P(Criminal) D' => 'Suo-Moto W.P(Criminal) D',
            'Tax Reference Case' => 'Tax Reference Case',
            'Tranfer Case (Civil)' => 'Tranfer Case (Civil)',
            'Transfer Case (Criminal)' => 'Transfer Case (Criminal)',
            'Transfer Petition (Civil)' => 'Transfer Petition (Civil)',
            'Transfer Petition (Criminal)' => 'Transfer Petition (Criminal)',
            'Writ Petition (Civil)' => 'Writ Petition (Civil)',
            'Writ Petition(Criminal)' => 'Writ Petition(Criminal)',
            'WRIT TO PETITION (CIVIL)' => 'WRIT TO PETITION (CIVIL)',
            'WRIT TO PETITION (CRIMINAL)' => 'WRIT TO PETITION (CRIMINAL)',
        ];
        return $types;
    }

    public static function casePriority()
    {
        return [
            'Super Critical' => 'Super Critical',
            'Critical' => 'Critical',
            'Important' => 'Important',
            'Routine' => 'Routine',
            'Normal' => 'Normal',
        ];
    }

    public static function caseJourney()
    {
        return [
            'Phase d\'admission du client',
'Phase d\'évaluation et de stratégie du dossier',
'Phase de rédaction des actes de procédure et de découverte',
'Phase de pratique des requêtes et de pré-procès',
'Phase de procès',
'Phase post-procès',
'Phase de clôture du dossier',
'Consultation initiale du client',
'Évaluation du dossier',
'Rédaction des actes de procédure judiciaire (plainte, réponse, requêtes)',
'Préparer et plaider les requêtes',
'Préparer la stratégie de procès',
'Examiner le verdict et le jugement',
'Finaliser le règlement (le cas échéant)',
'Collecter les informations du client',
'Mener des recherches juridiques',
'Dépôt des actes au tribunal',
'Préparer les audiences au tribunal',
'Rédiger le mémoire de procès',
'Initier les actions d\'exécution',
'Vérification de l\'état du dossier',
'Évaluer le dossier',
'Analyser les lois pertinentes',
'Signifier/répondre à une demande de découverte',
'Assister aux conférences pré-procès du tribunal',
'Interroger les témoins',
'Dépôt des requêtes post-procès (verdict, demande de nouveau procès)',
'Se conformer/exécuter l\'ordonnance du tribunal',
'Signer le contrat avec le client',
'Analyser les précédents et les affaires similaires',
'Participer à la négociation et au règlement',
'Activités pré-procès (déposition, préparation des témoins)',
'Préparer et présenter les preuves',
'Assurer la conformité aux obligations du verdict du tribunal',
'Clôturer toutes les tâches administratives',
'Signer la lettre de mission',
'Évaluer les forces et faiblesses',
'Planification et programmation du dossier',
'Préparation des témoins',
'Préparer les arguments d\'ouverture et de clôture',
'Évaluation de la nécessité d\'un appel',
'Clôturer le dossier et archiver les dossiers',
'Collecter les documents requis',
'Élaborer la stratégie du dossier',
'Planification et demandes de découverte',
'Identifier les preuves et les pièces à conviction',
'Assister aux audiences du tribunal',
'Préparer les mémoires d\'appel',
'Clôturer la relation client-avocat',
'Assigner l\'équipe de travail',
'Discuter des conseils juridiques avec le client',
'Dépositions des témoins et experts',
'Préparation au procès',
'Répondre à l\'avocat adverse',
'Présenter le client pour l\'appel',
'Révision et audit final du dossier',
        ];
    }

    public function motion($id)
    {
        return Motion::where('id', $id)->value('type');
    }

    public function getCourt()
    {
        return $this->belongsTo(Court::class, 'court', 'id');
    }

}
