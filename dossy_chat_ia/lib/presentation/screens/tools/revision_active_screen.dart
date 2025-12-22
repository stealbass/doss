import 'package:flutter/material.dart';
import '../../../core/theme/app_colors.dart';
import 'package:provider/provider.dart';
import '../../../core/constants/app_constants.dart';
import '../../../data/providers/auth_provider.dart';

class RevisionActiveScreen extends StatefulWidget {
  const RevisionActiveScreen({Key? key}) : super(key: key);
  @override
  State<RevisionActiveScreen> createState() => _RevisionActiveScreenState();
}
class _RevisionActiveScreenState extends State<RevisionActiveScreen>
    with SingleTickerProviderStateMixin {
  bool _isSessionActive = false;
  int _currentCardIndex = 0;
  bool _showAnswer = false;
  late AnimationController _flipController;
  late Animation<double> _flipAnimation;
  // Mock flashcards data
  final List<Map<String, dynamic>> _flashcards = [
    {
      'domain': 'Droit Civil',
      'question': 'Qu\'est-ce que la prescription acquisitive ?',
      'answer':
          'La prescription acquisitive (ou usucapion) est un mode d\'acquisition de la propriété ou d\'un droit réel par l\'effet d\'une possession prolongée pendant le délai fixé par la loi.\n\nConditions :\n• Possession continue et paisible\n• Possession publique et non équivoque\n• Possession à titre de propriétaire\n• Délai de 10 ou 30 ans selon les cas',
      'difficulty': 'Moyen',
      'tags': ['Propriété', 'Prescription', 'Biens'],
    },
    {
      'domain': 'Droit Pénal',
      'question': 'Quelle est la différence entre dol général et dol spécial ?',
      'answer':
          'Dol général : Conscience et volonté de commettre l\'acte prohibé par la loi pénale.\n\nDol spécial : Intention particulière exigée pour certaines infractions (ex: intention de tuer pour le meurtre).\n\nLe dol spécial s\'ajoute au dol général pour caractériser l\'élément moral de certaines infractions.',
      'difficulty': 'Difficile',
      'tags': ['Élément moral', 'Dol', 'Infraction'],
    },
    {
      'domain': 'Droit Commercial',
      'question': 'Définissez l\'acte de commerce par nature',
      'answer':
          'L\'acte de commerce par nature est un acte considéré comme commercial en raison de sa nature propre, indépendamment de la qualité de celui qui l\'accomplit.\n\nExemples :\n• Achat pour revendre\n• Opérations de banque\n• Opérations de bourse\n• Transport de marchandises',
      'difficulty': 'Facile',
      'tags': ['Actes de commerce', 'Droit commercial'],
    },
  ];
  Map<int, String> _cardRatings = {};
  @override
  void initState() {
    super.initState();
    _flipController = AnimationController(
      duration: const Duration(milliseconds: 400),
      vsync: this,
    );
    _flipAnimation = Tween<double>(begin: 0, end: 1).animate(
      CurvedAnimation(parent: _flipController, curve: Curves.easeInOut),
    );
  }
  @override
  void dispose() {
    _flipController.dispose();
    super.dispose();
  }
  void _startSession() {
    setState(() {
      _isSessionActive = true;
      _currentCardIndex = 0;
      _showAnswer = false;
      _cardRatings.clear();
    });
  }
  void _flipCard() {
    if (_showAnswer) {
      _flipController.reverse();
    } else {
      _flipController.forward();
    }
    setState(() {
      _showAnswer = !_showAnswer;
    });
  }
  void _rateCard(String rating) {
    setState(() {
      _cardRatings[_currentCardIndex] = rating;
    });
  }
  void _nextCard() {
    if (_currentCardIndex < _flashcards.length - 1) {
      setState(() {
        _currentCardIndex++;
        _showAnswer = false;
        _flipController.reset();
      });
    } else {
      _endSession();
    }
  }
  void _previousCard() {
    if (_currentCardIndex > 0) {
      setState(() {
        _currentCardIndex--;
        _showAnswer = false;
        _flipController.reset();
      });
    }
  }
  void _endSession() {
    setState(() {
      _isSessionActive = false;
    });
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
        title: Text(isFr ? 'Révision Active' : 'Active Revision'),
        centerTitle: true,
        actions: _isSessionActive
            ? [
                IconButton(
                  icon: Icon(Icons.close),
                  onPressed: () {
                    showDialog(
                      context: context,
                      builder: (context) => AlertDialog(
                        title: Text(isFr ? 'Quitter la session ?' : 'End session?'),
                        content: Text(
                          isFr
                              ? 'Voulez-vous vraiment arrêter cette session de révision ?'
                              : 'Do you really want to end this revision session?',
                        ),
                        actions: [
                          TextButton(
                            onPressed: () => Navigator.pop(context),
                            child: Text(isFr ? 'Non' : 'No'),
                          ),
                          TextButton(
                            onPressed: () {
                              Navigator.pop(context);
                              _endSession();
                            },
                            child: Text(
                              isFr ? 'Oui' : 'Yes',
                              style: TextStyle(color: Colors.red),
                            ),
                          ),
                        ],
                      ),
                    );
                  },
                )
              ]
            : null,
      ),
      body: hasAccess
          ? (_isSessionActive
              ? _buildActiveSession(context, isFr, colorScheme)
              : _buildStartScreen(context, isFr, colorScheme))
          : _buildUpgradePrompt(context, isFr),
    );
  }
  Widget _buildStartScreen(BuildContext context, bool isFr, ColorScheme colorScheme) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [Colors.purple.shade400, Colors.purple.shade600],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Icon(Icons.psychology, size: 48, color: Colors.white),
                const SizedBox(height: 12),
                Text(
                  isFr ? 'Révision Active' : 'Active Revision',
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  isFr
                      ? 'Flashcards intelligentes basées sur la répétition espacée'
                      : 'Smart flashcards based on spaced repetition',
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.white.withOpacity(0.9),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),
          // Stats
          Row(
            children: [
              Expanded(
                child: _StatCard(
                  icon: Icons.collections_bookmark,
                  value: '${_flashcards.length}',
                  label: isFr ? 'Cartes' : 'Cards',
                  color: Colors.blue,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _StatCard(
                  icon: Icons.trending_up,
                  value: '78%',
                  label: isFr ? 'Maîtrise' : 'Mastery',
                  color: Colors.green,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _StatCard(
                  icon: Icons.local_fire_department,
                  value: '5',
                  label: isFr ? 'Série' : 'Streak',
                  color: Colors.orange,
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),
          // How it works
          Text(
            isFr ? 'Comment ça marche ?' : 'How it works?',
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 16),
          _HowItWorksStep(
            number: '1',
            title: isFr ? 'Lisez la question' : 'Read the question',
            description: isFr
                ? 'Prenez le temps de bien comprendre ce qui est demandé'
                : 'Take time to understand what is being asked',
          ),
          _HowItWorksStep(
            number: '2',
            title: isFr ? 'Réfléchissez à la réponse' : 'Think about the answer',
            description: isFr
                ? 'Essayez de formuler mentalement la réponse'
                : 'Try to mentally formulate the answer',
          ),
          _HowItWorksStep(
            number: '3',
            title: isFr ? 'Retournez la carte' : 'Flip the card',
            description: isFr
                ? 'Vérifiez si votre réponse était correcte'
                : 'Check if your answer was correct',
          ),
          _HowItWorksStep(
            number: '4',
            title: isFr ? 'Évaluez votre maîtrise' : 'Rate your mastery',
            description: isFr
                ? 'Indiquez si vous avez su, hésité, ou ne saviez pas'
                : 'Indicate if you knew, hesitated, or didn\'t know',
          ),
          const SizedBox(height: 32),
          // Start button
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: _startSession,
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.purple.shade600,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 16),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.play_arrow, size: 28),
                  const SizedBox(width: 8),
                  Text(
                    isFr ? 'Commencer la révision' : 'Start revision',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
  Widget _buildActiveSession(BuildContext context, bool isFr, ColorScheme colorScheme) {
    final currentCard = _flashcards[_currentCardIndex];
    final progress = (_currentCardIndex + 1) / _flashcards.length;
    return Column(
      children: [
        // Progress
        LinearProgressIndicator(
          value: progress,
          backgroundColor: colorScheme.surfaceVariant,
          color: Colors.purple,
          minHeight: 6,
        ),
        Expanded(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              children: [
                // Counter
                Text(
                  '${_currentCardIndex + 1} / ${_flashcards.length}',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: Colors.purple.shade600,
                  ),
                ),
                const SizedBox(height: 8),
                // Tags
                Wrap(
                  spacing: 8,
                  children: [
                    Chip(
                      label: Text(
                        currentCard['domain'],
                        style: TextStyle(fontSize: 12),
                      ),
                      backgroundColor: Colors.blue.shade100,
                      padding: EdgeInsets.zero,
                    ),
                    Chip(
                      label: Text(
                        currentCard['difficulty'],
                        style: TextStyle(fontSize: 12),
                      ),
                      backgroundColor: Colors.orange.shade100,
                      padding: EdgeInsets.zero,
                    ),
                  ],
                ),
                const SizedBox(height: 24),
                // Flashcard
                Expanded(
                  child: GestureDetector(
                    onTap: _flipCard,
                    child: AnimatedBuilder(
                      animation: _flipAnimation,
                      builder: (context, child) {
                        final angle = _flipAnimation.value * 3.14159;
                        final isFront = angle < 1.5708;
                        return Transform(
                          alignment: Alignment.center,
                          transform: Matrix4.identity()
                            ..setEntry(3, 2, 0.001)
                            ..rotateY(angle),
                          child: Container(
                            width: double.infinity,
                            padding: const EdgeInsets.all(24),
                            decoration: BoxDecoration(
                              gradient: LinearGradient(
                                colors: isFront
                                    ? [Colors.purple.shade400, Colors.purple.shade600]
                                    : [Colors.green.shade400, Colors.green.shade600],
                                begin: Alignment.topLeft,
                                end: Alignment.bottomRight,
                              ),
                              borderRadius: BorderRadius.circular(20),
                              boxShadow: [
                                BoxShadow(
                                  color: Colors.black.withOpacity(0.2),
                                  blurRadius: 20,
                                  offset: Offset(0, 10),
                                ),
                              ],
                            ),
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(
                                  isFront ? Icons.help_outline : Icons.lightbulb_outline,
                                  size: 48,
                                  color: Colors.white.withOpacity(0.8),
                                ),
                                const SizedBox(height: 24),
                                Transform(
                                  alignment: Alignment.center,
                                  transform: Matrix4.identity()..rotateY(isFront ? 0 : 3.14159),
                                  child: Text(
                                    isFront ? currentCard['question'] : currentCard['answer'],
                                    textAlign: TextAlign.center,
                                    style: TextStyle(
                                      fontSize: isFront ? 20 : 16,
                                      fontWeight: isFront ? FontWeight.bold : FontWeight.normal,
                                      color: Colors.white,
                                      height: 1.5,
                                    ),
                                  ),
                                ),
                                const SizedBox(height: 24),
                                Text(
                                  isFr
                                      ? (isFront ? 'Appuyez pour voir la réponse' : 'Appuyez pour voir la question')
                                      : (isFront ? 'Tap to see answer' : 'Tap to see question'),
                                  style: TextStyle(
                                    fontSize: 14,
                                    color: Colors.white.withOpacity(0.8),
                                    fontStyle: FontStyle.italic,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        );
                      },
                    ),
                  ),
                ),
                const SizedBox(height: 24),
                // Rating buttons (only when showing answer)
                if (_showAnswer) ...[
                  Text(
                    isFr ? 'Comment avez-vous réussi ?' : 'How did you do?',
                    style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: _RatingButton(
                          label: isFr ? 'Je ne savais pas' : 'Didn\'t know',
                          icon: Icons.close,
                          color: Colors.red,
                          isSelected: _cardRatings[_currentCardIndex] == 'hard',
                          onTap: () => _rateCard('hard'),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: _RatingButton(
                          label: isFr ? 'J\'ai hésité' : 'Hesitated',
                          icon: Icons.remove,
                          color: Colors.orange,
                          isSelected: _cardRatings[_currentCardIndex] == 'medium',
                          onTap: () => _rateCard('medium'),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: _RatingButton(
                          label: isFr ? 'Je savais' : 'Knew it',
                          icon: Icons.check,
                          color: Colors.green,
                          isSelected: _cardRatings[_currentCardIndex] == 'easy',
                          onTap: () => _rateCard('easy'),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                ],
                // Navigation
                Row(
                  children: [
                    if (_currentCardIndex > 0)
                      IconButton(
                        onPressed: _previousCard,
                        icon: Icon(Icons.arrow_back),
                        style: IconButton.styleFrom(
                          backgroundColor: colorScheme.surfaceVariant,
                        ),
                      ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: ElevatedButton(
                        onPressed: _cardRatings.containsKey(_currentCardIndex) ? _nextCard : null,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.purple.shade600,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 14),
                        ),
                        child: Text(
                          _currentCardIndex == _flashcards.length - 1
                              ? (isFr ? 'Terminer' : 'Finish')
                              : (isFr ? 'Suivant' : 'Next'),
                          style: TextStyle(fontWeight: FontWeight.bold),
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ],
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
                  ? 'La Révision Active est réservée aux abonnés.'
                  : 'Active Revision is reserved for subscribers.',
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
// Stat Card Widget
class _StatCard extends StatelessWidget {
  final IconData icon;
  final String value;
  final String label;
  final Color color;
  const _StatCard({
    required this.icon,
    required this.value,
    required this.label,
    required this.color,
  });
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withOpacity(0.3)),
      ),
      child: Column(
        children: [
          Icon(icon, color: color),
          const SizedBox(height: 8),
          Text(
            value,
            style: TextStyle(
              fontSize: 24,
              fontWeight: FontWeight.bold,
              color: color,
            ),
          ),
          Text(
            label,
            style: TextStyle(fontSize: 12, color: color.withOpacity(0.8)),
          ),
        ],
      ),
    );
  }
}
// How It Works Step Widget
class _HowItWorksStep extends StatelessWidget {
  final String number;
  final String title;
  final String description;
  const _HowItWorksStep({
    required this.number,
    required this.title,
    required this.description,
  });
  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 32,
            height: 32,
            decoration: BoxDecoration(
              color: Colors.purple.shade600,
              shape: BoxShape.circle,
            ),
            child: Center(
              child: Text(
                number,
                style: TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 4),
                Text(
                  description,
                  style: TextStyle(fontSize: 14, color: Colors.grey.shade600),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
// Rating Button Widget
class _RatingButton extends StatelessWidget {
  final String label;
  final IconData icon;
  final Color color;
  final bool isSelected;
  final VoidCallback onTap;
  const _RatingButton({
    required this.label,
    required this.icon,
    required this.color,
    required this.isSelected,
    required this.onTap,
  });
  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(8),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 12),
          decoration: BoxDecoration(
            color: isSelected ? color.withOpacity(0.2) : Colors.transparent,
            borderRadius: BorderRadius.circular(8),
            border: Border.all(
              color: isSelected ? color : color.withOpacity(0.5),
              width: isSelected ? 2 : 1,
            ),
          ),
          child: Column(
            children: [
              Icon(icon, color: color, size: 24),
              const SizedBox(height: 4),
              Text(
                label,
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                  color: color,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
