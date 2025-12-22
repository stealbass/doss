import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import '../../../data/models/document.dart';

/// Feuille d'informations du document
class DocumentInfoSheet extends StatelessWidget {
  final Document document;

  const DocumentInfoSheet({
    Key? key,
    required this.document,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return DraggableScrollableSheet(
      initialChildSize: 0.6,
      minChildSize: 0.4,
      maxChildSize: 0.9,
      builder: (context, scrollController) {
        return Container(
          decoration: BoxDecoration(
            color: Theme.of(context).cardColor,
            borderRadius: BorderRadius.only(
              topLeft: Radius.circular(20.r),
              topRight: Radius.circular(20.r),
            ),
          ),
          child: Column(
            children: [
              // Handle
              Container(
                margin: EdgeInsets.symmetric(vertical: 12.h),
                width: 40.w,
                height: 4.h,
                decoration: BoxDecoration(
                  color: Colors.grey[300],
                  borderRadius: BorderRadius.circular(2.r),
                ),
              ),

              // Titre
              Padding(
                padding: EdgeInsets.symmetric(horizontal: 16.w),
                child: Row(
                  children: [
                    Icon(
                      Icons.info_outline,
                      color: Theme.of(context).primaryColor,
                    ),
                    SizedBox(width: 12.w),
                    Expanded(
                      child: Text(
                        'Informations du document',
                        style: TextStyle(
                          fontSize: 18.sp,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                    IconButton(
                      icon: const Icon(Icons.close),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ],
                ),
              ),

              const Divider(),

              // Contenu
              Expanded(
                child: ListView(
                  controller: scrollController,
                  padding: EdgeInsets.all(16.w),
                  children: [
                    _InfoItem(
                      label: 'Titre',
                      value: document.title,
                    ),
                    if (document.summary != null)
                      _InfoItem(
                        label: 'Résumé',
                        value: document.summary!,
                      ),
                    _InfoItem(
                      label: 'Type',
                      value: document.type,
                    ),
                    if (document.jurisdiction != null)
                      _InfoItem(
                        label: 'Juridiction',
                        value: document.jurisdiction!,
                      ),
                    if (document.category != null)
                      _InfoItem(
                        label: 'Catégorie',
                        value: document.category!,
                      ),
                    if (document.publishedAt != null)
                      _InfoItem(
                        label: 'Date de publication',
                        value: _formatDate(document.publishedAt!),
                      ),
                    _InfoItem(
                      label: 'Date de création',
                      value: _formatDate(document.createdAt ?? document.uploadedAt),
                    ),
                    if (document.fileSize != null)
                      _InfoItem(
                        label: 'Taille',
                        value: _formatFileSize(document.fileSize!),
                      ),
                    if (document.language != null)
                      _InfoItem(
                        label: 'Langue',
                        value: document.language!,
                      ),
                  ],
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  String _formatDate(DateTime date) {
    return '${date.day}/${date.month}/${date.year}';
  }

  String _formatFileSize(int bytes) {
    if (bytes < 1024) return '$bytes B';
    if (bytes < 1024 * 1024) return '${(bytes / 1024).toStringAsFixed(1)} KB';
    return '${(bytes / (1024 * 1024)).toStringAsFixed(1)} MB';
  }
}

/// Item d'information
class _InfoItem extends StatelessWidget {
  final String label;
  final String value;

  const _InfoItem({
    required this.label,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(bottom: 16.h),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: TextStyle(
              fontSize: 12.sp,
              color: Colors.grey,
              fontWeight: FontWeight.w500,
            ),
          ),
          SizedBox(height: 4.h),
          Text(
            value,
            style: TextStyle(
              fontSize: 14.sp,
              fontWeight: FontWeight.w500,
            ),
          ),
        ],
      ),
    );
  }
}
