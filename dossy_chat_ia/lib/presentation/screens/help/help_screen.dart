import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:url_launcher/url_launcher.dart';

/// Écran d'aide et FAQ
class HelpScreen extends StatelessWidget {
  const HelpScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Aide et Support'),
        elevation: 0,
      ),
      body: ListView(
        padding: EdgeInsets.all(16.w),
        children: [
          // Contact rapide
          _buildQuickContactSection(context),
          SizedBox(height: 24.h),

          // FAQ
          Text(
            'Questions Fréquentes',
            style: TextStyle(
              fontSize: 20.sp,
              fontWeight: FontWeight.bold,
            ),
          ),
          SizedBox(height: 16.h),

          _buildFAQSection(),
        ],
      ),
    );
  }

  Widget _buildQuickContactSection(BuildContext context) {
    return Card(
      child: Padding(
        padding: EdgeInsets.all(16.w),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Besoin d\'aide ?',
              style: TextStyle(
                fontSize: 18.sp,
                fontWeight: FontWeight.bold,
              ),
            ),
            SizedBox(height: 16.h),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                _buildContactButton(
                  icon: Icons.email,
                  label: 'Email',
                  onTap: () => _launchEmail(),
                  color: Colors.blue,
                ),
                _buildContactButton(
                  icon: Icons.chat,
                  label: 'WhatsApp',
                  onTap: () => _launchWhatsApp(),
                  color: Colors.green,
                ),
                _buildContactButton(
                  icon: Icons.phone,
                  label: 'Appeler',
                  onTap: () => _launchPhone(),
                  color: Colors.orange,
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildContactButton({
    required IconData icon,
    required String label,
    required VoidCallback onTap,
    required Color color,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12.r),
      child: Container(
        padding: EdgeInsets.all(12.w),
        child: Column(
          children: [
            CircleAvatar(
              radius: 24.r,
              backgroundColor: color.withAlpha((0.1 * 255).round()),
              child: Icon(icon, color: color, size: 24.sp),
            ),
            SizedBox(height: 8.h),
            Text(
              label,
              style: TextStyle(
                fontSize: 12.sp,
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildFAQSection() {
    return Column(
      children: [
        _buildFAQItem(
          question: 'Comment créer un compte ?',
          answer: 'Appuyez sur "S\'inscrire" sur l\'écran de connexion, '
              'remplissez vos informations et validez votre email.',
        ),
        _buildFAQItem(
          question: 'Comment fonctionne la recherche IA ?',
          answer: 'Notre recherche IA utilise GPT-4 et Pinecone pour comprendre '
              'votre question et trouver les documents juridiques les plus pertinents.',
        ),
        _buildFAQItem(
          question: 'Quels modes de paiement sont acceptés ?',
          answer: 'Nous acceptons Mobile Money (MTN, Orange, Moov) et les '
              'cartes bancaires (Visa, Mastercard) via Flutterwave.',
        ),
        _buildFAQItem(
          question: 'Comment annuler mon abonnement ?',
          answer: 'Allez dans Profil > Abonnement > Gérer l\'abonnement > '
              'Annuler. Vous conserverez l\'accès jusqu\'à la fin de la période.',
        ),
        _buildFAQItem(
          question: 'Puis-je utiliser l\'app hors ligne ?',
          answer: 'Oui, les documents téléchargés et l\'historique sont '
              'accessibles hors ligne. Activez le mode hors ligne dans les paramètres.',
        ),
        _buildFAQItem(
          question: 'Comment fonctionne le parrainage ?',
          answer: 'Partagez votre code de parrainage. Chaque filleul vous rapporte '
              '500 FCFA après son premier paiement.',
        ),
        _buildFAQItem(
          question: 'Les documents sont-ils à jour ?',
          answer: 'Oui, notre base est mise à jour quotidiennement avec les '
              'dernières jurisprudences et lois des 14 pays couverts.',
        ),
        _buildFAQItem(
          question: 'Combien de pays sont couverts ?',
          answer: '14 pays francophones africains : Bénin, Burkina Faso, '
              'Côte d\'Ivoire, Guinée-Bissau, Mali, Niger, Sénégal, Togo, '
              'Cameroun, RD Congo, Gabon, Madagascar, Maroc, Tunisie.',
        ),
      ],
    );
  }

  Widget _buildFAQItem({
    required String question,
    required String answer,
  }) {
    return Card(
      margin: EdgeInsets.only(bottom: 12.h),
      child: ExpansionTile(
        title: Text(
          question,
          style: TextStyle(
            fontSize: 14.sp,
            fontWeight: FontWeight.w600,
          ),
        ),
        children: [
          Padding(
            padding: EdgeInsets.fromLTRB(16.w, 0, 16.w, 16.h),
            child: Text(
              answer,
              style: TextStyle(
                fontSize: 14.sp,
                color: Colors.grey[700],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _launchEmail() async {
    final Uri emailUri = Uri(
      scheme: 'mailto',
      path: 'contact@dossypro.com',
      query: 'subject=Support DOSSY Chat IA',
    );
    if (await canLaunchUrl(emailUri)) {
      await launchUrl(emailUri);
    }
  }

  Future<void> _launchWhatsApp() async {
    final Uri whatsappUri = Uri.parse('https://wa.me/22900000000');
    if (await canLaunchUrl(whatsappUri)) {
      await launchUrl(whatsappUri, mode: LaunchMode.externalApplication);
    }
  }

  Future<void> _launchPhone() async {
    final Uri phoneUri = Uri.parse('tel:+22900000000');
    if (await canLaunchUrl(phoneUri)) {
      await launchUrl(phoneUri);
    }
  }
}
