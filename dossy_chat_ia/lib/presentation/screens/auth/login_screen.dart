import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../../data/providers/auth_provider.dart';
import '../../../data/providers/chat_provider.dart';
import '../../../data/providers/subscription_provider.dart';
import '../../../core/utils/api_helpers.dart';
import '../../../l10n/app_localizations.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _obscurePassword = true;

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _handleLogin() async {
    if (!_formKey.currentState!.validate()) return;

    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final subscriptionProvider =
        Provider.of<SubscriptionProvider>(context, listen: false);

    final success = await authProvider.login(
      email: _emailController.text.trim(),
      password: _passwordController.text,
    );

    if (!mounted) return;

    if (success) {
      // Clear any previous user's chat state
      Provider.of<ChatProvider>(context, listen: false).clearChat();

      // Fetch current subscription from API to sync with backend
      if (authProvider.token != null) {
        await subscriptionProvider.fetchCurrentSubscription(
            token: authProvider.token!);
      }

      if (authProvider.needsProfileCompletion) {
        await _showProfileCompletionDialog(context, authProvider);
        return;
      }

      Navigator.pushReplacementNamed(context, '/home');
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(authProvider.error ?? 'Erreur de connexion'),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    return Stack(
      children: [
        Scaffold(
          body: SafeArea(
            child: SingleChildScrollView(
              child: Padding(
                padding: EdgeInsets.all(24.w),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    SizedBox(height: 40.h),

                    // App Logo/Icon
                    Center(
                      child: Container(
                        width: 80.w,
                        height: 80.w,
                        decoration: BoxDecoration(
                          gradient: AppColors.primaryGradient,
                          borderRadius: BorderRadius.circular(16.r),
                        ),
                        padding: EdgeInsets.all(12.w),
                        child: Image.asset(
                          'assets/icons/logo_dossy_chat.png',
                          fit: BoxFit.contain,
                        ),
                      ),
                    ),

                    SizedBox(height: 32.h),

                    // Welcome Text
                    Text(
                      l10n.welcome,
                      style: TextStyle(
                        fontSize: 28.sp,
                        fontWeight: FontWeight.bold,
                        color: AppColors.textPrimary,
                      ),
                    ),

                    SizedBox(height: 8.h),

                    Text(
                      l10n.loginSubtitle,
                      style: TextStyle(
                        fontSize: 16.sp,
                        color: AppColors.textSecondary,
                      ),
                    ),

                    SizedBox(height: 40.h),

                    // Login Form
                    Form(
                      key: _formKey,
                      child: Column(
                        children: [
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

                          // Password Field
                          TextFormField(
                            controller: _passwordController,
                            obscureText: _obscurePassword,
                            decoration: InputDecoration(
                              labelText: l10n.password,
                              hintText: '••••••••',
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
                                return l10n.requiredField;
                              }
                              if (value.length < 6) {
                                return l10n.passwordMinLength;
                              }
                              return null;
                            },
                          ),

                          SizedBox(height: 8.h),

                          // Forgot Password
                          Align(
                            alignment: Alignment.centerRight,
                            child: TextButton(
                              onPressed: () {
                                final resetEmailController =
                                    TextEditingController();

                                showDialog(
                                  context: context,
                                  builder: (context) {
                                    return AlertDialog(
                                      title: Text(l10n.forgotPassword),
                                      content: TextField(
                                        controller: resetEmailController,
                                        keyboardType:
                                            TextInputType.emailAddress,
                                        decoration: InputDecoration(
                                          hintText: l10n.email,
                                        ),
                                      ),
                                      actions: [
                                        TextButton(
                                          onPressed: () {
                                            Navigator.of(context).pop();
                                          },
                                          child: Text(l10n.cancel),
                                        ),
                                        Consumer<AuthProvider>(
                                          builder:
                                              (context, authProvider, child) {
                                            return TextButton(
                                              onPressed: authProvider.isLoading
                                                  ? null
                                                  : () async {
                                                      final email =
                                                          resetEmailController
                                                              .text
                                                              .trim();
                                                      if (!ApiHelpers
                                                          .isValidEmail(
                                                              email)) {
                                                        ScaffoldMessenger.of(
                                                                context)
                                                            .showSnackBar(
                                                          SnackBar(
                                                            content: Text(l10n
                                                                .invalidEmail),
                                                            backgroundColor:
                                                                AppColors.error,
                                                          ),
                                                        );
                                                        return;
                                                      }

                                                      final result = await Provider
                                                              .of<AuthProvider>(
                                                                  context,
                                                                  listen: false)
                                                          .forgotPassword(
                                                              email: email);

                                                      if (result['success'] ==
                                                          true) {
                                                        Navigator.of(context)
                                                            .pop();
                                                        ScaffoldMessenger.of(
                                                                context)
                                                            .showSnackBar(
                                                          SnackBar(
                                                            content: Text(result[
                                                                    'message'] ??
                                                                l10n.resetEmailSent),
                                                            backgroundColor:
                                                                Colors.green,
                                                          ),
                                                        );
                                                      } else {
                                                        ScaffoldMessenger.of(
                                                                context)
                                                            .showSnackBar(
                                                          SnackBar(
                                                            content: Text(result[
                                                                    'message'] ??
                                                                l10n.requestError),
                                                            backgroundColor:
                                                                AppColors.error,
                                                          ),
                                                        );
                                                      }
                                                    },
                                              child: authProvider.isLoading
                                                  ? SizedBox(
                                                      width: 20.w,
                                                      height: 20.w,
                                                      child:
                                                          const CircularProgressIndicator(
                                                              strokeWidth: 2),
                                                    )
                                                  : Text(l10n.ok),
                                            );
                                          },
                                        ),
                                      ],
                                    );
                                  },
                                );
                              },
                              child: Text(
                                l10n.forgotPassword,
                                style: TextStyle(
                                  fontSize: 14.sp,
                                  color: AppColors.primary,
                                ),
                              ),
                            ),
                          ),

                          // Debug banner removed

                          // Login Button
                          Consumer<AuthProvider>(
                            builder: (context, authProvider, child) {
                              return SizedBox(
                                width: double.infinity,
                                child: ElevatedButton(
                                  onPressed: authProvider.isLoading
                                      ? null
                                      : _handleLogin,
                                  style: ElevatedButton.styleFrom(
                                    padding:
                                        EdgeInsets.symmetric(vertical: 16.h),
                                  ),
                                  child: authProvider.isLoading
                                      ? SizedBox(
                                          width: 20.w,
                                          height: 20.w,
                                          child:
                                              const CircularProgressIndicator(
                                            valueColor:
                                                AlwaysStoppedAnimation<Color>(
                                                    Colors.white),
                                            strokeWidth: 2,
                                          ),
                                        )
                                      : Text(
                                          l10n.login,
                                          style: TextStyle(fontSize: 16.sp),
                                        ),
                                ),
                              );
                            },
                          ),
                        ],
                      ),
                    ),

                    SizedBox(height: 24.h),

                    // Or Divider
                    Row(
                      children: [
                        const Expanded(
                            child: Divider(color: AppColors.divider)),
                        Padding(
                          padding: EdgeInsets.symmetric(horizontal: 16.w),
                          child: Text(
                            l10n.or,
                            style: TextStyle(
                              fontSize: 14.sp,
                              color: AppColors.textSecondary,
                            ),
                          ),
                        ),
                        const Expanded(
                            child: Divider(color: AppColors.divider)),
                      ],
                    ),

                    SizedBox(height: 24.h),

                    // Register Link
                    Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(
                          l10n.noAccountYet,
                          style: TextStyle(
                            fontSize: 14.sp,
                            color: AppColors.textSecondary,
                          ),
                        ),
                        TextButton(
                          onPressed: () {
                            Navigator.pushNamed(context, '/register');
                          },
                          style: TextButton.styleFrom(
                            padding: EdgeInsets.zero,
                          ),
                          child: Text(
                            l10n.register,
                            style: TextStyle(
                              fontSize: 14.sp,
                              fontWeight: FontWeight.w600,
                              color: AppColors.primary,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),

        // Debug overlay removed for production view
      ],
    );
  }

  Future<void> _showProfileCompletionDialog(
    BuildContext context,
    AuthProvider authProvider,
  ) async {
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
                Navigator.pushReplacementNamed(context, '/home');
              },
              child: Text(l10n.later),
            ),
            TextButton(
              onPressed: () {
                Navigator.of(context).pop();
                Navigator.pushReplacementNamed(context, '/profile');
              },
              child: Text(l10n.completeNow),
            ),
          ],
        );
      },
    );
  }
}
