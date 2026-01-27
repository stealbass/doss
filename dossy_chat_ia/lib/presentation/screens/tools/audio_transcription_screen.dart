import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import '../../../core/constants/app_constants.dart';
import '../../../data/providers/auth_provider.dart';

class AudioTranscriptionScreen extends StatefulWidget {
  const AudioTranscriptionScreen({super.key});

  @override
  State<AudioTranscriptionScreen> createState() => _AudioTranscriptionScreenState();
}

class _AudioTranscriptionScreenState extends State<AudioTranscriptionScreen> {
  bool _isRecording = false;
  bool _isTranscribing = false;
  String? _selectedAudioFile;
  String? _transcribedText;
  double _recordingDuration = 0;

  final List<Map<String, dynamic>> _transcriptionHistory = [
    {
      'title': 'Cours Droit Civil - 15 Jan 2024',
      'duration': '1h 23min',
      'wordsCount': '12,450',
      'date': '2024-01-15',
    },
    {
      'title': 'TD Droit Pénal - 12 Jan 2024',
      'duration': '45min',
      'wordsCount': '5,230',
      'date': '2024-01-12',
    },
  ];

  Future<void> _pickAudioFile() async {
    // Simulate file picker
    await Future.delayed(const Duration(milliseconds: 500));
    setState(() {
      _selectedAudioFile = 'cours_droit_civil_20240115.mp3';
    });
  }

  Future<void> _startRecording() async {
    setState(() {
      _isRecording = true;
      _recordingDuration = 0;
    });

    // Simulate recording
    while (_isRecording && _recordingDuration < 3600) {
      await Future.delayed(const Duration(seconds: 1));
      if (_isRecording) {
        setState(() {
          _recordingDuration++;
        });
      }
    }
  }

  void _stopRecording() {
    setState(() {
      _isRecording = false;
    });
  }

  Future<void> _transcribeAudio() async {
    setState(() {
      _isTranscribing = true;
      _transcribedText = null;
    });

    // Simulate transcription
    await Future.delayed(const Duration(seconds: 3));

    setState(() {
      _transcribedText = '''
Bonjour à tous, aujourd'hui nous allons étudier la notion de prescription acquisitive en droit civil.

La prescription acquisitive, également appelée usucapion, est un mode d'acquisition de la propriété ou d'un droit réel par l'effet d'une possession prolongée pendant le délai fixé par la loi.

Pour qu'il y ait prescription acquisitive, plusieurs conditions doivent être réunies :

Premièrement, la possession doit être continue et non interrompue. Cela signifie que le possesseur doit avoir exercé de manière constante les attributs du droit de propriété pendant toute la durée requise.

Deuxièmement, la possession doit être paisible. Elle ne doit pas avoir été obtenue par violence ou par des moyens illégaux.

Troisièmement, la possession doit être publique et non équivoque. Le possesseur doit se comporter comme le véritable propriétaire aux yeux de tous.

Quatrièmement, la possession doit être exercée à titre de propriétaire, c'est-à-dire "animo domini". Le possesseur doit avoir l'intention de se comporter comme le propriétaire du bien.

Enfin, la possession doit durer pendant le délai prévu par la loi, qui est généralement de 30 ans, mais peut être réduit à 10 ans entre personnes de bonne foi avec juste titre.

Il est important de noter que la prescription acquisitive peut être invoquée pour tous les biens, qu'ils soient meubles ou immeubles, sous réserve de certaines exceptions prévues par la loi.

Y a-t-il des questions sur ce point ?
''';
      _isTranscribing = false;
    });
  }

  String _formatDuration(double seconds) {
    final minutes = (seconds / 60).floor();
    final secs = (seconds % 60).floor();
    return '${minutes.toString().padLeft(2, '0')}:${secs.toString().padLeft(2, '0')}';
  }

  void _copyToClipboard(String text) {
    Clipboard.setData(ClipboardData(text: text));
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Copié dans le presse-papier'),
        backgroundColor: AppConstants.primaryGreen,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final authProvider = Provider.of<AuthProvider>(context);
    final user = authProvider.user;
    final colorScheme = Theme.of(context).colorScheme;
    final locale = Localizations.localeOf(context);
    final isFr = locale.languageCode == 'fr';

    // Check if user has access to audio transcription (Professional or Enterprise plans)
    final planLower = user?.plan?.toLowerCase() ?? '';
    final hasAccess = planLower.contains('professionnel') || planLower.contains('cabinet') || planLower.contains('entreprise');

    return Scaffold(
      appBar: AppBar(
        title: Text(isFr ? 'Transcription Audio' : 'Audio Transcription'),
        centerTitle: true,
      ),
      body: hasAccess
          ? SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Header
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.teal.shade50,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: Colors.teal.shade200),
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.mic, size: 40, color: Colors.teal.shade700),
                        const SizedBox(width: 16),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                isFr ? 'Transcription Audio → Texte' : 'Audio → Text Transcription',
                                style: TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.teal.shade900,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                isFr
                                    ? 'Convertissez vos cours audio en texte exploitable'
                                    : 'Convert your audio lectures to actionable text',
                                style: TextStyle(fontSize: 13, color: Colors.teal.shade700),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 24),

                  // Record or Upload Section
                  Text(
                    isFr ? 'Enregistrer ou importer' : 'Record or import',
                    style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 16),

                  // Recording Card
                  Container(
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      color: colorScheme.surface,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: colorScheme.outline.withValues(alpha: 0.2)),
                    ),
                    child: Column(
                      children: [
                        if (_isRecording) ...[
                          const Icon(
                            Icons.fiber_manual_record,
                            size: 60,
                            color: Colors.red,
                          ),
                          const SizedBox(height: 12),
                          Text(
                            isFr ? 'Enregistrement en cours...' : 'Recording...',
                            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            _formatDuration(_recordingDuration),
                            style: const TextStyle(
                              fontSize: 32,
                              fontWeight: FontWeight.bold,
                              color: Colors.red,
                            ),
                          ),
                          const SizedBox(height: 16),
                          ElevatedButton.icon(
                            onPressed: _stopRecording,
                            icon: const Icon(Icons.stop),
                            label: Text(isFr ? 'Arrêter' : 'Stop'),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: Colors.red,
                              foregroundColor: Colors.white,
                              padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 12),
                            ),
                          ),
                        ] else ...[
                          Icon(Icons.mic_none, size: 60, color: Colors.grey.shade400),
                          const SizedBox(height: 12),
                          Text(
                            isFr ? 'Enregistrer un audio' : 'Record audio',
                            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            isFr ? 'Enregistrez directement depuis votre appareil' : 'Record directly from your device',
                            textAlign: TextAlign.center,
                            style: TextStyle(fontSize: 13, color: Colors.grey.shade600),
                          ),
                          const SizedBox(height: 16),
                          Row(
                            children: [
                              Expanded(
                                child: OutlinedButton.icon(
                                  onPressed: _startRecording,
                                  icon: const Icon(Icons.mic),
                                  label: Text(isFr ? 'Enregistrer' : 'Record'),
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: OutlinedButton.icon(
                                  onPressed: _pickAudioFile,
                                  icon: const Icon(Icons.upload_file),
                                  label: Text(isFr ? 'Importer' : 'Import'),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ],
                    ),
                  ),

                  if (_selectedAudioFile != null) ...[
                    const SizedBox(height: 16),
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: Colors.green.shade50,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.green.shade200),
                      ),
                      child: Row(
                        children: [
                          Icon(Icons.audio_file, color: Colors.green.shade700),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  _selectedAudioFile!,
                                  style: const TextStyle(fontWeight: FontWeight.bold),
                                ),
                                Text(
                                  isFr ? 'Fichier sélectionné' : 'File selected',
                                  style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                                ),
                              ],
                            ),
                          ),
                          IconButton(
                            icon: const Icon(Icons.close, color: Colors.red),
                            onPressed: () {
                              setState(() {
                                _selectedAudioFile = null;
                              });
                            },
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: _isTranscribing ? null : _transcribeAudio,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.teal.shade600,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 16),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(8),
                          ),
                        ),
                        child: _isTranscribing
                            ? Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  const SizedBox(
                                    width: 20,
                                    height: 20,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                      color: Colors.white,
                                    ),
                                  ),
                                  const SizedBox(width: 12),
                                  Text(isFr ? 'Transcription...' : 'Transcribing...'),
                                ],
                              )
                            : Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  const Icon(Icons.auto_awesome),
                                  const SizedBox(width: 8),
                                  Text(
                                    isFr ? 'Transcrire' : 'Transcribe',
                                    style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                                  ),
                                ],
                              ),
                      ),
                    ),
                  ],

                  // Transcription Result
                  if (_transcribedText != null) ...[
                    const SizedBox(height: 32),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          isFr ? '📝 Transcription' : '📝 Transcription',
                          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                        ),
                        Row(
                          children: [
                            IconButton(
                              icon: const Icon(Icons.copy),
                              onPressed: () => _copyToClipboard(_transcribedText!),
                              tooltip: isFr ? 'Copier' : 'Copy',
                            ),
                            IconButton(
                              icon: const Icon(Icons.download),
                              onPressed: () {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  SnackBar(content: Text(isFr ? 'Export en cours...' : 'Exporting...')),
                                );
                              },
                              tooltip: isFr ? 'Télécharger' : 'Download',
                            ),
                          ],
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: colorScheme.surface,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: colorScheme.outline.withValues(alpha: 0.2)),
                      ),
                      child: Text(
                        _transcribedText!,
                        style: const TextStyle(fontSize: 14, height: 1.6),
                      ),
                    ),
                    const SizedBox(height: 16),
                    Row(
                      children: [
                        Expanded(
                          child: OutlinedButton.icon(
                            onPressed: () {
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(content: Text(isFr ? 'Export PDF...' : 'Exporting PDF...')),
                              );
                            },
                            icon: const Icon(Icons.picture_as_pdf),
                            label: const Text('PDF'),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: OutlinedButton.icon(
                            onPressed: () {
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(content: Text(isFr ? 'Export DOCX...' : 'Exporting DOCX...')),
                              );
                            },
                            icon: const Icon(Icons.description),
                            label: const Text('DOCX'),
                          ),
                        ),
                      ],
                    ),
                  ],

                  const SizedBox(height: 32),

                  // Transcription History
                  Text(
                    isFr ? 'Historique des transcriptions' : 'Transcription history',
                    style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 16),

                  ..._transcriptionHistory.map((item) {
                    return Container(
                      margin: const EdgeInsets.only(bottom: 12),
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: colorScheme.surface,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: colorScheme.outline.withValues(alpha: 0.2)),
                      ),
                      child: Row(
                        children: [
                          Icon(Icons.history, color: Colors.teal.shade600),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  item['title'],
                                  style: const TextStyle(fontWeight: FontWeight.bold),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  '${item['duration']} • ${item['wordsCount']} mots',
                                  style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                                ),
                              ],
                            ),
                          ),
                          IconButton(
                            icon: const Icon(Icons.arrow_forward_ios, size: 16),
                            onPressed: () {
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(content: Text(isFr ? 'Ouverture...' : 'Opening...')),
                              );
                            },
                          ),
                        ],
                      ),
                    );
                  }),
                ],
              ),
            )
          : _buildUpgradePrompt(context, isFr),
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
              isFr ? 'Fonctionnalité Professionnelle' : 'Professional Feature',
              style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 12),
            Text(
              isFr
                  ? 'La Transcription Audio est réservée aux plans Professionnel et Cabinet.'
                  : 'Audio Transcription is reserved for Professional and Firm plans.',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 16, color: Colors.grey.shade600),
            ),
            const SizedBox(height: 32),
            ElevatedButton(
              onPressed: () => Navigator.pushNamed(context, '/subscription-plans'),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppConstants.primaryGreen,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 16),
              ),
              child: Text(
                isFr ? 'Voir les plans' : 'View plans',
                style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
