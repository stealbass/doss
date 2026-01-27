import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import '../../../core/constants/app_constants.dart';
import '../../../data/providers/theme_provider.dart';
import '../../../data/providers/locale_provider.dart';
import '../../../data/providers/auth_provider.dart';
import '../../../data/services/storage_service.dart';
import '../../../l10n/app_localizations.dart';

/// Écran des paramètres de l'application
/// Gestion des préférences utilisateur, thème, langue, notifications
class SettingsScreen extends StatefulWidget {
  const SettingsScreen({super.key});

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  final StorageService _storageService = StorageService();

  bool _notificationsEnabled = true;
  bool _emailNotifications = true;
  bool _soundEnabled = true;
  bool _vibrationEnabled = true;
  bool _offlineMode = false;
  bool _autoDownload = false;

  @override
  void initState() {
    super.initState();
    _loadSettings();
  }

  /// Charger les paramètres sauvegardés
  Future<void> _loadSettings() async {
    try {
      final settings = await _storageService.getAppSettings();
      setState(() {
        _notificationsEnabled = settings['notificationsEnabled'] ?? true;
        _emailNotifications = settings['emailNotifications'] ?? true;
        _soundEnabled = settings['soundEnabled'] ?? true;
        _vibrationEnabled = settings['vibrationEnabled'] ?? true;
        _offlineMode = settings['offlineMode'] ?? false;
        _autoDownload = settings['autoDownload'] ?? false;
      });
    } catch (e) {
      debugPrint('Erreur chargement paramètres: $e');
    }
  }

  /// Sauvegarder les paramètres
  Future<void> _saveSettings() async {
    try {
      await _storageService.saveAppSettings({
        'notificationsEnabled': _notificationsEnabled,
        'emailNotifications': _emailNotifications,
        'soundEnabled': _soundEnabled,
        'vibrationEnabled': _vibrationEnabled,
        'offlineMode': _offlineMode,
        'autoDownload': _autoDownload,
      });
    } catch (e) {
      debugPrint('Erreur sauvegarde paramètres: $e');
    }
  }

  /// Vider le cache
  Future<void> _clearCache() async {
    final l10n = AppLocalizations.of(context)!;
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(l10n.clearCache),
        content: Text(l10n.clearCacheConfirm),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: Text(l10n.cancel),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
              child: Text(l10n.clearCacheAction),
          ),
        ],
      ),
    );

    if (confirmed == true) {
      try {
        await _storageService.clearCache();
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(l10n.cacheCleared),
              backgroundColor: Colors.green,
            ),
          );
        }
      } catch (e) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('${l10n.error}: $e'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    }
  }

  /// Se déconnecter
  Future<void> _logout() async {
    final l10n = AppLocalizations.of(context)!;
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(l10n.disconnection),
        content: Text(l10n.logoutConfirmMessage),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: Text(l10n.cancel),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            style: TextButton.styleFrom(foregroundColor: Colors.red),
            child: Text(l10n.disconnection),
          ),
        ],
      ),
    );

    if (confirmed == true) {
      final authProvider = context.read<AuthProvider>();
      await authProvider.logout();
      if (mounted) {
        Navigator.of(context).pushReplacementNamed('/login');
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final themeProvider = context.watch<ThemeProvider>();
    final localeProvider = context.watch<LocaleProvider>();

    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.settings),
        elevation: 0,
      ),
      body: ListView(
        padding: EdgeInsets.zero,
        children: [
          // Section Apparence
          _buildSectionHeader('Apparence'),
          _buildListTile(
            title: 'Thème',
            subtitle: themeProvider.isDarkMode ? 'Sombre' : 'Clair',
            leading: Icon(
              themeProvider.isDarkMode ? Icons.dark_mode : Icons.light_mode,
            ),
            trailing: Switch(
              value: themeProvider.isDarkMode,
              onChanged: (value) {
                themeProvider.toggleTheme();
              },
            ),
          ),
          _buildListTile(
            title: 'Langue',
            subtitle: localeProvider.locale.languageCode == 'fr'
                ? 'Français'
                : 'English',
            leading: const Icon(Icons.language),
            trailing: DropdownButton<String>(
              value: localeProvider.locale.languageCode,
              underline: const SizedBox(),
              items: const [
                DropdownMenuItem(value: 'fr', child: Text('FR')),
                DropdownMenuItem(value: 'en', child: Text('EN')),
              ],
              onChanged: (value) {
                if (value != null) {
                  // Use post frame callback to avoid setState during build
                  WidgetsBinding.instance.addPostFrameCallback((_) {
                    localeProvider.setLocale(value);
                  });
                }
              },
            ),
          ),

          Divider(height: 1.h),

          // Section Notifications
          _buildSectionHeader('Notifications'),
          _buildSwitchTile(
            title: 'Notifications push',
            subtitle: 'Recevoir des notifications',
            value: _notificationsEnabled,
            onChanged: (value) {
              setState(() {
                _notificationsEnabled = value;
              });
              _saveSettings();
            },
            icon: Icons.notifications,
          ),
          _buildSwitchTile(
            title: 'Notifications email',
            subtitle: 'Recevoir des emails',
            value: _emailNotifications,
            onChanged: (value) {
              setState(() {
                _emailNotifications = value;
              });
              _saveSettings();
            },
            icon: Icons.email,
          ),
          _buildSwitchTile(
            title: 'Son',
            subtitle: 'Son des notifications',
            value: _soundEnabled,
            onChanged: _notificationsEnabled
                ? (value) {
                    setState(() {
                      _soundEnabled = value;
                    });
                    _saveSettings();
                  }
                : null,
            icon: Icons.volume_up,
          ),
          _buildSwitchTile(
            title: 'Vibration',
            subtitle: 'Vibration des notifications',
            value: _vibrationEnabled,
            onChanged: _notificationsEnabled
                ? (value) {
                    setState(() {
                      _vibrationEnabled = value;
                    });
                    _saveSettings();
                  }
                : null,
            icon: Icons.vibration,
          ),

          Divider(height: 1.h),

          // Section Stockage
          _buildSectionHeader('Stockage'),
          _buildSwitchTile(
            title: 'Mode hors ligne',
            subtitle: 'Sauvegarder automatiquement',
            value: _offlineMode,
            onChanged: (value) {
              setState(() {
                _offlineMode = value;
              });
              _saveSettings();
            },
            icon: Icons.cloud_off,
          ),
          _buildSwitchTile(
            title: 'Téléchargement auto',
            subtitle: 'Télécharger les documents',
            value: _autoDownload,
            onChanged: (value) {
              setState(() {
                _autoDownload = value;
              });
              _saveSettings();
            },
            icon: Icons.download,
          ),
          _buildListTile(
            title: 'Vider le cache',
            subtitle: 'Libérer de l\'espace',
            leading: const Icon(Icons.cleaning_services),
            onTap: _clearCache,
            trailing: const Icon(Icons.chevron_right),
          ),

          Divider(height: 1.h),

          // Section À propos
          _buildSectionHeader('À propos'),
          _buildListTile(
            title: 'Version',
            subtitle: AppConstants.appVersion,
            leading: const Icon(Icons.info),
          ),
          _buildListTile(
            title: 'Conditions d\'utilisation',
            leading: const Icon(Icons.description),
            onTap: () {
              // TODO: Ouvrir les CGU
            },
            trailing: const Icon(Icons.chevron_right),
          ),
          _buildListTile(
            title: 'Politique de confidentialité',
            leading: const Icon(Icons.privacy_tip),
            onTap: () {
              // TODO: Ouvrir la politique
            },
            trailing: const Icon(Icons.chevron_right),
          ),
          _buildListTile(
            title: 'Aide et support',
            leading: const Icon(Icons.help),
            onTap: () {
              Navigator.pushNamed(context, '/help');
            },
            trailing: const Icon(Icons.chevron_right),
          ),

          Divider(height: 1.h),

          // Déconnexion
          _buildListTile(
            title: 'Déconnexion',
            titleColor: Colors.red,
            leading: const Icon(Icons.logout, color: Colors.red),
            onTap: _logout,
          ),

          SizedBox(height: 32.h),
        ],
      ),
    );
  }

  Widget _buildSectionHeader(String title) {
    return Container(
      padding: EdgeInsets.fromLTRB(16.w, 16.h, 16.w, 8.h),
      child: Text(
        title,
        style: TextStyle(
          fontSize: 14.sp,
          fontWeight: FontWeight.bold,
          color: Theme.of(context).primaryColor,
        ),
      ),
    );
  }

  Widget _buildListTile({
    required String title,
    String? subtitle,
    required Widget leading,
    Widget? trailing,
    VoidCallback? onTap,
    Color? titleColor,
  }) {
    return ListTile(
      leading: leading,
      title: Text(
        title,
        style: TextStyle(color: titleColor),
      ),
      subtitle: subtitle != null ? Text(subtitle) : null,
      trailing: trailing,
      onTap: onTap,
    );
  }

  Widget _buildSwitchTile({
    required String title,
    String? subtitle,
    required bool value,
    required ValueChanged<bool>? onChanged,
    required IconData icon,
  }) {
    return ListTile(
      leading: Icon(icon),
      title: Text(title),
      subtitle: subtitle != null ? Text(subtitle) : null,
      trailing: Switch(
        value: value,
        onChanged: onChanged,
      ),
    );
  }
}
