import 'package:flutter/material.dart';
import '../../../core/theme/app_colors.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import '../../../core/constants/app_constants.dart';
import '../../../data/providers/auth_provider.dart';

class QcmGeneratorScreen extends StatefulWidget {
  const QcmGeneratorScreen({Key? key}) : super(key: key);
  @override
  State<QcmGeneratorScreen> createState() => _QcmGeneratorScreenState();
}
class _QcmGeneratorScreenState extends State<QcmGeneratorScreen> {
  final _formKey = GlobalKey<FormState>();
  final _courseController = TextEditingController();
  int _numberOfQuestions = 10;
  String _difficulty = 'Moyen';
  String? _selectedDomain;
  bool _isGenerating = false;
  List<Map<String, dynamic>>? _generatedQcm;
  int _currentQuestionIndex = 0;
  Map<int, String> _userAnswers = {};
  bool _showResults = false;
  final List<String> _domains = [
    'Droit Civil',
    'Droit Pénal',
    'Droit Commercial',
    'Droit Administratif',
    'Droit du Travail',
    'Droit Constitutionnel',
  ];
  final List<String> _difficulties = ['Facile', 'Moyen', 'Difficile'];
  @override
  void dispose() {
    _courseController.dispose();
    super.dispose();
  }
  Future<void> _generateQcm() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _isGenerating = true;
      _generatedQcm = null;
      _userAnswers.clear();
      _showResults = false;
    });
    // Simulate API call
    await Future.delayed(const Duration(seconds: 2));
    // Mock generated QCM
    setState(() {
      _generatedQcm = List.generate(_numberOfQuestions, (index) {
        return {
          'question': 'Question ${index + 1}: Quelle est la définition juridique de la prescription acquisitive en droit civil ?',
          'options': [
            'A) Mode d\'extinction d\'une obligation par l\'écoulement du temps',
            'B) Mode d\'acquisition de la propriété par possession prolongée',
            'C) Délai pour exercer une action en justice',
            'D) Durée de validité d\'un contrat',
          ],
          'correctAnswer': 'B',
          'explanation': 'La prescription acquisitive (ou usucapion) est un mode d\'acquisition de la propriété ou d\'un droit réel par l\'effet d\'une possession prolongée pendant le délai fixé par la loi.',
        };
      });
      _isGenerating = false;
      _currentQuestionIndex = 0;
    });
  }
  void _submitAnswer(String answer) {
    setState(() {
      _userAnswers[_currentQuestionIndex] = answer;
    });
  }
  void _nextQuestion() {
    if (_currentQuestionIndex < _generatedQcm!.length - 1) {
      setState(() {
        _currentQuestionIndex++;
      });
    }
  }
  void _previousQuestion() {
    if (_currentQuestionIndex > 0) {
      setState(() {
        _currentQuestionIndex--;
      });
    }
  }
  void _finishQuiz() {
    setState(() {
      _showResults = true;
    });
  }
  int _calculateScore() {
    int correctAnswers = 0;
    for (int i = 0; i < _generatedQcm!.length; i++) {
      if (_userAnswers[i] == _generatedQcm![i]['correctAnswer']) {
        correctAnswers++;
      }
    }
    return correctAnswers;
  }
  @override
  Widget build(BuildContext context) {
    final authProvider = Provider.of<AuthProvider>(context);
    final user = authProvider.user;
    final colorScheme = Theme.of(context).colorScheme;
    final locale = Localizations.localeOf(context);
    final isFr = locale.languageCode == 'fr';
    final hasAccess = user?.plan != 'free';
    return Scaffold(
      appBar: AppBar(
        title: Text(isFr ? 'Générateur de QCM' : 'MCQ Generator'),
        centerTitle: true,
      ),
      body: hasAccess
          ? _generatedQcm == null
              ? _buildGeneratorForm(context, isFr, colorScheme)
              : _showResults
                  ? _buildResultsView(context, isFr, colorScheme)
                  : _buildQuizView(context, isFr, colorScheme)
          : _buildUpgradePrompt(context, isFr),
    );
  }
  Widget _buildGeneratorForm(BuildContext context, bool isFr, ColorScheme colorScheme) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.orange.shade50,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: Colors.orange.shade200),
            ),
            child: Row(
              children: [
                Icon(
                  Icons.quiz,
                  size: 40,
                  color: Colors.orange.shade700,
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        isFr ? 'QCM Personnalisé' : 'Custom Quiz',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: Colors.orange.shade900,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        isFr
                            ? 'Générez un quiz adapté à votre cours'
                            : 'Generate a quiz tailored to your course',
                        style: TextStyle(
                          fontSize: 13,
                          color: Colors.orange.shade700,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),
          Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Domain selection
                Text(
                  isFr ? 'Domaine' : 'Domain',
                  style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 8),
                DropdownButtonFormField<String>(
                  value: _selectedDomain,
                  decoration: InputDecoration(
                    hintText: isFr ? 'Choisissez le domaine' : 'Choose domain',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                  ),
                  items: _domains.map((d) => DropdownMenuItem(value: d, child: Text(d))).toList(),
                  onChanged: (value) => setState(() => _selectedDomain = value),
                  validator: (value) => value == null ? (isFr ? 'Requis' : 'Required') : null,
                ),
                const SizedBox(height: 20),
                // Number of questions
                Text(
                  isFr ? 'Nombre de questions' : 'Number of questions',
                  style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 8),
                Row(
                  children: [
                    Expanded(
                      child: Slider(
                        value: _numberOfQuestions.toDouble(),
                        min: 5,
                        max: 30,
                        divisions: 5,
                        label: _numberOfQuestions.toString(),
                        activeColor: AppColors.primaryGreen,
                        onChanged: (value) {
                          setState(() => _numberOfQuestions = value.toInt());
                        },
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                      decoration: BoxDecoration(
                        color: AppColors.primaryGreen.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(
                        _numberOfQuestions.toString(),
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: AppColors.primaryGreen,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 20),
                // Difficulty
                Text(
                  isFr ? 'Difficulté' : 'Difficulty',
                  style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 8),
                SegmentedButton<String>(
                  segments: _difficulties.map((d) {
                    return ButtonSegment(value: d, label: Text(d));
                  }).toList(),
                  selected: {_difficulty},
                  onSelectionChanged: (Set<String> newSelection) {
                    setState(() => _difficulty = newSelection.first);
                  },
                ),
                const SizedBox(height: 20),
                // Course content
                Text(
                  isFr ? 'Contenu du cours (optionnel)' : 'Course content (optional)',
                  style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 8),
                TextFormField(
                  controller: _courseController,
                  maxLines: 6,
                  decoration: InputDecoration(
                    hintText: isFr
                        ? 'Collez le contenu de votre cours pour un QCM personnalisé...'
                        : 'Paste your course content for a custom quiz...',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                    contentPadding: const EdgeInsets.all(16),
                  ),
                ),
                const SizedBox(height: 24),
                // Generate button
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _isGenerating ? null : _generateQcm,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.orange.shade600,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    ),
                    child: _isGenerating
                        ? Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              SizedBox(
                                width: 20,
                                height: 20,
                                child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                              ),
                              const SizedBox(width: 12),
                              Text(isFr ? 'Génération...' : 'Generating...'),
                            ],
                          )
                        : Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(Icons.auto_awesome),
                              const SizedBox(width: 8),
                              Text(
                                isFr ? 'Générer le QCM' : 'Generate Quiz',
                                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                              ),
                            ],
                          ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
  Widget _buildQuizView(BuildContext context, bool isFr, ColorScheme colorScheme) {
    final currentQuestion = _generatedQcm![_currentQuestionIndex];
    final progress = (_currentQuestionIndex + 1) / _generatedQcm!.length;
    return Column(
      children: [
        // Progress bar
        LinearProgressIndicator(
          value: progress,
          backgroundColor: colorScheme.surfaceVariant,
          color: AppColors.primaryGreen,
          minHeight: 6,
        ),
        Expanded(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Question counter
                Text(
                  '${isFr ? 'Question' : 'Question'} ${_currentQuestionIndex + 1}/${_generatedQcm!.length}',
                  style: TextStyle(
                    fontSize: 14,
                    color: AppColors.primaryGreen,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 16),
                // Question
                Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: colorScheme.surface,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: colorScheme.outline.withOpacity(0.2)),
                  ),
                  child: Text(
                    currentQuestion['question'],
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.w500, height: 1.5),
                  ),
                ),
                const SizedBox(height: 24),
                // Options
                ...List.generate(currentQuestion['options'].length, (index) {
                  final option = currentQuestion['options'][index];
                  final optionLetter = option.substring(0, 1);
                  final isSelected = _userAnswers[_currentQuestionIndex] == optionLetter;
                  return Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: Material(
                      color: Colors.transparent,
                      child: InkWell(
                        onTap: () => _submitAnswer(optionLetter),
                        borderRadius: BorderRadius.circular(12),
                        child: Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: isSelected
                                ? AppColors.primaryGreen.withOpacity(0.1)
                                : colorScheme.surface,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(
                              color: isSelected
                                  ? AppColors.primaryGreen
                                  : colorScheme.outline.withOpacity(0.2),
                              width: isSelected ? 2 : 1,
                            ),
                          ),
                          child: Row(
                            children: [
                              Container(
                                width: 28,
                                height: 28,
                                decoration: BoxDecoration(
                                  color: isSelected
                                      ? AppColors.primaryGreen
                                      : Colors.transparent,
                                  shape: BoxShape.circle,
                                  border: Border.all(
                                    color: isSelected
                                        ? AppColors.primaryGreen
                                        : colorScheme.outline.withOpacity(0.5),
                                  ),
                                ),
                                child: isSelected
                                    ? Icon(Icons.check, size: 18, color: Colors.white)
                                    : null,
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Text(
                                  option,
                                  style: TextStyle(
                                    fontSize: 14,
                                    fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  );
                }),
              ],
            ),
          ),
        ),
        // Navigation buttons
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: colorScheme.surface,
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(0.05),
                blurRadius: 10,
                offset: Offset(0, -2),
              ),
            ],
          ),
          child: Row(
            children: [
              if (_currentQuestionIndex > 0)
                Expanded(
                  child: OutlinedButton(
                    onPressed: _previousQuestion,
                    child: Text(isFr ? 'Précédent' : 'Previous'),
                  ),
                ),
              if (_currentQuestionIndex > 0) const SizedBox(width: 12),
              Expanded(
                flex: 2,
                child: ElevatedButton(
                  onPressed: _userAnswers.containsKey(_currentQuestionIndex)
                      ? (_currentQuestionIndex == _generatedQcm!.length - 1
                          ? _finishQuiz
                          : _nextQuestion)
                      : null,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primaryGreen,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                  ),
                  child: Text(
                    _currentQuestionIndex == _generatedQcm!.length - 1
                        ? (isFr ? 'Terminer' : 'Finish')
                        : (isFr ? 'Suivant' : 'Next'),
                    style: TextStyle(fontWeight: FontWeight.bold),
                  ),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
  Widget _buildResultsView(BuildContext context, bool isFr, ColorScheme colorScheme) {
    final score = _calculateScore();
    final percentage = (score / _generatedQcm!.length * 100).toInt();
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        children: [
          // Score card
          Container(
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [AppColors.primaryGreen, AppColors.primaryGreen.withOpacity(0.7)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Column(
              children: [
                Icon(Icons.emoji_events, size: 60, color: Colors.white),
                const SizedBox(height: 16),
                Text(
                  isFr ? 'Résultats' : 'Results',
                  style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: Colors.white),
                ),
                const SizedBox(height: 8),
                Text(
                  '$score / ${_generatedQcm!.length}',
                  style: TextStyle(fontSize: 48, fontWeight: FontWeight.bold, color: Colors.white),
                ),
                Text(
                  '$percentage%',
                  style: TextStyle(fontSize: 20, color: Colors.white.withOpacity(0.9)),
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),
          // Detailed answers
          Text(
            isFr ? 'Réponses détaillées' : 'Detailed answers',
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 16),
          ...List.generate(_generatedQcm!.length, (index) {
            final question = _generatedQcm![index];
            final userAnswer = _userAnswers[index] ?? '';
            final correctAnswer = question['correctAnswer'];
            final isCorrect = userAnswer == correctAnswer;
            return Container(
              margin: const EdgeInsets.only(bottom: 16),
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: colorScheme.surface,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                  color: isCorrect ? Colors.green : Colors.red,
                  width: 2,
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Icon(
                        isCorrect ? Icons.check_circle : Icons.cancel,
                        color: isCorrect ? Colors.green : Colors.red,
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          '${isFr ? 'Question' : 'Question'} ${index + 1}',
                          style: TextStyle(fontWeight: FontWeight.bold),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Text(question['question']),
                  const SizedBox(height: 12),
                  if (!isCorrect) ...[
                    Text(
                      '${isFr ? 'Votre réponse' : 'Your answer'}: $userAnswer',
                      style: TextStyle(color: Colors.red),
                    ),
                    const SizedBox(height: 4),
                  ],
                  Text(
                    '${isFr ? 'Bonne réponse' : 'Correct answer'}: $correctAnswer',
                    style: TextStyle(color: Colors.green, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 8),
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.blue.shade50,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      question['explanation'],
                      style: TextStyle(fontSize: 13, color: Colors.blue.shade900),
                    ),
                  ),
                ],
              ),
            );
          }),
          const SizedBox(height: 24),
          // Actions
          Row(
            children: [
              Expanded(
                child: OutlinedButton(
                  onPressed: () {
                    setState(() {
                      _generatedQcm = null;
                      _userAnswers.clear();
                      _showResults = false;
                    });
                  },
                  child: Text(isFr ? 'Nouveau QCM' : 'New Quiz'),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: ElevatedButton(
                  onPressed: () {
                    setState(() {
                      _userAnswers.clear();
                      _showResults = false;
                      _currentQuestionIndex = 0;
                    });
                  },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primaryGreen,
                    foregroundColor: Colors.white,
                  ),
                  child: Text(isFr ? 'Recommencer' : 'Retry'),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
  Widget _buildUpgradePrompt(BuildContext context, bool isFr) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.lock, size: 80, color: Colors.grey.shade400),
            const SizedBox(height: 24),
            Text(
              isFr ? 'Fonctionnalité Premium' : 'Premium Feature',
              style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 12),
            Text(
              isFr
                  ? 'Le générateur de QCM est réservé aux abonnés.'
                  : 'The MCQ Generator is reserved for subscribers.',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 16, color: Colors.grey.shade600),
            ),
            const SizedBox(height: 32),
            ElevatedButton(
              onPressed: () => Navigator.pushNamed(context, '/subscription-plans'),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primaryGreen,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 16),
              ),
              child: Text(
                isFr ? 'Voir les plans' : 'View plans',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
