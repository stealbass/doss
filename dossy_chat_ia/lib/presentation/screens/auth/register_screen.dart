import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/constants/app_constants.dart';
import '../../../data/providers/auth_provider.dart';
import '../../../l10n/app_localizations.dart';

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  final _passwordController = TextEditingController();
  final _passwordConfirmController = TextEditingController();
  final _referralCodeController = TextEditingController();

  bool _obscurePassword = true;
  bool _obscurePasswordConfirm = true;
  bool _acceptTerms = false;
  String? _selectedJurisdiction;
  String? _selectedRole = 'student';

  final List<String> _roles = [
    'student',
    'lawyer',
    'enterprise',
  ];

  @override
  void initState() {
    super.initState();
  }

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _passwordController.dispose();
    _passwordConfirmController.dispose();
    _referralCodeController.dispose();
    super.dispose();
  }

  Future<void> _handleRegister() async {
    if (!_formKey.currentState!.validate()) return;

    final l10n = AppLocalizations.of(context)!;

    if (!_acceptTerms) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(l10n.mustAcceptTerms),
          backgroundColor: AppColors.error,
        ),
      );
      return;
    }

    final authProvider = Provider.of<AuthProvider>(context, listen: false);

    final success = await authProvider.register(
      name: _nameController.text.trim(),
      email: _emailController.text.trim(),
      password: _passwordController.text,
      passwordConfirmation: _passwordConfirmController.text,
      phone: _phoneController.text.trim(),
      jurisdiction: _selectedJurisdiction,
      mobileRole: _selectedRole,
      referralCode: _referralCodeController.text.trim().isNotEmpty
          ? _referralCodeController.text.trim()
          : null,
    );

    if (!mounted) return;

    if (success) {
      Navigator.pushReplacementNamed(context, '/home');
    } else {
      final l10n = AppLocalizations.of(context)!;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(authProvider.error ?? l10n.registrationError),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }

  // Build country dropdown items with error handling
  List<DropdownMenuItem<String>> _buildCountryItems() {
    try {
      return AppConstants.countries.map((country) {
        return DropdownMenuItem(
          value: country['code'],
          child: Row(
            children: [
              Text(
                country['flag'] ?? '',
                style: TextStyle(fontSize: 20.sp),
              ),
              SizedBox(width: 8.w),
              // Avoid using Flexible/Expanded inside dropdown overlays (can be unbounded)
              Container(
                constraints: BoxConstraints(
                  maxWidth: MediaQuery.of(context).size.width * 0.55,
                ),
                child: Text(
                  country['name'] ?? '',
                  overflow: TextOverflow.ellipsis,
                  softWrap: false,
                ),
              ),
            ],
          ),
        );
      }).toList();
    } catch (e) {
      // Fallback si AppConstants.countries a un problème
      return [
        const DropdownMenuItem(
          value: 'CM',
          child: Text('🇨🇲 Cameroun'),
        ),
        const DropdownMenuItem(
          value: 'CI',
          child: Text('🇨🇮 Côte d\'Ivoire'),
        ),
        const DropdownMenuItem(
          value: 'SN',
          child: Text('🇸🇳 Sénégal'),
        ),
      ];
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.register),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          child: _buildRegisterForm(context, l10n),
        ),
      ),
    );
  }

  Widget _buildRegisterForm(BuildContext context, AppLocalizations l10n) {
    return Padding(
      padding: EdgeInsets.all(24.w),
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            SizedBox(height: 16.h),
            // Debug banner removed

            // Welcome Text
            Text(
              l10n.register,
              style: TextStyle(
                fontSize: 24.sp,
                fontWeight: FontWeight.bold,
                color: AppColors.textPrimary,
              ),
            ),

            SizedBox(height: 8.h),

            Text(
              l10n.registerSubtitle,
              style: TextStyle(
                fontSize: 14.sp,
                color: AppColors.textSecondary,
              ),
            ),

            SizedBox(height: 32.h),

            // Name Field
            TextFormField(
              controller: _nameController,
              decoration: InputDecoration(
                labelText: l10n.fullName,
                hintText: l10n.nameHint,
                prefixIcon: const Icon(Icons.person_outline),
              ),
              validator: (value) {
                if (value == null || value.isEmpty) {
                  return l10n.requiredField;
                }
                return null;
              },
            ),

            SizedBox(height: 16.h),

            // Email Field
            TextFormField(
              controller: _emailController,
              keyboardType: TextInputType.emailAddress,
              decoration: InputDecoration(
                labelText: l10n.email,
                hintText: l10n.emailHint,
                prefixIcon: const Icon(Icons.email_outlined),
              ),
              validator: (value) {
                if (value == null || value.isEmpty) {
                  return l10n.requiredField;
                }
                if (!value.contains('@')) {
                  return l10n.invalidEmail;
                }
                return null;
              },
            ),

            SizedBox(height: 16.h),

            // Phone Field
            TextFormField(
              controller: _phoneController,
              keyboardType: TextInputType.phone,
              decoration: InputDecoration(
                labelText: l10n.phone,
                hintText: l10n.phoneHint,
                prefixIcon: const Icon(Icons.phone_outlined),
              ),
              validator: (value) {
                if (value == null || value.isEmpty) {
                  return l10n.requiredField;
                }
                return null;
              },
            ),

            SizedBox(height: 16.h),

            // Role Selection
            DropdownButtonFormField<String>(
              initialValue: _selectedRole,
              decoration: InputDecoration(
                labelText: l10n.youAre,
                prefixIcon: const Icon(Icons.work_outline),
              ),
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
              onChanged: (value) {
                setState(() {
                  _selectedRole = value;
                });
              },
              validator: (value) {
                if (value == null) {
                  return l10n.selectYourProfile;
                }
                return null;
              },
            ),

            SizedBox(height: 16.h),

            // Jurisdiction Selection
            DropdownButtonFormField<String>(
              initialValue: _selectedJurisdiction,
              decoration: InputDecoration(
                labelText: l10n.countryJurisdiction,
                prefixIcon: const Icon(Icons.flag_outlined),
              ),
              items: _buildCountryItems(),
              onChanged: (value) {
                setState(() {
                  _selectedJurisdiction = value;
                });
              },
              validator: (value) {
                if (value == null) {
                  return l10n.selectCountry;
                }
                return null;
              },
            ),

            SizedBox(height: 16.h),

            // Password Field
            TextFormField(
              controller: _passwordController,
              obscureText: _obscurePassword,
              decoration: InputDecoration(
                labelText: l10n.password,
                hintText: l10n.passwordHint,
                prefixIcon: const Icon(Icons.lock_outline),
                suffixIcon: IconButton(
                  icon: Icon(
                    _obscurePassword
                        ? Icons.visibility_outlined
                        : Icons.visibility_off_outlined,
                  ),
                  onPressed: () {
                    setState(() {
                      _obscurePassword = !_obscurePassword;
                    });
                  },
                ),
              ),
              validator: (value) {
                if (value == null || value.isEmpty) {
                  return l10n.pleaseEnterPassword;
                }
                if (value.length < 8) {
                  return l10n.passwordMinLength8;
                }
                return null;
              },
            ),

            SizedBox(height: 16.h),

            // Password Confirmation Field
            TextFormField(
              controller: _passwordConfirmController,
              obscureText: _obscurePasswordConfirm,
              decoration: InputDecoration(
                labelText: l10n.confirmPassword,
                hintText: l10n.retypePassword,
                prefixIcon: const Icon(Icons.lock_outline),
                suffixIcon: IconButton(
                  icon: Icon(
                    _obscurePasswordConfirm
                        ? Icons.visibility_outlined
                        : Icons.visibility_off_outlined,
                  ),
                  onPressed: () {
                    setState(() {
                      _obscurePasswordConfirm = !_obscurePasswordConfirm;
                    });
                  },
                ),
              ),
              validator: (value) {
                if (value == null || value.isEmpty) {
                  return l10n.pleaseConfirmPassword;
                }
                if (value != _passwordController.text) {
                  return l10n.passwordsMismatch;
                }
                return null;
              },
            ),

            SizedBox(height: 16.h),

            // Referral Code (Optional)
            TextFormField(
              controller: _referralCodeController,
              decoration: InputDecoration(
                labelText: l10n.referralCodeOptional,
                hintText: l10n.referralCodeHint,
                prefixIcon: const Icon(Icons.card_giftcard_outlined),
              ),
            ),

            SizedBox(height: 24.h),

            // Terms & Conditions Checkbox
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Checkbox(
                  value: _acceptTerms,
                  onChanged: (value) {
                    setState(() {
                      _acceptTerms = value ?? false;
                    });
                  },
                  activeColor: AppColors.primary,
                ),
                Expanded(
                  child: GestureDetector(
                    onTap: () {
                      setState(() {
                        _acceptTerms = !_acceptTerms;
                      });
                    },
                    child: Padding(
                      padding: EdgeInsets.only(top: 12.h),
                      child: Wrap(
                        spacing: 4.w,
                        runSpacing: 4.h,
                        children: [
                          Text(
                            l10n.iAcceptThe,
                            style: TextStyle(
                              fontSize: 13.sp,
                              color: AppColors.textSecondary,
                            ),
                          ),
                          GestureDetector(
                            onTap: () async {
                              final url = 'https://dossypro.com/pages/conditions_g%C3%A9n%C3%A9rales_d%27utilisation';
                              if (await canLaunchUrl(Uri.parse(url))) {
                                await launchUrl(Uri.parse(url), mode: LaunchMode.externalApplication);
                              }
                            },
                            child: Text(
                              l10n.termsOfUse,
                              style: TextStyle(
                                fontSize: 13.sp,
                                color: AppColors.primary,
                                fontWeight: FontWeight.w600,
                                decoration: TextDecoration.underline,
                              ),
                            ),
                          ),
                          Text(
                            l10n.andThe,
                            style: TextStyle(
                              fontSize: 13.sp,
                              color: AppColors.textSecondary,
                            ),
                          ),
                          GestureDetector(
                            onTap: () async {
                              final url = 'https://dossypro.com/privacy';
                              if (await canLaunchUrl(Uri.parse(url))) {
                                await launchUrl(Uri.parse(url), mode: LaunchMode.externalApplication);
                              }
                            },
                            child: Text(
                              l10n.privacyPolicy,
                              style: TextStyle(
                                fontSize: 13.sp,
                                color: AppColors.primary,
                                fontWeight: FontWeight.w600,
                                decoration: TextDecoration.underline,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ],
            ),

            SizedBox(height: 24.h),

            // Register Button
            Consumer<AuthProvider>(
              builder: (context, authProvider, child) {
                return SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: authProvider.isLoading ? null : _handleRegister,
                    style: ElevatedButton.styleFrom(
                      padding: EdgeInsets.symmetric(vertical: 16.h),
                    ),
                    child: authProvider.isLoading
                        ? SizedBox(
                            width: 20.w,
                            height: 20.w,
                            child: const CircularProgressIndicator(
                              valueColor:
                                  AlwaysStoppedAnimation<Color>(Colors.white),
                              strokeWidth: 2,
                            ),
                          )
                        : Text(
                            l10n.createAccount,
                            style: TextStyle(fontSize: 16.sp),
                          ),
                  ),
                );
              },
            ),

            SizedBox(height: 24.h),

            // Already have account
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(
                  l10n.alreadyHaveAccountQuestion,
                  style: TextStyle(
                    fontSize: 14.sp,
                    color: AppColors.textSecondary,
                  ),
                ),
                TextButton(
                  onPressed: () {
                    Navigator.pop(context);
                  },
                  style: TextButton.styleFrom(
                    padding: EdgeInsets.zero,
                  ),
                  child: Text(
                    l10n.login,
                    style: TextStyle(
                      fontSize: 14.sp,
                      fontWeight: FontWeight.w600,
                      color: AppColors.primary,
                    ),
                  ),
                ),
              ],
            ),

            SizedBox(height: 24.h),
          ],
        ),
      ),
    );
  }
}
