import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:provider/provider.dart';
import 'package:file_picker/file_picker.dart';
import 'dart:io';
import '../../../core/theme/app_colors.dart';
import '../../../data/providers/auth_provider.dart';
import '../../../data/providers/document_provider.dart';
import '../../../l10n/app_localizations.dart';
import '../../widgets/documents/document_card.dart';

class DocumentsScreen extends StatefulWidget {
  const DocumentsScreen({super.key});

  @override
  State<DocumentsScreen> createState() => _DocumentsScreenState();
}

class _DocumentsScreenState extends State<DocumentsScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadDocuments();
    });
  }

  bool _firstLoad = true;

  Future<void> _loadDocuments() async {
      final authProvider = Provider.of<AuthProvider>(context, listen: false);
      final documentProvider =
          Provider.of<DocumentProvider>(context, listen: false);

      if (authProvider.token != null && authProvider.user != null) {
        await documentProvider.loadDocuments(
          token: authProvider.token!,
          userId: authProvider.user!.id,
          forceRefresh: _firstLoad, // Force refresh on first load after login, then use cache
        );
        _firstLoad = false; // Subsequent loads use cache
      }
    }

  Future<void> _pickAndUploadDocument() async {
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final l10n = AppLocalizations.of(context)!;

    if (!authProvider.user!.canDownload) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(l10n.uploadQuotaExhausted),
          backgroundColor: AppColors.warning,
        ),
      );
      return;
    }

    try {
      FilePickerResult? result = await FilePicker.platform.pickFiles(
        type: FileType.custom,
        allowedExtensions: [
          'pdf',
          'doc',
          'docx',
          'xls',
          'xlsx',
          'ppt',
          'pptx',
          'txt'
        ],
      );

      if (result != null && result.files.single.path != null) {
        final file = File(result.files.single.path!);

        if (!mounted) return;

        final documentProvider =
              Provider.of<DocumentProvider>(context, listen: false);

          final success = await documentProvider.uploadDocument(
            token: authProvider.token!,
            file: file,
            userId: authProvider.user!.id,
            title: result.files.single.name,
          );

        if (!mounted) return;

        if (success) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(l10n.documentUploadedSuccess),
              backgroundColor: AppColors.success,
              duration: const Duration(seconds: 3),
            ),
          );
        } else {
          final errorMessage = documentProvider.error ?? l10n.uploadError;
          print('🔴 UPLOAD FAILED: $errorMessage');
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: SingleChildScrollView(
                scrollDirection: Axis.vertical,
                child: Text(
                  errorMessage,
                  style: const TextStyle(
                    fontFamily: 'monospace',
                    fontSize: 12,
                  ),
                ),
              ),
              backgroundColor: AppColors.error,
              duration: const Duration(seconds: 10),
            ),
          );
        }
      }
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Erreur: $e'),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(AppLocalizations.of(context)!.navDocuments),
      ),
      body: Column(
        children: [
          // Quota Info
          Consumer<AuthProvider>(
            builder: (context, authProvider, child) {
              final user = authProvider.user;
              if (user == null) return const SizedBox.shrink();
              final l10n = AppLocalizations.of(context)!;

              return Container(
                padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 12.h),
                color: AppColors.primary.withAlpha((0.1 * 255).round()),
                child: Row(
                  children: [
                    Icon(
                      Icons.cloud_upload_outlined,
                      size: 20.sp,
                      color: AppColors.primary,
                    ),
                    SizedBox(width: 8.w),
                    Text(
                      '${l10n.uploads} ${user.downloadsUsed}/${user.downloadsLimit == -1 ? '\u221e' : user.downloadsLimit}',
                      style: TextStyle(
                        fontSize: 13.sp,
                        fontWeight: FontWeight.w500,
                        color: AppColors.textSecondary,
                      ),
                    ),
                    const Spacer(),
                    if (user.downloadsLimit != -1)
                      Container(
                        padding: EdgeInsets.symmetric(
                            horizontal: 8.w, vertical: 4.h),
                        decoration: BoxDecoration(
                          color: AppColors.getPlanColor(user.plan)
                              .withAlpha((0.2 * 255).round()),
                          borderRadius: BorderRadius.circular(12.r),
                        ),
                        child: Text(
                          user.plan,
                          style: TextStyle(
                            fontSize: 11.sp,
                            fontWeight: FontWeight.w600,
                            color: AppColors.getPlanColor(user.plan),
                          ),
                        ),
                      ),
                  ],
                ),
              );
            },
          ),

          // Documents List
          Expanded(
            child: Consumer<DocumentProvider>(
              builder: (context, documentProvider, child) {
                if (documentProvider.isLoading) {
                  return const Center(
                    child: CircularProgressIndicator(),
                  );
                }

                if (documentProvider.documents.isEmpty) {
                  return Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(
                          Icons.folder_open,
                          size: 80.sp,
                          color: AppColors.textHint,
                        ),
                        SizedBox(height: 16.h),
                        Text(
                          AppLocalizations.of(context)!.noDocuments,
                          style: TextStyle(
                            fontSize: 18.sp,
                            fontWeight: FontWeight.w600,
                            color: AppColors.textSecondary,
                          ),
                        ),
                        SizedBox(height: 8.h),
                        Text(
                          AppLocalizations.of(context)!.uploadDocumentsForAdvancedRAG,
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            fontSize: 14.sp,
                            color: AppColors.textSecondary,
                          ),
                        ),
                        SizedBox(height: 6.h),
                        Text(
                          AppLocalizations.of(context)!.uploadDocumentsAndAskChat,
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            fontSize: 13.sp,
                            color: AppColors.textSecondary,
                          ),
                        ),
                      ],
                    ),
                  );
                }

                return RefreshIndicator(
                  onRefresh: _loadDocuments,
                  child: ListView.builder(
                    padding: EdgeInsets.all(16.w),
                    itemCount: documentProvider.documents.length,
                    itemBuilder: (context, index) {
                      final document = documentProvider.documents[index];
                      return DocumentCard(
                        document: document,
                        onDelete: () => _deleteDocument(document.id),
                      );
                    },
                  ),
                );
              },
            ),
          ),
        ],
      ),
      floatingActionButton: Consumer<DocumentProvider>(
        builder: (context, documentProvider, child) {
          return FloatingActionButton.extended(
            onPressed:
                documentProvider.isUploading ? null : _pickAndUploadDocument,
            icon: documentProvider.isUploading
                ? SizedBox(
                    width: 20.w,
                    height: 20.w,
                    child: const CircularProgressIndicator(
                      valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                      strokeWidth: 2,
                    ),
                  )
                : const Icon(Icons.upload_file),
            label: Text(documentProvider.isUploading
                ? AppLocalizations.of(context)!.uploading
                : AppLocalizations.of(context)!.upload),
          );
        },
      ),
    );
  }

  Future<void> _deleteDocument(int documentId) async {
    final l10n = AppLocalizations.of(context)!;
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: Text(l10n.deleteDocument),
          content:
              Text(l10n.confirmDeleteDocument),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: Text(l10n.cancel),
            ),
            ElevatedButton(
              onPressed: () => Navigator.pop(context, true),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.error,
              ),
              child: Text(l10n.delete),
            ),
          ],
        );
      },
    );

    if (confirmed != true) return;
    if (!mounted) return;

    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final documentProvider =
        Provider.of<DocumentProvider>(context, listen: false);

    final success = await documentProvider.deleteDocument(
      token: authProvider.token!,
      documentId: documentId,
        userId: authProvider.user!.id,
    );

    if (!mounted) return;

    if (success) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(l10n.documentDeleted),
          backgroundColor: AppColors.success,
        ),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content:
              Text(documentProvider.error ?? l10n.deletionError),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }
}
