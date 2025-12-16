import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:provider/provider.dart';
import 'package:file_picker/file_picker.dart';
import 'dart:io';
import '../../../core/theme/app_colors.dart';
import '../../../core/constants/app_constants.dart';
import '../../../data/providers/auth_provider.dart';
import '../../../data/providers/document_provider.dart';
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

  Future<void> _loadDocuments() async {
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final documentProvider = Provider.of<DocumentProvider>(context, listen: false);
    
    if (authProvider.token != null) {
      await documentProvider.loadDocuments(
        token: authProvider.token!,
        category: documentProvider.selectedCategory,
      );
    }
  }

  Future<void> _pickAndUploadDocument() async {
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    
    if (!authProvider.user!.canDownload) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Quota de téléchargements épuisé. Veuillez souscrire à un plan.'),
          backgroundColor: AppColors.warning,
        ),
      );
      return;
    }

    try {
      FilePickerResult? result = await FilePicker.platform.pickFiles(
        type: FileType.custom,
        allowedExtensions: ['pdf', 'doc', 'docx'],
      );

      if (result != null && result.files.single.path != null) {
        final file = File(result.files.single.path!);
        
        // Show category selection dialog
        final category = await _showCategoryDialog();
        if (category == null) return;
        
        if (!mounted) return;
        
        final documentProvider = Provider.of<DocumentProvider>(context, listen: false);
        
        final success = await documentProvider.uploadDocument(
          token: authProvider.token!,
          file: file,
          category: category,
        );
        
        if (!mounted) return;
        
        if (success) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Document uploadé avec succès'),
              backgroundColor: AppColors.success,
            ),
          );
          // Refresh user to update quota
          await authProvider.refreshUser();
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(documentProvider.error ?? 'Erreur lors de l\'upload'),
              backgroundColor: AppColors.error,
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

  Future<String?> _showCategoryDialog() async {
    return showDialog<String>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Catégorie du document'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: AppConstants.legalCategories.map((category) {
              return ListTile(
                title: Text(category),
                onTap: () => Navigator.pop(context, category),
              );
            }).toList(),
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Mes Documents'),
        actions: [
          IconButton(
            icon: const Icon(Icons.filter_list),
            onPressed: _showFilterDialog,
          ),
        ],
      ),
      body: Column(
        children: [
          // Quota Info
          Consumer<AuthProvider>(
            builder: (context, authProvider, child) {
              final user = authProvider.user;
              if (user == null) return const SizedBox.shrink();
              
              return Container(
                padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 12.h),
                color: AppColors.primary.withOpacity(0.1),
                child: Row(
                  children: [
                    Icon(
                      Icons.cloud_upload_outlined,
                      size: 20.sp,
                      color: AppColors.primary,
                    ),
                    SizedBox(width: 8.w),
                    Text(
                      'Uploads: ${user.downloadsUsed}/${user.downloadsLimit == -1 ? '∞' : user.downloadsLimit}',
                      style: TextStyle(
                        fontSize: 13.sp,
                        fontWeight: FontWeight.w500,
                        color: AppColors.textSecondary,
                      ),
                    ),
                    const Spacer(),
                    if (user.downloadsLimit != -1)
                      Container(
                        padding: EdgeInsets.symmetric(horizontal: 8.w, vertical: 4.h),
                        decoration: BoxDecoration(
                          color: AppColors.getPlanColor(user.plan).withOpacity(0.2),
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
          
          // Category Filter Chips
          Consumer<DocumentProvider>(
            builder: (context, documentProvider, child) {
              return Container(
                padding: EdgeInsets.symmetric(vertical: 12.h),
                child: SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  padding: EdgeInsets.symmetric(horizontal: 16.w),
                  child: Row(
                    children: [
                      _buildFilterChip('Tous', null, documentProvider),
                      SizedBox(width: 8.w),
                      ...AppConstants.legalCategories.map((category) {
                        return Padding(
                          padding: EdgeInsets.only(right: 8.w),
                          child: _buildFilterChip(category, category, documentProvider),
                        );
                      }).toList(),
                    ],
                  ),
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
                
                if (documentProvider.filteredDocuments.isEmpty) {
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
                          'Aucun document',
                          style: TextStyle(
                            fontSize: 18.sp,
                            fontWeight: FontWeight.w600,
                            color: AppColors.textSecondary,
                          ),
                        ),
                        SizedBox(height: 8.h),
                        Text(
                          'Uploadez vos documents pour\nutiliser le RAG avancé',
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            fontSize: 14.sp,
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
                    itemCount: documentProvider.filteredDocuments.length,
                    itemBuilder: (context, index) {
                      final document = documentProvider.filteredDocuments[index];
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
            onPressed: documentProvider.isUploading ? null : _pickAndUploadDocument,
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
            label: Text(documentProvider.isUploading ? 'Upload...' : 'Upload'),
          );
        },
      ),
    );
  }

  Widget _buildFilterChip(String label, String? category, DocumentProvider provider) {
    final isSelected = provider.selectedCategory == category;
    
    return GestureDetector(
      onTap: () {
        provider.setCategory(category);
      },
      child: Container(
        padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 8.h),
        decoration: BoxDecoration(
          color: isSelected ? AppColors.primary : AppColors.inputBackground,
          borderRadius: BorderRadius.circular(20.r),
          border: Border.all(
            color: isSelected ? AppColors.primary : AppColors.border,
            width: 1,
          ),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 13.sp,
            fontWeight: FontWeight.w500,
            color: isSelected ? Colors.white : AppColors.textSecondary,
          ),
        ),
      ),
    );
  }

  void _showFilterDialog() {
    showModalBottomSheet(
      context: context,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20.r)),
      ),
      builder: (context) {
        return Consumer<DocumentProvider>(
          builder: (context, documentProvider, child) {
            return Padding(
              padding: EdgeInsets.all(24.w),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Filtrer par catégorie',
                    style: TextStyle(
                      fontSize: 18.sp,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  SizedBox(height: 16.h),
                  ListTile(
                    title: const Text('Tous'),
                    trailing: documentProvider.selectedCategory == null
                        ? const Icon(Icons.check, color: AppColors.primary)
                        : null,
                    onTap: () {
                      documentProvider.setCategory(null);
                      Navigator.pop(context);
                    },
                  ),
                  ...AppConstants.legalCategories.map((category) {
                    return ListTile(
                      title: Text(category),
                      trailing: documentProvider.selectedCategory == category
                          ? const Icon(Icons.check, color: AppColors.primary)
                          : null,
                      onTap: () {
                        documentProvider.setCategory(category);
                        Navigator.pop(context);
                      },
                    );
                  }).toList(),
                ],
              ),
            );
          },
        );
      },
    );
  }

  Future<void> _deleteDocument(int documentId) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Supprimer le document'),
          content: const Text('Êtes-vous sûr de vouloir supprimer ce document ?'),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Annuler'),
            ),
            ElevatedButton(
              onPressed: () => Navigator.pop(context, true),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.error,
              ),
              child: const Text('Supprimer'),
            ),
          ],
        );
      },
    );

    if (confirmed != true) return;
    if (!mounted) return;

    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final documentProvider = Provider.of<DocumentProvider>(context, listen: false);

    final success = await documentProvider.deleteDocument(
      token: authProvider.token!,
      documentId: documentId,
    );

    if (!mounted) return;

    if (success) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Document supprimé'),
          backgroundColor: AppColors.success,
        ),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(documentProvider.error ?? 'Erreur lors de la suppression'),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }
}
