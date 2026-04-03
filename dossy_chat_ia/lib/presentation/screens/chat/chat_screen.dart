import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:provider/provider.dart';
import 'dart:async';
import '../../../core/theme/app_colors.dart';
import '../../../core/constants/app_constants.dart';
import '../../../l10n/app_localizations.dart';
import '../../../data/providers/auth_provider.dart';
import '../../../data/providers/chat_provider.dart';
import '../../../data/providers/document_provider.dart';
import '../../../data/services/storage_service.dart';
import '../../../core/services/push_notification_service.dart';
import '../../widgets/chat/chat_bubble.dart';
import '../../widgets/chat/prompt_suggestion_chip.dart';
import 'conversations_list_screen.dart';

class ChatScreen extends StatefulWidget {
  const ChatScreen({super.key});

  @override
  State<ChatScreen> createState() => _ChatScreenState();
}

class _ChatScreenState extends State<ChatScreen> {
  final TextEditingController _messageController = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  final Set<int> _selectedDocumentIds = {};
  final PushNotificationService _pushNotificationService =
      PushNotificationService();

  StreamSubscription? _pushStreamSubscription;

  bool _showDocumentSelector = false;
  int _unreadPushCount = 0;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _scrollToBottom());
    _refreshUnreadPushCount();
    _pushStreamSubscription =
        _pushNotificationService.onMessageReceived.listen((_) {
      _refreshUnreadPushCount();
    });
  }

  @override
  void dispose() {
    _pushStreamSubscription?.cancel();
    _messageController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _refreshUnreadPushCount() async {
    final count = await _pushNotificationService.getUnreadInboxCount();
    if (!mounted) return;
    setState(() {
      _unreadPushCount = count;
    });
  }

  Future<void> _openPushInbox() async {
    final l10n = AppLocalizations.of(context)!;
    final messages = await _pushNotificationService.getInboxMessages();

    if (!mounted) return;

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(16.r)),
      ),
      builder: (context) {
        if (messages.isEmpty) {
          return SizedBox(
            height: 260.h,
            child: Center(
              child: Text(
                Localizations.localeOf(context).languageCode == 'en'
                    ? 'No recent push messages'
                    : 'Aucun message push recent',
                style: TextStyle(
                  fontSize: 14.sp,
                  color: AppColors.textSecondary,
                ),
              ),
            ),
          );
        }

        return SafeArea(
          child: SizedBox(
            height: 460.h,
            child: Column(
              children: [
                SizedBox(height: 8.h),
                Container(
                  width: 42.w,
                  height: 4.h,
                  decoration: BoxDecoration(
                    color: Colors.grey.shade300,
                    borderRadius: BorderRadius.circular(8.r),
                  ),
                ),
                Padding(
                  padding:
                      EdgeInsets.symmetric(horizontal: 16.w, vertical: 14.h),
                  child: Row(
                    children: [
                      Icon(Icons.notifications_active_outlined,
                          size: 20.sp, color: AppColors.primary),
                      SizedBox(width: 8.w),
                      Text(
                        l10n.pushNotifications,
                        style: TextStyle(
                          fontSize: 16.sp,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ],
                  ),
                ),
                Expanded(
                  child: ListView.separated(
                    itemCount: messages.length,
                    separatorBuilder: (_, __) => const Divider(height: 1),
                    itemBuilder: (context, index) {
                      final item = messages[index];
                      return ListTile(
                        leading: Icon(
                          item.isRead
                              ? Icons.notifications_none_outlined
                              : Icons.notifications_active,
                          color: item.isRead
                              ? AppColors.textSecondary
                              : AppColors.primary,
                        ),
                        title: Text(
                          item.title,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            fontSize: 13.sp,
                            fontWeight:
                                item.isRead ? FontWeight.w500 : FontWeight.w700,
                          ),
                        ),
                        subtitle: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            SizedBox(height: 4.h),
                            Text(
                              item.body,
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(fontSize: 12.sp),
                            ),
                            SizedBox(height: 4.h),
                            Text(
                              _formatPushDate(item.receivedAt),
                              style: TextStyle(
                                fontSize: 10.sp,
                                color: AppColors.textSecondary,
                              ),
                            ),
                          ],
                        ),
                        onTap: () {
                          Navigator.pop(context);
                          _showPushMessageDialog(item);
                        },
                      );
                    },
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );

    await _pushNotificationService.markAllInboxAsRead();
    await _refreshUnreadPushCount();
  }

  Future<void> _showPushMessageDialog(PushInboxItem item) async {
    if (!mounted) return;

    final String notificationId = (item.notificationId ?? '').trim();
    if (notificationId.isNotEmpty) {
      await _pushNotificationService.trackNotificationOpened(notificationId);
    }

    if (!mounted) return;

    final DateTime displayDate = item.receivedAt;
    final bool isEnglish = Localizations.localeOf(context).languageCode == 'en';

    await showDialog<void>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: Text(item.title),
          content: SingleChildScrollView(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  _formatPushDate(displayDate),
                  style: TextStyle(
                    fontSize: 11.sp,
                    color: AppColors.textSecondary,
                  ),
                ),
                SizedBox(height: 12.h),
                Text(
                  isEnglish
                      ? 'To view the full message content, please open it in your email inbox.'
                      : 'Pour voir le contenu complet du message, veuillez l\'ouvrir dans votre boite email.',
                  style: TextStyle(
                    fontSize: 13.sp,
                    height: 1.45,
                    color: AppColors.textPrimary,
                  ),
                ),
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: Text(isEnglish ? 'Close' : 'Fermer'),
            ),
          ],
        );
      },
    );
  }

  String _formatPushDate(DateTime dateTime) {
    final dt = dateTime.toLocal();
    final day = dt.day.toString().padLeft(2, '0');
    final month = dt.month.toString().padLeft(2, '0');
    final year = dt.year.toString();
    final hour = dt.hour.toString().padLeft(2, '0');
    final minute = dt.minute.toString().padLeft(2, '0');

    return '$day/$month/$year $hour:$minute';
  }

  void _scrollToBottom() {
    if (!_scrollController.hasClients) return;
    _scrollController.animateTo(
      _scrollController.position.maxScrollExtent,
      duration: const Duration(milliseconds: 300),
      curve: Curves.easeOut,
    );
  }

  Future<void> _sendMessage() async {
    final message = _messageController.text.trim();
    if (message.isEmpty) return;

    _messageController.clear();

    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final chatProvider = Provider.of<ChatProvider>(context, listen: false);
    final l10n = AppLocalizations.of(context)!;

    if (authProvider.token == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(l10n.pleaseLogin),
          backgroundColor: AppColors.error,
        ),
      );
      return;
    }

    if (authProvider.needsProfileCompletion) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(l10n.completeProfileMessage),
          backgroundColor: AppColors.warning,
        ),
      );
      if (!mounted) return;
      Navigator.pushNamed(context, '/profile');
      return;
    }

    final user = authProvider.user;
    if (user != null && !user.canAnalyze) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(l10n.analysisQuotaExhausted),
          backgroundColor: AppColors.warning,
        ),
      );
      return;
    }

    final documentIds =
        _selectedDocumentIds.isEmpty ? null : _selectedDocumentIds.toList();
    final hasSelectedDocs = documentIds != null && documentIds.isNotEmpty;

    await chatProvider.sendMessage(
      message: message,
      token: authProvider.token!,
      useSimpleRag: !hasSelectedDocs,
      useAdvancedRag: true,
      documentIds: documentIds,
      enableAnonymization: true,
    );

    if (chatProvider.error != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(chatProvider.error!),
          backgroundColor: AppColors.error,
        ),
      );
    }

    _scrollToBottom();
    await authProvider.refreshUser();
  }

  Widget _buildProfileGate(AppLocalizations l10n, AuthProvider authProvider) {
    final missingFields = authProvider.missingProfileFields;

    String missingLabel(String field) {
      if (field == 'jurisdiction') return l10n.jurisdiction;
      if (field == 'mobile_role') return l10n.role;
      return field;
    }

    final missingText = missingFields.isNotEmpty
        ? missingFields.map(missingLabel).join(', ')
        : '';

    return Center(
      child: Padding(
        padding: EdgeInsets.all(24.w),
        child: Card(
          elevation: 2,
          child: Padding(
            padding: EdgeInsets.all(20.w),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(
                  Icons.lock_outline,
                  size: 48.sp,
                  color: AppColors.warning,
                ),
                SizedBox(height: 12.h),
                Text(
                  l10n.completeProfileTitle,
                  style: TextStyle(
                    fontSize: 18.sp,
                    fontWeight: FontWeight.w700,
                  ),
                  textAlign: TextAlign.center,
                ),
                SizedBox(height: 8.h),
                Text(
                  missingText.isEmpty
                      ? l10n.completeProfileMessage
                      : '${l10n.completeProfileMessage}\n\n${l10n.missingFieldsLabel}: $missingText',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 13.sp,
                    color: AppColors.textSecondary,
                  ),
                ),
                SizedBox(height: 16.h),
                ElevatedButton(
                  onPressed: () {
                    Navigator.pushNamed(context, '/profile');
                  },
                  child: Text(l10n.completeNow),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildStatusBar(AppLocalizations l10n, AuthProvider authProvider) {
    final user = authProvider.user;
    if (user == null) return const SizedBox.shrink();

    return Container(
      padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 8.h),
      color: AppColors.primary.withAlpha((0.1 * 255).round()),
      child: Row(
        children: [
          Icon(
            Icons.analytics_outlined,
            size: 16.sp,
            color: AppColors.primary,
          ),
          SizedBox(width: 8.w),
          Text(
            '${l10n.analyses}: ${user.analysesUsed}/${user.analysesLimit == -1 ? '∞' : user.analysesLimit}',
            style: TextStyle(
              fontSize: 12.sp,
              color: AppColors.textSecondary,
            ),
          ),
          const Spacer(),
          if (user.analysesLimit != -1 && user.plan.isNotEmpty)
            Container(
              padding: EdgeInsets.symmetric(horizontal: 8.w, vertical: 4.h),
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
  }

  Widget _buildMessageList() {
    return Consumer<ChatProvider>(
      builder: (context, chatProvider, child) {
        if (chatProvider.messages.isEmpty) {
          return Padding(
            padding: EdgeInsets.all(16.w),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Aucun message pour le moment',
                  style: TextStyle(
                      color: AppColors.textSecondary, fontSize: 12.sp),
                ),
                SizedBox(height: 12.h),
                _buildPromptSuggestions(),
              ],
            ),
          );
        }
        return ListView.builder(
          controller: _scrollController,
          padding: EdgeInsets.all(16.w),
          itemCount: chatProvider.messages.length,
          itemBuilder: (context, index) {
            final message = chatProvider.messages[index];
            return ChatBubble(message: message);
          },
        );
      },
    );
  }

  Widget _buildPromptSuggestions() {
    final isEnglish = Localizations.localeOf(context).languageCode == 'en';

    Widget strategicSuggestion({
      required String role,
      required String message,
      required Color accent,
      String? actionLabel,
      VoidCallback? onAction,
    }) {
      return Container(
        width: double.infinity,
        margin: EdgeInsets.only(bottom: 8.h),
        padding: EdgeInsets.all(12.w),
        decoration: BoxDecoration(
          color: accent.withAlpha((0.08 * 255).round()),
          borderRadius: BorderRadius.circular(12.r),
          border: Border.all(color: accent.withAlpha((0.35 * 255).round())),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              role,
              style: TextStyle(
                fontSize: 12.sp,
                fontWeight: FontWeight.w700,
                color: accent,
              ),
            ),
            SizedBox(height: 4.h),
            GestureDetector(
              onTap: () {
                _messageController.text = message;
                _sendMessage();
              },
              child: Text(
                message,
                style: TextStyle(
                  fontSize: 12.sp,
                  color: AppColors.textPrimary,
                  height: 1.35,
                ),
              ),
            ),
            if (actionLabel != null && onAction != null) ...[
              SizedBox(height: 8.h),
              OutlinedButton(
                onPressed: onAction,
                style: OutlinedButton.styleFrom(
                  foregroundColor: accent,
                  side: BorderSide(color: accent),
                  padding:
                      EdgeInsets.symmetric(horizontal: 12.w, vertical: 6.h),
                  textStyle:
                      TextStyle(fontSize: 11.sp, fontWeight: FontWeight.w600),
                ),
                child: Text(actionLabel),
              ),
            ],
          ],
        ),
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          AppLocalizations.of(context)!.suggestions,
          style: TextStyle(
            fontSize: 13.sp,
            fontWeight: FontWeight.w600,
            color: AppColors.textSecondary,
          ),
        ),
        SizedBox(height: 8.h),
        strategicSuggestion(
          role: isEnglish ? 'Student' : 'Étudiant',
          message: isEnglish
              ? 'Send me a court ruling text, and I will generate a summary sheet in 10 seconds.'
              : 'Envoie-moi un texte d\'arrêt, je te génère la fiche en 10 secondes.',
          accent: AppColors.planEtudiant,
          actionLabel:
              isEnglish ? 'Boost your learning' : 'Boostez votre apprentissage',
          onAction: () => Navigator.pushNamed(context, '/tools'),
        ),
        strategicSuggestion(
          role: isEnglish ? 'Lawyer' : 'Avocat',
          message: isEnglish
              ? 'Upload a document, and I will provide a summary and answer your questions.'
              : 'Charge un document, je te donne un résumé et je réponds à tes questions.',
          accent: AppColors.primary,
          actionLabel: isEnglish ? 'Go to Documents' : 'Aller aux Documents',
          onAction: () => Navigator.pushNamed(context, '/documents'),
        ),
        strategicSuggestion(
          role: isEnglish ? 'Pro Library' : 'Bibliothèque Pro',
          message: isEnglish
              ? 'What recent OHADA text can help me draft a commercial contract?'
              : 'Quel texte OHADA récent peut m\'aider à rédiger un contrat commercial ?',
          accent: const Color(0xFF00796B),
          actionLabel:
              isEnglish ? 'Open Pro Library' : 'Ouvrir la Bibliothèque Pro',
          onAction: () => Navigator.pushNamed(context, '/library'),
        ),
        strategicSuggestion(
          role: isEnglish ? 'HR Manager' : 'DRH',
          message: isEnglish
              ? 'Provide the salary and hiring date, and I will calculate the severance compensation.'
              : 'Comment calculer les indemnités de licenciement ?',
          accent: AppColors.warning,
        ),
      ],
    );
  }

  Widget _buildTypingIndicator(AppLocalizations l10n) {
    return Consumer<ChatProvider>(
      builder: (context, chatProvider, child) {
        if (!chatProvider.isTyping) return const SizedBox.shrink();
        return Padding(
          padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 8.h),
          child: Row(
            children: [
              CircleAvatar(
                radius: 16.r,
                backgroundColor: AppColors.primary,
                child: Icon(
                  Icons.smart_toy,
                  size: 16.sp,
                  color: Colors.white,
                ),
              ),
              SizedBox(width: 8.w),
              Text(
                l10n.dossyIsTyping,
                style: TextStyle(
                  fontSize: 12.sp,
                  color: AppColors.textSecondary,
                  fontStyle: FontStyle.italic,
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildSelectedDocs() {
    if (_selectedDocumentIds.isEmpty) return const SizedBox.shrink();

    return Consumer<DocumentProvider>(
      builder: (context, docProvider, child) {
        final selectedDocs = docProvider.documents
            .where((doc) => _selectedDocumentIds.contains(doc.id))
            .toList();

        return Container(
          margin: EdgeInsets.only(bottom: 8.h),
          padding: EdgeInsets.symmetric(horizontal: 8.w, vertical: 4.h),
          decoration: BoxDecoration(
            color: AppColors.primary.withAlpha((0.1 * 255).round()),
            borderRadius: BorderRadius.circular(8.r),
          ),
          child: Wrap(
            spacing: 4.w,
            children: [
              ...selectedDocs.map(
                (doc) => Chip(
                  label: Text(
                    doc.name.length > 20
                        ? '${doc.name.substring(0, 20)}...'
                        : doc.name,
                    style: TextStyle(fontSize: 11.sp),
                  ),
                  onDeleted: () {
                    setState(() {
                      _selectedDocumentIds.remove(doc.id);
                    });
                  },
                  deleteIcon: Icon(Icons.close, size: 14.sp),
                  backgroundColor: Colors.white,
                  side: const BorderSide(color: AppColors.primary),
                ),
              ),
              if (_selectedDocumentIds.length < docProvider.documents.length)
                ActionChip(
                  label: Text(
                    '+${docProvider.documents.length - _selectedDocumentIds.length}',
                    style: TextStyle(fontSize: 11.sp, color: AppColors.primary),
                  ),
                  onPressed: () {
                    setState(() {
                      _showDocumentSelector = !_showDocumentSelector;
                    });
                  },
                  backgroundColor: Colors.white,
                  side: const BorderSide(color: AppColors.primary),
                ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildDocumentSelector() {
    if (!_showDocumentSelector) return const SizedBox.shrink();

    return Consumer<DocumentProvider>(
      builder: (context, docProvider, child) {
        if (docProvider.documents.isEmpty) {
          return Padding(
            padding: EdgeInsets.only(bottom: 8.h),
            child: Text(
              'Aucun document disponible',
              style: TextStyle(fontSize: 12.sp, color: Colors.grey),
            ),
          );
        }
        return Container(
          margin: EdgeInsets.only(bottom: 8.h),
          constraints: BoxConstraints(maxHeight: 150.h),
          child: ListView(
            shrinkWrap: true,
            children: docProvider.documents.map((doc) {
              final isSelected = _selectedDocumentIds.contains(doc.id);
              return CheckboxListTile(
                dense: true,
                contentPadding: EdgeInsets.symmetric(horizontal: 4.w),
                title: Text(
                  doc.name,
                  style: TextStyle(fontSize: 12.sp),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                subtitle: Text(
                  doc.fileSizeFormatted,
                  style: TextStyle(fontSize: 10.sp, color: Colors.grey),
                ),
                value: isSelected,
                onChanged: (selected) {
                  setState(() {
                    if (selected == true) {
                      _selectedDocumentIds.add(doc.id);
                    } else {
                      _selectedDocumentIds.remove(doc.id);
                    }
                  });
                },
              );
            }).toList(),
          ),
        );
      },
    );
  }

  Widget _buildInputArea(AppLocalizations l10n) {
    return Container(
      padding: EdgeInsets.symmetric(horizontal: 12.w, vertical: 6.h),
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withAlpha((0.05 * 255).round()),
            blurRadius: 10,
            offset: const Offset(0, -2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          _buildSelectedDocs(),
          _buildDocumentSelector(),
          Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _messageController,
                  maxLines: 3,
                  minLines: 1,
                  keyboardType: TextInputType.multiline,
                  textInputAction: TextInputAction.newline,
                  onSubmitted: (value) {
                    if (value.isNotEmpty) _sendMessage();
                  },
                  decoration: InputDecoration(
                    hintText: l10n.askLegalQuestion,
                    prefixIcon: Consumer<DocumentProvider>(
                      builder: (context, docProvider, child) {
                        final hasDocuments = docProvider.documents.isNotEmpty;
                        return IconButton(
                          icon: Icon(
                            Icons.attach_file,
                            color: _selectedDocumentIds.isNotEmpty
                                ? AppColors.primary
                                : (hasDocuments
                                    ? Colors.grey
                                    : Colors.grey.shade300),
                            size: 20.sp,
                          ),
                          onPressed: hasDocuments
                              ? () {
                                  setState(() {
                                    _showDocumentSelector =
                                        !_showDocumentSelector;
                                  });
                                }
                              : null,
                          tooltip: 'Selectionner des documents',
                        );
                      },
                    ),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(24.r),
                      borderSide: BorderSide.none,
                    ),
                    filled: true,
                    fillColor: AppColors.inputBackground,
                    contentPadding: EdgeInsets.symmetric(
                      horizontal: 12.w,
                      vertical: 8.h,
                    ),
                  ),
                ),
              ),
              SizedBox(width: 4.w),
              Consumer<ChatProvider>(
                builder: (context, chatProvider, child) {
                  return CircleAvatar(
                    radius: 20.r,
                    backgroundColor: AppColors.primary,
                    child: IconButton(
                      icon: Icon(
                        chatProvider.isLoading
                            ? Icons.hourglass_empty
                            : Icons.send,
                        color: Colors.white,
                        size: 18.sp,
                      ),
                      onPressed: chatProvider.isLoading ? null : _sendMessage,
                    ),
                  );
                },
              ),
            ],
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return Scaffold(
      resizeToAvoidBottomInset: false,
      appBar: AppBar(
        title: Text(l10n.navChat),
        actions: [
          Stack(
            children: [
              IconButton(
                icon: const Icon(Icons.notifications_outlined),
                tooltip: l10n.pushNotifications,
                onPressed: _openPushInbox,
              ),
              if (_unreadPushCount > 0)
                Positioned(
                  right: 10.w,
                  top: 8.h,
                  child: Container(
                    padding:
                        EdgeInsets.symmetric(horizontal: 4.w, vertical: 1.h),
                    decoration: BoxDecoration(
                      color: Colors.red,
                      borderRadius: BorderRadius.circular(10.r),
                    ),
                    constraints:
                        BoxConstraints(minWidth: 16.w, minHeight: 16.h),
                    child: Text(
                      _unreadPushCount > 99
                          ? '99+'
                          : _unreadPushCount.toString(),
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 9.sp,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                ),
            ],
          ),
          IconButton(
            icon: const Icon(Icons.add_comment_outlined),
            tooltip: 'Nouveau chat',
            onPressed: () {
              Provider.of<ChatProvider>(context, listen: false).clearChat();
              _messageController.clear();
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('Nouveau chat cree'),
                  duration: Duration(seconds: 2),
                ),
              );
            },
          ),
          IconButton(
            icon: const Icon(Icons.history),
            tooltip: l10n.chatHistory,
            onPressed: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => const ConversationsListScreen(),
                ),
              );
            },
          ),
          IconButton(
            icon: const Icon(Icons.delete_outline),
            tooltip: l10n.deleteConversation,
            onPressed: () {
              Provider.of<ChatProvider>(context, listen: false).clearChat();
            },
          ),
        ],
      ),
      body: SafeArea(
        bottom: false,
        child: Consumer<AuthProvider>(
          builder: (context, authProvider, child) {
            if (authProvider.needsProfileCompletion) {
              return _buildProfileGate(l10n, authProvider);
            }

            return Column(
              children: [
                _buildStatusBar(l10n, authProvider),
                Expanded(
                  child: Column(
                    children: [
                      Expanded(child: _buildMessageList()),
                      _buildTypingIndicator(l10n),
                    ],
                  ),
                ),
                _buildInputArea(l10n),
              ],
            );
          },
        ),
      ),
    );
  }
}
