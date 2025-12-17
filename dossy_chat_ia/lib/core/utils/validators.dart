/// Form validators
class Validators {
  /// Validate email format
  static String? email(String? value) {
    if (value == null || value.isEmpty) {
      return 'L\'email est requis';
    }
    
    final emailRegex = RegExp(
      r'^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$',
    );
    
    if (!emailRegex.hasMatch(value)) {
      return 'Email invalide';
    }
    
    return null;
  }

  /// Validate password
  static String? password(String? value, {int minLength = 8}) {
    if (value == null || value.isEmpty) {
      return 'Le mot de passe est requis';
    }
    
    if (value.length < minLength) {
      return 'Minimum $minLength caractères';
    }
    
    // Check for at least one letter and one number
    if (!RegExp(r'[A-Za-z]').hasMatch(value) || !RegExp(r'[0-9]').hasMatch(value)) {
      return 'Doit contenir lettres et chiffres';
    }
    
    return null;
  }

  /// Validate phone number (African format)
  static String? phone(String? value) {
    if (value == null || value.isEmpty) {
      return null; // Phone is optional
    }
    
    // Remove spaces and common separators
    final cleaned = value.replaceAll(RegExp(r'[\s\-\(\)]'), '');
    
    // Check African phone formats
    final africaRegex = RegExp(
      r'^\+?(225|221|237|223|226|227|228|229|224|243|242|241|235|236)\d{8,10}$',
    );
    
    if (!africaRegex.hasMatch(cleaned)) {
      return 'Numéro invalide';
    }
    
    return null;
  }

  /// Validate required field
  static String? required(String? value, {String? fieldName}) {
    if (value == null || value.trim().isEmpty) {
      return '${fieldName ?? 'Ce champ'} est requis';
    }
    return null;
  }

  /// Validate minimum length
  static String? minLength(String? value, int min, {String? fieldName}) {
    if (value == null || value.isEmpty) return null;
    
    if (value.length < min) {
      return '${fieldName ?? 'Ce champ'} doit contenir au moins $min caractères';
    }
    return null;
  }

  /// Validate maximum length
  static String? maxLength(String? value, int max, {String? fieldName}) {
    if (value == null || value.isEmpty) return null;
    
    if (value.length > max) {
      return '${fieldName ?? 'Ce champ'} ne doit pas dépasser $max caractères';
    }
    return null;
  }

  /// Validate number
  static String? number(String? value, {String? fieldName}) {
    if (value == null || value.isEmpty) return null;
    
    if (double.tryParse(value) == null) {
      return '${fieldName ?? 'Ce champ'} doit être un nombre';
    }
    return null;
  }

  /// Validate URL
  static String? url(String? value) {
    if (value == null || value.isEmpty) return null;
    
    final urlRegex = RegExp(
      r'^https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&//=]*)$',
    );
    
    if (!urlRegex.hasMatch(value)) {
      return 'URL invalide';
    }
    return null;
  }

  /// Validate confirmation field (e.g., confirm password)
  static String? confirmation(String? value, String? originalValue, {String? fieldName}) {
    if (value == null || value.isEmpty) {
      return 'Veuillez confirmer ${fieldName ?? 'le champ'}';
    }
    
    if (value != originalValue) {
      return 'Les ${fieldName ?? 'champs'} ne correspondent pas';
    }
    return null;
  }

  /// Validate name (letters only, may include spaces and hyphens)
  static String? name(String? value, {String? fieldName}) {
    if (value == null || value.trim().isEmpty) {
      return '${fieldName ?? 'Le nom'} est requis';
    }
    
    final nameRegex = RegExp(r'^[a-zA-ZÀ-ÿ\s\-]+$');
    if (!nameRegex.hasMatch(value)) {
      return '${fieldName ?? 'Le nom'} contient des caractères invalides';
    }
    
    if (value.trim().length < 2) {
      return '${fieldName ?? 'Le nom'} est trop court';
    }
    
    return null;
  }

  /// Validate referral code format
  static String? referralCode(String? value) {
    if (value == null || value.isEmpty) return null;
    
    final codeRegex = RegExp(r'^[A-Z0-9]{6,10}$');
    if (!codeRegex.hasMatch(value.toUpperCase())) {
      return 'Code de parrainage invalide';
    }
    return null;
  }

  /// Combine multiple validators
  static String? combine(String? value, List<String? Function(String?)> validators) {
    for (var validator in validators) {
      final error = validator(value);
      if (error != null) return error;
    }
    return null;
  }
}
