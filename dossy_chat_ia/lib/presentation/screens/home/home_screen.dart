import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../../data/providers/auth_provider.dart';
import '../../../l10n/app_localizations.dart';
import '../chat/chat_screen.dart';
import '../documents/documents_screen.dart';
import '../profile/profile_settings_screen.dart';
import '../tools/tools_hub_screen.dart';
import '../library/library_hub_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int _currentIndex = 0;
  bool _profilePromptShown = false;

  final List<Widget> _screens = [
    const ChatScreen(),
    const DocumentsScreen(),
    const ToolsHubScreen(),
    const LibraryHubScreen(),
    const ProfileSettingsScreen(),
  ];

  @override
  void initState() {
    super.initState();

    WidgetsBinding.instance.addPostFrameCallback((_) {
      _promptProfileCompletionIfNeeded();
    });
  }

  Future<void> _promptProfileCompletionIfNeeded() async {
    if (_profilePromptShown || !mounted) return;

    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    if (!authProvider.needsProfileCompletion) return;

    _profilePromptShown = true;
    final l10n = AppLocalizations.of(context)!;
    final missingFields = authProvider.missingProfileFields;

    String missingLabel(String field) {
      if (field == 'jurisdiction') return l10n.jurisdiction;
      if (field == 'mobile_role') return l10n.role;
      return field;
    }

    final missingText = missingFields.isNotEmpty
        ? missingFields.map(missingLabel).join(', ')
        : '';

    await showDialog<void>(
      context: context,
      barrierDismissible: true,
      builder: (context) {
        return AlertDialog(
          title: Text(l10n.completeProfileTitle),
          content: Text(
            missingText.isEmpty
                ? l10n.completeProfileMessage
                : '${l10n.completeProfileMessage}\n\n${l10n.missingFieldsLabel}: $missingText',
          ),
          actions: [
            TextButton(
              onPressed: () {
                Navigator.of(context).pop();
              },
              child: Text(l10n.later),
            ),
            TextButton(
              onPressed: () {
                Navigator.of(context).pop();
                setState(() {
                  _currentIndex = 4;
                });
              },
              child: Text(l10n.completeNow),
            ),
          ],
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    return Scaffold(
      body: _screens[_currentIndex],
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _currentIndex,
        onTap: (index) {
          setState(() {
            _currentIndex = index;
          });
        },
        type: BottomNavigationBarType.fixed,
        selectedItemColor: AppColors.primary,
        unselectedItemColor: AppColors.textSecondary,
        items: [
          BottomNavigationBarItem(
            icon: const Icon(Icons.chat_outlined),
            activeIcon: const Icon(Icons.chat),
            label: l10n.navChat,
          ),
          BottomNavigationBarItem(
            icon: const Icon(Icons.folder_outlined),
            activeIcon: const Icon(Icons.folder),
            label: l10n.navDocuments,
          ),
          BottomNavigationBarItem(
            icon: const Icon(Icons.build_outlined),
            activeIcon: const Icon(Icons.build),
            label: l10n.navTools,
          ),
          BottomNavigationBarItem(
            icon: const Icon(Icons.library_books_outlined),
            activeIcon: const Icon(Icons.library_books),
            label: l10n.navLibrary,
          ),
          BottomNavigationBarItem(
            icon: const Icon(Icons.person_outline),
            activeIcon: const Icon(Icons.person),
            label: l10n.navProfile,
          ),
        ],
      ),
    );
  }
}




