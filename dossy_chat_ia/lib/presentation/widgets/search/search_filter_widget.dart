import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import '../../../core/constants/app_constants.dart';
import '../../../l10n/app_localizations.dart';

/// Widget de filtres de recherche
class SearchFilterWidget extends StatelessWidget {
  final String? selectedJurisdiction;
  final String? selectedCategory;
  final DateTime? startDate;
  final DateTime? endDate;
  final Function(String?) onJurisdictionChanged;
  final Function(String?) onCategoryChanged;
  final Function(DateTime?) onStartDateChanged;
  final Function(DateTime?) onEndDateChanged;
  final VoidCallback onApplyFilters;
  final VoidCallback onResetFilters;

  const SearchFilterWidget({
    super.key,
    this.selectedJurisdiction,
    this.selectedCategory,
    this.startDate,
    this.endDate,
    required this.onJurisdictionChanged,
    required this.onCategoryChanged,
    required this.onStartDateChanged,
    required this.onEndDateChanged,
    required this.onApplyFilters,
    required this.onResetFilters,
  });

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    return SingleChildScrollView(
      padding: EdgeInsets.all(16.w),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Juridiction
          Text(
            'Juridiction',
            style: TextStyle(
              fontSize: 16.sp,
              fontWeight: FontWeight.bold,
            ),
          ),
          SizedBox(height: 12.h),
          DropdownButtonFormField<String>(
            initialValue: selectedJurisdiction,
            decoration: InputDecoration(
              hintText: 'Sélectionner une juridiction',
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12.r),
              ),
            ),
            items: AppConstants.jurisdictions.map((j) {
              return DropdownMenuItem<String>(
                value: j['code'] as String,
                child: Text(j['name']! as String),
              );
            }).toList(),
            onChanged: onJurisdictionChanged,
          ),

          SizedBox(height: 24.h),

          // Catégorie
          Text(
            'Catégorie',
            style: TextStyle(
              fontSize: 16.sp,
              fontWeight: FontWeight.bold,
            ),
          ),
          SizedBox(height: 12.h),
          DropdownButtonFormField<String>(
            initialValue: selectedCategory,
            decoration: InputDecoration(
              hintText: 'Sélectionner une catégorie',
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12.r),
              ),
            ),
              items: [
              DropdownMenuItem(value: 'jurisprudence', child: Text(l10n.jurisprudence)),
              DropdownMenuItem(value: 'legislation', child: Text(l10n.legislation)),
              DropdownMenuItem(value: 'doctrine', child: Text(l10n.doctrine)),
              DropdownMenuItem(value: 'procedure', child: Text(l10n.procedure)),
              DropdownMenuItem(value: 'fiscal', child: Text(l10n.fiscal)),
              DropdownMenuItem(value: 'social', child: Text(l10n.social)),
            ],
            onChanged: onCategoryChanged,
          ),

          SizedBox(height: 24.h),

          // Période
          Text(
            'Période',
            style: TextStyle(
              fontSize: 16.sp,
              fontWeight: FontWeight.bold,
            ),
          ),
          SizedBox(height: 12.h),
          Row(
            children: [
              Expanded(
                child: _DatePickerField(
                  label: 'Date début',
                  selectedDate: startDate,
                  onDateSelected: onStartDateChanged,
                ),
              ),
              SizedBox(width: 12.w),
              Expanded(
                child: _DatePickerField(
                  label: 'Date fin',
                  selectedDate: endDate,
                  onDateSelected: onEndDateChanged,
                ),
              ),
            ],
          ),

          SizedBox(height: 32.h),

          // Boutons d'action
          Row(
            children: [
              Expanded(
                child: OutlinedButton(
                  onPressed: onResetFilters,
                  style: OutlinedButton.styleFrom(
                    padding: EdgeInsets.symmetric(vertical: 16.h),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12.r),
                    ),
                  ),
                  child: const Text('Réinitialiser'),
                ),
              ),
              SizedBox(width: 12.w),
              Expanded(
                child: ElevatedButton(
                  onPressed: onApplyFilters,
                  style: ElevatedButton.styleFrom(
                    padding: EdgeInsets.symmetric(vertical: 16.h),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12.r),
                    ),
                  ),
                  child: const Text('Appliquer'),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

/// Widget de sélection de date
class _DatePickerField extends StatelessWidget {
  final String label;
  final DateTime? selectedDate;
  final Function(DateTime?) onDateSelected;

  const _DatePickerField({
    required this.label,
    this.selectedDate,
    required this.onDateSelected,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: () async {
        final date = await showDatePicker(
          context: context,
          initialDate: selectedDate ?? DateTime.now(),
          firstDate: DateTime(2000),
          lastDate: DateTime.now(),
        );
        onDateSelected(date);
      },
      child: InputDecorator(
        decoration: InputDecoration(
          labelText: label,
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12.r),
          ),
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(
              selectedDate != null
                  ? '${selectedDate!.day}/${selectedDate!.month}/${selectedDate!.year}'
                  : AppLocalizations.of(context)!.selectJurisdiction,
              style: TextStyle(
                fontSize: 14.sp,
                color: selectedDate != null ? Colors.black : Colors.grey,
              ),
            ),
            const Icon(Icons.calendar_today, size: 20),
          ],
        ),
      ),
    );
  }
}