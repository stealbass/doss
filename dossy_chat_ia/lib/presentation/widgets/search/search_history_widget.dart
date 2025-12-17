import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

/// Widget d'affichage de l'historique de recherche
class SearchHistoryWidget extends StatelessWidget {
  final List<String> searchHistory;
  final Function(String) onHistoryItemTap;
  final VoidCallback onClearHistory;

  const SearchHistoryWidget({
    Key? key,
    required this.searchHistory,
    required this.onHistoryItemTap,
    required this.onClearHistory,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    if (searchHistory.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.history, size: 64.sp, color: Colors.grey),
            SizedBox(height: 16.h),
            Text(
              'Aucun historique de recherche',
              style: TextStyle(fontSize: 16.sp, color: Colors.grey),
            ),
          ],
        ),
      );
    }

    return Column(
      children: [
        Padding(
          padding: EdgeInsets.all(16.w),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Historique (${searchHistory.length})',
                style: TextStyle(
                  fontSize: 18.sp,
                  fontWeight: FontWeight.bold,
                ),
              ),
              TextButton.icon(
                onPressed: onClearHistory,
                icon: const Icon(Icons.delete_outline),
                label: const Text('Effacer tout'),
              ),
            ],
          ),
        ),
        Expanded(
          child: ListView.separated(
            padding: EdgeInsets.symmetric(horizontal: 16.w),
            itemCount: searchHistory.length,
            separatorBuilder: (context, index) => const Divider(),
            itemBuilder: (context, index) {
              final query = searchHistory[index];
              return ListTile(
                leading: const Icon(Icons.history),
                title: Text(query),
                onTap: () => onHistoryItemTap(query),
                trailing: const Icon(Icons.arrow_forward_ios, size: 16),
              );
            },
          ),
        ),
      ],
    );
  }
}
