import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../../data/providers/auth_provider.dart';
import '../../../data/providers/theme_provider.dart';
import '../../../data/providers/locale_provider.dart';

class ProfileSettingsScreen extends StatefulWidget {
  const ProfileSettingsScreen({super.key});

  @override
  State<ProfileSettingsScreen> createState() => _ProfileSettingsScreenState();
}

class _ProfileSettingsScreenState extends State<ProfileSettingsScreen> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _nameController;
  late TextEditingController _phoneController;
  bool _isEditing = false;

  @override
  void initState() {
    super.initState();
    final user = Provider.of<AuthProvider>(context, listen: false).user;
    _nameController = TextEditingController(text: user?.name ?? '');
    _phoneController = TextEditingController(text: user?.phone ?? '');
  }

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    super.dispose();
  }

  Future<void> _saveProfile() async {
    if (!_formKey.currentState!.validate()) return;

    final authProvider = Provider.of<AuthProvider>(context, listen: false);

    final success = await authProvider.updateProfile(
      name: _nameController.text.trim(),
      phone: _phoneController.text.trim(),
    );

    if (!mounted) return;

    if (success) {
      setState(() {
        _isEditing = false;
      });
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Profil mis à jour avec succès'),
          backgroundColor: AppColors.success,
        ),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(authProvider.error ?? 'Erreur lors de la mise à jour'),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Paramètres'),
        actions: [
          if (_isEditing)
            TextButton(
              onPressed: _saveProfile,
              child: Text(
                'Enregistrer',
                style: TextStyle(
                  color: AppColors.primary,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
        ],
      ),
      body: Consumer<AuthProvider>(
        builder: (context, authProvider, child) {
          final user = authProvider.user;
          if (user == null) {
            return const Center(child: CircularProgressIndicator());
          }

          return SingleChildScrollView(
            child: Column(
              children: [
                // Profile Header
                Container(
                  width: double.infinity,
                  padding: EdgeInsets.all(24.w),
                  decoration: BoxDecoration(
                    gradient: AppColors.primaryGradient,
                  ),
                  child: Column(
                    children: [
                      // Avatar
                      CircleAvatar(
                        radius: 50.r,
                        backgroundColor: Colors.white,
                        child: Icon(
                          Icons.person,
                          size: 50.sp,
                          color: AppColors.primary,
                        ),
                      ),
                      SizedBox(height: 16.h),
                      Text(
                        user.name,
                        style: TextStyle(
                          fontSize: 22.sp,
                          fontWeight: FontWeight.bold,
                          color: Colors.white,
                        ),
                      ),
                      SizedBox(height: 4.h),
                      Text(
                        user.email,
                        style: TextStyle(
                          fontSize: 14.sp,
                          color: Colors.white.withOpacity(0.9),
                        ),
                      ),
                      SizedBox(height: 12.h),
                      Container(
                        padding: EdgeInsets.symmetric(horizontal: 12.w, vertical: 6.h),
                        decoration: BoxDecoration(
                          color: Colors.white.withOpacity(0.2),
                          borderRadius: BorderRadius.circular(20.r),
                        ),
                        child: Text(
                          user.plan,
                          style: TextStyle(
                            fontSize: 13.sp,
                            fontWeight: FontWeight.w600,
                            color: Colors.white,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),

                SizedBox(height: 24.h),

                // Edit Profile Section
                Padding(
                  padding: EdgeInsets.symmetric(horizontal: 16.w),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            'Informations personnelles',
                            style: TextStyle(
                              fontSize: 18.sp,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          if (!_isEditing)
                            IconButton(
                              icon: const Icon(Icons.edit),
                              onPressed: () {
                                setState(() {
                                  _isEditing = true;
                                });
                              },
                            ),
                        ],
                      ),

                      SizedBox(height: 16.h),

                      Form(
                        key: _formKey,
                        child: Column(
                          children: [
                            TextFormField(
                              controller: _nameController,
                              enabled: _isEditing,
                              decoration: const InputDecoration(
                                labelText: 'Nom complet',
                                prefixIcon: Icon(Icons.person_outline),
                              ),
                              validator: (value) {
                                if (value == null || value.isEmpty) {
                                  return 'Veuillez entrer votre nom';
                                }
                                return null;
                              },
                            ),
                            SizedBox(height: 16.h),
                            TextFormField(
                              controller: _phoneController,
                              enabled: _isEditing,
                              decoration: const InputDecoration(
                                labelText: 'Téléphone',
                                prefixIcon: Icon(Icons.phone_outlined),
                              ),
                            ),
                            SizedBox(height: 16.h),
                            TextFormField(
                              initialValue: user.email,
                              enabled: false,
                              decoration: const InputDecoration(
                                labelText: 'Email',
                                prefixIcon: Icon(Icons.email_outlined),
                              ),
                            ),
                          ],
                        ),
                      ),

                      if (_isEditing) ...[
                        SizedBox(height: 16.h),
                        Row(
                          children: [
                            Expanded(
                              child: OutlinedButton(
                                onPressed: () {
                                  setState(() {
                                    _isEditing = false;
                                    _nameController.text = user.name;
                                    _phoneController.text = user.phone ?? '';
                                  });
                                },
                                child: const Text('Annuler'),
                              ),
                            ),
                            SizedBox(width: 12.w),
                            Expanded(
                              child: ElevatedButton(
                                onPressed: _saveProfile,
                                child: const Text('Enregistrer'),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ],
                  ),
                ),

                SizedBox(height: 24.h),

                // Preferences Section
                Padding(
                  padding: EdgeInsets.symmetric(horizontal: 16.w),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Préférences',
                        style: TextStyle(
                          fontSize: 18.sp,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      SizedBox(height: 16.h),

                      // Dark Mode Toggle
                      Consumer<ThemeProvider>(
                        builder: (context, themeProvider, child) {
                          return SwitchListTile(
                            title: const Text('Mode sombre'),
                            subtitle: const Text('Activer le thème sombre'),
                            secondary: Icon(
                              themeProvider.isDarkMode ? Icons.dark_mode : Icons.light_mode,
                            ),
                            value: themeProvider.isDarkMode,
                            activeColor: AppColors.primary,
                            onChanged: (value) {
                              themeProvider.toggleTheme();
                            },
                          );
                        },
                      ),

                      // Language Toggle
                      Consumer<LocaleProvider>(
                        builder: (context, localeProvider, child) {
                          return SwitchListTile(
                            title: const Text('Langue'),
                            subtitle: Text(
                              localeProvider.locale.languageCode == 'fr'
                                  ? 'Français'
                                  : 'English',
                            ),
                            secondary: const Icon(Icons.language),
                            value: localeProvider.locale.languageCode == 'en',
                            activeColor: AppColors.primary,
                            onChanged: (value) {
                              localeProvider.setLocale(value ? 'en' : 'fr');
                            },
                          );
                        },
                      ),
                    ],
                  ),
                ),

                SizedBox(height: 24.h),

                // Subscription Info
                Padding(
                  padding: EdgeInsets.symmetric(horizontal: 16.w),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Abonnement',
                        style: TextStyle(
                          fontSize: 18.sp,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      SizedBox(height: 16.h),

                      Container(
                        padding: EdgeInsets.all(16.w),
                        decoration: BoxDecoration(
                          color: AppColors.getPlanColor(user.plan).withOpacity(0.1),
                          borderRadius: BorderRadius.circular(12.r),
                          border: Border.all(
                            color: AppColors.getPlanColor(user.plan),
                          ),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(
                                  user.plan,
                                  style: TextStyle(
                                    fontSize: 18.sp,
                                    fontWeight: FontWeight.bold,
                                    color: AppColors.getPlanColor(user.plan),
                                  ),
                                ),
                                if (user.isSubscriptionActive)
                                  Icon(
                                    Icons.check_circle,
                                    color: AppColors.success,
                                    size: 24.sp,
                                  ),
                              ],
                            ),
                            SizedBox(height: 12.h),
                            _buildQuotaInfo(
                              'Recherches',
                              user.searchesUsed,
                              user.searchesLimit,
                            ),
                            SizedBox(height: 8.h),
                            _buildQuotaInfo(
                              'Analyses',
                              user.analysesUsed,
                              user.analysesLimit,
                            ),
                            SizedBox(height: 8.h),
                            _buildQuotaInfo(
                              'Téléchargements',
                              user.downloadsUsed,
                              user.downloadsLimit,
                            ),
                            if (user.subscriptionEnd != null) ...[
                              SizedBox(height: 12.h),
                              Text(
                                'Expire le: ${user.subscriptionEnd!.day}/${user.subscriptionEnd!.month}/${user.subscriptionEnd!.year}',
                                style: TextStyle(
                                  fontSize: 12.sp,
                                  color: AppColors.textSecondary,
                                ),
                              ),
                            ],
                          ],
                        ),
                      ),

                      SizedBox(height: 16.h),

                      if (user.plan != 'Cabinet/Entreprise')
                        SizedBox(
                          width: double.infinity,
                          child: ElevatedButton(
                            onPressed: () {
                              Navigator.pushNamed(context, '/subscription');
                            },
                            child: const Text('Mettre à niveau'),
                          ),
                        ),
                    ],
                  ),
                ),

                SizedBox(height: 32.h),

                // Logout Button
                Padding(
                  padding: EdgeInsets.symmetric(horizontal: 16.w),
                  child: SizedBox(
                    width: double.infinity,
                    child: OutlinedButton.icon(
                      onPressed: () async {
                        await authProvider.logout();
                        if (!context.mounted) return;
                        Navigator.pushReplacementNamed(context, '/login');
                      },
                      icon: const Icon(Icons.logout),
                      label: const Text('Se déconnecter'),
                      style: OutlinedButton.styleFrom(
                        foregroundColor: AppColors.error,
                        side: const BorderSide(color: AppColors.error),
                      ),
                    ),
                  ),
                ),

                SizedBox(height: 32.h),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _buildQuotaInfo(String label, int used, int limit) {
    final percentage = limit == -1 ? 0.0 : (used / limit).clamp(0.0, 1.0);
    final isUnlimited = limit == -1;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(
              label,
              style: TextStyle(
                fontSize: 13.sp,
                color: AppColors.textSecondary,
              ),
            ),
            Text(
              isUnlimited ? '$used / ∞' : '$used / $limit',
              style: TextStyle(
                fontSize: 13.sp,
                fontWeight: FontWeight.w600,
                color: AppColors.textPrimary,
              ),
            ),
          ],
        ),
        if (!isUnlimited) ...[
          SizedBox(height: 4.h),
          LinearProgressIndicator(
            value: percentage,
            backgroundColor: AppColors.border,
            valueColor: AlwaysStoppedAnimation<Color>(
              percentage > 0.8 ? AppColors.error : AppColors.primary,
            ),
          ),
        ],
      ],
    );
  }
}
