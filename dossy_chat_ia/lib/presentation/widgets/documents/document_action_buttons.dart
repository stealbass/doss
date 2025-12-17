import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

/// Boutons d'action pour les documents
class DocumentActionButtons extends StatelessWidget {
  final bool isFavorite;
  final VoidCallback onFavoriteToggle;
  final VoidCallback onShare;
  final VoidCallback onDownload;
  final VoidCallback? onPrint;

  const DocumentActionButtons({
    Key? key,
    required this.isFavorite,
    required this.onFavoriteToggle,
    required this.onShare,
    required this.onDownload,
    this.onPrint,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceEvenly,
      children: [
        _ActionButton(
          icon: isFavorite ? Icons.favorite : Icons.favorite_border,
          label: 'Favori',
          onPressed: onFavoriteToggle,
          color: isFavorite ? Colors.red : null,
        ),
        _ActionButton(
          icon: Icons.share,
          label: 'Partager',
          onPressed: onShare,
        ),
        _ActionButton(
          icon: Icons.download,
          label: 'Télécharger',
          onPressed: onDownload,
        ),
        if (onPrint != null)
          _ActionButton(
            icon: Icons.print,
            label: 'Imprimer',
            onPressed: onPrint!,
          ),
      ],
    );
  }
}

/// Bouton d'action individuel
class _ActionButton extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback onPressed;
  final Color? color;

  const _ActionButton({
    required this.icon,
    required this.label,
    required this.onPressed,
    this.color,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onPressed,
      borderRadius: BorderRadius.circular(8.r),
      child: Padding(
        padding: EdgeInsets.all(8.w),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              icon,
              size: 28.sp,
              color: color ?? Theme.of(context).primaryColor,
            ),
            SizedBox(height: 4.h),
            Text(
              label,
              style: TextStyle(
                fontSize: 12.sp,
                color: color ?? Theme.of(context).textTheme.bodyMedium?.color,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
