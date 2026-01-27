import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/legal_library_provider.dart';
import '../../providers/auth_provider.dart';
import '../../utils/download_helpers.dart';
import '../../data/models/document_model.dart';
import '../../l10n/app_localizations.dart';

class LegalLibraryDetailScreen extends StatelessWidget {
  final DocumentModel document;
  const LegalLibraryDetailScreen({super.key, required this.document});

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    return Scaffold(
      appBar: AppBar(title: Text(l10n.documentDetails)),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(document.title, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
            const SizedBox(height: 8),
            if (document.description != null)
              Text(document.description!, style: const TextStyle(fontSize: 14, color: Colors.grey)),
            const SizedBox(height: 12),
            Wrap(spacing: 8, children: [
              if (document.category != null)
                Chip(label: Text(document.category!), backgroundColor: Colors.green[50]),
              Chip(label: Text(document.fileType.toUpperCase())),
            ]),
            const Spacer(),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: () async {
                  final auth = context.read<AuthProvider>();
                  final provider = context.read<LegalLibraryProvider>();
                  await DownloadHelpers.downloadLegalDocument(
                    context: context,
                    documentId: document.id,
                    documentTitle: document.title,
                    fileName: '${document.title}.pdf',
                    token: auth.token,
                    fetchDownloadUrl: provider.getDocumentDownloadUrl,
                  );
                },
                icon: const Icon(Icons.download),
                label: Text(l10n.download),
              ),
            )
          ],
        ),
      ),
    );
  }
}
