import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

/// Sélecteur de méthode de paiement
class PaymentMethodSelector extends StatelessWidget {
  final String selectedMethod;
  final String selectedProvider;
  final String phoneNumber;
  final Function(String) onMethodChanged;
  final Function(String) onProviderChanged;
  final Function(String) onPhoneNumberChanged;

  const PaymentMethodSelector({
    Key? key,
    required this.selectedMethod,
    required this.selectedProvider,
    required this.phoneNumber,
    required this.onMethodChanged,
    required this.onProviderChanged,
    required this.onPhoneNumberChanged,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Méthode de paiement',
          style: TextStyle(
            fontSize: 16.sp,
            fontWeight: FontWeight.bold,
          ),
        ),
        SizedBox(height: 12.h),

        // Mobile Money
        _PaymentMethodCard(
          title: 'Mobile Money',
          icon: Icons.phone_android,
          isSelected: selectedMethod == 'mobile_money',
          onTap: () => onMethodChanged('mobile_money'),
          child: selectedMethod == 'mobile_money'
              ? Column(
                  children: [
                    SizedBox(height: 16.h),
                    // Fournisseur
                    Wrap(
                      spacing: 12.w,
                      runSpacing: 12.h,
                      children: [
                        _ProviderChip(
                          label: 'MTN Money',
                          logo: '🟡',
                          isSelected: selectedProvider == 'mtn',
                          onTap: () => onProviderChanged('mtn'),
                        ),
                        _ProviderChip(
                          label: 'Orange Money',
                          logo: '🟠',
                          isSelected: selectedProvider == 'orange',
                          onTap: () => onProviderChanged('orange'),
                        ),
                        _ProviderChip(
                          label: 'Moov Money',
                          logo: '🔵',
                          isSelected: selectedProvider == 'moov',
                          onTap: () => onProviderChanged('moov'),
                        ),
                      ],
                    ),
                    SizedBox(height: 16.h),
                    // Numéro de téléphone
                    TextFormField(
                      initialValue: phoneNumber,
                      decoration: InputDecoration(
                        labelText: 'Numéro de téléphone',
                        hintText: 'Ex: +229 XX XX XX XX',
                        prefixIcon: const Icon(Icons.phone),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12.r),
                        ),
                      ),
                      keyboardType: TextInputType.phone,
                      validator: (value) {
                        if (value == null || value.isEmpty) {
                          return 'Veuillez entrer votre numéro';
                        }
                        return null;
                      },
                      onChanged: onPhoneNumberChanged,
                    ),
                  ],
                )
              : null,
        ),

        SizedBox(height: 12.h),

        // Carte bancaire
        _PaymentMethodCard(
          title: 'Carte bancaire',
          icon: Icons.credit_card,
          isSelected: selectedMethod == 'card',
          onTap: () => onMethodChanged('card'),
          child: selectedMethod == 'card'
              ? Padding(
                  padding: EdgeInsets.only(top: 16.h),
                  child: TextFormField(
                    initialValue: phoneNumber,
                    decoration: InputDecoration(
                      labelText: 'Numéro de téléphone',
                      hintText: 'Pour la confirmation',
                      prefixIcon: const Icon(Icons.phone),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12.r),
                      ),
                    ),
                    keyboardType: TextInputType.phone,
                    validator: (value) {
                      if (value == null || value.isEmpty) {
                        return 'Veuillez entrer votre numéro';
                      }
                      return null;
                    },
                    onChanged: onPhoneNumberChanged,
                  ),
                )
              : null,
        ),
      ],
    );
  }
}

/// Carte de méthode de paiement
class _PaymentMethodCard extends StatelessWidget {
  final String title;
  final IconData icon;
  final bool isSelected;
  final VoidCallback onTap;
  final Widget? child;

  const _PaymentMethodCard({
    required this.title,
    required this.icon,
    required this.isSelected,
    required this.onTap,
    this.child,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: isSelected ? 4 : 1,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12.r),
        side: BorderSide(
          color: isSelected ? Theme.of(context).primaryColor : Colors.grey.shade300,
          width: isSelected ? 2 : 1,
        ),
      ),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12.r),
        child: Padding(
          padding: EdgeInsets.all(16.w),
          child: Column(
            children: [
              Row(
                children: [
                  Icon(
                    icon,
                    color: isSelected ? Theme.of(context).primaryColor : Colors.grey,
                  ),
                  SizedBox(width: 12.w),
                  Text(
                    title,
                    style: TextStyle(
                      fontSize: 16.sp,
                      fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                    ),
                  ),
                  const Spacer(),
                  if (isSelected)
                    Icon(
                      Icons.check_circle,
                      color: Theme.of(context).primaryColor,
                    ),
                ],
              ),
              if (child != null) child!,
            ],
          ),
        ),
      ),
    );
  }
}

/// Chip de fournisseur
class _ProviderChip extends StatelessWidget {
  final String label;
  final String logo;
  final bool isSelected;
  final VoidCallback onTap;

  const _ProviderChip({
    required this.label,
    required this.logo,
    required this.isSelected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return FilterChip(
      label: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(logo, style: TextStyle(fontSize: 20.sp)),
          SizedBox(width: 8.w),
          Text(label),
        ],
      ),
      selected: isSelected,
      onSelected: (_) => onTap(),
      selectedColor: Theme.of(context).primaryColor.withOpacity(0.2),
    );
  }
}
