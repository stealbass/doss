import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/constants/app_constants.dart';
import '../../../core/theme/app_colors.dart';
import '../../../data/providers/auth_provider.dart';
import '../../../data/providers/chat_provider.dart';
import '../../../data/providers/document_provider.dart';
import '../../../data/providers/locale_provider.dart';
import '../../../data/providers/theme_provider.dart';
import '../../../l10n/app_localizations.dart';

class ProfileSettingsScreen extends StatefulWidget {
  const ProfileSettingsScreen({super.key});

  @override
  State<ProfileSettingsScreen> createState() => _ProfileSettingsScreenState();
}

class _ProfileSettingsScreenState extends State<ProfileSettingsScreen> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _nameController;
  late TextEditingController _phoneController;
  late TextEditingController _addressController;
  late TextEditingController _cityController;
  String? _selectedRole;
  String? _selectedJurisdiction;
  bool _isEditing = false;

  final List<String> _roles = const ['student', 'lawyer', 'enterprise'];

  @override
  void initState() {
    super.initState();
    final user = Provider.of<AuthProvider>(context, listen: false).user;
    _nameController = TextEditingController(text: user?.name ?? '');
    _phoneController = TextEditingController(text: user?.phone ?? '');
    _addressController = TextEditingController(text: user?.address ?? '');
    _cityController = TextEditingController(text: user?.city ?? '');
    _selectedRole = user?.role;
    _selectedJurisdiction = user?.jurisdiction;
  }

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    _addressController.dispose();
    _cityController.dispose();
    super.dispose();
  }

  List<DropdownMenuItem<String>> _buildCountryItems() {
    try {
      return AppConstants.countries.map((country) {
        return DropdownMenuItem(
          value: country['code'],
          child: Row(
            children: [
              Text(
                country['flag'] ?? '',
                style: const TextStyle(fontSize: 18),
              ),
              const SizedBox(width: 8),
              Expanded(child: Text(country['name'] ?? '')),
            ],
          ),
        );
      }).toList();
    } catch (_) {
      return [];
    }
  }

  String _roleLabel(AppLocalizations l10n, String? role) {
    if (role == 'lawyer') return l10n.roleLawyer;
    if (role == 'enterprise') return l10n.roleEnterprise;
    return l10n.roleStudent;
  }

  String _countryName(String? code) {
    if (code == null || code.isEmpty) return '';
    final match = AppConstants.countries.firstWhere(
      (country) => country['code'] == code,
      orElse: () => const {'name': ''},
    );
    return match['name'] ?? '';
  }

  Future<void> _saveProfile() async {
    if (!_formKey.currentState!.validate()) return;

    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final success = await authProvider.updateProfile(
      name: _nameController.text.trim(),
      phone: _phoneController.text.trim(),
      address: _addressController.text.trim(),
      city: _cityController.text.trim(),
      jurisdiction: _selectedJurisdiction,
      mobileRole: _selectedRole,
    );

    if (!mounted) return;

    if (success) {
      setState(() {
        _isEditing = false;
        final updatedUser = authProvider.user;
        if (updatedUser != null) {
          _nameController.text = updatedUser.name;
          _phoneController.text = updatedUser.phone ?? '';
          _addressController.text = updatedUser.address ?? '';
          _cityController.text = updatedUser.city ?? '';
          _selectedRole = updatedUser.role;
          _selectedJurisdiction = updatedUser.jurisdiction;
        }
      });
      final l10n = AppLocalizations.of(context)!;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(l10n.profileUpdatedSuccess),
          backgroundColor: AppColors.success,
        ),
      );
    } else {
      final l10n = AppLocalizations.of(context)!;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(authProvider.error ?? l10n.updateError),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }


  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final isFrench = Localizations.localeOf(context).languageCode == 'fr';

    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.settings),
        actions: [
          if (_isEditing)
            TextButton(
              onPressed: _saveProfile,
              child: Text(
                l10n.save,
                style: const TextStyle(
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

          final planLower = user.plan.toLowerCase();
          final isFreePlan = planLower == 'free' || planLower == 'gratuit';
          final remainingDownloads = isFreePlan
              ? user.downloadsLimit - user.downloadsUsed
              : null;
          final safeRemaining = remainingDownloads != null && remainingDownloads > 0
              ? remainingDownloads
              : 0;

          return SingleChildScrollView(
            child: Column(
              children: [
                Container(
                  width: double.infinity,
                  padding: EdgeInsets.all(24.w),
                  decoration: const BoxDecoration(
                    gradient: AppColors.primaryGradient,
                  ),
                  child: Column(
                    children: [
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
                          color: Colors.white.withAlpha((0.9 * 255).round()),
                        ),
                      ),
                      SizedBox(height: 6.h),
                      Text(
                        [
                          _roleLabel(l10n, user.role),
                          _countryName(user.jurisdiction),
                        ].where((value) => value.isNotEmpty).join(' • '),
                        style: TextStyle(
                          fontSize: 13.sp,
                          color: Colors.white.withAlpha((0.85 * 255).round()),
                        ),
                      ),
                      SizedBox(height: 16.h),
                      Container(
                        padding: EdgeInsets.symmetric(
                          horizontal: 12.w,
                          vertical: 6.h,
                        ),
                        decoration: BoxDecoration(
                          color: Colors.white.withAlpha((0.2 * 255).round()),
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
                Padding(
                  padding: EdgeInsets.symmetric(horizontal: 16.w),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            l10n.personalInformation,
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
                              decoration: InputDecoration(
                                labelText: l10n.fullName,
                                prefixIcon: const Icon(Icons.person_outline),
                              ),
                              validator: (value) {
                                if (value == null || value.isEmpty) {
                                  return l10n.pleaseEnterName;
                                }
                                return null;
                              },
                            ),
                            SizedBox(height: 16.h),
                            TextFormField(
                              controller: _addressController,
                              enabled: _isEditing,
                              decoration: InputDecoration(
                                labelText: l10n.address,
                                prefixIcon: const Icon(Icons.home_outlined),
                              ),
                            ),
                            SizedBox(height: 16.h),
                            DropdownButtonFormField<String>(
                              value: _selectedRole,
                              items: _roles.map((role) {
                                final roleLabel = role == 'student'
                                    ? l10n.roleStudent
                                    : role == 'lawyer'
                                        ? l10n.roleLawyer
                                        : l10n.roleEnterprise;
                                return DropdownMenuItem(
                                  value: role,
                                  child: Text(roleLabel),
                                );
                              }).toList(),
                              onChanged: _isEditing
                                  ? (value) {
                                      setState(() {
                                        _selectedRole = value;
                                      });
                                    }
                                  : null,
                              decoration: InputDecoration(
                                labelText: l10n.youAre,
                                prefixIcon: const Icon(Icons.work_outline),
                              ),
                              hint: Text(l10n.selectYourProfile),
                            ),
                            SizedBox(height: 16.h),
                            DropdownButtonFormField<String>(
                              value: _selectedJurisdiction,
                              items: _buildCountryItems(),
                              onChanged: _isEditing
                                  ? (value) {
                                      setState(() {
                                        _selectedJurisdiction = value;
                                      });
                                    }
                                  : null,
                              decoration: InputDecoration(
                                labelText: l10n.countryJurisdiction,
                                prefixIcon: const Icon(Icons.flag_outlined),
                              ),
                              hint: Text(l10n.selectYourCountry),
                            ),
                            SizedBox(height: 16.h),
                            TextFormField(
                              controller: _cityController,
                              enabled: _isEditing,
                              decoration: InputDecoration(
                                labelText: l10n.city,
                                prefixIcon:
                                    const Icon(Icons.location_city_outlined),
                              ),
                            ),
                            SizedBox(height: 16.h),
                            TextFormField(
                              controller: _phoneController,
                              enabled: _isEditing,
                              decoration: InputDecoration(
                                labelText: l10n.phoneNumber,
                                prefixIcon: const Icon(Icons.phone_outlined),
                              ),
                            ),
                            SizedBox(height: 16.h),
                            TextFormField(
                              initialValue: user.email,
                              enabled: false,
                              decoration: InputDecoration(
                                labelText: l10n.emailAddress,
                                prefixIcon: const Icon(Icons.email_outlined),
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
                                    _addressController.text =
                                        user.address ?? '';
                                    _cityController.text = user.city ?? '';
                                    _selectedRole = user.role;
                                    _selectedJurisdiction = user.jurisdiction;
                                  });
                                },
                                child: Text(l10n.cancel),
                              ),
                            ),
                            SizedBox(width: 12.w),
                            Expanded(
                              child: ElevatedButton(
                                onPressed: _saveProfile,
                                child: Text(l10n.save),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ],
                  ),
                ),
                SizedBox(height: 24.h),
                Padding(
                  padding: EdgeInsets.symmetric(horizontal: 16.w),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        l10n.preferences,
                        style: TextStyle(
                          fontSize: 18.sp,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      SizedBox(height: 16.h),
                      Consumer<ThemeProvider>(
                        builder: (context, themeProvider, child) {
                          return SwitchListTile(
                            key: const ValueKey('theme_switch'),
                            title: Text(l10n.darkMode),
                            subtitle: Text(l10n.enableDarkTheme),
                            secondary: Icon(
                              themeProvider.isDarkMode
                                  ? Icons.dark_mode
                                  : Icons.light_mode,
                            ),
                            value: themeProvider.isDarkMode,
                            activeThumbColor: AppColors.primary,
                            onChanged: (value) {
                              themeProvider.toggleTheme();
                            },
                          );
                        },
                      ),
                      Consumer<LocaleProvider>(
                        builder: (context, localeProvider, child) {
                          return SwitchListTile(
                            key: const ValueKey('language_switch'),
                            title: Text(l10n.language),
                            subtitle: Text(
                              localeProvider.locale.languageCode == 'fr'
                                  ? l10n.french
                                  : l10n.english,
                            ),
                            secondary: const Icon(Icons.language),
                            value: localeProvider.locale.languageCode == 'en',
                            activeThumbColor: AppColors.primary,
                            onChanged: (value) {
                              // Use post frame callback to avoid setState during build
                              WidgetsBinding.instance.addPostFrameCallback((_) {
                                localeProvider.setLocale(value ? 'en' : 'fr');
                              });
                            },
                          );
                        },
                      ),
                    ],
                  ),
                ),
                SizedBox(height: 24.h),
                Padding(
                  padding: EdgeInsets.symmetric(horizontal: 16.w),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        l10n.subscriptionPlan,
                        style: TextStyle(
                          fontSize: 18.sp,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      SizedBox(height: 16.h),
                      Container(
                        padding: EdgeInsets.all(16.w),
                        decoration: BoxDecoration(
                          color: AppColors.getPlanColor(user.plan)
                              .withAlpha((0.1 * 255).round()),
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
                              l10n.searches,
                              user.searchesUsed,
                              user.searchesLimit,
                            ),
                            SizedBox(height: 8.h),
                            _buildQuotaInfo(
                              l10n.analyses,
                              user.analysesUsed,
                              user.analysesLimit,
                            ),
                            SizedBox(height: 8.h),
                            _buildQuotaInfo(
                              l10n.downloads,
                              user.downloadsUsed,
                              user.downloadsLimit,
                            ),
                            if (isFreePlan) ...[
                              SizedBox(height: 6.h),
                              Row(
                                children: [
                                  Icon(
                                    Icons.download,
                                    size: 16.sp,
                                    color: AppColors.primary,
                                  ),
                                  SizedBox(width: 6.w),
                                  Text(
                                    '$safeRemaining telechargements gratuits restants',
                                    style: TextStyle(
                                      fontSize: 12.sp,
                                      fontWeight: FontWeight.w600,
                                      color: AppColors.primary,
                                    ),
                                  ),
                                ],
                              ),
                            ],
                            if (user.subscriptionEnd != null) ...[
                              SizedBox(height: 12.h),
                              Text(
                                '${l10n.expiresOn}: ${user.subscriptionEnd!.day}/${user.subscriptionEnd!.month}/${user.subscriptionEnd!.year}',
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
                              Navigator.pushNamed(
                                context,
                                '/subscription-plans',
                              );
                            },
                            child: Text(l10n.upgradeSubscription),
                          ),
                        ),
                    ],
                  ),
                ),
                SizedBox(height: 24.h),
                Padding(
                  padding: EdgeInsets.symmetric(horizontal: 16.w),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        l10n.features,
                        style: TextStyle(
                          fontSize: 18.sp,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      SizedBox(height: 16.h),
                      ListTile(
                        leading: const Icon(Icons.card_giftcard,
                            color: AppColors.primary),
                        title: Text(l10n.referralProgram),
                        subtitle: Text(l10n.earnRewards),
                        trailing: const Icon(Icons.arrow_forward_ios, size: 16),
                        onTap: () {
                          Navigator.pushNamed(context, '/referral');
                        },
                      ),
                      if (user.plan == 'Professionnel' ||
                          user.plan == 'Cabinet/Entreprise')
                        ListTile(
                          leading: const Icon(Icons.shield,
                              color: AppColors.primary),
                          title: Text(l10n.documentAnonymization),
                          subtitle: Text(l10n.dataProtection),
                          trailing:
                              const Icon(Icons.arrow_forward_ios, size: 16),
                          onTap: () {
                            Navigator.pushNamed(context, '/anonymization');
                          },
                        ),
                      if (user.plan == 'Professionnel' ||
                          user.plan == 'Cabinet/Entreprise')
                        ListTile(
                          leading: const Icon(Icons.newspaper,
                              color: AppColors.primary),
                          title: Text(l10n.legalMonitoring),
                          subtitle: Text(l10n.newsAndAlerts),
                          trailing:
                              const Icon(Icons.arrow_forward_ios, size: 16),
                          onTap: () {
                            Navigator.pushNamed(context, '/legal-monitoring');
                          },
                        ),
                    ],
                  ),
                ),
                SizedBox(height: 32.h),
                Padding(
                  padding: EdgeInsets.symmetric(horizontal: 16.w),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        l10n.support,
                        style: TextStyle(
                          fontSize: 18.sp,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      SizedBox(height: 16.h),
                      ListTile(
                        leading: const Icon(Icons.email,
                            color: AppColors.primary),
                        title: Text(l10n.contactUs),
                        subtitle: const Text('contact@dossypro.com'),
                        trailing: const Icon(Icons.arrow_forward_ios, size: 16),
                        onTap: () async {
                          final Uri emailUri = Uri(
                            scheme: 'mailto',
                            path: 'contact@dossypro.com',
                            queryParameters: {
                              'subject': 'Demande de support',
                              'body': 'Bonjour,\n\nJe contacte le support...',
                            },
                          );
                          try {
                            if (await canLaunchUrl(emailUri)) {
                              await launchUrl(emailUri);
                            } else {
                              // Fallback: essayer de copier et afficher les infos
                              if (!context.mounted) return;
                              showDialog(
                                context: context,
                                builder: (context) => AlertDialog(
                                  title: Text(l10n.contactUs),
                                  content: const Text(
                                    'Veuillez envoyer un email à:\ncontact@dossypro.com',
                                  ),
                                  actions: [
                                    TextButton(
                                      onPressed: () => Navigator.pop(context),
                                      child: Text(l10n.ok),
                                    ),
                                  ],
                                ),
                              );
                            }
                          } catch (e) {
                            if (!context.mounted) return;
                            showDialog(
                              context: context,
                              builder: (context) => AlertDialog(
                                title: Text(l10n.error),
                                content: const Text(
                                  'Veuillez envoyer un email à:\ncontact@dossypro.com',
                                ),
                                actions: [
                                  TextButton(
                                    onPressed: () => Navigator.pop(context),
                                    child: Text(l10n.ok),
                                  ),
                                ],
                              ),
                            );
                          }
                        },
                      ),
                    ],
                  ),
                ),
                SizedBox(height: 32.h),                Padding(
                  padding: EdgeInsets.symmetric(horizontal: 16.w),
                  child: SizedBox(
                    width: double.infinity,
                    child: OutlinedButton.icon(
                      onPressed: () async {
                        // Clear document cache before logout
                        final documentProvider = Provider.of<DocumentProvider>(context, listen: false);
                        await documentProvider.clearCache();

                        Provider.of<ChatProvider>(context, listen: false).clearChat();
                        
                        await authProvider.logout();
                        if (!context.mounted) return;
                        Navigator.pushReplacementNamed(context, '/login');
                      },
                      icon: const Icon(Icons.logout),
                      label: Text(l10n.logoutButton),
                      style: OutlinedButton.styleFrom(
                        foregroundColor: AppColors.error,
                        side: const BorderSide(color: AppColors.error),
                      ),
                    ),
                  ),
                ),
                SizedBox(height: 24.h),              ],
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
          ClipRRect(
            borderRadius: BorderRadius.circular(8.r),
            child: LinearProgressIndicator(
              value: percentage,
              backgroundColor: AppColors.cardBackground,
              color: AppColors.primary,
              minHeight: 8.h,
            ),
          ),
        ],
      ],
    );
  }
}
